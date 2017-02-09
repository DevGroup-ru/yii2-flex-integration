<?php

namespace DevGroup\FlexIntegration\models;

use DevGroup\EntitySearch\base\SearchQuery;
use DevGroup\FlexIntegration\abstractEntity\preProcessors\RelationFinder;
use DevGroup\FlexIntegration\base\AbstractEntitiesPostProcessor;
use DevGroup\FlexIntegration\base\AbstractEntity;
use DevGroup\FlexIntegration\base\AbstractEntityCollection;
use DevGroup\FlexIntegration\base\DocumentConfiguration;
use DevGroup\FlexIntegration\errors\BaseException;
use DevGroup\FlexIntegration\format\FormatMapper;
use DevGroup\FlexIntegration\format\FormatReducer;
use DotPlant\Store\models\goods\Goods;
use DotPlant\Store\models\warehouse\GoodsWarehouse;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class ImportTask extends BaseTask
{
    public $taskType = self::TASK_TYPE_IMPORT;
    /** @var array Entities declaration from schema, will be available after mapDoc */
    public $entitiesDecl = [];

    /** @var array[]  */
    public $preProcessors = [];

    /**
     * @var array Dependency counter is used for optimizing memory during relations linking.
     *            The whole logic:
     *            - Create dependency array: <"$entityKey:$identificationAttribute", counter>
     *            - Counter is number of records that are depending on this record
     *            - Once we linked any model to related - decrease the counter
     *            - Once counter is zero - free memory
     *            - Prioritize entities in collection by total number of dependent records DESC
     *            - Related records with initial zero dependencies are not stored in RAM at all after
     *              reduceCollection stage
     *
     *            And all the dependency array creation should be done on map input data(mapDoc) stage,
     *            right after all columns for record are mapped(before pre processors are executed).
     *
     *            What's the trick:
     *            Dictionary will cost less then storing all operated related records in RAM in most cases.
     * @todo Concept should be remade.
     *       Dependency array structure should be:
     *       entityKey => [
     *          "~~__$identificationAttribute" => counter,
     *       ]
     *       After preProcessing stage we should also replace identificationAttribute key with correct ID.
     *       Example situation:
     *       2 document uploads - categories and product.
     *       In categories document there's a changed name in one category.
     *       Products document is linking to NEW category name.
     *       On RelationFinder stage we have the following algorithm:
     *       - first check modelsCache for existing in RAM model instances(find by identification attribute)
     *       - models that are not in cache - retrieve from db the way it is implemented now
     *
     *       PROBLEM:
     *       ========================
     *       We don't know on which attr model category should be indexed in deps chain before we done pre processing
     *       for products.
     *
     */
    public $dependencyCounter = [];

    /**
     * @param array $config
     *
     */
    public function run(array $config = [])
    {
        Yii::configure($this, $config);
        /** @var AbstractEntityCollection[] $collections */
        $collections = [];
        $this->preProcessors = [];
        $this->entitiesDecl = [];
        $this->dependencyCounter = [];

        foreach ($this->documents as $documentIndex => $doc) {
            /** @var AbstractEntity[] $entities */
            $entities = $this->mapDoc($doc, $documentIndex);

            // reduce here
            $this->reduceDoc($doc, $entities, $collections);
            foreach ($doc->entitiesPreProcessors as $entityKey => $processorsByDocument) {
                if (isset($this->preProcessors[$entityKey]) === false) {
                    $this->preProcessors[$entityKey] = [];
                }
                /** @var AbstractEntitiesPostProcessor[] $initializedProcessors */
                $initializedProcessors = [];
                foreach ($processorsByDocument as $processorIndex => $processorConfig) {
                    $initializedProcessors[$processorIndex] = Yii::createObject($processorConfig);
                }
                $this->preProcessors[$entityKey][$documentIndex] = $initializedProcessors;
            }
        }
        /**
         * preProcessors array structure
         * entityKey
         *      => documentIndex => processors[]
         */

        $collections = $this->prioritizeCollections($collections);


        $prioritizedCollectionKeys = array_keys($collections);

        //! @todo this state can be dumped and saved to json

        // Go through prioritized collections
        foreach ($prioritizedCollectionKeys as $entityKey) {
            $processorsByDocument = isset($this->preProcessors[$entityKey]) ? $this->preProcessors[$entityKey] : [];
            /** @var array $processorsByDocument */
            if (isset($collections[$entityKey]) === false) {
                //! @todo Throw exception here as this can not be in real world, only with buggy hands
                continue;
            }
            $entitiesByDocument = [];
            $neededDocuments = array_keys($processorsByDocument);

            foreach ($collections[$entityKey]->entities as &$entity) {
                /** @var AbstractEntity $entity */
                if (in_array($entity->sourceId, $neededDocuments)) {
                    if (isset($entitiesByDocument[$entity->sourceId]) === false) {
                        $entitiesByDocument[$entity->sourceId] = [];
                    }
                    $entitiesByDocument[$entity->sourceId][] = &$entity;
                }
            }
            unset($entity);
            /** @var array $processorsByDocument */
            foreach ($processorsByDocument as $processors) {
                foreach ($entitiesByDocument as $documentIndex => &$entities) {
                    foreach ($processors as $processor) {
                        /** @var AbstractEntitiesPostProcessor $processor */
                        $processor->processEntities(
                            $entities,
                            $entityKey,
                            $this
                        );
                    }
                }
                unset($entities);
            }


            //! @todo Add map to entity, insert&save here!
            //! @todo After we made all our great things - collection can be unset for freeing memory :-D

            $this->reduceCollection($collections[$entityKey]);

            gc_collect_cycles();
            if (function_exists('gc_mem_caches')) {
                gc_mem_caches();
            }
        }
    }

    public function reduceCollection(AbstractEntityCollection $collection)
    {
        $collectionClassName = $this->entitiesDecl[$collection->key]['class'];
        /** @var SearchQuery $query */
        $query = $this->entitySearch()
            ->search($collectionClassName);

        /** @var string[] $collectionPk2Index <int,string> pairs */
        $collectionPk2Index = [];
        $searchBy = [];
        $foundCollectionIndexes = [];

        foreach ($collection->entities as $index => $abstractEntity) {
            $id = (int) $abstractEntity->pk;
            if ($id > 0) {
                $collectionPk2Index[$id] = $index;
            }

            if (count($abstractEntity->searchBy) > 0) {
                foreach ($abstractEntity->searchBy as $key=>$values) {
                    if (isset($searchBy[$key]) === false) {
                        $searchBy[$key] = [];
                    }
                    foreach ((array) $values as $val) {
                        $searchBy[$key][$val] = $index;
                    }
                }
            }
        }
        if (count($collectionPk2Index) > 0) {
            $query
                ->mainEntityAttributes(['id' => array_keys($collectionPk2Index)]);
        }

        $dependantRelationFinders = [];
        foreach ($this->entitiesDecl as $collectionKey => $item) {
            if (isset($item['depends'][$collectionClassName])) {
                $dependantRelationFinders[$collectionKey] = $item['depends'][$collectionClassName];
            }
        }


        $activeQuery = $query
            ->query()
            ->query;

        // add with
        $thisCollectionDepends = $this->entitiesDecl[$collection->key]['depends'];

        $with = [];
        foreach ($thisCollectionDepends as $bySources) {
            foreach ($bySources as $item) {
                $with[$item['relationName']] = 1;
            }
        }
        $activeQuery->with(array_keys($with));

        foreach ($searchBy as $key => $values) {
            $activeQuery->orWhere([
                $key => array_keys($values)
            ]);
        }

        $finalResult = true;
        // find old records
        foreach ($activeQuery->each($this->batchSize) as $model) {
            $id = (int) $model->id;
            /** @var ActiveRecord $model */
            if (!isset($collectionPk2Index[$id])) {
                // this item was found not by ID - it was found by searchby
                $collectionIndex = -1;
                $caseInsensitive = false;
                foreach ($searchBy as $attribute => $values) {
                    $attributeValue = $model->getAttribute($attribute);
                    $lowerCased = strtolower($attributeValue);

                    foreach ($values as $key => $index) {
                        if ($caseInsensitive === true) {
                            if (strtolower($key) === $lowerCased) {
                                $collectionIndex = $index;
                                break;
                            }
                        } elseif ($key === $attributeValue) {
                            $collectionIndex = $index;
                            break;
                        }
                    }
                    if ($collectionIndex !== -1) {
                        break;
                    }
                }
                if ($collectionIndex === -1) {
                    continue;
                }
            } else {
                $collectionIndex = $collectionPk2Index[$id];
            }
            $abstractEntity = $collection->entities[$collectionIndex];

            $this->fillModelFromAbstractEntity($model, $abstractEntity);
            $result = $model->save();
            $this->modelAfterSave($model, $abstractEntity);
            $finalResult = $finalResult && $result;
            if ($result) {
                // only saved models can be linked
                $finalResult = $finalResult && $this->fillModelRelations($model, $abstractEntity);
            }
            //! @todo Add option to skip or throw errors

            // put model to model cache
            $this->putToModelCache($model, $dependantRelationFinders);
            $foundCollectionIndexes[] = $collectionIndex;
            unset($model);
        }

        // create new records
        foreach ($collection->entities as $index => $abstractEntity) {
            if (in_array($index, $foundCollectionIndexes)) {
                continue;
            }
            $model = new $collectionClassName;
            $this->fillModelFromAbstractEntity($model, $abstractEntity);
            $model->id = null;
            $result = $model->save();
            $this->modelAfterSave($model, $abstractEntity);
            $finalResult = $finalResult && $result;
            if ($result) {
                // only saved models can be linked
                $finalResult = $finalResult && $this->fillModelRelations($model, $abstractEntity);
            }
            $this->putToModelCache($model, $dependantRelationFinders);
            unset($model);
        }

        return $finalResult;
    }

    protected function putToModelCache(ActiveRecord &$model, $dependantRelationFinders)
    {
        $modelClassName = $model::className();
        if (isset($this->dependencyCounter[$modelClassName]) === false) {
            return;
        }

        foreach ($dependantRelationFinders as $collectionKey => $bySources) {
            foreach ($bySources as $sourceId => $config) {
                $attributeName = $config['findByAttribute'];
                if (isset($this->dependencyCounter[$modelClassName][$attributeName]) === false) {
                    continue;
                }
                $attributeValue = $model->getAttribute($attributeName);
                if (isset($this->dependencyCounter[$modelClassName][$attributeName][$attributeValue]) === true) {
                    $this->dependencyCounter[$modelClassName][$attributeName][$attributeValue]['model'] = &$model;
                }
            }
        }
    }

    public function modelAfterSave(ActiveRecord &$model, AbstractEntity $entity)
    {
        if ($model instanceof Goods) {
            foreach ($entity->prices as $warehouse_id => $value) {
                $price = [
                    'wholesale_price' => $value,
                    'seller_price' => $value,
                    'retail_price' => $value,
                    'warehouse_id' => $warehouse_id,
                    'is_allowed' => true,
                    'currency_iso_code' => 'USD',
                ];
                $wh =  new GoodsWarehouse();
                $wh->setAttributes($price);
                $wh->goods_id = $model->id;
                $wh->save();
            }
        }


//        $model->save();
    }

    public function fillModelFromAbstractEntity(ActiveRecord &$model, AbstractEntity $entity)
    {
        $model->loadDefaultValues();

        $model->setAttributes(
            $entity->attributes,
            false
        );
        if ($model->hasAttribute('slug') && empty($model->slug)) {
            $model->slug = \yii\helpers\Inflector::slug($entity->attributes['name']);
        }
        if ($model->hasMethod('getDefaultTranslation')) {
            $model->translate(1)->setAttributes(
                $entity->attributes,
                false
            );
            if ($model->translate(1)->hasAttribute('slug') && empty($model->translate(1)->slug)) {
                $model->translate(1)->slug = \yii\helpers\Inflector::slug($entity->attributes['name']);
            }
        }
    }

    public function fillModelRelations(ActiveRecord &$model, AbstractEntity $entity)
    {
        foreach ($entity->relatesTo as $relationName => $newRelatedModels) {
            $allRelated = $model->$relationName;
            $idsOk = [];

            $ids = array_keys($newRelatedModels);

            foreach ($allRelated as $related) {
                /** @var ActiveRecord $related */
                $id = (int) $related->id;
                if (in_array($id, $ids) === false) {
                    $model->unlink($relationName, $related, true);
                } else {
                    $idsOk[] = $id;
                }
            }

            foreach ($newRelatedModels as $related) {
                $id = (int) $related->id;
                if (in_array($id, $idsOk)) {
                    continue;
                }
                $model->link($relationName, $related);
                $idsOk[] = $id;
            }
        }
        unset($entity->relatesTo);
        return true;
    }

    /**
     * @return \DevGroup\EntitySearch\base\BaseSearch|object
     */
    protected function entitySearch()
    {
        return Yii::$app->get('search');
    }

    /**
     * @param \DevGroup\FlexIntegration\base\DocumentConfiguration $doc
     * @return \DevGroup\FlexIntegration\base\AbstractEntity[]
     */
    public function mapDoc(DocumentConfiguration $doc, $sourceId)
    {
        /** @var FormatMapper $formatMapper */
        $formatMapper = Yii::createObject($doc->formatMapper);
        $formatMapper->task = &$this;

        $result = $formatMapper->mapInputDocument($this, $doc->importFilename(), $sourceId);

        $this->entitiesDecl = ArrayHelper::merge($this->entitiesDecl, $formatMapper->entitiesDecl);
        return $result;
    }

    /**
     * @param \DevGroup\FlexIntegration\base\DocumentConfiguration $doc
     * @param AbstractEntity[]                                     $entities
     * @param AbstractEntityCollection[]                           $collections
     * @return AbstractEntityCollection[]
     */
    public function reduceDoc(DocumentConfiguration $doc, array $entities, array &$collections)
    {
        /** @var FormatReducer $formatReducer */
        $formatReducer = Yii::createObject($doc->formatReducer);
        return $formatReducer->reduceToCollections($entities, $collections, $this);
    }

    /**
     * @param AbstractEntityCollection[]                           $collections
     * @return AbstractEntityCollection[]
     */
    public function prioritizeCollections(array $collections)
    {
        $entitiesDict = [];
        foreach ($this->entitiesDecl as $key => $decl) {
            $entitiesDict[$key] = $decl['class'];
        }
        $entitiesNormalized = [];

        foreach ($this->entitiesDecl as $key => $decl) {
            $deps = [];
            $classNameDepends = array_keys($decl['depends']);
            foreach ($classNameDepends as $className) {
                $dependencyKey = array_search($className, $entitiesDict, true);
                if ($dependencyKey !== false) {
                    $deps[] = $dependencyKey;
                }
            }
            $entitiesNormalized[$key] = $deps;
        }

        $prioritized = [];
        // first fill depends property of collections
        $resolved = [];
        $unresolved = [];
        $keys = array_keys($entitiesNormalized);

        foreach ($keys as $entityKey) {
            $this->resolveDependencies($entityKey, $entitiesNormalized, $resolved, $unresolved);
        }
        // as the result - we now have correct order in $resolved
        if (count($unresolved) > 0) {
            throw new BaseException('Unresolved dependencies found: ' . implode(',', $unresolved));
        }

        // now combine final collections list
        foreach ($resolved as $key) {
            $prioritized[$key] = $collections[$key];
        }
        return $prioritized;
    }

    protected function resolveDependencies($item, array $items, array &$resolved, array &$unresolved)
    {
        $unresolved[] = $item;
        foreach ($items[$item] as $dep) {
            if (!in_array($dep, $resolved)) {
                if (!in_array($dep, $unresolved)) {
                    array_push($unresolved, $dep);
                    $this->resolveDependencies($dep, $items, $resolved, $unresolved);
                } else {
                    throw new \RuntimeException("Circular dependency: $item -> $dep");
                }
            }
        }
        // Add $item to $resolved if it's not already there
        if (!in_array($item, $resolved)) {
            array_push($resolved, $item);
        }
        // Remove all occurrences of $item in $unresolved
        while (($index = array_search($item, $unresolved)) !== false) {
            unset($unresolved[$index]);
        }

    }
}

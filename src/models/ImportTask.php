<?php

namespace DevGroup\FlexIntegration\models;

use DevGroup\FlexIntegration\base\AbstractEntitiesPostProcessor;
use DevGroup\FlexIntegration\base\AbstractEntity;
use DevGroup\FlexIntegration\base\AbstractEntityCollection;
use DevGroup\FlexIntegration\base\DocumentConfiguration;
use DevGroup\FlexIntegration\errors\BaseException;
use DevGroup\FlexIntegration\format\FormatMapper;
use DevGroup\FlexIntegration\format\FormatReducer;
use Yii;
use yii\helpers\ArrayHelper;

class ImportTask extends BaseTask
{
    public $taskType = self::TASK_TYPE_IMPORT;
    /** @var array Entities declaration from schema, will be available after mapDoc */
    protected $entitiesDecl = [];

    /** @var array[]  */
    protected $preProcessors = [];

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
            if (isset($this->preProcessors[$entityKey]) === false) {
                continue; // that's normal situation, all's ok
            }
            $processorsByDocument = $this->preProcessors[$entityKey];
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
                            $this->entitiesDecl
                        );
                    }
                }
                unset($entities);
            }

            //! @todo Add map to entity, insert&save here!
            //! @todo After we made all our great things - collection can be unset for freeing memory :-D
        }

        codecept_debug($collections);

//
//        foreach ($collections as $entityKey => $collection) {
//            /** @var AbstractEntityCollection $collection*/
//            foreach ($collection->entities as &$entity) {
//                /** @var $processor AbstractEntitiesPostProcessor */
//                $processor->processEntities($entities, $collectionKey, $entitiesDecl);
//            }
//        }
    }

    /**
     * @param \DevGroup\FlexIntegration\base\DocumentConfiguration $doc
     * @return \DevGroup\FlexIntegration\base\AbstractEntity[]
     */
    public function mapDoc(DocumentConfiguration $doc, $sourceId)
    {
        /** @var FormatMapper $formatMapper */
        $formatMapper = Yii::createObject($doc->formatMapper);

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
        return $formatReducer->reduceToCollections($entities, $collections, $this->entitiesDecl);
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
            foreach ($decl['depends'] as $className) {
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

    public function combineSearchQueries(array $collections)
    {

    }
}

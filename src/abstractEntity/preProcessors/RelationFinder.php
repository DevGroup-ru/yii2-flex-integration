<?php

namespace DevGroup\FlexIntegration\abstractEntity\preProcessors;

use DevGroup\EntitySearch\response\ResultResponse;
use DevGroup\FlexIntegration\base\AbstractEntitiesPostProcessor;
use DevGroup\FlexIntegration\errors\RelationNotFound;
use DevGroup\FlexIntegration\models\ImportTask;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class RelationFinder extends AbstractEntitiesPostProcessor
{
    public $relationName = '';
    public $findByAttribute = '';
    /**
     * @var array
     */
    public $mainEntityAttributes = [];

    /**
     * @var array
     */
    public $relationAttributes = [];

    public $notFoundBehavior = 'skip';
    const NOT_FOUND_SKIP = 'skip';
    const NOT_FOUND_ERROR = 'error';

    public static $relationCache = [];

    /**
     * Helper function. Returns class name of relation's target model.
     * The result is cached inside static variable
     * Example:
     * product <-> product_categories <-> category
     * Calling `relationTarget(product::class, 'categories')` will return `category::class`
     *
     * @param string $modelClassName What model relates
     * @param string $relationName The name of that relation.
     *
     * @return string
     */
    public static function relationTarget($modelClassName, $relationName)
    {
        if (isset(static::$relationCache[$modelClassName]) === false) {
            static::$relationCache[$modelClassName] = [];
        }
        if (isset(static::$relationCache[$modelClassName][$relationName]) === false) {
            /** @var ActiveRecord $sampleModel */
            $sampleModel = new $modelClassName;
            /** @var ActiveQuery $relationQuery */
            $relationQuery = $sampleModel->getRelation($relationName);
            static::$relationCache[$modelClassName][$relationName] = $relationQuery->modelClass;
        }
        return static::$relationCache[$modelClassName][$relationName];
    }

    public function processEntities(array &$entities, $collectionKey = '', ImportTask &$task)
    {
        $entitiesDecl = &$task->entitiesDecl;
        $uniqueValues = [];

        $className = $entitiesDecl[$collectionKey]['class'];

        /** @var ActiveRecord $modelClass */
        $modelClass = static::relationTarget($className, $this->relationName);

        // make dictionary [id => attribute]
        $dictionary = [];

        foreach ($entities as $entity) {
            if ($entity->modelKey === $collectionKey && isset($entity->relatesTo[$this->relationName])) {
                $values = (array) $entity->relatesTo[$this->relationName];

                foreach ($values as $val) {
                    if (empty($val)) {
                        continue;
                    }
                    if (isset($task->dependencyCounter[$modelClass][$this->findByAttribute][$val]['model'])) {
                        // fill dictionary with already known values
                        $dictionary[$val] = &$task->dependencyCounter[$modelClass][$this->findByAttribute][$val]['model'];
                    } else {

                        $uniqueValues[] = $val;
                    }
                }
            }
        }
        $uniqueValues = array_unique($uniqueValues);


        //! @todo ADD: If model supports tag cache - then cache

        $q = $this->entitySearch()->search($modelClass)
            ->limit(null);
        $this->mainEntityAttributes[$this->findByAttribute] = $uniqueValues;
        $q->mainEntityAttributes($this->mainEntityAttributes);


        /** @var ResultResponse $result */
        $result = $q->all();

        $result = ArrayHelper::map(
            $result->entities,
            $this->findByAttribute,
            function (ActiveRecord &$row) {
                // currently we support ONLY non-composite numeric keys, lol
                return $row;
            }
        );
        /** @var array $result */

        $dictionary = ArrayHelper::merge($dictionary, $result);


        // bind attributes to values
        $notFound = [];

        foreach ($entities as $entity) {
            if ($entity->modelKey === $collectionKey && isset($entity->relatesTo[$this->relationName])) {
                $newValues = [];
                $values = (array) $entity->relatesTo[$this->relationName];
                foreach ($values as $value2search) {
                    if (isset($dictionary[$value2search])) {
                        $related = &$dictionary[$value2search];
                        $newValues[(int) $related->id] = &$related;
                    } else {
                        $notFound[] = $value2search;
                        if ($this->notFoundBehavior === self::NOT_FOUND_ERROR) {
                            throw new RelationNotFound('', [
                                'valueSearched' => $value2search,
                                'attributeSearched' => $this->findByAttribute,
                                'mainEntityAttributes' => $this->mainEntityAttributes,
                                'relationAttributes' => $this->relationAttributes,
                                'model2search' => $modelClass,
                            ]);
                        }
                    }
                }
                // replace with new values
                $entity->relatesTo[$this->relationName] = $newValues;
            }
        }
        $notFound = array_unique($notFound);
        //! @todo Add log not found here somewhere - for SKIP behavior
    }

    /**
     * @return \DevGroup\EntitySearch\base\BaseSearch|object
     */
    protected function entitySearch()
    {
        return Yii::$app->get('search');
    }
}

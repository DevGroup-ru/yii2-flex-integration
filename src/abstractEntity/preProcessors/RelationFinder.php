<?php

namespace DevGroup\FlexIntegration\abstractEntity\preProcessors;

use DevGroup\EntitySearch\base\BaseSearch;
use DevGroup\EntitySearch\base\SearchResponse;
use DevGroup\EntitySearch\response\ResultResponse;
use DevGroup\FlexIntegration\base\AbstractEntitiesPostProcessor;
use DevGroup\FlexIntegration\errors\RelationNotFound;
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

    public function processEntities(array &$entities, $collectionKey = '', array $entitiesDecl)
    {
        $uniqueValues = [];

        foreach ($entities as $entity) {
            if ($entity->modelKey === $collectionKey && isset($entity->relatesTo[$this->relationName])) {
                $values = (array) $entity->relatesTo[$this->relationName];
                foreach ($values as $val) {
                    $uniqueValues[] = $val;
                }
            }
        }
        $uniqueValues = array_unique($uniqueValues);

        // make dictionary [id => attribute]
        $className = $entitiesDecl[$collectionKey]['class'];
        $sampleModel = new $className;
        /** @var ActiveQuery $relationQuery */
        $relationQuery = call_user_func([$sampleModel, 'get' . ucfirst($this->relationName)]);
        /** @var ActiveRecord $modelClass */
        $modelClass = $relationQuery->modelClass;

        //! @todo ADD: If model supports tag cache - then cache

        $q = $this->entitySearch()->search($modelClass)
            ->limit(null);
        $this->mainEntityAttributes[$this->findByAttribute] = $uniqueValues;
        $q->mainEntityAttributes($this->mainEntityAttributes);


        /** @var ResultResponse $dictionary */
        $dictionary = $q->allArray();

        $dictionary = ArrayHelper::map(
            $dictionary->entities,
            $this->findByAttribute,
            function ($row) {
                // currently we support ONLY non-composite numeric keys, lol
                return (int) $row['id'];
            }
        );


        // bind attributes to values
        $notFound = [];

        foreach ($entities as $entity) {
            if ($entity->modelKey === $collectionKey && isset($entity->relatesTo[$this->relationName])) {
                $newValues = [];
                $values = (array) $entity->relatesTo[$this->relationName];
                foreach ($values as $value2search) {
                    if (isset($dictionary[$value2search])) {
                        $newValues[] = $dictionary[$value2search];
                    } else {
                        $notFound[] = $value2search;
                        if ($this->notFoundBehavior === self::NOT_FOUND_ERROR) {
                            throw new RelationNotFound('', [
                                'valueSearched' => $value2search,
                                'attributeSearched' => $this->findByAttribute,
                                'conditions' => $this->andWhere,
                                'model2search' => $modelClass,
                            ]);
                        }
                    }
                }
                // replace with new values
                $entity->relatesTo[$this->relationName] = $newValues;
            }
        }
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

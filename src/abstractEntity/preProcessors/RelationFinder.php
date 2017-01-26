<?php

namespace DevGroup\FlexIntegration\abstractEntity\preProcessors;

use DevGroup\FlexIntegration\base\AbstractEntitiesPostProcessor;
use DevGroup\FlexIntegration\errors\RelationNotFound;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

class RelationFinder extends AbstractEntitiesPostProcessor
{
    public $relationName = '';
    public $andWhere = [];
    public $findByAttribute = '';
    public $joinWith = [];

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
        $values2find = ['or', [$this->findByAttribute => $uniqueValues]];


        // make dictionary [id => attribute]
        $className = $entitiesDecl[$collectionKey]['class'];
        $sampleModel = new $className;
        /** @var ActiveQuery $relationQuery */
        $relationQuery = call_user_func([$sampleModel, 'get' . ucfirst($this->relationName)]);
        /** @var ActiveRecord $modelClass */
        $modelClass = $relationQuery->modelClass;

        //! @todo ADD: If model supports tag cache - then cache
        $q = $modelClass::find()
            ->select(['id', $this->findByAttribute])
            ->where($values2find);
        if (count($this->andWhere) > 0) {
            $q->andWhere($this->andWhere);
        }
        if (count($this->joinWith) > 0) {
            $q->joinWith($this->joinWith);
        }

        $dictionary = $q->asArray(true)->all();

        $dictionary = ArrayHelper::map(
            $dictionary,
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
}

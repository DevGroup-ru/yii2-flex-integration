<?php

namespace DevGroup\FlexIntegration\format\reducers;

use DevGroup\FlexIntegration\abstractEntity\preProcessors\RelationFinder;
use DevGroup\FlexIntegration\base\AbstractEntity;
use DevGroup\FlexIntegration\base\AbstractEntityCollection;
use DevGroup\FlexIntegration\format\FormatReducer;
use DevGroup\FlexIntegration\models\ImportTask;

class DefaultReducer extends FormatReducer
{

    /**
     * @param AbstractEntity[]           $entities
     * @param AbstractEntityCollection[] $collections
     * @param ImportTask                 $task
     *
     * @return AbstractEntityCollection[]
     */
    public function reduceToCollections($entities, array &$collections, ImportTask &$task)
    {
        $entitiesDecl = &$task->entitiesDecl;
        $dependencyCounter = &$task->dependencyCounter;

        foreach ($entities as $abstractEntity) {
            $collectionKey = $abstractEntity->modelKey;

            if (array_key_exists($collectionKey, $collections) === false) {
                $collection = new AbstractEntityCollection();
                $collection->key = $collectionKey;

                $collections[$collectionKey] = $collection;
            }
            // fill relation dependency counter
            foreach ($abstractEntity->relatesTo as $relationName => $identificationAttributesValues) {
                $identificationAttributesValues = (array) $identificationAttributesValues;
                $target = RelationFinder::relationTarget($entitiesDecl[$collectionKey]['class'], $relationName);
                if (isset($dependencyCounter[$target]) === false) {
                    $dependencyCounter[$target] = [];
                }

                $relationFinder = $entitiesDecl[$collectionKey]['depends'][$target][$abstractEntity->sourceId];
                $findByAttribute = $relationFinder['findByAttribute'];

                foreach ($identificationAttributesValues as $value) {
                    if (empty($value)) {
                        continue;
                    }

                    if (isset($dependencyCounter[$target][$findByAttribute]) === false) {
                        $dependencyCounter[$target][$findByAttribute] = [];
                    }
                    if (isset($dependencyCounter[$target][$findByAttribute][$value]) === false) {
                        $dependencyCounter[$target][$findByAttribute][$value] = [
                            'dependencyCounter' => 1,
                            'model' => null,
                        ];
                    } else {
                        $dependencyCounter[$target][$findByAttribute][$value]['dependencyCounter']++;
                    }
                }
            }

            $collections[$collectionKey]->put($abstractEntity, $this->onDuplicate);

        }

        return $collections;
    }

    /**
     * @param AbstractEntity $entity
     * @param AbstractEntityCollection[]                           $collections
     */
    protected function ensureCollection(AbstractEntity $entity, array &$collections)
    {
        if (array_key_exists($entity->modelKey, $collections)) {
            return;
        }

        $collection = new AbstractEntityCollection();
        $collection->key = $entity->modelKey;

        $collections[$entity->modelKey] = $collection;
    }
}

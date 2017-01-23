<?php

namespace DevGroup\FlexIntegration\format\reducers;

use DevGroup\FlexIntegration\base\AbstractEntity;
use DevGroup\FlexIntegration\base\AbstractEntityCollection;
use DevGroup\FlexIntegration\format\FormatReducer;

class DefaultReducer extends FormatReducer
{

    /**
     * @param AbstractEntity[]           $entities
     * @param AbstractEntityCollection[] $collections
     * @param array                      $entitiesDecl
     *
     * @return AbstractEntityCollection[]
     */
    public function reduceToCollections($entities, array &$collections, array $entitiesDecl)
    {
        foreach ($entities as $item) {
            $collectionKey = $item->modelKey;

            if (array_key_exists($collectionKey, $collections) === false) {
                $collection = new AbstractEntityCollection();
                $collection->key = $collectionKey;

                $collections[$collectionKey] = $collection;
            }

            $collections[$collectionKey]->put($item, $this->onDuplicate);

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

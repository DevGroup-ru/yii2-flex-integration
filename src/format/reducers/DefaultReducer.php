<?php

namespace DevGroup\FlexIntegration\format\reducers;

use DevGroup\FlexIntegration\base\AbstractEntity;
use DevGroup\FlexIntegration\base\AbstractEntityCollection;
use DevGroup\FlexIntegration\format\FormatReducer;

class DefaultReducer extends FormatReducer
{

    /**
     * @param AbstractEntity[] $entities
     * @param AbstractEntityCollection[]                           $collections
     *
     * @return AbstractEntityCollection[]
     */
    public function reduceToCollections($entities, array &$collections)
    {
        foreach ($entities as $entity) {
            $this->ensureCollection($entity, $collections);

            $collections[$entity->modelKey]->put($entity, $this->onDuplicate);
        }
        return $collections;
    }

    /**
     * @param AbstractEntity $entity
     * @param AbstractEntityCollection[]                           $collections
     */
    protected function ensureCollection(AbstractEntity $entity, array &$collections)
    {
        if (isset($collections[$entity->modelKey])) {
            return;
        }
        $collection = new AbstractEntityCollection();
        $collection->key = $entity->modelKey;

        $collections[$entity->modelKey] = $collection;
    }
}

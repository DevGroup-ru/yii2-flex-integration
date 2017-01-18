<?php

namespace DevGroup\FlexIntegration\base;

use DevGroup\FlexIntegration\errors\DuplicateEntity;
use DevGroup\FlexIntegration\format\FormatReducer;

class AbstractEntityCollection
{
    /** @var AbstractEntity[] indexed by document scope id */
    public $entities = [];

    /** @var string Key for identifying dependencies */
    public $key = '';

    /**
     * Puts entity into collection
     * @param AbstractEntity $entity
     * @param string         $onDuplicate Action to perform on duplicate entity found
     *
     * @throws \DevGroup\FlexIntegration\errors\DuplicateEntity
     */
    public function put($entity, $onDuplicate = FormatReducer::ON_DUPLICATE_SKIP)
    {
        $uuid = $entity->documentScopeId === '' ? uniqid("~~~_{$entity->modelKey}", true) : $entity->documentScopeId;
        if (isset($this->entities[$uuid])) {
            if ($onDuplicate === FormatReducer::ON_DUPLICATE_FAIL) {
                throw new DuplicateEntity('', [
                    'documentScopeId' => $uuid,
                    'modelKey' => $entity->modelKey,
                ]);
            } elseif ($onDuplicate === FormatReducer::ON_DUPLICATE_SKIP) {
                // skip this entity
                return;
            }
        }
        $this->entities[$uuid] = $entity;
    }
}

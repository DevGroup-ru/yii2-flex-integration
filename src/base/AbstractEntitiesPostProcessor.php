<?php

namespace DevGroup\FlexIntegration\base;

use yii\base\Object;

abstract class AbstractEntitiesPostProcessor extends Object
{
    abstract public function processEntities(array &$entities, $collectionKey = '', array $entitiesDecl);
}

<?php

namespace DevGroup\FlexIntegration\base;

use DevGroup\FlexIntegration\models\ImportTask;
use yii\base\Object;

abstract class AbstractEntitiesPostProcessor extends Object
{
    abstract public function processEntities(array &$entities, $collectionKey = '', ImportTask &$task);
}

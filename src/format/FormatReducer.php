<?php

namespace DevGroup\FlexIntegration\format;

use DevGroup\FlexIntegration\base\AbstractEntity;
use DevGroup\FlexIntegration\base\AbstractEntityCollection;
use Yii;
use yii\base\Object;

abstract class FormatReducer extends Object
{
    /**
     * @param AbstractEntity[] $entities
     *
     * @return AbstractEntityCollection[]
     */
    abstract public function reduceToCollections($entities);
}

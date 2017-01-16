<?php

namespace DevGroup\FlexIntegration\format;

use DevGroup\FlexIntegration\base\AbstractEntity;
use DevGroup\FlexIntegration\base\AbstractEntityCollection;
use Yii;
use yii\base\Object;

abstract class FormatReducer extends Object
{
    const ON_DUPLICATE_SKIP = 'on-duplicate-skip';
    const ON_DUPLICATE_FAIL = 'on-duplicate-fail';
//    const ON_DUPLICATE_NEW  = 'on-duplicate-new'; /// unsupported for now
//    const ON_DUPLICATE_MERGE = 'on-duplicate-merge'; /// unsupported for now, will merge second entity to existing

    public $onDuplicate = 'on-duplicate-skip';

    /**
     * @param AbstractEntity[] $entities
     * @param AbstractEntityCollection[]                           $collections
     *
     * @return AbstractEntityCollection[]
     */
    abstract public function reduceToCollections($entities, array &$collections);
}

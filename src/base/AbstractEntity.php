<?php

namespace DevGroup\FlexIntegration\base;

use yii\base\Model;

class AbstractEntity
{
    /** @var string */
    public $modelClassName = '';

    /** @var int Model PK */
    public $pk = -1;

    /** @var null|array Search existing models by this attributes */
    public $searchBy;

    /** @var string ID of entity inside this task document*/
    public $documentScopeId = '';

    /** @var bool */
    public $isNew = false;

    /** @var array  */
    public $attributes = [];

    /** @var AbstractEntityProperty[] */
    public $properties = [];

    /**
     * @var AbstractEntity[] Child entities, not related
     */
    public $childEntities = [];

    /**
     * @var EntityRelation[]
     */
    public $relatesTo = [];

    /**
     * @var Model mapped model
     */
    public $model;
}

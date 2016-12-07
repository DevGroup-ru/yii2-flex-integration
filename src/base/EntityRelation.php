<?php

namespace DevGroup\FlexIntegration\base;

class EntityRelation
{
    /**
     * @var bool is it has_many or many_many relation
     *           In case of many-many relation all existing will be unbinded
     */
    public $relationIsMany = false;

    /** @var string */
    public $fromKey;

    /** @var int */
    public $fromDocumentId;

    /** @var string */
    public $toKey;

    /** @var int */
    public $toTaskId;

    /** @var string|array Declaration of relation in our document */
    public $relationDeclaration;
}

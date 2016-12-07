<?php

namespace DevGroup\FlexIntegration\base;

class AbstractEntityProperty
{
    /** @var int */
    public $propertyId = -1;

    /** @var string */
    public $propertyKey = '';

    /** @var array[] */
    public $values = [];
}

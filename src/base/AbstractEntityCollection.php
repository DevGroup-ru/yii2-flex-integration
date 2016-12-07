<?php

namespace DevGroup\FlexIntegration\base;

class AbstractEntityCollection
{
    /** @var AbstractEntity[] */
    public $entities = [];

    /** @var string Key for identifying dependencies */
    public $key = '';

    /**
     * @var string[] Array of keys for abstract entities we depend on
     */
    public $depends = [];
}

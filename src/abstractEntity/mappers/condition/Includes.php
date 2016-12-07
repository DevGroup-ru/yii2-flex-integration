<?php

namespace DevGroup\FlexIntegration\abstractEntity\mappers\condition;

use DevGroup\FlexIntegration\abstractEntity\mappers\ConditionHandler;

class Includes extends ConditionHandler
{
    public $value;

    public function handle($haystack)
    {
        return mb_strpos($haystack, $this->value) !== false;
    }
}

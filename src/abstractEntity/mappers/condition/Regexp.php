<?php

namespace DevGroup\FlexIntegration\abstractEntity\mappers\condition;

use DevGroup\FlexIntegration\abstractEntity\mappers\ConditionHandler;

class Regexp extends ConditionHandler
{
    public $value;

    public function handle($haystack)
    {
        return preg_match($this->value, $haystack) > 0;
    }
}

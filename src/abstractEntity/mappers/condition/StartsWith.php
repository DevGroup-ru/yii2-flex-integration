<?php

namespace DevGroup\FlexIntegration\abstractEntity\mappers\condition;

use DevGroup\FlexIntegration\abstractEntity\mappers\ConditionHandler;

class StartsWith extends ConditionHandler
{
    public $value;

    public function handle($haystack)
    {
        $length = mb_strlen($this->value);
        return (mb_substr($haystack, 0, $length) === $this->value);
    }
}

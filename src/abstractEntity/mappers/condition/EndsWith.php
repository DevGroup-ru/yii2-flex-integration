<?php

namespace DevGroup\FlexIntegration\abstractEntity\mappers\condition;

use DevGroup\FlexIntegration\abstractEntity\mappers\ConditionHandler;

class EndsWith extends ConditionHandler
{
    public $value;

    public function handle($haystack)
    {
        $length = mb_strlen($this->value);
        if ($length == 0) {
            return true;
        }

        return (mb_substr($haystack, -$length) === $this->value);
    }
}

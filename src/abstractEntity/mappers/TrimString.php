<?php

namespace DevGroup\FlexIntegration\abstractEntity\mappers;

class TrimString extends FieldMapper
{
    public $charlist = " \t\n\r\0\x0B";

    public function map($value)
    {
        return trim($value, $this->charlist);
    }
}

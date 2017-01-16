<?php

namespace DevGroup\FlexIntegration\abstractEntity\mappers;

class UppercaseString extends FieldMapper
{
    public function map($value)
    {
        return strtoupper($value);
    }
}

<?php

namespace DevGroup\FlexIntegration\abstractEntity\mappers;

use Yii;

class Typecast extends FieldMapper
{
    public $type = 'float';

    const TYPE_INT = 'int';
    const TYPE_FLOAT = 'float';
    const TYPE_BOOL = 'bool';

    public function map($value)
    {
        switch ($this->type) {
            case self::TYPE_INT:
                return (int) $value;
                break;
            case self::TYPE_BOOL:
                return (bool) $value;
                break;
            case self::TYPE_FLOAT:
            default:
                return (float) $value;
        }
    }
}

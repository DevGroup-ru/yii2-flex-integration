<?php

namespace DevGroup\FlexIntegration\abstractEntity\mappers;

class Typecast extends FieldMapper
{
    public $type = 'float';

    const TYPE_INT = 'int';
    const TYPE_FLOAT = 'float';
    const TYPE_BOOL = 'bool';

    /**
     * Typecasts string value. By-default casts to float.
     * @param string $value
     *
     * @return bool|float|int
     */
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

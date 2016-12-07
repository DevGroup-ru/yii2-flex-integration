<?php

namespace DevGroup\FlexIntegration\abstractEntity\mappers;

use yii\base\Object;

class FieldMapper extends Object
{
    /**
     * @param string $value
     *
     * @return bool
     */
    public function isApplicable($value)
    {
        return true;
    }

    /**
     * @param string $value
     *
     * @return mixed
     */
    public function map($value)
    {
        return $value;
    }
}

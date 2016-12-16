<?php

namespace DevGroup\FlexIntegration\abstractEntity\mappers;

use Yii;

class Replace extends FieldMapper
{
    public $search = '';
    public $replace = '';
    public $isRegExp = false;
    public $caseInsensitive = false;

    public function map($value)
    {
        return $this->isRegExp ?
            preg_replace($this->search, $this->replace, $value) :
            $this->caseInsensitive ?
                str_ireplace($this->search, $this->replace, $value) :
                str_replace($this->search, $this->replace, $value);
    }
}

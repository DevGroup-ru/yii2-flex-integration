<?php

namespace DevGroup\FlexIntegration\abstractEntity\mappers;

use Yii;

class ConditionalMapper extends FieldMapper
{
    public $conditionHandler;

    /** @var ConditionHandler */
    public $condition;

    /** @inheritdoc */
    public function init()
    {
        parent::init();

        if (is_string($this->condition)) {
            $this->condition = ['class' => $this->condition];
        }

        if (is_array($this->condition)) {
            $this->condition = Yii::createObject($this->condition);
        }
    }

    public function isApplicable($value)
    {
        return $this->condition->handle($value);
    }
}

<?php

namespace DevGroup\FlexIntegration\abstractEntity\mappers;

use Yii;

class ConditionalMapper extends FieldMapper
{
    /** @var FieldMapper[] */
    public $mappers = [];

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

        foreach ($this->mappers as $i => $config) {
            if (is_object($config)) {
                continue;
            }
            if (!isset($config['class'])) {
                $config['class'] = TrimString::class;
            }
            $this->mappers[$i] = Yii::createObject($config);
        }
    }

    public function isApplicable($value)
    {
        return $this->condition->handle($value);
    }

    public function map($value)
    {
        foreach ($this->mappers as $mapper) {
            $value = $mapper->map($value);
        }
        return $value;
    }
}

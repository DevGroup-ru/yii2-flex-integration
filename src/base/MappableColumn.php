<?php

namespace DevGroup\FlexIntegration\base;

use DevGroup\FlexIntegration\abstractEntity\mappers\FieldMapper;
use Yii;
use yii\base\NotSupportedException;
use yii\base\Object;

class MappableColumn extends Object
{
    public $field = '';

    public $type = 'attribute';

    const TYPE_ATTRIBUTE = 'attribute';
    const TYPE_PROPERTY = 'property';
    const TYPE_RELATION = 'relation';

    /**
     * @var FieldMapper[]
     */
    public $mappers = [];

    public $entity = '';

    public $entityModel = '';

    public $skipRowOnEmptyValue = false;

    public $asPk = false;

    public $asSearch = false;

    /**
     * @param string $value
     *
     * @return mixed
     */
    public function map($value)
    {
        foreach ($this->mappers as $mapper) {
            if ($mapper->isApplicable($value)) {
                $value = $mapper->map($value);
            }
        }
        return $value;
    }

    /**
     * @param AbstractEntity $entity
     * @param mixed          $value
     */
    public function bindToEntity(AbstractEntity &$entity, $value)
    {
        switch ($this->type) {
            case self::TYPE_PROPERTY:
                throw new NotSupportedException("Not implemented yet");
                break;
            case self::TYPE_RELATION:
                throw new NotSupportedException("Not implemented yet");
                break;
            case self::TYPE_ATTRIBUTE:
            default:
                $entity->attributes[$this->field] = $value;
                break;
        }
    }
}

<?php

namespace DevGroup\FlexIntegration\base;

use DevGroup\FlexIntegration\abstractEntity\mappers\FieldMapper;
use yii\base\NotSupportedException;
use yii\base\Object;

class MappableColumn extends Object
{
    /** @var string Field name(attribute, relation or property key) */
    public $field = '';

    /** @var string Type of field: attribute, property or relation */
    public $type = 'attribute';

    const TYPE_ATTRIBUTE = 'attribute';
    const TYPE_PROPERTY = 'property';
    const TYPE_RELATION = 'relation';

    /**
     * @var FieldMapper[]
     */
    public $mappers = [];

    /** @var string entity key in AbstractEntityCollection */
    public $entity = '';

    /** @var bool If set to true and final value after all mappings is empty - skip the entire row */
    public $skipRowOnEmptyValue = false;

    /** @var bool Field is used as pk, only non-composite integer primary keys are supported for now */
    public $asPk = false;

    /** @var bool|string Field is used for searching models by attribute name specified in asSearch */
    public $asSearch = false;

    /** @var bool Field is used as document-scope ID */
    public $asDocumentScopeId = false;

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
     * @param mixed          $value Mapped value
     *
     * @throws \yii\base\NotSupportedException
     */
    public function bindToEntity(AbstractEntity $entity, $value)
    {
        switch ($this->type) {
            case self::TYPE_PROPERTY:
                throw new NotSupportedException('Not implemented yet');
                break;
            case self::TYPE_RELATION:
                throw new NotSupportedException('Not implemented yet');
                break;
            case self::TYPE_ATTRIBUTE:
            default:
                $entity->attributes[$this->field] = $value;
                break;
        }
        if ($this->asSearch !== false) {
            if ($entity->searchBy === null) {
                $entity->searchBy = [];
            }
            $entity->searchBy[$this->asSearch] = $value;
        }
        if ($this->asPk !== false && $value > 0) {
            $entity->pk = $value;
        }
        if ($this->asDocumentScopeId === true) {
            $entity->documentScopeId = "{$entity->modelKey}###$value";
        }
    }
}

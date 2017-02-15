<?php

namespace DevGroup\FlexIntegration\base;

use DevGroup\FlexIntegration\abstractEntity\mappers\FieldMapper;
use DevGroup\FlexIntegration\abstractEntity\preProcessors\RelationFinder;
use DevGroup\FlexIntegration\format\FormatMapper;
use DevGroup\FlexIntegration\models\ImportTask;
use Yii;
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
    const TYPE_VIRTUAL = 'virtual';
    const TYPE_PRICE = 'price';

    /**
     * @var FieldMapper[]
     */
    public $mappers = [];

    /**
     * @var array Configuration of relation searcher
     */
    public $relationSearchConfig = [];

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
     * @var string Delimiter used for joining multiple values for field.
     */
    public $multipleValuesDelimiter = '|';

    public $relationFinder = [];

    public $sourceId;

    /** @var  ImportTask */
    public $task;

    public $propertyMeta = [];

    /**
     * @param string $value
     *
     * @return mixed
     */
    public function map($value)
    {
        $valueArray =
            $this->multipleValuesDelimiter !== ''
                ? explode($this->multipleValuesDelimiter, $value)
                : [$value];

        foreach ($valueArray as &$item) {
            foreach ($this->mappers as $mapper) {
                if ($mapper->isApplicable($item)) {
                    $item = $mapper->map($item);
                }
            }
        }

        return
            count($valueArray) === 1
                ? reset($valueArray)
                : $valueArray;
    }

    /**
     * @param AbstractEntity $entity
     * @param mixed $value Mapped value
     *
     * @throws \yii\base\NotSupportedException
     */
    public function bindToEntity(AbstractEntity $entity, $value)
    {
        if ($this->type === self::TYPE_VIRTUAL) {
            return;
        }
        switch ($this->type) {
            case self::TYPE_PROPERTY:
                $entity->properties[$this->field] = [
                    'value' => $value,
                    'meta' => $this->propertyMeta
                ];
                break;

            case self::TYPE_RELATION:
                $entity->relatesTo[$this->field] = $value;
                break;

            case self::TYPE_PRICE:
                $entity->prices[$this->field] = $value;
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

    /**
     * @param FormatMapper|object $mapper
     */
    public function postConfig(&$mapper)
    {
        if ($this->type === self::TYPE_RELATION) {
            $modelClass = $mapper->entitiesDecl[$this->entity]['class'];

            $relatedClass = RelationFinder::relationTarget($modelClass, $this->field);
            if (isset($mapper->entitiesDecl[$this->entity]['depends'][$relatedClass]) === false) {
                $mapper->entitiesDecl[$this->entity]['depends'][$relatedClass] = [];
            }
            if ($this->sourceId === null) {
                throw new \Exception("FUCK NULL");
            }
            $mapper->entitiesDecl[$this->entity]['depends'][$relatedClass][$this->sourceId] = $this->relationFinder;
            if (isset($this->task->preProcessors[$this->entity]) === false) {
                $this->task->preProcessors[$this->entity] = [];
            }
            if (isset($this->task->preProcessors[$this->entity][$this->sourceId]) === false) {
                $this->task->preProcessors[$this->entity][$this->sourceId] = [];
            }
            /** @var RelationFinder $instance */
            $instance = Yii::createObject($this->relationFinder);
            $this->task->preProcessors[$this->entity][$this->sourceId][] = $instance;
        }
    }
}

<?php

namespace DevGroup\FlexIntegration\format\mappers;

use DevGroup\FlexIntegration\abstractEntity\mappers\TrimString;
use DevGroup\FlexIntegration\base\AbstractEntity;
use DevGroup\FlexIntegration\base\MappableColumn;
use Yii;
use yii\base\Model;

trait Document2D
{
    /**
     * @var int Hos much lines to skip from document start
     */
    public $skipLinesFromTop = 0;
    /**
     * @var int How much lines to read excluding skipLinesFromTop
     */
    public $maxLines = 0;

    /**
     * @var MappableColumn
     */
    protected $listMappers = [];

    /**
     * @param array  $row
     * @param string $schemaList
     *
     * @return \DevGroup\FlexIntegration\base\AbstractEntity[]
     */
    public function processRow($row, $schemaList = 'defaultList')
    {
        $entities = [];
        $schema = $this->prepareListSchema($schemaList);
        foreach ($schema as $index => $mappableColumn) {
            if (isset($row[$index])) {
                /** @var AbstractEntity $entity */
                $entity = $this->ensureEntity($entities, $mappableColumn);

                /** @var MappableColumn $mappableColumn */

                //! @todo add global pre and post process field mappers here?
                $value = $mappableColumn->map($row[$index]);

                if ($mappableColumn->skipRowOnEmptyValue && empty($value)) {
                    return [];
                }

                $mappableColumn->bindToEntity($entity, $value);

                if ($mappableColumn->asSearch !== false) {
                    if ($entity->searchBy === null) {
                        $entity->searchBy = [];
                    }
                    $entity->searchBy[$mappableColumn->asSearch] = $value;
                }
                if ($mappableColumn->asPk !== false && $value > 0) {
                    $entity->pk = $value;
                }
            }
        }

        return $entities;
    }

    /**
     * @param AbstractEntity[] $entities
     * @param \DevGroup\FlexIntegration\base\MappableColumn $mappableColumn
     *
     * @return AbstractEntity
     */
    protected function ensureEntity(&$entities, MappableColumn $mappableColumn)
    {
        if (isset($entities[$mappableColumn->entity]) === false) {
            $abstract = new AbstractEntity();
            $abstract->modelClassName = $mappableColumn->entityModel;

            $entities[$mappableColumn->entity] = $abstract;
        }
        return $entities[$mappableColumn->entity];
    }

    /**
     * @param string $schemaList
     *
     * @return MappableColumn[]
     */
    protected function prepareListSchema($schemaList = 'defaultList')
    {
        if (isset($this->listMappers[$schemaList]) === false) {
            $mappers = [];

            if (isset($this->schema[$schemaList]) === false) {
                if (isset($this->schema['defaultList'])) {
                    $schema = $this->schema['defaultList'];
                } else {
                    throw new \InvalidArgumentException('FormatMapper schema must include list declaration');
                }
            } else {
                $schema = $this->schema[$schemaList];
            }

            if (isset($schema['columns']) === false) {
                throw new \InvalidArgumentException('List declaration must include columns');
            }

            $defaultEntity = isset($schema['defaultEntity']) ? $schema['defaultEntity'] : 'row';
            $defaultMappers = isset($schema['defaultMappers']) ? $schema['defaultMappers'] : [
                TrimString::class,
            ];
            $entitiesDecl = isset($schema['entities']) ? $schema['entities'] : [
                $defaultEntity => Model::class,
            ];

            /** @var array $columns */
            $columns = $schema['columns'];
            foreach ($columns as $index => $config) {
                if (isset($config['class']) === false) {
                    $config['class'] = MappableColumn::class;
                }
                // field mappers
                $fieldMappers = isset($config['mappers']) ? $config['mappers'] : $defaultMappers;
                foreach ($fieldMappers as $i => $mapperConfig) {
                    if (is_string($mapperConfig)) {
                        $mapperConfig = [
                            'class' => $mapperConfig,
                        ];
                    }

                    if (!isset($mapperConfig['class'])) {
                        $mapperConfig['class'] = TrimString::class;
                    }
                    $fieldMappers[$i] = Yii::createObject($mapperConfig);
                }
                /** @var MappableColumn $mapper */
                $mapper = Yii::createObject($config);
                if ($mapper->entity === '') {
                    $mapper->entity = $defaultEntity;
                }
                $mapper->mappers = $fieldMappers;

                if (isset($entitiesDecl[$mapper->entity])) {
                    $mapper->entityModel = $entitiesDecl[$mapper->entity];
                } else {
                    throw new \InvalidArgumentException('Field tries to map to undeclared entity: ' . $mapper->entity);
                }

                $mappers[$index] = $mapper;
            }

            $this->listMappers[$schemaList] = $mappers;
        }
        return $this->listMappers[$schemaList];
    }
}

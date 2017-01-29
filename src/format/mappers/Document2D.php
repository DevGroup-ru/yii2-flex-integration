<?php

namespace DevGroup\FlexIntegration\format\mappers;

use DevGroup\FlexIntegration\abstractEntity\mappers\TrimString;
use DevGroup\FlexIntegration\base\AbstractEntity;
use DevGroup\FlexIntegration\base\MappableColumn;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

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

    /** @var MappableColumn[] */
    protected $processedSchema = [];

    /**
     * @param array  $row
     * @param string $schemaList
     *
     * @return \DevGroup\FlexIntegration\base\AbstractEntity[]
     */
    public function processRow($row, $sourceId, $schemaList = 'defaultList')
    {
        /** @var AbstractEntity[] Entities for this row */
        $entities = [];
        $this->processedSchema = $this->prepareListSchema($sourceId, $schemaList);
        foreach ($this->processedSchema as $index => $mappableColumn) {
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
            $abstract->modelKey = $mappableColumn->entity;

            $entities[$mappableColumn->entity] = $abstract;
        }
        return $entities[$mappableColumn->entity];
    }

    /**
     * @param string $schemaList
     *
     * @return MappableColumn[]
     */
    protected function prepareListSchema($sourceId, $schemaList = 'defaultList')
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
            $this->entitiesDecl = ArrayHelper::merge(
                $this->entitiesDecl,
                isset($schema['entities']) ? $schema['entities'] : [
                    $defaultEntity => [
                        'class' => Model::class,
                    ],
                ]
            );
            $this->ensureEntitiesDeclOk();


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
                /** @var MappableColumn $mappableColumn */
                $mappableColumn = Yii::createObject($config);
                if ($mappableColumn->entity === '') {
                    $mappableColumn->entity = $defaultEntity;
                }
                $mappableColumn->sourceId = $sourceId;
                $mappableColumn->mappers = $fieldMappers;
                $mappableColumn->task = $this->task;

                if (isset($this->entitiesDecl[$mappableColumn->entity]) === false) {
                    throw new \InvalidArgumentException('Field tries to map to undeclared entity: ' . $mappableColumn->entity);
                }
                $mappableColumn->postConfig($this);
                $mappers[$index] = $mappableColumn;
            }

            $this->listMappers[$schemaList] = $mappers;
        }
        return $this->listMappers[$schemaList];
    }
}

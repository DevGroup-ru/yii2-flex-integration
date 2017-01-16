<?php

namespace DevGroup\FlexIntegration\models;

use DevGroup\FlexIntegration\base\AbstractEntity;
use DevGroup\FlexIntegration\base\AbstractEntityCollection;
use DevGroup\FlexIntegration\base\DocumentConfiguration;
use DevGroup\FlexIntegration\format\FormatMapper;
use DevGroup\FlexIntegration\format\FormatReducer;
use Yii;

class ImportTask extends BaseTask
{
    public $taskType = self::TASK_TYPE_IMPORT;

    /**
     * @param array $config
     *
     */
    public function run(array $config = [])
    {
        Yii::configure($this, $config);
        $collections = [];
        foreach ($this->documents as $doc) {
            /** @var AbstractEntity[] $entities */
            $entities = $this->mapDoc($doc);

            // reduce here
            $this->reduceDoc($doc, $entities, $collections);
        }
    }

    /**
     * @param \DevGroup\FlexIntegration\base\DocumentConfiguration $doc
     *
     * @return \DevGroup\FlexIntegration\base\AbstractEntity[]
     */
    public function mapDoc(DocumentConfiguration $doc)
    {
        /** @var FormatMapper $formatMapper */
        $formatMapper = Yii::createObject($doc->formatMapper);
        return $formatMapper->mapInputDocument($this, $doc->importFilename());
    }

    /**
     * @param \DevGroup\FlexIntegration\base\DocumentConfiguration $doc
     * @param AbstractEntity[]                                     $entities
     * @param AbstractEntityCollection[]                           $collections
     * @return AbstractEntityCollection[]
     */
    public function reduceDoc(DocumentConfiguration $doc, array $entities, array &$collections)
    {
        /** @var FormatReducer $formatReducer */
        $formatReducer = Yii::createObject($doc->formatReducer);
        return $formatReducer->reduceToCollections($entities, $collections);
    }
}

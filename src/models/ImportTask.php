<?php

namespace DevGroup\FlexIntegration\models;

use DevGroup\FlexIntegration\base\AbstractEntity;
use DevGroup\FlexIntegration\base\DocumentConfiguration;
use DevGroup\FlexIntegration\format\FormatMapper;
use yii;

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
        foreach ($this->documents as $doc) {
            /** @var AbstractEntity[] $entities */
            $entities = $this->mapDoc($doc);
            file_put_contents('/tmp/flex.json', yii\helpers\Json::encode($entities, JSON_PRETTY_PRINT));
            // reduce here
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
}

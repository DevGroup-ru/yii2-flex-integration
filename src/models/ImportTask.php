<?php

namespace DevGroup\FlexIntegration\models;

use DevGroup\FlexIntegration\base\AbstractEntity;
use DevGroup\FlexIntegration\format\FormatMapper;
use yii;

class ImportTask extends BaseTask
{
    public $taskType = self::TASK_TYPE_IMPORT;


    /**
     * @param array $config
     *
     * @return mixed
     */
    public function run($config = [])
    {
        Yii::configure($this, $config);
        foreach ($this->documents as $doc) {
            /** @var AbstractEntity[] $entities */
            $entities = [];
            /** @var FormatMapper $formatMapper */
            $formatMapper = Yii::createObject($doc->formatMapper);
            $entities = $formatMapper->mapInputDocument($this, $doc->importFilename());
            file_put_contents('/tmp/flex.json', yii\helpers\Json::encode($entities, JSON_PRETTY_PRINT));
            // reduce here
        }
    }
}

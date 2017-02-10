<?php
namespace DevGroup\FlexIntegration\format\mappers;

use DevGroup\FlexIntegration\models\BaseTask;

interface MapperGeneratorInterface
{
    /**
     * @param BaseTask $task
     * @param $document
     * @param $sourceId
     * @return \Generator
     */
    public function getGenerator(BaseTask $task, $document, $sourceId);
}
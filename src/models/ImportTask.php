<?php

namespace DevGroup\FlexIntegration\models;

use yii;

class ImportTask extends BaseTask
{
    public $taskType = self::TASK_TYPE_IMPORT;

    /**
     * @return string
     */
    public function documentFilename()
    {
        return $this->repository()->inputFilesLocation . '/' . $this->document;
    }
}

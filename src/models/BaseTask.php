<?php

namespace DevGroup\FlexIntegration\models;

use DevGroup\FlexIntegration\models\traits\TaskStorage;
use yii;

class BaseTask extends yii\base\Model
{
    use TaskStorage;

    const TASK_TYPE_IMPORT = 'import';
    const TASK_TYPE_EXPORT = 'export';

    /**
     * @var string Task Type
     */
    public $taskType = self::TASK_TYPE_IMPORT;

    /** @var string Filename of input document */
    public $document = '';

    /** @var string  */
    public $name = '';

    /**
     * Creates needed task
     * @param string $type
     * @param array $config
     *
     * @return ExportTask|ImportTask
     */
    public static function create($type, $config)
    {
        return $type === self::TASK_TYPE_IMPORT ? new ImportTask($config) : new ExportTask($config);
    }


}

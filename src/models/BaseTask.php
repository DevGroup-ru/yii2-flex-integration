<?php

namespace DevGroup\FlexIntegration\models;

use DevGroup\FlexIntegration\base\DocumentConfiguration;
use DevGroup\FlexIntegration\models\traits\TaskStorage;
use yii;
use yii\helpers\Json;

/**
 * Class BaseTask
 *
 * @property DocumentConfiguration[] $documents
 * @package DevGroup\FlexIntegration\models
 */
abstract class BaseTask extends yii\base\Model
{
    use TaskStorage;

    const TASK_TYPE_IMPORT = 'import';
    const TASK_TYPE_EXPORT = 'export';

    /**
     * @var string Task Type
     */
    public $taskType = self::TASK_TYPE_IMPORT;

    /** @var DocumentConfiguration[] Input documents */
    protected $documents = [];

    /** @var string  */
    public $name = '';

    public $batchSize = 50;

    /**
     * @param DocumentConfiguration[] $documents
     */
    public function setDocuments($documents)
    {
        $this->documents = [];
        foreach ($documents as $key => $doc) {
            $this->documents[$key] = new DocumentConfiguration($doc);
        }
    }

    /**
     * @return DocumentConfiguration[]
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * @return string json encoded attributes
     */
    public function serialize()
    {
        $array = $this->attributes;
        $array['documents'] = [];
        foreach ($this->documents as $doc) {
            $array['documents'][] = $doc->attributes;
        }
        return Json::encode($array);
    }

    /**
     * @param string $string Json string
     *
     * @return BaseTask
     */
    public static function unserialize($string)
    {
        $config = Json::decode($string);
        $type = self::TASK_TYPE_IMPORT;
        if (isset($config['taskType'])) {
            $type = $config['taskType'];
        }
        return static::create($type, $config);
    }

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

    /**
     * @param array $config
     *
     */
    abstract public function run(array $config = []);
}

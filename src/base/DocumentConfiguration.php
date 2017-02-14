<?php

namespace DevGroup\FlexIntegration\base;

use DevGroup\FlexIntegration\components\TaskRepository;
use DevGroup\FlexIntegration\format\reducers\DefaultReducer;
use Yii;
use yii\base\Model;

/**
 * Class DocumentConfiguration
 * @package DevGroup\FlexIntegration\base
 */
class DocumentConfiguration extends Model
{
    /** @var string */
    public $filename = '';

    /**
     * @var array
     */
    public $formatMapper = [];

    /**
     * @var array
     */
    public $formatReducer = [
        'class' => DefaultReducer::class,
    ];

    /**
     * @var array
     */
    public $entitiesPreProcessors = [];

    /**
     * @var array
     */
    public $params = [];

    /**
     * @return string
     */
    public function importFilename()
    {
        /** @var TaskRepository $respository */
        $repository = Yii::$app->getModule('flex')->taskRepository;
        return $repository->inputFilesLocation . '/' . $this->filename;
    }
}

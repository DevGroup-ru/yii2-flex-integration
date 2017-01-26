<?php

namespace DevGroup\FlexIntegration\base;

use DevGroup\FlexIntegration\components\TaskRepository;
use DevGroup\FlexIntegration\format\reducers\DefaultReducer;
use Yii;
use yii\base\Model;

class DocumentConfiguration extends Model
{
    /** @var string  */
    public $filename = '';

    public $formatMapper = [];

    public $formatReducer = [
        'class' => DefaultReducer::class,
    ];

    public $entitiesPreProcessors = [];

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

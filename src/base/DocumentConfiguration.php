<?php

namespace DevGroup\FlexIntegration\base;

use DevGroup\FlexIntegration\components\TaskRepository;
use Yii;
use yii\base\Model;

class DocumentConfiguration extends Model
{
    /** @var string  */
    public $filename = '';

    public $formatMapper = [];

    public $formatReducer = [];

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

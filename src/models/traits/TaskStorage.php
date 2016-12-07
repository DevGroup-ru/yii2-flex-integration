<?php

namespace DevGroup\FlexIntegration\models\traits;

use DevGroup\FlexIntegration\components\TaskRepository;
use yii;

/**
 * Class TaskStorage
 *
 * @property string $name
 * @package DevGroup\FlexIntegration\models\traits
 */
trait TaskStorage
{
    /**
     * @return TaskRepository
     */
    protected function repository()
    {
        return Yii::$app->getModule('flex')->taskRepository;
    }

    /**
     * @return string Returns filename of stored file
     */
    public function storedFilename()
    {
        return $this->repository()->taskFilesLocation . '/' . $this->name . '.json';
    }

    /**
     * Stores task in json file
     * @return bool Result
     */
    public function store()
    {
        return file_put_contents(
            $this->storedFilename(),
            yii\helpers\Json::encode($this)
        ) > 0;
    }
}

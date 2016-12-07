<?php

namespace DevGroup\FlexIntegration;

use DevGroup\FlexIntegration\components\TaskRepository;
use yii;

class FlexIntegrationModule extends yii\base\Module
{
    /** @var array|TaskRepository|string */
    public $taskRepository = [
        'class' => 'DevGroup\FlexIntegration\components\TaskRepository',
    ];

    /** @inheritdoc */
    public function init()
    {
        parent::init();

        if (is_string($this->taskRepository)) {
            $this->taskRepository = ['class' => $this->taskRepository];
        }
        if (is_array($this->taskRepository)) {
            $this->taskRepository = Yii::createObject($this->taskRepository);
        }
    }
}

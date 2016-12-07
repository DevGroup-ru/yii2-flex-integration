<?php

namespace DevGroup\FlexIntegration\components;

use yii;

class TaskRepository extends yii\base\Component
{
    public $taskFilesLocation = '@app/flex/tasks';

    public $temporaryFilesLocation = '@app/flex/tmp';

    public $inputFilesLocation = '@app/flex/in';

    public $outputFilesLocation = '@app/flex/out';


    /** @inheritdoc */
    public function init()
    {
        parent::init();
        $this->taskFilesLocation = Yii::getAlias($this->taskFilesLocation);
        $this->temporaryFilesLocation = Yii::getAlias($this->temporaryFilesLocation);
        $this->inputFilesLocation = Yii::getAlias($this->inputFilesLocation);
        $this->outputFilesLocation = Yii::getAlias($this->outputFilesLocation);
    }
}

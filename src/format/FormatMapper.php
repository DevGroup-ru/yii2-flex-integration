<?php

namespace DevGroup\FlexIntegration\format;

use DevGroup\FlexIntegration\base\AbstractEntity;
use DevGroup\FlexIntegration\base\ConfigurableProcessor;
use DevGroup\FlexIntegration\models\BaseTask;
use Yii;
use yii\base\Object;

abstract class FormatMapper extends Object
{
    use ConfigurableProcessor;

    /** @var string  */
    public $defaultEntityClass = '';

    /**
     * @param \DevGroup\FlexIntegration\models\BaseTask $task
     * @param string $document
     *
     * @return AbstractEntity[]
     */
    abstract public function mapInputDocument(BaseTask $task, $document);
}

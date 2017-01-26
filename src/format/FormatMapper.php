<?php

namespace DevGroup\FlexIntegration\format;

use DevGroup\FlexIntegration\base\AbstractEntitiesPostProcessor;
use DevGroup\FlexIntegration\base\AbstractEntity;
use DevGroup\FlexIntegration\base\ConfigurableProcessor;
use DevGroup\FlexIntegration\models\BaseTask;
use Yii;
use yii\base\Object;

abstract class FormatMapper extends Object
{
    use ConfigurableProcessor;

    /**
     * @var string Chooses the strategy for dealing with duplicated rows on existing models
     */
    public $duplicatedExistingModelsStrategy = 'overwrite';

    /** Overwrite with last row data */
    const DUPLICATED_EXISTING_MODELS_OVERWRITE = 'overwrite';
    /** Ignore all duplicates - take first occurrence  */
    const DUPLICATED_EXISTING_MODELS_IGNORE = 'ignore';
    /** Exclude all rows for duplicates including first occurrence */
    const DUPLICATED_EXISTING_MODELS_EXCLUDE_ALL = 'exclude-all';

    /**
     * @var string Behavior if pk is specified but entity not found
     */
    public $notFoundEntityStrategy = 'create-new';

    const NOT_FOUND_ENTITY_CREATE_NEW = 'create-new';
    const NOT_FOUND_ENTITY_IGNORE = 'ignore';

    /** @var string  */
    public $defaultEntityClass = '';

    /** @var array  */
    public $entitiesDecl = [];



    /**
     * @param \DevGroup\FlexIntegration\models\BaseTask $task
     * @param string $document
     * @param string $sourceId
     *
     * @return AbstractEntity[]
     */
    abstract public function mapInputDocument(BaseTask $task, $document, $sourceId);

    public function ensureEntitiesDeclOk()
    {
        foreach ($this->entitiesDecl as $key => $value) {
            if (false === array_key_exists('depends', $value)) {
                $this->entitiesDecl[$key]['depends'] = [];
            }
        }
    }


}

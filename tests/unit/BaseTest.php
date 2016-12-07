<?php

use Codeception\Module\Filesystem;
use Codeception\Specify;
use DevGroup\FlexIntegration\FlexIntegrationModule;

class BaseTest extends \Codeception\Test\Unit
{
    use Specify;
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        /** @var Filesystem $fs */
        $fs = $this->getModule('Filesystem');
        $fs->cleanDir($this->flex()->taskRepository->taskFilesLocation);
        $fs->cleanDir($this->flex()->taskRepository->temporaryFilesLocation);
        $fs->cleanDir($this->flex()->taskRepository->inputFilesLocation);
        $fs->cleanDir($this->flex()->taskRepository->outputFilesLocation);
    }

    protected function _after()
    {

    }

    /**
     * @return FlexIntegrationModule
     */
    protected function flex()
    {
        return Yii::$app->getModule('flex');
    }

    /**
     * @return string
     */
    protected function getDataDir()
    {
        return Yii::getAlias('@app/data');
    }
}
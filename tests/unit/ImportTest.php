<?php


use DevGroup\FlexIntegration\components\TaskRepository;
use DevGroup\FlexIntegration\models\BaseTask;
use DevGroup\FlexIntegration\models\ImportTask;

class ImportTest extends BaseTest
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    // tests
    public function testCSVImport()
    {
        $repository = $this->flex()->taskRepository;

        $this->specify('Task repository is working', function () use ($repository) {
            $this->assertInstanceOf(TaskRepository::class, $repository);
        });

        // prepare input document
        $filename = 'import-test1.csv';
        copy($this->getDataDir() . '/' . $filename, $repository->inputFilesLocation . '/' . $filename);

        $config = [
            'document' => $filename
        ];

        // create task
        $task = BaseTask::create(BaseTask::TASK_TYPE_IMPORT, $config);
        $this->assertInstanceOf(ImportTask::class, $task);

        // check if we can read it
        $this->assertFileExists($task->documentFilename());
    }
}
<?php

use DevGroup\FlexIntegration\components\TaskRepository;
use DevGroup\FlexIntegration\models\BaseTask;
use DevGroup\FlexIntegration\models\ImportTask;

class TaskTest extends BaseTest
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    // tests
    public function testBaseTask()
    {
        $repository = $this->flex()->taskRepository;

        $this->specify('Task repository is working', function () use ($repository) {
            $this->assertInstanceOf(TaskRepository::class, $repository);
        });

        // prepare input document
        $filename = 'import-test1.csv';
        copy($this->getDataDir() . '/' . $filename, $repository->inputFilesLocation . '/' . $filename);

        $config = [
            'documents' => [[
                'filename' => $filename,
            ]],
        ];

        // create task
        $task = BaseTask::create(BaseTask::TASK_TYPE_IMPORT, $config);
        $this->assertInstanceOf(ImportTask::class, $task);

        // check if we can read it
        $this->assertFileExists($task->getDocuments()[0]->importFilename());

        $this->assertFileNotExists($task->storedFilename());
        $this->assertTrue($task->store());
        $this->assertFileExists($task->storedFilename());
    }

    public function testTaskSerialization()
    {
        $fn = 'hello.world.csv';
        $config = [
            'documents' => [
                [
                    'filename' => $fn,
                ]
            ]
        ];

        // create task
        $task = BaseTask::create(BaseTask::TASK_TYPE_IMPORT, $config);
        $this->assertSame($fn, $task->getDocuments()[0]->filename);

        $this->assertInstanceOf(ImportTask::class, $task);
        $serialized = $task->serialize();

        $unserialized = BaseTask::unserialize($serialized);
        $this->assertCount(1, $unserialized->getDocuments());
        $this->assertSame($fn, $unserialized->getDocuments()[0]->filename);

    }
}
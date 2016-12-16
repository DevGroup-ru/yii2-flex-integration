<?php

use DevGroup\FlexIntegration\abstractEntity\mappers\Replace;
use DevGroup\FlexIntegration\abstractEntity\mappers\TrimString;
use DevGroup\FlexIntegration\abstractEntity\mappers\Typecast;
use DevGroup\FlexIntegration\format\mappers\CSV;
use DevGroup\FlexIntegration\models\BaseTask;
use DevGroup\FlexIntegration\models\ImportTask;

class ImportTest extends BaseTest
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        parent::_before();
        $this->tester->haveFixtures([
            'categories' => [
                'class' => \DevGroup\FlexIntegration\Tests\fixtures\Category::className(),
                'dataFile' => __DIR__ . '/../data/categories.php',
            ],
            'products' => [
                'class' => \DevGroup\FlexIntegration\Tests\fixtures\Product::className(),
                'dataFile' => __DIR__ . '/../data/products.php',
            ],
            'bindings' => [
                'class' => \DevGroup\FlexIntegration\Tests\fixtures\ProductCategory::className(),
                'dataFile' => __DIR__ . '/../data/product_category.php',
            ]
        ]);
    }

    // tests
    public function testCSVImport()
    {
        $repository = $this->flex()->taskRepository;

                // prepare input document
        $filename = 'import-test1.csv';
        copy($this->getDataDir() . '/' . $filename, $repository->inputFilesLocation . '/' . $filename);

        $config = [
            'documents' => [[
                'filename' => $filename,
                'formatMapper' => [
                    'class' => CSV::class,
                    'delimiter' => ';',
                    'skipLinesFromTop' => 1,
                    'schema' => [
                        'defaultList' => [
                            'entities' => [
                                'product' => 'DotPlant\Store\models\goods\Product',
                            ],
                            'defaultEntity' => 'product',
                            'defaultMappers' => [
                                TrimString::class,
                            ],
                            'columns' => [
                                0 => [
                                    'field' => 'sku',
                                    'type' => 'attribute',
                                    'asSearch' => 'sku',
                                    'skipRowOnEmptyValue' => true,
                                ],
                                1 => [
                                    'field' => 'name',
                                    'mappers' => [
                                        TrimString::class,
                                    ],
                                ],
                                2 => [
                                    'field' => 'price',
                                    'type' => 'attribute',
                                    'mappers' => [
                                        TrimString::class,
                                        [
                                            'class' => Replace::class,
                                            'search' => ',',
                                            'replace' => '.',
                                        ],
                                        [
                                            'class' => Replace::class,
                                            'search' => '/[^0-9\.]/i',
                                            'replace' => '',
                                            'isRegExp' => true,
                                        ],
                                        Typecast::class,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]],
        ];

        // create task
        $task = BaseTask::create(BaseTask::TASK_TYPE_IMPORT, $config);
        $this->assertInstanceOf(ImportTask::class, $task);

        // check if we can read it
        $task->run();
    }
}
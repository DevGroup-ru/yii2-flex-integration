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

        $taskConfig = [
            'documents' => [[
                'filename' => $filename,
                'formatMapper' => [
                    'class' => CSV::class,
                    'delimiter' => ';',
                    'skipLinesFromTop' => 1,
                    'schema' => [
                        'defaultList' => [
                            'entities' => [
                                'DotPlant\Store\models\goods\Product' => 'product',
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
                                    'asDocumentScopeId' => true,
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
        $task = BaseTask::create(BaseTask::TASK_TYPE_IMPORT, $taskConfig);
        $this->assertInstanceOf(ImportTask::class, $task);

        // first - check every step
        $doc = $task->documents[0];
        $entities = $task->mapDoc($doc);
        $this->assertCount(4, $entities);
        codecept_debug($entities);

        // check reduce to collections
        $collections = [];
        $task->reduceDoc($doc, $entities, $collections);
        codecept_debug($collections);

        $this->assertArrayHasKey('product', $collections);
        // why 3? We have default on duplicate action = SKIP
        $this->assertCount(3, $collections['product']->entities);

    }
}
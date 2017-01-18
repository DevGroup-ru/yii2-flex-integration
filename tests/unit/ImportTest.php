<?php

use DevGroup\FlexIntegration\abstractEntity\mappers\Replace;
use DevGroup\FlexIntegration\abstractEntity\mappers\TrimString;
use DevGroup\FlexIntegration\abstractEntity\mappers\Typecast;
use DevGroup\FlexIntegration\abstractEntity\mappers\UppercaseString;
use DevGroup\FlexIntegration\base\MappableColumn;
use DevGroup\FlexIntegration\format\mappers\CSV;
use DevGroup\FlexIntegration\models\BaseTask;
use DevGroup\FlexIntegration\models\ImportTask;
use DevGroup\FlexIntegration\Tests\models\Product;

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
        $filename2 = 'import-test1_categories.csv';
        copy($this->getDataDir() . '/' . $filename2, $repository->inputFilesLocation . '/' . $filename2);


        $taskConfig = [
            'documents' => [
                0 => [
                    'filename' => $filename,
                    'formatMapper' => [
                        'class' => CSV::class,
                        'delimiter' => ';',
                        'skipLinesFromTop' => 1,
                        'schema' => [
                            'defaultList' => [
                                'entities' => [
                                    'product' => [
                                        'class' => 'DevGroup\FlexIntegration\Tests\models\Product',
                                    ],
                                ],
                                'defaultEntity' => 'product',
                                'defaultMappers' => [
                                    TrimString::class,
                                ],
                                'columns' => [
                                    0 => [
                                        'field' => 'sku',
                                        'type' => MappableColumn::TYPE_ATTRIBUTE,
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
                                        'type' => MappableColumn::TYPE_ATTRIBUTE,
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
                                    3 => [
                                        'field' => 'categories',
                                        'type' => MappableColumn::TYPE_RELATION,
                                        'mappers' => [
                                            // find by attribute?
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                1 => [
                    'filename' => $filename2,
                    'formatMapper' => [
                        'class' => CSV::class,
                        'delimiter' => ';',
                        'skipLinesFromTop' => 1,
                        'schema' => [
                            'defaultList' => [
                                'entities' => [
                                    'category' => [
                                        'class' => 'DevGroup\FlexIntegration\Tests\models\Category',
                                    ],
                                ],
                                'defaultEntity' => 'category',
                                'defaultMappers' => [
                                    TrimString::class,
                                ],
                                'columns' => [
                                    0 => [
                                        'asPk' => true,
                                        'field' => 'id',
                                    ],
                                    1 => [
                                        'field' => 'name',
                                        'mappers' => [
                                            TrimString::class,
                                            UppercaseString::class,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            ],
        ];

        // create task
        $task = BaseTask::create(BaseTask::TASK_TYPE_IMPORT, $taskConfig);
        $this->assertInstanceOf(ImportTask::class, $task);

        // first - check every step
        $doc = $task->documents[0];
        $entities = $task->mapDoc($doc);
        $this->assertCount(5, $entities);

        // check reduce to collections
        $collections = [];
        $task->reduceDoc($doc, $entities, $collections);

        $docCategories = $task->documents[1];
        $entities = $task->mapDoc($docCategories);
        $this->assertCount(3, $entities);
        $task->reduceDoc($docCategories, $entities, $collections);

        $this->assertArrayHasKey('product', $collections);
        $this->assertArrayHasKey('category', $collections);
        // why 4? We have default on duplicate action = SKIP and one duplicated row in csv file
        $this->assertCount(4, $collections['product']->entities);
        $this->assertCount(3, $collections['category']->entities);

        // check non prioritized order
        $this->assertSame(['product', 'category'], array_keys($collections));
        $collections = $task->prioritizeCollections($collections);
        // check prioritized order
        $this->assertSame(['category', 'product'], array_keys($collections));
    }
}
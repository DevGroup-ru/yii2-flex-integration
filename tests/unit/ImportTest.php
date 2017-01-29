<?php

use DevGroup\EntitySearch\base\BaseSearch;
use DevGroup\EntitySearch\response\ResultResponse;
use DevGroup\FlexIntegration\abstractEntity\mappers\Replace;
use DevGroup\FlexIntegration\abstractEntity\mappers\TrimString;
use DevGroup\FlexIntegration\abstractEntity\mappers\Typecast;
use DevGroup\FlexIntegration\abstractEntity\mappers\UppercaseString;
use DevGroup\FlexIntegration\abstractEntity\preProcessors\RelationFinder;
use DevGroup\FlexIntegration\base\MappableColumn;
use DevGroup\FlexIntegration\format\mappers\CSV;
use DevGroup\FlexIntegration\format\reducers\DefaultReducer;
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

    public function testBaseSearch()
    {
        /** @var BaseSearch $searcher */
        /** @var ResultResponse $response */
        $searcher = Yii::$app->get('search');

        $query = $searcher->search(Product::class)
            ->relationAttributes([
                'categories' => [
                    'id' => [1]
                ]
            ])
            ->order(['id' => SORT_ASC]);
        $response = $query->ids();
        $this->assertSame(['1', '2'], $response->ids);

        $response = $query
            ->relationAttributes([
                'categories' => [
                    'id' => 2
                ],
            ])->ids();
        $this->assertSame(['1', '3'], $response->ids);

        $response = $query
            ->relationAttributes([
                'categories' => [
                    'id' => [1,2]
                ],
            ])->ids();
        $this->assertSame(['1', '2', '3'], $response->ids);

    }

    protected function createTask()
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
                                                'search' => '/[^0-9\\.]/i',
                                                'replace' => '',
                                                'isRegExp' => true,
                                            ],
                                            Typecast::class,
                                        ],
                                    ],
                                    3 => [
                                        'field' => 'categories',
                                        'type' => MappableColumn::TYPE_RELATION,
                                        'relationFinder' => [
                                            'class' => RelationFinder::class,
                                            'findByAttribute' => 'name',
                                            'relationName' => 'categories',
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'formatReducer' => [
                        'class' => DefaultReducer::class,
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
//                                            UppercaseString::class,
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

        return $task;
    }

    // tests
    public function testCSVImport()
    {
        $task = $this->createTask();

        // first - check every step
        $doc = $task->documents[0];
        $entities = $task->mapDoc($doc, 0);
        $this->assertCount(5, $entities);

        // check reduce to collections
        $collections = [];
        $task->reduceDoc($doc, $entities, $collections);

        $docCategories = $task->documents[1];
        $entities = $task->mapDoc($docCategories, 1);
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

        codecept_debug($collections['product']);
    }

    public function testWholeProcess()
    {
        $db = Yii::$app->db;

        /** @var Product $exs */
        $exs = Product::findOne(['sku'=>'EXS-123']);
        $this->assertCount(2, $exs->categories);
        $this->assertCategoriesInList($exs, [1,2]);

        $productsRows = $db->createCommand('select * from {{%product}} order by id asc')->queryAll();
        $this->assertCount(3, $productsRows);
        $task = $this->createTask();
        // run the whole process
        $task->run();

        $productsRows = $db->createCommand('select * from {{%product}} order by id asc')->queryAll();
        $this->assertCount(6, $productsRows);
        $this->assertEquals(
            [
                'id' => '1',
                'name' => 'tres',
                'sku' => 'EXS-123',
                'price' => 1789.99,
            ],
            $productsRows[0]
        );

        $this->assertEquals(
            [
                'id' => '4',
                'name' => 'product uno',
                'sku' => 'p1',
                'price' => 1.9,
            ],
            $productsRows[3]
        );

        $this->assertEquals(
            [
                'id' => '5',
                'name' => 'product dos',
                'sku' => 'p2',
                'price' => 1.1,
            ],
            $productsRows[4]
        );

        $productWith2Cats = Product::find()->with('categories')->where(['name' => 'Product with 2 cats'])->one();
        $this->assertInstanceOf(Product::class, $productWith2Cats);
        $this->assertEquals(
            [
                'id' => 6,
                'sku' => 'p5',
                'name' => 'Product with 2 cats',
                'price' => 1.83,
            ],
            $productWith2Cats->attributes
        );
        $categories = $productWith2Cats->categories;
        $this->assertCount(2, $categories);
        $this->assertCategoriesInList($productWith2Cats, [1, 2]);

        $productWithoutCats = Product::find()->where(['sku'=>'p1'])->one();
        $this->assertInstanceOf(Product::class, $productWithoutCats);
        $this->assertCount(0, $productWithoutCats->categories);

        $exs = Product::findOne(['sku'=>'EXS-123']);
        $this->assertCount(0, $exs->categories);

        $this->assertNull(Product::findOne(['name' => 'empty sku']));
        /** @var Product $p2 */
        $p2 = Product::findOne(['sku' => 'p2']);
        $this->assertCount(1, $p2->categories);
        $this->assertCategoriesInList($p2, [1]);
    }

    protected function assertCategoriesInList($model, $categories)
    {
        foreach ($model->categories as $cat) {
            $this->assertTrue(
                in_array($cat->id, [1, 2]),
                "Product $model->id categories not matching list of " . implode(',', $categories)
            );
        }
    }
}
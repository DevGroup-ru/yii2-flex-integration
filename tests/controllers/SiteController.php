<?php

namespace DevGroup\FlexIntegration\Tests\controllers;

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
use Yii;
use yii\web\Controller;

class SiteController extends Controller
{
    public function actionIndex()
    {
        $repository = Yii::$app->getModule('flex')->taskRepository;

        // prepare input document
        $filename = 'import-test1.csv';
        copy(__DIR__ . '/../data/' . $filename, $repository->inputFilesLocation . '/' . $filename);
        $filename2 = 'import-test1_categories.csv';
        copy(__DIR__ . '/../data/' . $filename2, $repository->inputFilesLocation . '/' . $filename2);


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
                    'entitiesPreProcessors' => [
                        'product' => [
                            0 => [
                                'class' => RelationFinder::class,
                                'findByAttribute' => 'name',
                                'relationName' => 'categories',
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
        $task->run();
    }
}

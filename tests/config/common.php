<?php

use yii\helpers\ArrayHelper;

$config = [
    'id' => 'app-test-console',
    'basePath' => dirname(__DIR__),
    'language' => 'en-US',
    'modules' => [
        'flex' => [
            'class' => 'DevGroup\FlexIntegration\FlexIntegrationModule',
        ],
    ],
];

if (file_exists('common.local.php')) {
    $config = ArrayHelper::merge($config, include('common.local.php'));
}

return $config;
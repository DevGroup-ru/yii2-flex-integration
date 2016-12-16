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
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=yii2_flexintegration',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
        ],
    ],
];

if (file_exists(__DIR__ . '/common.local.php')) {
    $config = ArrayHelper::merge($config, include(__DIR__ . '/common.local.php'));
}

return $config;
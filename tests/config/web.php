<?php

use yii\helpers\ArrayHelper;

$config = [
    'controllerNamespace' => 'DevGroup\FlexIntegration\Tests\controllers',
];

// merge common config
$config = ArrayHelper::merge($config, include(__DIR__ . '/common.php'));

if (file_exists(__DIR__ . '/web.local.php')) {
    $config = ArrayHelper::merge($config, include(__DIR__ . '/web.local.php'));
}

return $config;
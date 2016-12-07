<?php

use yii\helpers\ArrayHelper;

$config = [
    'id' => 'app-test-console',
];

// merge common config
$config = ArrayHelper::merge(include('common.php'), $config);

if (file_exists('console.local.php')) {
    $config = ArrayHelper::merge($config, include('console.local.php'));
}

return $config;
<?php

use yii\helpers\ArrayHelper;

$config = [

];

// merge common config
$config = ArrayHelper::merge(include(__DIR__ . '/common.php'), $config);

if (file_exists(__DIR__ . '/console.local.php')) {
    $config = ArrayHelper::merge($config, include(__DIR__ . '/console.local.php'));
}

return $config;
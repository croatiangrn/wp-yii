<?php
/**
 * @author: Marko Mikulic
 */

require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/src/rnd/Rnd.php');

$config = [
    'id'         => 'My App',
    'name'       => get_bloginfo('name'),
    'bootstrap'  => ['log'],
    'components' => [
        'log' => [
            'flushInterval' => 1,
            'traceLevel' => 3,
            'targets' => [
                'file'  => [
                    'class'   => 'rnd\log\FileTarget',
                    'levels'  => ['error', 'warning'],
                    'exportInterval' => 1
                ],
            ],
        ],
    ]
];

$config = \rnd\helpers\ArrayHelper::merge(
    require(__DIR__ . '/src/rnd/config.php'),
    $config
);

try {
    (new rnd\web\Application($config));
} catch (\rnd\base\InvalidConfigException $e) {
    echo 'Call administrator!';
    die;
}

// USE THIS FOR PRODUCTION!
/*

require('vendor/autoload.php');
require ('vendor/croatiangrn/wp-yii/src/rnd/Rnd.php');

$config = [
	'id' => 'My App',
	'name' => get_bloginfo('name'),
];
$config = \rnd\helpers\ArrayHelper::merge(
	require(__DIR__ . '/vendor/croatiangrn/wp-yii/src/rnd/config.php'),
	$config
);

(new rnd\web\Application($config));

*/
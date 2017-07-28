<?php
/**
 * @author: Marko Mikulic
 */

require ( __DIR__ . '/vendor/autoload.php');
require (__DIR__ . '/src/rnd/Rnd.php');

$config = [
	'id' => 'My App',
	'name' => get_bloginfo('name'),
];
$config = \rnd\helpers\ArrayHelper::merge(
	require(__DIR__ . '/src/rnd/config.php'),
	$config
);

(new rnd\web\Application($config));

(new \rnd\assets\AppAsset());

// USE THIS FOR PRODUCTION!
/*

require('vendor/autoload.php');
require ('vendor/croatiangrn/wp-yii/src/rnd/Rnd.php');

$config = [
	'id' => 'My App',
	'name' => get_bloginfo('name'),
];
$config = \rnd\helpers\ArrayHelper::merge(
	require(__DIR__ . '/src/App/wp-yii/src/rnd/config.php'),
	$config
);

(new rnd\web\Application($config));

*/
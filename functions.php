<?php
/**
 * @author: Marko Mikulic
 */

require ( __DIR__ . '/vendor/autoload.php');
require (__DIR__ . '/src/rnd/Rnd.php');

$config = [
	'id' => 'My App',
	'name' => get_bloginfo('name'),
	'components' => [
    ]
	/*'aliases' => [
		'@app' => __DIR__
	],
	'components' => [
		'i18n' => [
			'translations' => [
				'app*' => [
					'class' => 'rnd\i18n\PhpMessageSource',
					'sourceLanguage' => 'en-US',
					'basePath' => '@app/messages',
				]
			]
		]
	]*/
];
$config = \rnd\helpers\ArrayHelper::merge(
	require(__DIR__ . '/src/rnd/config.php'),
	$config
);

(new rnd\web\Application($config));

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
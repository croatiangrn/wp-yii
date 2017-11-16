<?php
/**
 * Default configs for the app
 * @author: Marko Mikulic
 */

return [
	'id' => 'app',
	'basePath' => dirname(__DIR__),
	'components' => [
		'i18n' => [
			'translations' => [
				'app*' => [
					'class' => 'rnd\i18n\PhpMessageSource',
					//'basePath' => '@app/messages',
					//'sourceLanguage' => 'en-US',
					'fileMap' => [
						'app' => 'app.php',
						'app/error' => 'error.php',
					],
					'forceTranslation' => true
				],
			],
		],
	],

];
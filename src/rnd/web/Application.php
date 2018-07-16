<?php

/**
 * @author: Marko Mikulic
 */

namespace rnd\web;


use Rnd;
use rnd\elasticmailer\Mailer;
use rnd\widgets\Device;
use rnd\widgets\ReCaptcha;
use rnd\widgets\User;

/**
 * @property ReCaptcha reCaptcha
 * @property Mailer elasticMailer
 */
class Application extends \rnd\base\Application
{
	/**
	 * @var array the configuration specifying a controller action which should handle
	 * all user requests. This is mainly used when the application is in maintenance mode
	 * and needs to handle all incoming requests via a single action.
	 * The configuration is an array whose first element specifies the route of the action.
	 * The rest of the array elements (key-value pairs) specify the parameters to be bound
	 * to the action. For example,
	 *
	 * ```php
	 * [
	 *     'offline/notice',
	 *     'param1' => 'value1',
	 *     'param2' => 'value2',
	 * ]
	 * ```
	 *
	 * Defaults to null, meaning catch-all is not used.
	 */
	public $catchAll;
	/**
	 * @var Controller the currently active controller instance
	 */
	public $controller;

	/**
	 * @inheritdoc
	 */
	protected function bootstrap()
	{
		$request = $this->getRequest();
		Rnd::setAlias('@webroot', dirname($request->getScriptFile()));
		Rnd::setAlias('@themeroot', get_template_directory());
		Rnd::setAlias('@themeurl', get_stylesheet_directory_uri());
		Rnd::setAlias('@approot', '@themeroot/src/App');
		Rnd::setAlias('@web', $request->getBaseUrl());

		$this->setLanguage();
	}

	protected function setLanguage()
	{
		if (function_exists( 'pll_current_language')) {
			$this->language = pll_current_language();
		}
	}


	private $_homeUrl;

	/**
	 * @return string the homepage URL
	 */
	public function getHomeUrl()
	{
		return get_home_url();
	}

	/**
	 * @param string $value the homepage URL
	 */
	public function setHomeUrl(string $value)
	{
		$this->_homeUrl = $value;
	}

	/**
	 * Returns the error handler component.
	 * @return ErrorHandler the error handler application component.
	 */
	public function getErrorHandler()
	{
		return $this->get('errorHandler');
	}

	/**
	 * Returns the request component.
	 * @return Request the request component.
	 */
	public function getRequest()
	{
		return $this->get('request');
	}

	/**
	 * Returns the response component.
	 * @return Response the response component.
	 */
	public function getResponse()
	{
		return $this->get('response');
	}

	/**
	 * Returns the session component.
	 * @return Session the session component.
	 */
	public function getSession()
	{
		return $this->get('session');
	}

	/**
	 * Returns device component
	 * @return Device
	 */
	public function getDevice()
	{
		return $this->get('device');
	}

	/**
	 * @inheritdoc
	 */
	public function coreComponents()
	{
		return array_merge(parent::coreComponents(), [
			'request' => ['class' => 'rnd\web\Request'],
			'response' => ['class' => 'rnd\web\Response'],
			'session' => ['class' => 'rnd\web\Session'],
			'device' => ['class' => 'rnd\widgets\Device']
		]);
	}
}
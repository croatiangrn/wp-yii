<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\web;


use Rnd;

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
		Rnd::setAlias('@approot', '@themeroot/src/app');
		Rnd::setAlias('@web', $request->getBaseUrl());
	}

	private $_homeUrl;

	/**
	 * @return string the homepage URL
	 */
	public function getHomeUrl()
	{
		if ($this->_homeUrl === null) {
			return $this->getRequest()->getBaseUrl() . '/';
		} else {
			return $this->_homeUrl;
		}
	}

	/**
	 * @param string $value the homepage URL
	 */
	public function setHomeUrl($value)
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
	 * Returns the user component.
	 * @return User the user component.
	 */
	public function getUser()
	{
		return $this->get('user');
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
		]);
	}
}
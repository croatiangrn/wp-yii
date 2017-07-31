<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\base;


use Rnd;

abstract class Application extends Module
{
	/**
	 * @var string the application name.
	 */
	public $name = 'My Application';
	/**
	 * @var string the charset currently used for the application.
	 */
	public $charset = 'UTF-8';
	/**
	 * @var string the language that is meant to be used for end users. It is recommended that you
	 * use [IETF language tags](http://en.wikipedia.org/wiki/IETF_language_tag). For example, `en` stands
	 * for English, while `en-US` stands for English (United States).
	 * @see sourceLanguage
	 */
	public $language = 'en-US';
	/**
	 * @var string the language that the application is written in. This mainly refers to
	 * the language that the messages and view files are written in.
	 * @see language
	 */
	public $sourceLanguage = 'en-US';
	/**
	 * @var array list of components that should be run during the application [[bootstrap()|bootstrapping process]].
	 *
	 * Each component may be specified in one of the following formats:
	 *
	 * - an application component ID as specified via [[components]].
	 * - a module ID as specified via [[modules]].
	 * - a class name.
	 * - a configuration array.
	 *
	 * During the bootstrapping process, each component will be instantiated. If the component class
	 * implements [[BootstrapInterface]], its [[BootstrapInterface::bootstrap()|bootstrap()]] method
	 * will be also be called.
	 */
	public $bootstrap = [];
	/**
	 * @var array list of loaded modules indexed by their class names.
	 */
	public $loadedModules = [];

	/**
	 * Constructor.
	 * @param array $config name-value pairs that will be used to initialize the object properties.
	 * Note that the configuration must contain both [[id]] and [[basePath]].
	 * @throws InvalidConfigException if either [[id]] or [[basePath]] configuration is missing.
	 */
	public function __construct($config = [])
	{
		Rnd::$app = $this;
		static::setInstance($this);

		$this->preInit($config);

		Component::__construct($config);
	}

	/**
	 * Pre-initializes the application.
	 * This method is called at the beginning of the application constructor.
	 * It initializes several important application properties.
	 * If you override this method, please make sure you call the parent implementation.
	 * @param array $config the application configuration
	 * @throws InvalidConfigException if either [[id]] or [[basePath]] configuration is missing.
	 */
	public function preInit(&$config)
	{
		if (!isset($config['id'])) {
			throw new InvalidConfigException('The "id" configuration for the Application is required.');
		}
		if (isset($config['basePath'])) {
			$this->setBasePath($config['basePath']);
			unset($config['basePath']);
		} else {
			throw new InvalidConfigException('The "basePath" configuration for the Application is required.');
		}

		if (isset($config['vendorPath'])) {
			$this->setVendorPath($config['vendorPath']);
			unset($config['vendorPath']);
		} else {
			// set "@vendor"
			$this->getVendorPath();
		}
		if (isset($config['timeZone'])) {
			$this->setTimeZone($config['timeZone']);
			unset($config['timeZone']);
		} elseif (!ini_get('date.timezone')) {
			$this->setTimeZone('UTC');
		}

		if (isset($config['container'])) {
			$this->setContainer($config['container']);

			unset($config['container']);
		}

		// merge core components with custom components
		foreach ($this->coreComponents() as $id => $component) {
			if (!isset($config['components'][$id])) {
				$config['components'][$id] = $component;
			} elseif (is_array($config['components'][$id]) && !isset($config['components'][$id]['class'])) {
				$config['components'][$id]['class'] = $component['class'];
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		$this->bootstrap();
	}

	/**
	 * Initializes extensions and executes bootstrap components.
	 * This method is called by [[init()]] after the application has been fully configured.
	 * If you override this method, make sure you also call the parent implementation.
	 */
	protected function bootstrap()
	{
		foreach ($this->bootstrap as $class) {
			$component = null;
			if (is_string($class)) {
				if ($this->has($class)) {
					$component = $this->get($class);
				} elseif ($this->hasModule($class)) {
					$component = $this->getModule($class);
				} elseif (strpos($class, '\\') === false) {
					throw new InvalidConfigException("Unknown bootstrapping component ID: $class");
				}
			}
			if (!isset($component)) {
				$component = Rnd::createObject($class);
			}

			if ($component instanceof BootstrapInterface) {
				$component->bootstrap($this);
			}
		}
	}

	private $_vendorPath;

	/**
	 * Returns the directory that stores vendor files.
	 * @return string the directory that stores vendor files.
	 * Defaults to "vendor" directory under [[basePath]].
	 */
	public function getVendorPath()
	{
		if ($this->_vendorPath === null) {
			$this->setVendorPath($this->getBasePath() . DIRECTORY_SEPARATOR . 'vendor');
		}

		return $this->_vendorPath;
	}

	/**
	 * Sets the directory that stores vendor files.
	 * @param string $path the directory that stores vendor files.
	 */
	public function setVendorPath($path)
	{
		$this->_vendorPath = Rnd::getAlias($path);
		Rnd::setAlias('@vendor', $this->_vendorPath);
		Rnd::setAlias('@npm', $this->_vendorPath . DIRECTORY_SEPARATOR . 'npm');
	}

	/**
	 * Returns the time zone used by this application.
	 * This is a simple wrapper of PHP function date_default_timezone_get().
	 * If time zone is not configured in php.ini or application config,
	 * it will be set to UTC by default.
	 * @return string the time zone used by this application.
	 * @see http://php.net/manual/en/function.date-default-timezone-get.php
	 */
	public function getTimeZone()
	{
		return date_default_timezone_get();
	}

	/**
	 * Sets the time zone used by this application.
	 * This is a simple wrapper of PHP function date_default_timezone_set().
	 * Refer to the [php manual](http://www.php.net/manual/en/timezones.php) for available timezones.
	 * @param string $value the time zone used by this application.
	 * @see http://php.net/manual/en/function.date-default-timezone-set.php
	 */
	public function setTimeZone($value)
	{
		date_default_timezone_set($value);
	}

	/**
	 * Configures [[Rnd::$container]] with the $config
	 *
	 * @param array $config values given in terms of name-value pairs
	 * @since 2.0.11
	 */
	public function setContainer($config)
	{
		Rnd::configure(Rnd::$container, $config);
	}

	/**
	 * Returns the configuration of core application components.
	 * @see set()
	 */
	public function coreComponents()
	{
		return [
			'formatter' => ['class' => 'rnd\i18n\Formatter'],
			'i18n' => ['class' => 'rnd\i18n\I18N'],
		];
	}

	/**
	 * Returns the formatter component.
	 * @return \rnd\i18n\Formatter the formatter application component.
	 */
	public function getFormatter()
	{
		return $this->get('formatter');
	}

	/**
	 * Returns the request component.
	 * @return \rnd\web\Request
	 */
	public function getRequest()
	{
		return $this->get('request');
	}

	/**
	 * Returns the response component.
	 * @return \rnd\web\Response
	 */
	public function getResponse()
	{
		return $this->get('response');
	}
}
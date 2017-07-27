<?php
namespace rnd;

/**
 * Gets the application start timestamp.
 */
use rnd\base\InvalidConfigException;
use rnd\base\InvalidParamException;
use rnd\base\UnknownClassException;

defined('RND_BEGIN_TIME') or define('RND_BEGIN_TIME', microtime(true));
/**
 * This constant defines the framework installation directory.
 */
defined('RND2_PATH') or define('RND2_PATH', __DIR__);
/**
 * This constant defines whether the application should be in debug mode or not. Defaults to false.
 */
defined('RND_DEBUG') or define('RND_DEBUG', false);
/**
 * This constant defines in which environment the application is running. Defaults to 'prod', meaning production environment.
 * You may define this constant in the bootstrap script. The value could be 'prod' (production), 'dev' (development), 'test', 'staging', etc.
 */
defined('RND_ENV') or define('RND_ENV', 'prod');
/**
 * Whether the the application is running in production environment
 */
defined('RND_ENV_PROD') or define('RND_ENV_PROD', RND_ENV === 'prod');
/**
 * Whether the the application is running in development environment
 */
defined('RND_ENV_DEV') or define('RND_ENV_DEV', RND_ENV === 'dev');
/**
 * Whether the the application is running in testing environment
 */
defined('RND_ENV_TEST') or define('RND_ENV_TEST', RND_ENV === 'test');

/**
 * This constant defines whether error handling should be enabled. Defaults to true.
 */
defined('RND_ENABLE_ERROR_HANDLER') or define('RND_ENABLE_ERROR_HANDLER', true);


class BaseRnd
{
	/**
	 * @var array class map used by the Rnd autoloading mechanism.
	 * The array keys are the class names (without leading backslashes), and the array values
	 * are the corresponding class file paths (or [path aliases](guide:concept-aliases)). This property mainly affects
	 * how [[autoload()]] works.
	 * @see autoload()
	 */
	public static $classMap = [];
	/**
	 * @var \rnd\web\Application the application instance
	 */
	public static $app;
	/**
	 * @var array registered path aliases
	 * @see getAlias()
	 * @see setAlias()
	 */
	public static $aliases = ['@rnd' => __DIR__];
	/**
	 * @var Container the dependency injection (DI) container used by [[createObject()]].
	 * You may use [[Container::set()]] to set up the needed dependencies of classes and
	 * their initial property values.
	 * @see createObject()
	 * @see Container
	 */
	public static $container;
	/**
	 * Translates a path alias into an actual path.
	 *
	 * The translation is done according to the following procedure:
	 *
	 * 1. If the given alias does not start with '@', it is returned back without change;
	 * 2. Otherwise, look for the longest registered alias that matches the beginning part
	 *    of the given alias. If it exists, replace the matching part of the given alias with
	 *    the corresponding registered path.
	 * 3. Throw an exception or return false, depending on the `$throwException` parameter.
	 *
	 * For example, by default '@rnd' is registered as the alias to the Yii framework directory,
	 * say '/path/to/yii'. The alias '@rnd/web' would then be translated into '/path/to/yii/web'.
	 *
	 * If you have registered two aliases '@foo' and '@foo/bar'. Then translating '@foo/bar/config'
	 * would replace the part '@foo/bar' (instead of '@foo') with the corresponding registered path.
	 * This is because the longest alias takes precedence.
	 *
	 * However, if the alias to be translated is '@foo/barbar/config', then '@foo' will be replaced
	 * instead of '@foo/bar', because '/' serves as the boundary character.
	 *
	 * Note, this method does not check if the returned path exists or not.
	 *
	 * See the [guide article on aliases](guide:concept-aliases) for more information.
	 *
	 * @param string $alias the alias to be translated.
	 * @param bool $throwException whether to throw an exception if the given alias is invalid.
	 * If this is false and an invalid alias is given, false will be returned by this method.
	 * @return string|bool the path corresponding to the alias, false if the root alias is not previously registered.
	 * @throws InvalidParamException if the alias is invalid while $throwException is true.
	 * @see setAlias()
	 */
	public static function getAlias($alias, $throwException = true)
	{
		if (strncmp($alias, '@', 1)) {
			// not an alias
			return $alias;
		}

		$pos = strpos($alias, '/');
		$root = $pos === false ? $alias : substr($alias, 0, $pos);

		if (isset(static::$aliases[$root])) {
			if (is_string(static::$aliases[$root])) {
				return $pos === false ? static::$aliases[$root] : static::$aliases[$root] . substr($alias, $pos);
			}

			foreach (static::$aliases[$root] as $name => $path) {
				if (strpos($alias . '/', $name . '/') === 0) {
					return $path . substr($alias, strlen($name));
				}
			}
		}

		if ($throwException) {
			throw new InvalidParamException("Invalid path alias: $alias");
		}

		return false;
	}

	/**
	 * Returns the root alias part of a given alias.
	 * A root alias is an alias that has been registered via [[setAlias()]] previously.
	 * If a given alias matches multiple root aliases, the longest one will be returned.
	 * @param string $alias the alias
	 * @return string|bool the root alias, or false if no root alias is found
	 */
	public static function getRootAlias($alias)
	{
		$pos = strpos($alias, '/');
		$root = $pos === false ? $alias : substr($alias, 0, $pos);

		if (isset(static::$aliases[$root])) {
			if (is_string(static::$aliases[$root])) {
				return $root;
			}

			foreach (static::$aliases[$root] as $name => $path) {
				if (strpos($alias . '/', $name . '/') === 0) {
					return $name;
				}
			}
		}

		return false;
	}

	/**
	 * Registers a path alias.
	 *
	 * A path alias is a short name representing a long path (a file path, a URL, etc.)
	 * For example, we use '@rnd' as the alias of the path to the Yii framework directory.
	 *
	 * A path alias must start with the character '@' so that it can be easily differentiated
	 * from non-alias paths.
	 *
	 * Note that this method does not check if the given path exists or not. All it does is
	 * to associate the alias with the path.
	 *
	 * Any trailing '/' and '\' characters in the given path will be trimmed.
	 *
	 * See the [guide article on aliases](guide:concept-aliases) for more information.
	 *
	 * @param string $alias the alias name (e.g. "@rnd"). It must start with a '@' character.
	 * It may contain the forward slash '/' which serves as boundary character when performing
	 * alias translation by [[getAlias()]].
	 * @param string $path the path corresponding to the alias. If this is null, the alias will
	 * be removed. Trailing '/' and '\' characters will be trimmed. This can be
	 *
	 * - a directory or a file path (e.g. `/tmp`, `/tmp/main.txt`)
	 * - a URL (e.g. `http://www.sparkbp.com`)
	 * - a path alias (e.g. `@rnd/base`). In this case, the path alias will be converted into the
	 *   actual path first by calling [[getAlias()]].
	 *
	 * @throws InvalidParamException if $path is an invalid alias.
	 * @see getAlias()
	 */
	public static function setAlias($alias, $path)
	{
		if (strncmp($alias, '@', 1)) {
			$alias = '@' . $alias;
		}
		$pos = strpos($alias, '/');
		$root = $pos === false ? $alias : substr($alias, 0, $pos);
		if ($path !== null) {
			$path = strncmp($path, '@', 1) ? rtrim($path, '\\/') : static::getAlias($path);
			if (!isset(static::$aliases[$root])) {
				if ($pos === false) {
					static::$aliases[$root] = $path;
				} else {
					static::$aliases[$root] = [$alias => $path];
				}
			} elseif (is_string(static::$aliases[$root])) {
				if ($pos === false) {
					static::$aliases[$root] = $path;
				} else {
					static::$aliases[$root] = [
						$alias => $path,
						$root => static::$aliases[$root],
					];
				}
			} else {
				static::$aliases[$root][$alias] = $path;
				krsort(static::$aliases[$root]);
			}
		} elseif (isset(static::$aliases[$root])) {
			if (is_array(static::$aliases[$root])) {
				unset(static::$aliases[$root][$alias]);
			} elseif ($pos === false) {
				unset(static::$aliases[$root]);
			}
		}
	}

	/**
	 * Class autoload loader.
	 * This method is invoked automatically when PHP sees an unknown class.
	 * The method will attempt to include the class file according to the following procedure:
	 *
	 * 1. Search in [[classMap]];
	 * 2. If the class is namespaced (e.g. `yii\base\Component`), it will attempt
	 *    to include the file associated with the corresponding path alias
	 *    (e.g. `@rnd/base/Component.php`);
	 *
	 * This autoloader allows loading classes that follow the [PSR-4 standard](http://www.php-fig.org/psr/psr-4/)
	 * and have its top-level namespace or sub-namespaces defined as path aliases.
	 *
	 * Example: When aliases `@rnd` and `@rnd/bootstrap` are defined, classes in the `yii\bootstrap` namespace
	 * will be loaded using the `@rnd/bootstrap` alias which points to the directory where bootstrap extension
	 * files are installed and all classes from other `yii` namespaces will be loaded from the yii framework directory.
	 *
	 * Also the [guide section on autoloading](guide:concept-autoloading).
	 *
	 * @param string $className the fully qualified class name without a leading backslash "\"
	 * @throws UnknownClassException if the class does not exist in the class file
	 */
	public static function autoload($className)
	{
		if (isset(static::$classMap[$className])) {
			$classFile = static::$classMap[$className];
			if ($classFile[0] === '@') {
				$classFile = static::getAlias($classFile);
			}
		} elseif (strpos($className, '\\') !== false) {
			$classFile = static::getAlias('@' . str_replace('\\', '/', $className) . '.php', false);
			if ($classFile === false || !is_file($classFile)) {
				return;
			}
		} else {
			return;
		}

		include($classFile);

		if (RND_DEBUG && !class_exists($className, false) && !interface_exists($className, false) && !trait_exists($className, false)) {
			throw new UnknownClassException("Unable to find '$className' in file: $classFile. Namespace missing?");
		}
	}

	/**
	 * Creates a new object using the given configuration.
	 *
	 * You may view this method as an enhanced version of the `new` operator.
	 * The method supports creating an object based on a class name, a configuration array or
	 * an anonymous function.
	 *
	 * Below are some usage examples:
	 *
	 * ```php
	 * // create an object using a class name
	 * $object = Rnd::createObject('rnd\db\Connection');
	 *
	 * // create an object using a configuration array
	 * $object = Rnd::createObject([
	 *     'class' => 'rnd\db\Connection',
	 *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
	 *     'username' => 'root',
	 *     'password' => '',
	 *     'charset' => 'utf8',
	 * ]);
	 *
	 * // create an object with two constructor parameters
	 * $object = \Rnd::createObject('MyClass', [$param1, $param2]);
	 * ```
	 *
	 * Using [[\rnd\di\Container|dependency injection container]], this method can also identify
	 * dependent objects, instantiate them and inject them into the newly created object.
	 *
	 * @param string|array|callable $type the object type. This can be specified in one of the following forms:
	 *
	 * - a string: representing the class name of the object to be created
	 * - a configuration array: the array must contain a `class` element which is treated as the object class,
	 *   and the rest of the name-value pairs will be used to initialize the corresponding object properties
	 * - a PHP callable: either an anonymous function or an array representing a class method (`[$class or $object, $method]`).
	 *   The callable should return a new instance of the object being created.
	 *
	 * @param array $params the constructor parameters
	 * @return object the created object
	 * @throws InvalidConfigException if the configuration is invalid.
	 * @see \rnd\di\Container
	 */
	public static function createObject($type, array $params = [])
	{
		if (is_string($type)) {
			return static::$container->get($type, $params);
		} elseif (is_array($type) && isset($type['class'])) {
			$class = $type['class'];
			unset($type['class']);
			return static::$container->get($class, $params, $type);
		} elseif (is_callable($type, true)) {
			return static::$container->invoke($type, $params);
		} elseif (is_array($type)) {
			throw new InvalidConfigException('Object configuration must be an array containing a "class" element.');
		}

		throw new InvalidConfigException('Unsupported configuration type: ' . gettype($type));
	}

	/**
	 * Configures an object with the initial property values.
	 * @param object $object the object to be configured
	 * @param array $properties the property initial values given in terms of name-value pairs.
	 * @return object the object itself
	 */
	public static function configure($object, $properties)
	{
		foreach ($properties as $name => $value) {
			$object->$name = $value;
		}

		return $object;
	}

	/**
	 * Returns the public member variables of an object.
	 * This method is provided such that we can get the public member variables of an object.
	 * It is different from "get_object_vars()" because the latter will return private
	 * and protected variables if it is called within the object itself.
	 * @param object $object the object to be handled
	 * @return array the public member variables of the object
	 */
	public static function getObjectVars($object)
	{
		return get_object_vars($object);
	}

	/**
	 * Returns current URL
	 *
	 * @return string
	 */
	public static function getCurrentUri()
	{
		return wp_get_canonical_url();
	}
}
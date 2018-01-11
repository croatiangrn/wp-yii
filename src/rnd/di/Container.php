<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\di;


use ReflectionClass;
use Rnd;
use rnd\base\Component;
use rnd\base\InvalidConfigException;
use rnd\helpers\ArrayHelper;

class Container extends Component
{
	/**
	 * @var array singleton objects indexed by their types
	 */
	private $_singletons = [];
	/**
	 * @var array object definitions indexed by their types
	 */
	private $_definitions = [];
	/**
	 * @var array constructor parameters indexed by object types
	 */
	private $_params = [];
	/**
	 * @var array cached ReflectionClass objects indexed by class/interface names
	 */
	private $_reflections = [];
	/**
	 * @var array cached dependencies indexed by class/interface names. Each class name
	 * is associated with a list of constructor parameter types or default values.
	 */
	private $_dependencies = [];
	/**
	 * Returns an instance of the requested class.
	 *
	 * You may provide constructor parameters (`$params`) and object configurations (`$config`)
	 * that will be used during the creation of the instance.
	 *
	 * If the class implements [[\yii\base\Configurable]], the `$config` parameter will be passed as the last
	 * parameter to the class constructor; Otherwise, the configuration will be applied *after* the object is
	 * instantiated.
	 *
	 * Note that if the class is declared to be singleton by calling [[setSingleton()]],
	 * the same instance of the class will be returned each time this method is called.
	 * In this case, the constructor parameters and object configurations will be used
	 * only if the class is instantiated the first time.
	 *
	 * @param string $class the class name or an alias name (e.g. `foo`) that was previously registered via [[set()]]
	 * or [[setSingleton()]].
	 * @param array $params a list of constructor parameter values. The parameters should be provided in the order
	 * they appear in the constructor declaration. If you want to skip some parameters, you should index the remaining
	 * ones with the integers that represent their positions in the constructor parameter list.
	 * @param array $config a list of name-value pairs that will be used to initialize the object properties.
	 * @return object an instance of the requested class.
	 * @throws InvalidConfigException if the class cannot be recognized or correspond to an invalid definition
	 * @throws NotInstantiableException If resolved to an abstract class or an interface (since 2.0.9)
	 */
	public function get($class, $params = [], $config = [])
	{
		if (isset($this->_singletons[$class])) {
			// singleton
			return $this->_singletons[$class];
		} elseif (!isset($this->_definitions[$class])) {
			return $this->build($class, $params, $config);
		}

		$definition = $this->_definitions[$class];

		if (is_callable($definition, true)) {
			$params = $this->resolveDependencies($this->mergeParams($class, $params));
			$object = call_user_func($definition, $this, $params, $config);
		} elseif (is_array($definition)) {
			$concrete = $definition['class'];
			unset($definition['class']);

			$config = array_merge($definition, $config);
			$params = $this->mergeParams($class, $params);

			if ($concrete === $class) {
				$object = $this->build($class, $params, $config);
			} else {
				$object = $this->get($concrete, $params, $config);
			}
		} elseif (is_object($definition)) {
			return $this->_singletons[$class] = $definition;
		} else {
			throw new InvalidConfigException('Unexpected object definition type: ' . gettype($definition));
		}

		if (array_key_exists($class, $this->_singletons)) {
			// singleton
			$this->_singletons[$class] = $object;
		}

		return $object;
	}

	/**
	 * Returns the dependencies of the specified class.
	 * @param string $class class name, interface name or alias name
	 * @return array the dependencies of the specified class.
	 */
	protected function getDependencies($class)
	{
		if (isset($this->_reflections[$class])) {
			return [$this->_reflections[$class], $this->_dependencies[$class]];
		}

		$dependencies = [];
		$reflection = new ReflectionClass($class);

		$constructor = $reflection->getConstructor();
		if ($constructor !== null) {
			foreach ($constructor->getParameters() as $param) {
				if ($param->isDefaultValueAvailable()) {
					$dependencies[] = $param->getDefaultValue();
				} else {
					$c = $param->getClass();
					$dependencies[] = Instance::of($c === null ? null : $c->getName());
				}
			}
		}

		$this->_reflections[$class] = $reflection;
		$this->_dependencies[$class] = $dependencies;

		return [$reflection, $dependencies];
	}

	/**
	 * Resolves dependencies by replacing them with the actual object instances.
	 * @param array $dependencies the dependencies
	 * @param ReflectionClass $reflection the class reflection associated with the dependencies
	 * @return array the resolved dependencies
	 * @throws InvalidConfigException if a dependency cannot be resolved or if a dependency cannot be fulfilled.
	 */
	protected function resolveDependencies($dependencies, $reflection = null)
	{
		foreach ($dependencies as $index => $dependency) {
			if ($dependency instanceof Instance) {
				if ($dependency->id !== null) {
					$dependencies[$index] = $this->get($dependency->id);
				} elseif ($reflection !== null) {
					$name = $reflection->getConstructor()->getParameters()[$index]->getName();
					$class = $reflection->getName();
					throw new InvalidConfigException("Missing required parameter \"$name\" when instantiating \"$class\".");
				}
			}
		}
		return $dependencies;
	}

	/**
	 * Invoke a callback with resolving dependencies in parameters.
	 *
	 * This methods allows invoking a callback and let type hinted parameter names to be
	 * resolved as objects of the Container. It additionally allow calling function using named parameters.
	 *
	 * For example, the following callback may be invoked using the Container to resolve the formatter dependency:
	 *
	 * ```php
	 * $formatString = function($string, \yii\i18n\Formatter $formatter) {
	 *    // ...
	 * }
	 * Rnd::$container->invoke($formatString, ['string' => 'Hello World!']);
	 * ```
	 *
	 * This will pass the string `'Hello World!'` as the first param, and a formatter instance created
	 * by the DI container as the second param to the callable.
	 *
	 * @param callable $callback callable to be invoked.
	 * @param array $params The array of parameters for the function.
	 * This can be either a list of parameters, or an associative array representing named function parameters.
	 * @return mixed the callback return value.
	 * @throws InvalidConfigException if a dependency cannot be resolved or if a dependency cannot be fulfilled.
	 * @throws NotInstantiableException If resolved to an abstract class or an interface (since 2.0.9)
	 * @since 2.0.7
	 */
	public function invoke(callable $callback, $params = [])
	{
		if (is_callable($callback)) {
			return call_user_func_array($callback, $this->resolveCallableDependencies($callback, $params));
		}

		return call_user_func_array($callback, $params);
	}

	/**
	 * Resolve dependencies for a function.
	 *
	 * This method can be used to implement similar functionality as provided by [[invoke()]] in other
	 * components.
	 *
	 * @param callable $callback callable to be invoked.
	 * @param array $params The array of parameters for the function, can be either numeric or associative.
	 * @return array The resolved dependencies.
	 * @throws InvalidConfigException if a dependency cannot be resolved or if a dependency cannot be fulfilled.
	 * @throws NotInstantiableException If resolved to an abstract class or an interface (since 2.0.9)
	 * @since 2.0.7
	 */
	public function resolveCallableDependencies(callable $callback, $params = [])
	{
		if (is_array($callback)) {
			$reflection = new \ReflectionMethod($callback[0], $callback[1]);
		} else {
			$reflection = new \ReflectionFunction($callback);
		}

		$args = [];

		$associative = ArrayHelper::isAssociative($params);

		foreach ($reflection->getParameters() as $param) {
			$name = $param->getName();
			if (($class = $param->getClass()) !== null) {
				$className = $class->getName();
				if (version_compare(PHP_VERSION, '5.6.0', '>=') && $param->isVariadic()) {
					$args = array_merge($args, array_values($params));
					break;
				} elseif ($associative && isset($params[$name]) && $params[$name] instanceof $className) {
					$args[] = $params[$name];
					unset($params[$name]);
				} elseif (!$associative && isset($params[0]) && $params[0] instanceof $className) {
					$args[] = array_shift($params);
				} elseif (isset(Rnd::$app) && Rnd::$app->has($name) && ($obj = Rnd::$app->get($name)) instanceof $className) {
					$args[] = $obj;
				} else {
					// If the argument is optional we catch not instantiable exceptions
					try {
						$args[] = $this->get($className);
					} catch (NotInstantiableException $e) {
						if ($param->isDefaultValueAvailable()) {
							$args[] = $param->getDefaultValue();
						} else {
							throw $e;
						}
					}
				}
			} elseif ($associative && isset($params[$name])) {
				$args[] = $params[$name];
				unset($params[$name]);
			} elseif (!$associative && count($params)) {
				$args[] = array_shift($params);
			} elseif ($param->isDefaultValueAvailable()) {
				$args[] = $param->getDefaultValue();
			} elseif (!$param->isOptional()) {
				$funcName = $reflection->getName();
				throw new InvalidConfigException("Missing required parameter \"$name\" when calling \"$funcName\".");
			}
		}

		foreach ($params as $value) {
			$args[] = $value;
		}

		return $args;
	}

	/**
	 * Creates an instance of the specified class.
	 * This method will resolve dependencies of the specified class, instantiate them, and inject
	 * them into the new instance of the specified class.
	 * @param string $class the class name
	 * @param array $params constructor parameters
	 * @param array $config configurations to be applied to the new instance
	 * @return object the newly created instance of the specified class
	 * @throws NotInstantiableException If resolved to an abstract class or an interface (since 2.0.9)
	 * @throws InvalidConfigException
	 */
	protected function build($class, $params, $config)
	{
		/* @var $reflection ReflectionClass */
		list ($reflection, $dependencies) = $this->getDependencies($class);

		foreach ($params as $index => $param) {
			$dependencies[$index] = $param;
		}

		$dependencies = $this->resolveDependencies($dependencies, $reflection);
		if (!$reflection->isInstantiable()) {
			throw new NotInstantiableException($reflection->name);
		}
		if (empty($config)) {
			return $reflection->newInstanceArgs($dependencies);
		}

		if (!empty($dependencies) && $reflection->implementsInterface('rnd\base\Configurable')) {
			// set $config as the last parameter (existing one will be overwritten)
			$dependencies[count($dependencies) - 1] = $config;
			return $reflection->newInstanceArgs($dependencies);
		} else {
			$object = $reflection->newInstanceArgs($dependencies);
			foreach ($config as $name => $value) {
				$object->$name = $value;
			}
			return $object;
		}
	}

	/**
	 * Merges the user-specified constructor parameters with the ones registered via [[set()]].
	 * @param string $class class name, interface name or alias name
	 * @param array $params the constructor parameters
	 * @return array the merged parameters
	 */
	protected function mergeParams($class, $params)
	{
		if (empty($this->_params[$class])) {
			return $params;
		} elseif (empty($params)) {
			return $this->_params[$class];
		} else {
			$ps = $this->_params[$class];
			foreach ($params as $index => $value) {
				$ps[$index] = $value;
			}
			return $ps;
		}
	}
}
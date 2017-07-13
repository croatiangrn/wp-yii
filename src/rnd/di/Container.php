<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\di;


use ReflectionClass;
use rnd\base\Component;
use rnd\base\InvalidConfigException;

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
	 * Creates an instance of the specified class.
	 * This method will resolve dependencies of the specified class, instantiate them, and inject
	 * them into the new instance of the specified class.
	 * @param string $class the class name
	 * @param array $params constructor parameters
	 * @param array $config configurations to be applied to the new instance
	 * @return object the newly created instance of the specified class
	 * @throws NotInstantiableException If resolved to an abstract class or an interface (since 2.0.9)
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
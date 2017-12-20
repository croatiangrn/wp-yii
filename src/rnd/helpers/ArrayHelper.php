<?php


namespace rnd\helpers;


use rnd\base\Arrayable;
use rnd\base\InvalidParamException;

class ArrayHelper
{
	/**
	 * Converts an object or an array of objects into an array.
	 * @param object|array|string $object the object to be converted into an array
	 * @param array $properties a mapping from object class names to the properties that need to put into the resulting arrays.
	 * The properties specified for each class is an array of the following format:
	 *
	 * ```php
	 * [
	 *     'app\models\Post' => [
	 *         'id',
	 *         'title',
	 *         // the key name in array result => property name
	 *         'createTime' => 'created_at',
	 *         // the key name in array result => anonymous function
	 *         'length' => function ($post) {
	 *             return strlen($post->content);
	 *         },
	 *     ],
	 * ]
	 * ```
	 *
	 * The result of `ArrayHelper::toArray($post, $properties)` could be like the following:
	 *
	 * ```php
	 * [
	 *     'id' => 123,
	 *     'title' => 'test',
	 *     'createTime' => '2013-01-01 12:00AM',
	 *     'length' => 301,
	 * ]
	 * ```
	 *
	 * @param bool $recursive whether to recursively converts properties which are objects into arrays.
	 * @return array the array representation of the object
	 */
	public static function toArray($object, $properties = [], $recursive = true)
	{
		if (is_array($object)) {
			if ($recursive) {
				foreach ($object as $key => $value) {
					if (is_array($value) || is_object($value)) {
						$object[$key] = static::toArray($value, $properties, true);
					}
				}
			}

			return $object;
		} elseif (is_object($object)) {
			if (!empty($properties)) {
				$className = get_class($object);
				if (!empty($properties[$className])) {
					$result = [];
					foreach ($properties[$className] as $key => $name) {
						if (is_int($key)) {
							$result[$name] = $object->$name;
						} else {
							$result[$key] = static::getValue($object, $name);
						}
					}

					return $recursive ? static::toArray($result, $properties) : $result;
				}
			}
			if ($object instanceof Arrayable) {
				$result = $object->toArray([], [], $recursive);
			} else {
				$result = [];
				foreach ($object as $key => $value) {
					$result[$key] = $value;
				}
			}

			return $recursive ? static::toArray($result, $properties) : $result;
		}

		return [$object];
	}

	/**
	 * Builds a map (key-value pairs) from a multidimensional array or an array of objects.
	 * The `$from` and `$to` parameters specify the key names or property names to set up the map.
	 * Optionally, one can further group the map according to a grouping field `$group`.
	 *
	 * For example,
	 *
	 * ```php
	 * $array = [
	 *     ['id' => '123', 'name' => 'aaa', 'class' => 'x'],
	 *     ['id' => '124', 'name' => 'bbb', 'class' => 'x'],
	 *     ['id' => '345', 'name' => 'ccc', 'class' => 'y'],
	 * ];
	 *
	 * $result = ArrayHelper::map($array, 'id', 'name');
	 * // the result is:
	 * // [
	 * //     '123' => 'aaa',
	 * //     '124' => 'bbb',
	 * //     '345' => 'ccc',
	 * // ]
	 *
	 * $result = ArrayHelper::map($array, 'id', 'name', 'class');
	 * // the result is:
	 * // [
	 * //     'x' => [
	 * //         '123' => 'aaa',
	 * //         '124' => 'bbb',
	 * //     ],
	 * //     'y' => [
	 * //         '345' => 'ccc',
	 * //     ],
	 * // ]
	 * ```
	 *
	 * @param array $array
	 * @param string|\Closure $from
	 * @param string|\Closure $to
	 * @param string|\Closure $group
	 * @return array
	 */
	public static function map($array, $from, $to, $group = null)
	{
		$result = [];
		foreach ($array as $element) {
			$key = static::getValue($element, $from);
			$value = static::getValue($element, $to);
			if ($group !== null) {
				$result[static::getValue($element, $group)][$key] = $value;
			} else {
				$result[$key] = $value;
			}
		}

		return $result;
	}
	/**
	 * Check whether an array or [[\Traversable]] contains an element.
	 *
	 * This method does the same as the PHP function [in_array()](http://php.net/manual/en/function.in-array.php)
	 * but additionally works for objects that implement the [[\Traversable]] interface.
	 * @param mixed $needle The value to look for.
	 * @param array|\Traversable $haystack The set of values to search.
	 * @param bool $strict Whether to enable strict (`===`) comparison.
	 * @return bool `true` if `$needle` was found in `$haystack`, `false` otherwise.
	 * @throws InvalidParamException if `$haystack` is neither traversable nor an array.
	 * @see http://php.net/manual/en/function.in-array.php
	 * @since 2.0.7
	 */
	public static function isIn($needle, $haystack, $strict = false)
	{
		if ($haystack instanceof \Traversable) {
			foreach ($haystack as $value) {
				if ($needle == $value && (!$strict || $needle === $value)) {
					return true;
				}
			}
		} elseif (is_array($haystack)) {
			return in_array($needle, $haystack, $strict);
		} else {
			throw new InvalidParamException('Argument $haystack must be an array or implement Traversable');
		}
		return false;
	}
	/**
	 * Merges two or more arrays into one recursively.
	 * If each array has an element with the same string key value, the latter
	 * will overwrite the former (different from array_merge_recursive).
	 * Recursive merging will be conducted if both arrays have an element of array
	 * type and are having the same key.
	 * For integer-keyed elements, the elements from the latter array will
	 * be appended to the former array.
	 * You can use [[UnsetArrayValue]] object to unset value from previous array or
	 * [[ReplaceArrayValue]] to force replace former value instead of recursive merging.
	 * @param array $a array to be merged to
	 * @param array $b array to be merged from. You can specify additional
	 * arrays via third argument, fourth argument etc.
	 * @return array the merged array (the original arrays are not changed.)
	 */
	public static function merge($a, $b)
	{
		$args = func_get_args();
		$res = array_shift($args);
		while (!empty($args)) {
			$next = array_shift($args);
			foreach ($next as $k => $v) {
				if ($v instanceof UnsetArrayValue) {
					unset($res[$k]);
				} elseif ($v instanceof ReplaceArrayValue) {
					$res[$k] = $v->value;
				} elseif (is_int($k)) {
					if (isset($res[$k])) {
						$res[] = $v;
					} else {
						$res[$k] = $v;
					}
				} elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
					$res[$k] = self::merge($res[$k], $v);
				} else {
					$res[$k] = $v;
				}
			}
		}

		return $res;
	}
	/**
	 * Checks whether a variable is an array or [[\Traversable]].
	 *
	 * This method does the same as the PHP function [is_array()](http://php.net/manual/en/function.is-array.php)
	 * but additionally works on objects that implement the [[\Traversable]] interface.
	 * @param mixed $var The variable being evaluated.
	 * @return bool whether $var is array-like
	 * @see http://php.net/manual/en/function.is_array.php
	 * @since 2.0.8
	 */
	public static function isTraversable($var)
	{
		return is_array($var) || $var instanceof \Traversable;
	}
	/**
	 * Removes an item from an array and returns the value. If the key does not exist in the array, the default value
	 * will be returned instead.
	 *
	 * Usage examples,
	 *
	 * ```php
	 * // $array = ['type' => 'A', 'options' => [1, 2]];
	 * // working with array
	 * $type = \yii\helpers\ArrayHelper::remove($array, 'type');
	 * // $array content
	 * // $array = ['options' => [1, 2]];
	 * ```
	 *
	 * @param array $array the array to extract value from
	 * @param string $key key name of the array element
	 * @param mixed $default the default value to be returned if the specified key does not exist
	 * @return mixed|null the value of the element if found, default value otherwise
	 */
	public static function remove(&$array, $key, $default = null)
	{
		if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array))) {
			$value = $array[$key];
			unset($array[$key]);

			return $value;
		}

		return $default;
	}

	/**
	 * Retrieves the value of an array element or object property with the given key or property name.
	 * If the key does not exist in the array or object, the default value will be returned instead.
	 *
	 * The key may be specified in a dot format to retrieve the value of a sub-array or the property
	 * of an embedded object. In particular, if the key is `x.y.z`, then the returned value would
	 * be `$array['x']['y']['z']` or `$array->x->y->z` (if `$array` is an object). If `$array['x']`
	 * or `$array->x` is neither an array nor an object, the default value will be returned.
	 * Note that if the array already has an element `x.y.z`, then its value will be returned
	 * instead of going through the sub-arrays. So it is better to be done specifying an array of key names
	 * like `['x', 'y', 'z']`.
	 *
	 * Below are some usage examples,
	 *
	 * @param array|object $array array or object to extract value from
	 * @param string|\Closure|array $key key name of the array element, an array of keys or property name of the object,
	 * or an anonymous function returning the value. The anonymous function signature should be:
	 * `function($array, $defaultValue)`.
	 * The possibility to pass an array of keys is available since version 2.0.4.
	 * @param mixed $default the default value to be returned if the specified array key does not exist. Not used when
	 * getting value from an object.
	 * @return mixed the value of the element if found, default value otherwise
	 */
	public static function getValue($array, $key, $default = null)
	{
		if ($key instanceof \Closure) {
			return $key($array, $default);
		}

		if (is_array($key)) {
			$lastKey = array_pop($key);
			foreach ($key as $keyPart) {
				$array = static::getValue($array, $keyPart);
			}
			$key = $lastKey;
		}

		if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array)) ) {
			return $array[$key];
		}

		if (($pos = strrpos($key, '.')) !== false) {
			$array = static::getValue($array, substr($key, 0, $pos), $default);
			$key = substr($key, $pos + 1);
		}

		if (is_object($array)) {
			// this is expected to fail if the property does not exist, or __get() is not implemented
			// it is not reliably possible to check whether a property is accessible beforehand
			return $array->$key;
		} elseif (is_array($array)) {
			return (isset($array[$key]) || array_key_exists($key, $array)) ? $array[$key] : $default;
		} else {
			return $default;
		}
	}
}
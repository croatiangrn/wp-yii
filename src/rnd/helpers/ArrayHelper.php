<?php


namespace rnd\helpers;


use rnd\base\InvalidParamException;

class ArrayHelper
{
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
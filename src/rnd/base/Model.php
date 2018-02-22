<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\base;


use ReflectionClass;

/**
 * Class Model
 * @package rnd\base
 * @deprecated
 */
class Model extends RndObject {
	protected $safeAttributes = [];

	public function setSafeAttributes($attributes = [])
	{
		$this->safeAttributes = $attributes;
	}

	/**
	 * Returns the list of attribute names.
	 * By default, this method returns all public non-static properties of the class.
	 * You may override this method to change the default behavior.
	 * @return array list of attribute names.
	 */
	public function attributes()
	{
		$class = new ReflectionClass($this);
		$names = [];
		foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
			if (!$property->isStatic()) {
				$names[] = $property->getName();
			}
		}
		return $names;
	}


	/**
	 * Sets the attribute values in a massive way.
	 * @param array $values attribute values (name => value) to be assigned to the model.
	 * A safe attribute is one that is associated with a validation rule in the current [[scenario]].
	 * @see attributes()
	 */
	public function setAttributes($values)
	{
		if (is_array($values)) {
			$attributes = $this->attributes();
			$counter = 0;
			foreach ($values as $name => $value) {
				if (isset($attributes[$counter])) {
					$this->$name = $value;
				} else {
					unset ($this->$name);
				}
				$counter++;
			}
		}
	}

	/**
	 * Populates the model with input data.
	 * @param array $data the data array to load, typically `$_POST` or `$_GET`.
	 * @return bool whether `load()` found the expected form in `$data`.
	 */
	public function load($data)
	{
		if (!empty($data)) {
			$this->setAttributes($data);
			return true;
		}
		return false;
	}

	protected function required() {
		foreach ( $this->attributes() as $key => $val ) {
			if (!in_array($val, $this->safeAttributes)) {
				if (strlen($this->$val) == 0 ) {
					return false;
				}
			}
		}
		return true;
	}

	public function validate() {
		if ($this->required()) {
			return true;
		}
		return false;
	}
}
<?php


namespace rnd\helpers;


class ReplaceArrayValue
{
	/**
	 * @var mixed value used as replacement.
	 */
	public $value;


	/**
	 * Constructor.
	 * @param mixed $value value used as replacement.
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}
}
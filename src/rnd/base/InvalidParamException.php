<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\base;


class InvalidParamException extends \BadMethodCallException
{
	/**
	 * @return string the user-friendly name of this exception
	 */
	public function getName()
	{
		return 'Invalid Parameter';
	}
}
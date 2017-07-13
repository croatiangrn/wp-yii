<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\base;


class UnknownClassException extends Exception
{
	/**
	 * @return string the user-friendly name of this exception
	 */
	public function getName()
	{
		return 'Unknown Class';
	}
}
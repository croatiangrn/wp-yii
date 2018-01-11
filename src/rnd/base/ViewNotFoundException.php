<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\base;


/**
 * ViewNotFoundException represents an exception caused by view file not found.
 *
 * @author Alexander Makarov
 * @since 2.0.10
 */
class ViewNotFoundException extends InvalidParamException
{
	/**
	 * @return string the user-friendly name of this exception
	 */
	public function getName()
	{
		return 'View not Found';
	}
}
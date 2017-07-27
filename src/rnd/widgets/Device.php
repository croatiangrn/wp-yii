<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\widgets;


use Detection\MobileDetect;

class Device extends MobileDetect
{
	/**
	 * Checks if current device is PC
	 *
	 * @return bool
	 */
	public function isDesktop()
	{
		return !$this->isMobile() && !$this->isTablet();
	}
}
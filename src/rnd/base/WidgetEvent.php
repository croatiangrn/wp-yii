<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\base;


/**
 * WidgetEvent represents the event parameter used for a widget event.
 *
 * By setting the [[isValid]] property, one may control whether to continue running the widget.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2.0.11
 */
class WidgetEvent extends Event
{
	/**
	 * @var mixed the widget result. Event handlers may modify this property to change the widget result.
	 */
	public $result;
	/**
	 * @var bool whether to continue running the widget. Event handlers of
	 * [[Widget::EVENT_BEFORE_RUN]] may set this property to decide whether
	 * to continue running the current widget.
	 */
	public $isValid = true;
}
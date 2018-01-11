<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\web;

use rnd\base\Exception;


/**
 * HeadersAlreadySentException represents an exception caused by
 * any headers that were already sent before web response was sent.
 *
 * @author Dmitry Dorogin <dmirogin@ya.ru>
 * @since 2.0.14
 */
class HeadersAlreadySentException extends Exception
{
	/**
	 * @inheritdoc
	 */
	public function __construct($file, $line)
	{
		$message = RND_DEBUG ? "Headers already sent in {$file} on line {$line}." : 'Headers already sent.';
		parent::__construct($message);
	}
}
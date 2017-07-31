<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\web;


use rnd\base\InvalidParamException;

class BaseUrl
{
	/**
	 * Returns a value indicating whether a URL is relative.
	 * A relative URL does not have host info part.
	 * @param string $url the URL to be checked
	 * @return bool whether the URL is relative
	 */
	public static function isRelative($url)
	{
		return strncmp($url, '//', 2) && strpos($url, '://') === false;
	}
	/**
	 * Normalize URL by ensuring that it use specified scheme.
	 *
	 * If URL is relative or scheme is not string, normalization is skipped.
	 *
	 * @param string $url the URL to process
	 * @param string $scheme the URI scheme used in URL (e.g. `http` or `https`). Use empty string to
	 * create protocol-relative URL (e.g. `//example.com/path`)
	 * @return string the processed URL
	 * @since 2.0.11
	 */
	public static function ensureScheme($url, $scheme)
	{
		if (static::isRelative($url) || !is_string($scheme)) {
			return $url;
		}

		if (substr($url, 0, 2) === '//') {
			// e.g. //example.com/path/to/resource
			return $scheme === '' ? $url : "$scheme:$url";
		}

		if (($pos = strpos($url, '://')) !== false) {
			if ($scheme === '') {
				$url = substr($url, $pos + 1);
			} else {
				$url = $scheme . substr($url, $pos);
			}
		}

		return $url;
	}
	/**
	 * Creates a URL based on the given parameters.
	 *
	 * @param array|string $url the parameter to be used to generate a valid URL
	 * @param bool|string $scheme the URI scheme to use in the generated URL:
	 *
	 * - `false` (default): generating a relative URL.
	 * - string: generating an absolute URL with the specified scheme (either `http`, `https` or empty string
	 *   for protocol-relative URL).
	 *
	 * @return string the generated URL
	 * @throws InvalidParamException a relative route is given while there is no active controller
	 */
	public static function to($url = '', $scheme = true)
	{
		if ($url === '') {
			$request = new Request();
			$url = $request->getUrl();
		}

		if ($scheme === false) {
			return $url;
		}

		return static::ensureScheme($url, $scheme);
	}

	/**
	 * Remembers the specified URL so that it can be later fetched back by [[previous()]].
	 *
	 * @param string|array $url the URL to remember. Please refer to [[to()]] for acceptable formats.
	 * If this parameter is not specified, the currently requested URL will be used.
	 * @param string $name the name associated with the URL to be remembered. This can be used
	 * later by [[previous()]].
	 * @see previous()
	 * @throws InvalidParamException
	 */
	public static function remember($url = '', $name = null)
	{
		$url = static::to($url);
		if ($name === null) {
			throw new InvalidParamException('Parameter `url` cannot be emptyn!');
		} else {
			$session = new Session();
			$session->set($name, $url);
		}
	}

	/**
	 * Returns the URL previously [[remember()|remembered]].
	 *
	 * @param string $name the named associated with the URL that was remembered previously.
	 * If not set, it will use [[\yii\web\User::returnUrlParam]].
	 * @return string|null the URL previously remembered. Null is returned if no URL was remembered with the given name.
	 * @see remember()
	 * @throws InvalidParamException
	 */
	public static function previous($name = null)
	{
		if ($name === null) {
			throw new InvalidParamException('Parameter `url` cannot be empty!');
		} else {
			$session = new Session();
			return $session->get($name);
		}
	}
}
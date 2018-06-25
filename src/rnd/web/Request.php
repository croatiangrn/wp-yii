<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\web;


use rnd\base\Exception;
use rnd\base\InvalidConfigException;
use rnd\validators\IpValidator;

/**
* @property HeaderCollection $headers The header collection. This property is read-only.
*/

class Request
{
	private $_queryParams;
	/**
	 * @var string the name of the POST parameter that is used to indicate if a request is a PUT, PATCH or DELETE
	 * request tunneled through POST. Defaults to '_method'.
	 * @see getMethod()
	 * @see getBodyParams()
	 */
	public $methodParam = '_method';

	/**
	 * @var HeaderCollection Collection of request headers.
	 */
	private $_headers;

	/**
	 * @var array lists of headers that are, by default, subject to the trusted host configuration.
	 * These headers will be filtered unless explicitly allowed in [[trustedHosts]].
	 * The match of header names is case-insensitive.
	 * @see https://en.wikipedia.org/wiki/List_of_HTTP_header_fields
	 * @see $trustedHosts
	 * @since 2.0.13
	 */
	public $secureHeaders = [
		'X-Forwarded-For',
		'X-Forwarded-Host',
		'X-Forwarded-Proto',
		'Front-End-Https',
		'X-Rewrite-Url',
	];

    /**
     * @var string[] List of headers where proxies store the real client IP.
     * It's not advisable to put insecure headers here.
     * The match of header names is case-insensitive.
     * @see $trustedHosts
     * @see $secureHeaders
     * @since 2.0.13
     */
    public $ipHeaders = [
        'X-Forwarded-For', // Common
    ];

	/**
	 * Returns the method of the current request (e.g. GET, POST, HEAD, PUT, PATCH, DELETE).
	 * @return string request method, such as GET, POST, HEAD, PUT, PATCH, DELETE.
	 * The value returned is turned into upper case.
	 */
	public function getMethod()
	{
		if (isset($_POST[$this->methodParam])) {
			return strtoupper($_POST[$this->methodParam]);
		}

		if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
			return strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
		}

		if (isset($_SERVER['REQUEST_METHOD'])) {
			return strtoupper($_SERVER['REQUEST_METHOD']);
		}

		return 'GET';
	}

	/**
	 * Returns whether this is a GET request.
	 * @return bool whether this is a GET request.
	 */
	public function getIsGet()
	{
		return $this->getMethod() === 'GET';
	}

	/**
	 * Returns whether this is an OPTIONS request.
	 * @return bool whether this is a OPTIONS request.
	 */
	public function getIsOptions()
	{
		return $this->getMethod() === 'OPTIONS';
	}

	/**
	 * Returns whether this is a HEAD request.
	 * @return bool whether this is a HEAD request.
	 */
	public function getIsHead()
	{
		return $this->getMethod() === 'HEAD';
	}

	/**
	 * Returns whether this is a POST request.
	 * @return bool whether this is a POST request.
	 */
	public function getIsPost()
	{
		return $this->getMethod() === 'POST';
	}

	/**
	 * Returns whether this is a DELETE request.
	 * @return bool whether this is a DELETE request.
	 */
	public function getIsDelete()
	{
		return $this->getMethod() === 'DELETE';
	}

	/**
	 * Returns whether this is a PUT request.
	 * @return bool whether this is a PUT request.
	 */
	public function getIsPut()
	{
		return $this->getMethod() === 'PUT';
	}

	/**
	 * Returns whether this is a PATCH request.
	 * @return bool whether this is a PATCH request.
	 */
	public function getIsPatch()
	{
		return $this->getMethod() === 'PATCH';
	}

    /**
     * Returns the URL origin of a CORS request.
     *
     * The return value is taken from the `Origin` [[getHeaders()|header]] sent by the browser.
     *
     * Note that the origin request header indicates where a fetch originates from.
     * It doesn't include any path information, but only the server name.
     * It is sent with a CORS requests, as well as with POST requests.
     * It is similar to the referer header, but, unlike this header, it doesn't disclose the whole path.
     * Please refer to <https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Origin> for more information.
     *
     * @return string|null URL origin of a CORS request, `null` if not available.
     * @see   getHeaders()
     * @since 2.0.13
     * @throws \Exception
     */
	public function getOrigin()
	{
		return $this->getHeaders()->get('origin');
	}


	/**
	 * Returns whether this is an AJAX (XMLHttpRequest) request.
	 *
	 * Note that jQuery doesn't set the header in case of cross domain
	 * requests: https://stackoverflow.com/questions/8163703/cross-domain-ajax-doesnt-send-x-requested-with-header
	 *
	 * @return bool whether this is an AJAX (XMLHttpRequest) request.
	 */
	public function getIsAjax()
	{
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
	}

	/**
	 * Returns whether this is a PJAX request.
	 * @return bool whether this is a PJAX request
	 *
	 * @throws \Exception
	 */
	public function getIsPjax()
	{
		return $this->getIsAjax() && $this->getHeaders()->has('X-Pjax');
	}

	/**
	 * Returns part of the request URL that is after the question mark.
	 * @return string part of the request URL that is after the question mark
	 */
	public function getQueryString()
	{
		return isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
	}

	/**
	 * Return if the request is sent via secure channel (https).
	 * @return bool if the request is sent via secure channel (https)
	 */
	public function getIsSecureConnection()
	{
		return isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'], 'on') === 0 || $_SERVER['HTTPS'] == 1)
		       || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0;
	}

	/**
	 * Returns the server name.
	 * @return string server name, null if not available
	 */
	public function getServerName()
	{
		return isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : null;
	}

	/**
	 * Returns the server port number.
	 * @return int|null server port number, null if not available
	 */
	public function getServerPort()
	{
		return isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : null;
	}

	/**
	 * Returns the URL referrer.
	 * @return string|null URL referrer, null if not available
	 */
	public function getReferrer()
	{
		return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
	}

	/**
	 * Returns the user agent.
	 * @return string|null user agent, null if not available
	 */
	public function getUserAgent()
	{
		return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
	}

    /**
     * Returns the user IP address.
     * The IP is determined using headers and / or `$_SERVER` variables.
     * @return string|null user IP address, null if not available
     * @throws \Exception
     */
    public function getUserIP()
    {
        foreach ($this->ipHeaders as $ipHeader) {
            if ($this->getHeaders()->has($ipHeader)) {
                return trim(explode(',', $this->getHeaders()->get($ipHeader))[0]);
            }
        }

        return $this->getRemoteIP();
    }

	/**
	 * Returns the user host name.
	 * @return string|null user host name, null if not available
	 */
	public function getUserHost()
	{
		return isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : null;
	}

	/**
	 * @return string|null the username sent via HTTP authentication, null if the username is not given
	 */
	public function getAuthUser()
	{
		return isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
	}

	/**
	 * @return string|null the password sent via HTTP authentication, null if the password is not given
	 */
	public function getAuthPassword()
	{
		return isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;
	}

	/**
	 * Returns the currently requested absolute URL.
	 * This is a shortcut to the concatenation of [[hostInfo]] and [[url]].
	 * @return string the currently requested absolute URL.
	 * @throws InvalidConfigException
	 */
	public function getAbsoluteUrl()
	{
		return $this->getHostInfo() . $this->getUrl();
	}

	private $_url;

	/**
	 * Returns the currently requested relative URL.
	 * This refers to the portion of the URL that is after the [[hostInfo]] part.
	 * It includes the [[queryString]] part if any.
	 * @return string the currently requested relative URL. Note that the URI returned is URL-encoded.
	 * @throws InvalidConfigException if the URL cannot be determined due to unusual server configuration
	 */
	public function getUrl()
	{
		if ($this->_url === null) {
			$this->_url = $this->resolveRequestUri();
		}

		return $this->_url;
	}

	/**
	 * Resolves the request URI portion for the currently requested URL.
	 * This refers to the portion that is after the [[hostInfo]] part. It includes the [[queryString]] part if any.
	 * The implementation of this method referenced Zend_Controller_Request_Http in Zend Framework.
	 * @return string|bool the request URI portion for the currently requested URL.
	 * Note that the URI returned is URL-encoded.
	 * @throws InvalidConfigException if the request URI cannot be determined due to unusual server configuration
	 */
	protected function resolveRequestUri()
	{
		if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // IIS
			$requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
		} elseif (isset($_SERVER['REQUEST_URI'])) {
			$requestUri = $_SERVER['REQUEST_URI'];
			if ($requestUri !== '' && $requestUri[0] !== '/') {
				$requestUri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $requestUri);
			}
		} elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0 CGI
			$requestUri = $_SERVER['ORIG_PATH_INFO'];
			if (!empty($_SERVER['QUERY_STRING'])) {
				$requestUri .= '?' . $_SERVER['QUERY_STRING'];
			}
		} else {
			throw new InvalidConfigException('Unable to determine the request URI.');
		}

		return $requestUri;
	}

	private $_securePort;

	/**
	 * Returns the port to use for secure requests.
	 * Defaults to 443, or the port specified by the server if the current
	 * request is secure.
	 * @return int port number for secure requests.
	 * @see setSecurePort()
	 */
	public function getSecurePort()
	{
		if ($this->_securePort === null) {
			$this->_securePort = $this->getIsSecureConnection() && isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : 443;
		}

		return $this->_securePort;
	}

	/**
	 * Returns the header collection.
	 * The header collection contains incoming HTTP headers.
	 * @return HeaderCollection the header collection
	 * @throws \Exception
	 */
	public function getHeaders()
	{
		if ($this->_headers === null) {
			$this->_headers = new HeaderCollection();
			if (function_exists('getallheaders')) {
				$headers = getallheaders();
				foreach ($headers as $name => $value) {
					$this->_headers->add($name, $value);
				}
			} elseif (function_exists('http_get_request_headers')) {
				$headers = http_get_request_headers();
				foreach ($headers as $name => $value) {
					$this->_headers->add($name, $value);
				}
			} else {
				foreach ($_SERVER as $name => $value) {
					if (strncmp($name, 'HTTP_', 5) === 0) {
						$name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
						$this->_headers->add($name, $value);
					}
				}
			}
			$this->filterHeaders($this->_headers);
		}

		return $this->_headers;
	}

	/**
	 * Filters headers according to the [[trustedHosts]].
	 * @param HeaderCollection $headerCollection
	 * @since 2.0.13
	 * @throws \Exception
	 */
	protected function filterHeaders(HeaderCollection $headerCollection)
	{
		// do not trust any of the [[secureHeaders]] by default
		$trustedHeaders = [];

		// check if the client is a trusted host
		if (!empty($this->trustedHosts)) {
			$validator = $this->getIpValidator();
			$ip = $this->getRemoteIP();
			foreach ($this->trustedHosts as $cidr => $headers) {
				if (!is_array($headers)) {
					$cidr = $headers;
					$headers = $this->secureHeaders;
				}
                /** @var array $cidr */
                $validator->setRanges($cidr);
				if ($validator->validate($ip)) {
					$trustedHeaders = $headers;
					break;
				}
			}
		}

		// filter all secure headers unless they are trusted
		foreach ($this->secureHeaders as $secureHeader) {
			if (!in_array($secureHeader, $trustedHeaders)) {
				$headerCollection->remove($secureHeader);
			}
		}
	}

	/**
	 * Returns the IP on the other end of this connection.
	 * This is always the next hop, any headers are ignored.
	 * @return string|null remote IP address, `null` if not available.
	 * @since 2.0.13
	 */
	public function getRemoteIP()
	{
		return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
	}

	/**
	 * Creates instance of [[IpValidator]].
	 * You can override this method to adjust validator or implement different matching strategy.
	 *
	 * @return IpValidator
	 * @since 2.0.13
	 */
	protected function getIpValidator()
	{
		return new IpValidator();
	}



	private $_port;

	/**
	 * Returns the port to use for insecure requests.
	 * Defaults to 80, or the port specified by the server if the current
	 * request is insecure.
	 * @return int port number for insecure requests.
	 * @see setPort()
	 */
	public function getPort()
	{
		if ($this->_port === null) {
			$this->_port = !$this->getIsSecureConnection() && isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : 80;
		}

		return $this->_port;
	}

	private $_hostInfo;
	/**
	 * Returns the schema and host part of the current request URL.
	 *
	 * The returned URL does not have an ending slash.
	 *
	 * By default this value is based on the user request information. This method will
	 * return the value of `$_SERVER['HTTP_HOST']` if it is available or `$_SERVER['SERVER_NAME']` if not.
	 * You may want to check out the [PHP documentation](http://php.net/manual/en/reserved.variables.server.php)
	 * for more information on these variables.
	 *
	 * You may explicitly specify it by setting the [[setHostInfo()|hostInfo]] property.
	 *
	 * > Warning: Dependent on the server configuration this information may not be
	 * > reliable and [may be faked by the user sending the HTTP request](https://www.acunetix.com/vulnerabilities/web/host-header-attack).
	 * > If the webserver is configured to serve the same site independent of the value of
	 * > the `Host` header, this value is not reliable. In such situations you should either
	 * > fix your webserver configuration or explicitly set the value by setting the [[setHostInfo()|hostInfo]] property.
	 * > If you don't have access to the server configuration, you can setup [[\yii\filters\HostControl]] filter at
	 * > application level in order to protect against such kind of attack.
	 *
	 * @property string|null schema and hostname part (with port number if needed) of the request URL
	 * (e.g. `http://www.yiiframework.com`), null if can't be obtained from `$_SERVER` and wasn't set.
	 * See [[getHostInfo()]] for security related notes on this property.
	 * @return string|null schema and hostname part (with port number if needed) of the request URL
	 * (e.g. `http://www.yiiframework.com`), null if can't be obtained from `$_SERVER` and wasn't set.
	 * @see setHostInfo()
	 */
	public function getHostInfo()
	{
		if ($this->_hostInfo === null) {
			$secure = $this->getIsSecureConnection();
			$http = $secure ? 'https' : 'http';
			if (isset($_SERVER['HTTP_HOST'])) {
				$this->_hostInfo = $http . '://' . $_SERVER['HTTP_HOST'];
			} elseif (isset($_SERVER['SERVER_NAME'])) {
				$this->_hostInfo = $http . '://' . $_SERVER['SERVER_NAME'];
				$port = $secure ? $this->getSecurePort() : $this->getPort();
				if (($port !== 80 && !$secure) || ($port !== 443 && $secure)) {
					$this->_hostInfo .= ':' . $port;
				}
			}
		}

		return $this->_hostInfo;
	}

	/**
	 * Returns the request parameters given in the [[queryString]].
	 *
	 * This method will return the contents of `$_GET` if params where not explicitly set.
	 * @return array the request GET parameter values.
	 * @see setQueryParams()
	 */
	public function getQueryParams()
	{
		if ($this->_queryParams === null) {
			return $_GET;
		}

		return $this->_queryParams;
	}

	/**
	 * Returns the named GET parameter value.
	 * If the GET parameter does not exist, the second parameter passed to this method will be returned.
	 * @param string $name the GET parameter name.
	 * @param mixed $defaultValue the default parameter value if the GET parameter does not exist.
	 * @return mixed the GET parameter value
	 * @see getBodyParam()
	 */
	public function getQueryParam($name, $defaultValue = null)
	{
		$params = $this->getQueryParams();

		return isset($params[$name]) ? $params[$name] : $defaultValue;
	}

	/**
	 * Returns GET parameter with a given name. If name isn't specified, returns an array of all GET parameters.
	 *
	 * @param string $name the parameter name
	 * @param mixed $defaultValue the default parameter value if the parameter does not exist.
	 * @return array|mixed
	 */
	public function get($name = null, $defaultValue = null)
	{
		if ($name === null) {
			return $this->getQueryParams();
		} else {
			return $this->getQueryParam($name, $defaultValue);
		}
	}

	public function getQ($name = null) {
		return get_query_var($name);
	}

	/**
	 * Returns request content-type
	 * The Content-Type header field indicates the MIME type of the data
	 * contained in [[getRawBody()]] or, in the case of the HEAD method, the
	 * media type that would have been sent had the request been a GET.
	 * For the MIME-types the user expects in response, see [[acceptableContentTypes]].
	 * @return string request content-type. Null is returned if this information is not available.
	 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.17
	 * HTTP 1.1 header field definitions
	 */
	public function getContentType()
	{
		if (isset($_SERVER['CONTENT_TYPE'])) {
			return $_SERVER['CONTENT_TYPE'];
		} elseif (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
			//fix bug https://bugs.php.net/bug.php?id=66606
			return $_SERVER['HTTP_CONTENT_TYPE'];
		}

		return null;
	}

	private $_bodyParams;
	private $_rawBody;

	/**
	 * Returns the raw HTTP request body.
	 * @return string the request body
	 */
	public function getRawBody()
	{
		if ($this->_rawBody === null) {
			$this->_rawBody = file_get_contents('php://input');
		}

		return $this->_rawBody;
	}

	/**
	 * Returns the request parameters given in the request body.
	 *
	 * Request parameters are determined using the parsers configured in [[parsers]] property.
	 * If no parsers are configured for the current [[contentType]] it uses the PHP function `mb_parse_str()`
	 * to parse the [[rawBody|request body]].
	 * @return array the request parameters given in the request body.
	 * @see getMethod()
	 * @see getBodyParam()
	 * @see setBodyParams()
	 */
	public function getBodyParams()
	{
		if ($this->_bodyParams === null) {
			if (isset($_POST[$this->methodParam])) {
				$this->_bodyParams = $_POST;
				unset($this->_bodyParams[$this->methodParam]);
				return $this->_bodyParams;
			}

			if ($this->getMethod() === 'POST') {
				// PHP has already parsed the body so we have all params in $_POST
				$this->_bodyParams = $_POST;
			} else {
				$this->_bodyParams = [];
				mb_parse_str($this->getRawBody(), $this->_bodyParams);
			}
		}

		return $this->_bodyParams;
	}

	/**
	 * Returns the named request body parameter value.
	 * If the parameter does not exist, the second parameter passed to this method will be returned.
	 * @param string $name the parameter name
	 * @param mixed $defaultValue the default parameter value if the parameter does not exist.
	 * @return mixed the parameter value
	 * @see getBodyParams()
	 * @see setBodyParams()
	 */
	public function getBodyParam($name, $defaultValue = null)
	{
		$params = $this->getBodyParams();

		return isset($params[$name]) ? $params[$name] : $defaultValue;
	}

	/**
	 * Sets the request body parameters.
	 * @param array $values the request body parameters (name-value pairs)
	 * @see getBodyParam()
	 * @see getBodyParams()
	 */
	public function setBodyParams($values)
	{
		$this->_bodyParams = $values;
	}

	/**
	 * Returns POST parameter with a given name. If name isn't specified, returns an array of all POST parameters.
	 *
	 * @param string $name the parameter name
	 * @param mixed $defaultValue the default parameter value if the parameter does not exist.
	 * @return array|mixed
	 */
	public function post($name = null, $defaultValue = null)
	{
		if ($name === null) {
			return $this->getBodyParams();
		} else {
			return $this->getBodyParam($name, $defaultValue);
		}
	}

	private $_scriptFile;

	/**
	 * Returns the entry script file path.
	 * The default implementation will simply return `$_SERVER['SCRIPT_FILENAME']`.
	 * @return string the entry script file path
	 * @throws InvalidConfigException
	 */
	public function getScriptFile()
	{
		if (isset($this->_scriptFile)) {
			return $this->_scriptFile;
		}

		if (isset($_SERVER['SCRIPT_FILENAME'])) {
			return $_SERVER['SCRIPT_FILENAME'];
		}

		throw new InvalidConfigException('Unable to determine the entry script file path.');
	}

	private $_scriptUrl;

	/**
	 * Returns the relative URL of the entry script.
	 * The implementation of this method referenced Zend_Controller_Request_Http in Zend Framework.
	 * @return string the relative URL of the entry script.
	 * @throws InvalidConfigException if unable to determine the entry script URL
	 */
	public function getScriptUrl()
	{
		if ($this->_scriptUrl === null) {
			$scriptFile = $this->getScriptFile();
			$scriptName = basename($scriptFile);
			if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
				$this->_scriptUrl = $_SERVER['SCRIPT_NAME'];
			} elseif (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) === $scriptName) {
				$this->_scriptUrl = $_SERVER['PHP_SELF'];
			} elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
				$this->_scriptUrl = $_SERVER['ORIG_SCRIPT_NAME'];
			} elseif (isset($_SERVER['PHP_SELF']) && ($pos = strpos($_SERVER['PHP_SELF'], '/' . $scriptName)) !== false) {
				$this->_scriptUrl = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $scriptName;
			} elseif (!empty($_SERVER['DOCUMENT_ROOT']) && strpos($scriptFile, $_SERVER['DOCUMENT_ROOT']) === 0) {
				$this->_scriptUrl = str_replace('\\', '/', str_replace($_SERVER['DOCUMENT_ROOT'], '', $scriptFile));
			} else {
				throw new InvalidConfigException('Unable to determine the entry script URL.');
			}
		}

		return $this->_scriptUrl;
	}

	private $_baseUrl;

	/**
	 * Returns the relative URL for the application.
	 * This is similar to [[scriptUrl]] except that it does not include the script file name,
	 * and the ending slashes are removed.
	 * @return string the relative URL for the application
	 * @see setScriptUrl()
	 * @throws InvalidConfigException
	 */
	public function getBaseUrl()
	{
		if ($this->_baseUrl === null) {
			$this->_baseUrl = rtrim(dirname($this->getScriptUrl()), '\\/');
		}

		return $this->_baseUrl;
	}
}
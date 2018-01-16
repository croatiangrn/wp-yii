<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\base;
use rnd\helpers\StringHelper;

/**
 * Security provides a set of methods to handle common security-related tasks.
 *
 * In particular, Security supports the following features:
 *
 * - Encryption/decryption: [[encryptByKey()]], [[decryptByKey()]], [[encryptByPassword()]] and [[decryptByPassword()]]
 * - Key derivation using standard algorithms: [[pbkdf2()]] and [[hkdf()]]
 * - Data tampering prevention: [[hashData()]] and [[validateData()]]
 * - Password validation: [[generatePasswordHash()]] and [[validatePassword()]]
 *
 * > Note: this class requires 'OpenSSL' PHP extension for random key/string generation on Windows and
 * for encryption/decryption on all platforms. For the highest security level PHP version >= 5.5.0 is recommended.
 *
 * For more details and usage information on Security, see the [guide article on security](guide:security-overview).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Tom Worster <fsb@thefsb.org>
 * @author Klimov Paul <klimov.paul@gmail.com>
 * @since 2.0
 */
class Security extends Component
{
	private $_useLibreSSL;
	private $_randomFile;

	/**
	 * Generates specified number of random bytes.
	 * Note that output may not be ASCII.
	 * @see generateRandomString() if you need a string.
	 *
	 * @param int $length the number of bytes to generate
	 * @return string the generated random bytes
	 * @throws InvalidParamException if wrong length is specified
	 * @throws Exception on failure.
	 */
	public function generateRandomKey($length = 32)
	{
		if (!is_int($length)) {
			throw new InvalidParamException('First parameter ($length) must be an integer');
		}
		if ($length < 1) {
			throw new InvalidParamException('First parameter ($length) must be greater than 0');
		}
		// always use random_bytes() if it is available
		if (function_exists('random_bytes')) {
			return random_bytes($length);
		}
		// The recent LibreSSL RNGs are faster and likely better than /dev/urandom.
		// Parse OPENSSL_VERSION_TEXT because OPENSSL_VERSION_NUMBER is no use for LibreSSL.
		// https://bugs.php.net/bug.php?id=71143
		if ($this->_useLibreSSL === null) {
			$this->_useLibreSSL = defined('OPENSSL_VERSION_TEXT')
			                      && preg_match('{^LibreSSL (\d\d?)\.(\d\d?)\.(\d\d?)$}', OPENSSL_VERSION_TEXT, $matches)
			                      && (10000 * $matches[1]) + (100 * $matches[2]) + $matches[3] >= 20105;
		}
		// Since 5.4.0, openssl_random_pseudo_bytes() reads from CryptGenRandom on Windows instead
		// of using OpenSSL library. LibreSSL is OK everywhere but don't use OpenSSL on non-Windows.
		if (function_exists('openssl_random_pseudo_bytes')
		    && ($this->_useLibreSSL
		        || (
			        DIRECTORY_SEPARATOR !== '/'
			        && substr_compare(PHP_OS, 'win', 0, 3, true) === 0
		        ))
		) {
			$key = openssl_random_pseudo_bytes($length, $cryptoStrong);
			if ($cryptoStrong === false) {
				throw new Exception(
					'openssl_random_pseudo_bytes() set $crypto_strong false. Your PHP setup is insecure.'
				);
			}
			if ($key !== false && StringHelper::byteLength($key) === $length) {
				return $key;
			}
		}
		// mcrypt_create_iv() does not use libmcrypt. Since PHP 5.3.7 it directly reads
		// CryptGenRandom on Windows. Elsewhere it directly reads /dev/urandom.
		if (function_exists('mcrypt_create_iv')) {
			$key = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
			if (StringHelper::byteLength($key) === $length) {
				return $key;
			}
		}
		// If not on Windows, try to open a random device.
		if ($this->_randomFile === null && DIRECTORY_SEPARATOR === '/') {
			// urandom is a symlink to random on FreeBSD.
			$device = PHP_OS === 'FreeBSD' ? '/dev/random' : '/dev/urandom';
			// Check random device for special character device protection mode. Use lstat()
			// instead of stat() in case an attacker arranges a symlink to a fake device.
			$lstat = @lstat($device);
			if ($lstat !== false && ($lstat['mode'] & 0170000) === 020000) {
				$this->_randomFile = fopen($device, 'rb') ?: null;
				if (is_resource($this->_randomFile)) {
					// Reduce PHP stream buffer from default 8192 bytes to optimize data
					// transfer from the random device for smaller values of $length.
					// This also helps to keep future randoms out of user memory space.
					$bufferSize = 8;
					if (function_exists('stream_set_read_buffer')) {
						stream_set_read_buffer($this->_randomFile, $bufferSize);
					}
					// stream_set_read_buffer() isn't implemented on HHVM
					if (function_exists('stream_set_chunk_size')) {
						stream_set_chunk_size($this->_randomFile, $bufferSize);
					}
				}
			}
		}
		if (is_resource($this->_randomFile)) {
			$buffer = '';
			$stillNeed = $length;
			while ($stillNeed > 0) {
				$someBytes = fread($this->_randomFile, $stillNeed);
				if ($someBytes === false) {
					break;
				}
				$buffer .= $someBytes;
				$stillNeed -= StringHelper::byteLength($someBytes);
				if ($stillNeed === 0) {
					// Leaving file pointer open in order to make next generation faster by reusing it.
					return $buffer;
				}
			}
			fclose($this->_randomFile);
			$this->_randomFile = null;
		}
		throw new Exception('Unable to generate a random key');
	}

	/**
	 * Generates a random string of specified length.
	 * The string generated matches [A-Za-z0-9_-]+ and is transparent to URL-encoding.
	 *
	 * @param int $length the length of the key in characters
	 * @return string the generated random key
	 * @throws Exception on failure.
	 */
	public function generateRandomString($length = 32)
	{
		if (!is_int($length)) {
			throw new InvalidParamException('First parameter ($length) must be an integer');
		}
		if ($length < 1) {
			throw new InvalidParamException('First parameter ($length) must be greater than 0');
		}
		$bytes = $this->generateRandomKey($length);
		return substr(StringHelper::base64UrlEncode($bytes), 0, $length);
	}

}
<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\web;


use rnd\base\Object;

class Session extends Object
{
	const APP_DEBUG = true;
	/**
	 * @var string the name of the session variable that stores the flash message data.
	 */
	public $flashParam = '__flash';
	/**
	 * @return bool whether the session has started
	 */
	public function getIsActive()
	{
		return session_status() === PHP_SESSION_ACTIVE;
	}
	/**
	 * Starts the session.
	 */
	public function open()
	{
		if ($this->getIsActive()) {
			return;
		}

		@session_start();
	}

	/**
	 * Initializes the application component.
	 * This method is required by IApplicationComponent and is invoked by application.
	 */
	public function init()
	{
		parent::init();
		register_shutdown_function([$this, 'close']);
		$this->open();
		if ($this->getIsActive()) {
			$this->updateFlashCounters();
		}
	}

	/**
	 * Ends the current session and store session data.
	 */
	public function close()
	{
		if ($this->getIsActive()) {
			self::APP_DEBUG ? session_write_close() : @session_write_close();
		}
	}

	/**
	 * Updates the counters for flash messages and removes outdated flash messages.
	 * This method should only be called once in [[init()]].
	 */
	protected function updateFlashCounters()
	{
		$counters = $this->get($this->flashParam, []);
		if (is_array($counters)) {
			foreach ($counters as $key => $count) {
				if ($count > 0) {
					unset($counters[$key], $_SESSION[$key]);
				} elseif ($count == 0) {
					$counters[$key]++;
				}
			}
			$_SESSION[$this->flashParam] = $counters;
		} else {
			// fix the unexpected problem that flashParam doesn't return an array
			unset($_SESSION[$this->flashParam]);
		}
	}

	/**
	 * Returns the session variable value with the session variable name.
	 * If the session variable does not exist, the `$defaultValue` will be returned.
	 * @param string $key the session variable name
	 * @param mixed $defaultValue the default value to be returned when the session variable does not exist.
	 * @return mixed the session variable value, or $defaultValue if the session variable does not exist.
	 */
	public function get($key, $defaultValue = null)
	{
		$this->open();
		return isset($_SESSION[$key]) ? $_SESSION[$key] : $defaultValue;
	}
	/**
	 * Adds a session variable.
	 * If the specified name already exists, the old value will be overwritten.
	 * @param string $key session variable name
	 * @param mixed $value session variable value
	 */
	public function set($key, $value)
	{
		$this->open();
		$_SESSION[$key] = $value;
	}

	/**
	 * @param mixed $key session variable name
	 * @return bool whether there is the named session variable
	 */
	public function has($key)
	{
		$this->open();
		return isset($_SESSION[$key]);
	}

	/**
	 * Removes a session variable.
	 * @param string $key the name of the session variable to be removed
	 * @return mixed the removed value, null if no such session variable.
	 */
	public function remove($key)
	{
		$this->open();
		if (isset($_SESSION[$key])) {
			$value = $_SESSION[$key];
			unset($_SESSION[$key]);

			return $value;
		} else {
			return null;
		}
	}

	/**
	 * Returns a flash message.
	 * @param string $key the key identifying the flash message
	 * @param mixed $defaultValue value to be returned if the flash message does not exist.
	 * @param bool $delete whether to delete this flash message right after this method is called.
	 * If false, the flash message will be automatically deleted in the next request.
	 * @return mixed the flash message or an array of messages if addFlash was used
	 * @see setFlash()
	 * @see addFlash()
	 * @see hasFlash()
	 * @see getAllFlashes()
	 * @see removeFlash()
	 */
	public function getFlash($key, $defaultValue = null, $delete = false)
	{
		$counters = $this->get($this->flashParam, []);
		if (isset($counters[$key])) {
			$value = $this->get($key, $defaultValue);
			if ($delete) {
				$this->removeFlash($key);
			} elseif ($counters[$key] < 0) {
				// mark for deletion in the next request
				$counters[$key] = 1;
				$_SESSION[$this->flashParam] = $counters;
			}

			return $value;
		} else {
			return $defaultValue;
		}
	}

	/**
	 * Returns all flash messages.
	 *
	 * You may use this method to display all the flash messages in a view file:
	 *
	 * ```php
	 * <?php
	 * foreach (Yii::$app->session->getAllFlashes() as $key => $message) {
	 *     echo '<div class="alert alert-' . $key . '">' . $message . '</div>';
	 * } ?>
	 * ```
	 *
	 * With the above code you can use the [bootstrap alert][] classes such as `success`, `info`, `danger`
	 * as the flash message key to influence the color of the div.
	 *
	 * Note that if you use [[addFlash()]], `$message` will be an array, and you will have to adjust the above code.
	 *
	 * [bootstrap alert]: http://getbootstrap.com/components/#alerts
	 *
	 * @param bool $delete whether to delete the flash messages right after this method is called.
	 * If false, the flash messages will be automatically deleted in the next request.
	 * @return array flash messages (key => message or key => [message1, message2]).
	 * @see setFlash()
	 * @see addFlash()
	 * @see getFlash()
	 * @see hasFlash()
	 * @see removeFlash()
	 */
	public function getAllFlashes($delete = false)
	{
		$counters = $this->get($this->flashParam, []);
		$flashes = [];
		foreach (array_keys($counters) as $key) {
			if (array_key_exists($key, $_SESSION)) {
				$flashes[$key] = $_SESSION[$key];
				if ($delete) {
					unset($counters[$key], $_SESSION[$key]);
				} elseif ($counters[$key] < 0) {
					// mark for deletion in the next request
					$counters[$key] = 1;
				}
			} else {
				unset($counters[$key]);
			}
		}

		$_SESSION[$this->flashParam] = $counters;

		return $flashes;
	}

	/**
	 * Removes a flash message.
	 * @param string $key the key identifying the flash message. Note that flash messages
	 * and normal session variables share the same name space.  If you have a normal
	 * session variable using the same name, it will be removed by this method.
	 * @return mixed the removed flash message. Null if the flash message does not exist.
	 * @see getFlash()
	 * @see setFlash()
	 * @see addFlash()
	 * @see removeAllFlashes()
	 */
	public function removeFlash($key)
	{
		$counters = $this->get($this->flashParam, []);
		$value = isset($_SESSION[$key], $counters[$key]) ? $_SESSION[$key] : null;
		unset($counters[$key], $_SESSION[$key]);
		$_SESSION[$this->flashParam] = $counters;

		return $value;
	}

	/**
	 * Removes all flash messages.
	 * Note that flash messages and normal session variables share the same name space.
	 * If you have a normal session variable using the same name, it will be removed
	 * by this method.
	 * @see getFlash()
	 * @see setFlash()
	 * @see addFlash()
	 * @see removeFlash()
	 */
	public function removeAllFlashes()
	{
		$counters = $this->get($this->flashParam, []);
		foreach (array_keys($counters) as $key) {
			unset($_SESSION[$key]);
		}
		unset($_SESSION[$this->flashParam]);
	}

	/**
	 * Returns a value indicating whether there are flash messages associated with the specified key.
	 * @param string $key key identifying the flash message type
	 * @return bool whether any flash messages exist under specified key
	 */
	public function hasFlash($key)
	{
		return $this->getFlash($key) !== null;
	}

	/**
	 * Sets a flash message.
	 * A flash message will be automatically deleted after it is accessed in a request and the deletion will happen
	 * in the next request.
	 * If there is already an existing flash message with the same key, it will be overwritten by the new one.
	 * @param string $key the key identifying the flash message. Note that flash messages
	 * and normal session variables share the same name space. If you have a normal
	 * session variable using the same name, its value will be overwritten by this method.
	 * @param mixed $value flash message
	 * @param bool $removeAfterAccess whether the flash message should be automatically removed only if
	 * it is accessed. If false, the flash message will be automatically removed after the next request,
	 * regardless if it is accessed or not. If true (default value), the flash message will remain until after
	 * it is accessed.
	 * @see getFlash()
	 * @see addFlash()
	 * @see removeFlash()
	 */
	public function setFlash($key, $value = true, $removeAfterAccess = true)
	{
		$counters = $this->get($this->flashParam, []);
		$counters[$key] = $removeAfterAccess ? -1 : 0;
		$_SESSION[$key] = $value;
		$_SESSION[$this->flashParam] = $counters;
	}


	/**
	 * Adds a flash message.
	 * If there are existing flash messages with the same key, the new one will be appended to the existing message array.
	 * @param string $key the key identifying the flash message.
	 * @param mixed $value flash message
	 * @param bool $removeAfterAccess whether the flash message should be automatically removed only if
	 * it is accessed. If false, the flash message will be automatically removed after the next request,
	 * regardless if it is accessed or not. If true (default value), the flash message will remain until after
	 * it is accessed.
	 * @see getFlash()
	 * @see setFlash()
	 * @see removeFlash()
	 */
	public function addFlash($key, $value = true, $removeAfterAccess = true)
	{
		$counters = $this->get($this->flashParam, []);
		$counters[$key] = $removeAfterAccess ? -1 : 0;
		$_SESSION[$this->flashParam] = $counters;
		if (empty($_SESSION[$key])) {
			$_SESSION[$key] = [$value];
		} else {
			if (is_array($_SESSION[$key])) {
				$_SESSION[$key][] = $value;
			} else {
				$_SESSION[$key] = [$_SESSION[$key], $value];
			}
		}
	}

	/**
	 * Sets the session ID.
	 * This is a wrapper for [PHP session_id()](http://php.net/manual/en/function.session-id.php).
	 * @param string $value the session ID for the current session
	 */
	public function setId($value)
	{
		session_id($value);
	}

	/**
	 * Frees all session variables and destroys all data registered to a session.
	 *
	 * This method has no effect when session is not [[getIsActive()|active]].
	 * Make sure to call [[open()]] before calling it.
	 * @see open()
	 * @see isActive
	 */
	public function destroy()
	{
		if ($this->getIsActive()) {
			$sessionId = session_id();
			$this->close();
			$this->setId($sessionId);
			$this->open();
			session_unset();
			session_destroy();
			$this->setId($sessionId);
		}
	}
}
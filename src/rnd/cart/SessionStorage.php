<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\cart;

use Rnd;
use rnd\base\RndObject;


/**
 * Class SessionStorage is a session adapter for cart data storage.
 *
 * @property \rnd\web\Session session
 */
class SessionStorage extends RndObject implements StorageInterface
{
	/**
	 * @var string
	 */
	public $key = 'cart';

	/**
	 * @inheritdoc
	 */
	public function load(Cart $cart)
	{
		$cartData = [];

		if (false !== ($session = ($this->session->get($this->key, false)))) {
			$cartData = unserialize($session);
		}

		return $cartData;
	}

	/**
	 * @inheritdoc
	 */
	public function save(Cart $cart)
	{
		$sessionData = serialize($cart->getItems());

		$this->session->set($this->key, $sessionData);
	}

	/**
	 * @return object
	 */
	public function getSession()
	{
		return Rnd::$app->get('session');
	}
}
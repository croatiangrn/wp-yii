<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\cart;

/**
 * Interface StorageInterface
 *
 * @package yii2mod\cart\storage
 */
interface StorageInterface
{
	/**
	 * @param Cart $cart
	 *
	 * @return mixed
	 */
	public function load(Cart $cart);

	/**
	 * @param Cart $cart
	 */
	public function save(Cart $cart);
}
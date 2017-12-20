<?php
/**
 * @author: Marko Mikulic
 */

namespace App\payments;


use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

class PayPalCreds {
	static $clientID = '';
	static $clientSecret = '';

	public static function getCredentials($mode = 'debug') {
		$obj =  new ApiContext(
			new OAuthTokenCredential(
				static::$clientID,
				static::$clientSecret
			)
		);

		if ($mode === 'debug')
		{
			$obj->setConfig([
				'mode' => 'sandbox',
				'log.LogLevel' => 'DEBUG',
			]);
		} else if ($mode === 'info') {
			$obj->setConfig([
				'mode' => 'live',
				'log.LogLevel' => 'INFO',
			]);
		}

		return $obj;
	}
}
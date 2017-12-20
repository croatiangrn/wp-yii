<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\payments;


use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use rnd\base\Component;

class PayPalCreds extends Component {
	public $clientID = '';
	public $clientSecret = '';
	public $mode = 'debug';


	public function getCredentials() {
		$obj =  new ApiContext(
			new OAuthTokenCredential(
				$this->clientID,
				$this->clientSecret
			)
		);

		if ($this->mode === 'debug')
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
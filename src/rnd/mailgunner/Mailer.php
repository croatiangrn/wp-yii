<?php
/**
 * @author: Marko Mikulic
 */

namespace rnd\mailgunner;

use Mailgun\Mailgun;
use Rnd;
use rnd\base\InvalidConfigException;
use rnd\mail\BaseMailer;


/**
 * Mailer implements a mailer based on Mailgun.
 *
 * To use Mailer, you should configure it in the application configuration like the following,
 *
 * ~~~
 * 'components' => [
 *     ...
 *     'mailer' => [
 *         'class' => 'boundstate\mailgun\Mailer',
 *         'key' => 'key-example',
 *         'domain' => 'mg.example.com',
 *     ],
 *     ...
 * ],
 * ~~~
 *
 * To send an email, you may use the following code:
 *
 * ~~~
 * Yii::$app->mailer->compose('contact/html', ['contactForm' => $form])
 *     ->setFrom('from@domain.com')
 *     ->setTo($form->email)
 *     ->setSubject($form->subject)
 *     ->send();
 * ~~~
 */
class Mailer extends BaseMailer
{
	/**
	 * @var string message default class name.
	 */
	public $messageClass = 'rnd\mailer\Message';

	/**
	 * @var string Mailgun API credentials.
	 */
	public $key;

	/**
	 * @var string Mailgun domain.
	 */
	public $domain;

	/**
	 * @var Mailgun Mailgun instance.
	 */
	private $_mailgun;

	/**
	 * @return Mailgun
	 * @throws InvalidConfigException
	 */
	public function getMailgun()
	{
		if (!is_object($this->_mailgun)) {
			$this->_mailgun = $this->createMailgun();
		}

		return $this->_mailgun;
	}

	/**
	 * @param \rnd\mailgunner\Message $message
	 *
	 * @return bool
	 * @throws InvalidConfigException
	 */
	protected function sendMessage($message)
	{
		Rnd::info('Sending email', __METHOD__);

		$this->getMailgun()->post("{$this->domain}/messages",
			$message->getMessageBuilder()->getMessage(),
			$message->getMessageBuilder()->getFiles());

		return true;
	}

	/**
	 * Creates Mailgun instance.
	 * @return Mailgun Mailgun instance.
	 * @throws InvalidConfigException if required params are not set.
	 */
	protected function createMailgun()
	{
		if (!$this->key) {
			throw new InvalidConfigException('Mailer::key must be set.');
		}
		if (!$this->domain) {
			throw new InvalidConfigException('Mailer::domain must be set.');
		}
		return new Mailgun($this->key);
	}
}
<?php

namespace rnd\validators;

use Rnd;
use rnd\base\Exception;
use rnd\base\InvalidConfigException;
use rnd\httpclient\Client as HttpClient;
use rnd\httpclient\Request as HttpClientRequest;


/**
 * ReCaptcha widget validator.
 *
 * @author  HimikLab
 * @package himiklab\yii2\recaptcha
 */
class ReCaptchaValidator extends Validator
{
    const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /** @var boolean Whether to skip this validator if the input is empty. */
    public $skipOnEmpty = false;

    /** @var string The shared key between your site and ReCAPTCHA. */
    public $secret;

    /** @var string */
    public $uncheckedMessage;

    /** @var \rnd\httpclient\Request */
    public $httpClientRequest;

    /** @var boolean */
    protected $isValid = false;

    public function init() {
        parent::init();

        if (empty($this->secret)) {
            /** @var ReCaptcha $reCaptcha */
            $reCaptcha = Rnd::$app->reCaptcha;
            if ($reCaptcha && !empty($reCaptcha->secret)) {
                $this->secret = $reCaptcha->secret;
            } else {
                throw new InvalidConfigException('Required `secret` param isn\'t set.');
            }
        }

        if (empty($this->httpClientRequest) || !($this->httpClientRequest instanceof HttpClientRequest)) {
            $this->httpClientRequest = (new HttpClient())->createRequest();
        }

        if ($this->message === null) {
            $this->message = Rnd::t('The verification code is incorrect.', 'rnd');
        }
    }

    /**
     * @param \rnd\base\Model $model
     * @param string $attribute
     * @param View $view
     * @return string
     */
    public function clientValidateAttribute($model, $attribute, $view) {
        $message = addslashes($this->uncheckedMessage ?: Rnd::t(
            'yii',
            '{attribute} cannot be blank.',
            ['attribute' => $model->getAttributeLabel($attribute)]
        ));

        return <<<JS
if (!value) {
     messages.push("{$message}");
}
JS;
    }

    /**
     * @param string|array $value
     * @return array|null
     * @throws Exception
     * @throws \rnd\base\InvalidParamException
     */
    protected function validateValue($value) {
        if (!$this->isValid) {
            if (!empty($value)) {
                $response = $this->getResponse($value);
                if (!isset($response['success'])) {
                    throw new Exception('Invalid recaptcha verify response.');
                }

                $this->isValid = (boolean)$response['success'];
            } else {
                $this->isValid = false;
            }
        }

        return $this->isValid ? null : [$this->message, []];
    }

    /**
     * @param string $value
     * @return array
     * @throws Exception
     * @throws \rnd\base\InvalidParamException
     */
    protected function getResponse($value) {
        $response = $this->httpClientRequest
            ->setMethod('GET')
            ->setUrl(self::SITE_VERIFY_URL)
            ->setData(['secret' => $this->secret, 'response' => $value, 'remoteip' => Rnd::$app->request->userIP])
            ->send();
        if (!$response->isOk) {
            throw new Exception('Unable connection to the captcha server. Status code ' . $response->statusCode);
        }

        return $response->data;
    }
}
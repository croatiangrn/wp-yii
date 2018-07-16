<?php
/**
 * @author: Marko Mikulic <mikulic.marko@spark.ba>
 */

namespace rnd\elasticmailer;


use ElasticEmailApi\Email;
use ElasticEmailClient\ApiConfiguration;
use ElasticEmailClient\ElasticClient;
use ElasticEmailEnums\EncodingType;
use rnd\base\InvalidConfigException;
use rnd\mail\BaseMailer;

class Mailer extends BaseMailer
{
    public $messageClass = 'rnd\elasticmailer\Message';

    public $apiUrl;
    public $apiKey;

    /**
     * @var
     */
    private $_elastic_mailer;

    /**
     * @return mixed
     * @throws InvalidConfigException
     */
    public function getElasticMailer() {
        if (!is_object($this->_elastic_mailer)) {
            $this->_elastic_mailer = $this->createElasticMailer();
        }

        return $this->_elastic_mailer;
    }

    /**
     * @return Email
     * @throws InvalidConfigException
     */
    public function createElasticMailer() {
        if (!$this->apiUrl) {
            throw new InvalidConfigException('ElasticMailer::apiUrl must be set.');
        }
        if (!$this->apiKey) {
            throw new InvalidConfigException('ElasticMailer::apiKey must be set.');
        }

        $apiConfiguration = new ApiConfiguration([
            'apiUrl' => $this->apiUrl,
            'apiKey' => $this->apiKey,
        ]);

        $mailClient = new ElasticClient($apiConfiguration);
        return $mailClient->Email;
    }

    /**
     * Sends the specified message.
     * This method should be implemented by child classes with the actual email sending logic.
     * @param Message $message the message to be sent
     * @return bool whether the message is sent successfully
     * @throws InvalidConfigException
     */
    protected function sendMessage($message) {
        $this->createElasticMailer()->Send(
            $message->getSubject(),
            $message->getFrom(),
            $message->getFromName(),
            null,
            null,
            null,
            null,
            null,
            null,
            $message->getTo(),
            [],
            [],
            [],
            [],
            [],
            [],
            [],
            [],
            $message->getHtmlBody(),
            [],
            $message->getCharset(),
            $message->getCharset(),
            $message->getCharset(),
            EncodingType::None,
            null,
            $message->getAttachments(),
            [],
            null,
            [],
            null,
            null,
            $message->getIsTransactional()
        );
        return true;
    }
}
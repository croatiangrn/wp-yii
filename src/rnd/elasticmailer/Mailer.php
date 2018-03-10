<?php
/**
 * @author: Marko Mikulic <mikulic.marko@spark.ba>
 */

namespace rnd\elasticmailer;


use rnd\mail\BaseMailer;
use rnd\mail\MessageInterface;

class Mailer extends BaseMailer
{

    /**
     * Sends the specified message.
     * This method should be implemented by child classes with the actual email sending logic.
     * @param MessageInterface $message the message to be sent
     * @return bool whether the message is sent successfully
     */
    protected function sendMessage($message) {

    }
}
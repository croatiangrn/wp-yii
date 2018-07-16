<?php
/**
 * @author: Marko Mikulic <mikulic.marko@spark.ba>
 */

namespace rnd\elasticmailer;


use Rnd;
use rnd\base\InvalidParamException;
use rnd\base\NotSupportedException;
use rnd\mail\BaseMessage;
use rnd\mail\MessageInterface;

class Message extends BaseMessage
{
    public $charset;
    protected $subject;
    protected $from;
    protected $fromName;
    protected $to;
    protected $htmlBody;
    protected $attachments;
    protected $isTransactional;

    /**
     * Returns the character set of this message.
     * @return string the character set of this message.
     */
    public function getCharset() {
        return 'UTF-8';
    }

    /**
     * Sets the character set of this message.
     * @param string $charset character set name.
     * @return void self reference.
     * @throws NotSupportedException
     */
    public function setCharset($charset) {
        throw new NotSupportedException();
    }

    /**
     * Returns the message sender.
     * @return string the sender
     */
    public function getFrom() {
        return $this->from;
    }

    /**
     * Sets the message sender.
     * @param string|array $from sender email address.
     *                           You may pass an array of addresses if this message is from multiple people.
     *                           You may also specify sender name in addition to email address using format:
     *                           `[email => name]`.
     * @return Message
     */
    public function setFrom($from) {
        $this->from = $from;
        return $this;
    }

    /**
     * Returns the message recipient(s).
     * @return array the message recipients
     */
    public function getTo() {
        return $this->to;
    }

    /**
     * Sets the message recipient(s).
     * @param string|array $to receiver email address.
     *                         You may pass an array of addresses if multiple recipients should receive this message.
     *                         You may also specify receiver name in addition to email address using format:
     *                         `[email => name]`.
     * @return $this self reference.
     */
    public function setTo($to) {
        if (!is_array($to)) {
            $arr = [];
            $to = array_merge($arr, [$to]);
        }
        $this->to = $to;
        return $this;
    }

    /**
     * Returns the reply-to address of this message.
     * @return string the reply-to address of this message.
     */
    public function getReplyTo() {
        // TODO: Implement getReplyTo() method.
    }

    /**
     * Sets the reply-to address of this message.
     * @param string|array $replyTo the reply-to address.
     *                              You may pass an array of addresses if this message should be replied to multiple people.
     *                              You may also specify reply-to name in addition to email address using format:
     *                              `[email => name]`.
     * @return $this self reference.
     */
    public function setReplyTo($replyTo) {
        // TODO: Implement setReplyTo() method.
    }

    /**
     * Returns the Cc (additional copy receiver) addresses of this message.
     * @return array the Cc (additional copy receiver) addresses of this message.
     */
    public function getCc() {
        // TODO: Implement getCc() method.
    }

    /**
     * Sets the Cc (additional copy receiver) addresses of this message.
     * @param string|array $cc copy receiver email address.
     *                         You may pass an array of addresses if multiple recipients should receive this message.
     *                         You may also specify receiver name in addition to email address using format:
     *                         `[email => name]`.
     * @return $this self reference.
     */
    public function setCc($cc) {
        // TODO: Implement setCc() method.
    }

    /**
     * Returns the Bcc (hidden copy receiver) addresses of this message.
     * @return array the Bcc (hidden copy receiver) addresses of this message.
     */
    public function getBcc() {
        // TODO: Implement getBcc() method.
    }

    /**
     * Sets the Bcc (hidden copy receiver) addresses of this message.
     * @param string|array $bcc hidden copy receiver email address.
     *                          You may pass an array of addresses if multiple recipients should receive this message.
     *                          You may also specify receiver name in addition to email address using format:
     *                          `[email => name]`.
     * @return $this self reference.
     */
    public function setBcc($bcc) {
        // TODO: Implement setBcc() method.
    }

    /**
     * Returns the message subject.
     * @return string the message subject
     */
    public function getSubject() {
        return $this->subject;
    }

    /**
     * Sets the message subject.
     * @param string $subject message subject
     * @return $this self reference.
     */
    public function setSubject($subject) {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Sets message plain text content.
     * @param string $text message plain text content.
     * @return $this self reference.
     */
    public function setTextBody($text) {
        // TODO: Implement setTextBody() method.
    }

    /**
     * Sets message HTML content.
     * @param string $html message HTML content.
     * @return $this self reference.
     */
    public function setHtmlBody($html) {
        $this->htmlBody = $html;
        return $this;
    }

    /**
     * Attaches existing file to the email message.
     * @param string $fileName full file name
     * @param array $options   options for embed file. Valid options are:
     *
     * - fileName: name, which should be used to attach file.
     * - contentType: attached file MIME type.
     *
     * @return $this self reference.
     */
    public function attach($fileName, array $options = []) {
        // TODO: Implement attach() method.
    }

    /**
     * Attach specified content as file for the email message.
     * @param string $content attachment file content.
     * @param array $options  options for embed file. Valid options are:
     *
     * - fileName: name, which should be used to attach file.
     * - contentType: attached file MIME type.
     *
     * @return $this self reference.
     */
    public function attachContent($content, array $options = []) {
        // TODO: Implement attachContent() method.
    }

    /**
     * Attach a file and return it's CID source.
     * This method should be used when embedding images or other data in a message.
     * @param string $fileName file name.
     * @param array $options   options for embed file. Valid options are:
     *
     * - fileName: name, which should be used to attach file.
     * - contentType: attached file MIME type.
     *
     * @return string attachment CID.
     */
    public function embed($fileName, array $options = []) {
        // TODO: Implement embed() method.
    }

    /**
     * Attach a content as file and return it's CID source.
     * This method should be used when embedding images or other data in a message.
     * @param string $content attachment file content.
     * @param array $options  options for embed file. Valid options are:
     *
     * - fileName: name, which should be used to attach file.
     * - contentType: attached file MIME type.
     *
     * @return string attachment CID.
     */
    public function embedContent($content, array $options = []) {
        // TODO: Implement embedContent() method.
    }

    /**
     * Returns string representation of this message.
     * @return string the string representation of this message.
     */
    public function toString() {
        // TODO: Implement toString() method.
    }

    /**
     * @return mixed
     */
    public function getHtmlBody() {
        return $this->htmlBody;
    }

    /**
     * @return mixed
     */
    public function getFromName() {
        return $this->fromName;
    }

    /**
     * @param mixed $fromName
     * @return $this self reference
     */
    public function setFromName($fromName) {
        $this->fromName = $fromName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAttachments() {
        if ($this->attachments === null) {
            $this->attachments = [];
        } else if (!is_array($this->attachments)) {
            $this->attachments = [$this->attachments];
        }

        return $this->attachments;
    }

    /**
     * @param array $attachments
     * @throws InvalidParamException
     * @return Message
     */
    public function setAttachments($attachments) {
        foreach ($attachments as $file) {
            if (!is_file($file)) {
                throw new InvalidParamException("Message::attachment " . $file . " does not exist!");
            }
        }
        $this->attachments = $attachments;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsTransactional() {
        return $this->isTransactional;
    }

    /**
     * @param mixed $isTransactional
     * @return Message
     */
    public function setIsTransactional($isTransactional) {
        $this->isTransactional = $isTransactional;
        return $this;
    }
}
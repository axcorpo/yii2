<?php

namespace tws\sms;

/**
 * MessageInterface is the interface that should be implemented by sms message classes.
 *
 * A message represents the settings and content of an sms, such as the sender, recipient,
 * body of the message, etc.
 *
 * Messages are sent by a [[\tws\sms\SmsInterface|sms]], like the following,
 *
 * ```php
 * Yii::$app->sms->compose('text-message', ['user' => $user])
 *     ->setFrom('12345')
 *     ->setTo('+15552224444')
 *     ->send();
 * ```
 *
 * -- or --
 *
 * ```php
 * Yii::$app->sms->compose()
 *     ->setFrom('12345')
 *     ->setTo('+1234567890')
 *     ->setMessage('Hello, This is a test message.')
 *     ->send();
 * ```
 *
 * @see SmsInterface
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
interface MessageInterface
{
	/**
	 * Returns the message sender.
	 * @return string the sender phone number.
	 */
	public function getFrom();

	/**
	 * Sets the message sender.
	 * @param string $from sender phone number.
	 * @return $this self reference.
	 */
	public function setFrom($from);

	/**
	 * Returns the message recipient.
	 * @return string the message recipient.
	 */
	public function getTo();

	/**
	 * Sets the message recipient.
	 * @param string|array $to receiver phone number(s).
	 * @return $this self reference.
	 */
	public function setTo($to);

	/**
	 * Sets message plain text content.
	 * @param string $text message plain text content.
	 * @return $this self reference.
	 */
	public function setTextBody($text);

	/**
	 * Returns the character set of this message.
	 * @return string the character set of this message.
	 */
	public function getCharset();

	/**
	 * Sets the character set of this message.
	 * @param string $charset character set name.
	 * @return $this self reference.
	 */
	public function setCharset($charset);

	/**
	 * Sends this sms message.
	 * @param SmsInterface $sms the transport that should be used to send this message.
	 * If null, the "sms" application component will be used instead.
	 * @return bool whether this message is sent successfully.
	 */
	public function send(SmsInterface $sms = null);

	/**
	 * Returns string representation of this message.
	 * @return string the string representation of this message.
	 */
	public function toString();
}

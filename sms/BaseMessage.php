<?php

namespace tws\sms;

use Yii;
use yii\base\BaseObject;
use yii\base\ErrorHandler;

/**
 * BaseMessage serves as a base class that implements the [[send()]] method required by [[MessageInterface]].
 *
 * By default, [[send()]] will use the "sms" application component to send the current message.
 * The "sms" application component should be a sms instance implementing [[SmsInterface]].
 *
 * @see BaseSms
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
abstract class BaseMessage extends BaseObject implements MessageInterface
{
	/**
	 * @var SmsInterface the sms instance that created this message.
	 * For independently created messages this is `null`.
	 */
	public $sms;


	/**
	 * Sends this sms message.
	 * @param SmsInterface $sms the sms that should be used to send this message.
	 * If no sms is given it will first check if [[sms]] is set and if not,
	 * the "sms" application component will be used instead.
	 * @return bool whether this message is sent successfully.
	 */
	public function send(SmsInterface $sms = null)
	{
		if ($sms === null && $this->sms === null) {
			$sms = Yii::$app->getSms();
		} elseif ($sms === null) {
			$sms = $this->sms;
		}

		return $sms->send($this);
	}

	/**
	 * PHP magic method that returns the string representation of this object.
	 * @return string the string representation of this object.
	 */
	public function __toString()
	{
		// __toString cannot throw exception
		// use trigger_error to bypass this limitation
		try {
			return $this->toString();
		} catch (\Exception $e) {
			ErrorHandler::convertExceptionToError($e);
			return '';
		}
	}
}

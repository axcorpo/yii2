<?php

namespace tws\sms;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\ViewContextInterface;
use yii\web\View;

/**
 * BaseSms serves as a base class that implements the basic functions required by [[SmsInterface]].
 *
 * Concrete child classes should may focus on implementing the [[sendMessage()]] method.
 *
 * @see BaseMessage
 *
 * @property View $view View instance. Note that the type of this property differs in getter and setter. See
 * [[getView()]] and [[setView()]] for details.
 * @property string $viewPath The directory that contains the view files for composing sms messages Defaults
 * to '@app/sms'.
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
abstract class BaseSms extends Component implements SmsInterface, ViewContextInterface
{
	/**
	 * @event SmsEvent an event raised right before send.
	 * You may set [[SmsEvent::isValid]] to be false to cancel the send.
	 */
	const EVENT_BEFORE_SEND = 'beforeSend';

	/**
	 * @event SmsEvent an event raised right after send.
	 */
	const EVENT_AFTER_SEND = 'afterSend';

	/**
	 * @var string the default class name of the new message instances created by [[createMessage()]]
	 */
	public $messageClass = 'tws\sms\BaseMessage';

	/**
	 * @var array the configuration that should be applied to any newly created
	 * sms message instance by [[createMessage()]] or [[compose()]]. Any valid property defined
	 * by [[MessageInterface]] can be configured, such as `from`, `to`, `textBody`, etc.
	 *
	 * For example:
	 *
	 * ```php
	 * [
	 *     'charset' => 'UTF-8',
	 *     'from' => '12345',
	 * ]
	 * ```
	 */
	public $messageConfig = [];

	/**
	 * @var MessageInterface the the sms message.
	 */
	private $_message;

	/**
	 * @var bool whether to save sms messages as files under [[fileTransportPath]] instead of sending them
	 * to the actual recipients. This is usually used during development for debugging purpose.
	 * @see fileTransportPath
	 */
	public $useFileTransport = false;

	/**
	 * @var string the directory where the sms messages are saved when [[useFileTransport]] is true.
	 */
	public $fileTransportPath = '@runtime/sms';

	/**
	 * @var callable a PHP callback that will be called by [[send()]] when [[useFileTransport]] is true.
	 * The callback should return a file name which will be used to save the sms message.
	 * If not set, the file name will be generated based on the current timestamp.
	 *
	 * The signature of the callback is:
	 *
	 * ```php
	 * function ($sms, $message)
	 * ```
	 */
	public $fileTransportCallback;

	/**
	 * @var \yii\base\View|array view instance or its array configuration.
	 */
	private $_view = [];

	/**
	 * @var string the directory containing view files for composing sms messages.
	 */
	private $_viewPath;


	/**
	 * Creates a new message instance and optionally composes its body content via view rendering.
	 *
	 * @param string|array|null $view the view to be used for rendering the message body. This can be:
	 *
	 * - a string, which represents the view name or [path alias](guide:concept-aliases) for rendering the text body of the sms.
	 * - null, meaning the message instance will be returned without body content.
	 *
	 * The view to be rendered can be specified in one of the following formats:
	 *
	 * - path alias (e.g. "@app/sms/contact");
	 * - a relative view name (e.g. "contact") located under [[viewPath]].
	 *
	 * @param array $params the parameters (name-value pairs) that will be extracted and made available in the view file.
	 * @return MessageInterface message instance.
	 */
	public function compose($view = null, array $params = [])
	{
		$message = $this->createMessage();
		if ($view === null) {
			return $message;
		}

		if (!array_key_exists('message', $params)) {
			$params['message'] = $message;
		}

		$this->_message = $message;

		$text = $this->render($view, $params, false);

		$this->_message = null;

		if (isset($text)) {
			$message->setTextBody($text);
		}

		return $message;
	}

	/**
	 * Creates a new message instance.
	 * The newly created instance will be initialized with the configuration specified by [[messageConfig]].
	 * If the configuration does not specify a 'class', the [[messageClass]] will be used as the class
	 * of the new message instance.
	 * @return MessageInterface message instance.
	 */
	protected function createMessage()
	{
		$config = $this->messageConfig;
		if (!array_key_exists('class', $config)) {
			$config['class'] = $this->messageClass;
		}
		$config['sms'] = $this;

		/* @var $message MessageInterface */
		$message = Yii::createObject($config);

		return $message;
	}

	/**
	 * Sends the given sms message.
	 * This method will log a message about the sms being sent.
	 * If [[useFileTransport]] is true, it will save the sms as a file under [[fileTransportPath]].
	 * Otherwise, it will call [[sendMessage()]] to send the sms to its recipient(s).
	 * Child classes should implement [[sendMessage()]] with the actual sms sending logic.
	 * @param MessageInterface $message sms message instance to be sent.
	 * @return bool whether the message has been sent successfully.
	 */
	public function send($message)
	{
		if (!$this->beforeSend($message)) {
			return false;
		}

		$to = $message->getTo();
		if (is_array($to)) {
			$to = implode(', ', array_keys($to));
		}
		Yii::info("Sending sms to {$to}", __METHOD__);

		if ($this->useFileTransport) {
			$isSuccessful = $this->saveMessage($message);
		} else {
			$isSuccessful = $this->sendMessage($message);
		}
		$this->afterSend($message, $isSuccessful);

		return $isSuccessful;
	}

	/**
	 * Sends multiple messages at once.
	 *
	 * The default implementation simply calls [[send()]] multiple times.
	 * Child classes may override this method to implement more efficient way of
	 * sending multiple messages.
	 *
	 * @param array|MessageInterface[] $messages list of sms messages, which should be sent.
	 * @return int number of messages that are successfully sent.
	 */
	public function sendMultiple(array $messages)
	{
		$successCount = 0;

		foreach ($messages as $message) {
			if ($this->send($message)) {
				$successCount++;
			}
		}

		return $successCount;
	}

	/**
	 * Sends the specified message.
	 * This method should be implemented by child classes with the actual sms sending logic.
	 * @param MessageInterface|MessageInterface[] $message the message to be sent.
	 * @return bool whether the message is sent successfully.
	 */
	abstract protected function sendMessage($message);

	/**
	 * Saves the message as a file under [[fileTransportPath]].
	 * @param MessageInterface|MessageInterface[] $message the message to be saved.
	 * @return bool|int whether the message is saved successfully.
	 */
	protected function saveMessage($message)
	{
		$path = Yii::getAlias($this->fileTransportPath);
		if (!is_dir($path)) {
			mkdir($path, 0777, true);
		}
		if ($this->fileTransportCallback !== null) {
			$file = $path . '/' . call_user_func($this->fileTransportCallback, $this, $message);
		} else {
			$file = $path . '/' . $this->generateMessageFileName();
		}

		// Save multiple messages into a single file
		$content = [];
		$messages = $message;
		if (is_array($messages)) {
			$returnValue = count($messages);
		} else {
			$messages = [$message];
			$returnValue = true;
		}
		foreach ($messages as $message) {
			$content[] = implode(PHP_EOL, [
				"From: {$message->getFrom()}",
				"To: {$message->getTo()}",
				"Message: {$message->toString()}",
			]);
		}
		file_put_contents($file, implode(str_pad(PHP_EOL, 30, '-') . PHP_EOL, $content));

		return $returnValue;
	}

	/**
	 * @return string the file name for saving the message when [[useFileTransport]] is true.
	 */
	public function generateMessageFileName()
	{
		$time = microtime(true);

		return date('Ymd-His-', $time) . sprintf('%04d', (int) (($time - (int) $time) * 10000)) . '-' . sprintf('%04d', mt_rand(0, 10000)) . '.sms';
	}

	/**
	 * @return string the directory that contains the view files for composing sms messages
	 * Defaults to '@app/sms'.
	 */
	public function getViewPath()
	{
		if ($this->_viewPath === null) {
			$this->setViewPath('@app/sms');
		}

		return $this->_viewPath;
	}

	/**
	 * @param string $path the directory that contains the view files for composing sms messages
	 * This can be specified as an absolute path or a [path alias](guide:concept-aliases).
	 */
	public function setViewPath($path)
	{
		$this->_viewPath = Yii::getAlias($path);
	}

	/**
	 * @return View view instance.
	 */
	public function getView()
	{
		if (!is_object($this->_view)) {
			$this->_view = $this->createView($this->_view);
		}

		return $this->_view;
	}

	/**
	 * @param array|View $view view instance or its array configuration that will be used to render message bodies.
	 * @throws InvalidConfigException on invalid argument.
	 */
	public function setView($view)
	{
		if (!is_array($view) && !is_object($view)) {
			throw new InvalidConfigException('"' . get_class($this) . '::view" should be either object or configuration array, "' . gettype($view) . '" given.');
		}
		$this->_view = $view;
	}

	/**
	 * Creates view instance from given configuration.
	 * @param array $config view configuration.
	 * @return View view instance.
	 */
	protected function createView(array $config)
	{
		if (!array_key_exists('class', $config)) {
			$config['class'] = View::class;
		}
		return Yii::createObject($config);
	}

	/**
	 * Renders the specified view with optional parameters and layout.
	 * The view will be rendered using the [[view]] component.
	 * @param string $view the view name or the [path alias](guide:concept-aliases) of the view file.
	 * @param array $params the parameters (name-value pairs) that will be extracted and made available in the view file.
	 * @param string|bool $layout layout view name or [path alias](guide:concept-aliases). If false, no layout will be applied.
	 * @return string the rendering result.
	 */
	public function render($view, $params = [], $layout = false)
	{
		$output = $this->getView()->render($view, $params, $this);
		if ($layout !== false) {
			return $this->getView()->render($layout, ['content' => $output, 'message' => $this->_message], $this);
		}
		return $output;
	}

	/**
	 * This method is invoked right before sms send.
	 * You may override this method to do last-minute preparation for the message.
	 * If you override this method, please make sure you call the parent implementation first.
	 * @param MessageInterface|MessageInterface[] $message
	 * @return bool whether to continue sending an sms.
	 */
	public function beforeSend($message)
	{
		$event = new SmsEvent(['message' => $message]);
		$this->trigger(self::EVENT_BEFORE_SEND, $event);

		return $event->isValid;
	}

	/**
	 * This method is invoked right after sms was send.
	 * You may override this method to do some postprocessing or logging based on sms send status.
	 * If you override this method, please make sure you call the parent implementation first.
	 * @param MessageInterface|MessageInterface[] $message
	 * @param bool|int $isSuccessful
	 */
	public function afterSend($message, $isSuccessful)
	{
		$event = new SmsEvent(['message' => $message, 'isSuccessful' => $isSuccessful]);
		$this->trigger(self::EVENT_AFTER_SEND, $event);
	}
}

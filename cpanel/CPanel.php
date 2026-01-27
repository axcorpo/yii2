<?php

namespace tws\cpanel;

use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\httpclient\Client;

/**
 * CPanelClient is a wrapper component for the cPanel API.
 *
 * Example of usage:
 *
 * ```php
 * Yii::$app->cPanel->uapi->Mysql->create_database(['name' => 'DB_NAME'])
 * ```
 *
 * @link https://blog.cpanel.com/introduction_to_cpanel_whm_apis/
 * @link https://documentation.cpanel.net/display/DD/Guide+to+UAPI
 * @link https://github.com/N1ghteyes/cpanel-UAPI-php-class
 *
 * @property object $uapi cPanel UAPI accesses the cPanel interface's features.
 * @property object $api2 cPanel API 2 accesses cPanel account information and modifies settings.
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
class CPanel extends Component
{
	/**
	 * @var string The cPanel API base URL.
	 */
	public $baseUrl;

	/**
	 * @var string The cPanel username.
	 */
	public $username;

	/**
	 * @var string The cPanel password.
	 */
	public $password;

	/**
	 * @var string Thee cPanel UAPI or API2.
	 */
	protected $api;

	/**
	 * @var string The CURL request method. Defaults to GET.
	 */
	protected $method = 'GET';

	/**
	 * @var string The cPanel API module name.
	 */
	protected $module;

	/**
	 * @inheritdoc
	 * @throws InvalidConfigException
	 */
	public function init()
	{
		parent::init();

		if (!filter_var($this->baseUrl, FILTER_VALIDATE_URL)) {
			throw new InvalidConfigException('The "baseUrl" property must be a valid URL.');
		}
		if (empty($this->username) || empty($this->password)) {
			throw new InvalidConfigException('The "username" and "password" properties must be set to a non-empty value.');
		}
	}

	/**
	 * Makes an HTTP request to the API.
	 *
	 * @param string $function The API function name.
	 * @param array $data
	 * @return mixed
	 * @throws InvalidConfigException
	 */
	protected function makeRequest($function, $data = [])
	{
		$url = '';

		if ($this->api === 'uapi') {
			$url = "/execute/{$this->module}/{$function}";
			if (!empty($data)) {
				$url .= '?';
			}
		} elseif ($this->api === 'api2') {
			$url = '/json-api/cpanel?' . http_build_query([
				'cpanel_jsonapi_user' => $this->username,
				'cpanel_jsonapi_apiversion' => 2,
				'cpanel_jsonapi_module' => $this->module,
				'cpanel_jsonapi_func' => $function,
			]);
			if (!empty($data)) {
				$url .= '&';
			}
		}

		if ($this->method === 'GET') {
			$url .= http_build_query($data);
		}

		$client = new Client([
			'transport' => 'yii\httpclient\CurlTransport',
			'baseUrl' => $this->baseUrl,
			'requestConfig' => [
				'format' => Client::FORMAT_JSON,
			],
			'responseConfig' => [
				'format' => Client::FORMAT_JSON,
			],
		]);

		$response = $client->createRequest()
			->addHeaders(['Authorization: Basic ' => base64_encode($this->username . ":" . $this->password)])
			->setMethod($this->method)
			->setUrl($url)
			->setData($data)
			->send();

		return $response->isOk ? $response->data : false;
	}

	/**
	 * Sets the HTTP method.
	 *
	 * @param string $method the HTTP method name.
	 * @return $this
	 */
	public function setMethod($method)
	{
		$this->method = $method;

		return $this;
	}

	/**
	 * Magic __get method, will set class properties.
	 *
	 * @param string $name
	 * @return $this|mixed
	 */
	public function __get($name)
	{
		switch (strtolower($name)) {
			case 'get':
				$this->method = 'GET';
				break;
			case 'post':
				$this->method = 'POST';
				break;
			case 'uapi':
				$this->api = 'uapi';
				break;
			case 'api2':
				$this->api = 'api2';
				break;
			default:
				$this->module = $name;
				break;
		}

		return $this;
	}

	/**
	 * Magic __call method, will translate all function calls to object to API requests.
	 *
	 * @param string $name The name of the function.
	 * @param array $arguments An array of arguments.
	 * @return mixed
	 * @throws InvalidConfigException
	 */
	public function __call($name, $arguments)
	{
		if (count($arguments) < 1 || !is_array($arguments[0])) {
			$arguments[0] = [];
		}

		return $this->makeRequest($name, $arguments[0]);
	}
}

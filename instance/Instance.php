<?php

namespace tws\instance;

use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;

/**
 * Instance is a component that creates different Yii2 apps instances.
 *
 * Example of usage:
 *
 * ```php
 * Yii::$app->instance->get('@frontend');
 * ```
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
class Instance extends Component
{
	/**
	 * @var array The currently running application.
	 */
	protected static $currentApp = [];

	/**
	 * @var \yii\base\Application[]|array A stack of application instances.
	 */
	protected $stack = [];

	/**
	 * @var bool Whether a secondary application is currently being constructed.
	 * Bootstrap components can consult this to skip request-scoped side effects
	 * (session access, user-activity writes) that only make sense in the primary
	 * application actually handling the request.
	 */
	public static $creatingSecondaryApp = false;


	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		static::$currentApp = [
			'classMap' => Yii::$classMap,
			'app' => Yii::$app,
			'aliases' => Yii::$aliases,
			'container' => Yii::$container,
		];
	}

	/**
	 * Gets the instance of an application.
	 * Returns an instance from the stack or it creates a new instance if does not exist.
	 *
	 * @param string $path Could be a valid alias or an absolute path to the application.
	 * @param string $useStack Flag that indicates if stack should be used or a new instance should be retrieved.
	 * @return \yii\base\Application|null The application instance or null if errors occured.
	 */
	public function get($path, $useStack = true)
	{
		if ($useStack) {
			if (!isset($this->stack[$path])) {
				$this->stack[$path] = $this->getAppInstance($path);
			}
			return $this->stack[$path];
		}
		return $this->getAppInstance($path);
	}

	/**
	 * Gets the instance of an application.
	 *
	 * Constructing a secondary application has two side effects on the currently
	 * running application that must be undone:
	 *  - its `log` component's Dispatcher::init() points the global
	 *    `Yii::getLogger()->dispatcher` at the secondary app, so the final log
	 *    flush of the request (file logs, the yii2-debug data export) lands in
	 *    the secondary app's targets and runtime path instead of the primary's;
	 *  - dev-only modules (`debug`, `gii`) bootstrap themselves in the secondary
	 *    app although it never handles a request; yii2-debug's UserPanel also
	 *    touches the session for logged-in users ("Session is already started").
	 * The logger dispatcher is therefore restored after construction and the
	 * `debug`/`gii` modules are stripped from the secondary app's config.
	 *
	 * @param string $path Could be a valid alias or an absolute path to the application.
	 * @return \yii\base\Application|null The application instance or null if errors occured.
	 */
	protected function getAppInstance($path)
	{
		$logger = Yii::getLogger();
		$dispatcher = $logger->dispatcher;
		static::$creatingSecondaryApp = true;
		try {
			return $this->createAppInstance($path);
		} finally {
			static::$creatingSecondaryApp = false;
			$logger->dispatcher = $dispatcher;
		}
	}

	/**
	 * Builds the config and constructs the application instance.
	 *
	 * @param string $path Could be a valid alias or an absolute path to the application.
	 * @return \yii\base\Application|null The application instance or null if errors occured.
	 */
	protected function createAppInstance($path)
	{
		try {
			$path = Yii::getAlias($path);
			$rootPath = dirname($path);
			$appAlias = '@' . basename($path);
			$config = ArrayHelper::merge(
				static::requireFile("{$rootPath}/common/config/main.php"),
				static::requireFile("{$rootPath}/common/config/main-local.php"),
				static::requireFile("{$path}/config/main.php"),
				static::requireFile("{$path}/config/main-local.php")
			);
			// Add the child app config, if is the case
			$childAppBasePath = str_replace(basename(Yii::$app->basePath), ltrim($appAlias, '@'), Yii::$app->basePath);
			if (strpos($childAppBasePath, $rootPath) !== false) {
				$config = ArrayHelper::merge(
					$config,
					static::requireFile(dirname($childAppBasePath) . "/common/config/main.php"),
					static::requireFile("{$childAppBasePath}/config/main.php")
				);
			}
			$config['language'] = Yii::$app->language;
			$config['aliases'] = [
				"@app" => $path,
				"@runtime" => "{$path}/runtime",
				"@vendor" => "{$rootPath}/vendor",
				"@common" => "{$rootPath}/common",
				$appAlias => $path,
			];

			// Dev-only modules serve the app handling the request, not secondary instances
			unset($config['modules']['debug'], $config['modules']['gii']);
			if (isset($config['bootstrap']) && is_array($config['bootstrap'])) {
				$config['bootstrap'] = array_values(array_filter($config['bootstrap'], function ($component) {
					return !in_array($component, ['debug', 'gii'], true);
				}));
			}

			if ($appAlias === '@console') {
				$newApp = new \yii\console\Application($config);
			} else {
				// Set missing environment variables (required if called from a parent console app)
				if (!isset($_SERVER['REQUEST_URI'])) {
					$_SERVER['REQUEST_URI'] = '';
				}
				// Set application custom variables (required if called from a parent console app)
				if (isset($config['components']['assetManager'])) {
					$config['components']['assetManager']['basePath'] = "{$path}/web/assets";
				}
				$newApp = new \yii\web\Application($config);
			}

			static::preserveCurrentApp();

			return $newApp;
		} catch (\Exception $e) {
			static::preserveCurrentApp();
			return null;
		}
	}

	/**
	 * Requires a file if exists.
	 *
	 * @param string $filePath
	 * @param array|mixed $defaultValue
	 * @return array|mixed
	 */
	protected static function requireFile($filePath, $defaultValue = [])
	{
		if (is_file($filePath)) {
			return require $filePath;
		}
		return $defaultValue;
	}

	/**
	 * Preserves the currently running application.
	 */
	protected static function preserveCurrentApp()
	{
		Yii::$classMap = static::$currentApp['classMap'];
		Yii::$app = static::$currentApp['app'];
		Yii::$aliases = static::$currentApp['aliases'];
		Yii::$container = static::$currentApp['container'];
	}
}

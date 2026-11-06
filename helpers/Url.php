<?php

namespace tws\helpers;

use Yii;

/**
 * @inheritdoc
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
class Url extends \yii\helpers\Url
{
	/**
	 * @inheritdoc
	 * Additionally, this method return the url to a specific file if exists.
	 * Also, this method return the url from a given app.
	 *
	 * @param null|string|\yii\base\Application $app
	 */
	public static function to($url = '', $scheme = false, $app = null)
	{
		if (is_array($url)) {
			return static::toRoute($url, $scheme, $app);
		}

		$alias = self::extractAliasFromUrl($url);

		// If app argument is not set, then use the app alias from url if exists
		if (empty($app) && !empty($alias) && !in_array($alias, ['@web', '@uploads'])) {
			$app = $alias;
		}

		// Handle file url
		if (!empty($alias) && !in_array($alias, ['@web'])) {
			$filePath = Yii::getAlias($url);
			// If there is an app, replace the alias with the app's base path
			if ($app = static::getAppInstance($app)) {
				$replace = $alias === '@uploads' ? (dirname($app->basePath) . '/uploads') : $app->basePath;
				$filePath = str_replace(Yii::getAlias($alias), $replace, $filePath);
			}
			// Check if the url references a regular file
			$fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
			if (isset($fileExtension)) {
				if (!is_file($filePath)) {
					return null;
				}
				// Replace the available aliases in the URL
				$baseUrl = static::base($scheme, $app);
				$url = str_replace('@uploads', static::toUploads($scheme, $app), $url);
				$url = str_replace('@frontend/web', $baseUrl, $url);
				$url = str_replace('@backend/web', $baseUrl, $url);
				$url = str_replace('@api/web', $baseUrl, $url);
			}
		}

		$url = Yii::getAlias($url);
		if ($url === '') {
			$url = Yii::$app->getRequest()->getUrl();
		}

		if ($scheme === false) {
			return $url;
		}

		if (static::isRelative($url)) {
			// turn relative URL into absolute
			$url = static::getUrlManager($app)->getHostInfo() . '/' . ltrim($url, '/');
		}

		return static::ensureScheme($url, $scheme);
	}

	/**
	 * @inheritdoc
	 * Additionally, this method return the url from a given app.
	 *
	 * @param null|string|\yii\base\Application $app
	 */
	public static function toRoute($route, $scheme = false, $app = null)
	{
		$route = (array) $route;
		$route[0] = static::normalizeRoute($route[0]);

		if ($scheme !== false) {
			return static::getUrlManager($app)->createAbsoluteUrl($route, is_string($scheme) ? $scheme : null);
		}

		return static::getUrlManager($app)->createUrl($route);
	}

	/**
	 * @inheritDoc
	 * Additionally, this method return the base url from a given app.
	 *
	 * @param null|string|\yii\base\Application $app
	 */
	public static function base($scheme = false, $app = null)
	{
		$url = static::getUrlManager($app)->getBaseUrl();
		if ($scheme !== false) {
			$url = static::getUrlManager($app)->getHostInfo() . $url;
			$url = static::ensureScheme($url, $scheme);
		}

		return $url;
	}

	/**
	 * Returns the uploads directory URL.
	 *
	 * @param bool $scheme
	 * @param null|string|\yii\base\Application $app
	 * @return string
	 */
	public static function toUploads($scheme = false, $app = null)
	{
		if (!empty($app)) {
			$app = static::getAppInstance($app);
		}
		$appUrlSegment = '';

		if ($baseUrl = static::getUrlManager($app)->baseUrl) {
			$baseUrlSegments = array_filter(explode('/', $baseUrl));
			$baseUrlSegments = array_intersect(($app ?: Yii::$app)->params['upload.ignoredBasePaths'], $baseUrlSegments);
			$appUrlSegment = reset($baseUrlSegments);
		}

		return rtrim(str_replace($appUrlSegment, '', static::base($scheme, $app)), '/') . '/uploads';
	}

	/**
	 * Extracts the alias name from a given URL.
	 *
	 * @param string $url
	 * @return string|null
	 */
	protected static function extractAliasFromUrl($url)
	{
		if (!is_string($url)) {
			return null;
		}
		$urlParts = explode('/', $url);

		if (substr($urlParts[0], 0, 1) !== '@') {
			return null;
		}

		return $urlParts[0] ?: null;
	}

	/**
	 * Gets the instance of an application.
	 *
	 * @param string|\yii\base\Application $app
	 * @return \yii\base\Application
	 */
	protected static function getAppInstance($app)
	{
		if ($app instanceof \yii\base\Application) {
			return $app;
		}

		// No app requested (e.g. "@uploads/..." urls): nothing to resolve
		if (empty($app)) {
			return null;
		}

		// Return the current app instance if the alias matches the current app basePath
		if (\yii\helpers\FileHelper::normalizePath(Yii::getAlias($app)) == Yii::$app->basePath) {
			return Yii::$app;
		}

		return Yii::$app->instance->get($app);
	}

	/**
	 * @inheritdoc
	 * Additionally, this method returns the urlManager of a given app.
	 *
	 * @param string|\yii\base\Application $app
	 */
	protected static function getUrlManager($app = null)
	{
		$urlManager = static::$urlManager ?: Yii::$app->getUrlManager();

		if (!empty($app) && ($app = static::getAppInstance($app))) {
			if ($app->has('urlManager') && $app->has('request')) {
				$urlManager = $app->getUrlManager();
				if (!($app instanceof \yii\console\Application)) {
					$urlManager->setHostInfo($app->getRequest()->getHostInfo());
					$urlManager->setBaseUrl($app->getRequest()->getBaseUrl());
				}
			}
		}

		return $urlManager;
	}
}

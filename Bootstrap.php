<?php

namespace tws;

use Yii;
use yii\base\BootstrapInterface;
use yii\web\Application;

/**
 * This class participates in the application bootstrap process.
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
class Bootstrap implements BootstrapInterface
{
	/**
	 * @inheritdoc
	 */
	public function bootstrap($app)
	{
		Yii::setAlias('@tws', __DIR__);

		$this->registerTranslations();
	}

	/**
	 * Registers i18n translations.
	 */
	public function registerTranslations()
	{
		Yii::$app->i18n->translations['tws'] = [
			'class' => 'yii\i18n\PhpMessageSource',
			'sourceLanguage' => 'en-US',
			'basePath' => '@tws/messages',
		];
	}
}

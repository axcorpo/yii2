<?php

namespace tws\settings;

use yii\base\Component;

/**
 * Settings is a component that handles application settings.
 *
 * Examples of usage:
 *
 * ```php
 * Yii::$app->settings->get('ITEM_NAME');
 * ```
 *
 * -- or --
 *
 * ```php
 * Yii::$app->settings->getCategory('CATEGORY_NAME');
 * ```
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
class Settings extends Component
{
	/**
	 * @var array The settings data.
	 */
	public $data = [];

	/**
	 * Gets all settings.
	 *
	 * @return array
	 */
	public function getAll()
	{
		return $this->data;
	}

	/**
	 * Gets a category from settings data.
	 *
	 * @param string $category
	 * @return array|null
	 */
	public function getCategory($category)
	{
		return $this->data[$category] ?? [];
	}

	/**
	 * Gets an item from any category of settings data.
	 *
	 * @param string $item
	 * @return string|array|null
	 */
	public function getItem($item)
	{
		foreach ($this->data as $key => $value) {
			if (array_key_exists($item, (array) $value)) {
				return $value[$item];
			}
		}
		return null;
	}

	/**
	 * Gets an item from a category of settings data.
	 *
	 * @param string $item
	 * @param string|null $category
	 * @return string|array|null
	 */
	public function getItemFromCategory($item, $category)
	{
		return $this->data[$category][$item] ?? null;
	}

	/**
	 * Gets an item from settings data.
	 *
	 * @param string $item
	 * @param string|null $category
	 * @return string|array|null
	 */
	public function get($item, $category = null)
	{
		$result = null;
		if (empty($category) && array_key_exists($item, $this->data)) {
			$result = $this->getCategory($item);
		} elseif (empty($category)) {
			$result = $this->getItem($item);
		} else {
			$result = $this->getItemFromCategory($item, $category);
		}
		return $result;
	}
}

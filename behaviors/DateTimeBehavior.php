<?php

namespace tws\behaviors;

use yii\base\Behavior;
use yii\db\BaseActiveRecord;

/**
 * DateTimeBehavior automatically formats date/datetime attributes to MySQL DATETIME format.
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
class DateTimeBehavior extends Behavior
{
	/**
	 * @var string The datetime format.
	 */
	public $format = 'Y-m-d H:i:s';

	/**
	 * @var null|mixed The value used when the date format is invalid.
	 */
	public $invalidValue = null;

	/**
	 * @var array The attributes that reference a datetime string.
	 */
	public $attributes = [];

	/**
	 * @inheritdoc
	 */
	public function events()
	{
		return [
			BaseActiveRecord::EVENT_BEFORE_INSERT => 'evaluateAttributes',
			BaseActiveRecord::EVENT_BEFORE_UPDATE => 'evaluateAttributes',
		];
	}

	/**
	 * Evaluates the attribute value and assigns it to the current attributes.
	 *
	 * @param \yii\base\Event $event
	 * @throws \Exception if datetime format is invalid.
	 */
	public function evaluateAttributes($event)
	{
		foreach ($this->attributes as $attribute) {
			if (!isset($this->owner->$attribute)) {
				continue;
			}
			if (!is_string($attribute) || empty($this->owner->$attribute)) {
				$this->owner->$attribute = $this->invalidValue;
				continue;
			}
			$this->owner->$attribute = (new \DateTime($this->owner->$attribute))->format($this->format);
		}
	}
}

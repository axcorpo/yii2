<?php

namespace tws\behaviors;

use yii\base\Behavior;
use yii\base\ModelEvent;
use yii\db\BaseActiveRecord;

/**
 * DefaultBehavior automatically marks a record as default.
 *
 * @property BaseActiveRecord $owner owner ActiveRecord instance.
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
class DefaultBehavior extends Behavior
{
	/**
	 * @var string The attribute name model.
	 */
	public $attribute = 'default';

	/**
	 * @var mixed The falsy value of the attribute.
	 */
	public $falseValue = 0;

	/**
	 * @var mixed The truthy value of the attribute.
	 */
	public $trueValue = 1;

	/**
	 * @var array The list of owner attribute names, which values split records into the groups,
	 * which should have their own default value.
	 */
	public $groupAttributes = [];

	/**
	 * @var bool Ensures that the attribute has the trueValue.
	 */
	public $ensureDefaultValue = false;

	/**
	 * @inheritdoc
	 */
	public function events()
	{
		$events = [
			BaseActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
			BaseActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
		];

		if ($this->ensureDefaultValue === true) {
			$events[BaseActiveRecord::EVENT_AFTER_INSERT] = 'afterSave';
			$events[BaseActiveRecord::EVENT_AFTER_UPDATE] = 'afterSave';
		}

		return $events;
	}

	/**
	 * Creates array of group attributes with their values.
	 *
	 * @return array The attribute conditions.
	 */
	protected function createGroupConditionAttributes()
	{
		$conditions = [];

		if (!empty($this->groupAttributes)) {
			foreach ($this->groupAttributes as $attribute) {
				$conditions[$attribute] = $this->owner->$attribute;
			}
		}

		return $conditions;
	}

	/**
	 * Sets the false value to all records and true value for the current record.
	 *
	 * @param ModelEvent $event
	 * @throws \yii\base\NotSupportedException
	 */
	public function beforeSave($event)
	{
		$attribute = $this->attribute;

		if ($this->owner->$attribute == $this->trueValue) {
			$this->owner->updateAll([$attribute => $this->falseValue], array_merge(
				[$attribute => $this->trueValue],
				$this->createGroupConditionAttributes()
			));
		}
	}

	/**
	 * Makes the current record default if other does not exist.
	 *
	 * @param ModelEvent $event
	 */
	public function afterSave($event)
	{
		$attribute = $this->attribute;
		$defaultValue = $this->owner->find()->where(array_merge(
			[$attribute => $this->trueValue],
			$this->createGroupConditionAttributes()
		));

		if (!$defaultValue->exists()) {
			$this->owner->updateAttributes([
				$attribute => $this->trueValue,
			]);
		}
	}
}

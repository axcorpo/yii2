<?php

namespace tws\caching;

use yii\base\Event;

/**
 * CacheEvent represents the information available when the cache is flushed.
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
class CacheEvent extends Event
{
	/**
	 * @var string The cache key to be invalidated.
	 */
	public $key;
}

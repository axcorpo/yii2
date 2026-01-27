<?php

namespace tws\helpers;

/**
 * File system helper.
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
class FileHelper extends \yii\helpers\FileHelper
{
	/**
	 * Creates an operating system independent symbolic link.
	 *
	 * @param string|array $target a single link target or an array of multiple target => link pairs.
	 * @param string|null $link the link name, it will be ignored if the $target is array.
	 * @return bool the success or the failure of the symlink creation.
	 */
	public static function symlink($target, $link = null)
	{
		if (is_array($target)) {
			foreach ($target as $key => $val) {
				if (DIRECTORY_SEPARATOR === '\\') {
					exec("mklink /D " . static::normalizePath("{$val} {$key}"));
				} else {
					@symlink($key, $val);
				}
			}
			return true;
		}

		if (DIRECTORY_SEPARATOR === '\\') {
			exec("mklink /D " . static::normalizePath("{$link} {$target}"));
		} else {
			@symlink($target, $link);
		}
		return true;
	}
}

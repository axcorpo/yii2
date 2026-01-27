<?php

namespace tws\helpers;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * DbHelper provides a set of static methods for database operations.
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
class DbHelper
{
	/**
	 * Gets an attribute from the DSN string.
	 *
	 * @param string $name representing the attribute name.
	 * @param \yii\db\Connection|null $db the database instance.
	 * @return string|null the attribute name if exists or null if not.
	 */
	public static function getDsnAttribute($name, $db = null)
	{
		if ($db === null) {
			$db = Yii::$app->getDb();
		}
		if (preg_match('/' . $name . '=([^;]*)/', $db->dsn, $match)) {
			return $match[1];
		}

		return null;
	}

	/**
	 * Dumps a given database to a SQL file.
	 *
	 * @link https://dev.mysql.com/doc/refman/8.0/en/mysqldump.html#idm140221889713520
	 *
	 * @param \yii\db\Connection $db the database instance.
	 * @param string $path the file path where the SQL file is saved.
	 * @param array $options includes items and excluded items to be archived.
	 * @return string|null the file path if successful or null if fails.
	 */
	public static function dump($db, $path, $options = [])
	{
		try {
			$dbHost = static::getDsnAttribute('host', $db);
			$dbName = static::getDsnAttribute('name', $db);
			$command = [
				"mysqldump",
				"--user={$db->username}",
				"--password={$db->password}",
				"--host={$dbHost}",
			];

			// Add command arguments
			foreach ((array) $options as $key => $value) {
				// Skip the arguments that will be treated outside of this loop
				if (in_array($key, ['--tables'])) {
					continue;
				}

				$argKey = is_string($key) ? $key : '';
				$argVal = empty($argKey) ? "{$value}" : ("=" . implode(" ", (array) $value));

				if ($key === '--ignore-table') {
					$argVal = ("={$dbName}." . implode(" {$key}={$dbName}.", (array) $value));
				}
				$command[] = "{$argKey}{$argVal}";
			}

			$command[] = $dbName;
			if (isset($options['--tables'])) {
				$command[] = ("--tables " . implode(" ", (array) $options['--tables']));
			}
			$command[] = "--result-file={$path}";
			$command = implode(" ", $command);

			if (function_exists('exec')) {
				exec($command);
			} else {
				pclose(popen($command, 'r'));
			}

			if (!is_file($path)) {
				throw new \Exception('Cannot dump the database.');
			}

			return $path;
		} catch (\Exception $e) {
			return null;
		}
	}
}

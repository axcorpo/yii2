<?php

namespace tws\helpers;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use yii\helpers\FileHelper;
use ZipArchive;

/**
 * ArchiveHelper provides a set of static methods for archive operations.
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
class ArchiveHelper
{
	/**
	 * Creates a zip archive for a given files and directories.
	 *
	 * @param array|string $items
	 * @param string $path
	 * @param array $options Specific ZipArchive configurations.
	 * @return string|null The file path if successful or null if fails.
	 */
	public static function pack($items, $path, $options = [])
	{
		try {
			if (!is_array($items)) {
				$items = (array) $items;
			}
			$options = array_merge([
				'excludedItems' => [],
				'flags' => ZipArchive::CREATE|ZipArchive::OVERWRITE,
			], $options);

			// Ensure that the path directory tree exists
			FileHelper::createDirectory(pathinfo($path, PATHINFO_DIRNAME));

			$zip = new ZipArchive();

			if ($zip->open($path, $options['flags']) !== true) {
				throw new \Exception('Cannot create the archive file.');
			}

			foreach ($items as $i => $item) {
				if (is_file($item) && !in_array($item, $options['excludedItems'])) {
					$zip->addFile($item, pathinfo($item, PATHINFO_BASENAME));
				} elseif (is_dir($item)) {
					/** @var SplFileInfo[] $files */
					$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($item));
					foreach ($files as $file) {
						// Skip directories (they would be added automatically)
						if (!$file->isDir() && !in_array($file, $options['excludedItems'])) {
							$zip->addFile($file->getRealPath(), substr($file->getRealPath(), strlen(dirname($item)) + 1));
						}
					}
				}
			}

			$zip->close();

			return $path;
		} catch (\Exception $e) {
			return null;
		}
	}

	/**
	 * Unpacks an existing archive to a specific path.
	 *
	 * @param string $archive
	 * @param string $path
	 * @return bool
	 */
	public static function unpack($archive, $path)
	{
		try {
			$zip = new ZipArchive;
			if ($zip->open($archive) !== true) {
				throw new \Exception('Cannot open the archive file.');
			}
			if (!$zip->extractTo($path)) {
				throw new \Exception('Cannot extract the archive files.');
			}
			$zip->close();

			return true;
		} catch (\Exception $e) {
			return false;
		}
	}
}

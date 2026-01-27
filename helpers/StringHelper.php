<?php

namespace tws\helpers;

/**
 * @inheritdoc
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
class StringHelper extends \yii\helpers\StringHelper
{
	/**
	 * Generates a random alphanumeric string based of an integer length.
	 *
	 * @param int $length The number of the characters.
	 * @param string|array|null $source The source from where characters should be generated.
	 * @return string The generated random alphanumeric string.
	 */
	public static function generateRandomAlphanumerics($length, $source = null)
	{
		if (!$source) {
			$source = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		} elseif (is_array($source)) {
			$source = implode('', $source);
		}
		return substr(str_shuffle($source), 0, $length);
	}

	/**
	 * Generates a random string of letters based of an integer length.
	 *
	 * @param int $length The number of the letters.
	 * @param string|array|null $source The source from where letters should be generated.
	 * @return string The generated random letters.
	 */
	public static function generateRandomLetters($length, $source = null)
	{
		if (!$source) {
			$source = implode('', range('a','z'));
		} elseif (is_array($source)) {
			$source = implode('', $source);
		}
		return substr(str_shuffle($source), 0, $length);
	}

	/**
	 * Extract initials from a given string.
	 *
	 * @param string $source The source from where letters should be generated.
	 * @param int $length The exact number of the initials.
	 * @return string|null The extracted initials.
	 */
	public static function extractInitials($source, $length = null)
	{
		if (!is_string($source) || strlen($source) == 0) {
			return null;
		}

		$initials = [];
		if (!($sourceParts = preg_split('/\s+/', $source))) {
			$sourceParts = [$source];
		}
		if ($length > 0) {
			// Split by letters if there is only one word
			if (count($sourceParts) === 1) {
				preg_match_all('/[a-zA-Z]/', $source, $matches);
				$sourceParts = $matches[0];
			}
		}
		// Extract the first letter of each part
		foreach ($sourceParts as $sourcePart) {
			$initials[] = substr($sourcePart, 0, 1);
		}
		if ($length > 0) {
			$initials = array_slice($initials, 0, $length);
		}

		return implode('', $initials);
	}
}

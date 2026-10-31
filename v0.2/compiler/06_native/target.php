<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Reads native layout facts and supplies the target integer range used to validate executable exit values.
 */
final class Native_Target
{
	/** Adapt the existing C++ layout probe's facts to the semantic entry-exit boundary. */
	public static function load(string $path): entry_target
	{
		$text = @file_get_contents($path);
		if ($text === false) {
			throw new \RuntimeException('Cannot read native target facts: ' . $path);
		}
		try {
			$facts = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
		}
		catch (\JsonException $error) {
			throw new \RuntimeException('Invalid native target facts: ' . $error->getMessage());
		}
		$integers = is_array($facts) ? ($facts['native_integers'] ?? null) : null;
		$integer = is_array($integers) ? ($integers['int'] ?? null) : null;
		if (!is_array($integer) || (($facts['schema'] ?? null) !== 1)
			|| (($integer['signed'] ?? null) !== true) || !is_int($integer['digits'] ?? null)) {
			throw new \RuntimeException('Target facts require a signed native int');
		}
		if (($integer['digits'] < 15) || ($integer['digits'] > 63)) {
			throw new \RuntimeException('Unsupported native int width');
		}
		return new entry_target($integer['digits']);
	}
}

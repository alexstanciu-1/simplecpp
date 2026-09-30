<?php

/* Role: prepare exact integer literals under the Simple C++ contract. */
namespace scpp\compiler;

final class Integer_Literals
{
	/** Preserve exact decimal magnitude; never round through host PHP int or float. */
	public static function decimal(string $text): string
	{
		if (!Source_Text::digits($text)) {
			throw new \RuntimeException('S2S requires a decimal integer literal');
		}
		$length = string_byte_len($text);
		if (($length > 1) && (string_byte_at($text, 0) === 48)) {
			throw new \RuntimeException('S2S leading-zero numeric spelling is not implemented yet');
		}
		$maximum = '9223372036854775807';
		if ($length > string_byte_len($maximum)) {
			throw new \RuntimeException('S2S integer literal exceeds signed 64-bit representation');
		}
		if ($length === string_byte_len($maximum))
		{
			for ($index = 0; $index < $length; $index++)
			{
				$byte = string_byte_at($text, $index);
				$limit = string_byte_at($maximum, $index);
				if ($byte > $limit) {
					throw new \RuntimeException('S2S integer literal exceeds signed 64-bit representation');
				}
				if ($byte < $limit) {
					break;
				}
			}
		}
		return $text;
	}
}

/** Decode the supported quoted source form without host-PHP string evaluation. */
final class String_Literals
{
	/** Single quotes decode only escaped quote and backslash; all other pairs stay literal. */
	public static function single_quoted(string $text): string
	{
		$end = string_byte_len($text) - 1;
		if (($end < 1) || (string_byte_at($text, 0) !== 39) || (string_byte_at($text, $end) !== 39)) {
			throw new \RuntimeException('S2S requires a complete single-quoted string literal');
		}

		$value = '';
		for ($index = 1; $index < $end; $index++)
		{
			$byte = string_byte_at($text, $index);
			if ($byte !== 92) {
				$value .= string_byte_slice($text, $index, 1);
				continue;
			}

			$index++;
			if ($index >= $end) {
				throw new \RuntimeException('S2S single-quoted string ends with an incomplete escape');
			}
			$next = string_byte_at($text, $index);
			if (($next !== 39) && ($next !== 92)) {
				$value .= '\\';
			}
			$value .= string_byte_slice($text, $index, 1);
		}
		return $value;
	}
}

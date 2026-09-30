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
	private static function hex_digit(int $byte): int
	{
		if (($byte >= 48) && ($byte < 58)) {
			return $byte - 48;
		}
		if (($byte >= 65) && ($byte < 71)) {
			return $byte - 55;
		}
		if (($byte >= 97) && ($byte < 103)) {
			return $byte - 87;
		}
		return -1;
	}

	/** Decode either supported quote spelling into its canonical byte value. */
	public static function decode(string $text): string
	{
		$quote = string_byte_at($text, 0);
		$end = string_byte_len($text) - 1;
		if ((($quote !== 39) && ($quote !== 34)) || ($end < 1) || (string_byte_at($text, $end) !== $quote)) {
			throw new \RuntimeException('S2S requires a complete quoted string literal');
		}
		if ($quote === 39) {
			return self::single_quoted($text, $end);
		}
		return self::double_quoted($text, $end);
	}

	/** Single quotes decode only escaped quote and backslash; all other pairs stay literal. */
	private static function single_quoted(string $text, int $end): string
	{
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

	/** Double quotes decode PHP byte escapes but deliberately reject interpolation. */
	private static function double_quoted(string $text, int $end): string
	{
		$value = '';
		for ($index = 1; $index < $end; $index++)
		{
			$byte = string_byte_at($text, $index);
			$following = string_byte_at($text, $index + 1);
			if (($byte === 36) &&
				((($following >= 65) && ($following < 91)) || (($following >= 97) && ($following < 123)) ||
				 ($following === 95) || ($following === 123) || ($following >= 128))) {
				throw new \RuntimeException('S2S string interpolation is not supported');
			}
			if ($byte !== 92) {
				$value .= string_byte_slice($text, $index, 1);
				continue;
			}

			$index++;
			if ($index >= $end) {
				throw new \RuntimeException('S2S double-quoted string ends with an incomplete escape');
			}
			$next = string_byte_at($text, $index);
			if (($next === 92) || ($next === 34)) {
				$value .= string_byte_slice($text, $index, 1);
				continue;
			}

			$decoded = -1;
			if ($next === 110) {
				$decoded = 10;
			}
			elseif ($next === 114) {
				$decoded = 13;
			}
			elseif ($next === 116) {
				$decoded = 9;
			}
			elseif ($next === 118) {
				$decoded = 11;
			}
			elseif ($next === 102) {
				$decoded = 12;
			}
			elseif ($next === 101) {
				$decoded = 27;
			}
			elseif ($next === 36) {
				$decoded = 36;
			}
			elseif (($next >= 48) && ($next < 56))
			{
				$decoded = $next - 48;
				for ($digits = 1; ($digits < 3) && ($index + 1 < $end); $digits++)
				{
					$digit = string_byte_at($text, $index + 1) - 48;
					if (($digit < 0) || ($digit > 7)) {
						break;
					}
					$decoded = ($decoded * 8) + $digit;
					$index++;
				}
				$decoded = $decoded % 256;
			}
			elseif ($next === 120)
			{
				for ($digits = 0; ($digits < 2) && ($index + 1 < $end); $digits++)
				{
					$digit = self::hex_digit(string_byte_at($text, $index + 1));
					if ($digit < 0) {
						break;
					}
					if ($decoded < 0) {
						$decoded = 0;
					}
					$decoded = ($decoded * 16) + $digit;
					$index++;
				}
			}
			elseif (($next === 117) && (string_byte_at($text, $index + 1) === 123)) {
				throw new \RuntimeException('S2S Unicode escape syntax is not supported; use UTF-8 literal bytes');
			}

			if ($decoded >= 0) {
				$value .= string_byte_from_int($decoded);
			}
			else {
				$value .= '\\' . string_byte_slice($text, $index, 1);
			}
		}
		return $value;
	}
}

<?php

namespace scpp\compiler;

/** ASCII token spelling queries shared by syntax recognition. */
final class Source_Text
{
	/** Recognize a nonempty ASCII identifier without allocating intermediate substrings. */
	public static function identifier(string $text): bool
	{
		$length = string_byte_len($text);
		if ($length === 0) {
			return false;
		}
		if (!self::letter(string_byte_at($text, 0))) {
			return false;
		}
		for ($index = 1; $index < $length; $index++) {
			$byte = string_byte_at($text, $index);
			if (!self::letter($byte) && !self::digit($byte)) {
				return false;
			}
		}
		return true;
	}

	/** Accept only nonempty ASCII decimal spellings; signs and separators are not tokens. */
	public static function digits(string $text): bool
	{
		$length = string_byte_len($text);
		if ($length === 0) {
			return false;
		}
		for ($index = 0; $index < $length; $index++) {
			if (!self::digit(string_byte_at($text, $index))) {
				return false;
			}
		}
		return true;
	}

	private static function letter(int $byte): bool
	{
		return ($byte === 95) || (($byte >= 65) && ($byte < 91)) || (($byte >= 97) && ($byte < 123));
	}

	private static function digit(int $byte): bool
	{
		return ($byte >= 48) && ($byte < 58);
	}
}

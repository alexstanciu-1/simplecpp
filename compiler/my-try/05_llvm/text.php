<?php

namespace scpp\compiler;

/** Byte-exact LLVM spelling operations; decimal inputs are lexer-validated digits. */
final class LLVM_Text
{
	/** Replace the last extension of a source basename, matching the host filename contract. */
	public static function output_name(string $path): string
	{
		$name = fs_basename($path);
		$length = string_byte_len($name);
		$end = $length;
		for ($index = 0; $index < $length; $index++) {
			if (string_byte_at($name, $index) === 46) {
				$end = $index;
			}
		}
		return string_byte_slice($name, 0, $end) . '.ll';
	}

	/** Join already encoded fragments without leading or trailing separators. */
	public static function join(array $parts /** vector<string> */, string $separator): string
	{
		$result = '';
		$first = true;
		foreach ($parts as $part) {
			if (!$first) {
				$result .= $separator;
			}
			$result .= $part;
			$first = false;
		}
		return $result;
	}

	/** Canonicalize lexer-validated decimal digits, retaining one zero for an all-zero token. */
	public static function decimal(string $text): string
	{
		$length = string_byte_len($text);
		$start = 0;
		while (string_byte_at($text, $start) === 48) {
			$start++;
		}
		return $start === $length ? '0' : string_byte_slice($text, $start, $length - $start);
	}

	/** Compare canonical nonnegative decimal spellings without host integer conversion. */
	public static function decimal_exceeds(string $text, string $maximum): bool
	{
		$length = string_byte_len($text);
		if ($length !== string_byte_len($maximum)) {
			return $length > string_byte_len($maximum);
		}
		for ($index = 0; $index < $length; $index++) {
			$left = string_byte_at($text, $index);
			$right = string_byte_at($maximum, $index);
			if ($left !== $right) {
				return $left > $right;
			}
		}
		return false;
	}
}

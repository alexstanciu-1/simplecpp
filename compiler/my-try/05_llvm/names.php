<?php

/*
 * Role: encode exact identities into readable LLVM names.
 * Call map: LLVM_Preparation / LLVM_Struct_Preparation -> LLVM_Names::declaration / encode.
 */
namespace scpp\compiler;

final class LLVM_Names
{
	/** Reversibly encode source bytes; reserved _G sequences cannot come from this encoder. */
	public static function encode(string $source): string
	{
		$result = '';
		$hex = '0123456789ABCDEF';
		$length = string_byte_len($source);
		for ($index = 0; $index < $length; $index++)
		{
			$byte = string_byte_at($source, $index);
			if ($byte === 95) {
				$result .= '__';
			}
			elseif (self::digit($byte) || (($byte >= 65) && ($byte < 91)) || (($byte >= 97) && ($byte < 123))) {
				$result .= string_byte_from_int($byte);
			}
			else {
				$result .= '_x' . string_byte_slice($hex, (int) ($byte / 16), 1)
				. string_byte_slice($hex, $byte % 16, 1) . '_';
			}
		}
		return $result;
	}

	/** Recover source spelling, accepting an optional generated declaration-identity suffix. */
	public static function decode(string $encoded): string
	{
		$result = '';
		$length = string_byte_len($encoded);
		for ($index = 0; $index < $length;)
		{
			$byte = string_byte_at($encoded, $index);
			if ($byte !== 95) {
				$result .= string_byte_from_int($byte);
				$index++;
				continue;
			}
			if (string_byte_at($encoded, $index + 1) === 95) {
				$result .= '_';
				$index = $index + 2;
				continue;
			}
			if (string_byte_at($encoded, $index + 1) === 120)
			{
				$high = self::hex_digit(string_byte_at($encoded, $index + 2));
				$low = self::hex_digit(string_byte_at($encoded, $index + 3));
				if (($high >= 0) && ($low >= 0) && (string_byte_at($encoded, $index + 4) === 95)) {
					$result .= string_byte_from_int($high * 16 + $low);
					$index = $index + 5;
					continue;
				}
			}
			if (self::identity_suffix($encoded, $index)) {
				break;
			}
			throw new \InvalidArgumentException('Invalid encoded source name');
		}
		return $result;
	}

	private static function digit(int $byte): bool
	{
		return ($byte >= 48) && ($byte < 58);
	}

	/** Return -1 for absent or non-uppercase-hex bytes. */
	private static function hex_digit(int $byte): int
	{
		if (self::digit($byte)) {
			return $byte - 48;
		}
		if (($byte >= 65) && ($byte < 71)) {
			return $byte - 55;
		}
		return -1;
	}

	/** A suffix is exactly _Gf followed by digits, d and another nonempty digit run. */
	private static function identity_suffix(string $source, int $start): bool
	{
		if (string_byte_slice($source, $start, 3) !== '_Gf') {
			return false;
		}
		$index = $start + 3;
		$first = $index;
		while (self::digit(string_byte_at($source, $index))) {
			$index++;
		}
		if ($index === $first) {
			return false;
		}
		if (string_byte_at($source, $index) !== 100) {
			return false;
		}
		$index++;
		$first = $index;
		while (self::digit(string_byte_at($source, $index))) {
			$index++;
		}
		return ($index > $first) && ($index === string_byte_len($source));
	}

	/** Keep unique source names readable; preserve every colliding declaration with its identity. */
	public static function declaration(collected_name $entry, int $file_index): string
	{
		$entry_scope /** scope */ = object_cast(weakref_get($entry->scope), scope::class);
		$pool /** hash<vector<collected_name>> */ = $entry->kind === collected_name_kind::function_declaration ? $entry_scope->functions : $entry_scope->variables;
		$name = self::encode($entry->name);
		if (q_count($pool[$entry->name]) > 1) {
			$name .= '_Gf' . $file_index . 'd' . $entry->local_index;
		}
		return $name;
	}
}

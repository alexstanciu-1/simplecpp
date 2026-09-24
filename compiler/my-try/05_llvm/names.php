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
		for ($index = 0; $index < strlen($source); $index++)
		{
			$byte = $source[$index];
			if ($byte === '_') {
				$result .= '__';
			}
			elseif (str_contains('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', $byte)) {
				$result .= $byte;
			}
			else {
				$result .= sprintf('_x%02X_', ord($byte));
			}
		}
		return $result;
	}

	/** Recover source spelling, accepting an optional generated declaration-identity suffix. */
	public static function decode(string $encoded): string
	{
		$result = '';
		for ($index = 0; $index < strlen($encoded);)
		{
			$byte = $encoded[$index];
			if ($byte !== '_') {
				$result .= $byte;
				$index++;
				continue;
			}
			$tail = substr($encoded, $index);
			if (str_starts_with($tail, '__')) {
				$result .= '_';
				$index += 2;
			}
			elseif (preg_match('/^_x([0-9A-F]{2})_/', $tail, $match) === 1) {
				$result .= chr(hexdec($match[1]));
				$index += 5;
			}
			elseif (preg_match('/^_Gf[0-9]+d[0-9]+$/D', $tail) === 1) {
				break;
			}
			else {
				throw new \InvalidArgumentException('Invalid encoded source name');
			}
		}
		return $result;
	}

	/** Keep unique source names readable; preserve every colliding declaration with its identity. */
	public static function declaration(collected_name $entry, int $file_index): string
	{
		$pool = $entry->kind === collected_name_kind::function_declaration ? $entry->scope->functions : $entry->scope->variables;
		$name = self::encode($entry->name);
		if (count($pool[$entry->name]) > 1) {
			$name .= '_Gf' . $file_index . 'd' . $entry->local_index;
		}
		return $name;
	}
}

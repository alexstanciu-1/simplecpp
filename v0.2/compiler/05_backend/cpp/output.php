<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Spells exact output literal bytes; the compiled runtime owns formatting and IO.
 */
final class Cpp_Output
{
	/** Fixed-width octal spelling preserves arbitrary bytes without C++ escape-boundary ambiguity. */
	public static function bytes(string $bytes): string
	{
		$literal = '"';
		for ($index = 0; $index < strlen($bytes); ++$index) {
			$literal .= '\\' . str_pad(decoct(ord($bytes[$index])), 3, '0', STR_PAD_LEFT);
		}
		return $literal . '"';
	}

}

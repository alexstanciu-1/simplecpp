<?php

declare(strict_types=1);
namespace scpp\compiler;

// Generated from compiler entry-boundary metadata. Do not edit.
/** Public ABI binding only; entry range reasoning belongs to handwritten target-aware preparation. */
final class Cpp_Entry_Binding
{
	/** Header also provides the target assertion used for proven entry returns. */
	public static function header(): string
	{
		return @header@;
	}

	/** Spell the checked call without deciding whether a check is needed. */
	public static function checked(string $value): string
	{
		return @symbol@ . '(' . $value . ')';
	}
}

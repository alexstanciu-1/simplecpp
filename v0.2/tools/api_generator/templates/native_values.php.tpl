<?php

declare(strict_types=1);
namespace scpp\compiler;

// Generated from reviewed runtime metadata and validated target facts. Do not edit.
/** Native scalar spelling for settled AST values; e.g. int64 local_0 = 8 becomes native storage = 8LL. */
trait cpp_native_values
{
	private function native_type(type_definition $type): string
	{
		return match ($type) {
			type_definition::int64 => '@representation@',
			default => throw new \LogicException('Unsupported prepared native storage type'),
		};
	}

	/** Input is already checked int64, never an unvalidated source spelling. */
	private function native_integer(int $value /** int64 */): string
	{
		// Spell the minimum without an out-of-range positive signed literal.
		if ($value === PHP_INT_MIN) {
			return '(-9223372036854775807LL - 1LL)';
		}
		return $value . 'LL';
	}
}

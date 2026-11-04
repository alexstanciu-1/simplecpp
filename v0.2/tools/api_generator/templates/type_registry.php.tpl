<?php

declare(strict_types=1);
namespace scpp\compiler;

// Generated from the always-available default-runtime module. Do not edit.
/** Direct source-name registration; no runtime metadata parsing, descriptor lookup or per-use allocation. */
final class type_registry
{
	public static function resolve(string $name): ?type_definition
	{
		return match ($name) {
@cases@
			default => null,
		};
	}

	/** Literal spelling lookup is generated; parser retains syntax and does not settle facts. */
	public static function literal(string $word): ?type_definition
	{
		return match ($word) {
@literal_cases@
			default => null,
		};
	}

	/** Dispatch the bounded literal family; traversal and declaration resolution remain outside definitions. */
	public static function prepare_literal(ast_expression $value): void
	{
		$type = $value instanceof ast_integer_expression ? self::integer()
			: ($value instanceof ast_keyword_expression ? self::literal($value->word) : null);
		if ($type === null) {
			throw new diagnostic($value->source(), $value->byte_start(), 'Unsupported scalar literal');
		}
		$type->prepare_literal($value);
	}

	/** Canonical source integer identity for literals and the existing entry contract. */
	public static function integer(): type_@name@
	{
		return type_@name@::instance();
	}
}

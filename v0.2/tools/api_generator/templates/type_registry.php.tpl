<?php

declare(strict_types=1);
namespace scpp\compiler;
require_once __DIR__ . '/vectors.php';
require_once __DIR__ . '/carriers.php';
require_once __DIR__ . '/hashes.php';
@boxed_require@
// Generated from the always-available default-runtime module. Do not edit.
/** Direct source-name registration; no runtime metadata parsing, descriptor lookup or per-use allocation. */
final class type_registry
{
	/** Type names introduced by the active providers into scpp::types. */
	public static function has_cpp_type_alias(string $name): bool
	{
		return match ($name) {
@cpp_type_aliases@
			default => false,
		};
	}

	public static function resolve(string $name): ?type_definition
	{
		return match ($name) {
@cases@
			default => null,
		};
	}

	/** Generic family registration is independent of whether a specialization was published. */
	public static function generic_arity(string $name): ?int /** uint8|null */
	{
		return match ($name) {
@generic_arity@
			default => null,
		};
	}

	/** Select a published canonical specialization; no descriptor lookup or per-use allocation. */
	public static function specialize(string $family, type_definition $element, ?type_definition $second = null): ?type_definition
	{
@specializations@		return null;
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
		$type = self::literal_type($value);
		if ($type === null) {
			throw new diagnostic($value->source(), $value->byte_start(), 'Unsupported scalar literal');
		}
		$type->prepare_literal($value);
	}

	/** Identify literal families without preparing values; publication uses this for inferred global contracts. */
	public static function literal_type(ast_expression $value): ?type_definition
	{
		return match (true) {
@family_cases@
			default => null,
		};
	}

@family_accessors@
}

/** Published fixed-arity runtime call signatures, shared across source units. */
final class runtime_functions
{
	public static function free(string $key, flavor $flavor): ?runtime_function
	{
		return self::resolve($key) ?? self::resolve($flavor->name . ':' . $key);
	}

	public static function resolve(string $key): ?runtime_function
	{
		static $bindings = [];
		return match ($key) {
@runtime_cases@
			default => null,
		};
	}
}

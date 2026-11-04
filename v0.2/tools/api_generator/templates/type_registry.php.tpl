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
			@cases@ => type_@name@::instance(),
			default => null,
		};
	}

	/** Canonical source integer identity for literals and the existing entry contract. */
	public static function integer(): type_@name@
	{
		return type_@name@::instance();
	}
}

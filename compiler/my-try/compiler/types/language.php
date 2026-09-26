<?php

/* Language definitions follow Simple C++ contracts, independently of backend policies. */
namespace scpp\compiler;

final class Language_Types
{
	/** Install canonical language types supported by scalar generation. */
	public static function install(scope $language_scope): void
	{
		$integer = new type_definition();
		$integer->name = 'int';
		$integer->kind = type_kind::integer;
		$integer->origin = type_origin::language;
		$integer->value_bits = 64;
		$integer->signed = true;
		$language_scope->register_type($integer);

		$boolean = new type_definition();
		$boolean->name = 'bool';
		$boolean->kind = type_kind::boolean;
		$boolean->origin = type_origin::language;
		$boolean->value_bits = 1;
		$language_scope->register_type($boolean);
	}

	/** Literal defaults use the language definition, independently of source shadowing. */
	public static function integer(scope $language_scope): type_definition
	{
		$types /** vector<type_definition> */ = $language_scope->types_named('int');
		if (q_count($types) !== 1) {
			throw new \LogicException('Missing canonical integer definition');
		}
		return $types[0];
	}
	/** Boolean defaults use the same language scope as integer defaults. */
	public static function boolean(scope $language_scope): type_definition
	{
		$types /** vector<type_definition> */ = $language_scope->types_named('bool');
		if (q_count($types) !== 1) {
			throw new \LogicException('Missing canonical boolean definition');
		}
		return $types[0];
	}
}

<?php

/* Language definitions follow Simple C++ contracts, independently of backend policies. */
namespace scpp\compiler;

final class Language_Types
{
	/** Publish the already registered concrete language definitions into lexical lookup. */
	public static function install(scope $language_scope): void
	{
		foreach (['void', 'bool', 'int8', 'uint8', 'int16', 'uint16', 'int32', 'uint32',
			'int64', 'uint64', 'byte', 'int', 'float', 'string'] as $name) {
			$language_scope->register_type(Model::$type_catalog->definition($name));
		}
	}

	/** Literal defaults use the language definition, independently of source shadowing. */
	public static function integer(scope $language_scope): canonical_type_use
	{
		return self::canonical($language_scope, 'int');
	}

	/** Boolean defaults use the same language scope as integer defaults. */
	public static function boolean(scope $language_scope): canonical_type_use
	{
		return self::canonical($language_scope, 'bool');
	}

	/** Floating-point defaults use the same language scope as integer defaults. */
	public static function floating(scope $language_scope): canonical_type_use
	{
		return self::canonical($language_scope, 'float');
	}

	/** String literals and declarations share one binary-safe language identity. */
	public static function string_type(scope $language_scope): canonical_type_use
	{
		return self::canonical($language_scope, 'string');
	}

	/** Resolve the published definition and attach only its compact canonical identity. */
	private static function canonical(scope $language_scope, string $name): canonical_type_use
	{
		$types /** vector<type_definition_i> */ = $language_scope->types_named($name);
		if (q_count($types) !== 1) {
			throw new \LogicException('Missing canonical language type definition');
		}
		$definition = object_cast($types[0], concrete_type_definition_i::class);
		$type = Model::$type_catalog->registry()->canonical($definition);
		return Model::$type_catalog->registry()->use($type->type_id());
	}
}

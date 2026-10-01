<?php

/* Role: map canonical type identities to independent C++ representation bindings. */
namespace scpp\compiler;

final class CPP_Types
{
	/** Resolve the semantic identity first; backend metadata never establishes type equality. */
	public static function representation(canonical_type_use $type_use): cpp_type
	{
		$type = Type_Preparation::canonical($type_use);
		$definition = $type->definition();
		$result = new cpp_type();
		if ($definition->origin() === type_definition_origin::source) {
			$entry = Model::$type_catalog->source_declarations()->declaration($definition->definition_id());
			$result->spelling = CPP_Generator::source_name('record', $entry->name);
			$result->header = '';
			$result->literal = cpp_literal_kind::none;
			return $result;
		}

		$binding = Model::$cpp_type_bindings->definition($definition->definition_id());
		$result->spelling = $binding->name();
		$result->header = $binding->header();
		$result->literal = self::literal_kind($type, $definition);
		return $result;
	}

	/** Literal families are semantic; exact target spelling remains in the binding catalog. */
	private static function literal_kind(canonical_type_i $type, type_definition_i $definition): cpp_literal_kind
	{
		if ($type->family() === type_family::integer) {
			return cpp_literal_kind::signed_integer;
		}
		if ($type->family() === type_family::floating) {
			return cpp_literal_kind::floating;
		}
		if ($type->family() === type_family::boolean) {
			return cpp_literal_kind::boolean;
		}
		if (($type->family() === type_family::nominal) && ($definition->name() === 'string')) {
			return cpp_literal_kind::string_value;
		}
		return cpp_literal_kind::none;
	}
}

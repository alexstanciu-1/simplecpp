<?php

/* Role: map canonical types to C++ representations. */
namespace scpp\compiler;

final class CPP_Types
{
	/** Mapping accepts only the implemented canonical representation, never an unknown fallback. */
	public static function representation(type_definition $definition): cpp_type
	{
		if ($definition->origin !== type_origin::language) {
			throw new \RuntimeException('No C++ representation for this type');
		}
		$result = new cpp_type();
		if (($definition->kind === type_kind::integer) && ((int) $definition->value_bits === 64) && $definition->signed) {
			$result->spelling = 'scpp::int_t<>';
			$result->header = 'scpp/int_t.hpp';
			$result->literal = cpp_literal_kind::signed_integer;
		}
		elseif ($definition->kind === type_kind::boolean) {
			$result->spelling = 'scpp::bool_t';
			$result->header = 'scpp/bool_t.hpp';
			$result->literal = cpp_literal_kind::boolean;
		}
		else {
			throw new \RuntimeException('No C++ representation for this type');
		}
		return $result;
	}
}

<?php

/* Role: map canonical types to C++ representations. */
namespace scpp\compiler;

final class CPP_Types
{
	/** Mapping accepts only the implemented canonical representation, never an unknown fallback. */
	public static function representation(type_definition $definition): cpp_type
	{
		$result = new cpp_type();
		if ($definition->kind === type_kind::record) {
			$entry = object_cast($definition->declaration, collected_name::class);
			$result->spelling = 'record_' . $entry->token_index;
			$result->header = '';
			$result->literal = cpp_literal_kind::none;
		}
		elseif ($definition->kind === type_kind::void_type) {
			$result->spelling = 'void';
			$result->header = '';
			$result->literal = cpp_literal_kind::none;
		}
		elseif ($definition->kind === type_kind::integer)
		{
			if (($definition->name === 'int') && (((int) $definition->value_bits !== 64) || (!$definition->signed))) {
				throw new \RuntimeException('Invalid canonical default integer representation');
			}
			$result->spelling = 'scpp::int_t<>';
			if ($definition->name !== 'int') {
				$prefix = $definition->signed ? 'int' : 'uint';
				$result->spelling = 'scpp::int_t<std::' . $prefix . $definition->value_bits . '_t>';
			}
			$result->header = 'scpp/int_t.hpp';
			$result->literal = cpp_literal_kind::signed_integer;
		}
		elseif (($definition->kind === type_kind::floating) && ((int) $definition->value_bits === 64) && $definition->signed) {
			$result->spelling = 'scpp::float_t';
			$result->header = 'scpp/float_t.hpp';
			$result->literal = cpp_literal_kind::floating;
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

<?php
namespace scpp\compiler;

/** Native selection spelling consumes prepared domains without erasing enum identity. */
final class CPP_Selection
{
	public static function subject(string $expression, canonical_type_use $type): string
	{
		if (Type_Queries::is_enum($type)) {
			return '(' . $expression . ')';
		}
		return '(' . $expression . ').native_value()';
	}

	public static function constant(prepared_selection_constant $value, cpp_generation_context $context): string
	{
		if ($value instanceof prepared_integer_selection) {
			return CPP_Integer_Literals::constant(object_cast($value, prepared_integer_selection::class)->decimal);
		}
		if ($value instanceof prepared_enum_selection) {
			$enumeration = object_cast($value, prepared_enum_selection::class);
			return CPP_Declarations::type($enumeration->type, $context) . '::'
				. CPP_Generator::source_name('case', $enumeration->selected_case->name);
		}
		throw new \LogicException('Unsupported prepared selection constant');
	}
}

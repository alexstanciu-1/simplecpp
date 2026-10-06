<?php

/* One storage boundary protocol for locals, calls and returns; conversions remain separate. */
namespace scpp\compiler;

final class Value_Boundary_Preparation
{
	/** Select the operation once; emitters must not rediscover ownership from type spellings. */
	public static function decide(canonical_type_use $target, ?conversion_decision $conversion,
		preparation_context $context, prepared_expression $value,
		passing_mode $mode = passing_mode::value): value_boundary_operation
	{
		if ($mode === passing_mode::reference)
		{
			if ((!$value->addressable()) || !Type_Preparation::same_storage_type($value->type, $target)) {
				throw new \RuntimeException('S2S reference arguments require stable storage of the exact parameter type');
			}
			return value_boundary_operation::bind_reference;
		}
		if ($value->is_owned_temporary() && $value->type->matches($target)) {
			return value_boundary_operation::consume_value;
		}
		if ($conversion !== null) {
			if ($conversion->operation === conversion_operation::ownership_empty) {
				return value_boundary_operation::construct_empty;
			}
		}
		Capability_Preparation::require_copy($target, $context);
		return value_boundary_operation::copy_value;
	}
}

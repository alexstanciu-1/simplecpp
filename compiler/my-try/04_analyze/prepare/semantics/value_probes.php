<?php

/* Bounded probe admission; runtime probe helpers own presence and emptiness semantics. */
namespace scpp\compiler;

final class Value_Probe_Preparation
{
	public static function prepare(value_probe_node $syntax, preparation_context $context): prepared_value_probe
	{
		if (($syntax->operand instanceof field_access_node) || ($syntax->operand instanceof index_node)) {
			throw new \RuntimeException('S2S keyed/property-path probes are deferred');
		}
		$statement_assignment = $context->statement_assignment;
		$context->statement_assignment = false;
		try {
			$operand = Expression_Preparation::prepare($syntax->operand, $context);
			$type = $operand->type;
			$payload = Nullable_Conversions::payload($type);
			$result_role = Result_Conversions::role($type);
			if (Mixed_Conversions::is_mixed($type)) {
				if ($type->by_value()) {
					throw new \RuntimeException('S2S mixed probes do not support effective storage modifiers');
				}
			}
			elseif ($result_role !== null) {
				Result_Conversions::require_scalar_payload($type, $result_role);
			}
			elseif ($payload !== null) {
				Nullable_Conversions::require_scalar_payload($payload);
				if ($type->by_value()) { throw new \RuntimeException('S2S nullable probes do not support storage modifiers'); }
			}
			elseif (!Conversion_Preparation::is_scalar($type) && !Nullable_Conversions::is_null($type)) {
				throw new \RuntimeException('S2S value probes require a scalar, nullable scalar, Result scalar, mixed or null');
			}
			$facts = new prepared_value_probe();
			$facts->type = $context->boolean;
			$facts->operation = $syntax->probe_kind;
			return $facts;
		}
		finally { $context->statement_assignment = $statement_assignment; }
	}
}

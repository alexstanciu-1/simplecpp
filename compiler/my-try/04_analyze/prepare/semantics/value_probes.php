<?php

/* Probe admission is shared by plain values and keyed leaves. */
namespace scpp\compiler;

final class Value_Probe_Preparation
{
	public static function prepare(value_probe_node $syntax, preparation_context $context): prepared_value_probe
	{
		if ($syntax->operand instanceof field_access_node) {
			throw new \RuntimeException('S2S property-path probes are deferred');
		}
		$previous = $context->statement_assignment;
		$context->statement_assignment = false;
		try {
			$facts = new prepared_value_probe();
			$facts->type = $context->boolean;
			$facts->operation = $syntax->probe_kind;
			if ($syntax->operand instanceof index_node) {
				$facts->path = Keyed_Path_Preparation::prepare($syntax->operand, $context, false);
			}
			else {
				$operand = Expression_Preparation::prepare($syntax->operand, $context);
				self::require_type($operand->type);
			}
			return $facts;
		}
		finally { $context->statement_assignment = $previous; }
	}

	public static function require_type(canonical_type_use $type): void
	{
		if ($type->by_value()) {
			throw new \RuntimeException('S2S probes do not support effective storage modifiers');
		}
		$payload = Nullable_Conversions::payload($type);
		$result_role = Result_Conversions::role($type);
		$container = Model::$indexing_contracts->lookup(Type_Queries::canonical($type)->definition());
		if ($container !== null) {
			if ($container->supports_probes()) { return; }
		}
		if ($result_role !== null) {
			Result_Conversions::require_payload($type, $result_role);
		}
		elseif ($payload !== null) {
			Nullable_Conversions::require_scalar_payload($payload);
		}
		elseif (!Conversion_Preparation::is_scalar($type) && !Nullable_Conversions::is_null($type)) {
			throw new \RuntimeException('S2S value probes require a scalar, nullable scalar, Result scalar, mixed, registered container or null');
		}
	}
}

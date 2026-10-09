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
				$steps /** Storage<prepared_keyed_step> */ = $facts->path->steps;
				$leaf /** prepared_keyed_step */ = $steps[$steps->count() - 1];
				self::require_type($leaf->element_type, $syntax->probe_kind);
			}
			else {
				$operand = Expression_Preparation::prepare($syntax->operand, $context);
				self::require_type($operand->type, $syntax->probe_kind);
				$definition = Type_Queries::canonical($operand->type)->definition();
				if (($definition instanceof callable_type_definition) || ($definition instanceof closure_type_definition)) {
					$facts->presence = Condition_Truthiness::decide($operand->type);
				}
			}
			return $facts;
		}
		finally { $context->statement_assignment = $previous; }
	}

	public static function require_type(canonical_type_use $type, value_probe_kind $operation): void
	{
		if ($operation === value_probe_kind::empty) {
			$definition = Type_Queries::canonical($type)->definition();
			if (!$type->by_value() && (String_Conversions::is_string($type)
				|| Boxed_Conversions::is_boxed($type)
				|| ($definition === Model::$type_catalog->definition('vector'))
				|| ($definition === Model::$type_catalog->definition('hash')))) { return; }
			throw new \RuntimeException('S2S empty requires a collection or string; wrappers do not delegate emptiness');
		}
		if (Type_Queries::source_record($type) !== null) { return; }
		$definition = Type_Queries::canonical($type)->definition();
		if (($definition instanceof callable_type_definition) || ($definition instanceof closure_type_definition)) { return; }
		if ($type->by_value()) {
			throw new \RuntimeException('S2S probes do not support effective storage modifiers');
		}
		$payload = Nullable_Conversions::payload($type);
		if (Ownership_Conversions::role($type) !== null) {
			Ownership_Conversions::require_presence($type);
			return;
		}
		$result_role = Result_Conversions::role($type);
		$container = Model::$indexing_contracts->lookup(Type_Queries::canonical($type)->definition());
		if ($container !== null) {
			if ($container->supports_probes()) { return; }
		}
		if ($result_role !== null) {
			Result_Conversions::require_payload($type, $result_role);
		}
		elseif ($payload !== null) {
			Nullable_Conversions::require_value_payload($payload);
		}
		elseif (!Conversion_Preparation::is_scalar($type) && !Nullable_Conversions::is_null($type)) {
			throw new \RuntimeException('S2S value probes require a scalar, nullable scalar, Result scalar, mixed, registered container or null');
		}
	}
}

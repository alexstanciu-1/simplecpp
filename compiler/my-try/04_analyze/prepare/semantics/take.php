<?php

/* Wrapper-directed output typing and existing-place binding; no implicit declarations. */
namespace scpp\compiler;

final class Take_Preparation
{
	/** Validate both outputs regardless of the runtime-selected state. */
	public static function prepare(take_expression_node $syntax, preparation_context $context): prepared_take
	{
		$statement_assignment = $context->statement_assignment;
		$context->statement_assignment = false;
		try
		{
			$source = Expression_Preparation::prepare($syntax->source, $context);
			$payload = Nullable_Conversions::payload($source->type);
			$facts = new prepared_take();
			$facts->type = $context->boolean;
			$alternative_type /** nullable<canonical_type_use> */ = null;
			if ($payload !== null)
			{
				Nullable_Conversions::require_scalar_payload($payload);
				$facts->operation = take_operation::nullable_value;
			}
			else
			{
				$role = Result_Conversions::role($source->type);
				if ($role === null) {
					throw new \RuntimeException('S2S take requires a supported wrapper source');
				}
				$payload = Result_Conversions::require_scalar_payload($source->type, $role);
				$facts->operation = take_operation::false_result;
				if ($role->result_family() === result_kind::structured_error) {
					$facts->operation = take_operation::error_result;
					$alternative_type = Language_Types::error_type(Model::$language_scope);
				}
				elseif ($role->result_family() === result_kind::boolean_sentinels) {
					$facts->operation = take_operation::boolean_result;
					$alternative_type = $context->boolean;
				}
			}
			if ($source->type->by_value()) {
				throw new \RuntimeException('S2S take does not support source storage modifiers');
			}
			$payload_type /** canonical_type_use */ = $payload;
			$value = self::output($syntax->value_binding, $payload_type, $context);
			if ($alternative_type === null)
			{
				if ($syntax->alternative_binding !== null) {
					throw new \RuntimeException('S2S take source requires exactly one output');
				}
			}
			else
			{
				if ($syntax->alternative_binding === null) {
					throw new \RuntimeException('S2S take source requires an alternative output');
				}
				$alternative = self::output($syntax->alternative_binding, $alternative_type, $context);
				if (weakref_get($value->declaration) === weakref_get($alternative->declaration)) {
					throw new \RuntimeException('S2S take outputs must be distinct locals');
				}
			}
			Capability_Preparation::require_copy($source->type, $context);
			return $facts;
		}
		finally {
			$context->statement_assignment = $statement_assignment;
		}
	}

	/** First-pass bindings reference established locals or parameters, never fields or inferred storage. */
	private static function output(expression_node $syntax, canonical_type_use $expected,
		preparation_context $context): prepared_variable_reference
	{
		if (!($syntax instanceof variable_reference_node)) {
			throw new \RuntimeException('S2S take outputs require existing local variables or parameters');
		}
		$reference = object_cast($syntax, variable_reference_node::class);
		$reference->prepare($context);
		$facts = $reference->require_variable_reference_preparation();
		if (!$facts->type->matches($expected)) {
			throw new \RuntimeException('S2S take output requires the exact branch type');
		}
		return $facts;
	}
}

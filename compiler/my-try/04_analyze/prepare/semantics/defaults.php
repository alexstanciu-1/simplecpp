<?php

/* Declaration-owned defaults reuse constant evaluation and data-construction preparation. */
namespace scpp\compiler;

/** Property defaults admit constant values or constructions with constant leaves. */
final class Property_Defaults
{
	/** Validate the default expression family and reuse its existing semantic owner. */
	public static function prepare(expression_node $syntax, canonical_type_use $type,
		preparation_context $context): prepared_property_default
	{
		Preparation_Cleanup::tree($syntax);
		if ($syntax instanceof data_construction_node) {
			$construction = object_cast($syntax, data_construction_node::class);
			self::require_constant_entries($construction, $context);
			$value = Expression_Preparation::prepare($construction, $context, $type);
			$default = new prepared_construction_property_default();
			$default->construction = $construction;
			$default->conversion = Conversion_Preparation::decide($value->type, $type, conversion_context::assignment);
			return $default;
		}
		$facts = new prepared_constant_property_default();
		$facts->value = Constant_Expression_Preparation::evaluate($syntax, $context);
		$facts->conversion = Conversion_Preparation::decide(
			$facts->value->type, $type, conversion_context::assignment);
		return $facts;
	}

	/** Defaults admit construction with constant leaves, not arbitrary calls or instance reads. */
	private static function require_constant_entries(data_construction_node $syntax, preparation_context $context): void
	{
		$entries /** Storage<data_entry_node> */ = $syntax->entries;
		foreach ($entries as $entry) {
			if ($entry->key !== null) {
				Constant_Expression_Preparation::evaluate($entry->key, $context);
			}
			if ($entry->value instanceof data_construction_node) {
				self::require_constant_entries(object_cast($entry->value, data_construction_node::class), $context);
			}
			else {
				Constant_Expression_Preparation::evaluate($entry->value, $context);
			}
		}
	}

	/** Defaults affect construction even when field identity and layout are unchanged. */
	public static function same(?prepared_property_default $before, ?prepared_property_default $after): bool
	{
		if ($before === null) {
			return $after === null;
		}
		if ($after === null) {
			return false;
		}
		if (($before instanceof prepared_constant_property_default) && ($after instanceof prepared_constant_property_default)) {
			$left = object_cast($before, prepared_constant_property_default::class)->value;
			$right = object_cast($after, prepared_constant_property_default::class)->value;
			return $left->type->matches($right->type) && ($left->value_key() === $right->value_key());
		}
		// Construction facts are re-prepared with their declaration. Conservatively republish;
		// never compare mutable expression attachments as if they were immutable snapshots.
		return false;
	}
}

final class Parameter_Defaults
{
	public static function prepare(expression_node $syntax, callable_parameter $parameter,
		preparation_context $context): prepared_parameter_default
	{
		if ($parameter->mode !== passing_mode::value) {
			throw new \RuntimeException('S2S reference parameter defaults are deferred');
		}
		$value /** prepared_constant_expression */;
		if (Type_Queries::is_enum($parameter->type)) {
			if (!($syntax instanceof static_member_reference_node)) {
				throw new \RuntimeException('S2S enum parameter defaults require a constant enum case reference');
			}
			$reference = object_cast(Expression_Preparation::prepare($syntax, $context), prepared_enum_case_reference::class);
			if (!$reference->type->matches($parameter->type)) {
				throw new \RuntimeException('S2S enum parameter default requires the same enum type');
			}
			$value = $reference;
		}
		elseif (!Conversion_Preparation::is_scalar($parameter->type)) {
			throw new \RuntimeException('S2S parameter defaults require scalar value types');
		}
		else {
			$value = self::literal($syntax, $context);
		}
		$facts = new prepared_parameter_default();
		$facts->value = $value;
		$facts->conversion = Conversion_Preparation::decide(
			$value->type, $parameter->type, conversion_context::argument);
		Capability_Preparation::require_copy($parameter->type, $context);
		return $facts;
	}

	/** An optional single sign belongs to the literal domain, not general unary evaluation. */
	private static function literal(expression_node $syntax, preparation_context $context): prepared_literal
	{
		$literal = $syntax;
		$sign = '';
		if ($syntax instanceof unary_expression_node) {
			$unary = object_cast($syntax, unary_expression_node::class);
			$sign = $context->collection->token_snapshot()->text_at($unary->operator_token_index);
			$literal = $unary->operand;
			if ((($sign !== '+') && ($sign !== '-')) ||
				!(($literal instanceof integer_literal_node) || ($literal instanceof float_literal_node))) {
				throw new \RuntimeException('S2S parameter defaults require scalar literals with optional numeric signs');
			}
		}
		if (!(($literal instanceof integer_literal_node) || ($literal instanceof float_literal_node) ||
			($literal instanceof boolean_literal_node) || ($literal instanceof string_literal_node))) {
			throw new \RuntimeException('S2S parameter defaults require scalar literals');
		}
		Expression_Preparation::prepare($literal, $context);
		$value /** prepared_literal */;
		if ($literal instanceof integer_literal_node) {
			$value = object_cast($literal, integer_literal_node::class)->require_integer_literal_preparation();
		} elseif ($literal instanceof float_literal_node) {
			$value = object_cast($literal, float_literal_node::class)->require_float_literal_preparation();
		} elseif ($literal instanceof boolean_literal_node) {
			$value = object_cast($literal, boolean_literal_node::class)->require_boolean_literal_preparation();
		} else {
			$value = object_cast($literal, string_literal_node::class)->require_string_literal_preparation();
		}
		if ($sign === '-') {
			if ($value instanceof prepared_integer_literal) {
				$integer = new prepared_integer_literal();
				$integer->type = $value->type;
				$decimal = object_cast($value, prepared_integer_literal::class)->decimal;
				$integer->decimal = $decimal === '0' ? '0' : '-' . $decimal;
				return $integer;
			}
			$floating = new prepared_float_literal();
			$floating->type = $value->type;
			$floating->decimal = '-' . object_cast($value, prepared_float_literal::class)->decimal;
			return $floating;
		}
		return $value;
	}

	/** Compare immutable value snapshots, independent of token positions and AST reuse. */
	public static function same(?prepared_parameter_default $before, ?prepared_parameter_default $after): bool
	{
		if ($before === null) { return $after === null; }
		if ($after === null) { return false; }
		$left = $before->value;
		$right = $after->value;
		if (!$left->type->matches($right->type)) { return false; }
		return $left->value_key() === $right->value_key();
	}
}

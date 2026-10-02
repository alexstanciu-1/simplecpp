<?php

/* Declaration-owned scalar defaults; bounded normalization, not expression folding. */
namespace scpp\compiler;

final class Parameter_Defaults
{
	public static function prepare(expression_node $syntax, prepared_parameter $parameter,
		preparation_context $context): prepared_parameter_default
	{
		if ($parameter->mode !== passing_mode::value) {
			throw new \RuntimeException('S2S reference parameter defaults are deferred');
		}
		if (!Conversion_Preparation::is_scalar($parameter->type)) {
			throw new \RuntimeException('S2S parameter defaults require scalar value types');
		}
		$value = self::literal($syntax, $context);
		$facts = new prepared_parameter_default();
		$facts->value = $value;
		$facts->conversion = Conversion_Preparation::decide(
			$value->type, $parameter->type, conversion_context::argument);
		Capability_Preparation::require_copy($parameter->type, $context);
		return $facts;
	}

	/** An optional single sign belongs to the literal domain, not general unary evaluation. */
	private static function literal(expression_node $syntax, preparation_context $context): prepared_expression
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
		$value = Expression_Preparation::prepare($literal, $context);
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
		if (($left instanceof prepared_integer_literal) && ($right instanceof prepared_integer_literal)) {
			return object_cast($left, prepared_integer_literal::class)->decimal === object_cast($right, prepared_integer_literal::class)->decimal;
		}
		if (($left instanceof prepared_float_literal) && ($right instanceof prepared_float_literal)) {
			return object_cast($left, prepared_float_literal::class)->decimal === object_cast($right, prepared_float_literal::class)->decimal;
		}
		if (($left instanceof prepared_boolean_literal) && ($right instanceof prepared_boolean_literal)) {
			return object_cast($left, prepared_boolean_literal::class)->value === object_cast($right, prepared_boolean_literal::class)->value;
		}
		if (($left instanceof prepared_string_literal) && ($right instanceof prepared_string_literal)) {
			return object_cast($left, prepared_string_literal::class)->value === object_cast($right, prepared_string_literal::class)->value;
		}
		throw new \LogicException('Prepared parameter default is not a scalar literal');
	}
}

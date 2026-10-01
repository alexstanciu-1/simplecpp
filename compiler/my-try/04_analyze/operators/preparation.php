<?php

/* Prepare operator operands, select one language operation and retain its decision. */
namespace scpp\compiler;

final class Operator_Preparation
{
	/** Prepare one binary syntax shape and attach only its selected semantic decision. */
	public static function prepare_binary(binary_expression_node $syntax,
	preparation_context $context): prepared_binary_expression
	{
		$source_operator = self::binary_kind($syntax, $context);
		self::require_order_independent_operand($syntax->left);
		self::require_order_independent_operand($syntax->right);

		$left = Expression_Preparation::prepare($syntax->left, $context);
		$right = Expression_Preparation::prepare($syntax->right, $context);
		$decision = self::decide($source_operator, [$left->type, $right->type], operator_context::expression, $context);

		$facts = new prepared_binary_expression();
		$facts->decision = $decision;
		$facts->type = $decision->result_type;
		return $facts;
	}

	/** Select from hard-coded language candidates; later providers join at this boundary. */
	public static function decide(operator_kind $source_operator,
	array $operands /** vector<canonical_type_use> */, operator_context $operator_context,
	preparation_context $context): operator_decision
	{
		if ($operator_context !== operator_context::expression) {
			throw new \RuntimeException('S2S operator context is not supported yet');
		}
		if (($source_operator === operator_kind::addition) || ($source_operator === operator_kind::subtraction)
			|| ($source_operator === operator_kind::multiplication)) {
			return Integer_Operators::decide_arithmetic($source_operator, $operands, $context);
		}

		throw new \RuntimeException('S2S operator is not supported yet');
	}

	/** Normalize token spelling before candidate discovery; backends never inspect it. */
	private static function binary_kind(binary_expression_node $syntax,
	preparation_context $context): operator_kind
	{
		$operator_text = $context->collection->token_snapshot()->text_at($syntax->operator_token_index);
		if ($operator_text === '+') {
			return operator_kind::addition;
		}
		if ($operator_text === '-') {
			return operator_kind::subtraction;
		}
		if ($operator_text === '*') {
			return operator_kind::multiplication;
		}
		throw new \RuntimeException('S2S binary operator is not supported yet');
	}

	/** Keep C++ operand-order freedom harmless until general effect facts are available. */
	private static function require_order_independent_operand(expression_node $node): void
	{
		if ($node instanceof cast_expression_node) {
			$cast = object_cast($node, cast_expression_node::class);
			self::require_order_independent_operand($cast->operand);
			return;
		}
		if (($node instanceof integer_literal_node) || ($node instanceof float_literal_node) ||
		($node instanceof boolean_literal_node) || ($node instanceof string_literal_node) ||
		($node instanceof variable_reference_node) || ($node instanceof constant_reference_node) ||
		($node instanceof binary_expression_node)) {
			return;
		}
		throw new \RuntimeException('S2S integer arithmetic operation requires order-independent operands');
	}
}

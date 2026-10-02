<?php

/* Prepare operator operands, select one language operation and retain its decision. */
namespace scpp\compiler;

final class Operator_Preparation
{
	/** Prepare one value operand and retain the common operation decision. */
	public static function prepare_unary(unary_expression_node $syntax,
		preparation_context $context): prepared_unary_expression
	{
		$text = $context->collection->token_snapshot()->text_at($syntax->operator_token_index);
		$source_operator = operator_kind::unary_plus;
		if ($text === '-') {
			$source_operator = operator_kind::unary_minus;
		}
		elseif ($text === '~') {
			$source_operator = operator_kind::bitwise_not;
		}
		elseif ($text === '!') {
			$source_operator = operator_kind::logical_not;
		}
		elseif ($text !== '+') {
			throw new \RuntimeException('S2S unary operator is not supported');
		}
		self::require_order_independent_operand($syntax->operand);
		$operand = Expression_Preparation::prepare($syntax->operand, $context);
		$facts = new prepared_unary_expression();
		$facts->decision = self::decide($source_operator, [$operand->type], operator_context::expression, $context);
		$facts->type = $facts->decision->result_type;
		return $facts;
	}

	/** Prepare one binary syntax shape and attach only its selected semantic decision. */
	public static function prepare_binary(binary_expression_node $syntax,
		preparation_context $context): prepared_binary_expression
	{
		$text = $context->collection->token_snapshot()->operator_text_at($syntax->operator_token_index);
		$source_operator = self::binary_kind($text);
		$sequenced = ($source_operator === operator_kind::logical_and)
			|| ($source_operator === operator_kind::logical_or) || ($source_operator === operator_kind::logical_xor);
		self::require_operand($syntax->left, $sequenced);
		self::require_operand($syntax->right, $sequenced);

		$left = Expression_Preparation::prepare($syntax->left, $context);
		$right = Expression_Preparation::prepare($syntax->right, $context);
		$decision = self::decide($source_operator, [$left->type, $right->type], operator_context::expression, $context);

		$facts = new prepared_binary_expression();
		$facts->decision = $decision;
		$facts->type = $decision->result_type;
		return $facts;
	}

	/** Receiver first, then the optional explicit argument; empty brackets imply no special role. */
	public static function prepare_index(index_node $syntax, preparation_context $context): void
	{
		$statement_assignment = $context->statement_assignment;
		$context->statement_assignment = false;
		try
		{
			$base = Expression_Preparation::prepare($syntax->base, $context);
			$operands /** vector<canonical_type_use> */ = [$base->type];
			if ($syntax->index !== null) {
				$argument /** expression_node */ = $syntax->index;
				$value = Expression_Preparation::prepare($argument, $context);
				$operands[] = $value->type;
			}
			self::decide(operator_kind::index, $operands, operator_context::expression, $context);
		}
		finally {
			$context->statement_assignment = $statement_assignment;
		}
	}

	/** Select from hard-coded language candidates; later providers join at this boundary. */
	public static function decide(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */, operator_context $operator_context,
		preparation_context $context): operator_decision
	{
		if ($operator_context !== operator_context::expression) {
			throw new \RuntimeException('S2S operator context is not supported yet');
		}
		if ($source_operator === operator_kind::index) {
			return self::decide_index($operands);
		}
		if (($source_operator === operator_kind::pre_increment) || ($source_operator === operator_kind::post_increment)
			|| ($source_operator === operator_kind::pre_decrement) || ($source_operator === operator_kind::post_decrement)) {
			return Mutation_Preparation::decide($source_operator, $operands, $context);
		}
		if (($source_operator === operator_kind::unary_plus) || ($source_operator === operator_kind::unary_minus)
			|| ($source_operator === operator_kind::bitwise_not) || ($source_operator === operator_kind::logical_not)) {
			return self::decide_unary($source_operator, $operands, $context);
		}
		if ($source_operator === operator_kind::concatenation) {
			return self::decide_concatenation($operands, $context);
		}
		if (($source_operator === operator_kind::logical_and) || ($source_operator === operator_kind::logical_or)
			|| ($source_operator === operator_kind::logical_xor)) {
			return self::decide_logical($source_operator, $operands, $context);
		}
		return Integer_Operators::decide_binary($source_operator, $operands, $context);
	}

	/** No candidate means no result type or access contract can honestly be published. */
	private static function decide_index(array $operands /** vector<canonical_type_use> */): operator_decision
	{
		$arity = q_count($operands) - 1;
		if (($arity < 0) || ($arity > 1)) {
			throw new \LogicException('operator[] requires a receiver and zero or one explicit argument');
		}
		if (String_Conversions::is_string($operands[0])) {
			throw new \RuntimeException('S2S string operator[] with ' . $arity
				. ' argument(s) is deferred; no string overload is defined');
		}
		throw new \RuntimeException('S2S operator[] with ' . $arity
			. ' argument(s) overload resolution is deferred');
	}

	/** One exact candidate per prefix operator; no implicit truthiness or numeric promotion. */
	private static function decide_unary(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */, preparation_context $context): operator_decision
	{
		if (q_count($operands) !== 1) {
			throw new \LogicException('Unary operation requires one operand');
		}
		$expected = $context->integer;
		$operation = operator_operation::integer_positive;
		if ($source_operator === operator_kind::logical_not) {
			$expected = $context->boolean;
			$operation = operator_operation::boolean_not;
		}
		elseif ($source_operator === operator_kind::unary_minus) {
			$operation = operator_operation::integer_negative;
		}
		elseif ($source_operator === operator_kind::bitwise_not) {
			$operation = operator_operation::integer_complement;
		}
		if (!$operands[0]->matches($expected)) {
			throw new \RuntimeException('S2S unary operation requires its canonical int or bool operand type');
		}
		$decision = new operator_decision();
		$decision->source_operator = $source_operator;
		$decision->operation = $operation;
		$decision->result_type = $expected;
		$decision->operands[] = Conversion_Preparation::decide(
			$operands[0], $expected, conversion_context::operator_operand);
		return $decision;
	}

	/** The bounded string candidate requires explicit casts at non-string source boundaries. */
	private static function decide_concatenation(array $operands /** vector<canonical_type_use> */,
		preparation_context $context): operator_decision
	{
		if (q_count($operands) !== 2) {
			throw new \LogicException('Concatenation requires two operands');
		}
		$string_type = $context->string_type;
		if ((!$operands[0]->matches($string_type)) || (!$operands[1]->matches($string_type))) {
			throw new \RuntimeException('S2S concatenation requires string operands; use an explicit string cast');
		}
		$decision = new operator_decision();
		$decision->source_operator = operator_kind::concatenation;
		$decision->operation = operator_operation::string_concatenation;
		$decision->result_type = $string_type;
		$decision->operands[] = Conversion_Preparation::decide(
			$operands[0], $string_type, conversion_context::operator_operand);
		$decision->operands[] = Conversion_Preparation::decide(
			$operands[1], $string_type, conversion_context::operator_operand);
		return $decision;
	}

	/** Selected boolean operations require lazy RHS lowering, not overloaded C++ logical calls. */
	private static function decide_logical(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */, preparation_context $context): operator_decision
	{
		if (q_count($operands) !== 2) {
			throw new \LogicException('Logical operation requires two operands');
		}
		$boolean = $context->boolean;
		if ((!$operands[0]->matches($boolean)) || (!$operands[1]->matches($boolean))) {
			throw new \RuntimeException('S2S logical operation requires canonical bool operands');
		}
		$decision = new operator_decision();
		$decision->source_operator = $source_operator;
		$decision->operation = $source_operator === operator_kind::logical_and
			? operator_operation::boolean_and : operator_operation::boolean_or;
		if ($source_operator === operator_kind::logical_xor) {
			$decision->operation = operator_operation::boolean_xor;
		}
		$decision->result_type = $boolean;
		$decision->operands[] = Conversion_Preparation::decide(
			$operands[0], $boolean, conversion_context::operator_operand);
		$decision->operands[] = Conversion_Preparation::decide(
			$operands[1], $boolean, conversion_context::operator_operand);
		return $decision;
	}

	/** Normalize token spelling before candidate discovery; backends never inspect it. */
	public static function binary_kind(string $operator_text): operator_kind
	{
		if ($operator_text === '&') {
			return operator_kind::bitwise_and;
		}
		if ($operator_text === '|') {
			return operator_kind::bitwise_or;
		}
		if ($operator_text === '^') {
			return operator_kind::bitwise_xor;
		}
		if ($operator_text === '<<') {
			return operator_kind::shift_left;
		}
		if ($operator_text === '>>') {
			return operator_kind::shift_right;
		}
		if ($operator_text === '<=>') {
			return operator_kind::three_way;
		}
		if (($operator_text === '&&') || ($operator_text === 'and')) {
			return operator_kind::logical_and;
		}
		if (($operator_text === '||') || ($operator_text === 'or')) {
			return operator_kind::logical_or;
		}
		if ($operator_text === 'xor') {
			return operator_kind::logical_xor;
		}
		if ($operator_text === '.') {
			return operator_kind::concatenation;
		}
		if ($operator_text === '+') {
			return operator_kind::addition;
		}
		if ($operator_text === '-') {
			return operator_kind::subtraction;
		}
		if ($operator_text === '**') {
			return operator_kind::power;
		}
		if ($operator_text === '*') {
			return operator_kind::multiplication;
		}
		if ($operator_text === '/') {
			return operator_kind::division;
		}
		if ($operator_text === '%') {
			return operator_kind::remainder;
		}
		if ($operator_text === '==') {
			return operator_kind::equal;
		}
		if ($operator_text === '!=') {
			return operator_kind::not_equal;
		}
		if ($operator_text === '===') {
			return operator_kind::identical;
		}
		if ($operator_text === '!==') {
			return operator_kind::not_identical;
		}
		if ($operator_text === '<') {
			return operator_kind::less;
		}
		if ($operator_text === '<=') {
			return operator_kind::less_equal;
		}
		if ($operator_text === '>') {
			return operator_kind::greater;
		}
		if ($operator_text === '>=') {
			return operator_kind::greater_equal;
		}
		throw new \RuntimeException('S2S binary operator is not supported yet');
	}

	/** Keep C++ operand-order freedom harmless until general effect facts are available. */
	public static function require_order_independent_operand(expression_node $node): void
	{
		self::require_operand($node, false);
	}

	/** Sequenced logical operands admit writes; eager parents inspect the entire subtree. */
	private static function require_operand(expression_node $node, bool $sequenced): void
	{
		if ($node instanceof interpolated_string_node) {
			// The admitted grammar contains only literal parts and ordinary variable reads.
			return;
		}
		if ($node instanceof assignment_expression_node) {
			if ($sequenced) {
				return;
			}
		}
		if ($node instanceof binary_expression_node) {
			if (!$sequenced) {
				$binary = object_cast($node, binary_expression_node::class);
				self::require_operand($binary->left, false);
				self::require_operand($binary->right, false);
			}
			return;
		}
		if ($node instanceof unary_expression_node) {
			$unary = object_cast($node, unary_expression_node::class);
			self::require_operand($unary->operand, $sequenced);
			return;
		}
		if ($node instanceof cast_expression_node) {
			$cast = object_cast($node, cast_expression_node::class);
			self::require_operand($cast->operand, $sequenced);
			return;
		}
		if (($node instanceof integer_literal_node) || ($node instanceof float_literal_node) ||
			($node instanceof boolean_literal_node) || ($node instanceof string_literal_node) ||
			($node instanceof variable_reference_node) || ($node instanceof constant_reference_node)) {
			return;
		}
		throw new \RuntimeException('S2S binary operation requires order-independent operands');
	}
}

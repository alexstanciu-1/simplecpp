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
		// One operand has no sibling ordering ambiguity; it cannot introduce a local.
		$statement_assignment = $context->statement_assignment;
		$context->statement_assignment = false;
		try {
			$operand = Expression_Preparation::prepare($syntax->operand, $context);
			$facts = new prepared_unary_expression();
			$facts->decision = self::decide($source_operator, [$operand->type], operator_context::expression, $context);
			$facts->type = $facts->decision->result_type;
			return $facts;
		}
		finally {
			$context->statement_assignment = $statement_assignment;
		}
	}

	/** Prepare one binary syntax shape and attach only its selected semantic decision. */
	public static function prepare_binary(binary_expression_node $syntax,
		preparation_context $context): prepared_binary_expression
	{
		$text = $context->collection->token_snapshot()->operator_text_at($syntax->operator_token_index);
		$source_operator = self::binary_kind($text);
		// Every binary owns sequencing; operands remain expressions, not declaration sites.
		$previous = $context->statement_assignment;
		$context->statement_assignment = false;
		try
		{
			$left = Expression_Preparation::prepare($syntax->left, $context);
			$right = Expression_Preparation::prepare($syntax->right, $context);
			$decision = self::decide($source_operator, [$left->type, $right->type], operator_context::expression, $context);
			$facts = new prepared_binary_expression();
			$facts->decision = $decision;
			$facts->type = $decision->result_type;
			return $facts;
		}
		finally {
			$context->statement_assignment = $previous;
		}
	}

	/** Receiver first, then the optional explicit argument; empty brackets imply no special role. */
	public static function prepare_index(index_node $syntax, preparation_context $context): prepared_index_expression
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
			$facts = new prepared_index_expression();
			$facts->decision = self::decide(operator_kind::index, $operands, operator_context::expression, $context);
			$facts->type = $facts->decision->result_type;
			return $facts;
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
		if (($operator_context !== operator_context::expression)
			&& ($operator_context !== operator_context::compound_assignment)) {
			throw new \RuntimeException('S2S operator context is not supported yet');
		}
		if ($source_operator === operator_kind::concatenation) {
			return self::decide_concatenation($operands, $context);
		}
		if ($source_operator === operator_kind::index) {
			return Indexing_Preparation::decide($operands, $context);
		}
		if (q_count($operands) === 2) {
			if (Nullable_Conversions::is_null($operands[0]) || Nullable_Conversions::is_null($operands[1])) {
				if (Boxed_Conversions::is_boxed($operands[0]) || Boxed_Conversions::is_boxed($operands[1])) {
					return Boxed_Operators::decide($source_operator, $operands);
				}
				return Presence_Operators::decide_null_comparison($source_operator, $operands, $context);
			}
		}
		if (($source_operator === operator_kind::pre_increment) || ($source_operator === operator_kind::post_increment)
			|| ($source_operator === operator_kind::pre_decrement) || ($source_operator === operator_kind::post_decrement)) {
			return Mutation_Preparation::decide($source_operator, $operands, $context);
		}
		$wrapper_equality = Wrapper_Equality::try_decide($source_operator, $operands, $operator_context, $context);
		if ($wrapper_equality !== null) {
			return $wrapper_equality;
		}
		$guarded = Guarded_Operators::try_decide($source_operator, $operands, $operator_context, $context);
		if ($guarded !== null) {
			return $guarded;
		}
		if (($source_operator === operator_kind::unary_plus) || ($source_operator === operator_kind::unary_minus)
			|| ($source_operator === operator_kind::bitwise_not) || ($source_operator === operator_kind::logical_not)) {
			return self::decide_unary($source_operator, $operands, $context);
		}
		if (($source_operator === operator_kind::logical_and) || ($source_operator === operator_kind::logical_or)
			|| ($source_operator === operator_kind::logical_xor)) {
			return self::decide_logical($source_operator, $operands, $context);
		}
		foreach ($operands as $operand) {
			if (Type_Queries::is_enum($operand)) {
				return Enum_Operators::decide($source_operator, $operands, $context);
			}
		}
		foreach ($operands as $operand) {
			if (Boxed_Conversions::is_boxed($operand)) {
				return Boxed_Operators::decide($source_operator, $operands);
			}
		}
		$scalar_identity = Scalar_Identity::try_decide($source_operator, $operands, $context);
		if ($scalar_identity !== null) {
			return $scalar_identity;
		}
		if (($source_operator === operator_kind::equal) || ($source_operator === operator_kind::not_equal)) {
			if ((q_count($operands) === 2) && $operands[0]->matches($context->boolean)
				&& $operands[1]->matches($context->boolean)) {
				return Boolean_Operators::decide_equality($source_operator, $operands, $context);
			}
		}
		if (String_Operators::is_comparison($source_operator) && (q_count($operands) === 2)
			&& String_Conversions::is_string($operands[0]) && String_Conversions::is_string($operands[1])) {
			return String_Operators::decide_comparison($source_operator, $operands, $context);
		}
		if (Floating_Point_Operators::is_comparison($source_operator)) {
			foreach ($operands as $operand) {
				if ($operand->matches(Language_Types::floating(Model::$language_scope))) {
					return Floating_Point_Operators::decide_comparison($source_operator, $operands, $context);
				}
			}
		}
		if (Floating_Point_Operators::is_arithmetic($source_operator)) {
			foreach ($operands as $operand) {
				if ($operand->matches(Language_Types::floating(Model::$language_scope))) {
					return Floating_Point_Operators::decide($source_operator, $operands, $context);
				}
			}
		}
		return Integer_Operators::decide_binary($source_operator, $operands, $context);
	}

	/** Exact numeric/bool candidates plus explicit condition-negation roles; no numeric promotion. */
	private static function decide_unary(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */, preparation_context $context): operator_decision
	{
		if (q_count($operands) !== 1) {
			throw new \LogicException('Unary operation requires one operand');
		}
		if ((($source_operator === operator_kind::unary_plus) || ($source_operator === operator_kind::unary_minus))
			&& $operands[0]->matches(Language_Types::floating(Model::$language_scope))) {
			return Floating_Point_Operators::decide($source_operator, $operands, $context);
		}
		$expected = $context->integer;
		if ($source_operator === operator_kind::logical_not)
		{
			$definition = Type_Queries::canonical($operands[0])->definition();
			if (self::is_logical_numeric($operands[0])
				|| $operands[0]->matches(Language_Types::error_type(Model::$language_scope))
				|| ($definition instanceof callable_type_definition) || ($definition instanceof closure_type_definition)
				|| Boxed_Conversions::is_boxed($operands[0]) || (Ownership_Conversions::role($operands[0]) !== null)
				|| (Type_Queries::source_record($operands[0]) !== null)
				|| (Nullable_Conversions::payload($operands[0]) !== null))
			{
				$decision = new operator_decision();
				$decision->source_operator = $source_operator;
				$decision->operation = operator_operation::condition_not;
				$decision->result_type = $context->boolean;
				$decision->truthiness = Condition_Truthiness::decide($operands[0]);
				return $decision;
			}
		}
		$operation = operator_operation::integer_positive;
		if (Boxed_Conversions::is_boxed($operands[0])) {
			return Boxed_Operators::decide($source_operator, $operands);
		}
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

	/** Normalize each operand to text before primitive string concatenation. */
	private static function decide_concatenation(array $operands /** vector<canonical_type_use> */,
		preparation_context $context): operator_decision
	{
		if (q_count($operands) !== 2) {
			throw new \LogicException('Concatenation requires two operands');
		}
		if (String_Conversions::is_string_carrier($operands[0]) || String_Conversions::is_string_carrier($operands[1])) {
			foreach ($operands as $operand) {
				if (!String_Conversions::is_string($operand) && !String_Conversions::is_string_carrier($operand)) {
					throw new \RuntimeException('S2S string-carrier concatenation requires string or string-carrier operands');
				}
			}
		}
		$string_type = $context->string_type;
		if (Boxed_Conversions::is_boxed($operands[0]) && Boxed_Conversions::is_boxed($operands[1])) {
			if (!$operands[0]->matches($operands[1])) {
				throw new \RuntimeException('S2S concatenation requires the same boxed family');
			}
		}
		$decision = new operator_decision();
		$decision->source_operator = operator_kind::concatenation;
		$decision->operation = operator_operation::string_concatenation;
		$decision->result_type = $string_type;
		$decision->operands[] = Conversion_Preparation::decide(
			$operands[0], $string_type, conversion_context::concatenation);
		$decision->operands[] = Conversion_Preparation::decide(
			$operands[1], $string_type, conversion_context::concatenation);
		return $decision;
	}

	/** Numeric logical operands retain truth conversion; the operation decides lazy or eager evaluation. */
	private static function decide_logical(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */, preparation_context $context): operator_decision
	{
		if (q_count($operands) !== 2) {
			throw new \LogicException('Logical operation requires two operands');
		}
		$boolean = $context->boolean;
		if (Boxed_Conversions::is_boxed($operands[0]) || Boxed_Conversions::is_boxed($operands[1])) {
			return Boxed_Operators::decide_logical($source_operator, $operands);
		}
		if (self::is_logical_numeric($operands[0]) && self::is_logical_numeric($operands[1])) {
			$decision = new operator_decision();
			$decision->source_operator = $source_operator;
			$decision->operation = $source_operator === operator_kind::logical_and
				? operator_operation::condition_and : operator_operation::condition_or;
			if ($source_operator === operator_kind::logical_xor) {
				$decision->operation = operator_operation::condition_xor;
			}
			$decision->result_type = $boolean;
			foreach ($operands as $operand) {
				$decision->operand_truthiness[] = Condition_Truthiness::decide($operand);
			}
			return $decision;
		}
		if ((!$operands[0]->matches($boolean)) || (!$operands[1]->matches($boolean))) {
			throw new \RuntimeException('S2S logical operation requires canonical bool operands or canonical int/float operands');
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

	/** Numeric logical admission is narrower than the general condition surface. */
	private static function is_logical_numeric(canonical_type_use $operand): bool
	{
		return $operand->matches(Language_Types::integer(Model::$language_scope))
			|| $operand->matches(Language_Types::floating(Model::$language_scope));
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

}

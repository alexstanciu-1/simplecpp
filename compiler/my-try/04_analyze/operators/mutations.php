<?php

/* Prepare updates to established storage and retain computation and result-value contracts. */
namespace scpp\compiler;

final class Mutation_Preparation
{
	/** Resolve a read/write target without using assignment's implicit declaration path. */
	public static function prepare(mutation_expression_node $syntax,
		preparation_context $context): prepared_mutation_expression
	{
		$target = self::prepare_target($syntax->target, $context);
		$text = $context->collection->token_snapshot()->text_at($syntax->operator_token_index);
		$source_operator = operator_kind::pre_increment;
		if ($text === '++') {
			$source_operator = $syntax->postfix ? operator_kind::post_increment : operator_kind::pre_increment;
		}
		elseif ($text === '--') {
			$source_operator = $syntax->postfix ? operator_kind::post_decrement : operator_kind::pre_decrement;
		}
		else {
			throw new \LogicException('Unexpected mutation token');
		}
		$facts = new prepared_mutation_expression();
		$facts->decision = Operator_Preparation::decide(
			$source_operator, [$target->type], operator_context::expression, $context);
		$facts->type = $facts->decision->result_type;
		return $facts;
	}

	/** Share existing-storage validation between unary mutation and compound updates. */
	public static function prepare_target(expression_node $syntax,
		preparation_context $context): prepared_expression
	{
		if (!($syntax instanceof variable_reference_node)) {
			throw new \RuntimeException('S2S mutation requires an existing local or parameter target');
		}
		$target = Expression_Preparation::prepare($syntax, $context);
		if (!$target->addressable) {
			throw new \RuntimeException('S2S mutation requires writable storage');
		}
		return $target;
	}

	/** Compute through the ordinary binary candidate, then retain the explicit write-back boundary. */
	public static function prepare_compound(compound_assignment_expression_node $syntax,
		preparation_context $context): prepared_compound_assignment_expression
	{
		$target = self::prepare_target($syntax->target, $context);
		Operator_Preparation::require_order_independent_operand($syntax->value);
		$value = Expression_Preparation::prepare($syntax->value, $context);
		$text = $context->collection->token_snapshot()->text_at($syntax->operator_token_index);
		$source_operator = Operator_Preparation::binary_kind(string_byte_slice($text, 0, 1));
		$facts = new prepared_compound_assignment_expression();
		$facts->decision = Operator_Preparation::decide(
			$source_operator, [$target->type, $value->type], operator_context::expression, $context);
		$facts->write_back = Conversion_Preparation::decide(
			$facts->decision->result_type, $target->type, conversion_context::assignment);
		$facts->type = $target->type;
		return $facts;
	}

	/** The selected conversion must preserve the target's canonical integer storage type. */
	public static function decide(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */, preparation_context $context): operator_decision
	{
		if (q_count($operands) !== 1) {
			throw new \LogicException('Mutation requires one target');
		}
		$integer = $context->integer;
		if (!$operands[0]->matches($integer)) {
			throw new \RuntimeException('S2S mutation requires canonical int storage');
		}
		$decision = new operator_decision();
		$decision->source_operator = $source_operator;
		$decision->result_type = $integer;
		if ($source_operator === operator_kind::pre_increment) {
			$decision->operation = operator_operation::integer_pre_increment;
		}
		elseif ($source_operator === operator_kind::post_increment) {
			$decision->operation = operator_operation::integer_post_increment;
		}
		elseif ($source_operator === operator_kind::pre_decrement) {
			$decision->operation = operator_operation::integer_pre_decrement;
		}
		elseif ($source_operator === operator_kind::post_decrement) {
			$decision->operation = operator_operation::integer_post_decrement;
		}
		else {
			throw new \LogicException('Unsupported mutation operator');
		}
		$decision->operands[] = Conversion_Preparation::decide(
			$operands[0], $integer, conversion_context::operator_operand);
		return $decision;
	}
}

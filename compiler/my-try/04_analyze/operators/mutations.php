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
		$operands /** vector<canonical_type_use> */ = [$target->type];
		$facts->decision = Operator_Preparation::decide(
			$source_operator, $operands, operator_context::expression, $context);
		$facts->type = $facts->decision->result_type;
		return $facts;
	}

	/** Share existing-storage validation between unary mutation and compound updates. */
	public static function prepare_target(expression_node $syntax,
		preparation_context $context): prepared_expression
	{
		if ($syntax instanceof index_node) {
			Expression_Preparation::prepare($syntax, $context);
		}
		if (!($syntax instanceof variable_reference_node)
			&& !($syntax instanceof field_access_node)
			&& !($syntax instanceof static_member_reference_node)) {
			throw new \RuntimeException('S2S mutation requires an existing local or parameter target, or stable property storage');
		}
		$target = Expression_Preparation::prepare($syntax, $context);
		if (!$target->addressable()) {
			throw new \RuntimeException('S2S mutation requires writable storage');
		}
		Reference_Preparation::require_writable($target->place);
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
		$source_operator = Operator_Preparation::binary_kind(string_byte_slice($text, 0, string_byte_len($text) - 1));
		$facts = new prepared_compound_assignment_expression();
		$facts->write_back_mode = compound_write_back::converted_assignment;
		if ($source_operator === operator_kind::concatenation) {
			// Text replacement may change a boxed payload kind; numeric stored-kind checks do not apply.
			if (!String_Conversions::is_string($target->type) && !Boxed_Conversions::is_boxed($target->type)) {
				throw new \RuntimeException('S2S compound concatenation requires string or boxed storage');
			}
		}
		else {
			if (Boxed_Conversions::is_boxed($target->type)) {
				$facts->write_back_mode = compound_write_back::boxed_stored_kind;
			}
			elseif (Boxed_Conversions::is_boxed($value->type)) {
				throw new \RuntimeException('S2S boxed compound assignment into native storage is deferred');
			}
		}
		$operands /** vector<canonical_type_use> */ = [$target->type, $value->type];
		// Widened binary arithmetic does not yet extend the stored-value update contract.
		if ($source_operator !== operator_kind::concatenation) {
			foreach ($operands as $operand) {
				if ((Type_Queries::canonical($operand)->family() === type_family::integer)
					&& !$operand->matches($context->integer)) {
					throw new \RuntimeException('S2S fixed-width compound assignment is deferred');
				}
			}
		}
		$facts->decision = Operator_Preparation::decide(
			$source_operator, $operands, operator_context::expression, $context);
		$facts->write_back = Conversion_Preparation::decide(
			$facts->decision->result_type, $target->type, conversion_context::assignment);
		$facts->type = $target->type;
		return $facts;
	}

	/** Mutate established integer or boxed storage; boxed payload validity remains runtime-owned. */
	public static function decide(operator_kind $source_operator,
		array $operands /** vector<canonical_type_use> */, preparation_context $context): operator_decision
	{
		if (q_count($operands) !== 1) {
			throw new \LogicException('Mutation requires one target');
		}
		$integer = $context->integer;
		$boxed = Boxed_Conversions::is_boxed($operands[0]) && !$operands[0]->by_value();
		if (!$operands[0]->matches($integer) && !$boxed) {
			throw new \RuntimeException('S2S mutation requires canonical int or boxed storage');
		}
		$decision = new operator_decision();
		$decision->source_operator = $source_operator;
		$decision->result_type = $boxed ? $operands[0] : $integer;
		if ($source_operator === operator_kind::pre_increment) {
			$decision->operation = $boxed ? operator_operation::boxed_pre_increment : operator_operation::integer_pre_increment;
		}
		elseif ($source_operator === operator_kind::post_increment) {
			$decision->operation = $boxed ? operator_operation::boxed_post_increment : operator_operation::integer_post_increment;
		}
		elseif ($source_operator === operator_kind::pre_decrement) {
			$decision->operation = $boxed ? operator_operation::boxed_pre_decrement : operator_operation::integer_pre_decrement;
		}
		elseif ($source_operator === operator_kind::post_decrement) {
			$decision->operation = $boxed ? operator_operation::boxed_post_decrement : operator_operation::integer_post_decrement;
		}
		else {
			throw new \LogicException('Unsupported mutation operator');
		}
		$decision->operands[] = Conversion_Preparation::decide(
			$operands[0], $decision->result_type, conversion_context::operator_operand);
		return $decision;
	}
}

<?php

/* Conditional value joins follow the existing matrix, independently of C++ deduction. */
namespace scpp\compiler;

final class Conditional_Expression_Preparation
{
	/** Prepare all alternatives, without giving conditional writes declaration privileges. */
	public static function prepare(conditional_expression_node $syntax, preparation_context $context): prepared_conditional_expression
	{
		$statement_assignment = $context->statement_assignment;
		$context->statement_assignment = false;
		try
		{
			$condition = Expression_Preparation::prepare($syntax->condition, $context);
			$facts = new prepared_conditional_expression();
			$facts->truthiness = Condition_Truthiness::decide($condition->type);
			$selected /** prepared_expression */ = $condition;
			if ($syntax->true_branch !== null) {
				$branch /** expression_node */ = $syntax->true_branch;
				$selected = Expression_Preparation::prepare($branch, $context);
			}
			$fallback = Expression_Preparation::prepare($syntax->fallback, $context);
			if (!Conversion_Preparation::is_scalar($selected->type) || !Conversion_Preparation::is_scalar($fallback->type)) {
				throw new \RuntimeException('S2S conditional branches require supported scalar value types');
			}
			if (!$selected->type->matches($fallback->type)) {
				throw new \RuntimeException('S2S conditional branches require matching scalar types');
			}
			$facts->type = $selected->type;
			Capability_Preparation::require_copy($facts->type, $context);
			$facts->true_conversion = Conversion_Preparation::decide($selected->type, $facts->type, conversion_context::conditional_branch);
			$facts->fallback_conversion = Conversion_Preparation::decide($fallback->type, $facts->type, conversion_context::conditional_branch);
			return $facts;
		}
		finally { $context->statement_assignment = $statement_assignment; }
	}
}

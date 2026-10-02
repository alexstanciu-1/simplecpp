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
			$facts->type = Selection_Result_Preparation::begin($selected->type, $context, 'conditional');
			$facts->true_conversion = Selection_Result_Preparation::branch($selected->type, $facts->type, 'conditional');
			$facts->fallback_conversion = Selection_Result_Preparation::branch($fallback->type, $facts->type, 'conditional');
			return $facts;
		}
		finally { $context->statement_assignment = $statement_assignment; }
	}
}

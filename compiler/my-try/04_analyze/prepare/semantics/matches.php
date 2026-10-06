<?php

/* Ordered strict constant selection and matching direct-value results. */
namespace scpp\compiler;

final class Match_Preparation
{
	/** Prepare all arms; runtime selection must not hide invalid code or declarations. */
	public static function prepare(match_expression_node $syntax, preparation_context $context): prepared_match_expression
	{
		$statement_assignment = $context->statement_assignment;
		$context->statement_assignment = false;
		try
		{
			$selector = Expression_Preparation::prepare($syntax->selector, $context);
			Selection_Preparation::require_selector($selector->type, $context, 'match');
			$arms /** Storage<match_arm_node> */ = $syntax->arms;
			if ($arms->is_empty()) {
				throw new \RuntimeException('S2S match requires at least one result arm');
			}
			$facts = new prepared_match_expression();
			$has_result = false;
			$has_default = false;
			foreach ($arms as $arm)
			{
				$arm_facts = new prepared_match_arm();
				$labels /** Storage<expression_node> */ = $arm->labels;
				if ($labels->is_empty()) {
					if ($has_default) {
						throw new \RuntimeException('S2S match has duplicate default arms');
					}
					$has_default = true;
				}
				foreach ($labels as $label) {
					$arm_facts->labels[] = Selection_Preparation::label($label, $selector->type, $context, 'match label');
				}
				$result = Expression_Preparation::prepare($arm->result, $context);
				if (!$has_result) {
					$facts->type = Selection_Result_Preparation::begin($result->type, $context, 'match');
					$has_result = true;
				}
				$arm_facts->conversion = Selection_Result_Preparation::branch($result->type, $facts->type, 'match');
				$arm->set_preparation($arm_facts);
			}
			return $facts;
		}
		finally {
			$context->statement_assignment = $statement_assignment;
		}
	}
}

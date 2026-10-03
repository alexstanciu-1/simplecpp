<?php

/* Exact scalar selected-value domain; fallback is checked statically but evaluated lazily. */
namespace scpp\compiler;

final class Coalesce_Expression_Preparation
{
	public static function prepare(coalesce_expression_node $syntax, preparation_context $context): prepared_coalesce_expression
	{
		$statement_assignment = $context->statement_assignment;
		$context->statement_assignment = false;
		try {
			$left = Expression_Preparation::prepare($syntax->left, $context);
			$fallback = Expression_Preparation::prepare($syntax->fallback, $context);
			$result = $left->type;
			$payload = Nullable_Conversions::payload($result);
			if ($payload !== null) { $result = $payload; }
			$facts = new prepared_coalesce_expression();
			$facts->type = Selection_Result_Preparation::begin($result, $context, 'coalesce');
			$facts->fallback_conversion = Selection_Result_Preparation::branch($fallback->type, $result, 'coalesce');
			$facts->left_conversion = Conversion_Preparation::decide($left->type, $result, conversion_context::conditional_branch);
			return $facts;
		}
		finally { $context->statement_assignment = $statement_assignment; }
	}
}

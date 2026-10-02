<?php

/* Shared structured-loop entry/exit, with typed header and continuation hooks. */
namespace scpp\compiler;

final class Loop_Preparation
{
	/** Establish loop-local header visibility and nearest transfer targets for the body. */
	public static function prepare(loop_node $syntax, preparation_context $context): statement_completion
	{
		$enclosing = $context->locals;
		$outer_break /** nullable<breakable_node> */ = $context->break_target;
		$outer_continue /** nullable<loop_node> */ = $context->continue_target;
		$context->locals = new local_environment($enclosing);
		try
		{
			$facts = $syntax->prepare_header($context);
			$syntax->set_preparation($facts);
			$context->break_target = $syntax;
			$context->continue_target = $syntax;
			$body = $syntax->body->prepare_completion($context);
			$syntax->prepare_continuation($context);
			return Completion_Preparation::loop($body, $syntax->body_runs_first(), $facts->condition !== null);
		}
		finally {
			$context->locals = $enclosing;
			$context->break_target = $outer_break;
			$context->continue_target = $outer_continue;
		}
	}

	public static function condition_header(expression_node $condition, preparation_context $context): prepared_loop
	{
		$facts = new prepared_loop();
		$facts->condition = Body_Preparation::prepare_condition($condition, $context);
		return $facts;
	}

	/** Initialization executes in order; only the final condition expression supplies the test. */
	public static function for_header(for_node $syntax, preparation_context $context): prepared_loop
	{
		$initialization /** Storage<statement_node> */ = $syntax->initialization;
		foreach ($initialization as $statement) {
			$statement->prepare($context);
		}
		$facts = new prepared_loop();
		$conditions /** Storage<expression_node> */ = $syntax->conditions;
		$remaining = q_count($conditions);
		foreach ($conditions as $condition)
		{
			$remaining--;
			if ($remaining === 0) {
				$facts->condition = Body_Preparation::prepare_condition($condition, $context);
			}
			else {
				Expression_Preparation::prepare($condition, $context);
			}
		}
		return $facts;
	}

	/** Updates cannot see body locals or introduce new header bindings. */
	public static function for_continuation(for_node $syntax, preparation_context $context): void
	{
		$updates /** Storage<expression_node> */ = $syntax->updates;
		foreach ($updates as $update) {
			Expression_Preparation::prepare($update, $context);
		}
	}
}

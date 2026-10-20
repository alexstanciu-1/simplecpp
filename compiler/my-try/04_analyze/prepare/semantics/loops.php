<?php

/* Shared structured-loop entry/exit, with analysis-owned header and continuation dispatch. */
namespace scpp\compiler;

final class Loop_Preparation
{
	/** Establish loop-local header visibility and nearest transfer targets for the body. */
	public static function prepare(loop_node $syntax, preparation_context $context): statement_completion
	{
		$enclosing = $context->locals;
		$outer_break /** nullable<breakable_node> */ = $context->break_target;
		$outer_continue /** nullable<loop_node> */ = $context->continue_target;
		$outer_alias_permission = $context->alias_declarations_allowed;
		$context->locals = new local_environment($enclosing);
		try
		{
			// Body aliases are lexical; loop-header alias declarations remain a separate slice.
			$context->alias_declarations_allowed = false;
			$facts = self::prepare_header($syntax, $context);
			$context->alias_declarations_allowed = $outer_alias_permission;
			$syntax->set_preparation($facts);
			$context->break_target = $syntax;
			$context->continue_target = $syntax;
			$body = Body_Preparation::prepare_block($syntax->body, $context);
			if ($syntax instanceof for_node) {
				self::for_continuation(object_cast($syntax, for_node::class), $context);
			}
			return Completion_Preparation::loop($body, $syntax->body_runs_first(),
				($facts->condition !== null) || ($facts->iteration !== null));
		}
		finally {
			$context->alias_declarations_allowed = $outer_alias_permission;
			$context->locals = $enclosing;
			$context->break_target = $outer_break;
			$context->continue_target = $outer_continue;
		}
	}

	/** Loop family selection does not introduce another body or retained work owner. */
	private static function prepare_header(loop_node $syntax, preparation_context $context): prepared_loop
	{
		if ($syntax instanceof while_node) {
			return self::condition_header(object_cast($syntax, while_node::class)->condition, $context);
		}
		if ($syntax instanceof do_while_node) {
			return self::condition_header(object_cast($syntax, do_while_node::class)->condition, $context);
		}
		if ($syntax instanceof for_node) {
			return self::for_header(object_cast($syntax, for_node::class), $context);
		}
		if ($syntax instanceof foreach_node) {
			$facts = new prepared_loop();
			$facts->iteration = Iteration_Preparation::prepare(object_cast($syntax, foreach_node::class), $context);
			return $facts;
		}
		throw new \RuntimeException('Preparation is not supported for this loop');
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
			Body_Preparation::prepare_statement($statement, $context);
		}
		$facts = new prepared_loop();
		$conditions /** Storage<expression_node> */ = $syntax->conditions;
		$remaining = q_count($conditions);
		foreach ($conditions as $condition)
		{
			--$remaining;
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

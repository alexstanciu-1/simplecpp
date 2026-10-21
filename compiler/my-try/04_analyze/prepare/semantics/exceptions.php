<?php

/* Canonical admission and lexical handler lifetime; no interprocedural effect analysis. */
namespace scpp\compiler;

final class Exception_Preparation
{
	public static function is_exception(canonical_type_use $type, preparation_context $context): bool
	{
		if ($type->matches(Language_Types::exception_type(Model::$language_scope))) { return true; }
		if (Type_Queries::record_storage($type) !== record_storage_kind::shared) { return false; }
		$record = Type_Queries::source_record($type);
		if ($record === null) { return false; }
		return $context->worker->require_relationships($record, $context)->exception_type;
	}

	private static function require_exception(canonical_type_use $type, preparation_context $context): void
	{
		if (!self::is_exception($type, $context)) {
			throw new \RuntimeException('Throw and catch require the registered Exception type or a shared source descendant');
		}
	}

	public static function prepare_throw(throw_node $node, preparation_context $context): statement_completion
	{
		$value = Expression_Preparation::prepare($node->expression, $context);
		self::require_exception($value->type, $context);
		// Explicit throw has no normal continuation, irrespective of its enclosing handler.
		return new statement_completion(false);
	}

	/** A return crosses every enclosing finally, but never a separate callable boundary. */
	public static function require_return(preparation_context $context): void
	{
		if ($context->finally_return_forbidden) {
			throw new \RuntimeException('Return cannot leave a finally body');
		}
		if ($context->finally_region === null) {
			return;
		}
		if ($context->reference_return_parameter !== null) {
			throw new \RuntimeException('Reference returns through finally are deferred');
		}
		$type = $context->return_type;
		if ($type !== null) {
			if (!Conversion_Preparation::is_scalar($type)
				&& (Type_Queries::canonical($type)->family() !== type_family::no_value)) {
				throw new \RuntimeException('Finally supports only scalar or void return transport');
			}
		}
		$region /** prepared_finally */ = $context->finally_region;
		$region->captures_return = true;
	}

	/** Isolate transfer targets at each finally boundary, preserving nested local loops. */
	public static function prepare_try(try_node $node, preparation_context $context): statement_completion
	{
		if ($node->finalizer === null) {
			return self::prepare_protected($node, $context);
		}
		$outer_region = $context->finally_region;
		$outer_forbidden = $context->finally_return_forbidden;
		$outer_break = $context->break_target;
		$outer_continue = $context->continue_target;
		$facts = new prepared_finally();
		$context->finally_region = $facts;
		$context->break_target = null;
		$context->continue_target = null;
		try
		{
			$protected_completion = self::prepare_protected($node, $context);
			$context->finally_return_forbidden = true;
			$finalizer /** block_node */ = $node->finalizer;
			$final_completion = Body_Preparation::prepare_block($finalizer, $context);
			$result = Completion_Preparation::finalized($protected_completion, $final_completion);
			$facts->can_fall_through = $result->can_fall_through;
			$node->set_preparation($facts);
			if (($outer_region !== null) && $facts->captures_return) {
				$outer_region->captures_return = true;
			}
			return $result;
		}
		finally {
			$context->finally_region = $outer_region;
			$context->finally_return_forbidden = $outer_forbidden;
			$context->break_target = $outer_break;
			$context->continue_target = $outer_continue;
		}
	}

	/** Catch and protected locals occupy sibling lifetime regions. */
	private static function prepare_protected(try_node $node, preparation_context $context): statement_completion
	{
		$protected_completion = Body_Preparation::prepare_block($node->body, $context);
		if ($node->handler === null) {
			return $protected_completion;
		}
		$handler /** catch_node */ = $node->handler;
		$enclosing = $context->locals;
		$context->locals = new local_environment($enclosing);
		try
		{
			$binding = $handler->binding;
			$facts = Body_Preparation::prepare_local_storage($binding->occurrence(), $binding->type_syntax, null, $context);
			self::require_exception($facts->type, $context);
			$binding->set_preparation($facts);
			$handled = Body_Preparation::prepare_statements($handler->body, $context, 0);
			return Completion_Preparation::alternatives($protected_completion, $handled);
		}
		finally {
			$context->locals = $enclosing;
		}
	}
}

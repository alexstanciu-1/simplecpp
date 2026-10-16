<?php

/* Canonical admission and lexical handler lifetime; no interprocedural effect analysis. */
namespace scpp\compiler;

final class Exception_Preparation
{
	private static function require_exception(canonical_type_use $type): void
	{
		if (!$type->matches(Language_Types::exception_type(Model::$language_scope))) {
			throw new \RuntimeException('Throw and catch require the registered Exception type');
		}
	}

	public static function prepare_throw(throw_node $node, preparation_context $context): statement_completion
	{
		$value = Expression_Preparation::prepare($node->expression, $context);
		self::require_exception($value->type);
		// Explicit throw has no normal continuation, irrespective of its enclosing handler.
		return new statement_completion(false);
	}

	/** Bind the handler in a sibling lifetime region and conservatively join normal exits. */
	public static function prepare_try(try_node $node, preparation_context $context): statement_completion
	{
		$protected_completion = Body_Preparation::prepare_block($node->body, $context);
		$enclosing = $context->locals;
		$context->locals = new local_environment($enclosing);
		try
		{
			$binding = $node->handler->binding;
			$facts = Body_Preparation::prepare_local_storage($binding->occurrence(), $binding->type_syntax, null, $context);
			self::require_exception($facts->type);
			$binding->set_preparation($facts);
			$handled = Body_Preparation::prepare_statements($node->handler->body, $context, 0);
			// A call may throw even without an explicit throw in this block.
			return Completion_Preparation::alternatives($protected_completion, $handled);
		}
		finally {
			$context->locals = $enclosing;
		}
	}
}

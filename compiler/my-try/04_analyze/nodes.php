<?php

/* Role: typed access to prepared facts attached to the supported specialized nodes. */
namespace scpp\compiler;

final class Prepared_Nodes
{
	public static function binding(ast_node $node): prepared_binding
	{
		$binding = object_cast($node, variable_binding_statement_node::class);
		return object_cast($binding->prepared, prepared_binding::class);
	}

	/** Missing facts are a generation precondition failure, never inferred by the consumer. */
	public static function expression(ast_node $node): prepared_expression
	{
		if ($node->kind === node_kind::integer_literal) {
			$literal = object_cast($node, integer_literal_node::class);
			return object_cast($literal->prepared, prepared_expression::class);
		}
		if ($node->kind === node_kind::variable_reference) {
			$reference = object_cast($node, variable_reference_node::class);
			return object_cast($reference->prepared, prepared_expression::class);
		}
		throw new \LogicException('Expression has no supported preparation slot');
	}
}

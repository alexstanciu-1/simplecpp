<?php

/* Inspection queries derive categories from the concrete hierarchy. */
namespace scpp\compiler;

final class Syntax_Nodes
{
	public static function category(ast_node $node): node_category
	{
		if ($node instanceof expression_node) {
			return node_category::expression;
		}
		if ($node instanceof statement_node) {
			return node_category::statement;
		}
		return node_category::syntax;
	}
}

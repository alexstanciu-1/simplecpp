<?php

/* Role: traverse syntax while each specialized node clears its own derived state. */
namespace scpp\compiler;

final class Preparation_Cleanup
{
	/** Visit owned AST children, never declaration links, parent links or payload aliases. */
	public static function tree(ast_node $root): void
	{
		$root->clear_preparation();
		$child = $root->first_child();
		while ($child !== null) {
			$node /** ast_node */ = $child;
			self::tree($node);
			$child = $node->next();
		}
	}
}

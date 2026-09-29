<?php

/* Clear attached facts along named syntax ownership, including retired nodes. */
namespace scpp\compiler;

final class Preparation_Cleanup extends Syntax_Maintenance
{
	public static function tree(ast_node $root): void
	{
		$root->maintain(new Preparation_Cleanup());
	}

	protected function enter(ast_node $node): void
	{
		$node->clear_preparation();
	}

	protected function edge(ast_node $parent, ast_node $child): void
	{
		$child->maintain($this);
	}
}

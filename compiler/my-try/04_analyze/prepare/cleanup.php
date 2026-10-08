<?php

/* Clear attached facts along named syntax ownership, including retired nodes. */
namespace scpp\compiler;

final class Preparation_Cleanup implements node_maintenance_worker_i
{
	public static function tree(ast_node $root): void
	{
		$root->maintain(new Preparation_Cleanup());
	}

	public function enter(ast_node $node): void
	{
		$node->clear_preparation();
	}

	public function edge(ast_node $parent, ast_node $child): void
	{
		// A nested callable owns independently scheduled facts, not its enclosing body's facts.
		if ($child instanceof closure_node) { return; }
		$child->maintain($this);
	}

	public function token_index(int $index): int
	{
		return $index;
	}
}

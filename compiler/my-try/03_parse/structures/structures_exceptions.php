<?php

/* Bounded exception syntax owns named edges, not a second child inventory. */
namespace scpp\compiler;

final class throw_node extends statement_node
{
	use Node_Source_Span;

	public expression_node $expression;

	public function kind(): node_kind
	{
		return node_kind::throw_statement;
	}

	public function children(): child_iterator_i
	{
		return new throw_children_iterator($this);
	}

	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->expression);
	}
}

final class catch_node extends ast_node
{
	use Node_Source_Span;

	public variable_declaration_node $binding;
	public block_node $body;

	public function kind(): node_kind
	{
		return node_kind::catch_clause;
	}

	public function children(): child_iterator_i
	{
		return new pair_children_iterator($this->binding, $this->body);
	}

	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->binding);
		$worker->edge($this, $this->body);
	}
}

final class try_node extends statement_node
{
	use Node_Source_Span;

	public block_node $body;
	public catch_node $handler;

	public function kind(): node_kind
	{
		return node_kind::try_statement;
	}

	public function children(): child_iterator_i
	{
		return new pair_children_iterator($this->body, $this->handler);
	}

	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->body);
		$worker->edge($this, $this->handler);
	}
}

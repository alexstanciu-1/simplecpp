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
	use Preparation_Facts;

	public block_node $body;
	public ?catch_node $handler = null;
	public ?block_node $finalizer = null;

	private ?prepared_finally $prepared_facts = null;

	public function require_preparation(): prepared_finally
	{
		return $this->prepared_facts;
	}

	public function kind(): node_kind
	{
		return node_kind::try_statement;
	}

	public function children(): child_iterator_i
	{
		return new try_children_iterator($this);
	}

	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->body);
		if ($this->handler !== null) {
			$worker->edge($this, $this->handler);
		}
		if ($this->finalizer !== null) {
			$worker->edge($this, $this->finalizer);
		}
	}
}

<?php

/* Explicit transfer syntax owns one operand; source and consumer admission are analysis-owned. */
namespace scpp\compiler;

final class move_expression_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	public expression_node $operand;
	private ?prepared_move $prepared_facts = null;

	public function kind(): node_kind
	{
		return node_kind::move_expression;
	}

	public function require_preparation(): prepared_expression
	{
		return $this->prepared_facts;
	}

	public function require_move_preparation(): prepared_move
	{
		return $this->prepared_facts;
	}

	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->operand);
	}

	public function children(): child_iterator_i
	{
		return new move_expression_children_iterator($this);
	}
}

final class move_expression_children_iterator extends children_iterator
{
	private bool $read = false;

	public function __construct(private move_expression_node $source)
	{
	}

	/** Expose the original operand once without creating another owning syntax list. */
	protected function read_next(): ?ast_node
	{
		if ($this->read) {
			return null;
		}
		$this->read = true;
		return $this->source->operand;
	}
}

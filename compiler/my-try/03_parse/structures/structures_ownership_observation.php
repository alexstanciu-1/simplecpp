<?php

/* Observation syntax owns one operand; analysis selects its exact handle result. */
namespace scpp\compiler;

enum ownership_observation_kind
{
	case derive_weak;
	case lock_shared;
}

final class ownership_observation_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	public ownership_observation_kind $operation;
	public expression_node $operand;
	private ?prepared_ownership_observation $prepared_facts = null;

	public function kind(): node_kind
	{
		return node_kind::ownership_observation;
	}

	public function require_preparation(): prepared_expression
	{
		return $this->prepared_facts;
	}

	public function require_observation_preparation(): prepared_ownership_observation
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
		return new ownership_observation_children_iterator($this);
	}
}

final class ownership_observation_children_iterator extends children_iterator
{
	private bool $read = false;

	public function __construct(private ownership_observation_node $source)
	{
	}

	/** Inspect the single owning edge without manufacturing another syntax list. */
	protected function read_next(): ?ast_node
	{
		if ($this->read) {
			return null;
		}
		$this->read = true;
		return $this->source->operand;
	}
}

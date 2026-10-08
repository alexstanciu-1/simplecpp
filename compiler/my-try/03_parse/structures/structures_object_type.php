<?php

/* Checked access and type tests share one operand and a literal target type. */
namespace scpp\compiler;

enum object_type_operation
{
	case checked_cast;
	case type_test;
}

final class object_type_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	public object_type_operation $operation;
	public expression_node $operand;
	public type_node $target_type;
	private ?prepared_object_type $prepared_facts = null;

	public function kind(): node_kind { return node_kind::object_type; }
	public function require_preparation(): prepared_expression { return $this->prepared_facts; }
	public function require_object_type_preparation(): prepared_object_type { return $this->prepared_facts; }

	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->operand);
		$worker->edge($this, $this->target_type);
	}

	public function children(): child_iterator_i { return new object_type_children_iterator($this); }
}

final class object_type_children_iterator extends children_iterator
{
	private int $position = 0;
	public function __construct(private object_type_node $source) {}
	protected function read_next(): ?ast_node
	{
		$this->position++;
		if ($this->position === 1) { return $this->source->operand; }
		if ($this->position === 2) { return $this->source->target_type; }
		return null;
	}
}

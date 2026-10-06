<?php

/* One new-expression syntax, independent of selected ownership and constructor admission. */
namespace scpp\compiler;

final class object_construction_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	public type_node $target_type;
	public Storage $arguments /** Storage<expression_node> */;
	private ?prepared_object_construction $prepared_facts = null;

	public function __construct()
	{
		$this->arguments = new Storage /** Storage<expression_node> */();
	}

	public function kind(): node_kind
	{
		return node_kind::object_construction;
	}

	public function require_preparation(): prepared_expression
	{
		return $this->prepared_facts;
	}

	public function require_construction_preparation(): prepared_object_construction
	{
		return $this->prepared_facts;
	}

	public function children(): child_iterator_i
	{
		return new object_construction_children_iterator($this);
	}

	/** Named owning edges participate in ordinary cleanup and token relocation. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->target_type);
		$arguments /** Storage<expression_node> */ = $this->arguments;
		foreach ($arguments as $argument) {
			$worker->edge($this, $argument);
		}
	}
}

final class object_construction_children_iterator extends children_iterator
{
	private int $next_argument = -1;

	public function __construct(private object_construction_node $source)
	{
	}

	/** Inspect the type and then arguments without materializing another child list. */
	protected function read_next(): ?ast_node
	{
		if ($this->next_argument === -1) {
			$this->next_argument = 0;
			return $this->source->target_type;
		}
		$arguments /** Storage<expression_node> */ = $this->source->arguments;
		if ($this->next_argument >= $arguments->count()) {
			return null;
		}
		$argument = $arguments[$this->next_argument];
		$this->next_argument++;
		return $argument;
	}
}

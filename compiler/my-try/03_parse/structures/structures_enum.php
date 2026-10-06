<?php

/* Enum syntax owns ordered cases; preparation will select backing semantics and storage. */
namespace scpp\compiler;

final class enum_node extends declaration_node
{
	use Node_Source_Span;
	use Collected_Occurrence;

	public string $name;
	/** Absence means a unit declaration, not an inferred integer backing type. */
	public ?type_node $backing_type = null;
	/** @storage.owner */
	public Storage $cases /** Storage<enum_case_node> */;

	public function __construct()
	{
		$this->cases = new Storage /** Storage<enum_case_node> */();
	}

	public function kind(): node_kind
	{
		return node_kind::enum_declaration;
	}

	public function collect(Symbol_Collector $collector, scope $scope, int $index): void
	{
		$collector->collect_enum($this, $scope, $index);
	}

	public function children(): child_iterator_i
	{
		return new enum_children_iterator($this);
	}

	/** Owned edges follow grammar order; no member publication or stage processing happens here. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		if ($this->backing_type !== null) {
			$backing /** type_node */ = $this->backing_type;
			$worker->edge($this, $backing);
		}
		$cases /** Storage<enum_case_node> */ = $this->cases;
		foreach ($cases as $case_node) {
			$worker->edge($this, $case_node);
		}
	}
}

/** Cases are owned by their enum, never published as namespace constants. */
final class enum_case_node extends ast_node
{
	use Node_Source_Span;

	public string $name;
	public ?expression_node $value = null;

	public function kind(): node_kind
	{
		return node_kind::enum_case;
	}

	public function children(): child_iterator_i
	{
		return new enum_case_children_iterator($this);
	}

	/** Retain source expressions without deciding which initializer forms are admitted. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		if ($this->value !== null) {
			$initializer /** expression_node */ = $this->value;
			$worker->edge($this, $initializer);
		}
	}
}

<?php

/* Enum syntax owns ordered cases; preparation selects backing semantics and storage. */
namespace scpp\compiler;

final class enum_node extends declaration_node
{
	use Node_Source_Span;
	use Collected_Occurrence;
	use Preparation_Facts;

	private ?prepared_enum $prepared_facts = null;

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

	public function require_preparation(): prepared_enum
	{
		return $this->prepared_facts;
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

/** Syntax identifies an owner type and named member; preparation selects the member's role. */
final class static_member_reference_node extends assignable_expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	public ?named_type_node $owner_type = null;
	public ?expression_node $qualifier = null;
	/** Includes the source $ prefix for a property; constants/cases have no prefix. */
	public string $member_name;
	private ?prepared_expression $prepared_facts = null;

	public function kind(): node_kind
	{
		return node_kind::static_member_reference;
	}

	public function require_preparation(): prepared_expression
	{
		return $this->prepared_facts;
	}

	public function enum_case_preparation(): prepared_enum_case_reference
	{
		return object_cast($this->prepared_facts, prepared_enum_case_reference::class);
	}

	/** Compile-time type consumers must not discard an expression qualifier's effects. */
	public function require_type_owner(): named_type_node
	{
		if ($this->owner_type === null) { throw new \RuntimeException('Expression-qualified member is not a compile-time type or constant expression'); }
		return $this->owner_type;
	}

	public function children(): child_iterator_i
	{
		return new static_member_children_iterator($this);
	}

	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		if ($this->owner_type !== null) { $worker->edge($this, $this->owner_type); }
		if ($this->qualifier !== null) { $worker->edge($this, $this->qualifier); }
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

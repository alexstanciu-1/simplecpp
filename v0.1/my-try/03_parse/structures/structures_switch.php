<?php

/* Switch selection owns ordered label groups, each with the common lexical body. */
namespace scpp\compiler;

final class switch_node extends breakable_node
{
	use Node_Source_Span;

	public expression_node $selector;
	public Storage $groups /** Storage<switch_case_group> */;

	public function __construct()
	{
		$this->groups = new Storage /** Storage<switch_case_group> */();
	}

	public function kind(): node_kind { return node_kind::switch_statement; }

	public function children(): child_iterator_i
	{
		return new switch_children_iterator($this);
	}

	/** Maintenance owns traversal through selector and source-ordered groups. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->selector);
		$groups /** Storage<switch_case_group> */ = $this->groups;
		foreach ($groups as $group) { $worker->edge($this, $group); }
	}
}

/** Labels select this body together; fallthrough does not extend its lexical scope. */
final class switch_case_group extends ast_node
{
	use Node_Source_Span;

	public Storage $labels /** Storage<switch_label> */;
	public block_node $body;

	public function __construct()
	{
		$this->labels = new Storage /** Storage<switch_label> */();
	}

	public function kind(): node_kind { return node_kind::switch_group; }

	public function children(): child_iterator_i
	{
		return new switch_group_children_iterator($this);
	}

	/** No separate work owner or parent link accompanies a case group. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$labels /** Storage<switch_label> */ = $this->labels;
		foreach ($labels as $label) { $worker->edge($this, $label); }
		$worker->edge($this, $this->body);
	}
}

final class switch_label extends ast_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	public ?expression_node $value = null;
	private ?prepared_switch_label $prepared_facts = null;

	public function __construct(public switch_label_kind $label_kind)
	{
	}

	public function kind(): node_kind { return node_kind::switch_label; }

	public function require_preparation(): prepared_switch_label
	{
		return $this->prepared_facts;
	}

	public function children(): child_iterator_i
	{
		return new switch_label_children_iterator($this);
	}

	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		if ($this->value !== null) { $worker->edge($this, $this->value); }
	}
}

<?php

/* Match is a value selection, with no statement body or break target. */
namespace scpp\compiler;

final class match_expression_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	public expression_node $selector;
	public Storage $arms /** Storage<match_arm_node> */;
	private ?prepared_match_expression $prepared_facts = null;

	public function __construct()
	{
		$this->arms = new Storage /** Storage<match_arm_node> */();
	}

	public function kind(): node_kind
	{
		return node_kind::match_expression;
	}

	public function require_preparation(): prepared_expression
	{
		return $this->prepared_facts;
	}

	public function prepare(preparation_context $context): void
	{
		$this->set_preparation(Match_Preparation::prepare($this, $context));
	}

	public function children(): child_iterator_i
	{
		return new match_children_iterator($this);
	}

	/** Each arm is owned once; no fallback index duplicates the syntax ownership. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->selector);
		$arms /** Storage<match_arm_node> */ = $this->arms;
		foreach ($arms as $arm) {
			$worker->edge($this, $arm);
		}
	}

	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_match_expression($this);
	}
}

/** Empty labels mean default; otherwise every label selects the same result. */
final class match_arm_node extends ast_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	public Storage $labels /** Storage<expression_node> */;
	public expression_node $result;
	private ?prepared_match_arm $prepared_facts = null;

	public function __construct()
	{
		$this->labels = new Storage /** Storage<expression_node> */();
	}

	public function kind(): node_kind
	{
		return node_kind::match_arm;
	}

	public function require_preparation(): prepared_match_arm
	{
		return $this->prepared_facts;
	}

	public function children(): child_iterator_i
	{
		return new match_arm_children_iterator($this);
	}

	/** Labels precede the result in inspection and maintenance, not duplicated branches. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$labels /** Storage<expression_node> */ = $this->labels;
		foreach ($labels as $label) {
			$worker->edge($this, $label);
		}
		$worker->edge($this, $this->result);
	}
}

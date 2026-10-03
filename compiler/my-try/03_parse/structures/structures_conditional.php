<?php

/* Full and shorthand conditional selection share one value-expression shape. */
namespace scpp\compiler;

/** Presence-based lazy selection is distinct from a truthiness-based conditional. */
final class coalesce_expression_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;
	public expression_node $left;
	public expression_node $fallback;
	private ?prepared_coalesce_expression $prepared_facts = null;
	public function kind(): node_kind { return node_kind::coalesce_expression; }
	public function require_preparation(): prepared_expression { return $this->require_coalesce_preparation(); }
	public function require_coalesce_preparation(): prepared_coalesce_expression { return $this->prepared_facts; }
	public function prepare(preparation_context $context): void
	{
		$this->set_preparation(Coalesce_Expression_Preparation::prepare($this, $context));
	}
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->left);
		$worker->edge($this, $this->fallback);
	}
	public function children(): child_iterator_i { return new coalesce_expression_children_iterator($this); }
	public function generate_cpp(cpp_generation_worker_i $worker): string { return $worker->generate_coalesce_expression($this); }
}

final class conditional_expression_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	public expression_node $condition;
	public ?expression_node $true_branch = null;
	public expression_node $fallback;
	private ?prepared_conditional_expression $prepared_facts = null;

	public function kind(): node_kind
	{
		return node_kind::conditional_expression;
	}

	public function require_preparation(): prepared_expression
	{
		return $this->require_conditional_preparation();
	}

	public function require_conditional_preparation(): prepared_conditional_expression
	{
		return $this->prepared_facts;
	}

	public function prepare(preparation_context $context): void
	{
		$this->set_preparation(Conditional_Expression_Preparation::prepare($this, $context));
	}

	public function children(): child_iterator_i
	{
		return new conditional_expression_children_iterator($this);
	}

	/** Visit each owning edge once; shorthand does not duplicate its condition node. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->condition);
		if ($this->true_branch !== null) { $worker->edge($this, $this->true_branch); }
		$worker->edge($this, $this->fallback);
	}

	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_conditional_expression($this);
	}
}

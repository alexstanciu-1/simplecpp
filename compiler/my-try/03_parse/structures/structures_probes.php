<?php

/* One-value language probes, distinct from ordinary function lookup. */
namespace scpp\compiler;

enum value_probe_kind { case isset; case empty; }

final class value_probe_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;
	public value_probe_kind $probe_kind;
	public expression_node $operand;
	private ?prepared_value_probe $prepared_facts = null;
	public function kind(): node_kind { return node_kind::value_probe; }
	public function require_preparation(): prepared_expression { return $this->require_probe_preparation(); }
	public function require_probe_preparation(): prepared_value_probe { return $this->prepared_facts; }
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->operand);
	}
	public function children(): child_iterator_i { return new value_probe_children_iterator($this); }
}

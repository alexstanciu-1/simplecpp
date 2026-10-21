<?php
namespace scpp\compiler;

/** Await owns its operand; preparation chooses suspension versus synchronous waiting. */
final class await_expression_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;
	public expression_node $operand;
	private ?prepared_await $prepared_facts = null;
	public function kind(): node_kind { return node_kind::await_expression; }
	public function require_preparation(): prepared_expression { return $this->prepared_facts; }
	public function require_await_preparation(): prepared_await { return $this->prepared_facts; }
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->operand);
	}
	public function children(): child_iterator_i { return new await_children_iterator($this); }
}

final class await_children_iterator extends children_iterator
{
	private bool $read = false;
	public function __construct(private await_expression_node $syntax) {}
	protected function read_next(): ?ast_node
	{
		if ($this->read) { return null; }
		$this->read = true;
		return $this->syntax->operand;
	}
}

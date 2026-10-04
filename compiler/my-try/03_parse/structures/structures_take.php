<?php

/* Conditional output binding, not an ordinary callable or assignment target. */
namespace scpp\compiler;

final class take_expression_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;
	public expression_node $source;
	public expression_node $value_binding;
	public ?expression_node $alternative_binding = null;
	private ?prepared_take $prepared_facts = null;

	public function kind(): node_kind
	{
		return node_kind::take_expression;
	}

	public function require_preparation(): prepared_expression
	{
		return $this->require_take_preparation();
	}

	public function require_take_preparation(): prepared_take
	{
		return $this->prepared_facts;
	}

	public function prepare(preparation_context $context): void
	{
		$this->set_preparation(Take_Preparation::prepare($this, $context));
	}

	/** Named owning edges serve cleanup and incremental source movement alike. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->value_binding);
		if ($this->alternative_binding !== null) {
			$worker->edge($this, $this->alternative_binding);
		}
		$worker->edge($this, $this->source);
	}

	public function children(): child_iterator_i
	{
		return new take_children_iterator($this);
	}

	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_take_expression($this);
	}
}

final class take_children_iterator extends children_iterator
{
	private int $next_field = 0;
	public function __construct(private take_expression_node $syntax) {}

	/** Inspect binding syntax first, followed by the source, without allocating child lists. */
	protected function read_next(): ?ast_node
	{
		if ($this->next_field === 0) {
			$this->next_field = 1;
			return $this->syntax->value_binding;
		}
		if ($this->next_field === 1)
		{
			$this->next_field = 2;
			if ($this->syntax->alternative_binding !== null) {
				return $this->syntax->alternative_binding;
			}
		}
		if ($this->next_field === 2) {
			$this->next_field = 3;
			return $this->syntax->source;
		}
		return null;
	}
}

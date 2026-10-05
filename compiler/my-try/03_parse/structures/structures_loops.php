<?php

/* Loop forms own only their specific header syntax; body and work dispatch are shared. */
namespace scpp\compiler;

final class while_node extends loop_node
{
	public expression_node $condition;

	public function kind(): node_kind
	{
		return node_kind::while_loop;
	}

	public function children(): child_iterator_i
	{
		return new loop_pair_children_iterator($this->condition, $this->body);
	}

	/** Enumerate the two owning children in source grammar order. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->condition);
		$worker->edge($this, $this->body);
	}
}

final class do_while_node extends loop_node
{
	public expression_node $condition;

	public function kind(): node_kind
	{
		return node_kind::do_while_loop;
	}

	public function body_runs_first(): bool
	{
		return true;
	}

	public function children(): child_iterator_i
	{
		return new loop_pair_children_iterator($this->body, $this->condition);
	}

	/** Enumerate the two owning children in source grammar order. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->body);
		$worker->edge($this, $this->condition);
	}
}

/** For clauses are ordered syntax lists; only initialization admits declarations. */
final class for_node extends loop_node
{
	public Storage $initialization /** Storage<statement_node> */;
	public Storage $conditions /** Storage<expression_node> */;
	public Storage $updates /** Storage<expression_node> */;

	public function __construct()
	{
		$this->initialization = new Storage /** Storage<statement_node> */();
		$this->conditions = new Storage /** Storage<expression_node> */();
		$this->updates = new Storage /** Storage<expression_node> */();
	}

	public function kind(): node_kind
	{
		return node_kind::for_loop;
	}

	public function children(): child_iterator_i
	{
		return new for_children_iterator($this);
	}

	/** Keep for header order for maintenance; execution order belongs to preparation/emission. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$initialization /** Storage<statement_node> */ = $this->initialization;
		foreach ($initialization as $statement) {
			$worker->edge($this, $statement);
		}
		$conditions /** Storage<expression_node> */ = $this->conditions;
		foreach ($conditions as $condition) {
			$worker->edge($this, $condition);
		}
		$updates /** Storage<expression_node> */ = $this->updates;
		foreach ($updates as $update) {
			$worker->edge($this, $update);
		}
		$worker->edge($this, $this->body);
	}
}

/** Value iteration reuses the ordinary loop body and transfer protocol. */
final class foreach_node extends loop_node
{
	public expression_node $iterable;
	public ?variable_reference_node $key = null;
	public variable_reference_node $value;

	public function kind(): node_kind
	{
		return node_kind::foreach_loop;
	}

	public function children(): child_iterator_i
	{
		return new foreach_children_iterator($this);
	}

	/** Header binding syntax remains an owned child even though facts live on the loop. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->iterable);
		if ($this->key !== null) {
			$worker->edge($this, $this->key);
		}
		$worker->edge($this, $this->value);
		$worker->edge($this, $this->body);
	}
}

final class break_node extends control_transfer_node
{
	public function kind(): node_kind
	{
		return node_kind::break_statement;
	}

	public function transfer_kind(): control_transfer_kind
	{
		return control_transfer_kind::break_construct;
	}
}

final class continue_node extends control_transfer_node
{
	public function kind(): node_kind
	{
		return node_kind::continue_statement;
	}

	public function transfer_kind(): control_transfer_kind
	{
		return control_transfer_kind::continue_loop;
	}
}

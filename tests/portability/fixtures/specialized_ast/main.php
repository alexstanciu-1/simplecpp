<?php

/* Bounded migration proof: typed facts, trait fields and dispatch through a node base. */
namespace ast_contract;

class facts
{
	public int $value = 17;
}

final class integer_facts extends facts
{
	public int $precision = 64;
}

trait Source_Span
{
	public int $first_token_index /** uint32 */ = 0;
	public int $end_token_index /** uint32 */ = 1;
}

interface node_i
{
	public function require_preparation(): facts;
	public function evaluate(worker $process): int;
}

abstract class node implements node_i
{
	public abstract function require_preparation(): facts;
	public abstract function evaluate(worker $process): int;
}

final class integer_node extends node
{
	use Source_Span;

	private ?integer_facts $prepared_facts = null;

	public function __construct()
	{
		$this->prepared_facts = new integer_facts();
	}

	public function require_preparation(): integer_facts
	{
		return $this->prepared_facts;
	}

	public function cursor(): node_cursor
	{
		return new node_cursor($this);
	}

	public function evaluate(worker $process): int
	{
		return $process->integer($this);
	}
}

/** A cursor retains the existing source owner after its creator returns. */
final class node_cursor
{
	private integer_node $source;

	public function __construct(integer_node $source)
	{
		$this->source = $source;
	}

	public function current(): integer_node
	{
		return $this->source;
	}

	public static function retained(): node_cursor
	{
		$source = new integer_node();
		return $source->cursor();
	}
}

final class worker
{
	public function integer(integer_node $source): int
	{
		return $source->require_preparation()->precision;
	}

	public function process(node $source): int
	{
		return $source->evaluate($this);
	}
	public function through_base(node $source): int
	{
		return $source->require_preparation()->value;
	}

	public function through_interface(node_i $source): int
	{
		return $source->require_preparation()->value;
	}
}

$source = new integer_node();
$cursor = node_cursor::retained();
$process = new worker();
echo $process->process($source), ':', $process->through_base($source), ':', $process->through_interface($source), ':', $source->require_preparation()->value, ':', $cursor->current()->require_preparation()->precision, "\n";

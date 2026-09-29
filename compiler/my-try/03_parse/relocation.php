<?php

/* Keep retained syntax and collected provenance in the same token generation. */
namespace scpp\compiler;

final class Syntax_Relocation extends Syntax_Maintenance
{
	private Symbol_Collector $collector;
	private int $delta;
	private bool $discard;

	public function __construct(Symbol_Collector $collector, int $delta, bool $discard)
	{
		$this->collector = $collector;
		$this->delta = $delta;
		$this->discard = $discard;
	}

	/** Retire temporary occurrences or relocate the retained occurrence in place. */
	protected function enter(ast_node $node): void
	{
		$entry = $node->optional_occurrence();
		if ($entry !== null) {
			if ($this->discard) {
				$entry->revision = 0;
			}
			else {
				$this->collector->retain($entry, $this->delta);
			}
		}
		if (!$this->discard) {
			$node->set_span($node->start_token() + $this->delta, $node->end_token() + $this->delta);
		}
	}

	protected function edge(ast_node $parent, ast_node $child): void
	{
		$child->maintain($this);
	}

	/** Binary operator spelling is still token-backed in this unsupported syntax family. */
	public function visit_binary_expression(binary_expression_node $node): void
	{
		if (!$this->discard) {
			$node->operator_token_index += $this->delta;
		}
		parent::visit_binary_expression($node);
	}
}

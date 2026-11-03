<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Collects an inventory of declarations, then publishes one head per key; only the declaring scope links duplicates. */
final class Scope_Collection
{
	private readonly scope $scope;
	private array $pending /** array<uint64,ast_declaration_interface>; object-ID inventory, many distinct declarations are valid */ = [];
	private bool $published = false;

	public function __construct(scope $scope) { $this->scope = $scope; }

	/** Register syntax without changing links visible to the previous compilation generation. */
	public function declare(ast_declaration_interface $declaration): void
	{
		$this->require_open();
		$id = spl_object_id($declaration);
		if (isset($this->pending[$id])) { throw new \LogicException('Declaration registered twice'); }
		$this->pending[$id] = $declaration;
	}

	/** Non-owning indexes collect the same references; publish_references() never rewrites duplicate links. */
	public function import(ast_declaration_interface $declaration): void { $this->declare($declaration); }

	public function inventory(): array /** array<uint64,ast_declaration_interface> */ { return $this->pending; }

	/** Coordinator selects deterministic publication order without grouping declarations into per-key lists. */
	public function order(callable $compare): void
	{
		$this->require_open();
		uasort($this->pending, $compare);
	}

	/** File indexes retain their own first reference, but do not own a second duplicate chain. */
	public function publish_references(): scope { return $this->publish_index(null, false); }

	/** After successful collection and the reader barrier, rebuild the declaring scope's chains in place. */
	public function publish(?scope $previous = null): scope { return $this->publish_index($previous, true); }

	private function publish_index(?scope $previous, bool $owns_duplicates): scope
	{
		$this->require_open();
		$target = $previous ?? $this->scope;
		$heads = [];
		$expected = $target->declarations;
		$changed = [];
		// Compare the old chains before rewriting any link; no candidate lists or AST copies.
		foreach ($this->pending as $node) {
			$key = $node->symbol_key();
			$heads[$key] ??= $node;
			if (($expected[$key] ?? null) !== $node) { $changed[$key] = true; }
			$expected[$key] = ($expected[$key] ?? null)?->duplicate_element();
		}
		foreach ($expected as $key => $node) { if ($node !== null) { $changed[$key] = true; } }
		if ($owns_duplicates) {
			// Retired syntax keeps its body, but not a historical duplicate-chain snapshot.
			foreach ($target->declarations as $head) {
				for ($node = $head; $node !== null; $node = $next) {
					$next = $node->duplicate_element();
					$node->clear_duplicate();
				}
			}
			foreach ($this->pending as $node) { $node->clear_duplicate(); }
			$tails = [];
			foreach ($this->pending as $node) {
				$key = $node->symbol_key();
				if (isset($tails[$key])) { $tails[$key]->link_duplicate($node); }
				$tails[$key] = $node;
			}
		}
		foreach ($heads as $key => $head) { $target->entry($key); }
		foreach ($target->entries() as $key => $entry) { $entry->publish($heads[$key] ?? null, isset($changed[$key])); }
		$target->declarations = $heads;
		$this->published = true;
		return $target;
	}

	private function require_open(): void
	{
		if ($this->published) { throw new \LogicException('Scope collection is already published'); }
	}
}

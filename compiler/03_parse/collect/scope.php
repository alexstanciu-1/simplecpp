<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Collects an inventory of declarations, then publishes one head per key; only the declaring scope
 * links duplicates.
 */
final class Scope_Collection
{
	/** PROPERTIES */
	/** Native retained until publication; destination is owned by AST or session. */
	private readonly scope $scope;
	/**
	 * Object-ID inventory, many distinct declarations are valid; native non-owning references; source
	 * inventory retains nodes.
	 *
	 * @var ast_declaration_interface[]
	 */
	private array $pending /** array<uint64,ast_declaration_interface> */ = [];
	private bool $published = false;

	/** CONSTRUCTOR */
	public function __construct(scope $scope)
	{
		$this->scope = $scope;
	}

	/** METHODS */
	/** Register syntax without changing links visible to the previous compilation generation. */
	public function declare(ast_declaration_interface $declaration): void
	{
		$this->require_open();
		$id = spl_object_id($declaration);
		if (isset($this->pending[$id])) {
			throw new \LogicException('Declaration registered twice');
		}
		$this->pending[$id] = $declaration;
	}

	/** Non-owning indexes collect the same references; publish_references() never rewrites duplicate links. */
	public function import(ast_declaration_interface $declaration): void
	{
		$this->declare($declaration);
	}

	/** @return ast_declaration_interface[] */
	public function inventory(): array /** array<uint64,ast_declaration_interface> */
	{
		return $this->pending;
	}

	/** Coordinator selects deterministic publication order without grouping declarations into per-key lists. */
	public function order(callable $compare): void
	{
		$this->require_open();
		uasort($this->pending, $compare);
	}

	/** File indexes retain their own first reference, but do not own a second duplicate chain. */
	public function publish_references(): scope
	{
		return $this->publish_index(null, false);
	}

	/** After successful collection and the reader barrier, rebuild the declaring scope's chains in place. */
	public function publish(?scope $previous = null): scope
	{
		return $this->publish_index($previous, true);
	}

	/**
	 * Replace only affected chains, preserving declarations from unchanged source roots in those chains.
	 * @param bool[] $removed Retired declaration object identities.
	 * @param bool[] $keys Affected symbol keys from retired and incoming declarations.
	 */
	public function publish_changes(array $removed /** array<uint64,bool> */, array $keys /** array<string,bool> */,
		callable $compare): scope
	{
		$this->require_open();
		foreach ($keys as $key => $_) {
			for ($node = $this->scope->declarations[$key] ?? null; $node !== null; $node = $node->duplicate_element()) {
				if (!isset($removed[spl_object_id($node)])) {
					$this->import($node);
				}
			}
		}
		$this->order($compare);
		return $this->publish_index($this->scope, true, $keys);
	}

	/**
	 * Compare prior identities and rebuild only selected chains, or the complete index when keys are null.
	 * @param bool[]|null $keys
	 */
	private function publish_index(?scope $previous, bool $owns_duplicates, ?array $keys /** array<string,bool>|null */ = null): scope
	{
		$this->require_open();
		$target = $previous ?? $this->scope;
		/** @var ast_declaration_interface[] $heads */
		$heads = [];
		$old_heads = [];
		if ($keys === null) {
			$old_heads = $target->declarations;
		}
		else {
			foreach ($keys as $key => $_) {
				if (isset($target->declarations[$key])) {
					$old_heads[$key] = $target->declarations[$key];
				}
			}
		}
		$expected = $old_heads;
		$changed = [];
		// Compare the old chains before rewriting any link; no candidate lists or AST copies.
		foreach ($this->pending as $node)
		{
			$key = $node->symbol_key();
			$heads[$key] ??= $node;
			if (($expected[$key] ?? null) !== $node) {
				$changed[$key] = true;
			}
			$expected[$key] = ($expected[$key] ?? null)?->duplicate_element();
		}
		foreach ($expected as $key => $node) {
			if ($node !== null) {
				$changed[$key] = true;
			}
		}
		if ($owns_duplicates)
		{
			// Retired syntax keeps its body, but not a historical duplicate-chain snapshot.
			foreach ($old_heads as $head) {
				for ($node = $head; $node !== null; $node = $next) {
					$next = $node->duplicate_element();
					$node->clear_duplicate();
				}
			}
			foreach ($this->pending as $node) {
				$node->clear_duplicate();
			}
			/** @var ast_declaration_interface[] $tails */
			$tails = [];
			foreach ($this->pending as $node) {
				$key = $node->symbol_key();
				if (isset($tails[$key])) {
					$tails[$key]->link_duplicate($node);
				}
				$tails[$key] = $node;
			}
		}
		$target->begin_publication();
		if ($keys === null) {
			foreach ($heads as $key => $head) {
				$target->entry($key);
			}
			foreach ($target->entries() as $key => $entry) {
				$target->publish_entry($key, $heads[$key] ?? null, isset($changed[$key]));
			}
			$target->declarations = $heads;
		}
		else {
			foreach ($keys as $key => $_) {
				$head = $heads[$key] ?? null;
				$target->publish_entry($key, $head, isset($changed[$key]));
				if ($head === null) {
					unset($target->declarations[$key]);
				}
				else {
					$target->declarations[$key] = $head;
				}
			}
		}
		$this->published = true;
		return $target;
	}

	private function require_open(): void
	{
		if ($this->published) {
			throw new \LogicException('Scope collection is already published');
		}
	}
}

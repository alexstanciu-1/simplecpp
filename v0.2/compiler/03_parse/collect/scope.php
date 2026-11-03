<?php

declare(strict_types=1);
namespace scpp\compiler;

/** One collection/publication mechanism for every scope; syntax owners declare, aggregate indexes import references. */
final class Scope_Collection
{
	private readonly scope $scope;
	private array $pending /** array<string,list<ast_declaration_interface>> */ = [];
	private array $tails /** array<string,ast_declaration_interface>; declaration-chain tails owned by this collection */ = [];
	private bool $published = false;

	public function __construct(scope $scope) { $this->scope = $scope; }

	/** New syntax in its owning scope: link duplicates without searching the existing chain. */
	public function declare(ast_declaration_interface $declaration): void
	{
		$this->require_open();
		$key = $declaration->symbol_key();
		if (isset($this->tails[$key])) { $this->tails[$key]->link_duplicate($declaration); }
		$this->tails[$key] = $declaration;
		$this->import($declaration);
	}

	/** Aggregation of an existing declaration never changes the source-owned duplicate chain. */
	public function import(ast_declaration_interface $declaration): void
	{
		$this->require_open();
		$this->pending[$declaration->symbol_key()][] = $declaration;
	}

	public function candidates(): array /** array<string,list<ast_declaration_interface>> */ { return $this->pending; }

	/** Caller completes its syntax/IO validation before publishing. No semantic work occurs here. */
	public function publish(?scope $previous = null): scope
	{
		$this->require_open();
		$target = $previous ?? $this->scope;
		foreach ($this->pending as $key => $candidates) { $target->entry($key); }
		foreach ($target->entries() as $key => $entry) { $entry->publish($this->pending[$key] ?? []); }
		$target->declarations = $this->pending;
		$this->published = true;
		return $target;
	}

	private function require_open(): void
	{
		if ($this->published) { throw new \LogicException('Scope collection is already published'); }
	}
}

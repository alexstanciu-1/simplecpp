<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Discovery is a notification, not permission to consume an unfinished declaration. */
interface declaration_sink {
	public function discovered(ast_declaration_interface $declaration): void;
}

/**
 * Stable identity within one scope; one head reference retains error-only duplicates through the
 * declaring scope chain.
 */
final class symbol_entry
{
	public change_status $status = change_status::removed;
	/** Native non-owning; declaration lifetime belongs to its source AST. */
	private ?ast_declaration_interface $head = null;

	public function publish(?ast_declaration_interface $head, bool $changed = false): void
	{
		$this->status = $head === null ? change_status::removed
			: ($this->head === null ? change_status::added : ((($changed) || ($this->head !== $head)) ? change_status::changed : change_status::none));
		$this->head = $head;
	}

	public function declaration(): ?ast_declaration_interface
	{
		if ($this->head?->duplicate_element() !== null) {
			throw new \RuntimeException('Ambiguous symbol');
		}
		return $this->head;
	}
}

/** Declaration namespace shared by compilation, file and body scopes; e.g. var:x belongs to one body scope. */
final class scope
{
	/** PROPERTIES */
	/** Native non-owning lexical parent; null for a compilation or a detached file. No implicit parent lookup yet. */
	public ?scope $parent;
	/** @var ast_declaration_interface[] Published head references; native non-owning AST references. */
	public array $declarations /** array<string,ast_declaration_interface> */ = [];
	/** @var symbol_entry[] Stable identities including tombstones; native owning stable entries. */
	private array $entries /** array<string,symbol_entry> */ = [];

	/** CONSTRUCTOR */
	public function __construct(?scope $parent = null)
	{
		$this->parent = $parent;
	}

	/** METHODS */
	/** Find/create only in this scope; creation never writes to a parent. */
	public function entry(string $key): symbol_entry
	{
		return $this->entries[$key] ??= new symbol_entry();
	}

	/** @return symbol_entry[] */
	public function entries(): array /** array<string,symbol_entry> */
	{
		return $this->entries;
	}

	/** Resolve only in this scope; declaration-before-use and visibility remain consumer policies. */
	public function resolve(string $key): ?ast_declaration_interface
	{
		return isset($this->entries[$key]) ? $this->entries[$key]->declaration() : null;
	}

	/** @return ast_declaration_interface[] */
	public function heads(): array /** array<string,ast_declaration_interface> */
	{
		return $this->declarations;
	}
}

<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Discovery is a notification, not permission to consume an unfinished declaration. */
interface declaration_sink
{
	public function discovered(ast_declaration_interface $declaration): void;
}

/** Stable identity within one scope; candidate references retain duplicates without copying AST data. */
final class symbol_entry
{
	public change_status $status = change_status::removed;
	private array $candidates /** list<ast_declaration_interface> */ = [];

	public function publish(array $candidates /** list<ast_declaration_interface> */): void
	{
		$old = $this->candidates;
		$this->status = $candidates === [] ? change_status::removed
			: ($old === [] ? change_status::added : ($old === $candidates ? change_status::none : change_status::changed));
		$this->candidates = $candidates;
	}

	public function declaration(): ?ast_declaration_interface
	{
		if (count($this->candidates) > 1) { throw new \RuntimeException('Ambiguous symbol'); }
		return $this->candidates[0] ?? null;
	}
}

/** Declaration namespace shared by compilation, file and body scopes; e.g. var:x belongs to one body scope. */
final class scope
{
	/** Native non-owning lexical parent; null for a compilation or a detached file. No implicit parent lookup yet. */
	public ?scope $parent;
	public array $declarations /** array<string,list<ast_declaration_interface>>; published candidate index */ = [];
	private array $entries /** array<string,symbol_entry>; stable identities including tombstones */ = [];

	public function __construct(?scope $parent = null) { $this->parent = $parent; }

	/** Find/create only in this scope; creation never writes to a parent. */
	public function entry(string $key): symbol_entry
	{
		return $this->entries[$key] ??= new symbol_entry();
	}

	public function entries(): array /** array<string,symbol_entry> */ { return $this->entries; }

	/** Resolve only in this scope; declaration-before-use and visibility remain consumer policies. */
	public function resolve(string $key): ?ast_declaration_interface
	{
		return isset($this->entries[$key]) ? $this->entries[$key]->declaration() : null;
	}

	public function heads(): array /** array<string,ast_declaration_interface>; first candidate per published key */
	{
		$result = [];
		foreach ($this->declarations as $key => $candidates) { $result[$key] = $candidates[0]; }
		return $result;
	}
}

<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Authored member syntax and lexical provenance for a declaration such as trait Counter { ... }. */
final class ast_trait_declaration extends ast_node
{
	use ast_declaration;

	/** @var ast_type_reference[] Direct uses in authored order. */
	public readonly array $uses /** array<string,ast_type_reference> */;
	public readonly int $body_start /** uint32 */;
	/** @var ast_instance_field[] Authored fields, separate from the consumers' layout. */
	public readonly array $fields /** array<string,ast_instance_field> */;
	/** @var (ast_function_declaration|ast_literal_declaration)[] Authored members in lexical order. */
	public readonly array $members /** list<ast_function_declaration|ast_literal_declaration> */;

	/** @param ast_instance_field[] $fields @param (ast_function_declaration|ast_literal_declaration)[] $members @param ast_type_reference[] $uses */
	public function __construct(string $name, ?string $namespace, int $start /** uint32 */, int $end /** uint32 */,
		int $body_start /** uint32 */, array $fields /** array<string,ast_instance_field> */, array $members /** list<ast_function_declaration|ast_literal_declaration> */, array $uses /** array<string,ast_type_reference> */)
	{
		parent::__construct($start, $end);
		$this->short_name = $name;
		$this->namespace = $namespace;
		$this->body_start = $body_start;
		$this->fields = $fields;
		$this->members = $members;
		$this->uses = $uses;
		foreach ($uses as $use) $use->attach($this);
		ast_instance_field::attach_all($fields, $this);
		foreach ($members as $member) $member->attach($this);
	}
}

/** Sparse source-owned inventory. Only a source declaring or using a trait allocates one. */
final class trait_file_context
{
	/** @var ast_trait_declaration[] */
	public array $origins /** array<string,ast_trait_declaration> */ = [];
	/** @var ast_type_reference[] */
	public array $uses /** list<ast_type_reference> */ = [];
	/** @var array<string,array<string,ast_trait_declaration>> Direct spelling to reachable origins in composition order. */
	public array $bindings /** array<string,array<string,ast_trait_declaration>> */ = [];
}

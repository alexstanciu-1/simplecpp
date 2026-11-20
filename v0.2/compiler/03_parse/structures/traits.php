<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Authored member syntax and lexical provenance for a declaration such as trait Counter { ... }. */
final class ast_trait_declaration extends ast_node
{
	use ast_declaration;

	public readonly int $body_start /** uint32 */;
	/** @var ast_instance_field[] Authored fields, separate from the consumers' layout. */
	public readonly array $fields /** array<string,ast_instance_field> */;
	/** @var ast_function_declaration[] Authored methods in lexical order. */
	public readonly array $methods /** list<ast_function_declaration> */;

	/** @param ast_instance_field[] $fields @param ast_function_declaration[] $methods */
	public function __construct(string $name, ?string $namespace, int $start /** uint32 */, int $end /** uint32 */,
		int $body_start /** uint32 */, array $fields /** array<string,ast_instance_field> */, array $methods /** list<ast_function_declaration> */)
	{
		parent::__construct($start, $end);
		$this->short_name = $name;
		$this->namespace = $namespace;
		$this->body_start = $body_start;
		$this->fields = $fields;
		$this->methods = $methods;
		foreach ($fields as $field) {
			for (; $field !== null; $field = $field->duplicate_element()) $field->attach($this);
		}
		foreach ($methods as $method) $method->attach($this);
	}
}

/** Sparse source-owned inventory. Only a source declaring or using a trait allocates one. */
final class trait_file_context
{
	/** @var ast_trait_declaration[] */
	public array $origins /** array<string,ast_trait_declaration> */ = [];
	/** @var ast_type_reference[] */
	public array $uses /** list<ast_type_reference> */ = [];
	/** @var ast_trait_declaration[] Lexical spelling to the exact origin generation used in composition. */
	public array $bindings /** array<string,ast_trait_declaration> */ = [];
}

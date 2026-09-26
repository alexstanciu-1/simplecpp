<?php

/*
 * Role: specialized syntax records and their optional prepared facts.
 * Used by: Parser, Syntax_Nodes, Symbol_Collector and LLVM preparation/generation.
 * Flow: ast_node.payload() owns extra data; named children retain aliases of linked nodes.
 */
namespace scpp\compiler;

/** Specialized facts are attached by preparation and cleared locally. */
final class integer_literal_structure extends node_structure
{
	/** @ownership owner */
	private ?prepared_integer_literal $prepared_facts = null;

	public function preparation(): ?prepared_integer_literal
	{
		return $this->prepared_facts;
	}

	public function require_preparation(): prepared_integer_literal
	{
		return object_cast($this->prepared_facts, prepared_integer_literal::class);
	}

	public function set_preparation(prepared_integer_literal $facts): void
	{
		$this->prepared_facts = $facts;
	}

	public function clear_preparation(): void
	{
		$this->prepared_facts = null;
	}
}

/** Specialized facts are attached by preparation and cleared locally. */
final class variable_reference_structure extends node_structure
{
	/** @ownership owner */
	private ?prepared_variable_reference $prepared_facts = null;

	public function preparation(): ?prepared_variable_reference
	{
		return $this->prepared_facts;
	}

	public function require_preparation(): prepared_variable_reference
	{
		return object_cast($this->prepared_facts, prepared_variable_reference::class);
	}

	public function set_preparation(prepared_variable_reference $facts): void
	{
		$this->prepared_facts = $facts;
	}

	public function clear_preparation(): void
	{
		$this->prepared_facts = null;
	}
}

final class call_structure extends node_structure
{
	/** @storage.index token_list.tokens */
	public int $name_token_index;
	/** @storage.index token_list.tokens */
	public int $left_parenthesis_token_index;
	/** @storage.index token_list.tokens */
	public int $right_parenthesis_token_index;
	/**
	 * Arguments in source evaluation order. Ordered object list of child nodes.
	 * @storage.owner
	 */
	public Storage $arguments /** Storage<ast_node> */;
	/**
	 * Explicit type arguments. Ordered object list of child nodes.
	 * @storage.owner
	 */
	public Storage $template_arguments /** Storage<ast_node> */;

	public function __construct()
	{
		$this->arguments = new Storage /** Storage<ast_node> */();
		$this->template_arguments = new Storage /** Storage<ast_node> */();
	}
}

/** The body block references a file-owned local scope and an ordered statement list. */
final class function_structure extends node_structure
{
	/** Ordered formal names and declaration token indexes. */
	public array $template_parameters /** hash<int> */ = [];
	/** @storage.index token_list.tokens */
	public int $name_token_index;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $return_type;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $body;
	/**
	 * Parameter declarations in signature order. Ordered object list of child nodes.
	 * @storage.owner
	 */
	public Storage $parameters /** Storage<ast_node> */;

	public function __construct()
	{
		$this->parameters = new Storage /** Storage<ast_node> */();
	}
}

final class parameter_structure extends node_structure {
	public passing_mode $mode = passing_mode::value;
	/** Null for value parameters; present exactly when mode is reference.
	 * @storage.index token_list.tokens
	 */
	public ?int $reference_token_index = null;
	/** @storage.index token_list.tokens */
	public int $name_token_index;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $type_syntax;
}

/** Shared payload for a file body or a block that introduces a scope. */
final class block_structure extends node_structure
{
	/**
	 * Ordered object list of child nodes.
	 * @storage.owner
	 */
	public Storage $children /** Storage<ast_node> */;
	/**
	 * Non-owning lexical context: global scope or a scope owned by this parsed file.
	 * @reference.source model.global_scope
	 * @storage.reference parsed_file.scopes
	 * @reference.weak
	 */
	private scope $scope_reference /** weak<scope> */;

	public function __construct(scope $lexical_scope)
	{
		$this->scope_reference = $lexical_scope;
		$this->children = new Storage /** Storage<ast_node> */();
	}

	public function lexical_scope(): scope
	{
		return object_cast(weakref_get($this->scope_reference), scope::class);
	}
}

/** Binary and assignment expressions share operands; their node kinds retain the distinction. */
final class binary_structure extends node_structure {
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $left;
	/** @storage.index token_list.tokens */
	public int $operator_token_index;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $right;
}

/** An expression used as a statement owns its terminating semicolon here. */
final class expression_statement_structure extends node_structure {
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $expression;
	/** @storage.index token_list.tokens */
	public int $semicolon_token_index;
}

/** expression is null for a bare return; keyword and semicolon remain required. */
final class return_structure extends node_structure {
	/** @storage.index token_list.tokens */
	public int $keyword_token_index;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ?ast_node $expression = null;
	/** @storage.index token_list.tokens */
	public int $semicolon_token_index;
}

/** Preserve ambiguous binding syntax while declaration/assignment classification is refined.
 * type_syntax is absent on untyped writes; target is present only for indexed/field writes.
 * equals_token_index and value are either both present or both absent.
 * A typed declaration may omit its initializer; an untyped write requires a value.
 */
final class binding_structure extends node_structure
{
	/** Derived facts are absent before preparation and after cleanup. @ownership owner */
	private ?prepared_binding $prepared_facts = null;

	public binding_kind $syntax_kind = binding_kind::unresolved;
	/** @storage.index token_list.tokens */
	public int $name_token_index;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ?ast_node $type_syntax = null;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ?ast_node $target = null;
	/** @storage.index token_list.tokens */
	public ?int $equals_token_index = null;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ?ast_node $value = null;
	/** @storage.index token_list.tokens */
	public int $semicolon_token_index;

	public function preparation(): ?prepared_binding
	{
		return $this->prepared_facts;
	}

	public function require_preparation(): prepared_binding
	{
		return object_cast($this->prepared_facts, prepared_binding::class);
	}

	public function set_preparation(prepared_binding $facts): void
	{
		$this->prepared_facts = $facts;
	}

	public function clear_preparation(): void
	{
		$this->prepared_facts = null;
	}
}

/** Fixed extent is syntax until preparation checks and normalizes it. */
final class array_type_structure extends node_structure {
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $element_type;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $count;
}

final class array_literal_structure extends node_structure {
	/**
	 * Ordered object list of child nodes.
	 * @storage.owner
	 */
	public Storage $elements /** Storage<ast_node> */;

	public function __construct()
	{
		$this->elements = new Storage /** Storage<ast_node> */();
	}
}

final class index_structure extends node_structure {
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $base;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $index;
}

final class struct_structure extends node_structure
{
	/** @storage.index token_list.tokens */
	public int $name_token_index;
	/**
	 * Ordered field declarations. Ordered object list of child nodes.
	 * @storage.owner
	 */
	public Storage $fields /** Storage<ast_node> */;

	public function __construct()
	{
		$this->fields = new Storage /** Storage<ast_node> */();
	}
}

final class field_structure extends node_structure {
	/** @storage.index token_list.tokens */
	public int $name_token_index;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $type_syntax;
}

final class field_access_structure extends node_structure {
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $base;
	/** @storage.index token_list.tokens */
	public int $name_token_index;
}

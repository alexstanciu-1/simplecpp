<?php

/*
 * Role: node-specific structures and concrete AST node subclasses.
 * Used by: Parser, Syntax_Nodes, Symbol_Collector and LLVM preparation/generation.
 * Flow: ast_node.structure owns extra data; named children retain aliases of linked nodes.
 */
namespace scpp\compiler;

final class call_structure implements node_structure
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
final class function_structure implements node_structure
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

final class parameter_structure implements node_structure {
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
final class block_structure implements node_structure
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
	public scope $scope /** weak<scope> */;

	public function __construct()
	{
		$this->children = new Storage /** Storage<ast_node> */();
	}
}

/** Binary and assignment expressions share operands; their node kinds retain the distinction. */
final class binary_structure implements node_structure {
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
final class expression_statement_structure implements node_structure {
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $expression;
	/** @storage.index token_list.tokens */
	public int $semicolon_token_index;
}

/** expression is null for a bare return; keyword and semicolon remain required. */
final class return_structure implements node_structure {
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
final class binding_structure implements node_structure
{
	public binding_kind $classification = binding_kind::unresolved;
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
}

/** Fixed extent is syntax until preparation checks and normalizes it. */
final class array_type_structure implements node_structure {
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $element_type;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $count;
}

final class array_literal_structure implements node_structure {
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

final class index_structure implements node_structure {
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $base;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $index;
}

final class struct_structure implements node_structure
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

final class field_structure implements node_structure {
	/** @storage.index token_list.tokens */
	public int $name_token_index;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $type_syntax;
}

final class field_access_structure implements node_structure {
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $base;
	/** @storage.index token_list.tokens */
	public int $name_token_index;
}

/** Concrete struct_declaration syntax node. */
final class struct_declaration_node extends ast_node {
	public function __construct()
	{
		$this->kind = node_kind::struct_declaration;
	}
}

/** Concrete field_declaration syntax node. */
final class field_declaration_node extends ast_node {
	public function __construct()
	{
		$this->kind = node_kind::field_declaration;
	}
}

/** Concrete field_expression syntax node. */
final class field_expression_node extends ast_node {
	public function __construct()
	{
		$this->kind = node_kind::field_expression;
	}
}

/** Concrete file syntax node. */
final class file_node extends ast_node {
	public function __construct()
	{
		$this->kind = node_kind::file;
	}
}

/** Concrete function_declaration syntax node. */
final class function_declaration_node extends ast_node {
	public function __construct()
	{
		$this->kind = node_kind::function_declaration;
	}
}

/** Concrete parameter_declaration syntax node. */
final class parameter_declaration_node extends ast_node {
	public function __construct()
	{
		$this->kind = node_kind::parameter_declaration;
	}
}

/** Concrete block syntax node. */
final class block_node extends ast_node {
	public function __construct()
	{
		$this->kind = node_kind::block;
	}
}

/** Concrete identifier syntax node. */
final class identifier_node extends ast_node {
	public function __construct()
	{
		$this->kind = node_kind::identifier;
	}
}

/** Concrete punctuation syntax node. */
final class punctuation_node extends ast_node {
	public function __construct()
	{
		$this->kind = node_kind::punctuation;
	}
}

/** Concrete comment syntax node. */
final class comment_node extends ast_node {
	public function __construct()
	{
		$this->kind = node_kind::comment;
	}
}

/** Concrete array_type syntax node. */
final class array_type_node extends ast_node {
	public function __construct()
	{
		$this->kind = node_kind::array_type;
	}
}

/** Concrete array_literal syntax node. */
final class array_literal_node extends ast_node {
	public function __construct()
	{
		$this->kind = node_kind::array_literal;
	}
}

/** Concrete index_expression syntax node. */
final class index_expression_node extends ast_node {
	public function __construct()
	{
		$this->kind = node_kind::index_expression;
	}
}

/** Concrete integer_literal syntax node. */
final class integer_literal_node extends ast_node
{
	/** Derived facts owned by this node, absent before preparation or after cleanup. @ownership owner */
	public ?prepared_expression $prepared = null;

	public function __construct()
	{
		$this->kind = node_kind::integer_literal;
	}

	public function clear_preparation(): void
	{
		$this->prepared = null;
	}
}

/** Concrete variable_reference syntax node. */
final class variable_reference_node extends ast_node
{
	/** Derived facts owned by this node, absent before preparation or after cleanup. @ownership owner */
	public ?prepared_expression $prepared = null;

	public function __construct()
	{
		$this->kind = node_kind::variable_reference;
	}

	public function clear_preparation(): void
	{
		$this->prepared = null;
	}
}

/** Concrete binary_expression syntax node. */
final class binary_expression_node extends ast_node {
	public function __construct()
	{
		$this->kind = node_kind::binary_expression;
	}
}

/** Concrete assignment_expression syntax node. */
final class assignment_expression_node extends ast_node {
	public function __construct()
	{
		$this->kind = node_kind::assignment_expression;
	}
}

/** Concrete call_expression syntax node. */
final class call_expression_node extends ast_node {
	public function __construct()
	{
		$this->kind = node_kind::call_expression;
	}
}

/** Concrete expression_statement syntax node. */
final class expression_statement_node extends ast_node {
	public function __construct()
	{
		$this->kind = node_kind::expression_statement;
	}
}

/** Concrete return_statement syntax node. */
final class return_statement_node extends ast_node {
	public function __construct()
	{
		$this->kind = node_kind::return_statement;
	}
}

/** Concrete variable_binding_statement syntax node. */
final class variable_binding_statement_node extends ast_node
{
	/** Derived facts owned by this node, absent before preparation or after cleanup. @ownership owner */
	public ?prepared_binding $prepared = null;

	public function __construct()
	{
		$this->kind = node_kind::variable_binding_statement;
	}

	public function clear_preparation(): void
	{
		$this->prepared = null;
	}
}

<?php

/*
 * Role: retained syntax, specialization payloads and lexical scopes.
 * Used by: Parser, Symbol_Collector and preparation.
 */
namespace scpp\compiler;

enum node_category {
	case syntax;
	case expression;
	case statement;
}

enum node_kind
{
	case struct_declaration;
	case field_declaration;
	case field_expression;
	case file;
	case function_declaration;
	case parameter_declaration;
	case block;
	case identifier;
	case punctuation;
	case comment;
	case array_type;
	case array_literal;
	case index_expression;
	case integer_literal;
	case variable_reference;
	case binary_expression;
	case assignment_expression;
	case call_expression;
	case expression_statement;
	case return_statement;
	case variable_binding_statement;
}

enum binding_kind {
	case unresolved;
	case declaration;
	case assignment;
}

enum passing_mode {
	case value;
	case reference;
}

interface node_interface {
}

final class call_specialization implements node_interface
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
		$this->arguments = new Storage();
		$this->template_arguments = new Storage();
	}
}

final class ast_node
{
	/** @storage.index token_list.tokens */
	public int $token_index;
	/** @storage.boundary token_list.tokens */
	public int $end_token_index;
	public node_kind $kind;
	/** Concrete payload owned directly by this node; validated against kind.
	 * @ownership owner
	 */
	public ?node_interface $specialization = null;

}

final class scope
{
	/**
	 * Enclosing lexical scope; upward link does not own its parent.
	 * @reference.source model.global_scope
	 * @storage.reference parsed_file.scopes
	 * @reference.weak
	 */
	public ?scope $parent = null;
	public bool $function_boundary = false;
	/** Template name to owner-local slot. */
	public array $template_parameters /** hash<int> */ = [];
	/**
	 * References to file-local declaration entries.
	 * Secondary lookup index; references entries without owning declarations.
	 * @storage.reference collected_file.entries
	 * @reference.weak
	 */
	public array $variables /** hash<vector<collected_name>> */ = [];
	/**
	 * Secondary lookup index; references entries without owning declarations.
	 * @storage.reference collected_file.entries
	 * @reference.weak
	 */
	public array $functions /** hash<vector<collected_name>> */ = [];
	/**
	 * Secondary lookup index; references entries without owning declarations.
	 * @storage.reference collected_file.entries
	 * @reference.weak
	 */
	public array $types /** hash<vector<collected_name>> */ = [];
}

/** The body block references a file-owned local scope and an ordered statement list. */
final class function_specialization implements node_interface
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
		$this->parameters = new Storage();
	}
}

final class parameter_specialization implements node_interface {
	public passing_mode $mode = passing_mode::value;
	/** @storage.index token_list.tokens */
	public ?int $reference_token_index = null;
	/** @storage.index token_list.tokens */
	public int $name_token_index;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $type_syntax;
}

/** Shared payload for a file body or a block that introduces a scope. */
final class block_specialization implements node_interface
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
	public scope $scope;

	public function __construct()
	{
		$this->children = new Storage();
	}
}

/** Binary and assignment expressions share operands; their node kinds retain the distinction. */
final class binary_specialization implements node_interface {
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
final class expression_statement_specialization implements node_interface {
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $expression;
	/** @storage.index token_list.tokens */
	public int $semicolon_token_index;
}

final class return_specialization implements node_interface {
	/** @storage.index token_list.tokens */
	public int $keyword_token_index;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ?ast_node $expression = null;
	/** @storage.index token_list.tokens */
	public int $semicolon_token_index;
}

/** Preserve ambiguous binding syntax while declaration/assignment classification is refined. */
final class binding_specialization implements node_interface
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
final class array_type_specialization implements node_interface {
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $element_type;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $count;
}

final class array_literal_specialization implements node_interface
{
	/**
	 * Ordered object list of child nodes.
	 * @storage.owner
	 */
	public Storage $elements /** Storage<ast_node> */;

	public function __construct()
	{
		$this->elements = new Storage();
	}
}

final class index_specialization implements node_interface {
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $base;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $index;
}

final class struct_specialization implements node_interface
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
		$this->fields = new Storage();
	}
}

final class field_specialization implements node_interface {
	/** @storage.index token_list.tokens */
	public int $name_token_index;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $type_syntax;
}

final class field_access_specialization implements node_interface {
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $base;
	/** @storage.index token_list.tokens */
	public int $name_token_index;
}

/** Completed parsing output, sharing its source and collection with other model roots. */
final class parsed_file
{
	/** @storage.reference model.tokens */
	public token_list $tokens;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $root;
	/**
	 * Convenience link to this file's published collection result.
	 * @storage.reference model.collected_files
	 * @reference.weak
	 */
	public collected_file $collection;
	/** Local scopes; also owns the root scope when parsing without an external scope.
	 * @storage.owner
	 */
	public Storage $scopes /** Storage<scope> */;

	/** Initialize owned stores only; parser operations populate them. */
	public function __construct()
	{
		$this->scopes = new Storage();
	}
}

<?php

/*
 * Role: retained syntax roots, node kinds and lexical scopes.
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

/** Keep enum reflection beside its declaration for native lowering. */
final class Node_Kind_Name {
	public static function text(node_kind $kind): string
	{
		return enum_name($kind);
	}
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

/** Marker for the concrete payload owned by an AST node. */
interface node_specialization {
}

final class ast_node {
	/** @storage.index token_list.tokens */
	public int $token_index;
	/** @storage.boundary token_list.tokens */
	public int $end_token_index;
	public node_kind $kind;
	/** Concrete payload owned directly by this node; null only for payload-free kinds.
	 * Kind/payload agreement is validated by Syntax_Nodes.
	 * @ownership owner
	 */
	public ?node_specialization $specialization = null;
}

final class scope
{
	/**
	 * Enclosing lexical scope; upward link does not own its parent.
	 * @reference.source model.global_scope
	 * @storage.reference parsed_file.scopes
	 * @reference.weak
	 */
	public ?scope $parent /** weak<scope> */ = null;
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
		$this->scopes = new Storage /** Storage<scope> */();
	}
}

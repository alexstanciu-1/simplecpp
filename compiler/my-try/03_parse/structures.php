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

/** Marker for additional data owned by a concrete AST node. */
interface node_structure {
}

/** Common syntax header; links are private so native storage may later use positions. */
abstract class ast_node
{
	/** @storage.index token_list.tokens */
	public int $token_index /** uint32 */;
	/** Exclusive token boundary. @storage.boundary token_list.tokens */
	public int $end_token_index /** uint32 */;
	public node_kind $kind;
	/** Extra syntax data owned by this node. @ownership owner */
	public ?node_structure $structure = null;
	/** @reference.source parsed_file.root @reference.weak */
	private ?ast_node $parent_node /** weak<ast_node> */ = null;
	/** @reference.source parsed_file.root @reference.weak */
	private ?ast_node $previous_node /** weak<ast_node> */ = null;
	/** @ownership owner */
	private ?ast_node $next_node = null;
	/** @ownership owner */
	private ?ast_node $first_node = null;
	private int $position /** uint32 */ = 0;

	/** Initialize the compact header before the node is linked or published. */
	public function initialize(int $start, int $end, ?node_structure $data): void
	{
		if (($start < 0) || ($end < $start) || ($end > 4294967295)) {
			throw new \LogicException('AST token span exceeds uint32 bounds');
		}
		$this->token_index = $start;
		$this->end_token_index = $end;
		$this->position = 0;
		$this->structure = $data;
	}

	public function next(): ?ast_node
	{
		return $this->next_node;
	}

	public function prev(): ?ast_node
	{
		$previous = weakref_get($this->previous_node);
		if ($previous === null) {
			return null;
		}
		return object_cast($previous, ast_node::class);
	}

	public function parent(): ?ast_node
	{
		$owner = weakref_get($this->parent_node);
		if ($owner === null) {
			return null;
		}
		return object_cast($owner, ast_node::class);
	}

	public function first_child(): ?ast_node
	{
		return $this->first_node;
	}

	public function child_position(): int
	{
		return (int) $this->position;
	}

	public function has_children(): bool
	{
		return $this->first_node !== null;
	}

	/** Return a membership snapshot; traversal itself does not require a stored collection. */
	public function children(): Storage /** Storage<ast_node> */
	{
		$result /** Storage<ast_node> */ = new Storage();
		$current = $this->first_node;
		while ($current !== null) {
			$child = object_cast($current, ast_node::class);
			$result->append($child);
			$current = $child->next();
		}
		return $result;
	}

	/** Link a complete child list once; explicit handles avoid manufacturing ownership from $this. */
	public static function link_children(ast_node $owner, Storage $children /** Storage<ast_node> */): void
	{
		if ($owner->first_node !== null) {
			throw new \LogicException('AST children already linked');
		}
		if (q_count($children) > 4294967295) {
			throw new \LogicException('AST child positions exceed uint32 bounds');
		}
		// Validate before publishing any links, including duplicate membership.
		$seen /** hash<bool, shared<ast_node>> */ = new \SplObjectStorage /** hash<bool, shared<ast_node>> */();
		foreach ($children as $child)
		{
			if (($child === $owner) || ($child->parent() !== null)) {
				throw new \LogicException('AST child already belongs to a parent');
			}
			if (isset($seen[$child])) {
				throw new \LogicException('Duplicate AST child');
			}
			$ancestor = $owner->parent();
			while ($ancestor !== null) {
				$ancestor_node = object_cast($ancestor, ast_node::class);
				if ($ancestor_node === $child) {
					throw new \LogicException('AST links would form a cycle');
				}
				$ancestor = $ancestor_node->parent();
			}
			$seen[$child] = true;
		}
		$previous = $owner->first_node;
		$position = 0;
		foreach ($children as $child)
		{
			$child->parent_node = $owner;
			$child->position = $position;
			if ($previous === null) {
				$owner->first_node = $child;
			}
			else {
				$prior = object_cast($previous, ast_node::class);
				$prior->next_node = $child;
				$child->previous_node = $prior;
			}
			$previous = $child;
			$position++;
		}
	}
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

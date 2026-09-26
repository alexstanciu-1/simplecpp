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

/** Specialized data owns local cleanup; tree traversal belongs to preparation. */
abstract class node_structure {
	public function clear_preparation(): void
	{
		return;
	}
}

/** Common syntax header; links are private so native storage may later use positions. */
final class ast_node
{
	/** @storage.index token_list.tokens */
	public int $token_index /** uint32 */;
	/** Exclusive token boundary. @storage.boundary token_list.tokens */
	public int $end_token_index /** uint32 */;
	private node_kind $syntax_kind;
	/** Extra syntax data owned by this node. @ownership owner */
	private ?node_structure $payload_data = null;
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
	public function __construct(node_kind $kind, int $start, int $end, ?node_structure $data)
	{
		if (($start < 0) || ($end < $start) || ($end > 4294967295)) {
			throw new \LogicException('AST token span exceeds uint32 bounds');
		}
		$this->syntax_kind = $kind;
		$this->token_index = $start;
		$this->end_token_index = $end;
		$this->position = 0;
		$this->payload_data = $data;
	}

	public function kind(): node_kind
	{
		return $this->syntax_kind;
	}

	/** Access the specialization owned by this node. */
	public function payload(): ?node_structure
	{
		return $this->payload_data;
	}

	/** Delegate local fact cleanup to the specialization without traversing children. */
	public function clear_preparation(): void
	{
		if ($this->payload_data !== null) {
			$data /** node_structure */ = $this->payload_data;
			$data->clear_preparation();
		}
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
		return $previous;
	}

	public function parent(): ?ast_node
	{
		$owner = weakref_get($this->parent_node);
		if ($owner === null) {
			return null;
		}
		return $owner;
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
	public function children_snapshot(): Storage /** Storage<ast_node> */
	{
		$result /** Storage<ast_node> */ = new Storage();
		$current = $this->first_node;
		while ($current !== null) {
			$child /** ast_node */ = $current;
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
				$ancestor_node /** ast_node */ = $ancestor;
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
				$prior /** ast_node */ = $previous;
				$prior->next_node = $child;
				$child->previous_node = $prior;
			}
			$previous = $child;
			$position++;
		}
	}
}

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

	/** Publish a completed parse with initialized syntax, provenance and owned scopes. */
	public function __construct(token_list $tokens, ast_node $root, collected_file $collection, Storage $scopes /** Storage<scope> */)
	{
		$this->tokens = $tokens;
		$this->root = $root;
		$this->collection = $collection;
		$this->scopes = $scopes;
	}

	public function source_file(): file
	{
		return $this->tokens->file;
	}

	public function root_scope(): scope
	{
		return object_cast($this->root->payload(), block_structure::class)->lexical_scope();
	}
}

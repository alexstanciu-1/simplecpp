<?php

/*
 * Role: common syntax nodes, specialization operation contracts and parsed-file roots.
 * Used by: Parser, Symbol_Collector and preparation.
 */
namespace scpp\compiler;

/** Specializations route operations; process owners retain algorithms and invocation state.
 * Statement hooks process one node; expression hooks return one result.
 * Each process owns its child evaluation order. Hooks never retain the context.
 */
interface node_operations_i {
	public function prepare_statement(ast_node $node, preparation_context $context): void;
	public function prepare_expression(ast_node $node, preparation_context $context): prepared_expression;
	public function generate_cpp_statement(ast_node $node, cpp_generation_context $context): string;
	public function generate_cpp_expression(ast_node $node, cpp_generation_context $context): string;
}

/** Common structure contract; leaves have no children or local preparation to clear. */
abstract class node_structure implements node_operations_i
{
	public function attach_occurrence(collected_name $entry): void
	{
		throw new \LogicException('This syntax specialization does not collect a name');
	}

	public function occurrence(): collected_name
	{
		throw new \LogicException('This syntax specialization has no name occurrence');
	}

	public function append_children(Storage $result /** Storage<ast_node> */): void
	{
		return;
	}

	public function clear_preparation(): void
	{
		return;
	}

	public abstract function prepare_statement(ast_node $node, preparation_context $context): void;
	public abstract function prepare_expression(ast_node $node, preparation_context $context): prepared_expression;
	public abstract function generate_cpp_statement(ast_node $node, cpp_generation_context $context): string;
	public abstract function generate_cpp_expression(ast_node $node, cpp_generation_context $context): string;
}

/** Expression specializations must implement expression hooks; statement dispatch is invalid. */
abstract class expression_node_structure extends node_structure
{
	public function prepare_statement(ast_node $node, preparation_context $context): void
	{
		throw new \RuntimeException('S2S preparation does not support this statement yet');
	}

	public function generate_cpp_statement(ast_node $node, cpp_generation_context $context): string
	{
		throw new \RuntimeException('C++ statement emission is not implemented for this form');
	}
}

/** Statement specializations must implement statement hooks; expression dispatch is invalid. */
abstract class statement_node_structure extends node_structure
{
	public function prepare_expression(ast_node $node, preparation_context $context): prepared_expression
	{
		throw new \RuntimeException('S2S expression lowering is not implemented for this form');
	}

	public function generate_cpp_expression(ast_node $node, cpp_generation_context $context): string
	{
		throw new \RuntimeException('C++ expression emission is not implemented for this form');
	}
}

/** Explicit rejection base for forms outside the current preparation and C++ generation slice. */
abstract class unsupported_node_structure extends node_structure
{
	public function prepare_statement(ast_node $node, preparation_context $context): void
	{
		throw new \RuntimeException('S2S preparation does not support this statement yet');
	}

	public function prepare_expression(ast_node $node, preparation_context $context): prepared_expression
	{
		throw new \RuntimeException('S2S expression lowering is not implemented for this form');
	}

	public function generate_cpp_statement(ast_node $node, cpp_generation_context $context): string
	{
		throw new \RuntimeException('C++ statement emission is not implemented for this form');
	}

	public function generate_cpp_expression(ast_node $node, cpp_generation_context $context): string
	{
		throw new \RuntimeException('C++ expression emission is not implemented for this form');
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
	private node_structure $payload_data;
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
	public function __construct(node_kind $kind, int $start, int $end, node_structure $data)
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
	public function payload(): node_structure
	{
		return $this->payload_data;
	}

	/** Delegate local fact cleanup to the specialization without traversing children. */
	public function clear_preparation(): void
	{
		$this->payload_data->clear_preparation();
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

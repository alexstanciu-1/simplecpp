<?php

/* Typed syntax model; workers own compilation algorithms and traversal. */
namespace scpp\compiler;

enum assignment_operator {
	case assign;
}

/** Inspection contract; compilation-step contracts can be added independently. */
interface ast_node_i
{
	public function kind(): node_kind;
	public function prepare(preparation_worker_i $worker): void;
	public function generate_cpp(cpp_generation_worker_i $worker): string;
	public function maintain(node_maintenance_worker_i $worker): void;
	/**
	 * Iterate direct children; do not mutate membership during traversal.
	 */
	public function children(): child_iterator_i;
}

/** Shared leaf behavior only. This base deliberately has no properties. */
abstract class ast_node implements ast_node_i
{
	public abstract function set_span(int $first, int $end): void;
	public abstract function start_token(): int;
	public abstract function end_token(): int;

	public function optional_occurrence(): ?collected_name
	{
		return null;
	}

	public function occurrence(): collected_name
	{
		return $this->optional_occurrence();
	}

	public function attach_occurrence(collected_name $entry): void
	{
		throw new \LogicException('This syntax does not collect names');
	}

	public function clear_preparation(): void
	{
	}

	public abstract function kind(): node_kind;
	public abstract function maintain(node_maintenance_worker_i $worker): void;

	/** Trivia and semantic forms without active preparation support fail explicitly. */
	public function prepare(preparation_worker_i $worker): void
	{
		throw new \RuntimeException('Preparation is not supported for this node');
	}

	/** Unsupported generation fails rather than silently emitting an empty fragment. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		throw new \RuntimeException('C++ generation is not supported for this node');
	}

	/**
	 * Leaves expose an empty iterator; compilation does not use this inspection API.
	 */
	public function children(): child_iterator_i
	{
		return new empty_children_iterator();
	}
}

/** Type syntax is distinct from executable expressions and declaration names. */
abstract class type_node extends ast_node
{
	/** Resolved canonical type identity, attached by preparation in its owning context. */
	public abstract function require_preparation(): type_definition;
}

/** An expression produces a value; concrete forms define their own operands. */
abstract class expression_node extends ast_node
{
	/** Concrete supported expressions narrow this return to their specialized facts. */
	public function require_preparation(): prepared_expression
	{
		throw new \RuntimeException('Prepared expression facts are not supported for this node');
	}
}

/** Variable, field and index forms can occur as assignment targets. */
abstract class assignable_expression_node extends expression_node
{
}

/** Executable-body items: explicit local declarations, expression statements, returns and blocks. */
abstract class statement_node extends ast_node
{
}

/** File-level declarations only in the current grammar: functions and structs. */
abstract class declaration_node extends ast_node
{
}

/** Inspection-only syntax; punctuation/comments are not executable statements. */
abstract class trivia_node extends ast_node
{
}

/**
 * Source-backed nodes share a half-open span; the abstract bases retain no data.
 * The owning parsed-file/worker context selects and retains the token snapshot.
 * Retained indexes address appended storage until deferred token cleanup.
 */
trait Node_Source_Span
{
	/** Common access preserves the property-free base while spans remain concrete data. */
	public function set_span(int $first, int $end): void
	{
		if (($first < 0) || ($end < $first) || ($end > 4294967295)) {
			throw new \LogicException('AST token span exceeds uint32 bounds');
		}
		$this->first_token_index = $first;
		$this->end_token_index = $end;
	}

	public function start_token(): int
	{
		return (int)$this->first_token_index;
	}

	public function end_token(): int
	{
		return (int)$this->end_token_index;
	}

	/** Inclusive start in the owning token snapshot. */
	public int $first_token_index /** uint32 */;
	/** Exclusive end: token count is end - first, including zero for an empty span. */
	public int $end_token_index /** uint32 */;
}

/**
 * Only nodes that participate in collection carry this relationship.
 * Retained nodes keep their existing occurrence, including a deleted tombstone.
 * Only parser reconciliation may reactivate that tombstone before retirement:
 * keep identity, mark changed/active and schedule affected work before preparation.
 * A deleted tombstone is not a retired node. Retired nodes must not re-enter
 * the active graph; an expired observer is not permission to recycle a node.
 * The occurrence stores the enclosing collection/lookup scope; this is distinct
 * from a scope introduced by the node itself. Do not duplicate that scope link.
 * No historical attachment flag is kept, so this trait cannot detect that misuse
 * after the previous occurrence has expired.
 */
trait Collected_Occurrence
{
	private ?collected_name $collected_occurrence /** weak<collected_name> */ = null;

	public function optional_occurrence(): ?collected_name
	{
		return \weakref_get($this->collected_occurrence);
	}

	public function occurrence(): collected_name
	{
		return \weakref_get($this->collected_occurrence);
	}

	/** Reject replacement of a live occurrence; retirement discipline belongs to the lifecycle. */
	public function attach_occurrence(collected_name $entry): void
	{
		if ($this->optional_occurrence() !== null) {
			throw new \LogicException('Syntax occurrence is already attached');
		}
		$this->collected_occurrence = $entry;
	}
}

/** Existing method grouping; each concrete node supplies its own typed facts field. */
trait Preparation_Facts
{
	public function preparation(): ?object /** @field-type prepared_facts */
	{
		return $this->prepared_facts;
	}

	public function set_preparation(object /** @field-type prepared_facts */ $facts): void
	{
		$this->prepared_facts = $facts;
	}

	public function clear_preparation(): void
	{
		$this->prepared_facts = null;
	}
}

/**
 * Typed preparation dispatch contract, not a worker implementation. The worker
 * carries the active preparation context, owns ordering/dependency decisions and
 * attaches facts to concrete nodes. Returning void keeps this shared entry point
 * independent of the specialized fact type; typed accessors expose those facts.
 * Signature and executable-body work are scheduled independently. Type syntax,
 * parameters and fields are processed in their owning declaration/body context.
 * Pending native migration must adapt existing workers to these typed signatures;
 * these declarations do not claim that production workers implement this interface.
 */
interface preparation_worker_i
{
	public function prepare_file(file_node $node): void;
	public function prepare_function_body(function_body_node $node): void;
	public function prepare_block(block_node $node): void;
	public function prepare_named_type(named_type_node $node): void;
	public function prepare_integer_literal(integer_literal_node $node): void;
	public function prepare_float_literal(float_literal_node $node): void;
	public function prepare_boolean_literal(boolean_literal_node $node): void;
	public function prepare_variable_reference(variable_reference_node $node): void;
	public function prepare_call(call_node $node): void;
	public function prepare_function_signature(function_node $node): void;
	public function prepare_parameter(parameter_node $node): void;
	public function prepare_expression_statement(expression_statement_node $node): void;
	public function prepare_return(return_node $node): void;
	public function prepare_variable_declaration(variable_declaration_node $node): void;
	public function prepare_assignment(assignment_expression_node $node): void;
	public function prepare_array_type(array_type_node $node): void;
	public function prepare_struct(struct_node $node): void;
	public function prepare_field(field_node $node): void;
	public function prepare_field_access(field_access_node $node): void;
}

/**
 * Nodes enumerate their named owning syntax fields; workers supply the operation.
 * enter acts on one node. edge may recurse through maintain or only inspect the
 * immediate relationship. token_index preserves or remaps additional token sites.
 * No inspection iterators, semantic references, parent links or scopes are followed.
 * Workers are transient and never stored on nodes. Lifecycle owners initiate work.
 */
interface node_maintenance_worker_i
{
	public function enter(ast_node $node): void;
	public function edge(ast_node $parent, ast_node $child): void;
	public function token_index(int $index): int;
}

/**
 * Typed C++ fragment dispatch. The worker retains rendering context and traverses
 * typed fields; source type nodes are consumed through their resolved type facts.
 * Function signature and body fragments remain independent. Existing C++ caches,
 * include requirements and pending generation queues remain backend-owned.
 * Returning text here is not permission to settle pending work before complete
 * fragment/include publication succeeds. This is not a new emission layout.
 */
interface cpp_generation_worker_i
{
	public function generate_file(file_node $node): string;
	public function generate_function_body(function_body_node $node): string;
	public function generate_block(block_node $node): string;
	public function generate_integer_literal(integer_literal_node $node): string;
	public function generate_float_literal(float_literal_node $node): string;
	public function generate_boolean_literal(boolean_literal_node $node): string;
	public function generate_variable_reference(variable_reference_node $node): string;
	public function generate_call(call_node $node): string;
	public function generate_function_signature(function_node $node): string;
	public function generate_parameter(parameter_node $node): string;
	public function generate_assignment_expression(assignment_expression_node $node): string;
	public function generate_expression_statement(expression_statement_node $node): string;
	public function generate_return(return_node $node): string;
	public function generate_variable_declaration(variable_declaration_node $node): string;
	public function generate_struct(struct_node $node): string;
	public function generate_field(field_node $node): string;
	public function generate_field_access(field_access_node $node): string;
}

<?php

/* Typed syntax model; workers own compilation algorithms and traversal. */
namespace scpp\compiler;

enum assignment_operator {
	case assign;
}

/** Shared structural contract; processing dispatch belongs to the consuming stage. */
interface ast_node_i
{
	public function kind(): node_kind;
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
	public abstract function require_preparation(): canonical_type_use;
}

/** An expression produces a value; concrete forms define their own operands. */
abstract class expression_node extends ast_node
{
	/** Shared expression view; specialized facts use distinctly named concrete accessors. */
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

/** Shared ordered code body; concrete functions add only executable-unit state. */
abstract class statement_body_node extends statement_node
{
	use Node_Source_Span;

	/** @storage.owner */
	public Storage $statements /** Storage<statement_node> */;

	public function __construct()
	{
		$this->statements = new Storage /** Storage<statement_node> */();
	}

	public function kind(): node_kind
	{
		return node_kind::block;
	}

	public function children(): child_iterator_i
	{
		return new storage_children_iterator(new Storage_Cursor /** Storage_Cursor<ast_node> */($this->statements));
	}

	/** Expose owning statements once; maintenance workers choose whether to recurse. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$items /** Storage<statement_node> */ = $this->statements;
		foreach ($items as $child) {
			$worker->edge($this, $child);
		}
	}
}

/** Structured break targets are distinct from constructs that also accept continue. */
abstract class breakable_node extends statement_node
{
}

/** Shared loop body and facts; concrete forms own their header syntax. */
abstract class loop_node extends breakable_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	/** @ownership owner */
	public block_node $body;
	private ?prepared_loop $prepared_facts = null;

	public function require_preparation(): prepared_loop
	{
		return $this->prepared_facts;
	}

	public function body_runs_first(): bool
	{
		return false;
	}
}

/** Bare transfers share selected-target facts; stages own preparation and emission. */
abstract class control_transfer_node extends statement_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	private ?prepared_control_transfer $prepared_facts = null;

	public abstract function transfer_kind(): control_transfer_kind;

	public function require_preparation(): prepared_control_transfer
	{
		return $this->prepared_facts;
	}

	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
	}
}

/** Named source declarations, whether namespace-owned or type-owned. */
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

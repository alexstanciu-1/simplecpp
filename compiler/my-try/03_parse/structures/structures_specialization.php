<?php

/* Typed syntax model; workers own compilation algorithms and traversal. */
namespace scpp\compiler;

/** A source file owns occurrences, not a merged namespace or executable body. */
final class file_node extends ast_node
{
	use Node_Source_Span;

	public Storage $namespace_bodies /** Storage<namespace_body_node> */;
	/** Removed occurrences survive until coordinator-ordered semantic retirement. */
	public Storage $retired_namespace_bodies /** Storage<namespace_body_node> */;

	/** Global source starts with one implicit occurrence through the same body inventory. */
	public function __construct(scope $initial_scope)
	{
		$this->namespace_bodies = new Storage /** Storage<namespace_body_node> */();
		$this->retired_namespace_bodies = new Storage /** Storage<namespace_body_node> */();
		$bodies /** Storage<namespace_body_node> */ = $this->namespace_bodies;
		$bodies->append(new namespace_body_node($initial_scope));
	}

	/** Bounded standalone/global adapters must not silently select from several bodies. */
	public function single_body(): namespace_body_node
	{
		$bodies /** Storage<namespace_body_node> */ = $this->namespace_bodies;
		if (q_count($bodies) !== 1) {
			throw new \LogicException('This operation requires exactly one source namespace body');
		}
		foreach ($bodies as $body) {
			return $body;
		}
		throw new \LogicException('Missing source namespace body');
	}

	public function kind(): node_kind
	{
		return node_kind::file;
	}

	public function children(): child_iterator_i
	{
		return new storage_children_iterator(new Storage_Cursor /** Storage_Cursor<ast_node> */($this->namespace_bodies));
	}

	/** Active syntax traversal excludes the separately retained retirement inventory. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$bodies /** Storage<namespace_body_node> */ = $this->namespace_bodies;
		foreach ($bodies as $body) {
			$worker->edge($this, $body);
		}
	}
}

/** Source restriction; plain imports may expose each supported symbol kind. */
enum import_kind {
	case symbol;
	case function_name;
	case constant;
}

/** One normalized binding with its item span; groups never create definitions. */
final class import_node extends ast_node implements preparation_lookup_candidate_i
{
	use Node_Source_Span;
	public import_kind $restriction = import_kind::symbol;
	public string $target;
	public string $alias;
	public function kind(): node_kind { return node_kind::import_binding; }
	public function maintain(node_maintenance_worker_i $worker): void { $worker->enter($this); }
}

/** One source occurrence, with its own declaration/import environment and executable body. */
final class namespace_body_node extends ast_node
{
	use Node_Source_Span;

	public Storage $declarations /** Storage<declaration_node> */;
	public Storage $imports /** Storage<import_node> */;
	/** Persistent parser handoff; analysis consumes only after successful preparation. */
	public bool $imports_changed = true;
	public function_body_node $body;
	/**
	 * Required observer; the parser retains this scope in parsed_file.scopes.
	 * @storage.reference parsed_file.scopes
	 * @reference.weak
	 */
	private scope $body_scope_reference /** weak<scope> */;

	/** Initialize an empty source occurrence before parsing publishes declarations. */
	public function __construct(scope $body_scope)
	{
		$this->body_scope_reference = $body_scope;
		$this->declarations = new Storage /** Storage<declaration_node> */();
		$this->imports = new Storage /** Storage<import_node> */();
		$this->body = new function_body_node($body_scope);
		$this->body->set_span(0, 0);
		$this->set_span(0, 0);
	}

	/** Required lexical context, independent of syntax ownership. */
	public function body_scope(): scope
	{
		return \weakref_get($this->body_scope_reference);
	}

	public function kind(): node_kind
	{
		return node_kind::namespace_body;
	}

	/**
	 * Lazily inspect imports, declarations and body; spans retain original source locations.
	 */
	public function children(): child_iterator_i
	{
		return new namespace_body_children_iterator($this);
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$items /** Storage<declaration_node> */ = $this->declarations;
		$imports /** Storage<import_node> */ = $this->imports;
		foreach ($imports as $binding) { $worker->edge($this, $binding); }
		foreach ($items as $child) {
			$worker->edge($this, $child);
		}
		$worker->edge($this, $this->body);
	}
}

/**
 * Independently prepared executable unit, shared by files and declared functions.
 * No synthetic function name/signature; the owning process selects the context.
 * Only explicit local variable declarations and executable statements belong here.
 * Functions and structs are declaration_node, not statement_node, so cannot be
 * members of statements. Parameters and fields likewise belong to their own owners.
 * Future local declaration support must extend this contract explicitly.
 */
final class function_body_node extends statement_body_node
{
	/**
	 * Required observer; the parser retains this scope in parsed_file.scopes.
	 * @storage.reference parsed_file.scopes
	 * @reference.weak
	 */
	private scope $local_scope_reference /** weak<scope> */;
	/**
	 * Unconsumed source change for this executable unit, not an expression fact.
	 * Parsing accumulates text/structure changes across retries; successful preparation settles this
	 * flag. Scheduling also checks body_preparation state for dependency changes/retries.
	 * This flag never gates backend work and is not a duplicate change_status.
	 */
	public bool $syntax_changed = true;
	/**
	 * Retained work/dependency state for this body only, absent before scheduling.
	 * @ownership owner
	 * Signature/declaration work remains on collected occurrences. This is not
	 * Preparation_Facts and does not supply preparation()/require_preparation().
	 */
	private ?body_work $body_preparation = null;

	public function __construct(scope $local_scope)
	{
		parent::__construct();
		$this->local_scope_reference = $local_scope;
	}

	/** Required lexical context, independent of syntax ownership. */
	public function local_scope(): scope
	{
		return \weakref_get($this->local_scope_reference);
	}

	/** One canonical body schedule/dependency identity, shared with existing work lists. */
	public function work(): ?body_work
	{
		return $this->body_preparation;
	}

	/** Attach only after scheduling or an explicit transfer from a replaced body. */
	public function attach_work(body_work $work): void
	{
		if (($this->body_preparation !== null) && ($this->body_preparation !== $work)) {
			throw new \LogicException('Body already has a different preparation owner');
		}
		$this->body_preparation = $work;
	}

	/**
	 * Representation operation only. Lifecycle must notify consumers and detach
	 * dependencies before retirement, or transfer this exact record to the new body.
	 * Clearing this slot alone is not dependency cleanup or successful preparation.
	 */
	public function detach_work(): ?body_work
	{
		$work = $this->body_preparation;
		$this->body_preparation = null;
		return $work;
	}

	public function kind(): node_kind
	{
		return node_kind::function_body;
	}
}

/** Nested grouping only; it does not own an independent preparation work item. */
final class block_node extends statement_body_node
{
}

/** One owned arm in a conditional chain; only its initial arm is a sequence member. */
final class if_node extends statement_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	public if_arm_kind $arm_kind;
	public ?expression_node $condition = null;
	/** @ownership owner */
	public block_node $body;
	/** @ownership owner */
	public ?if_node $next_arm = null;
	private ?prepared_condition $prepared_facts = null;

	public function __construct(if_arm_kind $arm_kind)
	{
		$this->arm_kind = $arm_kind;
	}

	public function kind(): node_kind
	{
		return node_kind::conditional;
	}

	/** Inspect named conditional edges lazily, preserving their grammar order. */
	public function children(): child_iterator_i
	{
		return new if_children_iterator($this);
	}

	public function require_preparation(): prepared_condition
	{
		return $this->prepared_facts;
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		if ($this->condition !== null) {
			$worker->edge($this, $this->condition);
		}
		$worker->edge($this, $this->body);
		if ($this->next_arm !== null) {
			$worker->edge($this, $this->next_arm);
		}
	}
}

/** A type-reference spelling; declared names remain on their declaration nodes. */
final class named_type_node extends type_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	/** Canonical type alias; clearing syntax facts must not destroy or mutate the type definition. */
	private ?canonical_type_use $prepared_facts = null;
	use Collected_Occurrence;

	/** Stored reference spelling, independent of the token buffer lifetime. */
	public string $name;

	/** Parsing triggers collection here; registration and reconciliation belong to the collector. */
	public function collect(Symbol_Collector $collector, scope $scope, int $index): void
	{
		$collector->collect_type_reference($this, $scope, $index);
	}

	public function kind(): node_kind
	{
		return node_kind::named_type;
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
	}

	public function require_preparation(): canonical_type_use
	{
		return $this->prepared_facts;
	}
}

final class punctuation_node extends trivia_node
{
	use Node_Source_Span;

	public function kind(): node_kind
	{
		return node_kind::punctuation;
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
	}
}

final class comment_node extends trivia_node
{
	use Node_Source_Span;

	public function kind(): node_kind
	{
		return node_kind::comment;
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
	}
}

final class nullable_type_node extends type_node
{
	use Node_Source_Span;
	use Preparation_Facts;
	private ?canonical_type_use $prepared_facts = null;
	public type_node $payload;
	public function kind(): node_kind { return node_kind::nullable_type; }
	public function require_preparation(): canonical_type_use { return $this->prepared_facts; }
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->payload);
	}
	public function children(): child_iterator_i { return new nullable_type_children_iterator($this); }
}

final class null_literal_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;
	private ?prepared_null_literal $prepared_facts = null;
	public function kind(): node_kind { return node_kind::null_literal; }
	public function require_preparation(): prepared_expression { return $this->require_null_preparation(); }
	public function require_null_preparation(): prepared_null_literal { return $this->prepared_facts; }
	public function maintain(node_maintenance_worker_i $worker): void { $worker->enter($this); }
}

final class integer_literal_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	private ?prepared_integer_literal $prepared_facts = null;

	public function kind(): node_kind
	{
		return node_kind::integer_literal;
	}

	public function require_preparation(): prepared_expression
	{
		return $this->require_integer_literal_preparation();
	}

	public function require_integer_literal_preparation(): prepared_integer_literal
	{
		return $this->prepared_facts;
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
	}
}

final class float_literal_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	private ?prepared_float_literal $prepared_facts = null;

	public function kind(): node_kind
	{
		return node_kind::float_literal;
	}

	public function require_preparation(): prepared_expression
	{
		return $this->require_float_literal_preparation();
	}

	public function require_float_literal_preparation(): prepared_float_literal
	{
		return $this->prepared_facts;
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
	}
}

final class boolean_literal_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	private ?prepared_boolean_literal $prepared_facts = null;

	public bool $value;

	public function kind(): node_kind
	{
		return node_kind::boolean_literal;
	}

	public function require_preparation(): prepared_expression
	{
		return $this->require_boolean_literal_preparation();
	}

	public function require_boolean_literal_preparation(): prepared_boolean_literal
	{
		return $this->prepared_facts;
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
	}
}

final class string_literal_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	private ?prepared_string_literal $prepared_facts = null;

	/** Bare property names normalize to quoted spelling without changing source tokens. */
	public function literal_spelling(token_list $tokens): string
	{
		$text = $tokens->text_at($this->start_token());
		if (Source_Text::identifier($text)) {
			return "'" . $text . "'";
		}
		return $text;
	}

	public function kind(): node_kind
	{
		return node_kind::string_literal;
	}

	public function require_preparation(): prepared_expression
	{
		return $this->require_string_literal_preparation();
	}

	public function require_string_literal_preparation(): prepared_string_literal
	{
		return $this->prepared_facts;
	}

	/** Offer this leaf node to maintenance without inventing spelling-specific children. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
	}
}

/** Ordered source parts; there are no synthetic concatenation operators or cast nodes. */
final class interpolated_string_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	private ?prepared_interpolated_string $prepared_facts = null;
	public Storage $parts /** Storage<interpolation_part_node> */;

	public function __construct()
	{
		$this->parts = new Storage /** Storage<interpolation_part_node> */();
	}

	public function kind(): node_kind
	{
		return node_kind::interpolated_string;
	}

	public function children(): child_iterator_i
	{
		return new storage_children_iterator(new Storage_Cursor /** Storage_Cursor<ast_node> */($this->parts));
	}

	public function require_preparation(): prepared_expression
	{
		return $this->prepared_facts;
	}

	/** Traverse owned parts in source order, preserving their precise token-relative ranges. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$parts /** Storage<interpolation_part_node> */ = $this->parts;
		foreach ($parts as $part) {
			$worker->edge($this, $part);
		}
	}
}

/** Only literal bytes and variable insertions belong to the bounded interpolation grammar. */
abstract class interpolation_part_node extends ast_node
{
}

/** Source range lies within the node's single quoted token, so compaction cannot invalidate it. */
trait Interpolation_Source_Range
{
	public int $byte_offset;
	public int $byte_length;
}

final class interpolation_text_node extends interpolation_part_node
{
	use Node_Source_Span;
	use Interpolation_Source_Range;
	use Preparation_Facts;

	private ?prepared_string_literal $prepared_facts = null;

	public function kind(): node_kind
	{
		return node_kind::interpolation_text;
	}

	public function require_preparation(): prepared_string_literal
	{
		return $this->prepared_facts;
	}

	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
	}
}

final class interpolation_value_node extends interpolation_part_node
{
	use Node_Source_Span;
	use Interpolation_Source_Range;
	use Preparation_Facts;

	private ?prepared_interpolation_value $prepared_facts = null;
	public variable_reference_node $expression;

	public function kind(): node_kind
	{
		return node_kind::interpolation_value;
	}

	public function children(): child_iterator_i
	{
		return new interpolation_value_children_iterator($this);
	}

	public function require_preparation(): prepared_interpolation_value
	{
		return $this->prepared_facts;
	}

	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->expression);
	}
}

final class variable_reference_node extends assignable_expression_node
{
	use Node_Source_Span;
	use Collected_Occurrence;
	use Preparation_Facts;

	private ?prepared_variable_reference $prepared_facts = null;

	/** Stored reference spelling, independent of the token buffer lifetime. */
	public string $name;

	/** Parsing triggers collection here; registration and reconciliation belong to the collector. */
	public function collect(Symbol_Collector $collector, scope $scope, int $index): void
	{
		$collector->collect_variable_reference($this, $scope, $index);
	}

	/** A plain write has a distinct unresolved role; member/index bases are ordinary reads. */
	public function collect_write(Symbol_Collector $collector, scope $scope, int $index): void
	{
		$collector->collect_variable_write($this, $scope, $index);
	}

	public function kind(): node_kind
	{
		return node_kind::variable_reference;
	}

	public function require_preparation(): prepared_expression
	{
		return $this->require_variable_reference_preparation();
	}

	public function require_variable_reference_preparation(): prepared_variable_reference
	{
		return $this->prepared_facts;
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
	}
}

/** A named immutable value is an expression, never an assignment target. */
final class constant_reference_node extends expression_node
{
	use Node_Source_Span;
	use Collected_Occurrence;
	use Preparation_Facts;

	private ?prepared_constant_reference $prepared_facts = null;
	public string $name;

	/** Parsing records the occurrence; semantic lookup remains preparation-owned. */
	public function collect(Symbol_Collector $collector, scope $scope, int $index): void
	{
		$collector->collect_constant_reference($this, $scope, $index);
	}

	public function kind(): node_kind
	{
		return node_kind::constant_reference;
	}

	public function require_preparation(): prepared_expression
	{
		return $this->require_constant_reference_preparation();
	}

	public function require_constant_reference_preparation(): prepared_constant_reference
	{
		return $this->prepared_facts;
	}

	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
	}
}

final class call_node extends expression_node
{
	use Node_Source_Span;
	use Collected_Occurrence;
	use Preparation_Facts;

	private ?prepared_call $prepared_facts = null;

	/** Stored name used by collection/resolution and generation; never recovered from tokens. */
	public string $name;
	/** Absent for free calls; fixed instance calls own their receiver syntax. */
	public ?expression_node $receiver = null;
	/** Current frontend accepts only named explicit template type arguments. */
	public Storage $template_arguments /** Storage<named_type_node> */;
	public Storage $arguments /** Storage<expression_node> */;

	/** Allocate only the concrete node's ordered syntax collections. */
	public function __construct()
	{
		$this->template_arguments = new Storage /** Storage<named_type_node> */();
		$this->arguments = new Storage /** Storage<expression_node> */();
	}

	/** Parsing triggers collection here; registration and reconciliation belong to the collector. */
	public function collect(Symbol_Collector $collector, scope $scope, int $index): void
	{
		$collector->collect_call_reference($this, $scope, $index);
	}

	public function kind(): node_kind
	{
		return node_kind::call_expression;
	}

	/**
	 * Create an independent iterator retaining this node for lazy inspection.
	 */
	public function children(): child_iterator_i
	{
		return new call_children_iterator($this);
	}

	public function require_preparation(): prepared_expression
	{
		return $this->require_call_preparation();
	}

	public function require_call_preparation(): prepared_call
	{
		return $this->prepared_facts;
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		if ($this->receiver !== null) { $worker->edge($this, $this->receiver); }
		$template_arguments /** Storage<named_type_node> */ = $this->template_arguments;
		foreach ($template_arguments as $template_argument) {
			$worker->edge($this, $template_argument);
		}
		$arguments /** Storage<expression_node> */ = $this->arguments;
		foreach ($arguments as $argument) {
			$worker->edge($this, $argument);
		}
	}
}


final class parameter_node extends ast_node
{
	use Node_Source_Span;
	use Collected_Occurrence;
	use Preparation_Facts;

	private ?prepared_parameter $prepared_facts = null;

	public ?type_node $type_syntax = null;
	/** Contextual set: input; its type comes from a separately declared property. */
	public bool $property_backed = false;

	public function require_type_syntax(): type_node { return $this->type_syntax; }
	/** Stored name used by collection/resolution and generation; never recovered from tokens. */
	public string $name;
	public passing_mode $mode = passing_mode::value;
	public storage_access $access = storage_access::writable;
	/** Optional declaration-owned expression; absence makes a fixed parameter required. */
	public ?expression_node $default_value = null;
	/** Independent of reference/value passing mode; type_syntax is the pack element type. */
	public bool $variadic = false;

	/** Parsing triggers collection here; registration and reconciliation belong to the collector. */
	public function collect(Symbol_Collector $collector, scope $scope, int $index): void
	{
		$collector->collect_parameter($this, $scope, $index);
	}

	public function kind(): node_kind
	{
		return node_kind::parameter_declaration;
	}

	/**
	 * Create an independent iterator retaining this node for lazy inspection.
	 */
	public function children(): child_iterator_i
	{
		return new parameter_children_iterator($this);
	}

	public function require_preparation(): prepared_parameter
	{
		return $this->prepared_facts;
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		if ($this->type_syntax !== null) {
			$worker->edge($this, $this->type_syntax);
		}
		if ($this->default_value !== null) {
			$worker->edge($this, $this->default_value);
		}
	}
}

final class compound_assignment_expression_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	private ?prepared_compound_assignment_expression $prepared_facts = null;

	public expression_node $target;
	public int $operator_token_index;
	public expression_node $value;

	public function kind(): node_kind
	{
		return node_kind::compound_assignment_expression;
	}

	/**
	 * Create an independent iterator retaining this node for lazy inspection.
	 */
	public function children(): child_iterator_i
	{
		return new compound_assignment_expression_children_iterator($this);
	}

	public function require_preparation(): prepared_expression
	{
		return $this->require_compound_assignment_preparation();
	}

	public function require_compound_assignment_preparation(): prepared_compound_assignment_expression
	{
		return $this->prepared_facts;
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$this->operator_token_index = $worker->token_index($this->operator_token_index);
		$worker->enter($this);
		$worker->edge($this, $this->target);
		$worker->edge($this, $this->value);
	}
}

final class mutation_expression_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	private ?prepared_mutation_expression $prepared_facts = null;

	public expression_node $target;
	public int $operator_token_index;
	public bool $postfix = false;

	public function kind(): node_kind
	{
		return node_kind::mutation_expression;
	}

	/**
	 * Create an independent iterator retaining this node for lazy inspection.
	 */
	public function children(): child_iterator_i
	{
		return new mutation_expression_children_iterator($this);
	}

	public function require_preparation(): prepared_expression
	{
		return $this->require_mutation_preparation();
	}

	public function require_mutation_preparation(): prepared_mutation_expression
	{
		return $this->prepared_facts;
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$this->operator_token_index = $worker->token_index($this->operator_token_index);
		$worker->enter($this);
		$worker->edge($this, $this->target);
	}
}

final class unary_expression_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	private ?prepared_unary_expression $prepared_facts = null;

	public expression_node $operand;
	public int $operator_token_index;

	public function kind(): node_kind
	{
		return node_kind::unary_expression;
	}

	/**
	 * Create an independent iterator retaining this node for lazy inspection.
	 */
	public function children(): child_iterator_i
	{
		return new unary_expression_children_iterator($this);
	}

	public function require_preparation(): prepared_expression
	{
		return $this->require_unary_preparation();
	}

	public function require_unary_preparation(): prepared_unary_expression
	{
		return $this->prepared_facts;
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$this->operator_token_index = $worker->token_index($this->operator_token_index);
		$worker->enter($this);
		$worker->edge($this, $this->operand);
	}
}

final class binary_expression_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	private ?prepared_binary_expression $prepared_facts = null;

	public expression_node $left;
	public int $operator_token_index;
	public expression_node $right;

	public function kind(): node_kind
	{
		return node_kind::binary_expression;
	}

	/**
	 * Create an independent iterator retaining this node for lazy inspection.
	 */
	public function children(): child_iterator_i
	{
		return new binary_expression_children_iterator($this);
	}

	public function require_preparation(): prepared_expression
	{
		return $this->require_binary_preparation();
	}

	public function require_binary_preparation(): prepared_binary_expression
	{
		return $this->prepared_facts;
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$this->operator_token_index = $worker->token_index($this->operator_token_index);
		$worker->enter($this);
		$worker->edge($this, $this->left);
		$worker->edge($this, $this->right);
	}
}

/** One source-written cast shape; contextual conversions never inject this node. */
final class cast_expression_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	private ?prepared_cast_expression $prepared_facts = null;

	public type_node $target_type;
	public expression_node $operand;

	public function kind(): node_kind
	{
		return node_kind::cast_expression;
	}

	public function children(): child_iterator_i
	{
		return new cast_expression_children_iterator($this);
	}

	public function require_preparation(): prepared_expression
	{
		return $this->require_cast_preparation();
	}

	public function require_cast_preparation(): prepared_cast_expression
	{
		return $this->prepared_facts;
	}

	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->target_type);
		$worker->edge($this, $this->operand);
	}
}

/**
 * Assignment is an expression at every depth, including a standalone statement.
 * A simple variable target owns its collected occurrence; this node does not
 * introduce a second symbol identity. The worker shares binding resolution with
 * explicit declarations and determines first assignment without rewriting syntax.
 */
final class assignment_expression_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	private ?prepared_assignment_expression $prepared_facts = null;

	public assignable_expression_node $target;
	public assignment_operator $operator = assignment_operator::assign;
	public bool $binds_reference = false;
	public expression_node $value;

	public function kind(): node_kind
	{
		return node_kind::assignment_expression;
	}

	public function children(): child_iterator_i
	{
		return new assignment_expression_children_iterator($this);
	}

	public function require_preparation(): prepared_expression
	{
		return $this->require_write_preparation();
	}

	public function require_write_preparation(): prepared_assignment_expression
	{
		return $this->prepared_facts;
	}

	public function require_assignment_preparation(): prepared_assignment
	{
		return object_cast($this->prepared_facts, prepared_assignment::class);
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->target);
		$worker->edge($this, $this->value);
	}
}

final class expression_statement_node extends statement_node
{
	use Node_Source_Span;

	public expression_node $expression;

	public function kind(): node_kind
	{
		return node_kind::expression_statement;
	}

	/**
	 * Create an independent iterator retaining this node for lazy inspection.
	 */
	public function children(): child_iterator_i
	{
		return new expression_statement_children_iterator($this);
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->expression);
	}
}

final class return_node extends statement_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	private ?prepared_return $prepared_facts = null;

	public ?expression_node $expression = null;

	public function kind(): node_kind
	{
		return node_kind::return_statement;
	}

	/**
	 * Create an independent iterator retaining this node for lazy inspection.
	 */
	public function children(): child_iterator_i
	{
		return new return_children_iterator($this);
	}

	public function require_preparation(): prepared_return
	{
		return $this->prepared_facts;
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		if ($this->expression !== null) {
			$worker->edge($this, $this->expression);
		}
	}
}

/**
 * Explicit local declaration syntax: $a int; or $a int = expression;
 * Untyped first assignment remains an assignment expression, not this node.
 * The declaration owns its occurrence and reuses the existing prepared storage
 * binding facts, whose resolved kind is declaration here.
 */
final class variable_declaration_node extends statement_node
{
	use Node_Source_Span;
	use Collected_Occurrence;
	use Preparation_Facts;

	private ?prepared_binding $prepared_facts = null;

	/** Stored name used by collection/resolution and generation; never recovered from tokens. */
	public string $name;
	public type_node $type_syntax;
	public ?expression_node $initializer = null;
	public bool $binds_reference = false;

	/** Parsing triggers collection here; registration and reconciliation belong to the collector. */
	public function collect(Symbol_Collector $collector, scope $scope, int $index): void
	{
		$collector->collect_variable($this, $scope, $index);
	}

	public function kind(): node_kind
	{
		return node_kind::variable_declaration;
	}

	/**
	 * Create an independent iterator retaining this node for lazy inspection.
	 */
	public function children(): child_iterator_i
	{
		return new variable_declaration_children_iterator($this);
	}

	public function require_preparation(): prepared_binding
	{
		return $this->prepared_facts;
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->type_syntax);
		if ($this->initializer !== null) {
			$worker->edge($this, $this->initializer);
		}
	}
}

final class array_type_node extends type_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	/** Canonical type alias; clearing syntax facts must not destroy or mutate the type definition. */
	private ?canonical_type_use $prepared_facts = null;

	public type_node $element_type;
	/** Current frontend requires a nonnegative integer literal extent. */
	public integer_literal_node $count;

	public function kind(): node_kind
	{
		return node_kind::array_type;
	}

	/**
	 * Create an independent iterator retaining this node for lazy inspection.
	 */
	public function children(): child_iterator_i
	{
		return new array_type_children_iterator($this);
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->element_type);
		$worker->edge($this, $this->count);
	}

	public function require_preparation(): canonical_type_use
	{
		return $this->prepared_facts;
	}
}

/** A source type constructor and its ordered arguments; preparation attaches one interned identity. */
final class template_application_type_node extends type_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	private ?canonical_type_use $prepared_facts = null;
	public named_type_node $definition;
	public Storage $arguments /** Storage<type_node> */;

	public function __construct()
	{
		$this->arguments = new Storage /** Storage<type_node> */();
	}

	public function kind(): node_kind
	{
		return node_kind::template_application_type;
	}

	public function children(): child_iterator_i
	{
		return new template_application_type_children_iterator($this);
	}

	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->definition);
		$arguments /** Storage<type_node> */ = $this->arguments;
		foreach ($arguments as $argument) {
			$worker->edge($this, $argument);
		}
	}

	public function require_preparation(): canonical_type_use
	{
		return $this->prepared_facts;
	}
}

/** One explicit source modifier over a type occurrence; it does not name a template definition. */
final class type_use_modifier_node extends type_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	private ?canonical_type_use $prepared_facts = null;
	public string $name;
	public type_use_modifier_kind $modifier;
	public type_node $operand;

	public function kind(): node_kind
	{
		return node_kind::type_use_modifier;
	}

	public function children(): child_iterator_i
	{
		return new type_use_modifier_children_iterator($this);
	}

	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->operand);
	}

	public function require_preparation(): canonical_type_use
	{
		return $this->prepared_facts;
	}
}

/** All data notation retains the same target and ordered entry structure. */
final class data_construction_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	private ?prepared_data_construction $prepared_facts = null;

	public function require_preparation(): prepared_expression { return $this->prepared_facts; }
	public function require_data_preparation(): prepared_data_construction { return $this->prepared_facts; }

	public ?type_node $target_type = null;
	public Storage $entries /** Storage<data_entry_node> */;

	/** Allocate only the concrete node's ordered syntax collections. */
	public function __construct()
	{
		$this->entries = new Storage /** Storage<data_entry_node> */();
	}

	public function kind(): node_kind
	{
		return node_kind::data_construction;
	}

	/** Derive notation from provenance; an explicit target ends immediately before ::. */
	public function source_representation(token_list $tokens): data_source_representation
	{
		$opening = $this->start_token();
		if ($this->target_type !== null) {
			$opening = $this->target_type->end_token() + 1;
		}
		$text = $tokens->text_at($opening);
		if ($text === '[') {
			return data_source_representation::php_like;
		}
		if ($text === '{') {
			return data_source_representation::js_like;
		}
		throw new \LogicException('Data construction has no opening delimiter in its token snapshot');
	}

	/** Retain one independent inspection cursor over the named owning edges. */
	public function children(): child_iterator_i
	{
		return new data_construction_children_iterator($this);
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		if ($this->target_type !== null) {
			$worker->edge($this, $this->target_type);
		}
		$items /** Storage<data_entry_node> */ = $this->entries;
		foreach ($items as $child) {
			$worker->edge($this, $child);
		}
	}
}

/** An absent key means positional input; a null expression is still an explicit key. */
final class data_entry_node extends ast_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	private ?prepared_data_entry $prepared_facts = null;

	public function require_preparation(): prepared_data_entry { return $this->prepared_facts; }

	public ?expression_node $key = null;
	public expression_node $value;

	public function kind(): node_kind
	{
		return node_kind::data_entry;
	}

	public function children(): child_iterator_i
	{
		return new data_entry_children_iterator($this);
	}

	/** Preserve key-before-value order without synthesizing positional keys. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		if ($this->key !== null) {
			$worker->edge($this, $this->key);
		}
		$worker->edge($this, $this->value);
	}
}

final class index_node extends assignable_expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	private ?prepared_index_expression $prepared_facts = null;

	public function require_preparation(): prepared_expression
	{
		return $this->require_index_preparation();
	}

	public function require_index_preparation(): prepared_index_expression
	{
		return $this->prepared_facts;
	}

	public expression_node $base;
	/** Absence means zero explicit arguments, with no built-in append interpretation. */
	public ?expression_node $index = null;

	public function kind(): node_kind
	{
		return node_kind::index_expression;
	}

	/**
	 * Create an independent iterator retaining this node for lazy inspection.
	 */
	public function children(): child_iterator_i
	{
		return new index_children_iterator($this);
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->base);
		if ($this->index !== null) {
			$argument /** expression_node */ = $this->index;
			$worker->edge($this, $argument);
		}
	}
}


final class field_access_node extends assignable_expression_node
{
	use Node_Source_Span;
	use Collected_Occurrence;
	use Preparation_Facts;

	private ?prepared_field_access $prepared_facts = null;

	public expression_node $base;
	/** Stored name used by collection/resolution and generation; never recovered from tokens. */
	public string $name;

	/** Parsing triggers collection here; registration and reconciliation belong to the collector. */
	public function collect(Symbol_Collector $collector, scope $scope, int $index): void
	{
		$collector->collect_field_reference($this, $scope, $index);
	}

	public function kind(): node_kind
	{
		return node_kind::field_expression;
	}

	/**
	 * Create an independent iterator retaining this node for lazy inspection.
	 */
	public function children(): child_iterator_i
	{
		return new field_access_children_iterator($this);
	}

	public function require_preparation(): prepared_expression
	{
		return $this->require_field_access_preparation();
	}

	public function require_field_access_preparation(): prepared_field_access
	{
		return $this->prepared_facts;
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->base);
	}
}

/*
 * Migration boundary: imported collected_name.node, parsed_file.root and prepared/
 * backend references currently name the production ast_node. Retarget them together
 * when migrating; these imports are not an executable two-AST integration layer.
 * Deletion guard lives at work entry, not on every child. A retained tombstone
 * may be reactivated only by parser reconciliation before retirement. Normal
 * preparation/generation never resets deleted state; cleanup accepts deleted owners.
 * Reconciliation retains the same specialized declaration, occurrence, declaration
 * work owner and C++ record. Changed bodies replace syntax, not declaration identity.
 * File-body work lives canonically on function_body_node; collected_file and work
 * lists may reference that exact owner, never allocate a competing file-body owner.
 * Scope_Publication/Compiler_Lifecycle retain publication and deletion orchestration,
 * including locks, parse-complete gates and dependency notification before cleanup.
 * They call typed maintenance workers only for the selected syntax subtrees.
 *
 * Each stage owns dispatch and consumes typed fields directly, never children().
 * Analysis attaches specialized facts. Function signatures and executable bodies
 * retain separate scheduled work; file bodies use the same body representation.
 * Selection must not blindly reprepare unchanged declarations or bodies.
 * Nested block processing uses its current enclosing context.
 * Assignment syntax is always expression-shaped, including first writes. Example:
 * $a = $b = 0; is expression_statement(assignment($a, assignment($b, 0))).
 * $a int = 0; is variable_declaration(name=$a, type=int, initializer=0).
 * Target occurrences carry local introduction/reference identity; workers reuse
 * one binding path for declaration and assignment, in the enclosing body context.
 * Inspection order is target then value; it is not an evaluation-order algorithm.
 * Stage dispatch rejects unsupported semantic forms explicitly; adding syntax
 * here does not expand the current backend's language support.
 */

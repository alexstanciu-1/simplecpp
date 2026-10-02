<?php

/* Typed syntax model; workers own compilation algorithms and traversal. */
namespace scpp\compiler;

/** File-level symbols and the implicit executable body have separate owners. */
final class file_node extends ast_node
{
	use Node_Source_Span;

	public Storage $declarations /** Storage<declaration_node> */;
	public function_body_node $body;
	/**
	 * Required observer; the parser retains this scope in parsed_file.scopes.
	 * @storage.reference parsed_file.scopes
	 * @reference.weak
	 */
	private scope $file_scope_reference /** weak<scope> */;

	public function __construct(scope $file_scope)
	{
		$this->file_scope_reference = $file_scope;
		$this->declarations = new Storage /** Storage<declaration_node> */();
	}

	/** Required lexical context, independent of syntax ownership. */
	public function file_scope(): scope
	{
		return \weakref_get($this->file_scope_reference);
	}

	public function kind(): node_kind
	{
		return node_kind::file;
	}

	/**
	 * Lazily inspect declarations then body; spans retain original source locations.
	 */
	public function children(): child_iterator_i
	{
		return new file_children_iterator($this);
	}

	/** Dispatch only the file executable body; declarations are independently scheduled. */
	public function prepare(preparation_context $context): void
	{
		$this->body->prepare($context);
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$items /** Storage<declaration_node> */ = $this->declarations;
		foreach ($items as $child) {
			$worker->edge($this, $child);
		}
		$worker->edge($this, $this->body);
	}

	/** The generation worker reads attached facts and owns rendering and child traversal. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_file($this);
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
final class function_body_node extends ast_node
{
	use Node_Source_Span;

	public Storage $statements /** Storage<statement_node> */;
	/**
	 * Required observer; the parser retains this scope in parsed_file.scopes.
	 * @storage.reference parsed_file.scopes
	 * @reference.weak
	 */
	private scope $local_scope_reference /** weak<scope> */;
	/**
	 * Parser comparison result for this executable unit, not an expression fact.
	 * Parsing marks text/structure changes; successful body preparation settles this
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
		$this->local_scope_reference = $local_scope;
		$this->statements = new Storage /** Storage<statement_node> */();
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

	/**
	 * Retain only the collection in an independent cursor; do not copy membership.
	 */
	public function children(): child_iterator_i
	{
		return new storage_children_iterator(new Storage_Cursor /** Storage_Cursor<ast_node> */($this->statements));
	}

	/** Forward this specialized node and its active context to the owning preparation algorithm. */
	public function prepare(preparation_context $context): void
	{
		Body_Preparation::prepare_statements($this->statements, $context);
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$items /** Storage<statement_node> */ = $this->statements;
		foreach ($items as $child) {
			$worker->edge($this, $child);
		}
	}

	/** The generation worker reads attached facts and owns rendering and child traversal. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_function_body($this);
	}
}

/** Nested grouping only; it does not own an independent preparation work item. */
final class block_node extends statement_node
{
	use Node_Source_Span;

	public Storage $statements /** Storage<statement_node> */;

	/** Allocate only the concrete node's ordered syntax collections. */
	public function __construct()
	{
		$this->statements = new Storage /** Storage<statement_node> */();
	}

	public function kind(): node_kind
	{
		return node_kind::block;
	}

	/**
	 * Retain only the collection in an independent cursor; do not copy membership.
	 */
	public function children(): child_iterator_i
	{
		return new storage_children_iterator(new Storage_Cursor /** Storage_Cursor<ast_node> */($this->statements));
	}

	/** Forward this specialized node and its active context to the owning preparation algorithm. */
	public function prepare(preparation_context $context): void
	{
		Body_Preparation::prepare_statements($this->statements, $context);
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$items /** Storage<statement_node> */ = $this->statements;
		foreach ($items as $child) {
			$worker->edge($this, $child);
		}
	}

	/** The generation worker reads attached facts and owns rendering and child traversal. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_block($this);
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

	/** Forward this specialized node and its active context to the owning preparation algorithm. */
	public function prepare(preparation_context $context): void
	{
		Type_Preparation::prepare_named_type($this, $context);
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

	/** Forward this specialized node and its active context to the owning preparation algorithm. */
	public function prepare(preparation_context $context): void
	{
		$this->set_preparation(Expression_Preparation::prepare_integer($this, $context));
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
	}

	/** The generation worker reads attached facts and owns rendering and child traversal. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_integer_literal($this);
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

	/** Forward this specialized node and its active context to the owning preparation algorithm. */
	public function prepare(preparation_context $context): void
	{
		$this->set_preparation(Expression_Preparation::prepare_float($this, $context));
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
	}

	/** The generation worker reads attached facts and owns rendering and child traversal. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_float_literal($this);
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

	/** Forward this specialized node and its active context to the owning preparation algorithm. */
	public function prepare(preparation_context $context): void
	{
		$this->set_preparation(Expression_Preparation::prepare_boolean($this->value, $context));
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
	}

	/** The generation worker reads attached facts and owns rendering and child traversal. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_boolean_literal($this);
	}
}

final class string_literal_node extends expression_node
{
	use Node_Source_Span;
	use Preparation_Facts;

	private ?prepared_string_literal $prepared_facts = null;

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

	/** Forward this specialized node and its active context to the owning preparation algorithm. */
	public function prepare(preparation_context $context): void
	{
		$this->set_preparation(Expression_Preparation::prepare_string($this, $context));
	}

	/** Offer this leaf node to maintenance without inventing spelling-specific children. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
	}

	/** The generation worker reads decoded bytes and owns target escaping. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_string_literal($this);
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

	/** Forward this specialized node and its active context to the owning preparation algorithm. */
	public function prepare(preparation_context $context): void
	{
		$this->set_preparation(Expression_Preparation::prepare_reference($this, $context));
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
	}

	/** The generation worker reads attached facts and owns rendering and child traversal. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_variable_reference($this);
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

	public function prepare(preparation_context $context): void
	{
		$this->set_preparation(Expression_Preparation::prepare_constant_reference($this, $context));
	}

	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
	}

	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_constant_reference($this);
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
		$collector->collect_function_reference($this, $scope, $index);
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

	/** Forward this specialized node and its active context to the owning preparation algorithm. */
	public function prepare(preparation_context $context): void
	{
		$this->set_preparation(Expression_Preparation::prepare_call($this, $context));
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$template_arguments /** Storage<named_type_node> */ = $this->template_arguments;
		foreach ($template_arguments as $template_argument) {
			$worker->edge($this, $template_argument);
		}
		$arguments /** Storage<expression_node> */ = $this->arguments;
		foreach ($arguments as $argument) {
			$worker->edge($this, $argument);
		}
	}

	/** The generation worker reads attached facts and owns rendering and child traversal. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_call($this);
	}
}

final class function_node extends declaration_node
{
	use Node_Source_Span;
	use Collected_Occurrence;
	use Preparation_Facts;

	private ?prepared_function $prepared_facts = null;

	/** Stored name used by collection/resolution and generation; never recovered from tokens. */
	public string $name;
	public Storage $parameters /** Storage<parameter_node> */;
	public type_node $return_type;
	public function_body_node $body;
	/** A failed later parse may retain an earlier body; this is not current-file completion. */
	private bool $parsed_body = false;
	/**
	 * Scope introduced and owned by this declaration; parent wiring belongs to parsing.
	 * @ownership owner
	 */
	private scope $owned_signature_scope;
	/** Already indexed by saved formal names; values retain token locations only. */
	public array $template_parameters /** hash<int> */ = [];

	/** Allocate only the concrete node's ordered syntax collections. */
	public function __construct()
	{
		$this->owned_signature_scope = new scope();
		$this->owned_signature_scope->mark_function();
		$this->parameters = new Storage /** Storage<parameter_node> */();
	}

	/** Query construction state without reading a potentially uninitialized required field. */
	public function has_parsed_body(): bool
	{
		return $this->parsed_body;
	}

	/** Attach only a successfully parsed body; failures before attachment preserve the prior state. */
	public function set_parsed_body(function_body_node $body): void
	{
		$this->body = $body;
		$this->parsed_body = true;
	}

	public function signature_scope(): scope
	{
		return $this->owned_signature_scope;
	}

	/** Parsing triggers collection here; registration and reconciliation belong to the collector. */
	public function collect(Symbol_Collector $collector, scope $scope, int $index): void
	{
		$collector->collect_function($this, $scope, $index);
	}

	/** Forward scheduling by concrete role; the worker owns selection and work state. */
	public function select_preparation(Preparation_Worker $worker): void
	{
		$worker->select_function($this);
	}

	public function kind(): node_kind
	{
		return node_kind::function_declaration;
	}

	/**
	 * Create an independent iterator retaining this node for lazy inspection.
	 */
	public function children(): child_iterator_i
	{
		return new function_children_iterator($this);
	}

	public function require_preparation(): prepared_function
	{
		return $this->prepared_facts;
	}


	/** Forward this specialized node and its active context to the owning preparation algorithm. */
	public function prepare(preparation_context $context): void
	{
		Declaration_Preparation::prepare_function($this, $context);
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		// Formal names are stable keys; their source locations follow the worker's token policy.
		$formals /** hash<int> */ = $this->template_parameters;
		foreach ($formals as $name => $index) {
			$this->template_parameters[$name] = $worker->token_index($index);
		}
		$worker->enter($this);
		$items /** Storage<parameter_node> */ = $this->parameters;
		foreach ($items as $child) {
			$worker->edge($this, $child);
		}
		$worker->edge($this, $this->return_type);
		$worker->edge($this, $this->body);
	}

	/** The generation worker reads attached facts and owns rendering and child traversal. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_function_signature($this);
	}
}

final class parameter_node extends ast_node
{
	use Node_Source_Span;
	use Collected_Occurrence;
	use Preparation_Facts;

	private ?prepared_parameter $prepared_facts = null;

	public type_node $type_syntax;
	/** Stored name used by collection/resolution and generation; never recovered from tokens. */
	public string $name;
	public passing_mode $mode = passing_mode::value;

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

	/** Forward this specialized node and its active context to the owning preparation algorithm. */
	public function prepare(preparation_context $context): void
	{
		Declaration_Preparation::prepare_parameter($this, $context);
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->type_syntax);
	}

	/** The generation worker reads attached facts and owns rendering and child traversal. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_parameter($this);
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

	/** Forward operand typing and operation selection to semantic preparation. */
	public function prepare(preparation_context $context): void
	{
		$this->set_preparation(Expression_Preparation::prepare_unary($this, $context));
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$this->operator_token_index = $worker->token_index($this->operator_token_index);
		$worker->enter($this);
		$worker->edge($this, $this->operand);
	}

	/** The generation worker consumes the prepared operation, never token spelling. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_unary_expression($this);
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

	/** Forward operand typing and operation selection to semantic preparation. */
	public function prepare(preparation_context $context): void
	{
		$this->set_preparation(Expression_Preparation::prepare_binary($this, $context));
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$this->operator_token_index = $worker->token_index($this->operator_token_index);
		$worker->enter($this);
		$worker->edge($this, $this->left);
		$worker->edge($this, $this->right);
	}

	/** The generation worker consumes the prepared operation, never token spelling. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_binary_expression($this);
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

	public function prepare(preparation_context $context): void
	{
		$this->set_preparation(Expression_Preparation::prepare_cast($this, $context));
	}

	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->target_type);
		$worker->edge($this, $this->operand);
	}

	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_cast_expression($this);
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

	private ?prepared_assignment $prepared_facts = null;

	public assignable_expression_node $target;
	public assignment_operator $operator = assignment_operator::assign;
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
		return $this->require_assignment_preparation();
	}

	public function require_assignment_preparation(): prepared_assignment
	{
		return $this->prepared_facts;
	}

	/** Forward the whole write; the worker must not prepare its target as an ordinary read. */
	public function prepare(preparation_context $context): void
	{
		$this->set_preparation(Expression_Preparation::prepare_assignment($this, $context));
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->target);
		$worker->edge($this, $this->value);
	}

	/** The generation worker reads attached facts and owns rendering and child traversal. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_assignment_expression($this);
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

	/** Forward this specialized node and its active context to the owning preparation algorithm. */
	public function prepare(preparation_context $context): void
	{
		Body_Preparation::prepare_expression_statement($this, $context);
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->expression);
	}

	/** The generation worker reads attached facts and owns rendering and child traversal. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_expression_statement($this);
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

	/** Forward this specialized node and its active context to the owning preparation algorithm. */
	public function prepare(preparation_context $context): void
	{
		$this->set_preparation(Body_Preparation::prepare_return($this, $context));
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		if ($this->expression !== null) {
			$worker->edge($this, $this->expression);
		}
	}

	/** The generation worker reads attached facts and owns rendering and child traversal. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_return($this);
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

	/** Forward this specialized node and its active context to the owning preparation algorithm. */
	public function prepare(preparation_context $context): void
	{
		$this->set_preparation(Body_Preparation::prepare_local_storage($this->occurrence(), $this->type_syntax, $this->initializer, $context));
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

	/** The generation worker reads attached facts and owns rendering and child traversal. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_variable_declaration($this);
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

	/** Forward this specialized node and its active context to the owning preparation algorithm. */
	public function prepare(preparation_context $context): void
	{
		Type_Preparation::prepare_array_type($this, $context);
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

	public function prepare(preparation_context $context): void
	{
		Type_Preparation::prepare_template_application_type($this, $context);
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

	public function prepare(preparation_context $context): void
	{
		Type_Preparation::prepare_type_use_modifier($this, $context);
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

final class array_literal_node extends expression_node
{
	use Node_Source_Span;

	public Storage $elements /** Storage<expression_node> */;

	/** Allocate only the concrete node's ordered syntax collections. */
	public function __construct()
	{
		$this->elements = new Storage /** Storage<expression_node> */();
	}

	public function kind(): node_kind
	{
		return node_kind::array_literal;
	}

	/**
	 * Retain only the collection in an independent cursor; do not copy membership.
	 */
	public function children(): child_iterator_i
	{
		return new storage_children_iterator(new Storage_Cursor /** Storage_Cursor<ast_node> */($this->elements));
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$items /** Storage<expression_node> */ = $this->elements;
		foreach ($items as $child) {
			$worker->edge($this, $child);
		}
	}
}

final class index_node extends assignable_expression_node
{
	use Node_Source_Span;

	public expression_node $base;
	public expression_node $index;

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
		$worker->edge($this, $this->index);
	}
}

final class struct_node extends declaration_node
{
	use Node_Source_Span;
	use Collected_Occurrence;
	use Preparation_Facts;

	private ?prepared_record $prepared_facts = null;

	/** Stored name used by collection/resolution and generation; never recovered from tokens. */
	public string $name;
	public Storage $fields /** Storage<field_node> */;
	/**
	 * Scope introduced and owned by this declaration; parent wiring belongs to parsing.
	 * @ownership owner
	 */
	private scope $owned_member_scope;

	/** Allocate only the concrete node's ordered syntax collections. */
	public function __construct()
	{
		$this->owned_member_scope = new scope();
		$this->fields = new Storage /** Storage<field_node> */();
	}

	public function member_scope(): scope
	{
		return $this->owned_member_scope;
	}

	/** Parsing triggers collection here; registration and reconciliation belong to the collector. */
	public function collect(Symbol_Collector $collector, scope $scope, int $index): void
	{
		$collector->collect_struct($this, $scope, $index);
	}

	/** Forward scheduling by concrete role; the worker owns selection and work state. */
	public function select_preparation(Preparation_Worker $worker): void
	{
		$worker->select_record($this);
	}

	public function kind(): node_kind
	{
		return node_kind::struct_declaration;
	}

	/**
	 * Retain only the collection in an independent cursor; do not copy membership.
	 */
	public function children(): child_iterator_i
	{
		return new storage_children_iterator(new Storage_Cursor /** Storage_Cursor<ast_node> */($this->fields));
	}

	public function require_preparation(): prepared_record
	{
		return $this->prepared_facts;
	}

	/** Forward this specialized node and its active context to the owning preparation algorithm. */
	public function prepare(preparation_context $context): void
	{
		Declaration_Preparation::prepare_struct($this, $context);
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$items /** Storage<field_node> */ = $this->fields;
		foreach ($items as $child) {
			$worker->edge($this, $child);
		}
	}

	/** The generation worker reads attached facts and owns rendering and child traversal. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_struct($this);
	}
}

final class field_node extends ast_node
{
	use Node_Source_Span;
	use Collected_Occurrence;
	use Preparation_Facts;

	private ?prepared_field $prepared_facts = null;

	public type_node $type_syntax;
	/** Stored name used by collection/resolution and generation; never recovered from tokens. */
	public string $name;

	/** Parsing triggers collection here; registration and reconciliation belong to the collector. */
	public function collect(Symbol_Collector $collector, scope $scope, int $index): void
	{
		$collector->collect_field($this, $scope, $index);
	}

	public function kind(): node_kind
	{
		return node_kind::field_declaration;
	}

	/**
	 * Create an independent iterator retaining this node for lazy inspection.
	 */
	public function children(): child_iterator_i
	{
		return new field_children_iterator($this);
	}

	public function require_preparation(): prepared_field
	{
		return $this->prepared_facts;
	}

	/** Forward this specialized node and its active context to the owning preparation algorithm. */
	public function prepare(preparation_context $context): void
	{
		Declaration_Preparation::prepare_field($this, $context);
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->type_syntax);
	}

	/** The generation worker reads attached facts and owns rendering and child traversal. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_field($this);
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

	/** Forward this specialized node and its active context to the owning preparation algorithm. */
	public function prepare(preparation_context $context): void
	{
		$this->set_preparation(Expression_Preparation::prepare_field_access($this, $context));
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->base);
	}

	/** The generation worker reads attached facts and owns rendering and child traversal. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_field_access($this);
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
 * Preparation contract is declared in abstractions.php. Workers consume
 * typed fields directly and attach specialized facts; they never walk children().
 * function_node::prepare() dispatches only its signature. Its body is a separate
 * scheduled function_body_node::prepare(), also used for file executable bodies.
 * The file hook coordinates supplied work; it must not blindly reprepare retained
 * declarations or bodies. Nested block dispatch uses its current enclosing context.
 * Assignment syntax is always expression-shaped, including first writes. Example:
 * $a = $b = 0; is expression_statement(assignment($a, assignment($b, 0))).
 * $a int = 0; is variable_declaration(name=$a, type=int, initializer=0).
 * Target occurrences carry local introduction/reference identity; workers reuse
 * one binding path for declaration and assignment, in the enclosing body context.
 * Inspection order is target then value; it is not an evaluation-order algorithm.
 * Unsupported semantic forms and trivia inherit explicit rejection; adding syntax
 * here does not expand the current backend's language support.
 */

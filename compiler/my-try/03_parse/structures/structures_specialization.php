<?php

/* Typed syntax model; workers own compilation algorithms and traversal. */
namespace scpp\compiler;

/** File-level symbols and the implicit executable body have separate owners. */
final class file_node extends ast_node
{
	use Node_Source_Span;
	use Node_Inspection_Parent;

	public Storage $declarations /** Storage<declaration_node> */;
	public function_body_node $body;
	/**
	 * Required observer; the parser retains this scope in parsed_file.scopes.
	 * @storage.reference parsed_file.scopes
	 * @reference.weak
	 */
	private scope $file_scope /** weak<scope> */;

	public function __construct(scope $file_scope)
	{
		$this->file_scope = $file_scope;
		$this->declarations = new Storage /** Storage<declaration_node> */();
	}

	/** Required lexical context; AST parent() is unrelated to scope lookup. */
	public function file_scope(): scope
	{
		return \weakref_get($this->file_scope);
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

	/** Forward typed syntax; the worker owns preparation, context and traversal. */
	public function prepare(preparation_worker_i $worker): void
	{
		$worker->prepare_file($this);
	}

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_file($this);
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
	use Node_Inspection_Parent;

	public Storage $statements /** Storage<statement_node> */;
	/**
	 * Required observer; the parser retains this scope in parsed_file.scopes.
	 * @storage.reference parsed_file.scopes
	 * @reference.weak
	 */
	private scope $local_scope /** weak<scope> */;
	/**
	 * Parser comparison result for this executable unit, not an expression fact.
	 * Parsing marks text/structure changes; successful body preparation settles this
	 * flag. Scheduling also checks body_work state for dependency changes/retries.
	 * This flag never gates backend work and is not a duplicate change_status.
	 */
	public bool $syntax_changed = true;
	/**
	 * Retained work/dependency state for this body only, absent before scheduling.
	 * @ownership owner
	 * Signature/declaration work remains on collected occurrences. This is not
	 * Preparation_Facts and does not supply preparation()/require_preparation().
	 */
	private ?preparation_owner $body_work = null;

	public function __construct(scope $local_scope)
	{
		$this->local_scope = $local_scope;
		$this->statements = new Storage /** Storage<statement_node> */();
	}

	/** Required lexical context; AST parent() is unrelated to scope lookup. */
	public function local_scope(): scope
	{
		return \weakref_get($this->local_scope);
	}

	/** One canonical body schedule/dependency identity, shared with existing work lists. */
	public function work(): ?preparation_owner
	{
		return $this->body_work;
	}

	/** Attach only after scheduling or an explicit transfer from a replaced body. */
	public function attach_work(preparation_owner $work): void
	{
		if (($this->body_work !== null) && ($this->body_work !== $work)) {
			throw new \LogicException('Body already has a different preparation owner');
		}
		$this->body_work = $work;
	}

	/**
	 * Representation operation only. Lifecycle must notify consumers and detach
	 * dependencies before retirement, or transfer this exact record to the new body.
	 * Clearing this slot alone is not dependency cleanup or successful preparation.
	 */
	public function detach_work(): ?preparation_owner
	{
		$work = $this->body_work;
		$this->body_work = null;
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

	/** Forward typed syntax; the worker owns preparation, context and traversal. */
	public function prepare(preparation_worker_i $worker): void
	{
		$worker->prepare_function_body($this);
	}

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_function_body($this);
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
	use Node_Inspection_Parent;

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

	/** Forward typed syntax; the worker owns preparation, context and traversal. */
	public function prepare(preparation_worker_i $worker): void
	{
		$worker->prepare_block($this);
	}

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_block($this);
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
	use Node_Inspection_Parent;
	use Preparation_Facts;

	/** Canonical type alias; clearing syntax facts must not destroy or mutate the type definition. */
	private ?type_definition $prepared_facts = null;
	use Collected_Occurrence;

	/** Stored reference spelling, independent of the token buffer lifetime. */
	public string $name;

	public function kind(): node_kind
	{
		return node_kind::named_type;
	}

	/** Forward typed syntax; the worker owns preparation, context and traversal. */
	public function prepare(preparation_worker_i $worker): void
	{
		$worker->prepare_named_type($this);
	}

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_named_type($this);
	}

	public function require_preparation(): type_definition
	{
		return $this->prepared_facts;
	}
}

final class punctuation_node extends trivia_node
{
	use Node_Source_Span;
	use Node_Inspection_Parent;

	public function kind(): node_kind
	{
		return node_kind::punctuation;
	}

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_punctuation($this);
	}
}

final class comment_node extends trivia_node
{
	use Node_Source_Span;
	use Node_Inspection_Parent;

	public function kind(): node_kind
	{
		return node_kind::comment;
	}

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_comment($this);
	}
}

final class integer_literal_node extends expression_node
{
	use Node_Source_Span;
	use Node_Inspection_Parent;
	use Preparation_Facts;

	private ?prepared_integer_literal $prepared_facts = null;

	public function kind(): node_kind
	{
		return node_kind::integer_literal;
	}

	public function require_preparation(): prepared_integer_literal
	{
		return $this->prepared_facts;
	}

	/** Forward typed syntax; the worker owns preparation, context and traversal. */
	public function prepare(preparation_worker_i $worker): void
	{
		$worker->prepare_integer_literal($this);
	}

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_integer_literal($this);
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
	use Node_Inspection_Parent;
	use Preparation_Facts;

	private ?prepared_float_literal $prepared_facts = null;

	public function kind(): node_kind
	{
		return node_kind::float_literal;
	}

	public function require_preparation(): prepared_float_literal
	{
		return $this->prepared_facts;
	}

	/** Forward typed syntax; the worker owns preparation, context and traversal. */
	public function prepare(preparation_worker_i $worker): void
	{
		$worker->prepare_float_literal($this);
	}

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_float_literal($this);
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
	use Node_Inspection_Parent;
	use Preparation_Facts;

	private ?prepared_boolean_literal $prepared_facts = null;

	public bool $value;

	public function kind(): node_kind
	{
		return node_kind::boolean_literal;
	}

	public function require_preparation(): prepared_boolean_literal
	{
		return $this->prepared_facts;
	}

	/** Forward typed syntax; the worker owns preparation, context and traversal. */
	public function prepare(preparation_worker_i $worker): void
	{
		$worker->prepare_boolean_literal($this);
	}

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_boolean_literal($this);
	}

	/** The generation worker reads attached facts and owns rendering and child traversal. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_boolean_literal($this);
	}
}

final class variable_reference_node extends assignable_expression_node
{
	use Node_Source_Span;
	use Node_Inspection_Parent;
	use Collected_Occurrence;
	use Preparation_Facts;

	private ?prepared_variable_reference $prepared_facts = null;

	/** Stored reference spelling, independent of the token buffer lifetime. */
	public string $name;

	public function kind(): node_kind
	{
		return node_kind::variable_reference;
	}

	public function require_preparation(): prepared_variable_reference
	{
		return $this->prepared_facts;
	}

	/** Forward typed syntax; the worker owns preparation, context and traversal. */
	public function prepare(preparation_worker_i $worker): void
	{
		$worker->prepare_variable_reference($this);
	}

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_variable_reference($this);
	}

	/** The generation worker reads attached facts and owns rendering and child traversal. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_variable_reference($this);
	}
}

final class call_node extends expression_node
{
	use Node_Source_Span;
	use Node_Inspection_Parent;
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

	public function require_preparation(): prepared_call
	{
		return $this->prepared_facts;
	}

	/** Forward typed syntax; the worker owns preparation, context and traversal. */
	public function prepare(preparation_worker_i $worker): void
	{
		$worker->prepare_call($this);
	}

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_call($this);
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
	use Node_Inspection_Parent;
	use Collected_Occurrence;
	use Preparation_Facts;

	private ?prepared_function $prepared_facts = null;

	/** Stored name used by collection/resolution and generation; never recovered from tokens. */
	public string $name;
	public Storage $parameters /** Storage<parameter_node> */;
	public type_node $return_type;
	public function_body_node $body;
	/**
	 * Scope introduced and owned by this declaration; parent wiring belongs to parsing.
	 * @ownership owner
	 */
	private scope $signature_scope;
	/** Already indexed by saved formal names; values retain token locations only. */
	public array $template_parameters /** hash<int> */ = [];

	/** Allocate only the concrete node's ordered syntax collections. */
	public function __construct()
	{
		$this->signature_scope = new scope();
		$this->signature_scope->mark_function();
		$this->parameters = new Storage /** Storage<parameter_node> */();
	}

	public function signature_scope(): scope
	{
		return $this->signature_scope;
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


	/** Forward typed syntax; the worker owns preparation, context and traversal. */
	public function prepare(preparation_worker_i $worker): void
	{
		$worker->prepare_function_signature($this);
	}

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_function($this);
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
	use Node_Inspection_Parent;
	use Collected_Occurrence;
	use Preparation_Facts;

	private ?prepared_parameter $prepared_facts = null;

	public type_node $type_syntax;
	/** Stored name used by collection/resolution and generation; never recovered from tokens. */
	public string $name;
	public passing_mode $mode = passing_mode::value;

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

	/** Forward typed syntax; the worker owns preparation, context and traversal. */
	public function prepare(preparation_worker_i $worker): void
	{
		$worker->prepare_parameter($this);
	}

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_parameter($this);
	}

	/** The generation worker reads attached facts and owns rendering and child traversal. */
	public function generate_cpp(cpp_generation_worker_i $worker): string
	{
		return $worker->generate_parameter($this);
	}
}

final class binary_expression_node extends expression_node
{
	use Node_Source_Span;
	use Node_Inspection_Parent;

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

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_binary_expression($this);
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
	use Node_Inspection_Parent;
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

	public function require_preparation(): prepared_assignment
	{
		return $this->prepared_facts;
	}

	/** Forward the whole write; the worker must not prepare its target as an ordinary read. */
	public function prepare(preparation_worker_i $worker): void
	{
		$worker->prepare_assignment($this);
	}

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_assignment_expression($this);
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
	use Node_Inspection_Parent;

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

	/** Forward typed syntax; the worker owns preparation, context and traversal. */
	public function prepare(preparation_worker_i $worker): void
	{
		$worker->prepare_expression_statement($this);
	}

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_expression_statement($this);
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
	use Node_Inspection_Parent;

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

	/** Forward typed syntax; the worker owns preparation, context and traversal. */
	public function prepare(preparation_worker_i $worker): void
	{
		$worker->prepare_return($this);
	}

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_return($this);
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
	use Node_Inspection_Parent;
	use Collected_Occurrence;
	use Preparation_Facts;

	private ?prepared_binding $prepared_facts = null;

	/** Stored name used by collection/resolution and generation; never recovered from tokens. */
	public string $name;
	public type_node $type_syntax;
	public ?expression_node $initializer = null;

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

	/** Forward typed syntax; the worker owns preparation, context and traversal. */
	public function prepare(preparation_worker_i $worker): void
	{
		$worker->prepare_variable_declaration($this);
	}

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_variable_declaration($this);
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
	use Node_Inspection_Parent;
	use Preparation_Facts;

	/** Canonical type alias; clearing syntax facts must not destroy or mutate the type definition. */
	private ?type_definition $prepared_facts = null;

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

	/** Forward typed syntax; the worker owns preparation, context and traversal. */
	public function prepare(preparation_worker_i $worker): void
	{
		$worker->prepare_array_type($this);
	}

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_array_type($this);
	}

	public function require_preparation(): type_definition
	{
		return $this->prepared_facts;
	}
}

final class array_literal_node extends expression_node
{
	use Node_Source_Span;
	use Node_Inspection_Parent;

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

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_array_literal($this);
	}
}

final class index_node extends assignable_expression_node
{
	use Node_Source_Span;
	use Node_Inspection_Parent;

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

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_index($this);
	}
}

final class struct_node extends declaration_node
{
	use Node_Source_Span;
	use Node_Inspection_Parent;
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
	private scope $member_scope;

	/** Allocate only the concrete node's ordered syntax collections. */
	public function __construct()
	{
		$this->member_scope = new scope();
		$this->fields = new Storage /** Storage<field_node> */();
	}

	public function member_scope(): scope
	{
		return $this->member_scope;
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

	/** Forward typed syntax; the worker owns preparation, context and traversal. */
	public function prepare(preparation_worker_i $worker): void
	{
		$worker->prepare_struct($this);
	}

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_struct($this);
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
	use Node_Inspection_Parent;
	use Collected_Occurrence;
	use Preparation_Facts;

	private ?prepared_field $prepared_facts = null;

	public type_node $type_syntax;
	/** Stored name used by collection/resolution and generation; never recovered from tokens. */
	public string $name;

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

	/** Forward typed syntax; the worker owns preparation, context and traversal. */
	public function prepare(preparation_worker_i $worker): void
	{
		$worker->prepare_field($this);
	}

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_field($this);
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
	use Node_Inspection_Parent;
	use Collected_Occurrence;
	use Preparation_Facts;

	private ?prepared_field_access $prepared_facts = null;

	public expression_node $base;
	/** Stored name used by collection/resolution and generation; never recovered from tokens. */
	public string $name;

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

	public function require_preparation(): prepared_field_access
	{
		return $this->prepared_facts;
	}

	/** Forward typed syntax; the worker owns preparation, context and traversal. */
	public function prepare(preparation_worker_i $worker): void
	{
		$worker->prepare_field_access($this);
	}

	/** Dispatch only; the maintenance worker owns traversal and lifecycle policy. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->visit_field_access($this);
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
 * Preparation contract is declared in abstractions.php.example. Workers consume
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

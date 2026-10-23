<?php

/* Shared declaration syntax; semantic admission and processing remain stage-owned. */
namespace scpp\compiler;

/** Authored signature data, independent of declaration names and expression identity. */
final class callable_signature_syntax
{
	public bool $asynchronous = false;
	public Storage $parameters /** Storage<parameter_node> */;
	public ?type_node $return_type = null;
	/** Explicit source marker; its lifetime contract is established by preparation. */
	public bool $returns_reference = false;

	public function __construct()
	{
		$this->parameters = new Storage /** Storage<parameter_node> */();
	}
}

/** Read-only syntax role; it confers neither named publication nor callable semantics. */
interface callable_syntax_owner_i
{
	public function signature_parts(): callable_signature_syntax;
	public function optional_callable_body(): ?function_body_node;
}

/** Shared owned syntax and local construction state, without a declaration name. */
trait Callable_Syntax
{
	public function signature_parts(): callable_signature_syntax
	{
		return $this->signature_syntax;
	}

	public function optional_callable_body(): ?function_body_node
	{
		return $this->body;
	}

	public ?function_body_node $body = null;
	/** Preserve replaced executable ownership until join-ordered semantic retirement. */
	public Storage $retired_bodies /** Storage<function_body_node> */;
	/** A failed later parse may retain an earlier body; this is not current-file completion. */
	private bool $parsed_definition = false;

	/** Initialize collections and configure the already-owned signature scope. */
	private function initialize_callable_state(): void
	{
		$this->retired_bodies = new Storage /** Storage<function_body_node> */();
		$this->owned_signature_scope->mark_function();
	}

	/** Query construction state without reading a potentially uninitialized required field. */
	public function has_parsed_body(): bool
	{
		return $this->parsed_definition && ($this->body !== null);
	}

	/** Attach only a successfully parsed body; failures before attachment preserve the prior state. */
	public function set_parsed_body(function_body_node $body): void
	{
		$this->body = $body;
		$this->parsed_definition = true;
	}

	/** A completed semicolon declaration is different from an unfinished parse. */
	public function set_bodyless_declaration(): void
	{
		if ($this->body !== null) {
			$retired /** Storage<function_body_node> */ = $this->retired_bodies;
			$retired->append($this->body);
		}
		$this->body = null;
		$this->parsed_definition = true;
	}

	public function has_parsed_definition(): bool
	{
		return $this->parsed_definition;
	}

	public function require_body(): function_body_node
	{
		return $this->body;
	}

	public function require_return_type(): type_node
	{
		return $this->signature_syntax->return_type;
	}

	public function signature_scope(): scope
	{
		return $this->owned_signature_scope;
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain_callable_children(node_maintenance_worker_i $worker,
		?Storage $captures /** Storage<closure_capture_node> */ = null): void
	{
		$worker->enter($this);
		$items /** Storage<parameter_node> */ = $this->signature_syntax->parameters;
		foreach ($items as $child) {
			$worker->edge($this, $child);
		}
		if ($captures !== null) {
			$capture_items /** Storage<closure_capture_node> */ = $captures;
			foreach ($capture_items as $capture) { $worker->edge($this, $capture); }
		}
		if ($this->signature_syntax->return_type !== null) {
			$worker->edge($this, $this->signature_syntax->return_type);
		}
		if ($this->body !== null) {
			$worker->edge($this, $this->body);
		}
	}
}

abstract class callable_declaration_node extends declaration_node implements callable_syntax_owner_i
{
	use Node_Source_Span;
	use Collected_Occurrence;
	use Preparation_Facts;
	use Callable_Syntax;

	private ?prepared_source_callable $prepared_facts = null;

	/** Named identity and template bindings are not part of anonymous callable syntax. */
	public string $name;
	public array $template_parameters /** hash<int> */ = [];

	public function __construct(
		/** Single owner of authored parameter/return syntax. */
		public callable_signature_syntax $signature_syntax,
		/** Owned scope; parent wiring belongs to parsing. @ownership owner */
		private scope $owned_signature_scope)
	{
		$this->initialize_callable_state();
	}

	public function require_preparation(): prepared_source_callable
	{
		return $this->prepared_facts;
	}

	/** Named callables have no captures; both syntax owners share the iterator. */
	public function children(): child_iterator_i
	{
		return new callable_children_iterator($this);
	}

	public function maintain(node_maintenance_worker_i $worker): void
	{
		$formals /** hash<int> */ = $this->template_parameters;
		foreach ($formals as $name => $index) {
			$this->template_parameters[$name] = $worker->token_index($index);
		}
		$this->maintain_callable_children($worker);
	}
}

/** Capture-only syntax owns its passing mode and ordinary variable-reference child. */
final class closure_capture_node extends ast_node
{
	use Node_Source_Span;
	public passing_mode $mode = passing_mode::value;
	public variable_reference_node $variable;
	public function kind(): node_kind { return node_kind::closure_capture; }
	public function children(): child_iterator_i { return new closure_capture_children_iterator($this); }
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->variable);
	}
}

/** Anonymous expression syntax; semantic identity and executable preparation follow separately. */
final class closure_node extends expression_node implements callable_syntax_owner_i
{
	/** Allocate fresh callable-owned inputs before publishing the node. */
	public static function fresh_node(): closure_node
	{
		$signature = new callable_signature_syntax();
		$signature_scope = new scope();
		$body_scope = new scope();
		return new closure_node($signature, $signature_scope, $body_scope);
	}

	use Node_Source_Span;
	use Callable_Syntax;
	use Preparation_Facts;
	private ?prepared_expression $prepared_facts = null;
	public Storage $captures /** Storage<closure_capture_node> */;
	public closure_syntax_kind $syntax_kind = closure_syntax_kind::explicit_closure;
	public function require_preparation(): prepared_expression { return $this->prepared_facts; }
	private ?anonymous_callable_identity $source_identity /** weak<anonymous_callable_identity> */ = null;

	public function anonymous_identity(): anonymous_callable_identity
	{
		return weakref_get($this->source_identity);
	}

	public function attach_anonymous_identity(anonymous_callable_identity $identity): void
	{
		if (weakref_get($this->source_identity) !== null) {
			throw new \LogicException('Anonymous callable identity is already attached');
		}
		$this->source_identity = weak($identity);
	}

	public function __construct(
		/** Single owner of authored parameter/return syntax. */
		public callable_signature_syntax $signature_syntax,
		/** Owned scope; parent wiring belongs to parsing. @ownership owner */
		private scope $owned_signature_scope,
		/** Owned nested-body scope, retained across body reuse. @ownership owner */
		private scope $owned_body_scope)
	{
		$this->initialize_callable_state();
		$this->captures = new Storage /** Storage<closure_capture_node> */();
		$this->owned_body_scope->set_parent($this->signature_scope());
		$this->owned_body_scope->mark_function();
	}

	public function body_scope(): scope
	{
		return $this->owned_body_scope;
	}

	public function kind(): node_kind
	{
		return node_kind::closure_expression;
	}

	public function children(): child_iterator_i
	{
		return new callable_children_iterator($this, $this->captures);
	}

	public function maintain(node_maintenance_worker_i $worker): void
	{
		$this->maintain_callable_children($worker, $this->captures);
	}
}

/** Free-function semantics remain separate from member receiver/modifier semantics. */
final class function_node extends callable_declaration_node
{
	/** Allocate fresh callable-owned inputs before publishing the node. */
	public static function fresh_node(): function_node
	{
		$signature = new callable_signature_syntax();
		$signature_scope = new scope();
		return new function_node($signature, $signature_scope);
	}

	public function collect(Symbol_Collector $collector, scope $scope, int $index): void
	{
		$collector->collect_function($this, $scope, $index);
	}

	public function kind(): node_kind
	{
		return node_kind::function_declaration;
	}

}

/** Syntax foundation only; member signature/receiver preparation is a later slice. */
final class method_node extends callable_declaration_node
{
	/** Allocate fresh callable-owned inputs before publishing the node. */
	public static function fresh_node(): method_node
	{
		$signature = new callable_signature_syntax();
		$signature_scope = new scope();
		return new method_node($signature, $signature_scope);
	}

	public array $modifiers /** vector<declaration_modifier> */ = [];

	public function kind(): node_kind
	{
		return node_kind::method_declaration;
	}
}

abstract class constant_declaration_node extends declaration_node
{
	use Node_Source_Span;
	use Collected_Occurrence;
	use Preparation_Facts;
	private ?prepared_source_constant $prepared_facts = null;
	public string $name;
	/** Absent while the parser collects a new declaration before its expression. */
	private ?expression_node $initializer_value = null;

	public function require_initializer(): expression_node
	{
		$value = $this->initializer_value;
		if ($value === null) {
			throw new \LogicException('Constant initializer is not available');
		}
		return $value;
	}

	public function set_initializer(expression_node $value): void
	{
		$this->initializer_value = $value;
	}

	public function require_preparation(): prepared_source_constant
	{
		return $this->prepared_facts;
	}

	public function children(): child_iterator_i
	{
		return new constant_children_iterator($this);
	}

	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$worker->edge($this, $this->require_initializer());
	}
}

final class namespace_constant_node extends constant_declaration_node
{
	public function collect(Symbol_Collector $collector, scope $scope, int $index): void
	{
		$collector->collect_constant($this, $scope, $index);
	}

	public function kind(): node_kind
	{
		return node_kind::constant_declaration;
	}
}

final class member_constant_node extends constant_declaration_node
{
	public array $modifiers /** vector<declaration_modifier> */ = [];

	public function kind(): node_kind
	{
		return node_kind::member_constant_declaration;
	}
}

/** Common syntax owner; plain classes and structs publish record facts, other kinds remain gated. */
final class type_declaration_node extends declaration_node
{
	use Node_Source_Span;
	use Collected_Occurrence;
	use Preparation_Facts;

	private ?prepared_record $prepared_facts = null;

	/** Stored name used by collection/resolution and generation; never recovered from tokens. */
	public string $name;
	public type_declaration_kind $declaration_kind = type_declaration_kind::struct_type;
	public array $modifiers /** vector<declaration_modifier> */ = [];
	public ?named_type_node $base_class = null;
	public Storage $extended_interfaces /** Storage<named_type_node> */;
	public Storage $implemented_interfaces /** Storage<named_type_node> */;
	/** These typed collections own syntax; collected scopes own lookup membership. */
	public Storage $properties /** Storage<property_node> */;
	public Storage $methods /** Storage<method_node> */;
	public Storage $constants /** Storage<member_constant_node> */;
	public Storage $use_traits /** Storage<trait_use_node> */;
	/**
	 * Scope introduced and owned by this declaration; parent wiring belongs to parsing.
	 * @ownership owner
	 */
	private scope $owned_member_scope;

	/** Allocate only the concrete node's ordered syntax collections. */
	public function __construct()
	{
		$this->owned_member_scope = new scope();
		$this->extended_interfaces = new Storage /** Storage<named_type_node> */();
		$this->implemented_interfaces = new Storage /** Storage<named_type_node> */();
		$this->properties = new Storage /** Storage<property_node> */();
		$this->methods = new Storage /** Storage<method_node> */();
		$this->constants = new Storage /** Storage<member_constant_node> */();
		$this->use_traits = new Storage /** Storage<trait_use_node> */();
	}

	public function member_scope(): scope
	{
		return $this->owned_member_scope;
	}

	/** Parsing triggers collection here; registration and reconciliation belong to the collector. */
	public function collect(Symbol_Collector $collector, scope $scope, int $index): void
	{
		$collector->collect_member_owner($this, $scope, $index);
	}

	public function kind(): node_kind
	{
		return node_kind::type_declaration;
	}

	/**
	 * Retain only the collection in an independent cursor; do not copy membership.
	 */
	public function children(): child_iterator_i
	{
		return new type_declaration_children_iterator($this);
	}

	public function require_preparation(): prepared_record
	{
		return $this->prepared_facts;
	}

	/** Visit each typed owner once; category order is not language evaluation order. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		if ($this->base_class !== null) {
			$worker->edge($this, $this->base_class);
		}
		$extended /** Storage<named_type_node> */ = $this->extended_interfaces;
		foreach ($extended as $child) {
			$worker->edge($this, $child);
		}
		$implemented /** Storage<named_type_node> */ = $this->implemented_interfaces;
		foreach ($implemented as $child) {
			$worker->edge($this, $child);
		}
		$properties /** Storage<property_node> */ = $this->properties;
		foreach ($properties as $property) {
			$worker->edge($this, $property);
		}
		$methods /** Storage<method_node> */ = $this->methods;
		foreach ($methods as $method) {
			$worker->edge($this, $method);
		}
		$constants /** Storage<member_constant_node> */ = $this->constants;
		foreach ($constants as $constant) {
			$worker->edge($this, $constant);
		}
		$uses /** Storage<trait_use_node> */ = $this->use_traits;
		foreach ($uses as $use) {
			$worker->edge($this, $use);
		}
	}
}

/** One simple use declaration; no alias or precedence-adaptation syntax in this slice. */
final class trait_use_node extends ast_node
{
	use Node_Source_Span;
	public Storage $traits /** Storage<named_type_node> */;

	public function __construct()
	{
		$this->traits = new Storage /** Storage<named_type_node> */();
	}

	public function kind(): node_kind
	{
		return node_kind::trait_use;
	}

	public function children(): child_iterator_i
	{
		return new storage_children_iterator(new Storage_Cursor /** Storage_Cursor<ast_node> */($this->traits));
	}

	/** Trait references remain syntax children, not resolved declaration owners. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		$traits /** Storage<named_type_node> */ = $this->traits;
		foreach ($traits as $child) {
			$worker->edge($this, $child);
		}
	}
}

final class property_node extends declaration_node
{
	use Node_Source_Span;
	use Collected_Occurrence;
	use Preparation_Facts;

	private ?prepared_field $prepared_facts = null;

	public ?type_node $type_syntax = null;
	public ?expression_node $initializer = null;
	public array $modifiers /** vector<declaration_modifier> */ = [];
	/** Stored name used by collection/resolution and generation; never recovered from tokens. */
	public string $name;

	/** Parsing triggers collection here; registration and reconciliation belong to the collector. */
	public function collect(Symbol_Collector $collector, scope $scope, int $index): void
	{
		$collector->collect_field($this, $scope, $index);
	}

	public function kind(): node_kind
	{
		return node_kind::property_declaration;
	}

	/**
	 * Create an independent iterator retaining this node for lazy inspection.
	 */
	public function children(): child_iterator_i
	{
		return new property_children_iterator($this);
	}

	public function require_preparation(): prepared_field
	{
		return $this->prepared_facts;
	}

	public function require_type_syntax(): type_node
	{
		return $this->type_syntax;
	}

	/** Offer this node and its owned syntax in grammar order; the worker selects recursion. */
	public function maintain(node_maintenance_worker_i $worker): void
	{
		$worker->enter($this);
		if ($this->type_syntax !== null) {
			$worker->edge($this, $this->type_syntax);
		}
		if ($this->initializer !== null) {
			$worker->edge($this, $this->initializer);
		}
	}
}

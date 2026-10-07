<?php

/* Shared declaration syntax; semantic admission and processing remain stage-owned. */
namespace scpp\compiler;

abstract class callable_declaration_node extends declaration_node
{
	use Node_Source_Span;
	use Collected_Occurrence;
	use Preparation_Facts;

	private ?prepared_source_callable $prepared_facts = null;

	public function require_preparation(): prepared_source_callable
	{
		return $this->prepared_facts;
	}

	/** Stored name used by collection/resolution and generation; never recovered from tokens. */
	public string $name;
	public Storage $parameters /** Storage<parameter_node> */;
	public ?type_node $return_type = null;
	/** Explicit source marker; its lifetime contract is established by preparation. */
	public bool $returns_reference = false;
	public ?function_body_node $body = null;
	/** A failed later parse may retain an earlier body; this is not current-file completion. */
	private bool $parsed_definition = false;
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
		return $this->return_type;
	}

	public function signature_scope(): scope
	{
		return $this->owned_signature_scope;
	}

	/**
	 * Create an independent iterator retaining this node for lazy inspection.
	 */
	public function children(): child_iterator_i
	{
		return new callable_children_iterator($this);
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
		if ($this->return_type !== null) {
			$worker->edge($this, $this->return_type);
		}
		if ($this->body !== null) {
			$worker->edge($this, $this->body);
		}
	}
}

/** Free-function semantics remain separate from member receiver/modifier semantics. */
final class function_node extends callable_declaration_node
{

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
	public expression_node $initializer;

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
		$worker->edge($this, $this->initializer);
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
		$collector->collect_record($this, $scope, $index);
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

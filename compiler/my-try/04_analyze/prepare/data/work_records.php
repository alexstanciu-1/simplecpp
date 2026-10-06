<?php

/* Retained typed work identities; algorithms remain in Preparation_Worker. */
namespace scpp\compiler;

/** Processing kinds have separate work lists; a body is not a language symbol. */
enum preparation_kind {
	case declaration;
	case function_body;
	case file_body;
}

enum preparation_state {
	case pending;
	case processing;
	case ready;
}

enum preparation_lookup_kind {
	case import_name;
	case type;
	case function_name;
	case constant;
}

/** Stable work/dependency identity attached to an existing declaration or file/body owner. */
abstract class preparation_owner
{
	public preparation_state $state = preparation_state::pending;
	/** Persistent work selection; only successful preparation settles it. */
	public change_state $change_status = change_state::added;
	public bool $failed = false;
	public string $failure_message = '';
	public collected_file $source;
	public int $version = 0;
	/** Strong identity keys; explicit cleanup severs registrations on replacement, deletion and reset. */
	public \SplObjectStorage $dependencies /** hash<int, shared<declaration_work>> */;
	public \SplObjectStorage $dependents /** hash<bool, shared<preparation_owner>> */;
	public \SplObjectStorage $lookups /** hash<bool, shared<preparation_lookup>> */;

	/** Retained graph data never references a processing worker. */
	public function __construct(collected_file $source)
	{
		$this->source = $source;
		$this->dependencies = new \SplObjectStorage /** hash<int, shared<declaration_work>> */();
		$this->dependents = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		$this->lookups = new \SplObjectStorage /** hash<bool, shared<preparation_lookup>> */();
	}

	/** Computed compatibility category for backend fragment assembly. */
	abstract public function kind(): preparation_kind;

	/** Common optional declaration query; specialized accessors have distinct names. */
	abstract public function declaration(): ?collected_definition;
}

/** Declaration work is independently settled before any body consumes its facts. */
abstract class declaration_work extends preparation_owner
{
	abstract public function required_declaration(): collected_definition;

	public function declaration(): ?collected_definition
	{
		return $this->required_declaration();
	}

	public function kind(): preparation_kind
	{
		return preparation_kind::declaration;
	}
}

final class function_signature_work extends declaration_work
{
	private collected_function $definition;

	public function __construct(collected_function $definition)
	{
		parent::__construct($definition->collection);
		$this->definition = $definition;
	}

	public function required_declaration(): collected_definition
	{
		return $this->definition;
	}

	public function function_definition(): collected_function
	{
		return $this->definition;
	}
}

final class constant_definition_work extends declaration_work
{
	public function __construct(private collected_constant $definition) { parent::__construct($definition->collection); }
	public function required_declaration(): collected_definition { return $this->definition; }
	public function constant_definition(): collected_constant { return $this->definition; }
}

final class enum_definition_work extends declaration_work
{
	private collected_enum $definition;

	public function __construct(collected_enum $definition)
	{
		parent::__construct($definition->collection);
		$this->definition = $definition;
	}

	public function required_declaration(): collected_definition
	{
		return $this->definition;
	}

	public function enum_definition(): collected_enum
	{
		return $this->definition;
	}
}

final class record_definition_work extends declaration_work
{
	private collected_struct $definition;

	public function __construct(collected_struct $definition)
	{
		parent::__construct($definition->collection);
		$this->definition = $definition;
	}

	public function required_declaration(): collected_definition
	{
		return $this->definition;
	}

	public function record_definition(): collected_struct
	{
		return $this->definition;
	}
}

/** A body is an executable work unit, never a collected symbol. */
abstract class body_work extends preparation_owner
{
	/** Syntax owns this work; observe the exact executable unit, not its file root. */
	private function_body_node $body_reference /** weak<function_body_node> */;

	public function __construct(collected_file $source, function_body_node $body)
	{
		parent::__construct($source);
		$this->body_reference = $body;
	}

	public function executable_body(): function_body_node
	{
		return \weakref_get($this->body_reference);
	}

	/** Structural identity transfer only; preparation state remains analysis-owned. */
	public function rebind_body(function_body_node $body): void
	{
		$this->body_reference = $body;
	}
}

final class function_body_work extends body_work
{
	private collected_function $definition;

	public function __construct(collected_function $definition)
	{
		parent::__construct($definition->collection, $definition->syntax()->body);
		$this->definition = $definition;
	}

	public function declaration(): ?collected_definition
	{
		return $this->definition;
	}

	public function function_definition(): collected_function
	{
		return $this->definition;
	}

	public function kind(): preparation_kind
	{
		return preparation_kind::function_body;
	}
}

final class file_body_work extends body_work
{
	public function declaration(): ?collected_definition
	{
		return null;
	}

	public function kind(): preparation_kind
	{
		return preparation_kind::file_body;
	}
}

<?php

/* Retained typed work identities; algorithms remain in Preparation_Worker. */
namespace scpp\compiler;

/** Processing kinds have separate work lists; a body is not a language symbol. */
enum preparation_kind {
	case declaration;
	case anonymous_contract;
	case function_body;
	case file_body;
}

enum preparation_state {
	case pending;
	case processing;
	case ready;
}

enum preparation_lookup_kind {
	case property_name;
	case method_name;
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
	public \SplObjectStorage $dependencies /** hash<int, shared<contract_work>> */;
	public \SplObjectStorage $dependents /** hash<bool, shared<preparation_owner>> */;
	public \SplObjectStorage $lookups /** hash<bool, shared<preparation_lookup>> */;

	/** Retained graph data never references a processing worker. */
	public function __construct(collected_file $source)
	{
		$this->source = $source;
		$this->dependencies = new \SplObjectStorage /** hash<int, shared<contract_work>> */();
		$this->dependents = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		$this->lookups = new \SplObjectStorage /** hash<bool, shared<preparation_lookup>> */();
	}

	/** Computed compatibility category for backend fragment assembly. */
	abstract public function kind(): preparation_kind;

	/** Common optional declaration query; specialized accessors have distinct names. */
	abstract public function declaration(): ?collected_definition;
}

/** Independently settled facts are dependency targets, whether named or anonymous. */
abstract class contract_work extends preparation_owner
{
}

/** Named declaration contracts additionally expose their lookup/publication identity. */
abstract class declaration_work extends contract_work
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

final class source_callable_signature_work extends contract_work
{
	private collected_source_callable $definition;

	public function __construct(collected_source_callable $definition)
	{
		parent::__construct($definition->collection);
		$this->definition = $definition;
	}

	public function declaration(): ?collected_definition
	{
		return $this->definition;
	}

	public function kind(): preparation_kind
	{
		return preparation_kind::declaration;
	}

	public function callable_definition(): collected_source_callable
	{
		return $this->definition;
	}
}

final class anonymous_signature_work extends contract_work
{
	public ?prepared_source_callable $facts = null;
	/** Creation-site contract input, separate from authored syntax and resolved facts. */
	public ?canonical_type_use $expected_type = null;
	private anonymous_callable_identity $source_identity /** weak<anonymous_callable_identity> */;
	public function __construct(anonymous_callable_identity $identity)
	{
		parent::__construct($identity->collection());
		$this->source_identity = weak($identity);
	}
	public function anonymous_identity(): anonymous_callable_identity
	{
		return weakref_get($this->source_identity);
	}
	public function declaration(): ?collected_definition { return null; }
	public function kind(): preparation_kind { return preparation_kind::anonymous_contract; }
	public function require_facts(): prepared_source_callable { return $this->facts; }
}

final class constant_definition_work extends declaration_work
{
	private collected_constant $definition;

	public function __construct(collected_constant $definition)
	{
		parent::__construct($definition->collection);
		$this->definition = $definition;
	}
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

/** Independent type facts never acknowledge the record layout's source flag. */
abstract class type_semantic_work extends declaration_work
{
	public int $source_revision;
	private collected_member_owner $definition;

	public function __construct(collected_member_owner $definition)
	{
		parent::__construct($definition->collection);
		$this->source_revision = -1;
		$this->definition = $definition;
	}

	public function required_declaration(): collected_definition
	{
		return $this->definition;
	}

	public function member_definition(): collected_member_owner
	{
		return $this->definition;
	}
}

final class type_relationship_work extends type_semantic_work
{
	public ?prepared_type_relationships $facts = null;
	public function require_facts(): prepared_type_relationships
	{
		return $this->facts;
	}
}

final class type_contract_work extends type_semantic_work
{
	public ?prepared_type_contract $facts = null;
	public function require_facts(): prepared_type_contract { return $this->facts; }
}

/** Destruction admission is independent of layout and callable signatures. */
final class type_lifecycle_work extends type_semantic_work
{
	public ?prepared_type_lifecycle $facts = null;
	public function require_facts(): prepared_type_lifecycle { return $this->facts; }
}

final class record_definition_work extends declaration_work
{
	private collected_record $definition;

	public function __construct(collected_record $definition)
	{
		parent::__construct($definition->collection);
		$this->definition = $definition;
	}

	public function required_declaration(): collected_definition
	{
		return $this->definition;
	}

	public function record_definition(): collected_record
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
		$this->body_reference = weak($body);
	}

	public function executable_body(): function_body_node
	{
		return \weakref_get($this->body_reference);
	}

	/** Structural identity transfer only; preparation state remains analysis-owned. */
	public function rebind_body(function_body_node $body): void
	{
		$this->body_reference = weak($body);
	}
}

final class source_callable_body_work extends body_work
{
	private collected_source_callable $definition;

	public function __construct(collected_source_callable $definition)
	{
		parent::__construct($definition->collection, $definition->callable_syntax()->require_body());
		$this->definition = $definition;
	}

	public function declaration(): ?collected_definition
	{
		return $this->definition;
	}

	public function callable_definition(): collected_source_callable
	{
		return $this->definition;
	}

	public function kind(): preparation_kind
	{
		return preparation_kind::function_body;
	}
}

final class anonymous_body_work extends body_work
{
	/** Prepared creation-site inputs; never retain the enclosing transient environment. */
	public Storage $captures /** Storage<prepared_capture> */;
	private anonymous_callable_identity $source_identity /** weak<anonymous_callable_identity> */;
	public function __construct(anonymous_callable_identity $identity)
	{
		parent::__construct($identity->collection(), $identity->syntax()->require_body());
		$this->captures = new Storage /** Storage<prepared_capture> */();
		$this->source_identity = weak($identity);
	}
	public function anonymous_identity(): anonymous_callable_identity
	{
		return weakref_get($this->source_identity);
	}
	public function declaration(): ?collected_definition { return null; }
	public function kind(): preparation_kind { return preparation_kind::function_body; }
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

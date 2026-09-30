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
	case type;
	case function_name;
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

	/** Dispatch selects the work list; scheduling policy stays in the worker. */
	abstract public function enqueue(Preparation_Worker $worker): void;
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

	public function enqueue(Preparation_Worker $worker): void
	{
		$worker->enqueue_declaration($this);
	}

	abstract public function rebuild(Preparation_Worker $worker, preparation_context $context, \SplObjectStorage $previous /** hash<int, shared<declaration_work>> */): bool;

	abstract public function settle_members(Preparation_Worker $worker): void;
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

	public function rebuild(Preparation_Worker $worker, preparation_context $context, \SplObjectStorage $previous /** hash<int, shared<declaration_work>> */): bool
	{
		return $worker->prepare_function_signature($this, $context);
	}

	public function settle_members(Preparation_Worker $worker): void
	{
		$worker->settle_parameters($this->definition);
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

	public function rebuild(Preparation_Worker $worker, preparation_context $context, \SplObjectStorage $previous /** hash<int, shared<declaration_work>> */): bool
	{
		return $worker->prepare_record_definition($this, $context, $previous);
	}

	public function settle_members(Preparation_Worker $worker): void
	{
		$worker->settle_fields($this->definition);
	}
}

/** A body is an executable work unit, never a collected symbol. */
abstract class body_work extends preparation_owner
{
	abstract public function rebuild(Preparation_Worker $worker, preparation_context $context): void;
}

final class function_body_work extends body_work
{
	private collected_function $definition;

	public function __construct(collected_function $definition)
	{
		parent::__construct($definition->collection);
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

	public function enqueue(Preparation_Worker $worker): void
	{
		$worker->enqueue_function_body($this);
	}

	public function rebuild(Preparation_Worker $worker, preparation_context $context): void
	{
		$worker->prepare_function_body($this, $context);
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

	public function enqueue(Preparation_Worker $worker): void
	{
		$worker->enqueue_file_body($this);
	}

	public function rebuild(Preparation_Worker $worker, preparation_context $context): void
	{
		$worker->prepare_file_body($this, $context);
	}
}

<?php

/*
 * Role: backend-neutral preparation facts and invocation records.
 * Used by: File_Preparation and specialization-attached facts consumed by backends.
 */
namespace scpp\compiler;

/** Common backend-neutral result of preparing an expression. */
abstract class prepared_expression {
	public type_definition $type;
	/** True only for stable local/parameter storage and direct struct-field paths. */
	public bool $addressable = false;
}

/** Exact normalized decimal value; C++ spelling belongs to the backend. */
final class prepared_integer_literal extends prepared_expression {
	public string $decimal;
}

/** Exact decimal floating spelling; rounding belongs to the target representation. */
final class prepared_float_literal extends prepared_expression {
	public string $decimal;
}

/** Normalized boolean value shared by backends. */
final class prepared_boolean_literal extends prepared_expression {
	public bool $value;
}

/** A resolved reference always has a declaration; no literal fields belong here. */
final class prepared_variable_reference extends prepared_expression {
	/** @storage.reference collected_file.entries @reference.weak */
	public collected_name $declaration /** weak<collected_name> */;
}

/** Typed storage identity shared by bindings, parameters and fields; never an owning AST link. */
abstract class prepared_storage {
	/** @storage.reference collected_file.entries @reference.weak */
	public collected_name $declaration /** weak<collected_name> */;
	public type_definition $type;
}

/** A local binding or member write retains its resolved storage declaration and canonical type. */
final class prepared_binding extends prepared_storage {
	public binding_kind $resolved_kind;
}

/** Parameter facts add the source passing mode to the resolved storage identity. */
final class prepared_parameter extends prepared_storage {
	public passing_mode $mode;
}

/** Signatures are complete before bodies, permitting forward and recursive calls. */
final class prepared_function
{
	public type_definition $return_type;
	/** Ordered aliases of facts owned by parameter specializations. @storage.reference parameter_structure.prepared_facts */
	public Storage $parameters /** Storage<prepared_parameter> */;

	public function __construct()
	{
		$this->parameters = new Storage /** Storage<prepared_parameter> */();
	}
}

/** Field identity is scoped by its owning prepared record. */
final class prepared_field extends prepared_storage {
}

/** Field lookup retains declaration order and duplicate names without silently choosing one. */
final class prepared_record {
	/** Ordered aliases of facts owned by field specializations. @storage.reference field_structure.prepared_facts */
	public Key_Storage_List $fields /** Key_Storage_List<prepared_field> */;

	public function __construct()
	{
		$this->fields = new Key_Storage_List /** Key_Storage_List<prepared_field> */();
	}
}

/** Member access references the declaration facts without copying its field schema. */
final class prepared_field_access extends prepared_expression {
	/** @reference.source field_structure.prepared_facts */
	public prepared_field $field;
}

/** Resolved call target and signature; evaluation order remains with the process workers. */
final class prepared_call extends prepared_expression {
	/** @storage.reference collected_file.entries @reference.weak */
	public collected_name $declaration /** weak<collected_name> */;
	/** @reference.source function_structure.prepared_facts */
	public prepared_function $signature;
}

/** Completed file preparation; the source tree owns the actual facts. */
final class prepared_file {
	public collected_file $source;
}

/** Invocation-local preparation data; never published into AST records or source scopes. */
final class preparation_context
{
	public Preparation_Worker $worker;
	public preparation_owner $owner;
	public collected_file $collection;
	/** Invocation-local lookup of attached binding/parameter facts; does not mutate source scopes. */
	public Key_Storage_List $locals /** Key_Storage_List<prepared_storage> */;
	/** Null identifies the entry body; function bodies retain their declared return type. */
	public ?type_definition $return_type = null;
	public type_definition $integer;
	public type_definition $boolean;
	public type_definition $floating;
}

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
final class preparation_owner
{
	public preparation_kind $kind;
	public preparation_state $state = preparation_state::pending;
	/** Persistent work selection; only successful preparation settles it. */
	public change_state $change_status = change_state::added;
	public bool $failed = false;
	public string $failure_message = '';
	public collected_file $source;
	public ?collected_name $declaration = null;
	public int $version = 0;
	/** Strong identity keys; explicit cleanup severs registrations on replacement, deletion and reset. */
	public \SplObjectStorage $dependencies /** hash<int, shared<preparation_owner>> */;
	public \SplObjectStorage $dependents /** hash<bool, shared<preparation_owner>> */;
	public \SplObjectStorage $lookups /** hash<bool, shared<preparation_lookup>> */;

	/** Retained graph data never references a processing worker. */
	public function __construct(preparation_kind $kind, collected_file $source, ?collected_name $declaration = null)
	{
		$this->kind = $kind;
		$this->source = $source;
		$this->declaration = $declaration;
		$this->dependencies = new \SplObjectStorage /** hash<int, shared<preparation_owner>> */();
		$this->dependents = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		$this->lookups = new \SplObjectStorage /** hash<bool, shared<preparation_lookup>> */();
	}
}

/** Name-pool observation also represents absent or ambiguous lookup results. */
final class preparation_lookup
{
	public scope $scope;
	public string $name;
	public preparation_lookup_kind $kind;
	public array $candidates /** vector<collected_name> */ = [];
	public \SplObjectStorage $dependents /** hash<bool, shared<preparation_owner>> */;

	public function __construct(scope $scope, string $name, preparation_lookup_kind $kind)
	{
		$this->scope = $scope;
		$this->name = $name;
		$this->kind = $kind;
		$this->dependents = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
	}
}

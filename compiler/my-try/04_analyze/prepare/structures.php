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
	public collected_file $collection;
	/** Invocation-local lookup of attached binding/parameter facts; does not mutate source scopes. */
	public Key_Storage_List $locals /** Key_Storage_List<prepared_storage> */;
	/** Null identifies the entry body; function bodies retain their declared return type. */
	public ?type_definition $return_type = null;
	public type_definition $integer;
	public type_definition $boolean;
	public type_definition $floating;
}

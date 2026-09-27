<?php

/*
 * Role: backend-neutral preparation facts and invocation records.
 * Used by: File_Preparation and specialization-attached facts consumed by backends.
 */
namespace scpp\compiler;

/** Common backend-neutral result of preparing an expression. */
abstract class prepared_expression {
	public type_definition $type;
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

/** Facts owned by the binding specialization; declaration identity is a non-owning reference. */
final class prepared_binding {
	/** @storage.reference collected_file.entries @reference.weak */
	public collected_name $declaration /** weak<collected_name> */;
	public binding_kind $resolved_kind;
	public type_definition $type;
}

/** Completed file preparation; the source tree owns the actual facts. */
final class prepared_file {
	public collected_file $source;
}

/** Invocation-local preparation data; never published into AST records or source scopes. */
final class preparation_context {
	public collected_file $collection;
	public scope $locals;
	public type_definition $integer;
	public type_definition $boolean;
	public type_definition $floating;
}

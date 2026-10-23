<?php

/* Backend-neutral decisions for typed expression boundaries. */
namespace scpp\compiler;

enum conversion_context
{
	case assignment;
	case argument;
	case return_value;
	case explicit_cast;
	case operator_operand;
	case interpolation;
	case concatenation;
	case conditional_branch;
	case coalesce_value;
	case initializer_key;
	case container_element;
}

enum conversion_operation
{
	case callable_wrap;
	case identity;
	case integer_value_cast;
	case integer_to_floating;
	case explicit_runtime_cast;
	case text_conversion;
	case optional_payload_text;
	case required_payload_text;
	case nullable_empty;
	case ownership_empty;
	case ownership_upcast;
	case unique_upcast;
	case nullable_wrap;
	case nullable_extract;
	case nullable_map;
	case result_wrap;
	case result_error;
	case result_false;
	case result_true;
	case result_extract;
	case coalesce_selected_value;
	case boxed_wrap;
	case boxed_extract;
}

/** Transient analysis query; conversion facts never retain its worker or context. */
interface conversion_relationship_query
{
	public function is_callable_compatible(canonical_type_use $source, callable_type_definition $target): bool;
	public function is_subtype(canonical_type_use $source, canonical_type_use $target): bool;
	public function has_virtual_destruction(canonical_type_use $type): bool;
}

/** Retained semantic result of comparing one produced value with its consumer. */
final class conversion_decision
{
	/** Stable descriptive tag; enum-name lowering stays beside its declaration. */
	public function operation_name(): string { return enum_name($this->operation); }
	public canonical_type_use $source_type;
	public canonical_type_use $target_type;
	public canonical_type_use $result_type;
	public conversion_context $context;
	public conversion_operation $operation;
	/** Optional presence composition retains an independently validated payload conversion. */
	public ?conversion_decision $payload_conversion = null;

	public function requires_cast(): bool
	{
		return $this->operation !== conversion_operation::identity;
	}
}

/** Condition interpretation is not a value conversion or an explicit bool cast. */
enum truthiness_operation
{
	case boolean_identity;
	case scalar_condition;
	case nullable_condition;
	case value_presence;
	/** Probe presence includes the true sentinel; it does not imply a payload. */
	case sentinel_presence;
	case callable_presence;
	case result_condition;
	case error_condition;
	case boxed_condition;
	case always_true;
}

final class truthiness_decision
{
	public canonical_type_use $source_type;
	public truthiness_operation $operation;
}

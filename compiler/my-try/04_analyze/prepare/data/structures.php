<?php

/*
 * Role: backend-neutral preparation facts and invocation records.
 * Used by: File_Preparation and specialization-attached facts consumed by backends.
 */
namespace scpp\compiler;

/** Common backend-neutral result of preparing an expression. */
abstract class prepared_expression {
	public canonical_type_use $type;
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

/** Decoded binary-safe bytes; source quoting and C++ escaping are not semantic facts. */
final class prepared_string_literal extends prepared_expression {
	public string $value;
}

/** Parts own their facts; the enclosing interpolation produces a non-addressable string. */
final class prepared_interpolated_string extends prepared_expression {
}

/** Conversion is attached to the insertion that requires it, not a synthetic cast. */
final class prepared_interpolation_value extends prepared_expression {
	public conversion_decision $conversion;
}

/** A resolved reference always has a declaration; no literal fields belong here. */
final class prepared_variable_reference extends prepared_expression {
	/** @storage.reference collected_file.entries @reference.weak */
	public collected_name $declaration /** weak<collected_name> */;
}

/** Resolved immutable definition; its concrete value remains owned by the language scope. */
final class prepared_constant_reference extends prepared_expression {
	/** @reference.source model.language_scope */
	public constant_definition $definition;
}

/** Explicit source cast retains the central conversion decision and its result type. */
final class prepared_cast_expression extends prepared_expression
{
	public conversion_decision $conversion;
}

/** Compound updates retain computation and the conversion back into resolved storage. */
final class prepared_compound_assignment_expression extends prepared_expression {
	public operator_decision $decision;
	public conversion_decision $write_back;
}

/** Mutation returns a value snapshot; target syntax retains its resolved storage identity. */
final class prepared_mutation_expression extends prepared_expression {
	public operator_decision $decision;
}

/** Unary syntax retains the common selected operator decision. */
final class prepared_unary_expression extends prepared_expression {
	public operator_decision $decision;
}

/** Binary syntax retains the common selected operator decision. */
final class prepared_binary_expression extends prepared_expression {
	public operator_decision $decision;
}

/** Typed storage identity shared by bindings, parameters and fields; never an owning AST link. */
abstract class prepared_storage {
	/** @storage.reference collected_file.entries @reference.weak */
	public collected_name $declaration /** weak<collected_name> */;
	public canonical_type_use $type;
}

/** A local binding or member write retains its resolved storage declaration and canonical type. */
final class prepared_binding extends prepared_storage {
	public binding_kind $resolved_kind;
	/** Null only when an inferred declaration has no pre-existing expected type. */
	public ?conversion_decision $conversion = null;
}

/** Parameter facts add the source passing mode to the resolved storage identity. */
final class prepared_parameter extends prepared_storage {
	public passing_mode $mode;
}

/** Signatures are complete before bodies, permitting forward and recursive calls. */
final class prepared_function
{
	public canonical_type_use $return_type;
	/** Ordered aliases of facts owned by parameter specializations. @storage.reference parameter_node.prepared_facts */
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
	/** Derived only after every field has completed preparation. */
	public array $capabilities /** vector<generic_contract> */ = [];

	/** Ordered aliases of facts owned by field specializations. @storage.reference field_node.prepared_facts */
	public Key_Storage_List $fields /** Key_Storage_List<prepared_field> */;

	public function __construct()
	{
		$this->fields = new Key_Storage_List /** Key_Storage_List<prepared_field> */();
	}
	public function has_capability(generic_contract $capability): bool
	{
		foreach ($this->capabilities as $available) {
			if ($available === $capability) {
				return true;
			}
		}
		return false;
	}

}

/** Member access references the declaration facts without copying its field schema. */
final class prepared_field_access extends prepared_expression {
	/** @reference.source field_node.prepared_facts */
	public prepared_field $field;
}

/** Resolved call target and signature; evaluation order remains with the process workers. */
final class prepared_call extends prepared_expression
{
	/** @storage.reference collected_file.entries @reference.weak */
	public collected_function $declaration /** weak<collected_function> */;
	/** @reference.source function_node.prepared_facts */
	public prepared_function $signature;
	/** One boundary per source argument, aligned with syntax and signature positions. */
	public Storage $arguments /** Storage<prepared_call_argument> */;

	public function __construct()
	{
		$this->arguments = new Storage /** Storage<prepared_call_argument> */();
	}
}

/** A reference argument has no conversion; a by-value argument requires one decision. */
final class prepared_call_argument
{
	public ?conversion_decision $conversion = null;

	public function require_conversion(): conversion_decision
	{
		if ($this->conversion === null) {
			throw new \LogicException('Prepared by-value argument has no conversion decision');
		}
		return $this->conversion;
	}
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
	public local_environment $locals;
	/** Nearest legal targets; restored when leaving a structured construct. */
	public ?breakable_node $break_target = null;
	public ?loop_node $continue_target = null;
	/** Null identifies the entry body; function bodies retain their declared return type. */
	public ?canonical_type_use $return_type = null;
	/** Only a statement-root assignment and its direct RHS chain may introduce locals. */
	public bool $statement_assignment = false;
	public canonical_type_use $integer;
	public canonical_type_use $boolean;
	public canonical_type_use $floating;
	public canonical_type_use $string_type;
}

/** Transient completion summary; describes normal exit, independently of return requirements. */
final class statement_completion
{
	public bool $can_fall_through;
	public bool $can_return = false;
	public bool $can_break = false;
	public bool $can_continue = false;

	public function __construct(bool $can_fall_through)
	{
		$this->can_fall_through = $can_fall_through;
	}
}

/** A missing test denotes the omitted for condition, which always continues. */
final class prepared_loop
{
	public ?prepared_condition $condition = null;
}

/** Default has no value; every case retains its normalized exact signed decimal. */
final class prepared_switch_label
{
	public ?string $decimal = null;
}

/** Semantic transfer identity; the enclosing syntax tree owns its target. */
final class prepared_control_transfer
{
	public control_transfer_kind $transfer_kind;
	/** @reference.weak @reference.source enclosing syntax */
	public breakable_node $target /** weak<breakable_node> */;
}

/** Condition consumers retain the central conversion decision, including identity. */
final class prepared_condition
{
	public conversion_decision $conversion;
}

/** Invocation-local lexical membership; entries alias facts owned by syntax/signatures. */
final class local_environment
{
	public ?local_environment $parent;
	public Key_Storage_List $bindings /** Key_Storage_List<prepared_storage> */;
	/** A typed declaration reserves its name while preparing its initializer. */
	public ?string $initializing_name = null;

	public function __construct(?local_environment $parent)
	{
		$this->parent = $parent;
		$this->bindings = new Key_Storage_List /** Key_Storage_List<prepared_storage> */();
	}

	public function local_named(string $name): array /** vector<prepared_storage> */
	{
		$bindings /** Key_Storage_List<prepared_storage> */ = $this->bindings;
		return $bindings->named($name);
	}

	/** Nearest membership wins; a reserved declaration hides its outer names immediately. */
	public function named(string $name): array /** vector<prepared_storage> */
	{
		if ($this->initializing_name === $name) {
			throw new \RuntimeException('S2S local ' . $name . ' cannot read or write itself in its initializer');
		}
		$result /** vector<prepared_storage> */ = $this->local_named($name);
		if (q_count($result) !== 0) {
			return $result;
		}
		if ($this->parent !== null) {
			$parent /** local_environment */ = $this->parent;
			return $parent->named($name);
		}
		return $result;
	}

	public function add(string $name, prepared_storage $storage): void
	{
		$bindings /** Key_Storage_List<prepared_storage> */ = $this->bindings;
		$bindings->add($name, $storage);
	}
}

/** Name-pool observation also represents absent or ambiguous lookup results. */
final class preparation_lookup
{
	public scope $lookup_scope;
	public string $name;
	public preparation_lookup_kind $kind;
	public array $candidates /** vector<preparation_lookup_candidate_i> */ = [];
	public \SplObjectStorage $dependents /** hash<bool, shared<preparation_owner>> */;

	public function __construct(scope $lookup_scope, string $name, preparation_lookup_kind $kind)
	{
		$this->lookup_scope = $lookup_scope;
		$this->name = $name;
		$this->kind = $kind;
		$this->dependents = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
	}
}

/**
 * Expression facts for a write. Reuse prepared_binding for storage identity and
 * declaration-versus-reassignment outcome; inherited type describes the result.
 */
final class prepared_assignment extends prepared_expression
{
	/** @ownership owner */
	public prepared_binding $binding;
}

/** A typed value return owns a decision; void and program-entry returns do not. */
final class prepared_return
{
	public ?conversion_decision $conversion = null;

	public function require_conversion(): conversion_decision
	{
		if ($this->conversion === null) {
			throw new \LogicException('Prepared typed return has no conversion decision');
		}
		return $this->conversion;
	}
}

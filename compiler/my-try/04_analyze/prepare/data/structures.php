<?php

/*
 * Role: backend-neutral preparation facts and invocation records.
 * Used by: File_Preparation and specialization-attached facts consumed by backends.
 */
namespace scpp\compiler;

enum expression_value_origin
{
	case ordinary;
	case fresh_construction;
	case explicit_transfer;
	case owned_call_result;
}

/** The immediate consumer selects admission; this does not propagate into child expressions. */
enum expression_consumer
{
	case value;
	case local_storage;
	case typed_local_initialization;
	case consuming_argument;
	case consuming_return;
}

/** Storage admission is independent of type conversion and runtime representation. */
enum value_boundary_operation
{
	case copy_value;
	case consume_value;
	case bind_reference;
	case construct_empty;
}

/** Common backend-neutral result of preparing an expression. */
abstract class prepared_expression {
	public canonical_type_use $type;
	public expression_value_origin $value_origin = expression_value_origin::ordinary;
	/** Absent for values; present only for whole storage or stable direct struct fields. */
	public ?prepared_place $place = null;

	/** Fresh construction can initialize exact storage without copying an existing object. */
	public function is_fresh_value(): bool
	{
		return $this->value_origin === expression_value_origin::fresh_construction;
	}

	public function addressable(): bool
	{
		return $this->place !== null;
	}

	public function is_owned_temporary(): bool
	{
		return ($this->value_origin === expression_value_origin::fresh_construction)
			|| ($this->value_origin === expression_value_origin::explicit_transfer)
			|| ($this->value_origin === expression_value_origin::owned_call_result);
	}
}

/** Source place is observed, not exported as the move result's addressability. */
final class prepared_move extends prepared_expression
{
	public prepared_place $source_place;
}

/** Handle-valued observation result, never a place or an implicit pointee conversion. */
final class prepared_ownership_observation extends prepared_expression
{
	public ownership_observation_kind $operation;
}

/** A storage selection, not an owning payload or a certificate that a borrow may escape. */
final class prepared_place
{
	/** @reference.source declaration/parameter/field preparation facts */
	private prepared_storage $selected_storage /** weak<prepared_storage> */;
	/** A field projection shares its base place, never syntax or a transient environment. */
	private ?prepared_place $base_place = null;

	/** Whole roots are declarations/parameters; only direct fields may project from a base. */
	public function __construct(prepared_storage $storage, ?prepared_place $base = null,
		private bool $heap_projection = false)
	{
		if ($base === null)
		{
			if ($storage instanceof prepared_binding) {
				$binding = object_cast($storage, prepared_binding::class);
				if ($binding->resolved_kind !== binding_kind::declaration) {
					throw new \LogicException('A storage root must be a declaration, not a write');
				}
				if ($binding->alias_target !== null) {
					throw new \LogicException('An alias must reuse its original storage place');
				}
			}
			elseif (!($storage instanceof prepared_parameter)) {
				throw new \LogicException('A storage root requires a local or parameter');
			}
		}
		elseif (!($storage instanceof prepared_field)) {
			throw new \LogicException('A storage projection requires a direct struct field');
		}
		$this->selected_storage = $storage;
		$this->base_place = $base;
	}

	/** Writable heap projections must never escape as native aliases or reference arguments. */
	public function borrowable(): bool
	{
		if ($this->heap_projection) {
			return false;
		}
		if ($this->base_place !== null) {
			return $this->base_place->borrowable();
		}
		return true;
	}

	public function storage(): prepared_storage
	{
		return weakref_get($this->selected_storage);
	}

	public function base(): ?prepared_place
	{
		return $this->base_place;
	}

	/** Aliases share this place; field projections preserve access from the original root. */
	public function access(): storage_access
	{
		$root = $this->root_storage();
		if ($root instanceof prepared_parameter) {
			return object_cast($root, prepared_parameter::class)->contract->access;
		}
		return storage_access::writable;
	}

	/** Identity query only; escape and lifetime admission belong to analysis workers. */
	public function root_storage(): prepared_storage
	{
		if ($this->base_place !== null) {
			$base /** prepared_place */ = $this->base_place;
			return $base->root_storage();
		}
		return $this->storage();
	}
}

/** A selected bracket read is a copied value, never an interior storage location. */
final class prepared_index_expression extends prepared_expression
{
	public operator_decision $decision;
}

/** Settled default allocation: constructed object identity and independently selected handle type. */
final class prepared_object_construction extends prepared_expression
{
	public canonical_type_use $constructed_type;
	public record_storage_kind $storage;
}

/** Syntax owns entry order; the binding owns the selected factory contract. */
final class prepared_data_construction extends prepared_expression
{
	public initialization_binding $binding;
}

/** Conversions belong to the consuming entry, not synthetic cast syntax. */
final class prepared_data_entry
{
	public ?conversion_decision $key_conversion = null;
	public conversion_decision $value_conversion;
}

enum take_operation { case nullable_value; case false_result; case error_result; case boolean_result; }

final class prepared_take extends prepared_expression
{
	public take_operation $operation;
}

/** Constant expression values used by defaults; not every constant is a literal. */
abstract class prepared_constant_expression extends prepared_expression {
	public abstract function value_key(): string;
}

/** Literal identity is its canonical type and local value key, not source position. */
abstract class prepared_literal extends prepared_constant_expression {
	public abstract function literal_key(): string;
	public function value_key(): string { return $this->literal_key(); }
}

final class prepared_null_literal extends prepared_literal {
	public function literal_key(): string { return 'null'; }
}

/** Exact normalized decimal value; C++ spelling belongs to the backend. */
final class prepared_integer_literal extends prepared_literal {
	public string $decimal;
	public function literal_key(): string { return $this->decimal; }
}

/** Exact decimal floating spelling; rounding belongs to the target representation. */
final class prepared_float_literal extends prepared_literal {
	public string $decimal;
	public function literal_key(): string { return $this->decimal; }
}

/** Normalized boolean value shared by backends. */
final class prepared_boolean_literal extends prepared_literal {
	public bool $value;
	public function literal_key(): string { return $this->value ? '1' : '0'; }
}

/** Decoded binary-safe bytes; source quoting and C++ escaping are not semantic facts. */
final class prepared_string_literal extends prepared_literal {
	public string $value;
	public function literal_key(): string { return $this->value; }
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

/** Resolved target identity and immutable value snapshot; no backend spelling. */
final class prepared_constant_reference extends prepared_expression {
	/** Target owner is a published scope or source collection, not the reference. */
	public constant_target_i $target /** weak<constant_target_i> */;
	public constant_value $value;
}

/** Admitted one-value query; distinct helper semantics are retained for emission. */
final class prepared_value_probe extends prepared_expression
{
	public value_probe_kind $operation;
	public ?prepared_keyed_path $path = null;
}

/** Non-creating lookup recipe; workers select probing versus removal. */
final class prepared_keyed_path
{
	/** @reference.source keyed syntax graph */
	public expression_node $root;
	/** @storage.owner */
	public Storage $steps /** Storage<prepared_keyed_step> */;
	public function __construct()
	{
		$this->steps = new Storage /** Storage<prepared_keyed_step> */();
	}
}

/** A cleanup either replaces whole storage or removes an existing keyed slot. */
abstract class prepared_cleanup {
	abstract public function storage_type(): ?canonical_type_use;
}

final class prepared_storage_reset extends prepared_cleanup
{
	public function __construct(private canonical_type_use $target_type)
	{
	}

	public function storage_type(): ?canonical_type_use
	{
		return $this->target_type;
	}
}

final class prepared_keyed_removal extends prepared_cleanup
{
	public function __construct(public prepared_keyed_path $path)
	{
	}

	public function storage_type(): ?canonical_type_use
	{
		return null;
	}
}

final class prepared_keyed_step
{
	/** @reference.source keyed syntax graph */
	public index_node $syntax;
	public canonical_type_use $element_type;
	public conversion_decision $key_conversion;
}

/** Presence interpretation is independent of the selected branch conversion. */
enum coalesce_presence
{
	case always;
	case wrapper_value;
	case boxed_nonnull;
}

/** Owned selected-value domain; wrapper extraction occurs only after presence. */
final class prepared_coalesce_expression extends prepared_expression
{
	public coalesce_presence $presence;
	/** Source to selected-value domain; identity for direct scalar/mixed inputs. */
	public conversion_decision $left_extraction;
	/** Selected-value domain to the joined result; independent of extraction. */
	public conversion_decision $left_conversion;
	public conversion_decision $fallback_conversion;
}

/** Both ternary spellings share condition interpretation and an explicit value-result join. */
final class prepared_conditional_expression extends prepared_expression
{
	public truthiness_decision $truthiness;
	public conversion_decision $true_conversion;
	public conversion_decision $fallback_conversion;
}

/** The enclosing match owns the value type; each arm owns its selection facts. */
final class prepared_match_expression extends prepared_expression
{
}

final class prepared_match_arm
{
	public array $labels /** vector<prepared_selection_constant> */ = [];
	public conversion_decision $conversion;
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
	public compound_write_back $write_back_mode;
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
	abstract public function storage_type(): canonical_type_use;
}

/** A local binding or member write retains its resolved storage declaration and canonical type. */
final class prepared_binding extends prepared_storage {
	public ?value_boundary_operation $boundary = null;
	/** Alias declarations retain the original place, never a second owning root. */
	public ?prepared_place $alias_target = null;
	public canonical_type_use $type;
	public binding_kind $resolved_kind;
	/** Absent for alias bindings, uninitialized storage and inferred value declarations. */
	public ?conversion_decision $conversion = null;
	public function storage_type(): canonical_type_use { return $this->type; }
}

/** Source storage refers to the same contract consumed by calls, without duplicating its type. */
final class prepared_parameter extends prepared_storage {
	public callable_parameter $contract;
	public function storage_type(): canonical_type_use { return $this->contract->type; }
}

/** Normalized scalar literal snapshot; never retains mutable default syntax. */
final class prepared_parameter_default {
	public prepared_constant_expression $value;
	public conversion_decision $conversion;
}

/** Signatures are complete before bodies, permitting forward and recursive calls. */
final class prepared_function
{
	public callable_signature $signature;
	/** Ordered aliases of facts owned by parameter specializations. @storage.reference parameter_node.prepared_facts */
	public Storage $parameters /** Storage<prepared_parameter> */;

	public function __construct()
	{
		$this->signature = new callable_signature();
		$this->parameters = new Storage /** Storage<prepared_parameter> */();
	}
}

/** Field identity is scoped by its owning prepared record. */
final class prepared_field extends prepared_storage {
	public canonical_type_use $type;
	public function storage_type(): canonical_type_use { return $this->type; }
}

/** Field lookup retains declaration order and duplicate names without silently choosing one. */
final class prepared_record {
	/** Derived only after every field has completed preparation. */
	public array $capabilities /** vector<generic_contract> */ = [];

	/** Ordered aliases of facts owned by field specializations. @storage.reference property_node.prepared_facts */
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
	/** @reference.source property_node.prepared_facts */
	public prepared_field $field;
	public canonical_type_use $record_type;
	public record_storage_kind $receiver_storage;
}

/** Resolved call target and signature; evaluation order remains with the process workers. */
final class prepared_call extends prepared_expression
{
	/** Fixed prefix length; the remaining source arguments belong to variadic_parameter. */
	public int $fixed_count = 0;
	public ?callable_parameter $variadic_parameter = null;
	/** Owned by collection, runtime catalog or language scope. @reference.weak */
	public callable_definition_i $target /** weak<callable_definition_i> */;
	/** Call-owned specialization; no worker, syntax or target backlink. */
	public ?prepared_compiler_call $specialization = null;
	/** Completed fixed or argument-selected compiler signature. */
	public callable_signature $signature;
	/** One boundary per explicit source argument, including each variadic element. */
	public Storage $arguments /** Storage<prepared_call_argument> */;

	public function __construct()
	{
		$this->arguments = new Storage /** Storage<prepared_call_argument> */();
	}
}

/** A reference argument has no conversion; a by-value argument requires one decision. */
final class prepared_call_argument
{
	/** Runtime argument's position in the original syntax; compile-time inputs are omitted. */
	public int $source_index;
	public value_boundary_operation $boundary;
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
	/** Selected by analysis, never by a backend; syntax remains the owner. */
	public ?function_body_node $entry_body /** weak<function_body_node> */ = null;

	public function executable_body(): function_body_node
	{
		$body = \weakref_get($this->entry_body);
		if ($body === null) {
			throw new \RuntimeException('Executable body requires completed file preparation');
		}
		return $body;
	}
}

/** Invocation-local call-cycle vertex; integer edges do not retain other vertices. */
final class reference_cycle_vertex
{
	public collected_function $definition;
	public array $calls /** vector<int> */ = [];
	public int $visit_index;
	public int $low_link;
	public bool $on_stack = false;

	public function __construct(collected_function $definition)
	{
		$this->definition = $definition;
		$this->visit_index = -1;
		$this->low_link = -1;
	}
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
	/** Body aliases use lexical scope; loop-header alias declarations remain gated. */
	public bool $alias_declarations_allowed = true;
	/** Transient whole-parameter origin obligation shared by every return. */
	public ?prepared_parameter $reference_return_parameter = null;
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

/** Foreach carries iteration facts; other loops with no condition run until transfer. */
final class prepared_loop
{
	public ?prepared_condition $condition = null;
	public ?prepared_iteration $iteration = null;
}

/** Loop-owned binding facts refer to registry signatures and existing storage identities. */
final class prepared_iteration
{
	public iteration_declaration $contract;
	public canonical_type_use $key_type;
	public canonical_type_use $value_type;
	public ?prepared_binding $key = null;
	public prepared_binding $value;
}

/** Default has no value; every case retains one typed selection constant. */
final class prepared_switch_label
{
	public ?prepared_selection_constant $value = null;
}

/** Semantic transfer identity; the enclosing syntax tree owns its target. */
final class prepared_control_transfer
{
	public control_transfer_kind $transfer_kind;
	/** @reference.weak @reference.source enclosing syntax */
	public breakable_node $target /** weak<breakable_node> */;
}

/** All condition consumers retain the same truthiness decision, distinct from casts. */
final class prepared_condition
{
	public truthiness_decision $truthiness;
}

/** Invocation-local lexical membership; entries alias facts owned by syntax/signatures. */
final class local_environment
{
	public ?local_environment $parent = null;
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
 * Common value-result facts for storage assignments and selected element writes.
 */
abstract class prepared_assignment_expression extends prepared_expression
{
	/** Read the actual write destination without inferring native-reference eligibility. */
	abstract public function destination_type(): canonical_type_use;
}

/** Storage identity and declaration-versus-reassignment outcome. */
final class prepared_assignment extends prepared_assignment_expression
{
	/** @ownership owner */
	public prepared_binding $binding;

	public function destination_type(): canonical_type_use
	{
		return $this->binding->storage_type();
	}
}

/** Element writes select operations rather than pretending to declare native storage. */
final class prepared_index_assignment extends prepared_assignment_expression
{
	/** @reference.source assignment_expression_node.target (syntax graph) */
	public variable_reference_node $root;
	/** @storage.owner */
	public Storage $path /** Storage<prepared_index_path_step> */;
	public operator_decision $decision;
	public conversion_decision $value_conversion;

	public function __construct()
	{
		$this->path = new Storage /** Storage<prepared_index_path_step> */();
	}

	public function destination_type(): canonical_type_use
	{
		return $this->value_conversion->target_type;
	}
}

/** Ordered semantic traversal facts; no copied read or native slot binding. */
final class prepared_index_path_step
{
	/** @reference.source assignment_expression_node.target (syntax graph) */
	public index_node $syntax;
	public conversion_decision $key_conversion;
	public canonical_type_use $element_type;
}

/** A typed value return owns a decision; void and program-entry returns do not. */
final class prepared_return
{
	public ?value_boundary_operation $boundary = null;
	public ?conversion_decision $conversion = null;
	/** A verified reference return bypasses value conversion and copying. */
	public ?prepared_place $reference_target = null;

	public function require_conversion(): conversion_decision
	{
		if ($this->conversion === null) {
			throw new \LogicException('Prepared typed return has no conversion decision');
		}
		return $this->conversion;
	}
}

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
	case owned_selection_result;
}

/** The immediate consumer selects admission; this does not propagate into child expressions. */
enum expression_consumer
{
	case await_operand;
	case value;
	case local_storage;
	case typed_local_initialization;
	case consuming_argument;
	case consuming_return;
	case selection_branch;
}

/** Storage admission is independent of type conversion and runtime representation. */
enum value_boundary_operation
{
	case copy_value;
	case consume_value;
	case bind_reference;
	case observe_value;
	case construct_empty;
}

/** Common backend-neutral result of preparing an expression. */
abstract class prepared_expression {
	/** True only when this expression can suspend its enclosing async invocation. */
	public bool $suspends = false;
	public canonical_type_use $type;
	public expression_value_origin $value_origin = expression_value_origin::ordinary;
	/** Absent for values; present for whole storage and prepared property projections. */
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
			|| ($this->value_origin === expression_value_origin::owned_call_result)
			|| ($this->value_origin === expression_value_origin::owned_selection_result);
	}
}

/** Concrete anonymous value; callable contract ownership remains on its source identity. */
final class prepared_closure extends prepared_expression {}

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

/** Settled object access contract; backend never rediscovers ancestry or RTTI support. */
final class prepared_object_type extends prepared_expression
{
	public object_type_operation $operation;
	public canonical_type_use $target_payload;
}

/** A storage selection, not an owning payload or a certificate that a borrow may escape. */
final class prepared_place
{
	/** @reference.source declaration/parameter/field preparation facts */
	private prepared_storage $selected_storage /** weak<prepared_storage> */;
	/** A field projection shares its base place, never syntax or a transient environment. */
	private ?prepared_place $base_place = null;

	/** Whole roots include type-owned fields; instance fields require a base. */
	public function __construct(prepared_storage $storage, ?prepared_place $base = null,
		private bool $borrow_restricted = false, private ?storage_access $projected_access = null)
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
			elseif ($storage instanceof prepared_property_storage) {
				if (!object_cast($storage, prepared_property_storage::class)->static_member) { throw new \LogicException('A field storage root requires type-owned storage'); }
			}
			elseif (!($storage instanceof prepared_parameter) && !($storage instanceof prepared_receiver) && !($storage instanceof prepared_capture)) {
				throw new \LogicException('A storage root requires a local or parameter');
			}
		}
		elseif (!($storage instanceof prepared_property_storage)) {
			throw new \LogicException('A storage projection requires a direct struct field');
		}
		$this->selected_storage = weak($storage);
		$this->base_place = $base;
	}

	/** Heap projections and currently gated roots cannot escape as native borrows. */
	public function borrowable(): bool
	{
		if ($this->borrow_restricted) {
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
		$selected = $this->storage();
		if ($selected instanceof prepared_property_storage) {
			if (object_cast($selected, prepared_property_storage::class)->access === storage_access::read_only) {
				return storage_access::read_only;
			}
		}
		if ($this->projected_access !== null) { return $this->projected_access; }
		if ($this->base_place !== null) { return $this->base_place->access(); }
		$root = $this->root_storage();
		if ($root instanceof prepared_capture) { return object_cast($root, prepared_capture::class)->access; }
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
	public ?prepared_call $constructor_call = null;
	/** Provider factories share runtime call admission and physical transport. */
	public ?prepared_call $runtime_factory = null;
}

/** One syntax family, with distinct settled sequence-factory and typed-record contracts. */
abstract class prepared_data_construction extends prepared_expression
{
}

final class prepared_sequence_construction extends prepared_data_construction
{
	public initialization_binding $binding;
}

/** A source-owned heterogeneous input schema, independent of the selected storage. */
final class record_initialization_contract
{
	public ?collected_method $constructor /** weak<collected_method> */ = null;
	/** Acyclic settled base contract; local input membership remains local. */
	public ?record_initialization_contract $base_contract = null;
	public ?canonical_type_use $base_type = null;
	public Key_Storage_List $inputs /** Key_Storage_List<record_initialization_input> */;
	public function __construct()
	{
		$this->inputs = new Key_Storage_List /** Key_Storage_List<record_initialization_input> */();
	}
	/** Transient ordered view, retaining original input/default identities. */
	public function inputs_in_order(): array /** vector<record_initialization_input> */
	{
		$result /** vector<record_initialization_input> */ = [];
		if ($this->base_contract !== null) {
			foreach ($this->base_contract->inputs_in_order() as $input) { $result[] = $input; }
		}
		$local /** Key_Storage_List<record_initialization_input> */ = $this->inputs;
		foreach ($local->items() as $input) { $result[] = $input; }
		return $result;
	}
}

/** Named typed input; a property link is present only for synthesized member initialization. */
final class record_initialization_input
{
	public function __construct(public string $name, public callable_parameter $parameter, public ?prepared_field $field = null) {}
}

final class prepared_record_construction extends prepared_data_construction
{
	public record_initialization_contract $contract;
	public canonical_type_use $payload_type;
	public record_storage_kind $storage;
}

/** Conversions belong to the consuming entry, not synthetic cast syntax. */
final class prepared_data_entry
{
	public ?conversion_decision $key_conversion = null;
	public conversion_decision $value_conversion;
	/** Record input index; sequence entries retain their source position instead. */
	public int $input_position = -1;
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
	public ?prepared_call $runtime_call = null;
	public value_probe_kind $operation;
	public ?prepared_keyed_path $path = null;
	/** Present when this type's isset contract is exactly its condition/presence operation. */
	public ?truthiness_decision $presence = null;
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
	case handle_presence;
}

/** Owned selected-value domain; wrapper extraction occurs only after presence. */
final class prepared_coalesce_expression extends prepared_expression
{
	public coalesce_presence $presence;
	public value_boundary_operation $left_boundary;
	public value_boundary_operation $fallback_boundary;
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
	public value_boundary_operation $true_boundary;
	public value_boundary_operation $fallback_boundary;
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
	public ?prepared_index_target $indexed_target = null;
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
	abstract public function storage_type(): canonical_type_use;
}

/** Only source-owned storage carries a collected declaration. */
abstract class prepared_source_storage extends prepared_storage {
	/** @storage.reference collected_file.entries @reference.weak */
	public collected_name $declaration /** weak<collected_name> */;
}

enum capture_transfer { case forbidden; case owned_value; case callable_value; }

/** A capture binding is distinct from its source, whether copied or borrowed. */
final class prepared_capture extends prepared_source_storage {
	public canonical_type_use $type;
	public ?storage_borrow $borrow = null;
	public capture_transfer $transfer = capture_transfer::forbidden;
	public storage_access $access = storage_access::read_only;
	public function storage_type(): canonical_type_use { return $this->type; }
	/** Receiver role is derived from the borrow origin, never a duplicated kind flag. */
	public function receiver(): ?prepared_receiver
	{
		if ($this->borrow === null) { return null; }
		$origin = $this->borrow->place->root_storage();
		if ($origin instanceof prepared_receiver) { return object_cast($origin, prepared_receiver::class); }
		return null;
	}
}

/** A local declaration or reassignment retains its source identity and canonical type. */
final class prepared_binding extends prepared_source_storage {
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
final class prepared_parameter extends prepared_source_storage {
	public callable_parameter $contract;
	public ?prepared_field $initialized_property = null;
	public function storage_type(): canonical_type_use { return $this->contract->type; }
}

/** An instance receiver is not a source parameter or an owned local copy. */
final class prepared_receiver extends prepared_source_storage
{
	public canonical_type_use $type;
	public function storage_type(): canonical_type_use { return $this->type; }
}

/** Prepared parameter defaults expose their resulting type across concrete representations. */
abstract class prepared_parameter_default {
	abstract public function result_type(): canonical_type_use;
}

final class prepared_literal_parameter_default extends prepared_parameter_default {
	public prepared_constant_expression $value;
	public conversion_decision $conversion;
	public function result_type(): canonical_type_use { return $this->conversion->target_type; }
}

final class prepared_property_parameter_default extends prepared_parameter_default {
	public function __construct(public prepared_property_default $value) {}
	public function result_type(): canonical_type_use { return $this->value->conversion->target_type; }
}

/** Only synthesized public-property inputs use the type's implicit default. */
final class prepared_type_parameter_default extends prepared_parameter_default {
	public function __construct(public canonical_type_use $type) {}
	public function result_type(): canonical_type_use { return $this->type; }
}

/** Settled access is independent of syntax modifiers and of C++ spelling. */
enum member_visibility
{
	case public_access;
	case protected_access;
	case private_access;
}

/** Constant visibility participates in dependency equivalence alongside its value. */
final class prepared_source_constant
{
	public function __construct(public constant_value $value, public member_visibility $visibility) {}
}

/** Signatures are complete before bodies, permitting forward and recursive calls. */
final class prepared_source_callable
{
	public bool $abstract_method = false;
	public bool $final_method = false;
	public bool $override_assertion = false;
	public bool $static_member = false;
	public member_visibility $visibility = member_visibility::public_access;
	public callable_signature $signature;
	public ?prepared_receiver $receiver = null;
	public ?record_initialization_contract $initialization = null;
	/** Ordered aliases of facts owned by parameter specializations. @storage.reference parameter_node.prepared_facts */
	public Storage $parameters /** Storage<prepared_parameter> */;

	public function __construct()
	{
		$this->signature = new callable_signature();
		$this->parameters = new Storage /** Storage<prepared_parameter> */();
	}
}

/** Property storage has a type and location independently of source syntax. */
abstract class prepared_property_storage extends prepared_storage {
	public bool $static_member = false;
	public canonical_type_use $type;
	public storage_access $access = storage_access::writable;
	public function storage_type(): canonical_type_use { return $this->type; }
}

/** Materialized provider property storage; retains its one registered recipe. */
final class registered_property extends prepared_property_storage
{
	public function __construct(public runtime_property_definition $definition,
		public canonical_type_use $owner_type) {}
}

/** Source field identity is scoped by its owning prepared record. */
final class prepared_field extends prepared_property_storage {
	/** @storage.reference collected_file.entries @reference.weak */
	public collected_name $declaration /** weak<collected_name> */;
	public member_visibility $visibility = member_visibility::public_access;
	public ?prepared_property_default $default_value = null;
}

/** Declaration-owned typed default boundary; concrete forms distinguish constants from construction. */
abstract class prepared_property_default
{
	public conversion_decision $conversion;
	abstract public function value_type(): canonical_type_use;
}

final class prepared_constant_property_default extends prepared_property_default
{
	public constant_value $value;
	public function value_type(): canonical_type_use { return $this->value->type; }
}

/** Observes the property-owned construction; its ordinary expression facts own execution. */
final class prepared_construction_property_default extends prepared_property_default
{
	/** @reference.source property_node.initializer */
	public data_construction_node $construction;
	public function value_type(): canonical_type_use { return $this->conversion->source_type; }
}

/** Field lookup retains declaration order and duplicate names without silently choosing one. */
final class prepared_record {
	/** Inline base subobject; declared member inventories remain local. */
	public ?canonical_type_use $base_type = null;
	/** Same property facts, separate storage inventories for layout and type-owned definitions. */
	public Key_Storage_List $static_fields /** Key_Storage_List<prepared_field> */;
	/** Non-owning member index; collection owns identities, signatures own contracts. */
	public Key_Storage_List $methods /** Key_Storage_List<collected_method> */;
	public bool $data_initializable = false;
	public ?record_initialization_contract $initialization = null;
	/** Effective constructor, possibly inherited; methods still index only local declarations. */
	public ?collected_method $constructor /** weak<collected_method> */ = null;
	/** Derived only after every field has completed preparation. */
	public array $capabilities /** vector<generic_contract> */ = [];

	/** Ordered aliases of facts owned by field specializations. @storage.reference property_node.prepared_facts */
	public Key_Storage_List $fields /** Key_Storage_List<prepared_field> */;

	public function __construct()
	{
		$this->fields = new Key_Storage_List /** Key_Storage_List<prepared_field> */();
		$this->static_fields = new Key_Storage_List /** Key_Storage_List<prepared_field> */();
		$this->methods = new Key_Storage_List /** Key_Storage_List<collected_method> */();
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
	public prepared_property_storage $field;
	public canonical_type_use $record_type;
	public record_storage_kind $receiver_storage;
}

final class prepared_static_property extends prepared_expression
{
	public prepared_property_storage $field;
	public canonical_type_use $record_type;
}

/** Resolved call target and signature; evaluation order remains with the process workers. */
final class prepared_call extends prepared_expression
{
	/** Selected runtime owner application supplies template arguments without enumerated registrations. */
	public ?canonical_type_use $runtime_owner = null;
	/** Evaluate the expression qualifier for effects, without binding an instance. */
	public bool $evaluates_qualifier = false;
	public ?prepared_method_receiver $receiver = null;
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

/** Body-owned base initialization; the optional source call remains owned by body syntax. */
final class prepared_base_initialization
{
	public canonical_type_use $base_type;
	public ?prepared_call $call = null;
	public ?call_node $source_call /** weak<call_node> */ = null;
}

/** Preparation chooses receiver lifetime handling; emission does not infer admissibility. */
enum method_receiver_lifetime
{
	case retain_shared;
	case borrow_inline;
	case current_instance;
}

final class prepared_method_receiver
{
	/** Explicit ancestor qualification; null retains ordinary fixed member dispatch. */
	public ?canonical_type_use $dispatch_type = null;

	public function __construct(public canonical_type_use $record_type,
		public method_receiver_lifetime $lifetime) {}
}

/** A reference argument has no conversion; a by-value argument requires one decision. */
final class prepared_call_argument
{
	/** Optional implementation of copy_value; absence always retains the semantic snapshot. */
	public bool $borrow_snapshot = false;
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
	public callable_target_i $target;
	public ?function_body_node $body = null;
	public string $label;
	public array $calls /** vector<int> */ = [];
	public int $visit_index;
	public int $low_link;
	public bool $on_stack = false;

	public function __construct(callable_target_i $target, ?function_body_node $body, string $label)
	{
		$this->target = $target;
		$this->body = $body;
		$this->label = $label;
		$this->visit_index = -1;
		$this->low_link = -1;
	}
}

/** Settled exit facts for one finally region; return values keep ordinary prepared conversions. */
final class prepared_finally
{
	public bool $captures_return = false;
	public bool $can_fall_through = true;
}

/** Invocation-local preparation data; never published into AST records or source scopes. */
final class preparation_context
{
	public bool $asynchronous = false;
	public int $suspension_count = 0;
	/** Current protected region; nested callable preparation starts with a fresh context. */
	public ?prepared_finally $finally_region = null;
	public bool $finally_return_forbidden = false;
	public ?collected_member_owner $member_owner = null;
	/** Concrete implementation context, independent of lexical access/receiver ownership. */
	public ?collected_record $called_type = null;
	public ?prepared_receiver $receiver = null;
	public Preparation_Worker $worker;
	public preparation_owner $owner;
	public collected_file $collection;
	/** Invocation-local lookup of attached binding/parameter facts; does not mutate source scopes. */
	public local_environment $locals;
	/** Each callable invocation is independent of its lexical creation environment. */
	public storage_lifetime_region $invocation_lifetime;
	public Storage $borrowed_lifetimes /** Storage<storage_lifetime> */;
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
	public ?iteration_declaration $contract = null;
	public ?prepared_iterator_calls $source_iterator = null;
	public canonical_type_use $key_type;
	public canonical_type_use $value_type;
	public ?prepared_binding $key = null;
	public prepared_binding $value;
}

/** Five ordinary source calls, evaluated against one retained receiver for the loop. */
final class prepared_iterator_calls
{
	public prepared_call $current;
	public prepared_call $key;
	public prepared_call $valid;
	public prepared_call $next;
	public prepared_call $rewind;
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
	public storage_lifetime_region $region;
	private \SplObjectStorage $lifetimes /** hash<storage_lifetime, prepared_source_storage> */;
	public Key_Storage_List $bindings /** Key_Storage_List<prepared_source_storage> */;
	/** A typed declaration reserves its name while preparing its initializer. */
	public ?string $initializing_name = null;

	public function __construct(?local_environment $parent)
	{
		$this->parent = $parent;
		$parent_region /** nullable<storage_lifetime_region> */ = null;
		$entered_at = 0;
		if ($parent !== null) {
			$parent_region = $parent->region;
			$entered_at = $parent_region->next_order++;
		}
		$this->region = new storage_lifetime_region($parent_region, $entered_at);
		$this->lifetimes = new \SplObjectStorage /** hash<storage_lifetime, prepared_source_storage> */();
		$this->bindings = new Key_Storage_List /** Key_Storage_List<prepared_source_storage> */();
	}

	public function local_named(string $name): array /** vector<prepared_source_storage> */
	{
		$bindings /** Key_Storage_List<prepared_source_storage> */ = $this->bindings;
		return $bindings->named($name);
	}

	/** Nearest membership wins; a reserved declaration hides its outer names immediately. */
	public function named(string $name): array /** vector<prepared_source_storage> */
	{
		if ($this->initializing_name === $name) {
			throw new \RuntimeException('S2S local ' . $name . ' cannot read or write itself in its initializer');
		}
		$result /** vector<prepared_source_storage> */ = $this->local_named($name);
		if (q_count($result) !== 0) {
			return $result;
		}
		if ($this->parent !== null) {
			$parent /** local_environment */ = $this->parent;
			return $parent->named($name);
		}
		return $result;
	}

	public function add(string $name, prepared_source_storage $storage): void
	{
		// Membership establishes storage order; signatures themselves retain no invocation facts.
		$this->lifetimes[$storage] = new storage_lifetime($this->region, $this->region->next_order++);
		$bindings /** Key_Storage_List<prepared_source_storage> */ = $this->bindings;
		$bindings->add($name, $storage);
	}

	public function lifetime_of(prepared_source_storage $storage): storage_lifetime
	{
		if (isset($this->lifetimes[$storage])) { return $this->lifetimes[$storage]; }
		if ($this->parent !== null) { return $this->parent->lifetime_of($storage); }
		throw new \LogicException('Storage has no lifetime in the current invocation');
	}
}

/** Name-pool observation also represents absent or ambiguous lookup results. */
final class preparation_lookup
{
	public scope $lookup_scope;
	public string $name;
	public preparation_lookup_kind $kind;
	public array $candidates /** vector<preparation_lookup_candidate_i> */ = [];
	public \SplObjectStorage $dependents /** hash<bool, preparation_owner> */;

	public function __construct(scope $lookup_scope, string $name, preparation_lookup_kind $kind)
	{
		$this->lookup_scope = $lookup_scope;
		$this->name = $name;
		$this->kind = $kind;
		$this->dependents = new \SplObjectStorage /** hash<bool, preparation_owner> */();
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

/** A property write observes actual storage, never a fabricated local declaration. */
final class prepared_property_assignment extends prepared_assignment_expression
{
	public bool $initializes_union_member = false;
	public prepared_property_storage $destination;
	public conversion_decision $conversion;
	public ?value_boundary_operation $boundary = null;
	public function destination_type(): canonical_type_use
	{
		return $this->destination->storage_type();
	}
}

/** Element writes select operations rather than pretending to declare native storage. */
final class prepared_index_assignment extends prepared_assignment_expression
{
	public prepared_index_target $target;
	public conversion_decision $value_conversion;

	public function destination_type(): canonical_type_use
	{
		return $this->value_conversion->target_type;
	}
}

/** Indexed storage protocol shared by plain and read-modify-write assignments. */
final class prepared_index_target
{
	/** @reference.source indexed target syntax graph */
	public expression_node $root;
	/** @storage.owner */
	public Storage $path /** Storage<prepared_index_path_step> */;
	public operator_decision $decision;
	public canonical_type_use $nested_type;

	public function __construct()
	{
		$this->path = new Storage /** Storage<prepared_index_path_step> */();
	}
}

/** Ordered semantic traversal facts; no copied read or native slot binding. */
final class prepared_index_path_step
{
	/** @reference.source indexed assignment target (syntax graph) */
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

/** Keep enum reflection beside its declaration for native lowering. */
final class Value_Boundary_Operation_Name
{
	public static function text(value_boundary_operation $kind): string
	{
		return enum_name($kind);
	}
}

/** Keep enum reflection beside its declaration for native lowering. */
final class Method_Receiver_Lifetime_Name
{
	public static function text(method_receiver_lifetime $kind): string
	{
		return enum_name($kind);
	}
}

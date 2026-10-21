<?php

/*
 * Role: Canonical Simple C++ type definitions, concrete identities and exact
 * template-application interning. Backend spelling remains in the independent
 * C++ binding catalog.
 */
namespace scpp\compiler;

enum type_family: int
{
	case boolean = 1;
	case integer = 2;
	case floating = 3;
	case nominal = 4;
	case no_value = 5;
	case null_sentinel = 6;
}

enum type_definition_kind: int
{
	case boolean = 1;
	case integer = 2;
	case floating = 3;
	case nominal = 4;
	case template = 5;
	case no_value = 6;
	case null_sentinel = 7;
}

enum type_definition_origin: int
{
	case language = 1;
	case source = 2;
	case runtime = 3;
}

enum nominal_type_kind: int
{
	case structure = 1;
	case class_type = 2;
	case enumeration = 3;
	case interface_type = 4;
	case trait_type = 5;
	case closure_type = 6;
	case callable_type = 7;
	case async_task = 8;
}

/** Storage of a record instance, independently of its nominal declaration identity. */
enum record_storage_kind
{
	case inline_value;
	case shared;
	case unique;
	case weak;
}

enum floating_format: int
{
	case binary = 1;
	case decimal = 2;
}

/** Shared vocabulary for formal requirements and explicitly declared type capabilities. */
enum generic_contract: int
{
	case copyable_value = 1;
	case value_storable = 2;
	case hashable = 3;
	case comparable = 4;
	// Provider opt-in for exact Result value transport; not truthiness or copyability.
	case result_payload = 5;
}

/** Stable text for contracts; native enum reflection is not required by the model. */
final class Generic_Contract_Name
{
	public static function text(generic_contract $contract): string
	{
		if ($contract === generic_contract::copyable_value) {
			return 'copyable_value';
		}
		if ($contract === generic_contract::value_storable) {
			return 'value_storable';
		}
		if ($contract === generic_contract::hashable) {
			return 'hashable';
		}
		if ($contract === generic_contract::comparable) {
			return 'comparable';
		}
		if ($contract === generic_contract::result_payload) {
			return 'result_payload';
		}
		throw new \LogicException('Unknown generic contract');
	}
}

/** Validate the explicit uint32 boundary shared by definition and concrete IDs. */
final class Type_Identity
{
	public static function maximum(): int
	{
		return (int)4294967295;
	}

	public static function require_valid(int $identity, string $description): void
	{
		if (($identity <= 0) || ($identity > self::maximum())) {
			throw new \RangeException($description . ' must be a positive uint32');
		}
	}
}

interface type_definition_i
{
	public function definition_id(): int;
	public function name(): string;
	public function origin(): type_definition_origin;
	public function kind(): type_definition_kind;
	public function declares_capability(generic_contract $capability): bool;
}

/** A definition that denotes one concrete type before any template application. */
interface concrete_type_definition_i
{
	public function create_canonical_type(int $type_id): canonical_type_i;
}

/** Shared identity and source-facing name; subtype state remains invariant-specific. */
abstract class semantic_type_definition implements type_definition_i
{
	private int $identity /** uint32 */;
	private string $source_name;
	private type_definition_origin $definition_origin;
	private array $declared_capabilities /** vector<generic_contract> */;

	public function __construct(int $identity, string $source_name, type_definition_origin $origin,
		array $capabilities /** vector<generic_contract> */)
	{
		Type_Identity::require_valid($identity, 'Type definition identity');
		if ($source_name === '') {
			throw new \InvalidArgumentException('Type definition name must not be empty');
		}
		$this->identity = $identity;
		$this->source_name = $source_name;
		$this->definition_origin = $origin;
		$this->declared_capabilities = self::validated_capabilities($capabilities);
	}

	public function definition_id(): int
	{
		return (int)$this->identity;
	}

	public function name(): string
	{
		return $this->source_name;
	}

	public function origin(): type_definition_origin
	{
		return $this->definition_origin;
	}

	public function capabilities(): array /** vector<generic_contract> */
	{
		return $this->declared_capabilities;
	}

	public function declares_capability(generic_contract $capability): bool
	{
		foreach ($this->declared_capabilities as $declared) {
			if ($declared === $capability) {
				return true;
			}
		}
		return false;
	}

	/** Preserve declaration order while rejecting malformed or duplicate capability facts. */
	private static function validated_capabilities(array $capabilities /** vector<generic_contract> */): array /** vector<generic_contract> */
	{
		$result /** vector<generic_contract> */ = [];
		$seen /** hash<bool> */ = [];
		foreach ($capabilities as $position => $capability)
		{
			$declared_capability /** generic_contract */ = $capability;
			if ($position !== q_count($result)) {
				throw new \InvalidArgumentException('Type capabilities must be an ordered list');
			}
			$capability_key = Generic_Contract_Name::text($declared_capability);
			if (isset($seen[$capability_key])) {
				throw new \InvalidArgumentException('Type capabilities must be unique');
			}
			$seen[$capability_key] = true;
			$result[] = $declared_capability;
		}
		return $result;
	}
}

final class boolean_type_definition extends semantic_type_definition implements concrete_type_definition_i
{
	public function kind(): type_definition_kind
	{
		return type_definition_kind::boolean;
	}

	public function create_canonical_type(int $type_id): canonical_type_i
	{
		return new boolean_type($type_id, $this);
	}
}

final class integer_type_definition extends semantic_type_definition implements concrete_type_definition_i
{
	private int $width /** uint32 */;
	private bool $is_signed;

	public function __construct(int $identity, string $source_name, type_definition_origin $origin,
		int $width, bool $is_signed, array $capabilities /** vector<generic_contract> */)
	{
		parent::__construct($identity, $source_name, $origin, $capabilities);
		if (($width <= 0) || ($width > Type_Identity::maximum())) {
			throw new \RangeException('Integer width must be a positive uint32');
		}
		$this->width = $width;
		$this->is_signed = $is_signed;
	}

	public function kind(): type_definition_kind
	{
		return type_definition_kind::integer;
	}

	public function bit_width(): int
	{
		return (int)$this->width;
	}

	public function signed(): bool
	{
		return $this->is_signed;
	}

	public function create_canonical_type(int $type_id): canonical_type_i
	{
		return new integer_type($type_id, $this);
	}
}

final class floating_type_definition extends semantic_type_definition implements concrete_type_definition_i
{
	private floating_format $number_format;
	private int $stored_bits /** uint32 */;
	private int $precision /** uint32 */;

	public function __construct(int $identity, string $source_name, type_definition_origin $origin,
		floating_format $format, int $stored_bits, int $precision_bits,
		array $capabilities /** vector<generic_contract> */)
	{
		parent::__construct($identity, $source_name, $origin, $capabilities);
		if (($stored_bits <= 0) || ($stored_bits > Type_Identity::maximum())
			|| ($precision_bits <= 0) || ($precision_bits > $stored_bits)) {
			throw new \RangeException('Floating storage and precision must be valid positive bit counts');
		}
		$this->number_format = $format;
		$this->stored_bits = $stored_bits;
		$this->precision = $precision_bits;
	}

	public function kind(): type_definition_kind
	{
		return type_definition_kind::floating;
	}

	public function format(): floating_format
	{
		return $this->number_format;
	}

	public function storage_bits(): int
	{
		return (int)$this->stored_bits;
	}

	public function precision_bits(): int
	{
		return (int)$this->precision;
	}

	public function create_canonical_type(int $type_id): canonical_type_i
	{
		return new floating_type($type_id, $this);
	}
}

/** A nominal identity; source members and derived facts retain their existing owners. */
abstract class abstract_nominal_type_definition extends semantic_type_definition implements concrete_type_definition_i
{
	private nominal_type_kind $nominal_kind_data;

	public function __construct(int $identity, string $source_name, type_definition_origin $origin,
		nominal_type_kind $nominal_kind, array $capabilities /** vector<generic_contract> */)
	{
		parent::__construct($identity, $source_name, $origin, $capabilities);
		$this->nominal_kind_data = $nominal_kind;
	}

	public function kind(): type_definition_kind
	{
		return type_definition_kind::nominal;
	}

	public function nominal_kind(): nominal_type_kind
	{
		return $this->nominal_kind_data;
	}

	public function create_canonical_type(int $type_id): canonical_type_i
	{
		return new nominal_type($type_id, $this);
	}
}

final class nominal_type_definition extends abstract_nominal_type_definition
{
}

/** Unique expression type; source ownership and callable facts live outside the registry. */
final class closure_type_definition extends abstract_nominal_type_definition
{
}

/** Internal awaitable identity. Source task storage/passing is not exposed by this slice. */
final class async_task_type_definition extends abstract_nominal_type_definition
{
	public function __construct(int $id, string $name, public readonly canonical_type_use $value_type)
	{
		parent::__construct($id, $name, type_definition_origin::language, nominal_type_kind::async_task,
			self::task_capabilities());
	}

	private static function task_capabilities(): array /** vector<generic_contract> */
	{
		return [generic_contract::value_storable];
	}
}

/** Structural, name-free callable contract; no source occurrence or backend spelling. */
final class callable_type_definition extends abstract_nominal_type_definition implements callable_target_i
{
	private callable_signature $call_contract;
	public function __construct(int $id, string $name, canonical_type_use $result, array $inputs /** vector<callable_parameter> */,
		?reference_return_contract $reference_return = null)
	{
		parent::__construct($id, $name, type_definition_origin::language, nominal_type_kind::callable_type, self::callable_capabilities());
		$this->call_contract = new callable_signature();
		$this->call_contract->return_type = $result;
		$this->call_contract->reference_return = $reference_return;
		$parameters /** Storage<callable_parameter> */ = $this->call_contract->parameters;
		foreach ($inputs as $input) {
			$parameter = new callable_parameter();
			$parameter->type = $input->type;
			$parameter->mode = $input->mode;
			$parameter->access = $input->access;
			$parameter->element_type = $input->element_type;
			$parameters->append($parameter);
		}
	}
	public function signature(): callable_signature { return $this->call_contract; }
	private static function callable_capabilities(): array /** vector<generic_contract> */
	{
		return [generic_contract::value_storable, generic_contract::copyable_value, generic_contract::result_payload];
	}
}

/** Enum identity is nominal; backing syntax, case values and storage selection are not registry facts. */
final class enum_type_definition extends abstract_nominal_type_definition
{
	public function __construct(int $identity, string $source_name, type_definition_origin $origin,
		array $capabilities /** vector<generic_contract> */)
	{
		parent::__construct($identity, $source_name, $origin, nominal_type_kind::enumeration, $capabilities);
	}
}

/** Broad boxed-value semantics; operation admission remains preparation-owned. */
interface mixed_semantics
{
}

/** Tagged dynamic-family values; only container payloads have shared identity. */
interface dynamic_semantics
{
}

final class mixed_type_definition extends abstract_nominal_type_definition implements mixed_semantics
{
	public function __construct(int $identity, string $source_name, type_definition_origin $origin,
		array $capabilities /** vector<generic_contract> */)
	{
		parent::__construct($identity, $source_name, $origin, nominal_type_kind::structure, $capabilities);
	}
}

final class dynamic_type_definition extends abstract_nominal_type_definition implements dynamic_semantics
{
	public function __construct(int $identity, string $source_name, type_definition_origin $origin,
		array $capabilities /** vector<generic_contract> */)
	{
		parent::__construct($identity, $source_name, $origin, nominal_type_kind::structure, $capabilities);
	}
}

final class no_value_type_definition extends semantic_type_definition implements concrete_type_definition_i
{
	public function kind(): type_definition_kind
	{
		return type_definition_kind::no_value;
	}

	public function create_canonical_type(int $type_id): canonical_type_i
	{
		return new no_value_type($type_id, $this);
	}
}

/** The owning definition's ordered parameter list supplies this formal's position. */
final class template_type_parameter
{
	private string $parameter_name;
	private array $parameter_contracts /** vector<generic_contract> */;
	private array $dependent_capabilities /** vector<generic_contract> */;
	private ?canonical_type_use $default_type_data = null;

	/**
	 * A null contract list means the source-language bare-T default. Runtime
	 * definitions pass an explicit list, including an empty list when no provider
	 * capability requirement is currently known.
	 */
	public function __construct(string $name, ?array $contracts /** vector<generic_contract> */ = null,
		?canonical_type_use $default_type = null, ?array $dependencies /** vector<generic_contract> */ = null)
	{
		if ($name === '') {
			throw new \InvalidArgumentException('Template parameter name must not be empty');
		}
		$effective_contracts /** vector<generic_contract> */ = [];
		if ($contracts === null) {
			$effective_contracts[] = generic_contract::copyable_value;
		}
		else {
			$effective_contracts = $contracts;
		}

		$seen /** hash<bool> */ = [];
		foreach ($effective_contracts as $position => $contract)
		{
			$parameter_contract /** generic_contract */ = $contract;
			if ($position !== q_count($seen)) {
				throw new \InvalidArgumentException('Template parameter contracts must be an ordered list');
			}
			$contract_key = Generic_Contract_Name::text($parameter_contract);
			if (isset($seen[$contract_key])) {
				throw new \InvalidArgumentException('Template parameter contracts must be unique');
			}
			$seen[$contract_key] = true;
		}
		$this->parameter_name = $name;
		$this->parameter_contracts = $effective_contracts;
		$this->default_type_data = $default_type;
		$effective_dependencies /** vector<generic_contract> */ = [];
		if ($dependencies !== null) { $effective_dependencies = $dependencies; }
		$dependency_keys /** hash<bool> */ = [];
		foreach ($effective_dependencies as $position => $dependency)
		{
			$key = Generic_Contract_Name::text($dependency);
			if ($dependency === generic_contract::result_payload) {
				throw new \InvalidArgumentException('Result payload registration is an unconditional transport opt-in');
			}
			if (($position !== q_count($dependency_keys)) || isset($dependency_keys[$key])) {
				throw new \InvalidArgumentException('Capability dependencies must be an ordered unique list');
			}
			$dependency_keys[$key] = true;
		}
		$this->dependent_capabilities = $effective_dependencies;
	}

	public function name(): string
	{
		return $this->parameter_name;
	}

	public function contracts(): array /** vector<generic_contract> */
	{
		return $this->parameter_contracts;
	}

	/** Unlike formation constraints, these gate the applied type's capabilities. */
	public function capability_dependencies(): array /** vector<generic_contract> */
	{
		return $this->dependent_capabilities;
	}

	public function default_type(): ?canonical_type_use
	{
		return $this->default_type_data;
	}
}

/** A template is a definition recipe and never a concrete type by itself. */
abstract class abstract_template_type_definition extends semantic_type_definition
{
	private nominal_type_kind $result_kind_data;
	private array $ordered_parameters /** vector<template_type_parameter> */;
	private int $minimum_arity /** uint32 */;

	/** Preserve the owner's explicit list order and reject duplicate formal names. */
	public function __construct(int $identity, string $source_name, type_definition_origin $origin,
		nominal_type_kind $result_kind, array $parameters /** vector<template_type_parameter> */,
		array $capabilities /** vector<generic_contract> */)
	{
		parent::__construct($identity, $source_name, $origin, $capabilities);
		if ($parameters === []) {
			throw new \InvalidArgumentException('Template definition requires at least one type parameter');
		}

		$expected_position = 0;
		$minimum_arity = q_count($parameters);
		$seen_default = false;
		$names /** hash<bool> */ = [];
		foreach ($parameters as $position => $parameter)
		{
			if (($position !== $expected_position) || !($parameter instanceof template_type_parameter)) {
				throw new \InvalidArgumentException('Template parameters must use ordered zero-based positions');
			}
			$parameter_name = $parameter->name();
			foreach ($parameter->capability_dependencies() as $dependency) {
				if (!$this->declares_capability($dependency)) {
					throw new \InvalidArgumentException('Capability dependency requires a declared template capability');
				}
			}
			if (isset($names[$parameter_name])) {
				throw new \InvalidArgumentException('Template parameter names must be unique');
			}
			$names[$parameter_name] = true;
			if ($parameter->default_type() !== null) {
				if (!$seen_default) {
					$minimum_arity = $position;
					$seen_default = true;
				}
			}
			elseif ($seen_default) {
				throw new \InvalidArgumentException('Required template parameter cannot follow a defaulted parameter');
			}
			$expected_position++;
		}

		$this->result_kind_data = $result_kind;
		$this->ordered_parameters = $parameters;
		$this->minimum_arity = $minimum_arity;
	}

	public function kind(): type_definition_kind
	{
		return type_definition_kind::template;
	}

	public function result_kind(): nominal_type_kind
	{
		return $this->result_kind_data;
	}

	public function parameters(): array /** vector<template_type_parameter> */
	{
		return $this->ordered_parameters;
	}

	/** Resolve the formal whose identity is this definition and the supplied zero-based slot. */
	public function parameter(int $position): template_type_parameter
	{
		if (($position < 0) || !isset($this->ordered_parameters[$position])) {
			throw new \OutOfBoundsException('Unknown template type parameter position');
		}
		return $this->ordered_parameters[$position];
	}

	public function arity(): int
	{
		return q_count($this->ordered_parameters);
	}

	public function required_arity(): int
	{
		return (int)$this->minimum_arity;
	}
}

/** Ordinary templates add no language-specific interpretation. */
final class template_type_definition extends abstract_template_type_definition
{
}

/** Definition-owned nullable interpretation; operation policy belongs to semantic workers. */
interface nullable_semantics
{
	public function payload_type(applied_template_type $application): canonical_type_use;
}

final class nullable_type_definition extends abstract_template_type_definition implements nullable_semantics
{
	public function __construct(int $identity, string $source_name, type_definition_origin $origin,
		nominal_type_kind $result_kind, array $parameters /** vector<template_type_parameter> */,
		array $capabilities /** vector<generic_contract> */)
	{
		parent::__construct($identity, $source_name, $origin, $result_kind, $parameters, $capabilities);
		if (($this->arity() !== 1) || ($this->required_arity() !== 1)) {
			throw new \InvalidArgumentException('Nullable definition requires exactly one required payload parameter');
		}
	}

	public function payload_type(applied_template_type $application): canonical_type_use
	{
		if ($application->template_definition() !== $this) {
			throw new \InvalidArgumentException('Nullable payload requires an application of this definition');
		}
		$arguments /** vector<canonical_type_use> */ = $application->arguments();
		if (q_count($arguments) !== 1) {
			throw new \InvalidArgumentException('Nullable application requires one payload type');
		}
		return $arguments[0];
	}
}

/** Result family identity, not the active state of a runtime value. */
enum result_kind
{
	case structured_error;
	case false_sentinel;
	case boolean_sentinels;
}

interface result_semantics
{
	public function result_family(): result_kind;
	public function payload_type(applied_template_type $application): canonical_type_use;
}

/** All result families share a recipe shape; workers own their distinct operation policies. */
final class result_type_definition extends abstract_template_type_definition implements result_semantics
{
	private result_kind $family_kind;

	public function __construct(int $identity, string $source_name, type_definition_origin $origin,
		nominal_type_kind $result_type_kind, array $parameters /** vector<template_type_parameter> */,
		array $capabilities /** vector<generic_contract> */, result_kind $family_kind)
	{
		parent::__construct($identity, $source_name, $origin, $result_type_kind, $parameters, $capabilities);
		if (($this->arity() !== 1) || ($this->required_arity() !== 1)) {
			throw new \InvalidArgumentException('Result definition requires exactly one required payload parameter');
		}
		$this->family_kind = $family_kind;
	}

	public function result_family(): result_kind { return $this->family_kind; }

	public function payload_type(applied_template_type $application): canonical_type_use
	{
		if ($application->template_definition() !== $this) {
			throw new \InvalidArgumentException('Result payload requires an application of this definition');
		}
		$arguments /** vector<canonical_type_use> */ = $application->arguments();
		if (q_count($arguments) !== 1) {
			throw new \InvalidArgumentException('Result application requires one payload type');
		}
		return $arguments[0];
	}
}

/** Ownership describes the handle, not the runtime pointee or its copy capability. */
enum ownership_kind
{
	case shared;
	case unique;
	case weak;
}

interface ownership_semantics
{
	public function ownership_family(): ownership_kind;
	public function target_type(applied_template_type $application): canonical_type_use;
}

/** Fixed family capabilities cannot accidentally make unique handles copyable. */
final class ownership_type_definition extends abstract_template_type_definition implements ownership_semantics
{
	private ownership_kind $family_kind;

	/** Copying a handle does not require copying its target; operation workers own access/transfer. */
	public function __construct(int $identity, string $source_name, type_definition_origin $origin,
		array $parameters /** vector<template_type_parameter> */, ownership_kind $family_kind)
	{
		parent::__construct($identity, $source_name, $origin, nominal_type_kind::class_type,
			$parameters, self::handle_capabilities($family_kind));
		if (($this->arity() !== 1) || ($this->required_arity() !== 1)) {
			throw new \InvalidArgumentException('Ownership definition requires exactly one required target parameter');
		}
		$this->family_kind = $family_kind;
	}

	public function ownership_family(): ownership_kind
	{
		return $this->family_kind;
	}

	/** Compute base-construction data without statements preceding the native base initializer. */
	private static function handle_capabilities(ownership_kind $family_kind): array /** vector<generic_contract> */
	{
		$capabilities /** vector<generic_contract> */ = [generic_contract::value_storable];
		if ($family_kind !== ownership_kind::unique) {
			$capabilities = [generic_contract::copyable_value, generic_contract::value_storable];
		}
		return $capabilities;
	}

	/** Target queries cannot cross distinct family or provider definition identities. */
	public function target_type(applied_template_type $application): canonical_type_use
	{
		if ($application->template_definition() !== $this) {
			throw new \InvalidArgumentException('Ownership target requires an application of this definition');
		}
		$arguments /** vector<canonical_type_use> */ = $application->arguments();
		if (q_count($arguments) !== 1) {
			throw new \InvalidArgumentException('Ownership application requires one target type');
		}
		return $arguments[0];
	}
}

/** Internal literal identity, never a source storage type or optional payload. */
final class null_type_definition extends semantic_type_definition implements concrete_type_definition_i
{
	public function kind(): type_definition_kind { return type_definition_kind::null_sentinel; }
	public function create_canonical_type(int $type_id): canonical_type_i
	{
		return new null_type($type_id, $this);
	}
}

interface canonical_type_i
{
	public function type_id(): int;
	public function family(): type_family;
	public function definition(): type_definition_i;
}

/** Common canonical identity; only concrete semantic types enter the type registry. */
abstract class canonical_type implements canonical_type_i
{
	private int $identity /** uint32 */;

	public function __construct(int $identity)
	{
		Type_Identity::require_valid($identity, 'Canonical type identity');
		$this->identity = $identity;
	}

	public function type_id(): int
	{
		return (int)$this->identity;
	}

	abstract public function definition(): type_definition_i;
}

final class null_type extends canonical_type
{
	private null_type_definition $null_definition;

	public function __construct(int $identity, null_type_definition $null_definition)
	{
		parent::__construct($identity);
		$this->null_definition = $null_definition;
	}
	public function family(): type_family { return type_family::null_sentinel; }
	public function definition(): type_definition_i { return $this->null_definition; }
}

final class boolean_type extends canonical_type
{
	private boolean_type_definition $boolean_definition_data;

	public function __construct(int $identity, boolean_type_definition $definition)
	{
		parent::__construct($identity);
		$this->boolean_definition_data = $definition;
	}

	public function family(): type_family
	{
		return type_family::boolean;
	}

	public function definition(): type_definition_i
	{
		return object_cast($this->boolean_definition_data, type_definition_i::class);
	}

	public function boolean_definition(): boolean_type_definition
	{
		return $this->boolean_definition_data;
	}
}

final class integer_type extends canonical_type
{
	private integer_type_definition $integer_definition_data;

	public function __construct(int $identity, integer_type_definition $definition)
	{
		parent::__construct($identity);
		$this->integer_definition_data = $definition;
	}

	public function family(): type_family
	{
		return type_family::integer;
	}

	public function definition(): type_definition_i
	{
		return object_cast($this->integer_definition_data, type_definition_i::class);
	}

	public function integer_definition(): integer_type_definition
	{
		return $this->integer_definition_data;
	}
}

final class floating_type extends canonical_type
{
	private floating_type_definition $floating_definition_data;

	public function __construct(int $identity, floating_type_definition $definition)
	{
		parent::__construct($identity);
		$this->floating_definition_data = $definition;
	}

	public function family(): type_family
	{
		return type_family::floating;
	}

	public function definition(): type_definition_i
	{
		return object_cast($this->floating_definition_data, type_definition_i::class);
	}

	public function floating_definition(): floating_type_definition
	{
		return $this->floating_definition_data;
	}
}

final class nominal_type extends canonical_type
{
	private abstract_nominal_type_definition $nominal_definition_data;

	public function __construct(int $identity, abstract_nominal_type_definition $definition)
	{
		parent::__construct($identity);
		$this->nominal_definition_data = $definition;
	}

	public function family(): type_family
	{
		return type_family::nominal;
	}

	public function definition(): type_definition_i
	{
		return object_cast($this->nominal_definition_data, type_definition_i::class);
	}

	public function nominal_definition(): abstract_nominal_type_definition
	{
		return $this->nominal_definition_data;
	}
}

final class no_value_type extends canonical_type
{
	private no_value_type_definition $no_value_definition_data;

	public function __construct(int $identity, no_value_type_definition $definition)
	{
		parent::__construct($identity);
		$this->no_value_definition_data = $definition;
	}

	public function family(): type_family
	{
		return type_family::no_value;
	}

	public function definition(): type_definition_i
	{
		return object_cast($this->no_value_definition_data, type_definition_i::class);
	}

	public function no_value_definition(): no_value_type_definition
	{
		return $this->no_value_definition_data;
	}
}

/** One canonical specialization of a template definition and exact ordered type arguments. */
final class applied_template_type extends canonical_type
{
	private abstract_template_type_definition $template_definition_data;
	private array $argument_types /** vector<canonical_type_use> */;

	public function __construct(int $identity, abstract_template_type_definition $definition,
		array $arguments /** vector<canonical_type_use> */)
	{
		parent::__construct($identity);
		$this->template_definition_data = $definition;
		$this->argument_types = $arguments;
	}

	public function family(): type_family
	{
		return type_family::nominal;
	}

	public function definition(): type_definition_i
	{
		return object_cast($this->template_definition_data, type_definition_i::class);
	}

	public function template_definition(): abstract_template_type_definition
	{
		return $this->template_definition_data;
	}

	public function arguments(): array /** vector<canonical_type_use> */
	{
		return $this->argument_types;
	}
}

/** Lightweight prepared use; by_value records only a non-redundant storage modifier. */
final class canonical_type_use
{
	private int $identity /** uint32 */;
	private bool $use_by_value;

	public function __construct(int $type_id, bool $by_value = false)
	{
		Type_Identity::require_valid($type_id, 'Type use identity');
		$this->identity = $type_id;
		$this->use_by_value = $by_value;
	}

	public function type_id(): int
	{
		return (int)$this->identity;
	}

	public function by_value(): bool
	{
		return $this->use_by_value;
	}

	public function matches(canonical_type_use $other): bool
	{
		return ($this->identity === $other->identity) && ($this->use_by_value === $other->use_by_value);
	}
}

/** Exact ordered application index; argument identities and modifier bits form trie edges. */
final class type_application_index
{
	private array $ordinary_children /** hash<type_application_index, int> */ = [];
	private array $by_value_children /** hash<type_application_index, int> */ = [];
	private ?applied_template_type $published_type = null;

	public function child(int $type_id, bool $by_value): type_application_index
	{
		Type_Identity::require_valid($type_id, 'Application index identity');
		if ($by_value)
		{
			if (isset($this->by_value_children[$type_id])) {
				return $this->by_value_children[$type_id];
			}
			else {
				$child = new type_application_index();
				$this->by_value_children[$type_id] = $child;
				return $child;
			}
		}

		if (isset($this->ordinary_children[$type_id])) {
			return $this->ordinary_children[$type_id];
		}
		else {
			$child = new type_application_index();
			$this->ordinary_children[$type_id] = $child;
			return $child;
		}
	}

	public function application(): ?applied_template_type
	{
		return $this->published_type;
	}

	public function publish(applied_template_type $type): void
	{
		if ($this->published_type !== null) {
			throw new \LogicException('Canonical template application was published twice');
		}
		$this->published_type = $type;
	}
}

/** Capability providers answer from registered contracts or completed semantic facts. */
interface type_capability_query
{
	public function has_capability(canonical_type_use $type_use, generic_contract $capability): bool;
}

/** Own numeric identities, canonical concrete types and exact template application reuse. */
final class Type_Registry implements type_capability_query
{
	/** Explicit provider storage is independent of nominal kind and payload capabilities. */
	private array $runtime_storage_policies /** hash<record_storage_kind, int> */ = [];

	/** Install once with the definition; never derive provider representation from its name. */
	public function register_runtime_storage(type_definition_i $definition, record_storage_kind $storage): void
	{
		$id = $definition->definition_id();
		if (($definition->origin() !== type_definition_origin::runtime)
			|| !isset($this->definitions[$id]) || ($this->definitions[$id] !== $definition)
			|| isset($this->runtime_storage_policies[$id])) {
			throw new \LogicException('Runtime storage requires an owned, unconfigured runtime definition');
		}
		if (($storage !== record_storage_kind::inline_value) && ($storage !== record_storage_kind::shared)) {
			throw new \InvalidArgumentException('Runtime storage supports inline or shared');
		}
		$this->runtime_storage_policies[$id] = $storage;
	}

	public function runtime_storage(type_definition_i $definition): ?record_storage_kind
	{
		$key = $definition->definition_id();
		if (!isset($this->runtime_storage_policies[$key])) { return null; }
		return $this->runtime_storage_policies[$key];
	}

	private array $callable_types /** hash<callable_type_definition> */ = [];
	private array $async_task_types /** hash<async_task_type_definition> */ = [];
	public function define_async_task(canonical_type_use $value): async_task_type_definition
	{
		$key = $value->type_id() . ':' . ($value->by_value() ? 'v' : 'n');
		if (isset($this->async_task_types[$key])) { return $this->async_task_types[$key]; }
		$definition = new async_task_type_definition($this->allocate_definition_id(), '<async:' . $key . '>', $value);
		$this->register_definition($definition);
		$this->async_task_types[$key] = $definition;
		return $definition;
	}
	public function define_callable(canonical_type_use $result, array $inputs /** vector<callable_parameter> */,
		?reference_return_contract $reference_return = null): callable_type_definition
	{
		$key = $result->type_id() . ':' . ($result->by_value() ? 'v' : 'n');
		if ($reference_return !== null) { $key .= '&' . $reference_return->parameter_index(); }
		foreach ($inputs as $input) {
			if ($input->default_value !== null) { throw new \InvalidArgumentException('Callable type defaults are deferred'); }
			$key .= ',' . $input->type->type_id() . ':' . ($input->type->by_value() ? 'v' : 'n')
				. ($input->mode === passing_mode::reference ? '&' : '=')
				. ($input->access === storage_access::read_only ? 'c' : 'w');
			if ($input->element_type !== null) {
				$element /** canonical_type_use */ = $input->element_type;
				$key .= '...' . $element->type_id() . ':' . ($element->by_value() ? 'v' : 'n');
			}
		}
		if (isset($this->callable_types[$key])) { return $this->callable_types[$key]; }
		$definition = new callable_type_definition($this->allocate_definition_id(), '<function:' . $key . '>', $result, $inputs, $reference_return);
		$this->register_definition($definition);
		$this->callable_types[$key] = $definition;
		return $definition;
	}
	private int $next_definition_id /** uint32 */ = 1;
	private int $next_type_id /** uint32 */ = 1;
	private bool $definition_ids_exhausted = false;
	private bool $type_ids_exhausted = false;
	private array $definitions /** hash<type_definition_i, int> */ = [];
	private array $types /** hash<canonical_type_i, int> */ = [];
	private array $concrete_types /** hash<int, int> */ = [];
	private type_application_index $application_index;
	private int $applied_type_count /** uint32 */ = 0;

	public function __construct()
	{
		$this->application_index = new type_application_index();
	}

	public function define_boolean(string $name, type_definition_origin $origin,
		array $capabilities /** vector<generic_contract> */): boolean_type_definition
	{
		$definition = new boolean_type_definition($this->allocate_definition_id(), $name, $origin, $capabilities);
		$this->register_definition($definition);
		return $definition;
	}

	public function define_null(): null_type_definition
	{
		$capabilities /** vector<generic_contract> */ = [];
		$definition = new null_type_definition($this->allocate_definition_id(), 'null',
			type_definition_origin::language, $capabilities);
		$this->register_definition($definition);
		return $definition;
	}

	public function define_integer(string $name, type_definition_origin $origin,
		int $bit_width, bool $signed, array $capabilities /** vector<generic_contract> */): integer_type_definition
	{
		$definition = new integer_type_definition($this->allocate_definition_id(), $name, $origin,
			$bit_width, $signed, $capabilities);
		$this->register_definition($definition);
		return $definition;
	}

	public function define_floating(string $name, type_definition_origin $origin,
		floating_format $format, int $storage_bits, int $precision_bits,
		array $capabilities /** vector<generic_contract> */): floating_type_definition
	{
		$definition = new floating_type_definition($this->allocate_definition_id(), $name, $origin,
			$format, $storage_bits, $precision_bits, $capabilities);
		$this->register_definition($definition);
		return $definition;
	}

	public function define_nominal(string $name, type_definition_origin $origin,
		nominal_type_kind $kind, array $capabilities /** vector<generic_contract> */): nominal_type_definition
	{
		$definition = new nominal_type_definition($this->allocate_definition_id(), $name, $origin, $kind,
			$capabilities);
		$this->register_definition($definition);
		return $definition;
	}

	public function define_closure(): closure_type_definition
	{
		$id = $this->allocate_definition_id();
		$capabilities /** vector<generic_contract> */ = [generic_contract::value_storable, generic_contract::copyable_value];
		$definition = new closure_type_definition($id, '<closure:' . $id . '>', type_definition_origin::source,
			nominal_type_kind::closure_type, $capabilities);
		$this->register_definition($definition);
		return $definition;
	}

	public function define_mixed(string $name, type_definition_origin $origin,
		array $capabilities /** vector<generic_contract> */): mixed_type_definition
	{
		$definition = new mixed_type_definition($this->allocate_definition_id(), $name, $origin, $capabilities);
		$this->register_definition($definition);
		return $definition;
	}

	public function define_enum(string $name, type_definition_origin $origin,
		array $capabilities /** vector<generic_contract> */): enum_type_definition
	{
		$definition = new enum_type_definition($this->allocate_definition_id(), $name, $origin, $capabilities);
		$this->register_definition($definition);
		return $definition;
	}

	public function define_dynamic(string $name, type_definition_origin $origin,
		array $capabilities /** vector<generic_contract> */): dynamic_type_definition
	{
		$definition = new dynamic_type_definition($this->allocate_definition_id(), $name, $origin, $capabilities);
		$this->register_definition($definition);
		return $definition;
	}

	public function define_no_value(string $name, type_definition_origin $origin): no_value_type_definition
	{
		$no_capabilities /** vector<generic_contract> */ = [];
		$definition = new no_value_type_definition($this->allocate_definition_id(), $name, $origin,
			$no_capabilities);
		$this->register_definition($definition);
		return $definition;
	}

	public function define_template(string $name, type_definition_origin $origin,
		nominal_type_kind $result_kind, array $parameters /** vector<template_type_parameter> */,
		array $capabilities /** vector<generic_contract> */): template_type_definition
	{
		$definition = new template_type_definition($this->allocate_definition_id(), $name, $origin,
			$result_kind, $parameters, $capabilities);
		$this->register_definition($definition);
		return $definition;
	}

	public function define_nullable(string $name, type_definition_origin $origin,
		nominal_type_kind $result_kind, array $parameters /** vector<template_type_parameter> */,
		array $capabilities /** vector<generic_contract> */): nullable_type_definition
	{
		$definition = new nullable_type_definition($this->allocate_definition_id(), $name, $origin,
			$result_kind, $parameters, $capabilities);
		$this->register_definition($definition);
		return $definition;
	}

	/** Register a distinct result definition while sharing its family implementation. */
	public function define_result(string $name, type_definition_origin $origin,
		nominal_type_kind $result_type_kind, array $parameters /** vector<template_type_parameter> */,
		array $capabilities /** vector<generic_contract> */, result_kind $family_kind): result_type_definition
	{
		$definition = new result_type_definition($this->allocate_definition_id(), $name, $origin,
			$result_type_kind, $parameters, $capabilities, $family_kind);
		$this->register_definition($definition);
		return $definition;
	}

	/** Install semantic ownership independently of source exposure and backend binding. */
	public function define_ownership(string $name, type_definition_origin $origin,
		array $parameters /** vector<template_type_parameter> */, ownership_kind $family_kind): ownership_type_definition
	{
		$definition = new ownership_type_definition($this->allocate_definition_id(), $name, $origin,
			$parameters, $family_kind);
		$this->register_definition($definition);
		return $definition;
	}

	/** Reuse the sole concrete type belonging to a non-template definition. */
	public function canonical(concrete_type_definition_i $definition): canonical_type_i
	{
		$semantic_definition = object_cast($definition, type_definition_i::class);
		$this->require_registered_definition($semantic_definition);
		$definition_id = $semantic_definition->definition_id();
		if (isset($this->concrete_types[$definition_id])) {
			return $this->type($this->concrete_types[$definition_id]);
		}

		$type = $definition->create_canonical_type($this->allocate_type_id());
		$this->register_type($type);
		$this->concrete_types[$definition_id] = $type->type_id();
		return $type;
	}

	/** Intern exactly one canonical type for a definition and ordered canonical arguments. */
	public function intern_application(abstract_template_type_definition $definition,
		array $arguments /** vector<canonical_type_use> */, ?type_capability_query $capabilities = null): applied_template_type
	{
		$complete_arguments = $this->validate_application($definition, $arguments, $capabilities);
		$index = $this->application_index->child($definition->definition_id(), false);
		foreach ($complete_arguments as $argument) {
			$index = $index->child($argument->type_id(), $argument->by_value());
		}
		$existing = $index->application();
		if ($existing !== null) {
			return $existing;
		}

		$type = new applied_template_type($this->allocate_type_id(), $definition, $complete_arguments);
		$this->register_type($type);
		$index->publish($type);
		$this->applied_type_count++;
		return $type;
	}

	public function definition(int $definition_id): type_definition_i
	{
		Type_Identity::require_valid($definition_id, 'Type definition identity');
		if (!isset($this->definitions[$definition_id])) {
			throw new \OutOfBoundsException('Unknown type definition identity');
		}
		return $this->definitions[$definition_id];
	}

	public function type(int $type_id): canonical_type_i
	{
		Type_Identity::require_valid($type_id, 'Canonical type identity');
		if (!isset($this->types[$type_id])) {
			throw new \OutOfBoundsException('Unknown canonical type identity');
		}
		return $this->types[$type_id];
	}

	/** Retain only an effective storage modifier; source spelling remains in the AST. */
	public function use(int $type_id, bool $by_value = false): canonical_type_use
	{
		$type = $this->type($type_id);
		$definition = $type->definition();
		if ($by_value && ($this->runtime_storage($definition) === record_storage_kind::shared)) {
			throw new \InvalidArgumentException('Opaque runtime shared payload does not permit value storage');
		}
		if ($by_value && ($definition->origin() === type_definition_origin::source)) {
			if ($definition instanceof abstract_nominal_type_definition) {
				if (object_cast($definition, abstract_nominal_type_definition::class)->nominal_kind() === nominal_type_kind::interface_type) {
					throw new \InvalidArgumentException('Interface storage requires its default shared handle');
				}
				if (object_cast($definition, abstract_nominal_type_definition::class)->nominal_kind() === nominal_type_kind::structure) {
					throw new \InvalidArgumentException('value<Struct> repeats the declaration default storage');
				}
			}
		}
		if ($by_value) {
			if ($type->definition() instanceof ownership_semantics) {
				throw new \InvalidArgumentException('Value modifier cannot wrap an ownership type');
			}
		}
		if ($by_value && $this->is_value_type($type_id)) {
			$by_value = false;
		}
		return new canonical_type_use($type->type_id(), $by_value);
	}

	/** Registered families and nominal kinds establish default storage, never source names. */
	public function is_value_type(int $type_id): bool
	{
		$type = $this->type($type_id);
		$family = $type->family();
		if (($family === type_family::boolean) || ($family === type_family::integer)
			|| ($family === type_family::floating)) {
			return true;
		}
		$definition = $type->definition();
		$registered_storage = $this->runtime_storage($definition);
		if ($registered_storage !== null) {
			return $registered_storage === record_storage_kind::inline_value;
		}
		if ($definition instanceof abstract_nominal_type_definition) {
			$nominal = object_cast($definition, abstract_nominal_type_definition::class);
			return ($nominal->nominal_kind() === nominal_type_kind::structure)
				|| ($nominal->nominal_kind() === nominal_type_kind::closure_type)
				|| ($nominal->nominal_kind() === nominal_type_kind::callable_type)
				|| ($nominal->nominal_kind() === nominal_type_kind::async_task)
				|| ($nominal->nominal_kind() === nominal_type_kind::enumeration);
		}
		if ($definition instanceof abstract_template_type_definition) {
			$template_definition = object_cast($definition, abstract_template_type_definition::class);
			return $template_definition->result_kind() === nominal_type_kind::structure;
		}
		return false;
	}

	public function definition_count(): int
	{
		return q_count($this->definitions);
	}

	public function type_count(): int
	{
		return q_count($this->types);
	}

	public function application_count(): int
	{
		return (int)$this->applied_type_count;
	}

	/** Evaluate registered facts/rules without inferring source structure or driving lifecycle. */
	public function has_capability(canonical_type_use $type_use, generic_contract $capability): bool
	{
		return $this->has_registered_capability($type_use, $capability, $this);
	}

	/** Read registered rules; the caller supplies argument facts without retaining a worker. */
	public function has_registered_capability(canonical_type_use $type_use, generic_contract $capability,
		type_capability_query $arguments): bool
	{
		$canonical = $this->type($type_use->type_id());
		$definition = $canonical->definition();
		if ($definition->origin() === type_definition_origin::source) {
			throw new \LogicException('Source type capabilities require completed declaration preparation');
		}
		if (!$type_use->by_value() && ($this->runtime_storage($definition) === record_storage_kind::shared)) {
			if (($capability === generic_contract::copyable_value) || ($capability === generic_contract::value_storable)) {
				return true;
			}
		}
		if (!$definition->declares_capability($capability)) {
			return false;
		}
		if ($canonical instanceof applied_template_type)
		{
			$application = object_cast($canonical, applied_template_type::class);
			$recipe = $application->template_definition();
			foreach ($application->arguments() as $position => $argument) {
				foreach ($recipe->parameter($position)->capability_dependencies() as $dependency) {
					if ($dependency === $capability) {
						if (!$arguments->has_capability($argument, $dependency)) {
							return false;
						}
					}
				}
			}
		}
		return true;
	}

	private function register_definition(type_definition_i $definition): void
	{
		$id = $definition->definition_id();
		if (isset($this->definitions[$id])) {
			throw new \LogicException('Duplicate type definition identity');
		}
		$this->definitions[$id] = $definition;
	}

	private function register_type(canonical_type_i $type): void
	{
		$id = $type->type_id();
		if (isset($this->types[$id])) {
			throw new \LogicException('Duplicate canonical type identity');
		}
		$this->types[$id] = $type;
	}

	private function require_registered_definition(type_definition_i $definition): void
	{
		$definition_id = $definition->definition_id();
		if ((!isset($this->definitions[$definition_id]))
			|| ($this->definitions[$definition_id] !== $definition)) {
			throw new \InvalidArgumentException('Type definition belongs to another registry');
		}
	}

	/** Validate supplied arguments and append the definition's trailing defaults. */
	public function complete_application_arguments(abstract_template_type_definition $definition,
		array $arguments /** vector<canonical_type_use> */): array /** vector<canonical_type_use> */
	{
		$count = q_count($arguments);
		$this->validate_application_arity($definition, $count);

		$expected_position = 0;
		foreach ($arguments as $position => $argument)
		{
			if (($position !== $expected_position) || !($argument instanceof canonical_type_use)) {
				throw new \InvalidArgumentException('Template type arguments must be an ordered list');
			}
			$this->type($argument->type_id());
			$expected_position++;
		}

		$complete /** vector<canonical_type_use> */ = $arguments;
		while ($expected_position < $definition->arity())
		{
			$default_argument = $definition->parameter($expected_position)->default_type();
			if ($default_argument === null) {
				throw new \LogicException('Missing template argument has no default');
			}
			$this->type($default_argument->type_id());
			$complete[] = $default_argument;
			$expected_position++;
		}
		return $complete;
	}

	/** Validate one complete application from declared facts before interning any identity. */
	public function validate_application(abstract_template_type_definition $definition,
		array $arguments /** vector<canonical_type_use> */, ?type_capability_query $capabilities = null): array /** vector<canonical_type_use> */
	{
		$complete = $this->complete_application_arguments($definition, $arguments);
		if ($definition instanceof ownership_semantics) {
			$payload = $this->type($complete[0]->type_id());
			$ownership_family = object_cast($definition, ownership_semantics::class)->ownership_family();
			if (($payload->family() === type_family::no_value)
				&& (($ownership_family === ownership_kind::shared) || ($ownership_family === ownership_kind::unique))) {
				throw new \InvalidArgumentException('Shared and unique ownership require a non-void target type');
			}
			if ($payload->definition() instanceof ownership_semantics) {
				throw new \InvalidArgumentException('Direct ownership wrapper nesting is not supported in v0.2');
			}
			if ($complete[0]->by_value()) {
				throw new \InvalidArgumentException('Ownership wrappers require an unmodified payload type');
			}
			$payload_definition = $payload->definition();
			if ($this->runtime_storage($payload_definition) === record_storage_kind::shared) {
				throw new \InvalidArgumentException('Opaque runtime handle ownership transformations are not registered');
			}
			if ($payload_definition->origin() === type_definition_origin::source) {
				if ($payload_definition instanceof abstract_nominal_type_definition) {
					if ((object_cast($payload_definition, abstract_nominal_type_definition::class)->nominal_kind() === nominal_type_kind::interface_type)
						&& ($ownership_family !== ownership_kind::weak)) {
						throw new \InvalidArgumentException('Interface ownership permits weak<I>; use the default interface type for shared ownership');
					}
					if ((object_cast($payload_definition, abstract_nominal_type_definition::class)->nominal_kind() === nominal_type_kind::class_type)
						&& ($ownership_family === ownership_kind::shared)) {
						throw new \InvalidArgumentException('shared<Class> repeats the declaration default storage');
					}
				}
			}
		}
		foreach ($complete as $position => $argument)
		{
			foreach ($definition->parameter($position)->contracts() as $required) {
				$required_contract /** generic_contract */ = $required;
				$supported = false;
				if ($capabilities === null) {
					$supported = $this->has_capability($argument, $required_contract);
				}
				else {
					$supported = $capabilities->has_capability($argument, $required_contract);
				}
				if (!$supported) {
					throw new \InvalidArgumentException('Template type argument does not declare required capability '
						. Generic_Contract_Name::text($required_contract) . ' for ' . $definition->name());
				}
			}
		}
		return $complete;
	}

	/** Reject an impossible argument list before preparing any nested application. */
	public function validate_application_arity(abstract_template_type_definition $definition, int $argument_count): void
	{
		$this->require_registered_definition($definition);
		if (($argument_count < $definition->required_arity()) || ($argument_count > $definition->arity())) {
			throw new \InvalidArgumentException('Template type argument count does not match definition arity');
		}
	}

	private function allocate_definition_id(): int
	{
		if ($this->definition_ids_exhausted) {
			throw new \OverflowException('Type definition identity space exhausted');
		}
		$identity = $this->next_definition_id;
		if ($identity === Type_Identity::maximum()) {
			$this->definition_ids_exhausted = true;
		}
		else {
			$this->next_definition_id++;
		}
		return (int)$identity;
	}

	private function allocate_type_id(): int
	{
		if ($this->type_ids_exhausted) {
			throw new \OverflowException('Canonical type identity space exhausted');
		}
		$identity = $this->next_type_id;
		if ($identity === Type_Identity::maximum()) {
			$this->type_ids_exhausted = true;
		}
		else {
			$this->next_type_id++;
		}
		return (int)$identity;
	}
}

/** Keep enum reflection beside its declaration for native lowering. */
final class Record_Storage_Kind_Name
{
	public static function text(record_storage_kind $kind): string
	{
		return enum_name($kind);
	}
}

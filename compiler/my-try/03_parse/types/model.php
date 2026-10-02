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
}

enum type_definition_kind: int
{
	case boolean = 1;
	case integer = 2;
	case floating = 3;
	case nominal = 4;
	case template = 5;
	case no_value = 6;
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

/** A source or predefined structure/class definition; its members retain their existing owners. */
final class nominal_type_definition extends semantic_type_definition implements concrete_type_definition_i
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
	private ?canonical_type_use $default_type_data = null;

	/**
	 * A null contract list means the source-language bare-T default. Runtime
	 * definitions pass an explicit list, including an empty list when no provider
	 * capability requirement is currently known.
	 */
	public function __construct(string $name, ?array $contracts /** vector<generic_contract> */ = null,
		?canonical_type_use $default_type = null)
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
	}

	public function name(): string
	{
		return $this->parameter_name;
	}

	public function contracts(): array /** vector<generic_contract> */
	{
		return $this->parameter_contracts;
	}

	public function default_type(): ?canonical_type_use
	{
		return $this->default_type_data;
	}
}

/** A template is a definition recipe and never a concrete type by itself. */
final class template_type_definition extends semantic_type_definition
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
	private nominal_type_definition $nominal_definition_data;

	public function __construct(int $identity, nominal_type_definition $definition)
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

	public function nominal_definition(): nominal_type_definition
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
	private template_type_definition $template_definition_data;
	private array $argument_types /** vector<canonical_type_use> */;

	public function __construct(int $identity, template_type_definition $definition,
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

	public function template_definition(): template_type_definition
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
	public function intern_application(template_type_definition $definition,
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
		if ($definition instanceof nominal_type_definition) {
			$nominal = object_cast($definition, nominal_type_definition::class);
			return $nominal->nominal_kind() === nominal_type_kind::structure;
		}
		if ($definition instanceof template_type_definition) {
			$template_definition = object_cast($definition, template_type_definition::class);
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

	/** Query only stored facts; this performs no structural or lifecycle inference. */
	public function has_capability(canonical_type_use $type_use, generic_contract $capability): bool
	{
		$definition = $this->type($type_use->type_id())->definition();
		if ($definition->origin() === type_definition_origin::source) {
			throw new \LogicException('Source type capabilities require completed declaration preparation');
		}
		return $definition->declares_capability($capability);
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
	public function complete_application_arguments(template_type_definition $definition,
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
	public function validate_application(template_type_definition $definition,
		array $arguments /** vector<canonical_type_use> */, ?type_capability_query $capabilities = null): array /** vector<canonical_type_use> */
	{
		$complete = $this->complete_application_arguments($definition, $arguments);
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
	public function validate_application_arity(template_type_definition $definition, int $argument_count): void
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

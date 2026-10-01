<?php

/*
 * Role: Canonical Simple C++ type definitions, concrete identities and exact
 * template-application interning. This review candidate contains no backend
 * spelling and is not bootstrapped into the current compiler yet.
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

/** Bare type parameters use this contract; richer contracts remain future work. */
enum generic_contract: int
{
	case copyable_value = 1;
}

/** Validate the explicit uint32 boundary shared by definition and concrete IDs. */
final class Type_Identity
{
	public const MAX = 4294967295;

	public static function require_valid(int $identity, string $description): void
	{
		if (($identity <= 0) || ($identity > self::MAX)) {
			throw new \OutOfRangeException($description . ' must be a positive uint32');
		}
	}
}

interface type_definition_i
{
	public function definition_id(): int;
	public function name(): string;
	public function origin(): type_definition_origin;
	public function kind(): type_definition_kind;
}

/** A definition that denotes one concrete type before any template application. */
interface concrete_type_definition_i extends type_definition_i
{
	public function create_canonical_type(int $type_id): canonical_type_i;
}

/** Shared identity and source-facing name; subtype state remains invariant-specific. */
abstract class semantic_type_definition implements type_definition_i
{
	private int $identity /** uint32 */;
	private string $source_name;
	private type_definition_origin $definition_origin;

	public function __construct(int $identity, string $source_name, type_definition_origin $origin)
	{
		Type_Identity::require_valid($identity, 'Type definition identity');
		if ($source_name === '') {
			throw new \InvalidArgumentException('Type definition name must not be empty');
		}
		$this->identity = $identity;
		$this->source_name = $source_name;
		$this->definition_origin = $origin;
	}

	public function definition_id(): int
	{
		return $this->identity;
	}

	public function name(): string
	{
		return $this->source_name;
	}

	public function origin(): type_definition_origin
	{
		return $this->definition_origin;
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
		int $width, bool $is_signed)
	{
		parent::__construct($identity, $source_name, $origin);
		if (($width <= 0) || ($width > Type_Identity::MAX)) {
			throw new \OutOfRangeException('Integer width must be a positive uint32');
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
		return $this->width;
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
		floating_format $format, int $stored_bits, int $precision_bits)
	{
		parent::__construct($identity, $source_name, $origin);
		if (($stored_bits <= 0) || ($stored_bits > Type_Identity::MAX)
			|| ($precision_bits <= 0) || ($precision_bits > $stored_bits)) {
			throw new \OutOfRangeException('Floating storage and precision must be valid positive bit counts');
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
		return $this->stored_bits;
	}

	public function precision_bits(): int
	{
		return $this->precision;
	}

	public function create_canonical_type(int $type_id): canonical_type_i
	{
		return new floating_type($type_id, $this);
	}
}

/** A source or predefined structure/class definition; its members retain their existing owners. */
final class nominal_type_definition extends semantic_type_definition implements concrete_type_definition_i
{
	private nominal_type_kind $nominal_kind;

	public function __construct(int $identity, string $source_name, type_definition_origin $origin,
		nominal_type_kind $nominal_kind)
	{
		parent::__construct($identity, $source_name, $origin);
		$this->nominal_kind = $nominal_kind;
	}

	public function kind(): type_definition_kind
	{
		return type_definition_kind::nominal;
	}

	public function nominal_kind(): nominal_type_kind
	{
		return $this->nominal_kind;
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

/** Ordered formal identity is its owning template definition plus this zero-based position. */
final class template_type_parameter
{
	private int $parameter_position /** uint32 */;
	private string $parameter_name;
	private generic_contract $parameter_contract;

	public function __construct(int $position, string $name,
		generic_contract $contract = generic_contract::copyable_value)
	{
		if (($position < 0) || ($position > Type_Identity::MAX)) {
			throw new \OutOfRangeException('Template parameter position must be a uint32');
		}
		if ($name === '') {
			throw new \InvalidArgumentException('Template parameter name must not be empty');
		}
		$this->parameter_position = $position;
		$this->parameter_name = $name;
		$this->parameter_contract = $contract;
	}

	public function position(): int
	{
		return $this->parameter_position;
	}

	public function name(): string
	{
		return $this->parameter_name;
	}

	public function contract(): generic_contract
	{
		return $this->parameter_contract;
	}
}

/** A template is a definition recipe and never a concrete type by itself. */
final class template_type_definition extends semantic_type_definition
{
	private nominal_type_kind $result_kind;
	private array $ordered_parameters /** vector<template_type_parameter> */;

	/** Validate stable positional identity and reject duplicate formal names. */
	public function __construct(int $identity, string $source_name, type_definition_origin $origin,
		nominal_type_kind $result_kind, array $parameters /** vector<template_type_parameter> */)
	{
		parent::__construct($identity, $source_name, $origin);
		if ($parameters === []) {
			throw new \InvalidArgumentException('Template definition requires at least one type parameter');
		}

		$expected_position = 0;
		$names /** hash<string, bool> */ = [];
		foreach ($parameters as $position => $parameter)
		{
			if (($position !== $expected_position) || ($parameter->position() !== $expected_position)) {
				throw new \InvalidArgumentException('Template parameters must use ordered zero-based positions');
			}
			if (isset($names[$parameter->name()])) {
				throw new \InvalidArgumentException('Template parameter names must be unique');
			}
			$names[$parameter->name()] = true;
			$expected_position++;
		}

		$this->result_kind = $result_kind;
		$this->ordered_parameters = $parameters;
	}

	public function kind(): type_definition_kind
	{
		return type_definition_kind::template;
	}

	public function result_kind(): nominal_type_kind
	{
		return $this->result_kind;
	}

	public function parameters(): array /** vector<template_type_parameter> */
	{
		return $this->ordered_parameters;
	}

	public function arity(): int
	{
		return q_count($this->ordered_parameters);
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
	private type_definition_i $type_definition;

	public function __construct(int $identity, type_definition_i $definition)
	{
		Type_Identity::require_valid($identity, 'Canonical type identity');
		$this->identity = $identity;
		$this->type_definition = $definition;
	}

	public function type_id(): int
	{
		return $this->identity;
	}

	public function definition(): type_definition_i
	{
		return $this->type_definition;
	}
}

final class boolean_type extends canonical_type
{
	private boolean_type_definition $boolean_definition;

	public function __construct(int $identity, boolean_type_definition $definition)
	{
		parent::__construct($identity, $definition);
		$this->boolean_definition = $definition;
	}

	public function family(): type_family
	{
		return type_family::boolean;
	}

	public function boolean_definition(): boolean_type_definition
	{
		return $this->boolean_definition;
	}
}

final class integer_type extends canonical_type
{
	private integer_type_definition $integer_definition;

	public function __construct(int $identity, integer_type_definition $definition)
	{
		parent::__construct($identity, $definition);
		$this->integer_definition = $definition;
	}

	public function family(): type_family
	{
		return type_family::integer;
	}

	public function integer_definition(): integer_type_definition
	{
		return $this->integer_definition;
	}
}

final class floating_type extends canonical_type
{
	private floating_type_definition $floating_definition;

	public function __construct(int $identity, floating_type_definition $definition)
	{
		parent::__construct($identity, $definition);
		$this->floating_definition = $definition;
	}

	public function family(): type_family
	{
		return type_family::floating;
	}

	public function floating_definition(): floating_type_definition
	{
		return $this->floating_definition;
	}
}

final class nominal_type extends canonical_type
{
	private nominal_type_definition $nominal_definition;

	public function __construct(int $identity, nominal_type_definition $definition)
	{
		parent::__construct($identity, $definition);
		$this->nominal_definition = $definition;
	}

	public function family(): type_family
	{
		return type_family::nominal;
	}

	public function nominal_definition(): nominal_type_definition
	{
		return $this->nominal_definition;
	}
}

final class no_value_type extends canonical_type
{
	private no_value_type_definition $no_value_definition;

	public function __construct(int $identity, no_value_type_definition $definition)
	{
		parent::__construct($identity, $definition);
		$this->no_value_definition = $definition;
	}

	public function family(): type_family
	{
		return type_family::no_value;
	}

	public function no_value_definition(): no_value_type_definition
	{
		return $this->no_value_definition;
	}
}

/** One canonical specialization of a template definition and exact ordered type arguments. */
final class applied_template_type extends canonical_type
{
	private template_type_definition $template_definition;
	private array $arguments /** vector<canonical_type_use> */;

	public function __construct(int $identity, template_type_definition $definition,
		array $arguments /** vector<canonical_type_use> */)
	{
		parent::__construct($identity, $definition);
		$this->template_definition = $definition;
		$this->arguments = $arguments;
	}

	public function family(): type_family
	{
		return type_family::nominal;
	}

	public function template_definition(): template_type_definition
	{
		return $this->template_definition;
	}

	public function arguments(): array /** vector<canonical_type_use> */
	{
		return $this->arguments;
	}
}

/** Lightweight prepared fact attached to a source occurrence; `value<T>` sets by_value. */
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
		return $this->identity;
	}

	public function by_value(): bool
	{
		return $this->use_by_value;
	}
}

/** Own numeric identities, canonical concrete types and exact template application reuse. */
final class Type_Registry
{
	private int $next_definition_id /** uint64 */ = 1;
	private int $next_type_id /** uint64 */ = 1;
	private array $definitions /** hash<int, type_definition_i> */ = [];
	private array $types /** hash<int, canonical_type_i> */ = [];
	private array $concrete_types /** hash<int, int> */ = [];
	private array $applied_types /** hash<string, int> */ = [];

	public function define_boolean(string $name, type_definition_origin $origin): boolean_type_definition
	{
		$definition = new boolean_type_definition($this->allocate_definition_id(), $name, $origin);
		$this->register_definition($definition);
		return $definition;
	}

	public function define_integer(string $name, type_definition_origin $origin,
		int $bit_width, bool $signed): integer_type_definition
	{
		$definition = new integer_type_definition($this->allocate_definition_id(), $name, $origin,
			$bit_width, $signed);
		$this->register_definition($definition);
		return $definition;
	}

	public function define_floating(string $name, type_definition_origin $origin,
		floating_format $format, int $storage_bits, int $precision_bits): floating_type_definition
	{
		$definition = new floating_type_definition($this->allocate_definition_id(), $name, $origin,
			$format, $storage_bits, $precision_bits);
		$this->register_definition($definition);
		return $definition;
	}

	public function define_nominal(string $name, type_definition_origin $origin,
		nominal_type_kind $kind): nominal_type_definition
	{
		$definition = new nominal_type_definition($this->allocate_definition_id(), $name, $origin, $kind);
		$this->register_definition($definition);
		return $definition;
	}

	public function define_no_value(string $name, type_definition_origin $origin): no_value_type_definition
	{
		$definition = new no_value_type_definition($this->allocate_definition_id(), $name, $origin);
		$this->register_definition($definition);
		return $definition;
	}

	public function define_template(string $name, type_definition_origin $origin,
		nominal_type_kind $result_kind, array $parameters /** vector<template_type_parameter> */): template_type_definition
	{
		$definition = new template_type_definition($this->allocate_definition_id(), $name, $origin,
			$result_kind, $parameters);
		$this->register_definition($definition);
		return $definition;
	}

	/** Reuse the sole concrete type belonging to a non-template definition. */
	public function canonical(concrete_type_definition_i $definition): canonical_type_i
	{
		$this->require_registered_definition($definition);
		$definition_id = $definition->definition_id();
		$type_id = $this->concrete_types[$definition_id] ?? 0;
		if ($type_id !== 0) {
			return $this->type($type_id);
		}

		$type = $definition->create_canonical_type($this->allocate_type_id());
		$this->register_type($type);
		$this->concrete_types[$definition_id] = $type->type_id();
		return $type;
	}

	/** Intern exactly one canonical type for a definition and ordered canonical arguments. */
	public function intern_application(template_type_definition $definition,
		array $arguments /** vector<canonical_type_use> */): applied_template_type
	{
		$this->require_registered_definition($definition);
		$this->require_arguments($definition, $arguments);
		$key = $this->application_key($definition->definition_id(), $arguments);
		$type_id = $this->applied_types[$key] ?? 0;
		if ($type_id !== 0) {
			$type = $this->type($type_id);
			return object_cast($type, applied_template_type::class);
		}

		$type = new applied_template_type($this->allocate_type_id(), $definition, $arguments);
		$this->register_type($type);
		$this->applied_types[$key] = $type->type_id();
		return $type;
	}

	public function definition(int $definition_id): type_definition_i
	{
		Type_Identity::require_valid($definition_id, 'Type definition identity');
		$definition = $this->definitions[$definition_id] ?? null;
		if ($definition === null) {
			throw new \OutOfBoundsException('Unknown type definition identity');
		}
		return $definition;
	}

	public function type(int $type_id): canonical_type_i
	{
		Type_Identity::require_valid($type_id, 'Canonical type identity');
		$type = $this->types[$type_id] ?? null;
		if ($type === null) {
			throw new \OutOfBoundsException('Unknown canonical type identity');
		}
		return $type;
	}

	/** Create a compact occurrence fact only for an identity owned by this registry. */
	public function use(int $type_id, bool $by_value = false): canonical_type_use
	{
		$this->type($type_id);
		return new canonical_type_use($type_id, $by_value);
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
		$current = $this->definitions[$definition->definition_id()] ?? null;
		if ($current !== $definition) {
			throw new \InvalidArgumentException('Type definition belongs to another registry');
		}
	}

	/** Validate arity, list order and membership without interpreting argument capabilities. */
	private function require_arguments(template_type_definition $definition,
		array $arguments /** vector<canonical_type_use> */): void
	{
		if (q_count($arguments) !== $definition->arity()) {
			throw new \InvalidArgumentException('Template type argument count does not match definition arity');
		}

		$expected_position = 0;
		foreach ($arguments as $position => $argument)
		{
			if (($position !== $expected_position) || !($argument instanceof canonical_type_use)) {
				throw new \InvalidArgumentException('Template type arguments must be an ordered list');
			}
			$this->type($argument->type_id());
			$expected_position++;
		}
	}

	/** Encode the complete tuple reversibly; this string indexes identity but is not an identity. */
	private function application_key(int $definition_id,
		array $arguments /** vector<canonical_type_use> */): string
	{
		$key = (string)$definition_id . ':' . (string)q_count($arguments);
		foreach ($arguments as $argument) {
			$value_flag = $argument->by_value() ? '1' : '0';
			$key .= ':' . (string)$argument->type_id() . ':' . $value_flag;
		}
		return $key;
	}

	private function allocate_definition_id(): int
	{
		if ($this->next_definition_id > Type_Identity::MAX) {
			throw new \OverflowException('Type definition identity space exhausted');
		}
		$identity = $this->next_definition_id;
		$this->next_definition_id++;
		return $identity;
	}

	private function allocate_type_id(): int
	{
		if ($this->next_type_id > Type_Identity::MAX) {
			throw new \OverflowException('Canonical type identity space exhausted');
		}
		$identity = $this->next_type_id;
		$this->next_type_id++;
		return $identity;
	}
}

<?php

/* Role: C++ representation bindings for canonical semantic definitions and type-use modifiers. */
namespace scpp\compiler;

final class cpp_type_definition_binding
{
	private int $semantic_definition_id /** uint32 */;
	private string $cpp_name;
	private string $required_header;

	public function __construct(int $definition_id, string $name, string $header)
	{
		Type_Identity::require_valid($definition_id, 'C++ binding definition identity');
		if ($name === '') {
			throw new \InvalidArgumentException('C++ type binding name must not be empty');
		}
		$this->semantic_definition_id = $definition_id;
		$this->cpp_name = $name;
		$this->required_header = $header;
	}

	public function definition_id(): int
	{
		return (int)$this->semantic_definition_id;
	}

	public function name(): string
	{
		return $this->cpp_name;
	}

	public function header(): string
	{
		return $this->required_header;
	}
}

final class cpp_type_modifier_binding
{
	private type_use_modifier_kind $modifier_kind;
	private string $cpp_name;
	private string $required_header;

	public function __construct(type_use_modifier_kind $kind, string $name, string $header)
	{
		if ($name === '') {
			throw new \InvalidArgumentException('C++ type modifier binding name must not be empty');
		}
		$this->modifier_kind = $kind;
		$this->cpp_name = $name;
		$this->required_header = $header;
	}

	public function kind(): type_use_modifier_kind
	{
		return $this->modifier_kind;
	}

	public function name(): string
	{
		return $this->cpp_name;
	}

	public function header(): string
	{
		return $this->required_header;
	}
}

/** Backend bindings remain independent of source spellings and provider identities. */
final class CPP_Type_Bindings
{
	private array $definitions /** hash<cpp_type_definition_binding, int> */ = [];
	private array $modifiers /** hash<cpp_type_modifier_binding> */ = [];

	public function bind_definition(type_definition_i $definition, string $name, string $header): void
	{
		$id = $definition->definition_id();
		if (isset($this->definitions[$id])) {
			throw new \LogicException('Duplicate C++ type definition binding');
		}
		$this->definitions[$id] = new cpp_type_definition_binding($id, $name, $header);
	}

	public function bind_modifier(type_use_modifier_kind $kind, string $name, string $header): void
	{
		$key = Type_Use_Modifier_Name::text($kind);
		if (isset($this->modifiers[$key])) {
			throw new \LogicException('Duplicate C++ type modifier binding');
		}
		$this->modifiers[$key] = new cpp_type_modifier_binding($kind, $name, $header);
	}

	public function definition(int $definition_id): cpp_type_definition_binding
	{
		Type_Identity::require_valid($definition_id, 'C++ binding definition identity');
		if (!isset($this->definitions[$definition_id])) {
			throw new \OutOfBoundsException('Semantic type definition has no C++ binding');
		}
		return $this->definitions[$definition_id];
	}

	public function modifier(type_use_modifier_kind $kind): cpp_type_modifier_binding
	{
		$key = Type_Use_Modifier_Name::text($kind);
		if (!isset($this->modifiers[$key])) {
			throw new \OutOfBoundsException('Type-use modifier has no C++ binding');
		}
		return $this->modifiers[$key];
	}

	public function definition_count(): int
	{
		return q_count($this->definitions);
	}
}

final class CPP_Type_Binding_Registration
{
	/** Bind every installed definition without consulting its source spelling during emission. */
	public static function install(registered_type_catalog $types): CPP_Type_Bindings
	{
		$bindings = new CPP_Type_Bindings();
		self::bind_scalars($bindings, $types);
		self::bind_runtime_types($bindings, $types);
		$bindings->bind_modifier(type_use_modifier_kind::by_value, 'scpp::value_p', 'scpp/value_p.hpp');
		return $bindings;
	}

	/** Bind each concrete built-in identity; source aliases share its one binding. */
	private static function bind_scalars(CPP_Type_Bindings $bindings, registered_type_catalog $types): void
	{
		$bindings->bind_definition($types->definition('void'), 'void', '');
		$bindings->bind_definition($types->definition('bool'), 'scpp::bool_t', 'scpp/bool_t.hpp');
		foreach ([8, 16, 32, 64] as $bits)
		{
			$bindings->bind_definition($types->definition('int' . $bits),
				'scpp::int_t<std::int' . $bits . '_t>', 'scpp/int_t.hpp');
			$bindings->bind_definition($types->definition('uint' . $bits),
				'scpp::int_t<std::uint' . $bits . '_t>', 'scpp/int_t.hpp');
		}
		$bindings->bind_definition($types->definition('int'), 'scpp::int_t<>', 'scpp/int_t.hpp');
		$bindings->bind_definition($types->definition('float'), 'scpp::float_t', 'scpp/float_t.hpp');
		$bindings->bind_definition($types->definition('string'), 'scpp::string_t', 'scpp/string_t.hpp');
	}

	/** Bind runtime semantic families without adding their backend names to source lookup. */
	private static function bind_runtime_types(CPP_Type_Bindings $bindings,
		registered_type_catalog $types): void
	{
		$bindings->bind_definition($types->definition('vector'), 'scpp::vector_t', 'scpp/vector_t.hpp');
		$bindings->bind_definition($types->definition('hash'), 'scpp::hash_t', 'scpp/hash_t.hpp');
		$bindings->bind_definition($types->definition('nullable'), 'scpp::nullable', 'scpp/nullable.hpp');
		$bindings->bind_definition($types->definition('shared'), 'scpp::shared_p', 'scpp/shared_p.hpp');
		$bindings->bind_definition($types->definition('weak'), 'scpp::weak_p', 'scpp/weak_p.hpp');
		$bindings->bind_definition($types->definition('unique'), 'scpp::unique_p', 'scpp/unique_p.hpp');
	}
}

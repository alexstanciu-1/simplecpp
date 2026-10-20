<?php

/* Role: C++ representation bindings for canonical semantic definitions and type-use modifiers. */
namespace scpp\compiler;

enum cpp_type_argument_kind { case native_type; case parameter; case modified_type; }

/** Resolved physical argument; semantic identity and admission stay in the registry. */
final class cpp_type_argument_binding
{
	public cpp_type_argument_kind $kind;
	public int $position = 0;
	public string $native_name = '';
	public string $header = '';
	public bool $payload = false;
}

/** Native type construction, shared by definition and modifier bindings. */
final class cpp_type_recipe
{
	public bool $template_application = false;
	public bool $concrete_boundary = false;
	public string $literal_interface = '';
	public string $byte_interface = '';
	public int $pointee_position = -1;
	public array $arguments /** vector<cpp_type_argument_binding> */ = [];
	public function __construct(public string $symbol, public string $header) {}
}

final class cpp_type_definition_binding
{
	private int $semantic_definition_id /** uint32 */;
	private cpp_type_recipe $native_recipe;

	public function __construct(int $definition_id, cpp_type_recipe $recipe)
	{
		Type_Identity::require_valid($definition_id, 'C++ binding definition identity');
		if ($recipe->symbol === '') {
			throw new \InvalidArgumentException('C++ type binding name must not be empty');
		}
		$this->semantic_definition_id = $definition_id;
		$this->native_recipe = $recipe;
	}

	public function definition_id(): int
	{
		return (int)$this->semantic_definition_id;
	}

	public function name(): string
	{
		return $this->native_recipe->symbol;
	}

	public function header(): string
	{
		return $this->native_recipe->header;
	}
	public function recipe(): cpp_type_recipe { return $this->native_recipe; }
}

final class cpp_type_modifier_binding
{
	private type_use_modifier_kind $modifier_kind;
	private cpp_type_recipe $native_recipe;

	public function __construct(type_use_modifier_kind $kind, cpp_type_recipe $recipe)
	{
		if ($recipe->symbol === '') {
			throw new \InvalidArgumentException('C++ type modifier binding name must not be empty');
		}
		$this->modifier_kind = $kind;
		$this->native_recipe = $recipe;
	}

	public function kind(): type_use_modifier_kind
	{
		return $this->modifier_kind;
	}

	public function name(): string
	{
		return $this->native_recipe->symbol;
	}

	public function header(): string
	{
		return $this->native_recipe->header;
	}
	public function recipe(): cpp_type_recipe { return $this->native_recipe; }
}

/** Backend bindings remain independent of source spellings and provider identities. */
final class CPP_Type_Bindings
{
	private array $definitions /** hash<cpp_type_definition_binding, int> */ = [];
	private array $modifiers /** hash<cpp_type_modifier_binding> */ = [];

	public function bind_definition(type_definition_i $definition, string $name, string $header): void
	{
		$recipe = new cpp_type_recipe($name, $header);
		if ($definition instanceof abstract_template_type_definition) {
			$template_definition = object_cast($definition, abstract_template_type_definition::class);
			$recipe->template_application = true;
			foreach ($template_definition->parameters() as $position => $parameter) {
				$argument = new cpp_type_argument_binding();
				$argument->kind = cpp_type_argument_kind::parameter;
				$argument->position = $position;
				$recipe->arguments[] = $argument;
			}
		}
		$this->bind_recipe($definition, $recipe);
	}

	public function bind_recipe(type_definition_i $definition, cpp_type_recipe $recipe): void
	{
		$id = $definition->definition_id();
		if (isset($this->definitions[$id])) {
			throw new \LogicException('Duplicate C++ type definition binding');
		}
		$this->definitions[$id] = new cpp_type_definition_binding($id, $recipe);
	}

	public function bind_modifier(type_use_modifier_kind $kind, cpp_type_recipe $recipe): void
	{
		$key = Type_Use_Modifier_Name::text($kind);
		if (isset($this->modifiers[$key])) {
			throw new \LogicException('Duplicate C++ type modifier binding');
		}
		$this->modifiers[$key] = new cpp_type_modifier_binding($kind, $recipe);
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
	/** Intrinsic spellings; exposed runtime type representations come from selected catalogs. */
	public static function install(registered_type_catalog $types): CPP_Type_Bindings
	{
		$bindings = new CPP_Type_Bindings();
		$bindings->bind_definition($types->definition('void'), 'void', '');
		// The expression-only sentinel has no source type name for a metadata target.
		$bindings->bind_definition($types->registry()->type($types->null_type()->type_id())->definition(), 'scpp::null_t', 'scpp/null_t.hpp');
		return $bindings;
	}
}

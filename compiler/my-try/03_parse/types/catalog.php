<?php

/* Role: source exposure and runtime-provider identity for semantic type definitions. */
namespace scpp\compiler;

enum type_use_modifier_kind: int
{
	case by_value = 1;
}

final class Type_Use_Modifier_Name
{
	public static function text(type_use_modifier_kind $kind): string
	{
		if ($kind === type_use_modifier_kind::by_value) {
			return 'by_value';
		}
		throw new \LogicException('Unknown type-use modifier');
	}
}

final class source_type_exposure
{
	private string $source_name;
	private int $semantic_definition_id /** uint32 */;

	public function __construct(string $name, int $definition_id)
	{
		if ($name === '') {
			throw new \InvalidArgumentException('Source type name must not be empty');
		}
		Type_Identity::require_valid($definition_id, 'Source type definition identity');
		$this->source_name = $name;
		$this->semantic_definition_id = $definition_id;
	}

	public function name(): string
	{
		return $this->source_name;
	}

	public function definition_id(): int
	{
		return (int)$this->semantic_definition_id;
	}
}

final class source_type_modifier_exposure
{
	private string $source_name;
	private type_use_modifier_kind $modifier_kind;

	public function __construct(string $name, type_use_modifier_kind $kind)
	{
		if ($name === '') {
			throw new \InvalidArgumentException('Source type modifier name must not be empty');
		}
		$this->source_name = $name;
		$this->modifier_kind = $kind;
	}

	public function name(): string
	{
		return $this->source_name;
	}

	public function kind(): type_use_modifier_kind
	{
		return $this->modifier_kind;
	}
}

/** Source lookup owns spellings independently of semantic definition names. */
final class Source_Type_Exposures
{
	private array $types /** hash<source_type_exposure> */ = [];
	private array $modifiers /** hash<source_type_modifier_exposure> */ = [];

	public function expose_type(string $name, type_definition_i $definition): void
	{
		if (isset($this->types[$name]) || isset($this->modifiers[$name])) {
			throw new \LogicException('Duplicate source type exposure: ' . $name);
		}
		$this->types[$name] = new source_type_exposure($name, $definition->definition_id());
	}

	public function modifier_or_null(string $name): ?source_type_modifier_exposure
	{
		if (!isset($this->modifiers[$name])) {
			return null;
		}
		return $this->modifiers[$name];
	}

	public function expose_modifier(string $name, type_use_modifier_kind $kind): void
	{
		if (isset($this->types[$name]) || isset($this->modifiers[$name])) {
			throw new \LogicException('Duplicate source type exposure: ' . $name);
		}
		$this->modifiers[$name] = new source_type_modifier_exposure($name, $kind);
	}

	public function type(string $name): source_type_exposure
	{
		if (!isset($this->types[$name])) {
			throw new \OutOfBoundsException('Unknown source type: ' . $name);
		}
		return $this->types[$name];
	}

	public function modifier(string $name): source_type_modifier_exposure
	{
		if (!isset($this->modifiers[$name])) {
			throw new \OutOfBoundsException('Unknown source type modifier: ' . $name);
		}
		return $this->modifiers[$name];
	}

	public function type_count(): int
	{
		return q_count($this->types);
	}

	public function modifier_count(): int
	{
		return q_count($this->modifiers);
	}
}

/** Stable semantic identity exported by a runtime provider; it is not a C++ name. */
final class runtime_type_provider_identity
{
	private string $provider_name;
	private string $family_name;

	public function __construct(string $provider, string $family)
	{
		if (($provider === '') || ($family === '')) {
			throw new \InvalidArgumentException('Runtime provider and family names must not be empty');
		}
		$this->provider_name = $provider;
		$this->family_name = $family;
	}

	public function provider(): string
	{
		return $this->provider_name;
	}

	public function family(): string
	{
		return $this->family_name;
	}
}

final class runtime_type_provider
{
	private int $semantic_definition_id /** uint32 */;
	private runtime_type_provider_identity $provider_identity;

	public function __construct(int $definition_id, runtime_type_provider_identity $identity)
	{
		Type_Identity::require_valid($definition_id, 'Runtime provider definition identity');
		$this->semantic_definition_id = $definition_id;
		$this->provider_identity = $identity;
	}

	public function definition_id(): int
	{
		return (int)$this->semantic_definition_id;
	}

	public function identity(): runtime_type_provider_identity
	{
		return $this->provider_identity;
	}
}

final class runtime_type_modifier_provider
{
	private type_use_modifier_kind $modifier_kind;
	private runtime_type_provider_identity $provider_identity;

	public function __construct(type_use_modifier_kind $kind, runtime_type_provider_identity $identity)
	{
		$this->modifier_kind = $kind;
		$this->provider_identity = $identity;
	}

	public function kind(): type_use_modifier_kind
	{
		return $this->modifier_kind;
	}

	public function identity(): runtime_type_provider_identity
	{
		return $this->provider_identity;
	}
}

/** Provider registrations are keyed by semantic definition, never backend spelling. */
final class Runtime_Type_Providers
{
	private array $providers /** hash<runtime_type_provider, int> */ = [];
	private array $modifier_providers /** hash<runtime_type_modifier_provider> */ = [];
	private array $identities /** hash<bool> */ = [];

	/** Attach one unique provider identity to a semantic runtime definition. */
	public function register(type_definition_i $definition, string $provider, string $family): void
	{
		$id = $definition->definition_id();
		if (isset($this->providers[$id])) {
			throw new \LogicException('Duplicate runtime provider for semantic type definition');
		}
		$key = $this->reserve_identity($provider, $family);
		$identity = new runtime_type_provider_identity($provider, $family);
		$this->providers[$id] = new runtime_type_provider($id, $identity);
		$this->identities[$key] = true;
	}

	/** Keep modifier provider identity separate from its eventual backend wrapper. */
	public function register_modifier(type_use_modifier_kind $kind, string $provider, string $family): void
	{
		$key = Type_Use_Modifier_Name::text($kind);
		if (isset($this->modifier_providers[$key])) {
			throw new \LogicException('Duplicate runtime provider for type-use modifier');
		}
		$identity_key = $this->reserve_identity($provider, $family);
		$identity = new runtime_type_provider_identity($provider, $family);
		$this->modifier_providers[$key] = new runtime_type_modifier_provider($kind, $identity);
		$this->identities[$identity_key] = true;
	}

	public function provider_for(int $definition_id): runtime_type_provider
	{
		Type_Identity::require_valid($definition_id, 'Runtime provider definition identity');
		if (!isset($this->providers[$definition_id])) {
			throw new \OutOfBoundsException('Semantic type definition has no runtime provider');
		}
		return $this->providers[$definition_id];
	}

	public function modifier_provider(type_use_modifier_kind $kind): runtime_type_modifier_provider
	{
		$key = Type_Use_Modifier_Name::text($kind);
		if (!isset($this->modifier_providers[$key])) {
			throw new \OutOfBoundsException('Type-use modifier has no runtime provider');
		}
		return $this->modifier_providers[$key];
	}

	public function count(): int
	{
		return q_count($this->providers) + q_count($this->modifier_providers);
	}

	private function reserve_identity(string $provider, string $family): string
	{
		$key = q_strlen($provider) . ':' . $provider . q_strlen($family) . ':' . $family;
		if (isset($this->identities[$key])) {
			throw new \LogicException('Duplicate runtime provider type identity');
		}
		return $key;
	}
}

/** One non-owning association from a semantic definition to collected source syntax. */
final class source_type_declaration
{
	/** @storage.reference collected_file.entries @reference.weak */
	private collected_struct $source_declaration /** weak<collected_struct> */;

	public function __construct(collected_struct $declaration)
	{
		$this->source_declaration = $declaration;
	}

	public function declaration(): collected_struct
	{
		return object_cast(weakref_get($this->source_declaration), collected_struct::class);
	}
}

/** Source syntax remains owned by collected files; this is a non-owning identity index. */
final class Source_Type_Declarations
{
	private array $declarations /** hash<source_type_declaration, int> */ = [];

	public function register(nominal_type_definition $definition, collected_struct $declaration): void
	{
		if ($definition->origin() !== type_definition_origin::source) {
			throw new \InvalidArgumentException('Source declaration requires a source type definition');
		}
		$id = $definition->definition_id();
		if (isset($this->declarations[$id])) {
			throw new \LogicException('Duplicate source type declaration association');
		}
		$this->declarations[$id] = new source_type_declaration($declaration);
	}

	public function declaration(int $definition_id): collected_struct
	{
		Type_Identity::require_valid($definition_id, 'Source type definition identity');
		if (!isset($this->declarations[$definition_id])) {
			throw new \OutOfBoundsException('Source type definition has no declaration association');
		}
		return $this->declarations[$definition_id]->declaration();
	}

	public function count(): int
	{
		return q_count($this->declarations);
	}

	/** Remove every semantic association for one retired collected declaration. */
	public function retire(collected_struct $declaration): void
	{
		foreach ($this->declarations as $definition_id => $association) {
			if ($association->declaration() === $declaration) {
				unset($this->declarations[$definition_id]);
			}
		}
	}
}

/** Complete semantic installation result; backend bindings are deliberately absent. */
final class registered_type_catalog
{
	private Type_Registry $type_registry;
	private Source_Type_Exposures $source_exposures;
	private Runtime_Type_Providers $runtime_providers;
	private Source_Type_Declarations $source_declaration_index;

	public function __construct(Type_Registry $registry, Source_Type_Exposures $source,
		Runtime_Type_Providers $providers)
	{
		$this->type_registry = $registry;
		$this->source_exposures = $source;
		$this->runtime_providers = $providers;
		$this->source_declaration_index = new Source_Type_Declarations();
	}

	public function registry(): Type_Registry
	{
		return $this->type_registry;
	}

	public function source(): Source_Type_Exposures
	{
		return $this->source_exposures;
	}

	public function providers(): Runtime_Type_Providers
	{
		return $this->runtime_providers;
	}

	public function source_declarations(): Source_Type_Declarations
	{
		return $this->source_declaration_index;
	}

	/** Create one source nominal identity while syntax remains owned by its collected file. */
	public function define_source_structure(string $name, collected_struct $declaration): nominal_type
	{
		$capabilities /** vector<generic_contract> */ = [];
		$definition = $this->type_registry->define_nominal($name, type_definition_origin::source,
			nominal_type_kind::structure, $capabilities);
		$this->source_declaration_index->register($definition, $declaration);
		$type = $this->type_registry->canonical($definition);
		return object_cast($type, nominal_type::class);
	}

	public function definition(string $source_name): type_definition_i
	{
		$exposure = $this->source_exposures->type($source_name);
		return $this->type_registry->definition($exposure->definition_id());
	}

	public function canonical(string $source_name): canonical_type_i
	{
		$definition = $this->definition($source_name);
		$concrete = object_cast($definition, concrete_type_definition_i::class);
		return $this->type_registry->canonical($concrete);
	}
}

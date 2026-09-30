<?php

/* Role: encapsulate shared lexical and published scope indexes. */
namespace scpp\compiler;

final class scope
{
	/** Upward observers; owners are Model roots or parsed_file.scopes. */
	private ?scope $enclosing /** weak<scope> */ = null;
	private ?scope $publication /** weak<scope> */ = null;
	private bool $function_boundary = false;
	private Key_Storage_List $preparation_lookups /** Key_Storage_List<preparation_lookup> */;
	private array $template_parameters /** hash<int> */ = [];
	/** @storage.reference collected_file.entries @reference.weak */
	private Key_Storage_List $variables /** Key_Storage_List<collected_name> */;
	/** @storage.reference collected_file.entries @reference.weak */
	private Key_Storage_List $functions /** Key_Storage_List<collected_function> */;
	/** Definitions owned here; published scopes retain the same definition objects. */
	private Key_Storage_List $types /** Key_Storage_List<type_definition> */;
	/** Constant namespace is independent of variables, functions and types. */
	private Key_Storage_List $constants /** Key_Storage_List<constant_definition> */;

	public function __construct()
	{
		$this->variables = new Key_Storage_List /** Key_Storage_List<collected_name> */();
		$this->preparation_lookups = new Key_Storage_List /** Key_Storage_List<preparation_lookup> */();
		$this->functions = new Key_Storage_List /** Key_Storage_List<collected_function> */();
		$this->types = new Key_Storage_List /** Key_Storage_List<type_definition> */();
		$this->constants = new Key_Storage_List /** Key_Storage_List<constant_definition> */();
	}

	public function set_parent(scope $parent): void
	{
		$this->enclosing = $parent;
	}

	public function parent_scope(): ?scope
	{
		return weakref_get($this->enclosing);
	}

	public function published_scope(): ?scope
	{
		return weakref_get($this->publication);
	}

	public function mark_function(): void
	{
		$this->function_boundary = true;
	}

	public function is_function(): bool
	{
		return $this->function_boundary;
	}

	public function set_templates(array $slots /** hash<int> */): void
	{
		$this->template_parameters = $slots;
	}

	public function has_template(string $name): bool
	{
		return isset($this->template_parameters[$name]);
	}

	public function template_slot(string $name): int
	{
		return $this->template_parameters[$name];
	}

	/** Register source identities without merging duplicates or doing name resolution. */
	public function register(collected_name $entry): void
	{
		if ($entry instanceof collected_function) {
			$functions /** Key_Storage_List<collected_function> */ = $this->functions;
			$function = object_cast($entry, collected_function::class);
			$functions->add($function->name, $function);
		}
		else {
			$variables /** Key_Storage_List<collected_name> */ = $this->variables;
			$variables->add($entry->name, $entry);
		}
	}

	public function register_type(type_definition $definition): void
	{
		$types /** Key_Storage_List<type_definition> */ = $this->types;
		$types->add($definition->name, $definition);
	}

	public function register_constant(constant_definition $definition): void
	{
		$constants /** Key_Storage_List<constant_definition> */ = $this->constants;
		$constants->add($definition->name, $definition);
	}

	/** Remove index membership by identity; same-name declarations from other owners survive. */
	public function unregister(collected_name $entry): void
	{
		$functions /** Key_Storage_List<collected_function> */ = $this->functions;
		$variables /** Key_Storage_List<collected_name> */ = $this->variables;
		$types /** Key_Storage_List<type_definition> */ = $this->types;
		if ($entry instanceof collected_function) {
			$functions->remove($entry->name, object_cast($entry, collected_function::class));
		}
		$variables->remove($entry->name, $entry);
		foreach ($types->named($entry->name) as $definition) {
			if ($definition->declaration === $entry) {
				$types->remove($entry->name, $definition);
			}
		}
	}

	/** Lookups are grouped by name; the kind disambiguates independent type/function pools. */
	public function preparation_lookups_named(string $name): array /** vector<preparation_lookup> */
	{
		$items /** Key_Storage_List<preparation_lookup> */ = $this->preparation_lookups;
		return $items->named($name);
	}

	public function add_preparation_lookup(preparation_lookup $lookup): void
	{
		$items /** Key_Storage_List<preparation_lookup> */ = $this->preparation_lookups;
		$items->add($lookup->name, $lookup);
	}

	public function remove_preparation_lookup(preparation_lookup $lookup): void
	{
		$items /** Key_Storage_List<preparation_lookup> */ = $this->preparation_lookups;
		$items->remove($lookup->name, $lookup);
	}

	/** Local inventories include tombstones; callers choose live resolution explicitly. */
	public function variables_named(string $name): array /** vector<collected_name> */
	{
		$items /** Key_Storage_List<collected_name> */ = $this->variables;
		return $items->named($name);
	}

	/** Return a typed snapshot; an absent name has no candidates. */
	public function functions_named(string $name): array /** vector<collected_function> */
	{
		$items /** Key_Storage_List<collected_function> */ = $this->functions;
		return $items->named($name);
	}

	/** The small definition store owns records; lookup does not introduce another registry. */
	public function types_named(string $name): array /** vector<type_definition> */
	{
		$items /** Key_Storage_List<type_definition> */ = $this->types;
		return $items->named($name);
	}

	/** Constant lookup is exact and case-sensitive; callers select lexical precedence. */
	public function constants_named(string $name): array /** vector<constant_definition> */
	{
		$items /** Key_Storage_List<constant_definition> */ = $this->constants;
		return $items->named($name);
	}

	/** Source-only identity snapshot for preparation observations and legacy consumers. */
	public function source_types_named(string $name): array /** vector<collected_struct> */
	{
		$result /** vector<collected_struct> */ = [];
		foreach ($this->types_named($name) as $definition) {
			if ($definition->declaration !== null) {
				$entry /** collected_struct */ = $definition->declaration;
				$result[] = $entry;
			}
		}
		return $result;
	}

	public function has_functions(): bool
	{
		return !$this->functions->is_empty();
	}

	public function has_variables(): bool
	{
		return !$this->variables->is_empty();
	}

	public function set_publication(scope $global): void
	{
		$this->publication = $global;
	}

	/** Snapshot of local declaration references; no liveness or publication policy. */
	public function declarations(): array /** vector<collected_name> */
	{
		$result /** vector<collected_name> */ = [];
		$functions /** Key_Storage_List<collected_function> */ = $this->functions;
		$variables /** Key_Storage_List<collected_name> */ = $this->variables;
		foreach ($functions->items() as $function) {
			$result[] = $function;
		}
		foreach ($variables->items() as $variable) {
			$result[] = $variable;
		}
		return $result;
	}

	/** Snapshot membership while preserving type-definition identity. */
	public function type_definitions(): array /** vector<type_definition> */
	{
		$result /** vector<type_definition> */ = [];
		$types /** Key_Storage_List<type_definition> */ = $this->types;
		foreach ($types->items() as $definition) {
			$result[] = $definition;
		}
		return $result;
	}
}

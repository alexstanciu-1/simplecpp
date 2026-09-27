<?php

/* Role: encapsulate shared lexical and published scope indexes. */
namespace scpp\compiler;

final class scope
{
	/** Upward observers; owners are Model roots or parsed_file.scopes. */
	private ?scope $enclosing /** weak<scope> */ = null;
	private ?scope $publication /** weak<scope> */ = null;
	private bool $function_boundary = false;
	private array $template_parameters /** hash<int> */ = [];
	/** @storage.reference collected_file.entries @reference.weak */
	private Key_Storage_List $variables /** Key_Storage_List<collected_name> */;
	/** @storage.reference collected_file.entries @reference.weak */
	private Key_Storage_List $functions /** Key_Storage_List<collected_name> */;
	/** Definitions owned here; published scopes retain the same definition objects. */
	private Key_Storage_List $types /** Key_Storage_List<type_definition> */;

	public function __construct()
	{
		$this->variables = new Key_Storage_List /** Key_Storage_List<collected_name> */();
		$this->functions = new Key_Storage_List /** Key_Storage_List<collected_name> */();
		$this->types = new Key_Storage_List /** Key_Storage_List<type_definition> */();
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
		if ($entry->kind === collected_name_kind::function_declaration) {
			$functions /** Key_Storage_List<collected_name> */ = $this->functions;
			$functions->add($entry->name, $entry);
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

	/** Local inventories include tombstones; callers choose live resolution explicitly. */
	public function variables_named(string $name): array /** vector<collected_name> */
	{
		$items /** Key_Storage_List<collected_name> */ = $this->variables;
		return $items->named($name);
	}

	/** Return a typed snapshot; an absent name has no candidates. */
	public function functions_named(string $name): array /** vector<collected_name> */
	{
		$items /** Key_Storage_List<collected_name> */ = $this->functions;
		return $items->named($name);
	}

	/** The small definition store owns records; lookup does not introduce another registry. */
	public function types_named(string $name): array /** vector<type_definition> */
	{
		$items /** Key_Storage_List<type_definition> */ = $this->types;
		return $items->named($name);
	}

	/** Source-only projection keeps the experimental consumer independent of built-in metadata. */
	public function source_types_named(string $name): array /** vector<collected_name> */
	{
		$result /** vector<collected_name> */ = [];
		foreach ($this->types_named($name) as $definition) {
			if ($definition->declaration !== null) {
				$entry /** collected_name */ = $definition->declaration;
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
		$functions /** Key_Storage_List<collected_name> */ = $this->functions;
		$variables /** Key_Storage_List<collected_name> */ = $this->variables;
		foreach ($functions->items() as $entry) {
			$result[] = $entry;
		}
		foreach ($variables->items() as $entry) {
			$result[] = $entry;
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

	/** Rebuild local indexes from a caller-selected membership. */
	public function replace_declarations(array $entries /** vector<collected_name> */): void
	{
		$this->functions = new Key_Storage_List /** Key_Storage_List<collected_name> */();
		$this->variables = new Key_Storage_List /** Key_Storage_List<collected_name> */();
		foreach ($entries as $entry) {
			$this->register($entry);
		}
	}

	/** Replace membership without deciding which definitions belong in this scope. */
	public function replace_types(array $definitions /** vector<type_definition> */): void
	{
		$this->types = new Key_Storage_List /** Key_Storage_List<type_definition> */();
		foreach ($definitions as $definition) {
			$this->register_type($definition);
		}
	}
}

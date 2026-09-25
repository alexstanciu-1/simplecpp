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
	private array $variables /** hash<vector<collected_name>> */ = [];
	/** @storage.reference collected_file.entries @reference.weak */
	private array $functions /** hash<vector<collected_name>> */ = [];
	/** Definitions owned here; published scopes retain the same definition objects. */
	private Storage $types /** Storage<type_definition> */;

	public function __construct()
	{
		$this->types = new Storage /** Storage<type_definition> */();
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
			$this->functions[$entry->name][] = $entry;
		}
		else {
			$this->variables[$entry->name][] = $entry;
		}
	}

	public function register_type(type_definition $definition): void
	{
		$types /** Storage<type_definition> */ = $this->types;
		$types->append($definition);
	}

	/** Local inventories include tombstones; callers choose live resolution explicitly. */
	public function variables_named(string $name): array /** vector<collected_name> */
	{
		$result /** vector<collected_name> */ = [];
		if (isset($this->variables[$name])) {
			$result = $this->variables[$name];
		}
		return $result;
	}

	/** Return a typed snapshot; an absent name has no candidates. */
	public function functions_named(string $name): array /** vector<collected_name> */
	{
		$result /** vector<collected_name> */ = [];
		if (isset($this->functions[$name])) {
			$result = $this->functions[$name];
		}
		return $result;
	}

	/** The small definition store owns records; lookup does not introduce another registry. */
	public function types_named(string $name): array /** vector<type_definition> */
	{
		$result /** vector<type_definition> */ = [];
		$types /** Storage<type_definition> */ = $this->types;
		foreach ($types as $definition) {
			if ($definition->name === $name) {
				$result[] = $definition;
			}
		}
		return $result;
	}

	/** Source-only projection keeps the experimental consumer independent of built-in metadata. */
	public function source_types_named(string $name): array /** vector<collected_name> */
	{
		$result /** vector<collected_name> */ = [];
		foreach ($this->types_named($name) as $definition) {
			if ($definition->declaration !== null) {
				$result[] = object_cast($definition->declaration, collected_name::class);
			}
		}
		return $result;
	}

	public function has_functions(): bool
	{
		return q_count($this->functions) !== 0;
	}

	public function has_variables(): bool
	{
		return q_count($this->variables) !== 0;
	}

	/** Publish references from a completed root without copying source/type identities. */
	public static function publish(scope $local_scope, scope $global): void
	{
		if ($local_scope->published_scope() !== null) {
			throw new \LogicException('Parsed file was already published');
		}
		foreach ($local_scope->functions as $name => $entries) {
			foreach ($entries as $entry) {
				$global->functions[$name][] = $entry;
			}
		}
		foreach ($local_scope->variables as $name => $entries) {
			foreach ($entries as $entry) {
				$global->variables[$name][] = $entry;
			}
		}
		$types /** Storage<type_definition> */ = $local_scope->types;
		foreach ($types as $definition) {
			$global->register_type($definition);
		}
		$local_scope->publication = $global;
	}

	/** Drop superseded live definitions from one file while retaining deletion evidence. */
	public function replace_source(string $path): void
	{
		$this->variables = self::retain_other($this->variables, $path);
		$this->functions = self::retain_other($this->functions, $path);
		$types /** Storage<type_definition> */ = new Storage();
		$previous /** Storage<type_definition> */ = $this->types;
		foreach ($previous as $definition)
		{
			$keep = true;
			if ($definition->declaration !== null) {
				$entry = object_cast($definition->declaration, collected_name::class);
				if ($entry->changes !== \scpp\compiler\SYNC_DELETED) {
					$keep = $entry->file->source->file->path !== $path;
				}
			}
			if ($keep) {
				$types->append($definition);
			}
		}
		$this->types = $types;
	}

	/** Replacement keeps other files and tombstones; it does not create version history. */
	private static function retain_other(array $index /** hash<vector<collected_name>> */, string $path): array /** hash<vector<collected_name>> */
	{
		$result /** hash<vector<collected_name>> */ = [];
		foreach ($index as $name => $entries)
		{
			foreach ($entries as $entry)
			{
				if ($entry->changes === \scpp\compiler\SYNC_DELETED) {
					$result[$name][] = $entry;
					continue;
				}
				if ($entry->file->source->file->path !== $path) {
					$result[$name][] = $entry;
				}
			}
		}
		return $result;
	}
}

/** Completed parsing output, sharing its source and collection with other model roots. */

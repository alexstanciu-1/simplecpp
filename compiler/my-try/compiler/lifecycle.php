<?php

/* Role: sequence retained-root resets, type installation and node cleanup. */
namespace scpp\compiler;

final class Compiler_Lifecycle
{
	/** Initial reset has no prior syntax graph to clean. */
	private static bool $syntax_initialized = false;

	public static function initialized(): bool
	{
		return self::$syntax_initialized;
	}

	/** Validate startup configuration privately before retaining it; hot reload is deferred. */
	public static function configure_runtime(runtime_catalog_input $input): void
	{
		if (self::$syntax_initialized) {
			throw new \LogicException('Runtime catalogs must be configured before compilation');
		}
		$types = Type_Definition_Registration::install(new Type_Registry());
		Runtime_Type_Registration::install($input, $types);
		Runtime_Catalog_Registration::resolve($input, $types);
		Runtime_Catalog_Registration::resolve_constants($input, $types);
		// Validate existing protocol prerequisites against these privately installed types too.
		Initialization_Registration::install($types);
		Indexing_Registration::install($types);
		Iteration_Registration::install($types);
		CPP_Runtime_Catalog::validate($input);
		Model::$runtime_metadata = $input;
	}

	/** Advance the shared run marker, clearing every participant before uint32 rollover. */
	public static function next_revision(): int
	{
		if ((int)Model::$revision === 4294967295)
		{
			foreach (Model::$modules as $module) {
				$module->revision = 0;
				foreach ($module->sources as $source) {
					$source->revision = 0;
				}
			}
			Model::$revision = 0;
		}
		Model::$revision++;
		return (int)Model::$revision;
	}

	/** Explicit fresh session also discards retained module identities and tombstones. */
	public static function reset(): void
	{
		if (self::$syntax_initialized) {
			self::discard_preparation_links();
		}
		Model::$modules = new Keyed_Storage /** Keyed_Storage<module> */();
		Model::$revision = 0;
		self::reset_compilation();
		Model::$modules_ready = true;
		Model::$full_sync_pending = false;
	}

	/** Retire compilation roots without traversing facts; recovery retains disk/in-memory inputs only. */
	public static function reset_compilation(bool $retain_inputs = false): void
	{
		if (!$retain_inputs) { Model::$entry_selection = null; }
		Model::$prepared_entry = null;
		if (self::$syntax_initialized) {
			self::discard_preparation_links();
		}
		foreach (Model::$modules as $module)
		{
			if (!$retain_inputs) {
				$module->sources = new Keyed_Storage /** Keyed_Storage<source_record> */();
				continue;
			}
			foreach ($module->sources as $source)
			{
				$source->tokens = null;
				$source->parsed = null;
				$source->file->tokens = null;
				if ($source->changes !== change_state::deleted) {
					$source->changes = change_state::added;
					$source->file->changes = \scpp\compiler\SYNC_ADDED;
				}
			}
		}
		Model::$prepared_files = new Storage /** Storage<prepared_file> */();
		Model::$cpp_output_program = new cpp_program();
		self::reset_types();
		Model::$language_scope = new scope();
		Language_Types::install(Model::$language_scope);
		Language_Constants::install(Model::$language_scope, Model::$type_catalog);
		Runtime_Catalog_Registration::publish_constants(Model::$runtime_constants, Model::$language_scope);
		Runtime_Catalog_Registration::publish(Model::$runtime_metadata, Model::$runtime_callables, Model::$language_scope);
		Language_Callables::install(Model::$language_scope);
		CPP_Runtime_Catalog::install(Model::$runtime_metadata);
		Model::$namespaces = new Namespace_Registry(Model::$language_scope);
		Model::$global_scope = Model::$namespaces->register('')->published_scope();
		self::reset_cpp();
		self::reset_llvm();
		self::$syntax_initialized = true;
		Model::$rebuild_required = false;
	}

	/** Restart scanning: invalidate every dependent root and all source backlinks first. */
	public static function reset_tokens(): void
	{
		self::reset_syntax();
		foreach (Model::sources() as $source) {
			$source->tokens = null;
			$source->file->tokens = null;
		}
	}

	/** Restart parsing: old scopes, occurrences and generated output no longer apply. */
	public static function reset_syntax(): void
	{
		self::discard_preparation_links();
		Model::$cpp_output_program = new cpp_program();
		self::reset_preparation();
		foreach (Model::sources() as $source) {
			$source->parsed = null;
			if ($source->changes === change_state::unchanged) {
				$source->changes = change_state::changed;
			}
		}
		self::$syntax_initialized = true;

		self::reset_types();
		Model::$language_scope = new scope();
		Language_Types::install(Model::$language_scope);
		Language_Constants::install(Model::$language_scope, Model::$type_catalog);
		Runtime_Catalog_Registration::publish_constants(Model::$runtime_constants, Model::$language_scope);
		Runtime_Catalog_Registration::publish(Model::$runtime_metadata, Model::$runtime_callables, Model::$language_scope);
		Language_Callables::install(Model::$language_scope);
		CPP_Runtime_Catalog::install(Model::$runtime_metadata);
		Model::$namespaces = new Namespace_Registry(Model::$language_scope);
		Model::$global_scope = Model::$namespaces->register('')->published_scope();

		self::reset_llvm();
	}

	/** Replace semantic and backend type roots together at a full syntax identity boundary. */
	private static function reset_types(): void
	{
		$registry = new Type_Registry();
		Model::$type_catalog = Type_Definition_Registration::install($registry);
		Runtime_Type_Registration::install(Model::$runtime_metadata, Model::$type_catalog);
		Model::$cpp_type_bindings = CPP_Type_Binding_Registration::install(Model::$type_catalog);
		CPP_Runtime_Catalog::install_types(Model::$runtime_metadata, Model::$type_catalog, Model::$cpp_type_bindings);
		Model::$runtime_callables = Runtime_Catalog_Registration::resolve(Model::$runtime_metadata, Model::$type_catalog);
		Model::$runtime_constants = Runtime_Catalog_Registration::resolve_constants(Model::$runtime_metadata, Model::$type_catalog);
		Model::$runtime_members = Runtime_Catalog_Registration::members(Model::$runtime_constants);
		Model::$initialization_contracts = Initialization_Registration::install(Model::$type_catalog);
		Model::$indexing_contracts = Indexing_Registration::install(Model::$type_catalog);
		Model::$iteration_contracts = Iteration_Registration::install(Model::$type_catalog);
		Model::$cpp_callable_bindings = new CPP_Callable_Bindings();
		CPP_Initialization_Registration::install();
	}

	/** Clear node-owned facts before releasing preparation/output roots or replacing syntax. */
	public static function reset_preparation(): void
	{
		Model::$prepared_entry = null;
		if (self::$syntax_initialized)
		{
			$syntax_files /** Storage<parsed_file> */ = new Storage();
			$source_files /** Storage<collected_file> */ = new Storage();
			foreach (Model::syntax_files() as $syntax_file) {
				$syntax_files->append($syntax_file);
				$source_files->append($syntax_file->collection);
			}
			foreach (Trait_Composition::private_parses($source_files) as $private_parse) { $syntax_files->append($private_parse); }
			foreach (Callable_Instances::private_parses($source_files) as $private_parse) { $syntax_files->append($private_parse); }
			foreach ($syntax_files as $parsed)
			{
				Preparation_Cleanup::tree($parsed->root);
				$source = $parsed->collection;
				if ($source->prepared !== null) {
					$source->prepared->entry_body = null;
				}
				$bodies /** Storage<namespace_body_node> */ = $source->root->namespace_bodies;
				foreach ($bodies as $namespace_body) {
					$work = $namespace_body->body->work();
					if ($work !== null) {
						$work->state = preparation_state::pending;
						$work->change_status = change_state::changed;
					}
				}
				$entries /** Storage<collected_name> */ = $source->entries;
				foreach ($entries as $entry)
				{
					if ($entry instanceof collected_member_owner) {
						$relationships = object_cast($entry, collected_member_owner::class)->relationship_work;
						$contracts = object_cast($entry, collected_member_owner::class)->contract_work;
						$lifecycle = object_cast($entry, collected_member_owner::class)->lifecycle_work;
						if ($lifecycle !== null) {
							$lifecycle->state = preparation_state::pending;
							$lifecycle->change_status = change_state::changed;
							$lifecycle->facts = null;
						}
						if ($contracts !== null) {
							$contracts->state = preparation_state::pending;
							$contracts->change_status = change_state::changed;
							$contracts->facts = null;
						}
						if ($relationships !== null) {
							$relationships->state = preparation_state::pending;
							$relationships->change_status = change_state::changed;
							$relationships->facts = null;
						}
					}
					$work = $entry->preparation_work_owner();
					if ($work !== null) {
						$work->state = preparation_state::pending;
						$work->change_status = change_state::changed;
					}
					if ($entry instanceof collected_source_callable) {
						$function = object_cast($entry, collected_source_callable::class)->callable_syntax();
						if (!$function->has_parsed_body()) { continue; }
						if ($function->require_body()->work() !== null) {
							$function->require_body()->work()->state = preparation_state::pending;
							$function->require_body()->work()->change_status = change_state::changed;
						}
					}
				}
			}
		}
		Model::$prepared_files = new Storage /** Storage<prepared_file> */();
		self::reset_cpp();
	}

	/** Join-owned ordering: semantic notification/unlinking, then frontend membership disposal. */
	public static function retire_deleted_sources(Storage $sources /** Storage<collected_file> */, scope $language): void
	{
		// Check the whole join before notifying consumers or disposing any frontend data.
		foreach ($sources as $source) {
			if (!$source->deleted && !$source->parse_complete) {
				throw new \RuntimeException('Retirement requires a successful parsing/collection join');
			}
		}
		Callable_Instances::retire_stale($sources, $language);
		Trait_Composition::retire_stale($sources, $language);
		(new Preparation_Worker($language))->retire_deleted_sources($sources);
		Collection_Retirement::remove_deleted_sources($sources);
	}

	/** Include deleted modules too; their retained dependency graphs must not survive a reset. */
	private static function discard_preparation_links(): void
	{
		$sources /** Storage<collected_file> */ = new Storage();
		foreach (Model::$modules as $module) {
			foreach ($module->sources as $source) {
				if ($source->parsed !== null) {
					$sources->append($source->parsed->collection);
				}
			}
		}
		Callable_Instances::retire_sources($sources, Model::$language_scope);
		foreach (Trait_Composition::private_parses($sources) as $private_parse) { $sources->append($private_parse->collection); }
		(new Preparation_Worker(Model::$language_scope))->discard_sources($sources);
	}

	/** Discard C++ artifacts while retaining shared prepared facts. */
	public static function reset_cpp(): void
	{
		if (self::$syntax_initialized) {
			CPP_Generator::discard_deleted(Model::$cpp_output_program);
		}
		Model::$cpp_files = new Storage /** Storage<cpp_artifact> */();
	}

	public static function reset_llvm(): void
	{
		Model::$llvm_files = new Storage /** Storage<llvm_module> */();
	}
}

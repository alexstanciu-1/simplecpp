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
				$source->previous_tokens = null;
				$source->parsed = null;
				$source->file->tokens = null;
				if ($source->changes !== change_state::deleted) {
					$source->changes = change_state::added;
					$source->file->changes = \scpp\compiler\SYNC_ADDED;
				}
			}
		}
		Model::$prepared_files = new Storage /** Storage<prepared_file> */();
		Model::$cpp_program = new cpp_program();
		Model::$language_scope = new scope();
		Language_Types::install(Model::$language_scope);
		Model::$global_scope = new scope();
		Model::$global_scope->set_parent(Model::$language_scope);
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
		Model::$cpp_program = new cpp_program();
		self::reset_preparation();
		foreach (Model::sources() as $source) {
			$source->parsed = null;
			if ($source->changes === change_state::unchanged) {
				$source->changes = change_state::changed;
			}
		}
		self::$syntax_initialized = true;

		Model::$language_scope = new scope();
		Language_Types::install(Model::$language_scope);
		Model::$global_scope = new scope();
		Model::$global_scope->set_parent(Model::$language_scope);

		self::reset_llvm();
	}

	/** Clear node-owned facts before releasing preparation/output roots or replacing syntax. */
	public static function reset_preparation(): void
	{
		if (self::$syntax_initialized)
		{
			foreach (Model::syntax_files() as $parsed)
			{
				Preparation_Cleanup::tree($parsed->root);
				$source = $parsed->collection;
				if ($source->root->body->work() !== null) {
					$source->root->body->work()->state = preparation_state::pending;
					$source->root->body->work()->change_status = change_state::changed;
				}
				$entries /** Storage<collected_name> */ = $source->entries;
				foreach ($entries as $entry)
				{
					if ($entry->preparation !== null) {
						$entry->preparation->state = preparation_state::pending;
						$entry->preparation->change_status = change_state::changed;
					}
					if ($entry->kind === collected_name_kind::function_declaration) {
						$function = object_cast($entry->node, function_node::class);
						if ($function->body->work() !== null) {
							$function->body->work()->state = preparation_state::pending;
							$function->body->work()->change_status = change_state::changed;
						}
					}
				}
			}
		}
		Model::$prepared_files = new Storage /** Storage<prepared_file> */();
		self::reset_cpp();
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
		(new Preparation_Worker(Model::$language_scope))->discard_sources($sources);
	}

	/** Discard C++ artifacts while retaining shared prepared facts. */
	public static function reset_cpp(): void
	{
		if (self::$syntax_initialized) {
			CPP_Generator::discard_deleted(Model::$cpp_program);
		}
		Model::$cpp_files = new Storage /** Storage<cpp_module> */();
	}

	public static function reset_llvm(): void
	{
		Model::$llvm_files = new Storage /** Storage<llvm_module> */();
	}
}

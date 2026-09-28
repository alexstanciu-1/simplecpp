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

	/** Explicit fresh session also discards retained module identities and tombstones. */
	public static function reset(): void
	{
		Model::$modules = new module_collection();
		self::reset_compilation();
		Model::$modules_ready = true;
		Model::$full_sync_pending = false;
	}

	/** Retire the whole graph by its roots; discarded syntax needs no fact-cleanup walk. */
	public static function reset_compilation(): void
	{
		foreach (Model::$modules->inventory() as $module) {
			$module->sources = new Storage /** Storage<source_record> */();
		}
		Model::$sources_by_path = new Keyed_Storage /** Keyed_Storage<source_record> */();
		Model::$prepared_files = new Storage /** Storage<prepared_file> */();
		Model::$language_scope = new scope();
		Language_Types::install(Model::$language_scope);
		Model::$global_scope = new scope();
		Model::$global_scope->set_parent(Model::$language_scope);
		self::reset_cpp();
		self::reset_llvm();
		self::$syntax_initialized = true;
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
		self::reset_preparation();
		foreach (Model::sources() as $source) {
			$source->parsed = null;
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
		if (self::$syntax_initialized) {
			foreach (Model::syntax_files() as $parsed) {
				Preparation_Cleanup::tree($parsed->root);
			}
		}
		Model::$prepared_files = new Storage /** Storage<prepared_file> */();
		self::reset_cpp();
	}

	/** Discard C++ artifacts while retaining shared prepared facts. */
	public static function reset_cpp(): void
	{
		Model::$cpp_files = new Storage /** Storage<cpp_module> */();
	}

	public static function reset_llvm(): void
	{
		Model::$llvm_files = new Storage /** Storage<llvm_module> */();
	}
}

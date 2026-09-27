<?php

/* Role: sequence retained-root resets, type installation and node cleanup. */
namespace scpp\compiler;

final class Compiler_Lifecycle
{
	/** Initial reset has no prior syntax graph to clean. */
	private static bool $syntax_initialized = false;

	/** Start a fresh compilation without retaining output or indexes from an earlier run. */
	public static function reset(): void
	{
		if (self::$syntax_initialized) {
			self::reset_preparation();
		}
		Model::$modules = new Storage /** Storage<module> */();
		Model::$sources_by_path = new Keyed_Storage /** Keyed_Storage<source_record> */();
		self::reset_tokens();
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

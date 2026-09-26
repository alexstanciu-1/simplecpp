<?php

/*
 * Role: shared owner of the retained compiler object graph.
 * Used by: Compiler stages, host reporting and behavioral tests.
 * Flow: modules -> tokens -> parsing/collection -> preparation -> C++ output.
 * Experimental LLVM output remains separate.
 * Graph boundaries and mutation owners are documented in ../docs/architecture/MODEL.md.
 */
namespace scpp\compiler;

final class Model
{
	/** Initial reset has no prior syntax graph to clean. */
	private static bool $syntax_initialized = false;
	/**
	 * Numeric storage of module records.
	 * @storage.owner
	 */
	public static Storage $modules /** Storage<module> */;
	/**
	 * Numeric storage of token_list records.
	 * @storage.owner
	 */
	public static Storage $tokens /** Storage<token_list> */;
	/**
	 * Numeric storage of parsed_file records.
	 * @storage.owner
	 */
	public static Storage $syntax_files /** Storage<parsed_file> */;
	/**
	 * Established by reset; sync updates candidate lists while retaining deleted entries.
	 * @ownership owner
	 * Directly owned record; not an element of a Storage.
	 */
	public static scope $global_scope;
	/** Built-in and runtime definitions; parent of global scope. @ownership owner */
	public static scope $language_scope;
	/**
	 * Numeric storage of collected_file records.
	 * @storage.owner
	 */
	public static Storage $collected_files /** Storage<collected_file> */;
	/**
	 * Numeric storage of llvm_module records, including their LLVM text.
	 * @storage.owner
	 */
	public static Storage $llvm_files /** Storage<llvm_module> */;
	/** Complete source preparation for C++ generation. @storage.owner */
	public static Storage $prepared_files /** Storage<prepared_file> */;
	/** Final C++ artifacts. @storage.owner */
	public static Storage $cpp_files /** Storage<cpp_module> */;

	/** Start a fresh compilation without retaining output or indexes from an earlier run. */
	public static function reset(): void
	{
		self::$modules = new Storage /** Storage<module> */();
		self::reset_tokens();
	}

	/** Restart scanning: invalidate every dependent root and all source backlinks first. */
	public static function reset_tokens(): void
	{
		self::$tokens = new Storage /** Storage<token_list> */();
		self::reset_syntax();
		foreach (self::$modules as $module) {
			foreach ($module->files as $file) {
				$file->tokens = null;
			}
		}
	}

	/** Restart parsing: old scopes, occurrences and generated output no longer apply. */
	public static function reset_syntax(): void
	{
		self::reset_cpp();
		self::$syntax_files = new Storage /** Storage<parsed_file> */();
		self::$syntax_initialized = true;
		self::$language_scope = new scope();
		Language_Types::install(self::$language_scope);
		self::$global_scope = new scope();
		self::$global_scope->set_parent(self::$language_scope);
		self::$collected_files = new Storage /** Storage<collected_file> */();
		self::reset_llvm();
	}

	/** Clear node-owned facts before releasing preparation/output roots or replacing syntax. */
	public static function reset_cpp(): void
	{
		if (self::$syntax_initialized) {
			foreach (self::$syntax_files as $parsed) {
				Preparation_Cleanup::tree($parsed->root);
			}
		}
		self::$prepared_files = new Storage /** Storage<prepared_file> */();
		self::$cpp_files = new Storage /** Storage<cpp_module> */();
	}

	public static function reset_llvm(): void
	{
		self::$llvm_files = new Storage /** Storage<llvm_module> */();
	}
}

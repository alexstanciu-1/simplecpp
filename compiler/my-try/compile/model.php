<?php

/*
 * Role: shared owner of the retained compiler object graph.
 * Used by: Compiler stages, host reporting and behavioral tests.
 * Flow: modules -> tokens -> parsing/collection -> LLVM output.
 * Graph boundaries and mutation owners are documented in ../MODEL.md.
 */
namespace scpp\compiler;

final class Model
{
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
		self::$syntax_files = new Storage /** Storage<parsed_file> */();
		self::$global_scope = new scope();
		self::$collected_files = new Storage /** Storage<collected_file> */();
		self::reset_llvm();
	}

	public static function reset_llvm(): void
	{
		self::$llvm_files = new Storage /** Storage<llvm_module> */();
	}
}

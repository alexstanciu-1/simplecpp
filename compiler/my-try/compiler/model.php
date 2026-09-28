<?php

/*
 * Role: shared owner of the retained compiler object graph.
 * Used by: Compiler_Lifecycle, stage processors, host reporting and tests.
 * Flow: modules -> tokens -> parsing/collection -> preparation -> C++ output.
 * Experimental LLVM output remains separate.
 * Graph boundaries and mutation owners are documented in ../docs/architecture/MODEL.md.
 */
namespace scpp\compiler;

final class Model
{
	/**
	 * Keyed module identities and current active order.
	 * @storage.owner
	 */
	public static module_collection $modules;
	/** Discovery failure blocks frontend use until initialization succeeds. */
	public static bool $modules_ready = true;
	/** Module changes require every discovered file to cross the frontend barrier. */
	public static bool $full_sync_pending = false;
	/**
	 * Established by reset; sync updates candidate lists while retaining deleted entries.
	 * @ownership owner
	 * Directly owned record; not an element of a Storage.
	 */
	public static scope $global_scope;
	/** Built-in and runtime definitions; parent of global scope. @ownership owner */
	public static scope $language_scope;
	/**
	 * Numeric storage of llvm_module records, including their LLVM text.
	 * @storage.owner
	 */
	public static Storage $llvm_files /** Storage<llvm_module> */;
	/** Complete source preparation for C++ generation. @storage.owner */
	public static Storage $prepared_files /** Storage<prepared_file> */;
	/** Final C++ artifacts. @storage.owner */
	public static Storage $cpp_files /** Storage<cpp_module> */;
	/** Unique path index; module.sources owns the stable records. @reference.weak */
	public static Keyed_Storage $sources_by_path /** Keyed_Storage<source_record> */;

	public static function modules(): Storage /** Storage<module> */
	{
		return self::$modules->items();
	}

	/** Ordered snapshot of stable source membership, never a second retained store. */
	public static function sources(): Storage /** Storage<source_record> */
	{
		$result /** Storage<source_record> */ = new Storage();
		foreach (self::modules() as $module) {
			foreach ($module->sources as $source) {
				$result->append($source);
			}
		}
		return $result;
	}

	/** Project completed scans in module/source order. */
	public static function tokens(): Storage /** Storage<token_list> */
	{
		$result /** Storage<token_list> */ = new Storage();
		foreach (self::sources() as $source) {
			if ($source->tokens !== null) {
				$result->append($source->tokens);
			}
		}
		return $result;
	}

	/** Project completed parses in module/source order. */
	public static function syntax_files(): Storage /** Storage<parsed_file> */
	{
		$result /** Storage<parsed_file> */ = new Storage();
		foreach (self::sources() as $source) {
			if ($source->parsed !== null) {
				$result->append($source->parsed);
			}
		}
		return $result;
	}

	/** Collection ownership follows its parse result. */
	public static function collected_files(): Storage /** Storage<collected_file> */
	{
		$result /** Storage<collected_file> */ = new Storage();
		foreach (self::syntax_files() as $parsed) {
			$result->append($parsed->collection);
		}
		return $result;
	}
}

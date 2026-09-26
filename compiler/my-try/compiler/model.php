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
}

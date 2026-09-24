<?php

/*
 * Role: resolved source-local declaration bindings.
 * Used by: Name_Preparation and LLVM_Preparation.
 */
namespace scpp\compiler;

/** Source-local token indexes point to retained declaration identities. */
final class prepared_names {
	/** @storage.reference collected_file.entries
	 * @reference.weak
	 */
	public array $declarations /** hash<collected_name, int> */ = [];
	/** Resolved source type declarations by use token. */
	/** @storage.reference collected_file.entries
	 * @reference.weak
	 */
	public array $types /** hash<collected_name, int> */ = [];
	/** Type-use token index to its owning template parameter slot. */
	public array $template_slots /** hash<int, int> */ = [];
	/** @storage.reference collected_file.entries
	 * @reference.weak
	 */
	public array $references /** hash<collected_name, int> */ = [];
	/** Call-name token index to declaration. */
	/** @storage.reference collected_file.entries
	 * @reference.weak
	 */
	public array $function_references /** hash<collected_name, int> */ = [];
}

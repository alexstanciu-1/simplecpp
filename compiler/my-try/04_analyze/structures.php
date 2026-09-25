<?php

/*
 * Role: retained S2S preparation facts and experimental name/check invocation records.
 * Used by: File_Preparation; experimental Name_Preparation, LLVM_Preparation and template checking.
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

/** One template-check invocation; published complete before file workers run. */
final class template_check_context {
	/** Shared policy supplied to Template_Checker::check. */
	public llvm_policy $policy;
	/** Index references the prepared files supplied to Template_Checker::check.
	 * @reference.weak
	 */
	public \SplObjectStorage $files /** hash<llvm_prepared_file, shared<collected_file>> */;
}

/** Resolved expression facts; syntax and declaration links reference the retained source. */
final class prepared_expression {
	public ast_node $syntax;
	public type_definition $type;
	/** Decimal value for integer literals; empty for other expressions. */
	public string $literal = '';
	public ?collected_name $declaration = null;
}

/** Declaration identity survives every use within this preparation invocation. */
final class prepared_binding {
	public ast_node $syntax;
	public collected_name $declaration;
	public binding_kind $classification;
	public type_definition $type;
	public prepared_expression $initializer;
}

/** Complete per-file facts; keys are existing source token positions, not persistent IDs. */
final class prepared_file {
	public collected_file $source;
	/** Owns prepared expression records. */
	public array $expressions /** hash<prepared_expression, int> */ = [];
	/** Owns prepared binding records. */
	public array $bindings /** hash<prepared_binding, int> */ = [];
}

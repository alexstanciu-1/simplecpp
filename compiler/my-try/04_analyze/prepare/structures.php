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

/** Common backend-neutral result of preparing an expression. */
abstract class prepared_expression {
	public type_definition $type;
}

/** Exact normalized decimal value; C++ spelling belongs to the backend. */
final class prepared_integer_literal extends prepared_expression {
	public string $decimal;
}

/** Normalized boolean value shared by backends. */
final class prepared_boolean_literal extends prepared_expression {
	public bool $value;
}

/** A resolved reference always has a declaration; no literal fields belong here. */
final class prepared_variable_reference extends prepared_expression {
	/** @storage.reference collected_file.entries @reference.weak */
	public collected_name $declaration /** weak<collected_name> */;
}

/** Facts owned by the binding specialization; declaration identity is a non-owning reference. */
final class prepared_binding {
	/** @storage.reference collected_file.entries @reference.weak */
	public collected_name $declaration /** weak<collected_name> */;
	public binding_kind $resolved_kind;
	public type_definition $type;
}

/** Completed file preparation; the source tree owns the actual facts. */
final class prepared_file {
	public collected_file $source;
}

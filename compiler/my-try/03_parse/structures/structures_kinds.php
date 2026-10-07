<?php

/* Syntax tags are independent of specialization operation and preparation types. */
namespace scpp\compiler;

enum node_category {
	case syntax;
	case expression;
	case statement;
}

enum node_kind
{
	case type_declaration;
	case method_declaration;
	case member_constant_declaration;
	case trait_use;
	case enum_declaration;
	case enum_case;
	case static_member_reference;
	case property_declaration;
	case field_expression;
	case file;
	case namespace_body;
	case import_binding;
	case function_declaration;
	case parameter_declaration;
	case block;
	case conditional;
	case switch_statement;
	case switch_group;
	case switch_label;
	case while_loop;
	case do_while_loop;
	case for_loop;
	case foreach_loop;
	case break_statement;
	case continue_statement;
	case function_body;
	case named_type;
	case nullable_type;
	case template_application_type;
	case type_use_modifier;
	case punctuation;
	case comment;
	case array_type;
	case data_construction;
	case object_construction;
	case move_expression;
	case ownership_observation;
	case data_entry;
	case index_expression;
	case integer_literal;
	case null_literal;
	case boolean_literal;
	case float_literal;
	case string_literal;
	case interpolated_string;
	case interpolation_text;
	case interpolation_value;
	case variable_reference;
	case constant_reference;
	case constant_declaration;
	case cast_expression;
	case conditional_expression;
	case coalesce_expression;
	case value_probe;
	case unset_statement;
	case clean_statement;
	case take_expression;
	case match_expression;
	case match_arm;
	case compound_assignment_expression;
	case mutation_expression;
	case unary_expression;
	case binary_expression;
	case assignment_expression;
	case call_expression;
	case expression_statement;
	case return_statement;
	case variable_declaration;
}

/** Authored declaration form, independent of semantic storage and capabilities. */
enum type_declaration_kind {
	case class_type;
	case struct_type;
	case interface_type;
	case trait_type;
}

/** Retain explicit source modifiers; preparation owns defaults and legality. */
enum declaration_modifier
{
	case public_access;
	case protected_access;
	case private_access;
	case static_member;
	case abstract_declaration;
	case final_declaration;
	case readonly_declaration;
}

/** Source notation is queried from tokens, never retained as a duplicate node flag. */
enum data_source_representation {
	case php_like;
	case js_like;
}

/** Keep enum reflection beside its declaration for native lowering. */
final class Node_Kind_Name {
	public static function text(node_kind $kind): string
	{
		return enum_name($kind);
	}
}

enum binding_kind {
	case unresolved;
	case declaration;
	case assignment;
}

enum if_arm_kind {
	case initial;
	case elseif_arm;
	case else_arm;
}

enum passing_mode {
	case value;
	case reference;
}

/** Access through a binding is independent of how its value is passed. */
enum storage_access {
	case writable;
	case read_only;
}

enum control_transfer_kind {
	case break_construct;
	case continue_loop;
}

enum switch_label_kind {
	case value;
	case fallback;
}

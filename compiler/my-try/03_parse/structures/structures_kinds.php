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
	case struct_declaration;
	case field_declaration;
	case field_expression;
	case file;
	case function_declaration;
	case parameter_declaration;
	case block;
	case conditional;
	case while_loop;
	case do_while_loop;
	case for_loop;
	case break_statement;
	case continue_statement;
	case function_body;
	case named_type;
	case template_application_type;
	case type_use_modifier;
	case punctuation;
	case comment;
	case array_type;
	case array_literal;
	case index_expression;
	case integer_literal;
	case boolean_literal;
	case float_literal;
	case string_literal;
	case interpolated_string;
	case interpolation_text;
	case interpolation_value;
	case variable_reference;
	case constant_reference;
	case cast_expression;
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

enum control_transfer_kind {
	case break_loop;
	case continue_loop;
}

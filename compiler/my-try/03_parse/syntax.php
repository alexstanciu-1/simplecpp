<?php

/*
 * Role: derived syntax queries without processing state in data records.
 * Call map: Parser -> validate_payload; syntax consumers -> category.
 */
namespace scpp\compiler;

final class Syntax_Nodes
{
	/** Reject inconsistent kind/payload pairs at the parser construction boundary. */
	public static function validate_payload(node_kind $kind, ?node_interface $payload): void
	{
		$valid = match ($kind) {
			node_kind::file, node_kind::block => $payload instanceof block_specialization,
			node_kind::function_declaration => $payload instanceof function_specialization,
			node_kind::parameter_declaration => $payload instanceof parameter_specialization,
			node_kind::call_expression => $payload instanceof call_specialization,
			node_kind::binary_expression, node_kind::assignment_expression => $payload instanceof binary_specialization,
			node_kind::expression_statement => $payload instanceof expression_statement_specialization,
			node_kind::return_statement => $payload instanceof return_specialization,
			node_kind::variable_binding_statement => $payload instanceof binding_specialization,
			node_kind::array_type => $payload instanceof array_type_specialization,
			node_kind::array_literal => $payload instanceof array_literal_specialization,
			node_kind::index_expression => $payload instanceof index_specialization,
			node_kind::struct_declaration => $payload instanceof struct_specialization,
			node_kind::field_declaration => $payload instanceof field_specialization,
			node_kind::field_expression => $payload instanceof field_access_specialization,
			node_kind::identifier, node_kind::punctuation, node_kind::comment,
			node_kind::integer_literal, node_kind::variable_reference => $payload === null,
		};
		if (!$valid) {
			throw new \LogicException('Invalid AST payload for ' . $kind->name);
		}
	}

	/** Derive the category from the node kind so classifications cannot disagree. */
	public static function category(ast_node $node): node_category
	{
		return match ($node->kind) {
			node_kind::struct_declaration, node_kind::field_declaration, node_kind::array_type, node_kind::file, node_kind::function_declaration, node_kind::parameter_declaration, node_kind::identifier, node_kind::punctuation,
			node_kind::comment => node_category::syntax,
			node_kind::field_expression, node_kind::array_literal, node_kind::index_expression, node_kind::integer_literal, node_kind::variable_reference,
			node_kind::binary_expression, node_kind::assignment_expression, node_kind::call_expression => node_category::expression,
			node_kind::block, node_kind::expression_statement,
			node_kind::return_statement, node_kind::variable_binding_statement => node_category::statement,
		};
	}
}

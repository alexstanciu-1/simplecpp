<?php

/*
 * Role: derived syntax queries without processing state in data records.
 * Call map: Parser -> validate_payload; syntax consumers -> category.
 */
namespace scpp\compiler;

final class Syntax_Nodes
{
	/** Reject inconsistent kind/payload pairs at the parser construction boundary. */
	public static function validate_payload(node_kind $kind, ?node_specialization $payload): void
	{
		$valid = match ($kind)
		{
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
			throw new \LogicException('Invalid AST payload for ' . Node_Kind_Name::text($kind));
		}

		// Check local field relationships once, before the parser publishes the node.
		// Children were validated when constructed; do not traverse the subtree again.
		if ($kind === node_kind::variable_binding_statement) {
			self::validate_binding(object_cast($payload, binding_specialization::class));
		}
		elseif ($kind === node_kind::parameter_declaration) {
			self::validate_parameter(object_cast($payload, parameter_specialization::class));
		}
	}

	/** Preserve the distinction between declarations, unresolved names and explicit target writes. */
	private static function validate_binding(binding_specialization $binding): void
	{
		$has_type = $binding->type_syntax !== null;
		$has_target = $binding->target !== null;
		$has_value = $binding->value !== null;
		$has_equals = $binding->equals_token_index !== null;

		if ($has_equals !== $has_value) {
			throw new \LogicException('Invalid binding: equals token and value must appear together');
		}
		if (($has_type) && ($has_target)) {
			throw new \LogicException('Invalid binding: a declaration cannot have an assignment target');
		}
		if ((!$has_type) && (!$has_value)) {
			throw new \LogicException('Invalid binding: an untyped write requires a value');
		}

		if (($binding->classification === binding_kind::declaration) !== $has_type) {
			throw new \LogicException('Invalid binding: declaration classification must agree with type syntax');
		}
		if ($has_target) {
			if ($binding->classification !== binding_kind::assignment) {
				throw new \LogicException('Invalid binding: an explicit target requires assignment classification');
			}
		}
	}

	/** A reference parameter carries its ampersand token; a value parameter never does. */
	private static function validate_parameter(parameter_specialization $parameter): void
	{
		$is_reference = $parameter->mode === passing_mode::reference;
		$has_reference_token = $parameter->reference_token_index !== null;
		if ($is_reference !== $has_reference_token) {
			throw new \LogicException('Invalid parameter: reference mode and ampersand token must agree');
		}
	}

	/** Derive the category from the node kind so classifications cannot disagree. */
	public static function category(ast_node $node): node_category
	{
		return match ($node->kind)
		{
			node_kind::struct_declaration, node_kind::field_declaration, node_kind::array_type, node_kind::file, node_kind::function_declaration, node_kind::parameter_declaration, node_kind::identifier, node_kind::punctuation,
			node_kind::comment => node_category::syntax,
			node_kind::field_expression, node_kind::array_literal, node_kind::index_expression, node_kind::integer_literal, node_kind::variable_reference,
			node_kind::binary_expression, node_kind::assignment_expression, node_kind::call_expression => node_category::expression,
			node_kind::block, node_kind::expression_statement,
			node_kind::return_statement, node_kind::variable_binding_statement => node_category::statement,
		};
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function block_data(ast_node $node): block_specialization
	{
		return object_cast($node->specialization, block_specialization::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function function_data(ast_node $node): function_specialization
	{
		return object_cast($node->specialization, function_specialization::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function parameter_data(ast_node $node): parameter_specialization
	{
		return object_cast($node->specialization, parameter_specialization::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function call_data(ast_node $node): call_specialization
	{
		return object_cast($node->specialization, call_specialization::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function binding_data(ast_node $node): binding_specialization
	{
		return object_cast($node->specialization, binding_specialization::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function return_data(ast_node $node): return_specialization
	{
		return object_cast($node->specialization, return_specialization::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function statement_data(ast_node $node): expression_statement_specialization
	{
		return object_cast($node->specialization, expression_statement_specialization::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function struct_data(ast_node $node): struct_specialization
	{
		return object_cast($node->specialization, struct_specialization::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function field_data(ast_node $node): field_specialization
	{
		return object_cast($node->specialization, field_specialization::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function field_access_data(ast_node $node): field_access_specialization
	{
		return object_cast($node->specialization, field_access_specialization::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function array_data(ast_node $node): array_literal_specialization
	{
		return object_cast($node->specialization, array_literal_specialization::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function array_type_data(ast_node $node): array_type_specialization
	{
		return object_cast($node->specialization, array_type_specialization::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function index_data(ast_node $node): index_specialization
	{
		return object_cast($node->specialization, index_specialization::class);
	}
}

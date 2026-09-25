<?php

/*
 * Role: derived syntax queries without processing state in data records.
 * Call map: Parser -> validate_payload; syntax consumers -> category.
 */
namespace scpp\compiler;

final class Syntax_Nodes
{
	/** Allocate the concrete node corresponding to a parser production. */
	public static function allocate(node_kind $kind): ast_node
	{
		if ($kind === node_kind::struct_declaration) {
			return new struct_declaration_node();
		}
		if ($kind === node_kind::field_declaration) {
			return new field_declaration_node();
		}
		if ($kind === node_kind::field_expression) {
			return new field_expression_node();
		}
		if ($kind === node_kind::file) {
			return new file_node();
		}
		if ($kind === node_kind::function_declaration) {
			return new function_declaration_node();
		}
		if ($kind === node_kind::parameter_declaration) {
			return new parameter_declaration_node();
		}
		if ($kind === node_kind::block) {
			return new block_node();
		}
		if ($kind === node_kind::identifier) {
			return new identifier_node();
		}
		if ($kind === node_kind::punctuation) {
			return new punctuation_node();
		}
		if ($kind === node_kind::comment) {
			return new comment_node();
		}
		if ($kind === node_kind::array_type) {
			return new array_type_node();
		}
		if ($kind === node_kind::array_literal) {
			return new array_literal_node();
		}
		if ($kind === node_kind::index_expression) {
			return new index_expression_node();
		}
		if ($kind === node_kind::integer_literal) {
			return new integer_literal_node();
		}
		if ($kind === node_kind::variable_reference) {
			return new variable_reference_node();
		}
		if ($kind === node_kind::binary_expression) {
			return new binary_expression_node();
		}
		if ($kind === node_kind::assignment_expression) {
			return new assignment_expression_node();
		}
		if ($kind === node_kind::call_expression) {
			return new call_expression_node();
		}
		if ($kind === node_kind::expression_statement) {
			return new expression_statement_node();
		}
		if ($kind === node_kind::return_statement) {
			return new return_statement_node();
		}
		if ($kind === node_kind::variable_binding_statement) {
			return new variable_binding_statement_node();
		}
		throw new \LogicException("Unknown AST node kind");
	}

	/** Enumerate direct syntax children in grammar order before publishing navigation links. */
	public static function child_nodes(ast_node $node): Storage /** Storage<ast_node> */
	{
		$result /** Storage<ast_node> */ = new Storage();
		if ($node->structure instanceof block_structure)
		{
			$data = object_cast($node->structure, block_structure::class);
			$items /** Storage<ast_node> */ = $data->children;
			foreach ($items as $child) {
				$result->append($child);
			}
			return $result;
		}
		if ($node->structure instanceof function_structure)
		{
			$data = object_cast($node->structure, function_structure::class);
			$items /** Storage<ast_node> */ = $data->parameters;
			foreach ($items as $child) {
				$result->append($child);
			}
			$result->append($data->return_type);
			$result->append($data->body);
			return $result;
		}
		if ($node->structure instanceof parameter_structure) {
			$data = object_cast($node->structure, parameter_structure::class);
			$result->append($data->type_syntax);
			return $result;
		}
		if ($node->structure instanceof call_structure)
		{
			$data = object_cast($node->structure, call_structure::class);
			$items /** Storage<ast_node> */ = $data->template_arguments;
			foreach ($items as $child) {
				$result->append($child);
			}
			$items /** Storage<ast_node> */ = $data->arguments;
			foreach ($items as $child) {
				$result->append($child);
			}
			return $result;
		}
		if ($node->structure instanceof binary_structure) {
			$data = object_cast($node->structure, binary_structure::class);
			$result->append($data->left);
			$result->append($data->right);
			return $result;
		}
		if ($node->structure instanceof expression_statement_structure) {
			$data = object_cast($node->structure, expression_statement_structure::class);
			$result->append($data->expression);
			return $result;
		}
		if ($node->structure instanceof return_structure) {
			$data = object_cast($node->structure, return_structure::class);
			if ($data->expression !== null) {
				$result->append(object_cast($data->expression, ast_node::class));
			}
			return $result;
		}
		if ($node->structure instanceof binding_structure)
		{
			$data = object_cast($node->structure, binding_structure::class);
			if ($data->type_syntax !== null) {
				$result->append(object_cast($data->type_syntax, ast_node::class));
			}
			if ($data->target !== null) {
				$result->append(object_cast($data->target, ast_node::class));
			}
			if ($data->value !== null) {
				$result->append(object_cast($data->value, ast_node::class));
			}
			return $result;
		}
		if ($node->structure instanceof array_type_structure) {
			$data = object_cast($node->structure, array_type_structure::class);
			$result->append($data->element_type);
			$result->append($data->count);
			return $result;
		}
		if ($node->structure instanceof array_literal_structure)
		{
			$data = object_cast($node->structure, array_literal_structure::class);
			$items /** Storage<ast_node> */ = $data->elements;
			foreach ($items as $child) {
				$result->append($child);
			}
			return $result;
		}
		if ($node->structure instanceof index_structure) {
			$data = object_cast($node->structure, index_structure::class);
			$result->append($data->base);
			$result->append($data->index);
			return $result;
		}
		if ($node->structure instanceof struct_structure)
		{
			$data = object_cast($node->structure, struct_structure::class);
			$items /** Storage<ast_node> */ = $data->fields;
			foreach ($items as $child) {
				$result->append($child);
			}
			return $result;
		}
		if ($node->structure instanceof field_structure) {
			$data = object_cast($node->structure, field_structure::class);
			$result->append($data->type_syntax);
			return $result;
		}
		if ($node->structure instanceof field_access_structure) {
			$data = object_cast($node->structure, field_access_structure::class);
			$result->append($data->base);
			return $result;
		}
		return $result;
	}

	/** Reject inconsistent kind/payload pairs at the parser construction boundary. */
	public static function validate_payload(node_kind $kind, ?node_structure $payload): void
	{
		$valid = match ($kind)
		{
			node_kind::file, node_kind::block => $payload instanceof block_structure,
			node_kind::function_declaration => $payload instanceof function_structure,
			node_kind::parameter_declaration => $payload instanceof parameter_structure,
			node_kind::call_expression => $payload instanceof call_structure,
			node_kind::binary_expression, node_kind::assignment_expression => $payload instanceof binary_structure,
			node_kind::expression_statement => $payload instanceof expression_statement_structure,
			node_kind::return_statement => $payload instanceof return_structure,
			node_kind::variable_binding_statement => $payload instanceof binding_structure,
			node_kind::array_type => $payload instanceof array_type_structure,
			node_kind::array_literal => $payload instanceof array_literal_structure,
			node_kind::index_expression => $payload instanceof index_structure,
			node_kind::struct_declaration => $payload instanceof struct_structure,
			node_kind::field_declaration => $payload instanceof field_structure,
			node_kind::field_expression => $payload instanceof field_access_structure,
			node_kind::identifier, node_kind::punctuation, node_kind::comment,
			node_kind::integer_literal, node_kind::variable_reference => $payload === null,
		};
		if (!$valid) {
			throw new \LogicException('Invalid AST payload for ' . Node_Kind_Name::text($kind));
		}

		// Check local field relationships once, before the parser publishes the node.
		// Children were validated when constructed; do not traverse the subtree again.
		if ($kind === node_kind::variable_binding_statement) {
			self::validate_binding(object_cast($payload, binding_structure::class));
		}
		elseif ($kind === node_kind::parameter_declaration) {
			self::validate_parameter(object_cast($payload, parameter_structure::class));
		}
	}

	/** Preserve the distinction between declarations, unresolved names and explicit target writes. */
	private static function validate_binding(binding_structure $binding): void
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
	private static function validate_parameter(parameter_structure $parameter): void
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
	public static function block_data(ast_node $node): block_structure
	{
		return object_cast($node->structure, block_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function function_data(ast_node $node): function_structure
	{
		return object_cast($node->structure, function_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function parameter_data(ast_node $node): parameter_structure
	{
		return object_cast($node->structure, parameter_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function call_data(ast_node $node): call_structure
	{
		return object_cast($node->structure, call_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function binding_data(ast_node $node): binding_structure
	{
		return object_cast($node->structure, binding_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function return_data(ast_node $node): return_structure
	{
		return object_cast($node->structure, return_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function statement_data(ast_node $node): expression_statement_structure
	{
		return object_cast($node->structure, expression_statement_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function struct_data(ast_node $node): struct_structure
	{
		return object_cast($node->structure, struct_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function field_data(ast_node $node): field_structure
	{
		return object_cast($node->structure, field_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function field_access_data(ast_node $node): field_access_structure
	{
		return object_cast($node->structure, field_access_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function array_data(ast_node $node): array_literal_structure
	{
		return object_cast($node->structure, array_literal_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function array_type_data(ast_node $node): array_type_structure
	{
		return object_cast($node->structure, array_type_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function index_data(ast_node $node): index_structure
	{
		return object_cast($node->structure, index_structure::class);
	}
}

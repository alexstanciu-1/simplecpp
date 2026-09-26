<?php

/*
 * Role: derived syntax queries without processing state in data records.
 * Call map: Parser -> validate_payload; syntax consumers -> category.
 */
namespace scpp\compiler;

final class Syntax_Nodes
{
	/** Construct a complete kind/payload pair and link its direct syntax children once. */
	public static function make(node_kind $kind, int $start, int $end, ?node_structure $data = null): ast_node
	{
		if ($data === null)
		{
			if ($kind === node_kind::integer_literal) {
				$data = new integer_literal_structure();
			}
			elseif ($kind === node_kind::variable_reference) {
				$data = new variable_reference_structure();
			}
		}
		self::validate_payload($kind, $data);
		$node = new ast_node($kind, $start, $end, $data);
		$children /** Storage<ast_node> */ = self::child_nodes($node);
		ast_node::link_children($node, $children);
		return $node;
	}

	public static function integer_data(ast_node $node): integer_literal_structure
	{
		return object_cast($node->payload(), integer_literal_structure::class);
	}

	public static function boolean_data(ast_node $node): boolean_literal_structure
	{
		return object_cast($node->payload(), boolean_literal_structure::class);
	}

	public static function reference_data(ast_node $node): variable_reference_structure
	{
		return object_cast($node->payload(), variable_reference_structure::class);
	}

	/** Enumerate direct syntax children in grammar order before publishing navigation links. */
	public static function child_nodes(ast_node $node): Storage /** Storage<ast_node> */
	{
		$result /** Storage<ast_node> */ = new Storage();
		if ($node->payload() instanceof block_structure)
		{
			$data = object_cast($node->payload(), block_structure::class);
			$items /** Storage<ast_node> */ = $data->children;
			foreach ($items as $child) {
				$result->append($child);
			}
			return $result;
		}
		if ($node->payload() instanceof function_structure)
		{
			$data = object_cast($node->payload(), function_structure::class);
			$items /** Storage<ast_node> */ = $data->parameters;
			foreach ($items as $child) {
				$result->append($child);
			}
			$result->append($data->return_type);
			$result->append($data->body);
			return $result;
		}
		if ($node->payload() instanceof parameter_structure) {
			$data = object_cast($node->payload(), parameter_structure::class);
			$result->append($data->type_syntax);
			return $result;
		}
		if ($node->payload() instanceof call_structure)
		{
			$data = object_cast($node->payload(), call_structure::class);
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
		if ($node->payload() instanceof binary_structure) {
			$data = object_cast($node->payload(), binary_structure::class);
			$result->append($data->left);
			$result->append($data->right);
			return $result;
		}
		if ($node->payload() instanceof expression_statement_structure) {
			$data = object_cast($node->payload(), expression_statement_structure::class);
			$result->append($data->expression);
			return $result;
		}
		if ($node->payload() instanceof return_structure)
		{
			$data = object_cast($node->payload(), return_structure::class);
			if ($data->expression !== null) {
				$expression /** ast_node */ = $data->expression;
				$result->append($expression);
			}
			return $result;
		}
		if ($node->payload() instanceof binding_structure)
		{
			$data = object_cast($node->payload(), binding_structure::class);
			if ($data->type_syntax !== null) {
				$type_syntax /** ast_node */ = $data->type_syntax;
				$result->append($type_syntax);
			}
			if ($data->target !== null) {
				$target /** ast_node */ = $data->target;
				$result->append($target);
			}
			if ($data->value !== null) {
				$value /** ast_node */ = $data->value;
				$result->append($value);
			}
			return $result;
		}
		if ($node->payload() instanceof array_type_structure) {
			$data = object_cast($node->payload(), array_type_structure::class);
			$result->append($data->element_type);
			$result->append($data->count);
			return $result;
		}
		if ($node->payload() instanceof array_literal_structure)
		{
			$data = object_cast($node->payload(), array_literal_structure::class);
			$items /** Storage<ast_node> */ = $data->elements;
			foreach ($items as $child) {
				$result->append($child);
			}
			return $result;
		}
		if ($node->payload() instanceof index_structure) {
			$data = object_cast($node->payload(), index_structure::class);
			$result->append($data->base);
			$result->append($data->index);
			return $result;
		}
		if ($node->payload() instanceof struct_structure)
		{
			$data = object_cast($node->payload(), struct_structure::class);
			$items /** Storage<ast_node> */ = $data->fields;
			foreach ($items as $child) {
				$result->append($child);
			}
			return $result;
		}
		if ($node->payload() instanceof field_structure) {
			$data = object_cast($node->payload(), field_structure::class);
			$result->append($data->type_syntax);
			return $result;
		}
		if ($node->payload() instanceof field_access_structure) {
			$data = object_cast($node->payload(), field_access_structure::class);
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
			node_kind::identifier, node_kind::punctuation, node_kind::comment => $payload === null,
			node_kind::integer_literal => $payload instanceof integer_literal_structure,
			node_kind::boolean_literal => $payload instanceof boolean_literal_structure,
			node_kind::variable_reference => $payload instanceof variable_reference_structure,
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

		if (($binding->syntax_kind === binding_kind::declaration) !== $has_type) {
			throw new \LogicException('Invalid binding: declaration classification must agree with type syntax');
		}
		if ($has_target) {
			if ($binding->syntax_kind !== binding_kind::assignment) {
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
		return match ($node->kind())
		{
			node_kind::struct_declaration, node_kind::field_declaration, node_kind::array_type, node_kind::file, node_kind::function_declaration, node_kind::parameter_declaration, node_kind::identifier, node_kind::punctuation,
			node_kind::comment => node_category::syntax,
			node_kind::field_expression, node_kind::array_literal, node_kind::index_expression, node_kind::integer_literal, node_kind::boolean_literal, node_kind::variable_reference,
			node_kind::binary_expression, node_kind::assignment_expression, node_kind::call_expression => node_category::expression,
			node_kind::block, node_kind::expression_statement,
			node_kind::return_statement, node_kind::variable_binding_statement => node_category::statement,
		};
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function block_data(ast_node $node): block_structure
	{
		return object_cast($node->payload(), block_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function function_data(ast_node $node): function_structure
	{
		return object_cast($node->payload(), function_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function parameter_data(ast_node $node): parameter_structure
	{
		return object_cast($node->payload(), parameter_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function call_data(ast_node $node): call_structure
	{
		return object_cast($node->payload(), call_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function binding_data(ast_node $node): binding_structure
	{
		return object_cast($node->payload(), binding_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function return_data(ast_node $node): return_structure
	{
		return object_cast($node->payload(), return_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function statement_data(ast_node $node): expression_statement_structure
	{
		return object_cast($node->payload(), expression_statement_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function struct_data(ast_node $node): struct_structure
	{
		return object_cast($node->payload(), struct_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function field_data(ast_node $node): field_structure
	{
		return object_cast($node->payload(), field_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function field_access_data(ast_node $node): field_access_structure
	{
		return object_cast($node->payload(), field_access_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function array_data(ast_node $node): array_literal_structure
	{
		return object_cast($node->payload(), array_literal_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function array_type_data(ast_node $node): array_type_structure
	{
		return object_cast($node->payload(), array_type_structure::class);
	}

	/** Checked shared payload access; retains the node-owned record identity. */
	public static function index_data(ast_node $node): index_structure
	{
		return object_cast($node->payload(), index_structure::class);
	}
}

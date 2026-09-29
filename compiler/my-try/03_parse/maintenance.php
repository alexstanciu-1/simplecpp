<?php

/* Named owning edges only. Inspection cursors and semantic links are never traversed. */
namespace scpp\compiler;

abstract class Syntax_Maintenance implements node_maintenance_worker_i
{
	protected abstract function enter(ast_node $node): void;
	protected abstract function edge(ast_node $parent, ast_node $child): void;

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_file(file_node $node): void
	{
		$this->enter($node);
		$items /** Storage<declaration_node> */ = $node->declarations;
		foreach ($items as $child) {
			$this->edge($node, $child);
		}
		$this->edge($node, $node->body);
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_function_body(function_body_node $node): void
	{
		$this->enter($node);
		$items /** Storage<statement_node> */ = $node->statements;
		foreach ($items as $child) {
			$this->edge($node, $child);
		}
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_block(block_node $node): void
	{
		$this->enter($node);
		$items /** Storage<statement_node> */ = $node->statements;
		foreach ($items as $child) {
			$this->edge($node, $child);
		}
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_call(call_node $node): void
	{
		$this->enter($node);
		$items /** Storage<named_type_node> */ = $node->template_arguments;
		foreach ($items as $child) {
			$this->edge($node, $child);
		}
		$items /** Storage<expression_node> */ = $node->arguments;
		foreach ($items as $child) {
			$this->edge($node, $child);
		}
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_function(function_node $node): void
	{
		$this->enter($node);
		$items /** Storage<parameter_node> */ = $node->parameters;
		foreach ($items as $child) {
			$this->edge($node, $child);
		}
		$this->edge($node, $node->return_type);
		$this->edge($node, $node->body);
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_parameter(parameter_node $node): void
	{
		$this->enter($node);
		$this->edge($node, $node->type_syntax);
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_binary_expression(binary_expression_node $node): void
	{
		$this->enter($node);
		$this->edge($node, $node->left);
		$this->edge($node, $node->right);
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_assignment_expression(assignment_expression_node $node): void
	{
		$this->enter($node);
		$this->edge($node, $node->target);
		$this->edge($node, $node->value);
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_expression_statement(expression_statement_node $node): void
	{
		$this->enter($node);
		$this->edge($node, $node->expression);
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_return(return_node $node): void
	{
		$this->enter($node);
		if ($node->expression !== null) {
			$this->edge($node, $node->expression);
		}
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_variable_declaration(variable_declaration_node $node): void
	{
		$this->enter($node);
		$this->edge($node, $node->type_syntax);
		if ($node->initializer !== null) {
			$this->edge($node, $node->initializer);
		}
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_array_type(array_type_node $node): void
	{
		$this->enter($node);
		$this->edge($node, $node->element_type);
		$this->edge($node, $node->count);
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_array_literal(array_literal_node $node): void
	{
		$this->enter($node);
		$items /** Storage<expression_node> */ = $node->elements;
		foreach ($items as $child) {
			$this->edge($node, $child);
		}
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_index(index_node $node): void
	{
		$this->enter($node);
		$this->edge($node, $node->base);
		$this->edge($node, $node->index);
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_struct(struct_node $node): void
	{
		$this->enter($node);
		$items /** Storage<field_node> */ = $node->fields;
		foreach ($items as $child) {
			$this->edge($node, $child);
		}
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_field(field_node $node): void
	{
		$this->enter($node);
		$this->edge($node, $node->type_syntax);
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_field_access(field_access_node $node): void
	{
		$this->enter($node);
		$this->edge($node, $node->base);
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_named_type(named_type_node $node): void
	{
		$this->enter($node);
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_punctuation(punctuation_node $node): void
	{
		$this->enter($node);
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_comment(comment_node $node): void
	{
		$this->enter($node);
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_integer_literal(integer_literal_node $node): void
	{
		$this->enter($node);
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_float_literal(float_literal_node $node): void
	{
		$this->enter($node);
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_boolean_literal(boolean_literal_node $node): void
	{
		$this->enter($node);
	}

	/** Process this node and its directly owned syntax in grammar order. */
	public function visit_variable_reference(variable_reference_node $node): void
	{
		$this->enter($node);
	}
}

/** Publish inspection parents after the parser finishes a node's named fields. */
final class Syntax_Attachment extends Syntax_Maintenance
{
	public static function publish(ast_node $node): void
	{
		$node->maintain(new Syntax_Attachment());
	}

	protected function enter(ast_node $node): void
	{
	}

	protected function edge(ast_node $parent, ast_node $child): void
	{
		$child->set_inspection_parent($parent);
	}
}

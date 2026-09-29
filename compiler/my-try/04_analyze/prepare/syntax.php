<?php

/* Typed operation entry; semantic algorithms and their context remain preparation-owned. */
namespace scpp\compiler;

final class Syntax_Preparation implements preparation_worker_i
{
	private preparation_context $context;

	public function __construct(preparation_context $context)
	{
		$this->context = $context;
	}

	public static function expression(expression_node $node, preparation_context $context): prepared_expression
	{
		$node->prepare(new Syntax_Preparation($context));
		return $node->require_preparation();
	}

	/** Prepare only the scheduled executable body; declarations have separate work owners. */
	public function prepare_file_body(file_node $node): void
	{
		$this->prepare_function_body($node->body);
	}

	public function prepare_function_body(function_body_node $node): void
	{
		File_Preparation::prepare_statements($node->statements, $this);
	}

	public function prepare_block(block_node $node): void
	{
		File_Preparation::prepare_statements($node->statements, $this);
	}

	public function prepare_named_type(named_type_node $node): void
	{
		Declaration_Preparation::prepare_named_type($node, $this->context);
	}

	public function prepare_array_type(array_type_node $node): void
	{
		throw new \RuntimeException('S2S constructed types are not supported yet');
	}

	public function prepare_integer_literal(integer_literal_node $node): void
	{
		$node->set_preparation(File_Preparation::prepare_integer($node, $this->context));
	}

	public function prepare_float_literal(float_literal_node $node): void
	{
		$node->set_preparation(File_Preparation::prepare_float($node, $this->context));
	}

	public function prepare_boolean_literal(boolean_literal_node $node): void
	{
		$node->set_preparation(File_Preparation::prepare_boolean($node->value, $this->context));
	}

	public function prepare_variable_reference(variable_reference_node $node): void
	{
		$node->set_preparation(File_Preparation::prepare_reference($node, $this->context));
	}

	public function prepare_call(call_node $node): void
	{
		$node->set_preparation(Declaration_Preparation::prepare_call($node, $this->context));
	}

	public function prepare_field_access(field_access_node $node): void
	{
		$node->set_preparation(Declaration_Preparation::prepare_field_access($node, $this->context));
	}

	public function prepare_function_signature(function_node $node): void
	{
		Declaration_Preparation::prepare_function($node, $this->context);
	}

	public function prepare_struct(struct_node $node): void
	{
		Declaration_Preparation::prepare_struct($node, $this->context);
	}

	public function prepare_parameter(parameter_node $node): void
	{
		Declaration_Preparation::prepare_parameter($node, $this->context);
	}

	public function prepare_field(field_node $node): void
	{
		Declaration_Preparation::prepare_field($node, $this->context);
	}

	public function prepare_expression_statement(expression_statement_node $node): void
	{
		File_Preparation::prepare_expression_statement($node, $this->context);
	}

	public function prepare_return(return_node $node): void
	{
		File_Preparation::prepare_return($node, $this->context);
	}

	public function prepare_variable_declaration(variable_declaration_node $node): void
	{
		$node->set_preparation(File_Preparation::prepare_local_storage($node->occurrence(), $node->type_syntax, $node->initializer, $this->context));
	}

	public function prepare_assignment(assignment_expression_node $node): void
	{
		$node->set_preparation(File_Preparation::prepare_assignment($node, $this->context));
	}
}

<?php

/* Typed C++ dispatch over attached facts; fragment publication remains generator-owned. */
namespace scpp\compiler;

final class CPP_Syntax implements cpp_generation_worker_i
{
	private cpp_generation_context $context;

	public function __construct(cpp_generation_context $context)
	{
		$this->context = $context;
	}

	public function generate_file(file_node $node): string
	{
		return $this->generate_function_body($node->body);
	}

	public function generate_function_body(function_body_node $node): string
	{
		return CPP_Generator::generate_statements($node, $this->context);
	}

	public function generate_block(statement_body_node $node): string
	{
		return "{\n" . CPP_Generator::generate_statements($node, $this->context) . "}\n";
	}

	/** C++ native branches preserve lazy arm selection; conversion is already decided. */
	public function generate_if(if_node $node): string
	{
		if ($node->arm_kind === if_arm_kind::else_arm) {
			return 'else ' . $node->body->generate_cpp($this);
		}
		$condition /** expression_node */ = $node->condition;
		$value = $condition->generate_cpp($this);
		$value = CPP_Declarations::conversion($value, $node->require_preparation()->conversion, $this->context);
		$prefix = $node->arm_kind === if_arm_kind::initial ? 'if' : 'else if';
		$text = $prefix . ' ((' . $value . ').native_value()) ' . $node->body->generate_cpp($this);
		if ($node->next_arm !== null) {
			$next /** if_node */ = $node->next_arm;
			$text .= $next->generate_cpp($this);
		}
		return $text;
	}

	public function generate_integer_literal(integer_literal_node $node): string
	{
		return CPP_Generator::generate_integer($node->require_integer_literal_preparation(), $this->context);
	}

	public function generate_float_literal(float_literal_node $node): string
	{
		return CPP_Generator::generate_float($node->require_float_literal_preparation(), $this->context);
	}

	public function generate_boolean_literal(boolean_literal_node $node): string
	{
		return CPP_Generator::generate_boolean($node->require_boolean_literal_preparation(), $this->context);
	}

	public function generate_interpolated_string(interpolated_string_node $node): string
	{
		return CPP_Generator::generate_interpolated_string($node, $this->context);
	}

	public function generate_interpolation_text(interpolation_text_node $node): string
	{
		return CPP_Generator::generate_string($node->require_preparation(), $this->context);
	}

	public function generate_interpolation_value(interpolation_value_node $node): string
	{
		$value = $node->expression->generate_cpp($this);
		return CPP_Declarations::conversion($value, $node->require_preparation()->conversion, $this->context);
	}

	public function generate_string_literal(string_literal_node $node): string
	{
		return CPP_Generator::generate_string($node->require_string_literal_preparation(), $this->context);
	}

	public function generate_variable_reference(variable_reference_node $node): string
	{
		return CPP_Generator::generate_reference($node->require_variable_reference_preparation(), $this->context);
	}

	public function generate_constant_reference(constant_reference_node $node): string
	{
		return CPP_Generator::generate_constant($node->require_constant_reference_preparation(), $this->context);
	}

	public function generate_cast_expression(cast_expression_node $node): string
	{
		return CPP_Generator::generate_cast($node, $this->context);
	}

	public function generate_compound_assignment_expression(compound_assignment_expression_node $node): string
	{
		return CPP_Generator::generate_compound_assignment($node, $this->context);
	}

	public function generate_mutation_expression(mutation_expression_node $node): string
	{
		return CPP_Generator::generate_mutation($node, $this->context);
	}

	public function generate_unary_expression(unary_expression_node $node): string
	{
		return CPP_Generator::generate_unary($node, $this->context);
	}

	public function generate_binary_expression(binary_expression_node $node): string
	{
		return CPP_Generator::generate_binary($node, $this->context);
	}

	public function generate_call(call_node $node): string
	{
		return CPP_Declarations::generate_call($node, $this->context);
	}

	public function generate_field_access(field_access_node $node): string
	{
		return CPP_Declarations::generate_field_access($node, $this->context);
	}

	public function generate_function_signature(function_node $node): string
	{
		return CPP_Declarations::signature($node, $this->context);
	}

	public function generate_struct(struct_node $node): string
	{
		return CPP_Declarations::generate_struct($node, $this->context);
	}

	public function generate_parameter(parameter_node $node): string
	{
		$facts = $node->require_preparation();
		$reference = $facts->mode === passing_mode::reference ? '&' : '';
		return CPP_Declarations::type($facts->type, $this->context) . $reference . ' ' . CPP_Generator::local_name($node->occurrence());
	}

	public function generate_field(field_node $node): string
	{
		return CPP_Declarations::type($node->require_preparation()->type, $this->context) . ' ' . CPP_Generator::source_name('field', $node->name);
	}

	public function generate_expression_statement(expression_statement_node $node): string
	{
		return CPP_Generator::generate_expression_statement($node, $this->context);
	}

	public function generate_return(return_node $node): string
	{
		return CPP_Generator::generate_return($node, $this->context);
	}

	public function generate_variable_declaration(variable_declaration_node $node): string
	{
		return "\t" . CPP_Generator::generate_storage($node->require_preparation(), null, $node->initializer, true, $this->context) . ";\n";
	}

	/** Nested assignment returns a stored-value snapshot; statements own declaration lifting. */
	public function generate_assignment_expression(assignment_expression_node $node): string
	{
		return CPP_Generator::generate_assignment_expression($node, $this->context);
	}
}

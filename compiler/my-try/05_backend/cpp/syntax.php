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
		$value = $this->native_condition($condition, $node->require_preparation());
		$prefix = $node->arm_kind === if_arm_kind::initial ? 'if' : 'else if';
		$text = $prefix . ' (' . $value . ') ' . $node->body->generate_cpp($this);
		if ($node->next_arm !== null) {
			$next /** if_node */ = $node->next_arm;
			$text .= $next->generate_cpp($this);
		}
		return $text;
	}

	private function native_condition(expression_node $expression, prepared_condition $facts): string
	{
		$value = CPP_Declarations::conversion($expression->generate_cpp($this), $facts->conversion, $this->context);
		return '(' . $value . ').native_value()';
	}

	/** Native switch preserves single selection, source-order fallthrough and loop continue. */
	public function generate_switch(switch_node $node): string
	{
		$text = 'switch ((' . $node->selector->generate_cpp($this) . ").native_value()) {\n";
		$outer_break /** nullable<breakable_node> */ = $this->context->break_target;
		$this->context->break_target = $node;
		try {
			$groups /** Storage<switch_case_group> */ = $node->groups;
			foreach ($groups as $group) { $text .= $group->generate_cpp($this); }
		}
		finally { $this->context->break_target = $outer_break; }
		return $text . "}\n";
	}

	public function generate_switch_group(switch_case_group $node): string
	{
		$text = '';
		$labels /** Storage<switch_label> */ = $node->labels;
		foreach ($labels as $label) { $text .= $label->generate_cpp($this); }
		return $text . $node->body->generate_cpp($this);
	}

	public function generate_switch_label(switch_label $node): string
	{
		$value /** nullable<string> */ = $node->require_preparation()->decimal;
		if ($value === null) { return "default:\n"; }
		if ($value === '-9223372036854775808') { return "case (-9223372036854775807LL - 1LL):\n"; }
		return 'case ' . $value . "LL:\n";
	}

	private function loop_body(loop_node $node): string
	{
		$outer_break /** nullable<breakable_node> */ = $this->context->break_target;
		$outer_continue /** nullable<loop_node> */ = $this->context->continue_target;
		$this->context->break_target = $node;
		$this->context->continue_target = $node;
		try { return $node->body->generate_cpp($this); }
		finally {
			$this->context->break_target = $outer_break;
			$this->context->continue_target = $outer_continue;
		}
	}

	public function generate_while(while_node $node): string
	{
		$facts /** prepared_condition */ = $node->require_preparation()->condition;
		return 'while (' . $this->native_condition($node->condition, $facts) . ') ' . $this->loop_body($node);
	}

	public function generate_do_while(do_while_node $node): string
	{
		$facts /** prepared_condition */ = $node->require_preparation()->condition;
		return 'do ' . $this->loop_body($node) . 'while (' . $this->native_condition($node->condition, $facts) . ");\n";
	}

	/** A surrounding block preserves header bindings and ordinary declaration lowering. */
	public function generate_for(for_node $node): string
	{
		$text = "{\n";
		$initialization /** Storage<statement_node> */ = $node->initialization;
		foreach ($initialization as $statement) { $text .= $statement->generate_cpp($this); }
		$test = '';
		$conditions /** Storage<expression_node> */ = $node->conditions;
		$remaining = q_count($conditions);
		foreach ($conditions as $condition)
		{
			if ($test !== '') { $test .= ', '; }
			$remaining--;
			if ($remaining === 0) {
				$facts /** prepared_condition */ = $node->require_preparation()->condition;
				$test .= $this->native_condition($condition, $facts);
			}
			else { $test .= '(void)(' . $condition->generate_cpp($this) . ')'; }
		}
		$update_text = '';
		$updates /** Storage<expression_node> */ = $node->updates;
		foreach ($updates as $update) {
			if ($update_text !== '') { $update_text .= ', '; }
			$update_text .= '(void)(' . $update->generate_cpp($this) . ')';
		}
		return $text . 'for (; ' . $test . '; ' . $update_text . ') ' . $this->loop_body($node) . "}\n";
	}

	public function generate_control_transfer(control_transfer_node $node): string
	{
		$facts = $node->require_preparation();
		$expected /** nullable<breakable_node> */ = $this->context->break_target;
		if ($facts->transfer_kind === control_transfer_kind::continue_loop) {
			$expected = $this->context->continue_target;
		}
		if (weakref_get($facts->target) !== $expected) {
			throw new \RuntimeException('Prepared control transfer disagrees with emission target');
		}
		return $facts->transfer_kind === control_transfer_kind::break_construct ? "break;\n" : "continue;\n";
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

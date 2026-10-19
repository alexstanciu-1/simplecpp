<?php

/* Typed C++ dispatch over attached facts; fragment publication remains generator-owned. */
namespace scpp\compiler;

final class CPP_Syntax
{
	private cpp_generation_context $context;

	public function __construct(cpp_generation_context $context)
	{
		$this->context = $context;
	}

	/** Backend-owned selection only; typed handlers own child traversal. */
	public function expression(expression_node $node): string
	{
		if ($node instanceof closure_node) {
			$closure = object_cast($node, closure_node::class);
			$signature_work /** anonymous_signature_work */ = $closure->anonymous_identity()->signature_work;
			if (!Preparation_Status::complete($signature_work) || !Preparation_Status::complete($closure->require_body()->work())) {
				throw new \LogicException('C++ closure requires settled callable work');
			}
			$facts = $signature_work->require_facts();
			$parts /** vector<string> */ = [];
			$parameters /** Storage<prepared_parameter> */ = $facts->parameters;
			foreach ($parameters as $parameter) {
				$parts[] = CPP_Declarations::parameter_type($parameter->contract, $this->context, false) . ' '
					. CPP_Generator::local_name(object_cast(weakref_get($parameter->declaration), collected_name::class));
			}
			$previous_return = $this->context->return_type;
			$capture_parts /** vector<string> */ = [];
			$body_work = object_cast($closure->require_body()->work(), anonymous_body_work::class);
			$captures /** Storage<prepared_capture> */ = $body_work->captures;
			foreach ($captures as $capture) {
				if ($capture->receiver() !== null) { $capture_parts[] = 'this'; continue; }
				$capture_parts[] = ($capture->borrow === null ? '' : '&')
					. CPP_Generator::local_name(object_cast(weakref_get($capture->declaration), collected_name::class));
			}
			$this->context->return_type = $facts->signature->return_type;
			$body = '';
			try { $body = CPP_Generator::generate_statements($closure->require_body(), $this->context); }
			finally { $this->context->return_type = $previous_return; }
			// Value bindings stay read-only in preparation; mutable permits shared-payload APIs and callable invocation.
			$qualifier = q_count($capture_parts) === 0 ? '' : ' mutable';
			return '[' . self::comma_list($capture_parts) . '](' . self::comma_list($parts) . ')' . $qualifier . ' -> ' . CPP_Declarations::return_type($facts->signature, $this->context, false) . " {\n" . $body . '}';
		}
		if ($node instanceof object_type_node) {
			$object_type = object_cast($node, object_type_node::class);
			$facts = $object_type->require_object_type_preparation();
			$target = CPP_Types::payload($facts->target_payload, $this->context);
			CPP_Types::record_dependencies($target, $this->context);
			CPP_Declarations::type($object_type->operand->require_preparation()->type, $this->context);
			$this->context->headers['scpp/object_cast.hpp'] = true;
			$helper = $facts->operation === object_type_operation::checked_cast ? 'checked_object_cast' : 'object_is';
			return '::scpp::' . $helper . '<' . $target->spelling . '>('
				. $this->expression($object_type->operand) . ')';
		}
		if ($node instanceof ownership_observation_node)
		{
			$observation = object_cast($node, ownership_observation_node::class);
			$facts = $observation->require_observation_preparation();
			$operand = $this->expression($observation->operand);
			if ($facts->operation === ownership_observation_kind::derive_weak) {
				$this->context->headers['scpp/memory.hpp'] = true;
				return '::scpp::weak(' . $operand . ')';
			}
			$this->context->headers['scpp/weak_p.hpp'] = true;
			return '(' . $operand . ').lock()';
		}
		if ($node instanceof move_expression_node)
		{
			$move = object_cast($node, move_expression_node::class);
			$facts = $move->require_move_preparation();
			$this->context->headers['utility'] = true;
			// Materialize an owner now: std::move alone would only change the value category.
			return CPP_Declarations::type($facts->type, $this->context)
				. '(std::move(' . $this->expression($move->operand) . '))';
		}
		if ($node instanceof object_construction_node) {
			$construction = object_cast($node, object_construction_node::class);
			return CPP_Record_Construction::object($construction->require_construction_preparation(), $construction->arguments, $this->context);
		}
		if ($node instanceof index_node) {
			return $this->generate_index(object_cast($node, index_node::class));
		}
		if ($node instanceof data_construction_node) {
			return CPP_Data_Construction::generate(object_cast($node, data_construction_node::class), $this->context);
		}
		if ($node instanceof null_literal_node) {
			return $this->generate_null_literal(object_cast($node, null_literal_node::class));
		}
		if ($node instanceof integer_literal_node) {
			return $this->generate_integer_literal(object_cast($node, integer_literal_node::class));
		}
		if ($node instanceof float_literal_node) {
			return $this->generate_float_literal(object_cast($node, float_literal_node::class));
		}
		if ($node instanceof boolean_literal_node) {
			return $this->generate_boolean_literal(object_cast($node, boolean_literal_node::class));
		}
		if ($node instanceof string_literal_node) {
			return $this->generate_string_literal(object_cast($node, string_literal_node::class));
		}
		if ($node instanceof interpolated_string_node) {
			return $this->generate_interpolated_string(object_cast($node, interpolated_string_node::class));
		}
		if ($node instanceof variable_reference_node) {
			return $this->generate_variable_reference(object_cast($node, variable_reference_node::class));
		}
		if ($node instanceof constant_reference_node) {
			return $this->generate_constant_reference(object_cast($node, constant_reference_node::class));
		}
		if ($node instanceof static_member_reference_node) {
			return CPP_Declarations::member_reference(object_cast($node, static_member_reference_node::class), $this->context);
		}
		if ($node instanceof call_node) {
			return $this->generate_call(object_cast($node, call_node::class));
		}
		if ($node instanceof compound_assignment_expression_node) {
			return $this->generate_compound_assignment_expression(object_cast($node, compound_assignment_expression_node::class));
		}
		if ($node instanceof mutation_expression_node) {
			return $this->generate_mutation_expression(object_cast($node, mutation_expression_node::class));
		}
		if ($node instanceof unary_expression_node) {
			return $this->generate_unary_expression(object_cast($node, unary_expression_node::class));
		}
		if ($node instanceof binary_expression_node) {
			return $this->generate_binary_expression(object_cast($node, binary_expression_node::class));
		}
		if ($node instanceof cast_expression_node) {
			return $this->generate_cast_expression(object_cast($node, cast_expression_node::class));
		}
		if ($node instanceof assignment_expression_node) {
			return $this->generate_assignment_expression(object_cast($node, assignment_expression_node::class));
		}
		if ($node instanceof field_access_node) {
			return $this->generate_field_access(object_cast($node, field_access_node::class));
		}
		if ($node instanceof match_expression_node) {
			return $this->generate_match_expression(object_cast($node, match_expression_node::class));
		}
		if ($node instanceof coalesce_expression_node) {
			return $this->generate_coalesce_expression(object_cast($node, coalesce_expression_node::class));
		}
		if ($node instanceof conditional_expression_node) {
			return $this->generate_conditional_expression(object_cast($node, conditional_expression_node::class));
		}
		if ($node instanceof value_probe_node) {
			return $this->generate_value_probe(object_cast($node, value_probe_node::class));
		}
		if ($node instanceof take_expression_node) {
			return $this->generate_take_expression(object_cast($node, take_expression_node::class));
		}
		throw new \RuntimeException('C++ generation is not supported for this node');
	}

	/** Bind the receiver once, convert the key next, and copy the read result before releasing temporaries. */
	private function generate_index(index_node $node): string
	{
		if ($this->context->specialization_engine !== null) {
			return CPP_Concrete_Operations::read($node, $this->context);
		}
		$decision = $node->require_index_preparation()->decision;
		$helper = 'scpp::checked_index_read';
		if ($decision->operation === operator_operation::index_null_read) {
			$helper = 'scpp::null_index_read';
		}
		elseif ($decision->operation !== operator_operation::index_checked_read) {
			throw new \LogicException('Unsupported prepared index operation');
		}
		$this->context->headers['scpp/index_read.hpp'] = true;
		$id = $this->context->next_temporary;
		$this->context->next_temporary++;
		$receiver = 'index_receiver_' . $id;
		$key = 'index_key_' . $id;
		$argument /** expression_node */ = $node->index;
		$base = CPP_Declarations::bind_storage_receiver($node->base, $this->context);
		$converted_key = CPP_Declarations::conversion($this->expression($argument), $decision->operands[1], $this->context);
		return '([&]() -> ' . CPP_Declarations::type($decision->result_type, $this->context) . ' { '
			. $base->statements . 'const auto& ' . $receiver . ' = ' . $base->value . '; '
			. CPP_Declarations::type($decision->operands[1]->target_type, $this->context) . ' ' . $key . ' = ' . $converted_key . '; '
			. 'return ' . $helper . '(' . $receiver . ', ' . $key . '); }())';
	}

	/** Backend-owned selection only; typed handlers own child traversal. */
	public function statement(statement_node $node): string
	{
		if ($node instanceof cleanup_node) {
			return $this->generate_cleanup(object_cast($node, cleanup_node::class));
		}
		if ($node instanceof function_body_node) {
			return $this->generate_function_body(object_cast($node, function_body_node::class));
		}
		if ($node instanceof statement_body_node) {
			return $this->generate_block(object_cast($node, statement_body_node::class));
		}
		if ($node instanceof if_node) {
			return $this->generate_if(object_cast($node, if_node::class));
		}
		if ($node instanceof while_node) {
			return $this->generate_while(object_cast($node, while_node::class));
		}
		if ($node instanceof do_while_node) {
			return $this->generate_do_while(object_cast($node, do_while_node::class));
		}
		if ($node instanceof for_node) {
			return $this->generate_for(object_cast($node, for_node::class));
		}
		if ($node instanceof foreach_node) {
			return $this->generate_foreach(object_cast($node, foreach_node::class));
		}
		if ($node instanceof switch_node) {
			return $this->generate_switch(object_cast($node, switch_node::class));
		}
		if ($node instanceof control_transfer_node) {
			return $this->generate_control_transfer(object_cast($node, control_transfer_node::class));
		}
		if ($node instanceof expression_statement_node) {
			return $this->generate_expression_statement(object_cast($node, expression_statement_node::class));
		}
		if ($node instanceof echo_node) {
			$output = object_cast($node, echo_node::class);
			$this->context->headers['lang/php/support/php_string.hpp'] = true;
			$result = '';
			$operands /** Storage<expression_node> */ = $output->operands;
			foreach ($operands as $operand) {
				$result .= "\t::scpp::php::echo_one(" . $this->expression($operand) . ");\n";
			}
			return $result;
		}
		if ($node instanceof return_node) {
			return $this->generate_return(object_cast($node, return_node::class));
		}
		if ($node instanceof variable_declaration_node) {
			return $this->generate_variable_declaration(object_cast($node, variable_declaration_node::class));
		}
		throw new \RuntimeException('C++ generation is not supported for this node');
	}

	/** Backend-owned selection only; typed handlers own child traversal. */
	public function interpolation_part(interpolation_part_node $node): string
	{
		if ($node instanceof interpolation_text_node) {
			return $this->generate_interpolation_text(object_cast($node, interpolation_text_node::class));
		}
		if ($node instanceof interpolation_value_node) {
			return $this->generate_interpolation_value(object_cast($node, interpolation_value_node::class));
		}
		throw new \RuntimeException('C++ generation is not supported for this node');
	}

	public function generate_function_body(function_body_node $node): string
	{
		return CPP_Generator::generate_statements($node, $this->context);
	}

	public function generate_block(statement_body_node $node): string
	{
		return "{\n" . CPP_Generator::generate_statements($node, $this->context) . "}\n";
	}

	/** Consume the admitted operation; reset storage without clearing a shared pointee in place. */
	private function generate_cleanup(cleanup_node $node): string
	{
		$facts = $node->require_preparation();
		if ($facts instanceof prepared_keyed_removal) {
			$keyed = object_cast($facts, prepared_keyed_removal::class);
			return "\t" . CPP_Keyed_Path::generate($keyed->path, $this->context, true) . ";\n";
		}
		$type /** canonical_type_use */ = $facts->storage_type();
		return "\t" . $this->expression($node->target) . ' = '
			. CPP_Declarations::type($type, $this->context) . "{};\n";
	}

	/** C++ native branches preserve lazy arm selection; truthiness is already decided. */
	public function generate_if(if_node $node): string
	{
		if ($node->arm_kind === if_arm_kind::else_arm) {
			return 'else ' . $this->generate_block($node->body);
		}
		$condition /** expression_node */ = $node->condition;
		$value = $this->native_condition($condition, $node->require_preparation());
		$prefix = $node->arm_kind === if_arm_kind::initial ? 'if' : 'else if';
		$text = $prefix . ' (' . $value . ') ' . $this->generate_block($node->body);
		if ($node->next_arm !== null) {
			$next /** if_node */ = $node->next_arm;
			$text .= $this->generate_if($next);
		}
		return $text;
	}

	private function native_condition(expression_node $expression, prepared_condition $facts): string
	{
		return CPP_Declarations::truthiness($this->expression($expression), $facts->truthiness, $this->context);
	}

	/** Native switch preserves single selection, source-order fallthrough and loop continue. */
	public function generate_switch(switch_node $node): string
	{
		$subject = CPP_Selection::subject($this->expression($node->selector), $node->selector->require_preparation()->type);
		$text = 'switch (' . $subject . ") {\n";
		$outer_break /** nullable<breakable_node> */ = $this->context->break_target;
		$this->context->break_target = $node;
		try {
			$groups /** Storage<switch_case_group> */ = $node->groups;
			foreach ($groups as $group) { $text .= $this->generate_switch_group($group); }
		}
		finally { $this->context->break_target = $outer_break; }
		return $text . "}\n";
	}

	public function generate_switch_group(switch_case_group $node): string
	{
		$text = '';
		$labels /** Storage<switch_label> */ = $node->labels;
		foreach ($labels as $label) { $text .= $this->generate_switch_label($label); }
		return $text . $this->generate_block($node->body);
	}

	public function generate_switch_label(switch_label $node): string
	{
		$value /** nullable<prepared_selection_constant> */ = $node->require_preparation()->value;
		if ($value === null) { return "default:\n"; }
		return 'case ' . CPP_Selection::constant($value, $this->context) . ":\n";
	}

	/** Strict typed selection consumes exact constants; fallback is always last. */
	public function generate_match_expression(match_expression_node $node): string
	{
		$result_type = CPP_Declarations::type($node->require_preparation()->type, $this->context);
		$selector = $this->expression($node->selector);
		$temporary = 'match_subject_' . $this->context->next_temporary;
		$this->context->next_temporary++;
		$text = '([&]() -> ' . $result_type . ' { const auto ' . $temporary
			. ' = ' . CPP_Selection::subject($selector, $node->selector->require_preparation()->type) . '; ';
		$fallback = '';
		$arms /** Storage<match_arm_node> */ = $node->arms;
		foreach ($arms as $arm)
		{
			$facts = $arm->require_preparation();
			$result = CPP_Declarations::conversion($this->expression($arm->result), $facts->conversion, $this->context);
			if (q_count($facts->labels) === 0) {
				$fallback = 'return ' . $result . '; ';
				continue;
			}
			$checks = '';
			foreach ($facts->labels as $label) {
				if ($checks !== '') {
					$checks .= ' || ';
				}
				$checks .= '(' . $temporary . ' == ' . CPP_Selection::constant($label, $this->context) . ')';
			}
			$text .= 'if (' . $checks . ') { return ' . $result . '; } ';
		}
		if ($fallback === '') {
			$this->context->headers['stdexcept'] = true;
			$fallback = 'throw std::runtime_error("Unhandled match expression"); ';
		}
		return $text . $fallback . '}())';
	}

	public function loop_body(loop_node $node): string
	{
		$outer_break /** nullable<breakable_node> */ = $this->context->break_target;
		$outer_continue /** nullable<loop_node> */ = $this->context->continue_target;
		$this->context->break_target = $node;
		$this->context->continue_target = $node;
		try { return $this->generate_block($node->body); }
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
		foreach ($initialization as $statement) { $text .= $this->statement($statement); }
		$test = '';
		$conditions /** Storage<expression_node> */ = $node->conditions;
		$remaining = q_count($conditions);
		foreach ($conditions as $condition)
		{
			if ($test !== '') { $test .= ', '; }
			$remaining -= 1;
			if ($remaining === 0) {
				$facts /** prepared_condition */ = $node->require_preparation()->condition;
				$test .= $this->native_condition($condition, $facts);
			}
			else { $test .= '(void)(' . $this->expression($condition) . ')'; }
		}
		$update_text = '';
		$updates /** Storage<expression_node> */ = $node->updates;
		foreach ($updates as $update) {
			if ($update_text !== '') { $update_text .= ', '; }
			$update_text .= '(void)(' . $this->expression($update) . ')';
		}
		return $text . 'for (; ' . $test . '; ' . $update_text . ') ' . $this->loop_body($node) . "}\n";
	}

	/** Borrow the once-evaluated source; value bindings copy entries, not the collection. */
	public function generate_foreach(foreach_node $node): string
	{
		if ($this->context->specialization_engine !== null) {
			return CPP_Concrete_Operations::iterate($node, $this->context);
		}
		$facts /** prepared_iteration */ = $node->require_preparation()->iteration;
		$this->context->headers['scpp/value_iteration.hpp'] = true;
		$id = $this->context->next_temporary;
		$this->context->next_temporary++;
		$source = 'foreach_source_' . $id;
		$entry = 'foreach_entry_' . $id;
		$text = "{\nconst auto& " . $source . ' = ' . $this->expression($node->iterable) . ";\n";
		$text .= 'for (auto ' . $entry . ' : scpp::value_iteration(' . $source . ")) {\n";
		if ($facts->key !== null) {
			$text .= $this->iteration_binding($facts->key, $entry . '.key()');
		}
		$text .= $this->iteration_binding($facts->value, $entry . '.value_copy()');
		return $text . $this->loop_body($node) . "}\n}\n";
	}

	public function iteration_binding(prepared_binding $binding, string $value): string
	{
		$entry = object_cast(weakref_get($binding->declaration), collected_name::class);
		return CPP_Generator::generate_storage_value($binding, CPP_Generator::local_name($entry),
			$value, true, $this->context) . ";\n";
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

	/** Snapshot the source before any output write, then delegate branch behavior to the runtime. */
	public function generate_take_expression(take_expression_node $node): string
	{
		$node->require_take_preparation();
		$this->context->headers['lang/php/support/php_take.hpp'] = true;
		$source = $this->expression($node->source);
		$name = 'take_source_' . $this->context->next_temporary;
		$this->context->next_temporary++;
		$outputs = $this->expression($node->value_binding);
		if ($node->alternative_binding !== null) {
			$outputs .= ', ' . $this->expression($node->alternative_binding);
		}
		return '([&]() -> scpp::bool_t { auto ' . $name . ' = ' . $source
			. '; return scpp::php::take(' . $outputs . ', ' . $name . '); }())';
	}

	public function generate_null_literal(null_literal_node $node): string
	{
		$node->require_null_preparation();
		$this->context->headers['scpp/null_t.hpp'] = true;
		return 'scpp::null';
	}

	/** Probes use their own runtime semantics and evaluate the operand exactly once. */
	public function generate_value_probe(value_probe_node $node): string
	{
		$facts = $node->require_probe_preparation();
		$path = $facts->path;
		if ($path !== null) {
			return CPP_Keyed_Path::generate($path, $this->context, false, $facts->operation === value_probe_kind::empty, $facts->presence);
		}
		if ($facts->presence !== null) {
			$this->context->headers['scpp/bool_t.hpp'] = true;
			return 'scpp::bool_t(' . CPP_Declarations::truthiness($this->expression($node->operand), $facts->presence, $this->context) . ')';
		}
		$name = $facts->operation === value_probe_kind::isset ? 'isset' : 'empty';
		$this->context->headers['lang/php/operators/' . $name . '/' . $name . '.hpp'] = true;
		return 'scpp::php::' . $name . '(' . $this->expression($node->operand) . ')';
	}

	/** Typed returns force value results; runtime evaluation occurs only in the selected arm. */
	public function generate_coalesce_expression(coalesce_expression_node $node): string
	{
		$facts = $node->require_coalesce_preparation();
		$result_type = CPP_Declarations::type($facts->type, $this->context);
		$left = $this->expression($node->left);
		$fallback = CPP_Declarations::conversion($this->expression($node->fallback), $facts->fallback_conversion, $this->context);
		if ($facts->presence === coalesce_presence::always) {
			$extracted = CPP_Declarations::conversion($left, $facts->left_extraction, $this->context);
			$selected = CPP_Declarations::conversion($extracted, $facts->left_conversion, $this->context);
			return '([&]() -> ' . $result_type . ' { return ' . $selected . '; }())';
		}
		$temporary = 'coalesce_' . $this->context->next_temporary;
		$this->context->next_temporary++;
		$source_type = CPP_Declarations::type($facts->left_extraction->source_type, $this->context);
		$extracted = CPP_Declarations::conversion($temporary, $facts->left_extraction, $this->context);
		$selected = CPP_Declarations::conversion($extracted, $facts->left_conversion, $this->context);
		$presence = '';
		if ($facts->presence === coalesce_presence::wrapper_value) {
			$presence = $temporary . '.has_value().native_value()';
		}
		elseif ($facts->presence === coalesce_presence::boxed_nonnull) {
			$presence = '!' . $temporary . '.is_null().native_value()';
		}
		elseif ($facts->presence === coalesce_presence::handle_presence) {
			$presence = 'static_cast<bool>(' . $temporary . ')';
		}
		else {
			throw new \LogicException('Unsupported prepared coalesce presence');
		}
		return '([&]() -> ' . $result_type . ' { ' . $source_type . ' ' . $temporary . ' = ' . $left
			. '; if (' . $presence . ') { return ' . $selected
			. '; } return ' . $fallback . '; }())';
	}

	/** Full ternary and Elvis use truthiness, not the coalescing presence rule. */
	public function generate_conditional_expression(conditional_expression_node $node): string
	{
		$facts = $node->require_conditional_preparation();
		$result_type = CPP_Declarations::type($facts->type, $this->context);
		$condition = $this->expression($node->condition);
		$prefix = '';
		$selected = '';
		if ($node->true_branch === null) {
			$temporary = 'conditional_' . $this->context->next_temporary;
			$this->context->next_temporary++;
			$condition_type = CPP_Declarations::type($facts->truthiness->source_type, $this->context);
			$prefix = $condition_type . ' ' . $temporary . ' = ' . $condition . '; ';
			$condition = $temporary;
			$selected = $temporary;
		}
		else {
			$branch /** expression_node */ = $node->true_branch;
			$selected = $this->expression($branch);
		}
		$test = CPP_Declarations::truthiness($condition, $facts->truthiness, $this->context);
		$selected = CPP_Declarations::conversion($selected, $facts->true_conversion, $this->context);
		$fallback = CPP_Declarations::conversion($this->expression($node->fallback), $facts->fallback_conversion, $this->context);
		return '([&]() -> ' . $result_type . ' { ' . $prefix . 'if (' . $test . ') { return '
			. $selected . '; } return ' . $fallback . '; }())';
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
		$value = $this->expression($node->expression);
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

	private static function comma_list(array $parts /** vector<string> */): string
	{
		$result = '';
		$separator = '';
		foreach ($parts as $part) {
			$result .= $separator . $part;
			$separator = ', ';
		}
		return $result;
	}
}

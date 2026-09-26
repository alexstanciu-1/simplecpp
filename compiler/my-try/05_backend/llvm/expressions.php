<?php

/*
 * Role: value and address emission methods on LLVM_Function_Generator.
 * Call map: LLVM_Generator::expression -> expression handlers -> expression_storage -> emit.
 */
namespace scpp\compiler;

trait LLVM_Expressions
{
	/** Keep decimal literal spelling canonical without relying on host integer width. */
	private function to_llvm_integer_literal(ast_node $node): llvm_operand
	{
		$tokens /** Storage<token> */ = $this->prepared->source->token_snapshot()->tokens;
		$text = $tokens[(int) $node->token_index]->text();
		$text = LLVM_Text::decimal($text);
		$text = $text === '' ? '0' : $text;
		$maximum = $this->policy->integer_max;
		if (LLVM_Text::decimal_exceeds($text, $maximum)) {
			throw new \RuntimeException('LLVM experiment supports only nonnegative signed int32 literals');
		}
		$operand = new llvm_operand();
		$operand->type = $this->policy->integer_type;
		$operand->text = $text;
		return $operand;
	}

	/** Use the prepared declaration target; never perform name lookup in generation. */
	private function to_llvm_variable_reference(ast_node $node): llvm_operand
	{
		$local = $this->expression_storage($node);
		if (($local->array_type !== null) || ($local->struct_type !== null)) {
			throw new \RuntimeException('Whole-aggregate values are not supported yet');
		}
		$operand = new llvm_operand();
		$operand->type = $local->type;
		$operand->text = $this->temporary();
		$this->emit(($operand->text . " = load " . $local->type . ", ptr " . $local->address));
		return $operand;
	}

	/** Obtain writable storage from a resolved expression; calls and literals have no address. */
	private function expression_storage(ast_node $node): llvm_place
	{
		if ($node->kind() === node_kind::field_expression) {
			return $this->field_storage($node);
		}
		if ($node->kind() === node_kind::index_expression) {
			return $this->index_storage($node);
		}
		if ($node->kind() !== node_kind::variable_reference) {
			throw new \RuntimeException('Reference argument requires writable variable storage');
		}
		$lookup_token_index /** int */ = (int) $node->token_index;
		if (!isset($this->prepared->names->references[$lookup_token_index])) {
			throw new \RuntimeException('Missing prepared variable target');
		}
		$declaration /** collected_name */ = $this->prepared->names->references[(int) $node->token_index];
		if (!isset($this->initialized[$declaration->local_index])) {
			throw new \RuntimeException('Variable must be initialized before reading or passing by reference');
		}
		$local = $this->instance->locals[$declaration->local_index];
		$place = new llvm_place();
		$place->type = $local->type;
		$place->address = $local->address;
		$place->array_type = $local->array_type;
		$place->struct_type = $local->struct_type;
		return $place;
	}

	/** Field identity was resolved in preparation; only address calculation remains. */
	private function field_storage(ast_node $node): llvm_place
	{
		$syntax = Syntax_Nodes::field_access_data($node);
		$base = $this->expression_storage($syntax->base);
		$field = $this->instance->fields[$syntax->name_token_index];
		$place = new llvm_place();
		$place->type = $field->type;
		$place->address = $this->temporary();
		$this->emit(($place->address . " = getelementptr " . $base->type . ", ptr " . $base->address . ", i32 0, i32 " . $field->index));
		return $place;
	}

	/** Check every constant index before producing the element address for any consumer. */
	private function index_storage(ast_node $node): llvm_place
	{
		$syntax = Syntax_Nodes::index_data($node);
		$base = $this->expression_storage($syntax->base);
		if ($base->array_type === null) {
			throw new \RuntimeException('Indexing requires a fixed array');
		}
		if ($syntax->index->kind() !== node_kind::integer_literal) {
			throw new \RuntimeException('Dynamic indexes require runtime bounds checks and are not supported yet');
		}
		$index = $this->to_llvm_integer_literal($syntax->index);
		if ((int) $index->text >= $base->array_type->count) {
			throw new \RuntimeException('Fixed array index out of bounds: ' . $index->text);
		}
		$place = new llvm_place();
		$place->type = $base->array_type->element_type;
		$place->address = $this->temporary();
		$this->emit(($place->address . " = getelementptr " . $base->type . ", ptr " . $base->address . ", i32 0, i32 " . $index->text));
		return $place;
	}

	/** Build a constant aggregate only for an exact-length integer literal initializer. */
	private function array_initializer(ast_node $node, llvm_local $local): llvm_operand
	{
		if ($node->kind() !== node_kind::array_literal) {
			throw new \RuntimeException('Fixed arrays require an exact-length literal initializer');
		}
		$elements /** Storage<ast_node> */ = Syntax_Nodes::array_data($node)->elements;
		if (q_count($elements) !== $local->array_type->count) {
			throw new \RuntimeException('Fixed array initializer length does not match its type');
		}
		$values /** vector<string> */ = [];
		foreach ($elements as $element) {
			if ($element->kind() !== node_kind::integer_literal) {
				throw new \RuntimeException('Fixed array initializer elements must be integer literals');
			}
			$value = $this->to_llvm_integer_literal($element);
			$values[] = $value->type . ' ' . $value->text;
		}
		$result = new llvm_operand();
		$result->type = $local->type;
		$result->text = q_count($values) === 0 ? 'zeroinitializer' : '[' . LLVM_Text::join($values, ', ') . ']';
		return $result;
	}

	/** Emit a call using only its prepared signature and name; void supplies no value operand. */
	private function to_llvm_call_expression(ast_node $node): llvm_operand
	{
		$lookup_token_index /** int */ = (int) $node->token_index;
		if (!isset($this->instance->calls[$lookup_token_index])) {
			throw new \RuntimeException('Missing prepared function target');
		}
		$target /** llvm_prepared_function */ = $this->instance->calls[(int) $node->token_index];
		if (q_count(Syntax_Nodes::call_data($node)->arguments) !== q_count($target->parameters)) {
			throw new \RuntimeException(("Incorrect argument count for " . $target->name));
		}
		// Evaluate each argument expression in source order before emitting the call.
		$parameters /** Storage<llvm_parameter> */ = $target->parameters;
		$arguments /** vector<string> */ = [];
		foreach (Syntax_Nodes::call_data($node)->arguments as $index => $argument)
		{
			$parameter = $parameters[$index];
			$type = '';
			$text = '';
			if ($parameter->mode === passing_mode::reference) {
				$storage = $this->expression_storage($argument);
				$type = $storage->type;
				$text = $storage->address;
			}
			else {
				$value = $this->expression($argument);
				$type = $value->type;
				$text = $value->text;
			}
			if ($type !== $parameter->local->type) {
				throw new \RuntimeException(("Argument type mismatch for " . $target->name));
			}
			$arguments[] = $parameter->incoming->type . ' ' . $text;
		}
		$argument_text = LLVM_Text::join($arguments, ', ');
		$result = new llvm_operand();
		$result->type = $target->return_type;
		$result->text = $target->return_type === 'void' ? '' : $this->temporary();
		$assignment = $result->text === '' ? '' : $result->text . ' = ';
		$this->emit(($assignment . "call " . $target->return_type . " @" . $target->name . "(" . $argument_text . ")"));
		return $result;
	}
}

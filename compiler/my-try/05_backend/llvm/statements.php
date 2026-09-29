<?php

/*
 * Role: statement emission methods on LLVM_Function_Generator.
 * Call map: LLVM_Function_Generator::to_llvm_block -> statement handlers -> expression.
 */
namespace scpp\compiler;

trait LLVM_Statements
{
	/** Emit executable block children; declarations are emitted as separate LLVM functions. */
	private function to_llvm_block(ast_node $node): void
	{
		foreach (object_cast($node, function_body_node::class)->statements as $statement)
		{
			if ((($statement->kind() === node_kind::function_declaration) || ($statement->kind() === node_kind::struct_declaration))) {
				continue;
			}
			if ($this->block->terminated) {
				throw new \RuntimeException('LLVM experiment does not yet lower statements after return');
			}
			if ($statement->kind() === node_kind::variable_declaration) {
				$declaration = object_cast($statement, variable_declaration_node::class);
				$this->to_llvm_store($declaration, $declaration->type_syntax, null, $declaration->initializer);
			}
			elseif ($statement->kind() === node_kind::return_statement) {
				$this->to_llvm_return_statement($statement);
			}
			elseif ($statement->kind() === node_kind::expression_statement) {
				$this->to_llvm_expression_statement($statement);
			}
			else {
				throw new \RuntimeException('Unsupported LLVM statement: ' . Node_Kind_Name::text($statement->kind()));
			}
		}
		if (!$this->block->terminated) {
			if ($this->return_type !== 'void') {
				throw new \RuntimeException('LLVM experiment requires an explicit return value');
			}
			$this->emit('ret void');
			$this->block->terminated = true;
		}
	}

	private function to_llvm_expression_statement(ast_node $node): void
	{
		$expression = object_cast($node, expression_statement_node::class)->expression;
		if ($expression instanceof assignment_expression_node) {
			$assignment = object_cast($expression, assignment_expression_node::class);
			$target = $assignment->target;
			if ($target instanceof variable_reference_node) {
				$this->to_llvm_store($target, null, null, $assignment->value);
			}
			else {
				$this->to_llvm_store($target, null, $target, $assignment->value);
			}
			return;
		}
		$this->expression($expression);
	}

	/** Initialize or assign through prepared storage, including borrowed parameter addresses. */
	private function to_llvm_store(ast_node $node, ?type_node $type_syntax, ?assignable_expression_node $target, ?expression_node $initializer): void
	{
		if ($target !== null) {
			$place = $this->expression_storage($target);
			$value = $this->expression($initializer);
			$this->store_value($place->type, $place->address, $value);
			return;
		}
		$declaration = $this->binding_declaration($node, $type_syntax !== null);
		$local = $this->instance->locals[$declaration->local_index];
		if ($local->struct_type !== null)
		{
			if (($type_syntax === null) || ($initializer !== null)) {
				throw new \RuntimeException('Struct proof supports default initialization only');
			}
			$value = new llvm_operand();
			$value->type = $local->type;
			$value->text = 'zeroinitializer';
			$this->store_value($local->type, $local->address, $value);
			$this->initialized[$declaration->local_index] = true;
			return;
		}
		if ($initializer === null) {
			throw new \RuntimeException('LLVM store requires an initializer');
		}
		if (($local->array_type !== null) && ($type_syntax === null)) {
			throw new \RuntimeException('Whole-array assignment is not supported yet');
		}
		$value = $local->array_type !== null
		? $this->array_initializer($initializer, $local)
		: $this->expression($initializer);
		$this->store_value($local->type, $local->address, $value);
		$this->initialized[$declaration->local_index] = true;
	}

	/** A missing lookup is rejected before constructing a required handle. */
	private function binding_declaration(ast_node $node, bool $declaring): collected_name
	{
		if ($declaring) {
			$lookup_token_index /** int */ = $node->start_token();
			if (!isset($this->prepared->names->declarations[$lookup_token_index])) {
				throw new \RuntimeException('LLVM store requires a prepared declaration and value');
			}
			return $this->prepared->names->declarations[$node->start_token()];
		}
		$lookup_token_index /** int */ = $node->start_token();
		if (!isset($this->prepared->names->references[$lookup_token_index])) {
			throw new \RuntimeException('LLVM store requires a prepared declaration and value');
		}
		return $this->prepared->names->references[$node->start_token()];
	}

	/** All writes consume the same typed address/value contract. */
	private function store_value(string $type, string $address, llvm_operand $value): void
	{
		if ($value->type !== $type) {
			throw new \RuntimeException('LLVM experiment does not implement store conversions');
		}
		$this->emit(("store " . $type . " " . $value->text . ", ptr " . $address));
	}

	/** End the current block with the entry function's explicitly typed return. */
	private function to_llvm_return_statement(ast_node $node): void
	{
		if ($this->return_type === 'void')
		{
			if (object_cast($node, return_node::class)->expression !== null) {
				throw new \RuntimeException('A void function cannot return a value');
			}
			$this->emit('ret void');
			$this->block->terminated = true;
			return;
		}
		if (object_cast($node, return_node::class)->expression === null) {
			throw new \RuntimeException('LLVM experiment requires a return value');
		}
		$value = $this->expression(object_cast($node, return_node::class)->expression);
		if ($value->type !== $this->return_type) {
			throw new \RuntimeException('LLVM experiment does not implement return conversions');
		}
		$this->emit(("ret " . $value->type . " " . $value->text));
		$this->block->terminated = true;
	}
}

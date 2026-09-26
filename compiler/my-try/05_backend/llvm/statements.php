<?php

/*
 * Role: statement emission methods on LLVM_Function_Generator.
 * Call map: LLVM_Generator::to_llvm_block -> statement handlers -> expression.
 */
namespace scpp\compiler;

trait LLVM_Statements
{
	/** Emit executable block children; declarations are emitted as separate LLVM functions. */
	private function to_llvm_block(ast_node $node): void
	{
		foreach (Syntax_Nodes::block_data($node)->children as $statement)
		{
			if ((($statement->kind() === node_kind::function_declaration) || ($statement->kind() === node_kind::struct_declaration))) {
				continue;
			}
			if ($this->block->terminated) {
				throw new \RuntimeException('LLVM experiment does not yet lower statements after return');
			}
			if ($statement->kind() === node_kind::variable_binding_statement) {
				$this->to_llvm_variable_binding_statement($statement);
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
		$this->expression(Syntax_Nodes::statement_data($node)->expression);
	}

	/** Initialize or assign through prepared storage, including borrowed parameter addresses. */
	private function to_llvm_variable_binding_statement(ast_node $node): void
	{
		$binding = Syntax_Nodes::binding_data($node);
		if ($binding->target !== null) {
			$place = $this->expression_storage($binding->target);
			$value = $this->expression($binding->value);
			$this->store_value($place->type, $place->address, $value);
			return;
		}
		$declaration = $this->binding_declaration($node, $binding->type_syntax !== null);
		$local = $this->instance->locals[$declaration->local_index];
		if ($local->struct_type !== null)
		{
			if (($binding->type_syntax === null) || ($binding->value !== null)) {
				throw new \RuntimeException('Struct proof supports default initialization only');
			}
			$value = new llvm_operand();
			$value->type = $local->type;
			$value->text = 'zeroinitializer';
			$this->store_value($local->type, $local->address, $value);
			$this->initialized[$declaration->local_index] = true;
			return;
		}
		if ($binding->value === null) {
			throw new \RuntimeException('LLVM store requires an initializer');
		}
		if (($local->array_type !== null) && ($binding->type_syntax === null)) {
			throw new \RuntimeException('Whole-array assignment is not supported yet');
		}
		$value = $local->array_type !== null
		? $this->array_initializer($binding->value, $local)
		: $this->expression($binding->value);
		$this->store_value($local->type, $local->address, $value);
		$this->initialized[$declaration->local_index] = true;
	}

	/** A missing lookup is rejected before constructing a required handle. */
	private function binding_declaration(ast_node $node, bool $declaring): collected_name
	{
		if ($declaring) {
			$lookup_token_index /** int */ = (int) $node->token_index;
			if (!isset($this->prepared->names->declarations[$lookup_token_index])) {
				throw new \RuntimeException('LLVM store requires a prepared declaration and value');
			}
			return $this->prepared->names->declarations[(int) $node->token_index];
		}
		$lookup_token_index /** int */ = (int) $node->token_index;
		if (!isset($this->prepared->names->references[$lookup_token_index])) {
			throw new \RuntimeException('LLVM store requires a prepared declaration and value');
		}
		return $this->prepared->names->references[(int) $node->token_index];
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
			if (Syntax_Nodes::return_data($node)->expression !== null) {
				throw new \RuntimeException('A void function cannot return a value');
			}
			$this->emit('ret void');
			$this->block->terminated = true;
			return;
		}
		if (Syntax_Nodes::return_data($node)->expression === null) {
			throw new \RuntimeException('LLVM experiment requires a return value');
		}
		$value = $this->expression(Syntax_Nodes::return_data($node)->expression);
		if ($value->type !== $this->return_type) {
			throw new \RuntimeException('LLVM experiment does not implement return conversions');
		}
		$this->emit(("ret " . $value->type . " " . $value->text));
		$this->block->terminated = true;
	}
}

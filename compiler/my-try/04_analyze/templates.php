<?php

/*
 * Role: check bounded symbolic template contracts.
 * Call map: LLVM_Preparation::prepare_program -> Template_Checker::check -> statement -> expression.
 */
namespace scpp\compiler;

/** Bounded symbolic checks run even for unused definitions; substitution grants no permissions. */
final class Template_Checker
{
	private llvm_prepared_file $file;
	private array $bindings /** vector<string> */ = [];
	private array $locals /** hash<string, int> */ = [];
	private \SplObjectStorage $files;
	private llvm_policy $policy;

	/** Check template signatures and the supported straight-line bodies without concrete substitution. */
	public function check(Storage $files /** Storage<llvm_prepared_file> */, llvm_policy $policy): void
	{
		$this->policy = $policy;
		$this->files = new \SplObjectStorage();
		foreach ($files as $file) {
			$this->files[$file->source] = $file;
		}
		foreach ($files as $file)
		{
			$this->file = $file;
			foreach ($file->source->defined_elements as $index)
			{
				$entry = $file->source->entries[$index];
				if (($entry->kind !== collected_name_kind::function_declaration) || ($entry->node->specialization->template_parameters === [])) {
					continue;
				}
				$syntax = $entry->node->specialization;
				$this->bindings = [];
				foreach (array_keys($syntax->template_parameters) as $slot => $name) {
					if (isset($this->policy->types[$name])) {
						throw new \RuntimeException('Template parameter shadows a builtin type');
					}
					$this->bindings[] = 'parameter:' . $slot;
				}
				$this->locals = [];
				foreach ($syntax->parameters as $parameter)
				{
					$type = $this->type($file, $parameter->specialization->type_syntax, $this->bindings);
					if (($type === 'void') || (($parameter->specialization->mode === passing_mode::reference) && str_starts_with($type, 'parameter:'))) {
						throw new \RuntimeException('Generic contract does not support void parameters or mutable borrowing of bare T');
					}
					$declaration = $file->names->declarations[$parameter->specialization->name_token_index];
					$this->locals[$declaration->local_index] = $type;
				}
				$return = $this->type($file, $syntax->return_type, $this->bindings);
				$returned = false;
				foreach ($syntax->body->specialization->children as $statement) {
					if ($returned) {
						throw new \RuntimeException('Statements after return are not supported in template definitions');
					}
					$this->statement($statement, $return);
					$returned = $statement->kind === node_kind::return_statement;
				}
				if (($return !== 'void') && !$returned) {
					throw new \RuntimeException('Template definition requires an explicit value return');
				}
			}
		}
	}

	/** Symbolic type terms retain parameter slots; unknown coverage fails before specialization. */
	private function type(llvm_prepared_file $file, ast_node $node, array $bindings /** vector<string> */): string
	{
		if ($node->kind !== node_kind::identifier) {
			throw new \RuntimeException('Aggregate types in template definitions are not supported yet');
		}
		$slot = $file->names->template_slots[$node->token_index] ?? null;
		if ($slot !== null) {
			return $bindings[$slot] ?? throw new \RuntimeException('Missing symbolic template binding');
		}
		$name = $file->source->source->tokens[$node->token_index]->text;
		if (!isset($this->policy->types[$name])) {
			throw new \RuntimeException('Template proof supports only int, void and bound type parameters');
		}
		return $name;
	}

	/** Check stores and returns against symbolic types, keeping initialization separate from assignment. */
	private function statement(ast_node $node, string $return): void
	{
		$syntax = $node->specialization;
		if ($node->kind === node_kind::variable_binding_statement)
		{
			if (($syntax->target !== null) || ($syntax->value === null)) {
				throw new \RuntimeException('Template proof requires explicit value initialization and simple variable stores');
			}
			$declaration = $syntax->type_syntax === null
				? $this->file->names->references[$node->token_index] : $this->file->names->declarations[$node->token_index];
			$type = $syntax->type_syntax === null ? ($this->locals[$declaration->local_index] ?? null)
				: $this->type($this->file, $syntax->type_syntax, $this->bindings);
			$value = $this->expression($syntax->value);
			if (($value === 'void') || ($type !== $value)) {
				throw new \RuntimeException('Generic store requires matching symbolic types');
			}
			$this->locals[$declaration->local_index] = $type;
		}
		elseif ($node->kind === node_kind::return_statement) {
			$type = $syntax->expression === null ? 'void' : $this->expression($syntax->expression);
			if (($return !== $type) || (($return === 'void') && ($syntax->expression !== null))) {
				throw new \RuntimeException('Generic return requires matching symbolic types');
			}
		}
		elseif ($node->kind === node_kind::expression_statement) {
			$this->expression($syntax->expression);
		}
		else {
			throw new \RuntimeException('Unsupported statement in template definition');
		}
	}

	/** Call checking reads declared signatures only, allowing recursion without entering another body. */
	private function expression(ast_node $node): string
	{
		if ($node->kind === node_kind::integer_literal) {
			return 'int';
		}
		if ($node->kind === node_kind::variable_reference) {
			$declaration = $this->file->names->references[$node->token_index];
			return $this->locals[$declaration->local_index] ?? throw new \RuntimeException('Generic variable is not initialized');
		}
		if ($node->kind !== node_kind::call_expression) {
			throw new \RuntimeException('Generic member/index operations are not permitted by the current proof');
		}
		$target = $this->file->names->function_references[$node->token_index];
		$signature = $target->node->specialization;
		$arguments /** vector<string> */ = [];
		foreach ($node->specialization->template_arguments as $argument) {
			$type = $this->type($this->file, $argument, $this->bindings);
			if ($type === 'void') {
				throw new \RuntimeException('Template argument lacks the default generic value contract');
			}
			$arguments[] = $type;
		}
		if ((count($arguments) !== count($signature->template_parameters)) || (count($node->specialization->arguments) !== count($signature->parameters))) {
			throw new \RuntimeException('Generic call argument count mismatch');
		}
		// Name bindings for a target file remain shared and independent of this checking context.
		$target_file = $this->files[$target->file];
		foreach ($node->specialization->arguments as $index => $argument)
		{
			$parameter = $signature->parameters[$index]->specialization;
			$expected = $this->type($target_file, $parameter->type_syntax, $arguments);
			$actual = $this->expression($argument);
			if (($actual === 'void') || ($actual !== $expected)) {
				throw new \RuntimeException('Generic call requires matching symbolic types');
			}
			if (($parameter->mode === passing_mode::reference) && (($argument->kind !== node_kind::variable_reference) || str_starts_with($actual, 'parameter:'))) {
				throw new \RuntimeException('Generic reference call is unsupported');
			}
		}
		return $this->type($target_file, $signature->return_type, $arguments);
	}
}

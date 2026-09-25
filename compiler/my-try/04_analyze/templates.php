<?php

/*
 * Role: check bounded symbolic template contracts.
 * Call map: LLVM_Preparation::prepare_program -> Template_Checker::check -> statement -> expression.
 */
namespace scpp\compiler;

/** Bounded symbolic checks run even for unused definitions; substitution grants no permissions. */
final class Template_Checker
{
	public function check(Storage $files /** Storage<llvm_prepared_file> */, llvm_policy $policy): void
	{
		$file_index /** hash<llvm_prepared_file, shared<collected_file>> */ = new \SplObjectStorage /** hash<llvm_prepared_file, shared<collected_file>> */();
		foreach ($files as $file) { $file_index[$file->source] = $file; }
		$context = new template_check_context();
		$context->files = $file_index;
		$context->policy = $policy;
		foreach ($files as $file) {
			(new Template_File_Checker($file, $context))->check();
		}
	}
}

/** A complete file context; symbolic locals reset for each template definition. */
final class Template_File_Checker
{
	private llvm_prepared_file $file;
	private array $bindings /** vector<string> */ = [];
	private array $locals /** hash<string, int> */ = [];
	private template_check_context $context;

	public function __construct(llvm_prepared_file $file, template_check_context $context)
	{
		$this->file = $file;
		$this->context = $context;
	}

	public function check(): void
	{
		$file = $this->file;
		$entries /** Storage<collected_name> */ = $file->source->entries;
		foreach ($file->source->defined_elements as $index)
		{
			$entry = $entries[$index];
			if ($entry->kind !== collected_name_kind::function_declaration) { continue; }
			if (q_count(Syntax_Nodes::function_data($entry->node)->template_parameters) === 0) {
				continue;
			}
			$syntax = Syntax_Nodes::function_data($entry->node);
			$bindings /** vector<string> */ = [];
			$this->bindings = $bindings;
			foreach ($syntax->template_parameters as $name => $token_index) {
				if (isset($this->context->policy->types[$name])) {
					throw new \RuntimeException('Template parameter shadows a builtin type');
				}
				$this->bindings[] = 'parameter:' . q_count($this->bindings);
			}
			$locals /** hash<string, int> */ = [];
			$this->locals = $locals;
			foreach ($syntax->parameters as $parameter)
			{
				$type = $this->type($file, Syntax_Nodes::parameter_data($parameter)->type_syntax, $this->bindings);
				if (($type === 'void') || ((Syntax_Nodes::parameter_data($parameter)->mode === passing_mode::reference) && string_byte_starts_with($type, 'parameter:'))) {
					throw new \RuntimeException('Generic contract does not support void parameters or mutable borrowing of bare T');
				}
				$declaration = $file->names->declarations[Syntax_Nodes::parameter_data($parameter)->name_token_index];
				$this->locals[$declaration->local_index] = $type;
			}
			$return_type = $this->type($file, $syntax->return_type, $this->bindings);
			$return_typeed = false;
			foreach (Syntax_Nodes::block_data($syntax->body)->children as $statement) {
				if ($return_typeed) {
					throw new \RuntimeException('Statements after return are not supported in template definitions');
				}
				$this->statement($statement, $return_type);
				$return_typeed = $statement->kind === node_kind::return_statement;
			}
			if (($return_type !== 'void') && !$return_typeed) {
				throw new \RuntimeException('Template definition requires an explicit value return');
			}
		}
	}

	/** Symbolic type terms retain parameter slots; unknown coverage fails before specialization. */
	private function type(llvm_prepared_file $file, ast_node $node, array $bindings /** vector<string> */): string
	{
		if ($node->kind !== node_kind::identifier) {
			throw new \RuntimeException('Aggregate types in template definitions are not supported yet');
		}
		if (isset($file->names->template_slots[$node->token_index])) {
			$slot /** int */ = $file->names->template_slots[$node->token_index];
			if (!isset($bindings[$slot])) {
				throw new \RuntimeException('Missing symbolic template binding');
			}
			return $bindings[$slot];
		}
		$tokens /** Storage<token> */ = $file->source->source->tokens;
		$name = $tokens[$node->token_index]->text();
		if (!isset($this->context->policy->types[$name])) {
			throw new \RuntimeException('Template proof supports only int, void and bound type parameters');
		}
		return $name;
	}

	/** Check stores and returns against symbolic types, keeping initialization separate from assignment. */
	private function statement(ast_node $node, string $return_type): void
	{
		if ($node->kind === node_kind::variable_binding_statement)
		{
			$syntax = Syntax_Nodes::binding_data($node);
			if (($syntax->target !== null) || ($syntax->value === null)) {
				throw new \RuntimeException('Template proof requires explicit value initialization and simple variable stores');
			}
			$declaration = $syntax->type_syntax === null
				? $this->file->names->references[$node->token_index] : $this->file->names->declarations[$node->token_index];
			$type = '';
			if ($syntax->type_syntax === null) {
				if (isset($this->locals[$declaration->local_index])) { $type = $this->locals[$declaration->local_index]; }
			} else {
				$type = $this->type($this->file, $syntax->type_syntax, $this->bindings);
			}
			$value = $this->expression($syntax->value);
			if (($value === 'void') || ($type !== $value)) {
				throw new \RuntimeException('Generic store requires matching symbolic types');
			}
			$this->locals[$declaration->local_index] = $type;
		}
		elseif ($node->kind === node_kind::return_statement) {
			$syntax = Syntax_Nodes::return_data($node);
			$type = $syntax->expression === null ? 'void' : $this->expression($syntax->expression);
			if (($return_type !== $type) || (($return_type === 'void') && ($syntax->expression !== null))) {
				throw new \RuntimeException('Generic return requires matching symbolic types');
			}
		}
		elseif ($node->kind === node_kind::expression_statement) {
			$syntax = Syntax_Nodes::statement_data($node);
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
			if (!isset($this->locals[$declaration->local_index])) {
				throw new \RuntimeException('Generic variable is not initialized');
			}
			return $this->locals[$declaration->local_index];
		}
		if ($node->kind !== node_kind::call_expression) {
			throw new \RuntimeException('Generic member/index operations are not permitted by the current proof');
		}
		$target = $this->file->names->function_references[$node->token_index];
		$signature = Syntax_Nodes::function_data($target->node);
		$parameters /** Storage<ast_node> */ = $signature->parameters;
		$arguments /** vector<string> */ = [];
		foreach (Syntax_Nodes::call_data($node)->template_arguments as $argument) {
			$type = $this->type($this->file, $argument, $this->bindings);
			if ($type === 'void') {
				throw new \RuntimeException('Template argument lacks the default generic value contract');
			}
			$arguments[] = $type;
		}
		if ((q_count($arguments) !== q_count($signature->template_parameters)) || (q_count(Syntax_Nodes::call_data($node)->arguments) !== q_count($signature->parameters))) {
			throw new \RuntimeException('Generic call argument count mismatch');
		}
		// Name bindings for a target file remain shared and independent of this checking context.
		$target_file = $this->context->files[$target->file];
		foreach (Syntax_Nodes::call_data($node)->arguments as $index => $argument)
		{
			$parameter = Syntax_Nodes::parameter_data($parameters[$index]);
			$expected = $this->type($target_file, $parameter->type_syntax, $arguments);
			$actual = $this->expression($argument);
			if (($actual === 'void') || ($actual !== $expected)) {
				throw new \RuntimeException('Generic call requires matching symbolic types');
			}
			if (($parameter->mode === passing_mode::reference) && (($argument->kind !== node_kind::variable_reference) || string_byte_starts_with($actual, 'parameter:'))) {
				throw new \RuntimeException('Generic reference call is unsupported');
			}
		}
		return $this->type($target_file, $signature->return_type, $arguments);
	}
}

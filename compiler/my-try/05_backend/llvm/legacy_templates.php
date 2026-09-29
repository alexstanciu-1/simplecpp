<?php

/*
 * Role: check bounded template contracts for the parked LLVM experiment only.
 * Status: parked; extend File_Preparation/attached facts for new semantics, not this path.
 * Call map: LLVM_Preparation_Run::prepare_program -> LLVM_Legacy_Template_Checker::check -> LLVM_Legacy_Template_File_Checker::check.
 */
namespace scpp\compiler;

/** Bounded symbolic checks run even for unused definitions; substitution grants no permissions. */
final class LLVM_Legacy_Template_Checker
{
	/** Index prepared files once and check every template against the shared symbolic policy. */
	public function check(Storage $files /** Storage<llvm_prepared_file> */, llvm_policy $policy): void
	{
		$file_index /** hash<llvm_prepared_file, shared<collected_file>> */ = new \SplObjectStorage /** hash<llvm_prepared_file, shared<collected_file>> */();
		foreach ($files as $file) {
			$file_index[$file->source] = $file;
		}

		$context = new llvm_legacy_template_check_context();
		$context->files = $file_index;
		$context->policy = $policy;

		foreach ($files as $file) {
			(new LLVM_Legacy_Template_File_Checker($file, $context))->check();
		}
	}
}

/** A complete file context; symbolic locals reset for each template definition. */
final class LLVM_Legacy_Template_File_Checker
{
	private llvm_prepared_file $file;
	private array $bindings /** vector<string> */ = [];
	private array $locals /** hash<string, int> */ = [];
	private llvm_legacy_template_check_context $context;

	public function __construct(llvm_prepared_file $file, llvm_legacy_template_check_context $context)
	{
		$this->file = $file;
		$this->context = $context;
	}

	/** Validate each template with fresh bindings and locals, including unused definitions. */
	public function check(): void
	{
		$file = $this->file;
		$entries /** Storage<collected_name> */ = $file->source->entries;
		foreach ($file->source->defined_elements as $index)
		{
			$entry = $entries[$index];
			if (($entry->changes === \scpp\compiler\SYNC_DELETED) || ($entry->kind === collected_name_kind::field_declaration)) {
				continue;
			}
			if ($entry->kind !== collected_name_kind::function_declaration) {
				continue;
			}
			if (q_count(object_cast($entry->node, function_node::class)->template_parameters) === 0) {
				continue;
			}
			$syntax = object_cast($entry->node, function_node::class);

			// Each definition starts a new symbolic environment.
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
				$type = $this->type($file, object_cast($parameter, parameter_node::class)->type_syntax, $this->bindings);
				if (($type === 'void') || ((object_cast($parameter, parameter_node::class)->mode === passing_mode::reference) && string_byte_starts_with($type, 'parameter:'))) {
					throw new \RuntimeException('Generic contract does not support void parameters or mutable borrowing of bare T');
				}
				$declaration = $file->names->declarations[object_cast($parameter, parameter_node::class)->occurrence()->token_index];
				$this->locals[$declaration->local_index] = $type;
			}

			$return_type = $this->type($file, $syntax->return_type, $this->bindings);
			$returned = false;
			foreach (object_cast($syntax->body, function_body_node::class)->statements as $statement) {
				if ($returned) {
					throw new \RuntimeException('Statements after return are not supported in template definitions');
				}
				$this->statement($statement, $return_type);
				$returned = ($statement->kind() === node_kind::return_statement);
			}
			if (($return_type !== 'void') && (!$returned)) {
				throw new \RuntimeException('Template definition requires an explicit value return');
			}
		}
	}

	/** Symbolic type terms retain parameter slots; unknown coverage fails before specialization. */
	private function type(llvm_prepared_file $file, ast_node $node, array $bindings /** vector<string> */): string
	{
		if ($node->kind() !== node_kind::named_type) {
			throw new \RuntimeException('Aggregate types in template definitions are not supported yet');
		}
		$lookup_token_index /** int */ = $node->start_token();
		if (isset($file->names->template_slots[$lookup_token_index])) {
			$slot /** int */ = $file->names->template_slots[$node->start_token()];
			if (!isset($bindings[$slot])) {
				throw new \RuntimeException('Missing symbolic template binding');
			}
			return $bindings[$slot];
		}
		$tokens /** Storage<token> */ = $file->source->token_snapshot()->tokens;
		$name = $tokens[$node->start_token()]->text();
		if (!isset($this->context->policy->types[$name])) {
			throw new \RuntimeException('Template proof supports only int, void and bound type parameters');
		}
		return $name;
	}

	/** Check stores and returns against symbolic types, keeping initialization separate from assignment. */
	private function statement(ast_node $node, string $return_type): void
	{
		if ($node instanceof variable_declaration_node) {
			$declaration = object_cast($node, variable_declaration_node::class);
			$this->store($declaration, $declaration->type_syntax, $declaration->initializer);
		}
		elseif ($node->kind() === node_kind::return_statement) {
			$syntax = object_cast($node, return_node::class);
			$type = $syntax->expression === null ? 'void' : $this->expression($syntax->expression);
			if (($return_type !== $type) || (($return_type === 'void') && ($syntax->expression !== null))) {
				throw new \RuntimeException('Generic return requires matching symbolic types');
			}
		}
		elseif ($node->kind() === node_kind::expression_statement) {
			$syntax = object_cast($node, expression_statement_node::class);
			$expression = $syntax->expression;
			if ($expression instanceof assignment_expression_node) {
				$assignment = object_cast($expression, assignment_expression_node::class);
				if (!($assignment->target instanceof variable_reference_node)) {
					throw new \RuntimeException('Template proof requires explicit value initialization and simple variable stores');
				}
				$this->store($assignment->target, null, $assignment->value);
			}
			else {
				$this->expression($expression);
			}
		}
		else {
			throw new \RuntimeException('Unsupported statement in template definition');
		}
	}

	/** Preserve the parked symbolic-store rules over the two explicit syntax forms. */
	private function store(ast_node $node, ?type_node $type_syntax, ?expression_node $initializer): void
	{
		if ($initializer === null) {
			throw new \RuntimeException('Template proof requires explicit value initialization and simple variable stores');
		}
		$declaration = $type_syntax === null
		? $this->file->names->references[$node->start_token()] : $this->file->names->declarations[$node->start_token()];
		$type = '';
		if ($type_syntax === null) {
			if (isset($this->locals[$declaration->local_index])) {
				$type = $this->locals[$declaration->local_index];
			}
		}
		else {
			$type = $this->type($this->file, $type_syntax, $this->bindings);
		}
		$value = $this->expression($initializer);
		if (($value === 'void') || ($type !== $value)) {
			throw new \RuntimeException('Generic store requires matching symbolic types');
		}
		$this->locals[$declaration->local_index] = $type;
	}

	/** Call checking reads declared signatures only, allowing recursion without entering another body. */
	private function expression(ast_node $node): string
	{
		if ($node->kind() === node_kind::integer_literal) {
			return 'int';
		}
		if ($node->kind() === node_kind::variable_reference) {
			$declaration = $this->file->names->references[$node->start_token()];
			if (!isset($this->locals[$declaration->local_index])) {
				throw new \RuntimeException('Generic variable is not initialized');
			}
			return $this->locals[$declaration->local_index];
		}
		if ($node->kind() !== node_kind::call_expression) {
			throw new \RuntimeException('Generic member/index operations are not permitted by the current proof');
		}
		$target = $this->file->names->function_references[$node->start_token()];
		$signature = object_cast($target->node, function_node::class);
		$parameters /** Storage<parameter_node> */ = $signature->parameters;
		$arguments /** vector<string> */ = [];
		foreach (object_cast($node, call_node::class)->template_arguments as $argument) {
			$type = $this->type($this->file, $argument, $this->bindings);
			if ($type === 'void') {
				throw new \RuntimeException('Template argument lacks the default generic value contract');
			}
			$arguments[] = $type;
		}
		if ((q_count($arguments) !== q_count($signature->template_parameters)) || (q_count(object_cast($node, call_node::class)->arguments) !== q_count($signature->parameters))) {
			throw new \RuntimeException('Generic call argument count mismatch');
		}
		// Name bindings for a target file remain shared and independent of this checking context.
		$target_file = $this->context->files[$target->collection];
		foreach (object_cast($node, call_node::class)->arguments as $index => $argument)
		{
			$parameter = object_cast($parameters[$index], parameter_node::class);
			$expected = $this->type($target_file, $parameter->type_syntax, $arguments);
			$actual = $this->expression($argument);
			if (($actual === 'void') || ($actual !== $expected)) {
				throw new \RuntimeException('Generic call requires matching symbolic types');
			}
			if (($parameter->mode === passing_mode::reference) && (($argument->kind() !== node_kind::variable_reference) || string_byte_starts_with($actual, 'parameter:'))) {
				throw new \RuntimeException('Generic reference call is unsupported');
			}
		}
		return $this->type($target_file, $signature->return_type, $arguments);
	}
}

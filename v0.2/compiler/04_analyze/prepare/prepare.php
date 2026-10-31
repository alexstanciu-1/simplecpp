<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Chooses the entry return conversion from known value facts and the native target range.
 */
final class Exit_Preparation
{
	/** Decide the native exit boundary once; unknown values retain a required runtime check. */
	public static function decide(?int $constant /** int64 */, entry_target $target, ast_node $span): exit_conversion
	{
		if ($constant === null) {
			return exit_conversion::checked;
		}
		if (!$target->contains($constant)) {
			throw new diagnostic($span->source(), $span->byte_start(), 'Entry return is outside native int range');
		}
		return exit_conversion::proven;
	}
}

/**
 * Prepares a body in source order, resolving locals and calls and retaining only the additional facts lowering needs.
 */
final class Preparation
{
	private array $locals /** array<string,ast_local_declaration> */ = [];
	private int $statement_visits /** uint32 */ = 0;
	private int $expression_visits /** uint32 */ = 0;
	private int $name_lookups /** uint32 */ = 0;

	/** Standalone clients use the same declaration-before-body orchestration as multifile programs. */
	public function prepare(ast_file_node $file, entry_target $target): prepared_entry
	{
		return Program_Preparation::prepare('entry', ['entry' => $file], $target)->entry;
	}

	/** Prepare only the selected executable body, consuming already settled callable contracts. */
	public function entry(ast_file_node $file, entry_target $target, array $callables /** array<string,ast_function_declaration> */): void
	{
		$source = $file->tokens->source;
		$main = $file->main();
		if ($main === null) {
			throw new diagnostic($source, strlen($source->content), 'Entry slice requires an explicit value return');
		}
		$this->function_body($main->body(), $callables, $target);
	}

	public function function_body(ast_function_body $syntax, array $callables /** array<string,ast_function_declaration> */, ?entry_target $target = null): void
	{
		Function_Preparation::reset_body($syntax);
		$errors = Declaration_Diagnostics::locals($syntax);
		if ($errors !== []) { throw new diagnostic_group($errors); }
		$this->body($syntax->statements, $syntax->source(),
			($target === null ? $syntax->byte_end() : strlen($syntax->source()->content)), $target, $callables);
		$syntax->settle_preparation($this->statement_visits, $this->expression_visits, $this->name_lookups);
	}

	/** One source-order body traversal; null exit target denotes a void function, never inferred return typing. */
	private function body(array $syntax /** list<ast_statement> */,
		source_file $source, int $end /** uint32 */, ?entry_target $target, array $callables /** array<string,ast_function_declaration> */): void
	{
		$this->locals = [];
		$this->statement_visits = 0;
		$this->expression_visits = 0;
		$this->name_lookups = 0;
		$returned = false;
		foreach ($syntax as $statement)
		{
			$this->statement_visits++;
			if ($returned) {
				throw new diagnostic($statement->source(), $statement->byte_start(),
					'Statements after return are outside the entry slice');
			}
			if ($statement instanceof ast_local_declaration) {
				$this->declaration($statement);
			}
			elseif ($statement instanceof ast_echo_statement)
			{
				if ($statement->value instanceof ast_string_expression) {
					++$this->expression_visits;
					$statement->value->settle_bytes(Output_Literals::decode($statement->value));
				}
				else {
					$this->value($statement->value);
				}
			}
			elseif ($statement instanceof ast_call_statement)
			{
				$this->name_lookups++;
				$function = $callables[$statement->name] ?? null;
				if ($function === null) {
					throw new diagnostic($source, $statement->byte_start(), 'Unknown function: ' . $statement->name);
				}
				$statement->settle_function($function);
			}
			elseif ($statement instanceof ast_return_statement)
			{
				if ($target === null) {
					if ($statement->value !== null) {
						throw new diagnostic($source, $statement->byte_start(), 'Void function cannot return a value');
					}
				}
				else {
					if ($statement->value === null) {
						throw new diagnostic($source, $statement->byte_start(), 'Entry requires a value return');
					}
					$this->value($statement->value);
					$statement->settle_conversion(Exit_Preparation::decide($statement->value->constant(), $target, $statement->value));
				}
				$returned = true;
			}
			else {
				throw new diagnostic($source, $statement->byte_start(), 'Assignments are outside the entry slice');
			}
		}
		if (($target !== null) && !$returned) {
			throw new diagnostic($source, $end, 'Entry slice requires an explicit value return');
		}
	}

	/** Establish a binding only after its initializer succeeds; collection grants no forward visibility. */
	private function declaration(ast_local_declaration $declaration): void
	{
		$name = $declaration->declaration_key();
		$source = $declaration->source();
		$root = $declaration->root();
		if (isset($this->locals[$name])) {
			throw new diagnostic($source, $root->byte_at($declaration->name_position()), 'Duplicate local: ' . $name);
		}
		if ($declaration->type_name === null) {
			throw new diagnostic($source, $root->byte_at($declaration->name_position()),
				'Inferred declarations are outside the entry slice');
		}
		if (!in_array($declaration->type_name, ['int', 'int64'], true)) {
			throw new diagnostic($source, $root->byte_at($declaration->type_position()),
				'Unknown or unsupported type: ' . $declaration->type_name);
		}
		$this->value($declaration->initializer, $name);
		$declaration->settle_type(type_definition::int64, $declaration->initializer->constant());
		$this->locals[$name] = $declaration;
	}

	/** Resolve each expression once; a reference retains its exact declaration binding. */
	private function value(ast_expression $syntax, ?string $initializing = null): void
	{
		$this->expression_visits++;
		if ($syntax instanceof ast_integer_expression) {
			$syntax->settle_integer($this->integer($syntax));
			return;
		}
		if ($syntax instanceof ast_name_expression)
		{
			$this->name_lookups++;
			$name = $syntax->name;
			if ($name === $initializing) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(),
					'Cannot read local during its initialization: ' . $name);
			}
			$binding = $this->locals[$name] ?? null;
			if ($binding === null) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(),
					'Unknown or not-yet-initialized local: ' . $name);
			}
			$syntax->settle_local($binding);
			return;
		}
		throw new \LogicException('Unhandled expression preparation');
	}

	/** Compare decimal magnitude before host conversion; leading zeros must not become C++ octal. */
	private function integer(ast_integer_expression $syntax): int /** int64; nonnegative in this syntax slice */
	{
		$decimal = ltrim($syntax->source_text(), '0');
		$limit = '9223372036854775807';
		if ((strlen($decimal) > strlen($limit))
			|| ((strlen($decimal) === strlen($limit)) && (strcmp($decimal, $limit) > 0))) {
			throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Integer literal is outside int64 range');
		}
		return $decimal === '' ? 0 : (int) $decimal;
	}
}

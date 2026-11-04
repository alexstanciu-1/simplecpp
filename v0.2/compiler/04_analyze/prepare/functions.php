<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Settles function return types before body analysis and resets source-owned facts for sequential reanalysis.
 */
final class Function_Preparation
{
	/** Reset source-owned facts before a new sequential run; no foreign bodies are traversed. */
	public static function reset(ast_function_declaration $function): void
	{
		$function->reset_preparation();
		self::reset_body($function->body());
	}

	/** Reset every supported body fact before a new traversal, including expression occurrences. */
	public static function reset_body(ast_function_body $body): void
	{
		$body->root()->begin_preparation();
		$body->reset_preparation();
		foreach ($body->statements as $statement)
		{
			if (($statement instanceof ast_local_declaration) || ($statement instanceof ast_call_statement)
				|| ($statement instanceof ast_return_statement)) {
				$statement->reset_preparation();
			}
			if ($statement instanceof ast_local_declaration) {
				$statement->initializer->reset_preparation();
			}
			elseif (($statement instanceof ast_echo_statement) || ($statement instanceof ast_return_statement)
				|| ($statement instanceof ast_assignment_statement)) {
				$statement->value?->reset_preparation();
			}
		}
	}

	/**
	 * Settle reset declarations before body work; callable recursion has no signature dependency
	 * cycle.
	 */
	public static function declarations(scope $symbols): scope
	{
		$errors = Declaration_Diagnostics::ordered(Declaration_Diagnostics::functions($symbols));
		if ($errors !== []) {
			throw new diagnostic_group($errors);
		}
		foreach ($symbols->declarations as $declaration)
		{
			$type = $declaration->return_type_name;
			if ($type !== 'void') {
				throw new diagnostic($declaration->source(), $declaration->root()->byte_at($declaration->return_type_position()),
					'Only explicit void functions are supported');
			}
			if (!$declaration->return_type_ready()) {
				$declaration->settle_return_type(type_definition::void);
			}
		}
		return $symbols;
	}

	/**
	 * Prepare only this source's bodies, reading foreign signatures but never foreign executable
	 * syntax.
	 */
	public static function bodies(ast_file_node $file, scope $symbols): void
	{
		foreach ($file->functions() as $declaration) {
			$node = $declaration;
			$node->return_type_def();
			(new Preparation())->function_body($node->body(), $symbols);
		}
	}
}

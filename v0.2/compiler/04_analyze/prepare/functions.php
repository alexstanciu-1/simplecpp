<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Settles function return types before body analysis and resets source-owned facts for sequential reanalysis. */
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
			if (($statement instanceof ast_local_declaration)
				|| ($statement instanceof ast_return_statement) || ($statement instanceof ast_assignment_statement)) {
				$statement->reset_preparation();
			}
			if ($statement instanceof ast_local_declaration) {
				self::expression($statement->initializer);
			}
			elseif (($statement instanceof ast_echo_statement) || ($statement instanceof ast_return_statement)
				|| ($statement instanceof ast_assignment_statement) || ($statement instanceof ast_call_statement)) {
				if ($statement->value !== null) {
					self::expression($statement->value);
				}
			}
		}
	}

	/** Reset expression occurrences recursively; invocation targets remain foreign read-only contracts. */
	private static function expression(ast_expression $value): void
	{
		$value->reset_preparation();
		if ($value instanceof ast_call_expression) {
			foreach ($value->arguments as $argument) {
				self::expression($argument);
			}
		}
	}

	/** Settle reset declarations before body work; callable recursion has no signature dependency cycle. */
	public static function declarations(scope $symbols): scope
	{
		$errors = Declaration_Diagnostics::ordered(Declaration_Diagnostics::published($symbols));
		if ($errors !== []) {
			throw new diagnostic_group($errors);
		}
		foreach ($symbols->declarations as $declaration)
		{
			if (!($declaration instanceof ast_function_declaration)) {
				continue;
			}
			if ($declaration->return_type_ready()) {
				continue;
			}
			$type = $declaration->return_type_name === 'void' ? type_void::instance()
				: type_registry::resolve($declaration->return_type_name ?? '');
			if ($type === null) {
				throw new diagnostic($declaration->source(), $declaration->root()->byte_at($declaration->return_type_position()),
					'Unknown or unsupported return type: ' . $declaration->return_type_name);
			}
			$optional = false;
			foreach ($declaration->parameters as $parameter) {
				$parameter->reset_preparation();
				$parameter_type = type_registry::resolve($parameter->type_name);
				if ($parameter_type === null) {
					throw new diagnostic($parameter->source(), $parameter->byte_start(), 'Unknown or unsupported parameter type: ' . $parameter->type_name);
				}
				$parameter->settle_type($parameter_type);
				$default = $parameter->default_value;
				if ($default !== null) {
					if ($parameter->mode === passing_mode::reference) {
						throw new diagnostic($parameter->source(), $parameter->byte_start(), 'Reference parameter defaults are deferred');
					}
					if (!(($default instanceof ast_integer_expression) || ($default instanceof ast_float_expression)
						|| ($default instanceof ast_keyword_expression) || ($default instanceof ast_string_expression))) {
						throw new diagnostic($default->source(), $default->byte_start(), 'Parameter defaults require literals');
					}
					type_registry::prepare_literal($default);
					$parameter->settle_default($parameter_type->prepare_conversion($default, true));
					$optional = true;
				}
				elseif ($optional) {
					throw new diagnostic($parameter->source(), $parameter->byte_start(), 'Required parameter follows optional parameter');
				}
			}
			$declaration->settle_return_type($type);
		}
		return $symbols;
	}

	/** Prepare only this source's bodies, reading foreign signatures but never foreign executable syntax. */
	public static function bodies(ast_file_node $file, scope $symbols): void
	{
		foreach ($file->functions() as $declaration) {
			$node = $declaration;
			$node->return_type_def();
			(new Preparation())->function_body($node->body(), $symbols);
		}
	}
}

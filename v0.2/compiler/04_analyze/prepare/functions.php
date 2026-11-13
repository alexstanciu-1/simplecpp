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
		self::reset_block($body);
	}

	/** Reset nested source syntax once; the function remains the only dependency/completion owner. */
	private static function reset_block(ast_statement_block $body): void
	{
		foreach ($body->statements as $statement)
		{
			if ($statement instanceof ast_loop_statement) {
				if ($statement instanceof ast_for_statement) {
					$statement->initializer->reset_preparation();
					if (!($statement->initializer instanceof ast_update_statement)) {
						self::expression($statement->initializer instanceof ast_local_declaration
							? $statement->initializer->initializer : $statement->initializer->value);
					}
					$statement->update->reset_preparation();
					if ($statement->update instanceof ast_assignment_statement) {
						self::expression($statement->update->value);
					}
				}
				self::expression($statement->condition);
				self::reset_block($statement->body);
				continue;
			}
			if ($statement instanceof ast_if_statement) {
				foreach ($statement->arms as $arm) {
					if ($arm->condition !== null) {
						self::expression($arm->condition);
					}
					self::reset_block($arm->body);
				}
				continue;
			}
			if ($statement instanceof ast_field_assignment) {
				$statement->conversion = null;
				self::expression($statement->target);
				self::expression($statement->value);
				continue;
			}
			if (($statement instanceof ast_append_statement) || ($statement instanceof ast_index_assignment)) {
				$statement->reset_preparation();
				self::expression($statement->receiver);
				if ($statement instanceof ast_index_assignment) {
					self::expression($statement->index);
				}
				self::expression($statement->value);
				continue;
			}
			if (($statement instanceof ast_local_declaration)
				|| ($statement instanceof ast_return_statement) || ($statement instanceof ast_storage_write)) {
				$statement->reset_preparation();
			}
			if ($statement instanceof ast_property_compound) self::expression($statement->target);
			if ($statement instanceof ast_local_declaration) {
				self::expression($statement->initializer);
			}
			elseif ($statement instanceof ast_echo_statement) {
				foreach ($statement->values as $value) {
					self::expression($value);
				}
			}
			elseif (($statement instanceof ast_return_statement)
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
		if ($value instanceof ast_interpolated_string) {
			foreach ($value->parts as $part) {
				self::expression($part);
			}
			return;
		}
		if (($value instanceof ast_ownership_expression) || ($value instanceof ast_isset_expression) || ($value instanceof ast_not_expression) || ($value instanceof ast_cast_expression)
			|| ($value instanceof ast_unary_expression)) {
			self::expression($value->operand);
		}
		if ($value instanceof ast_field_expression) {
			self::expression($value->receiver);
		}
		if ($value instanceof ast_index_expression) {
			self::expression($value->receiver);
			self::expression($value->index);
		}
		if ($value instanceof ast_binary_expression) {
			self::expression($value->left);
			self::expression($value->right);
		}
		if ($value instanceof ast_call_expression || $value instanceof ast_construction_expression) {
			if ($value instanceof ast_call_expression && $value->receiver !== null) {
				self::expression($value->receiver);
			}
			foreach ($value->arguments as $argument) {
				self::expression($argument);
			}
		}
	}

	/** Settle reset declarations before body work; callable recursion has no signature dependency cycle. */
	public static function declarations(scope $symbols, ?Dependency_Watches $dependencies = null): scope
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
			$type = $declaration->return_type_reference === null ? null
				: Type_Resolution::signature($declaration->return_type_reference, $symbols, $declaration, $dependencies, true);
			if ($type === null) {
				throw new diagnostic($declaration->source(), $declaration->root()->byte_at($declaration->return_type_position()),
					'Unknown or unsupported return type: ' . $declaration->return_type_reference?->spelling());
			}
			$optional = false;
			foreach ($declaration->parameters as $parameter) {
				$parameter->reset_preparation();
				$parameter_type = Type_Resolution::signature($parameter->type_reference, $symbols, $declaration, $dependencies);
				if ($parameter_type === null) {
					throw new diagnostic($parameter->source(), $parameter->byte_start(), 'Unknown or unsupported parameter type: ' . $parameter->type_reference->spelling());
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
					$parameter->settle_default($parameter_type->prepare_conversion($default, conversion_context::typed_destination));
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
	public static function bodies(ast_file_node $file, scope $symbols, ?Dependency_Watches $dependencies = null): void
	{
		foreach ($file->functions() as $declaration) {
			$node = $declaration;
			$node->return_type_def();
			(new Preparation())->function_body($node->body(), $symbols, null, $dependencies);
		}
	}
}

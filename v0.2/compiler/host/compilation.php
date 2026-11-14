<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Transient JSON views expose stage contracts without putting serialization on retained records. */
final class Compilation_Inspection
{
	/**
	 * Collected references deliberately have no resolved binding or type.
	 *
	 * @return mixed[]
	 */
	public static function collection(ast_file_node $file): array /** array<string,mixed> */
	{
		$locals = [];
		foreach (($file->main()?->body()->locals ?? []) as $position => $local) {
			$locals[] = ['id' => $local->id, 'statement' => $position, 'name' => $local->name()];
		}
		$references = [];
		foreach (($file->main()?->body()->references ?? []) as $reference) {
			$references[] = ['name' => $reference->name, 'offset' => $reference->byte_start()];
		}
		$functions = [];
		foreach ($file->functions() as $id => $function) {
			$functions[] = ['id' => $id, 'name' => $function->name(),
				'return_type' => $function->return_type_reference?->spelling(),
				'body_locals' => count($function->body()->locals)];
		}
		$result = ['locals' => $locals, 'references' => $references, 'functions' => $functions];
		$result['globals'] = array_map(static fn(ast_global_declaration $global): array => [
			'name' => $global->name(), 'symbol' => $global->symbol_key(), 'type' => $global->type_reference?->spelling(),
		], $file->globals());
		$result['constants'] = array_map(static fn(ast_constant_declaration $constant): array => [
			'name' => $constant->name(), 'symbol' => $constant->symbol_key(), 'type' => $constant->type_reference?->spelling(),
		], $file->constants());
		if ($file->diagnostics !== []) {
			$result['diagnostics'] = array_map(static fn(diagnostic $error): string => $error->getMessage(),
				$file->diagnostics);
		}
		return $result;
	}

	/**
	 * Report settled identities, value facts, explicit exit conversions and actual work counts.
	 *
	 * @return mixed[]
	 */
	public static function program(prepared_entry $program): array /** array<string,mixed> */
	{
		$result = self::body($program->body());
		$result['native_int_digits'] = $program->target->digits;
		$result['functions'] = [];
		$result['globals'] = array_map(static fn(ast_global_declaration $global): array => [
			'name' => $global->name(), 'type' => $global->type_def()->name,
			'initializer' => self::scalar($global->type_def()->convert_constant(
				$global->initialization(), $global->initializer->constant())),
		], $program->file->globals());
		$result['constants'] = array_map(static fn(ast_constant_declaration $constant): array => [
			'name' => $constant->name(), 'type' => $constant->type_def()->name,
			'conversion' => value_conversion::name($constant->initialization()), 'initializer' => self::value($constant->initializer),
		], $program->file->constants());
		foreach ($program->file->functions() as $function) {
			$result['functions'][] = ['name' => $function->name(), 'return_type' => $function->return_type_def()->name,
				'parameters' => array_map(static fn(ast_parameter_declaration $parameter): array => [
					'name' => $parameter->short_name, 'type' => $parameter->type_def()->name, 'id' => $parameter->storage_id(),
					'passing' => $parameter->mode->name, 'default_conversion' => $parameter->default_value === null ? null : value_conversion::name($parameter->default_conversion()),
				], $function->parameters), 'body' => self::body($function->body())];
		}
		return $result;
	}

	/**
	 * Body views report resolved callable contracts without traversing another function's body.
	 *
	 * @return mixed[]
	 */
	public static function body(ast_statement_block $program): array /** array<string,mixed> */
	{
		if ($program instanceof ast_function_body) {
			$program->require_prepared();
		}
		$statements = [];
		foreach ($program->statements as $statement)
		{
			if (($statement instanceof ast_break_statement) || ($statement instanceof ast_continue_statement)) {
				$statements[] = ['kind' => $statement instanceof ast_break_statement ? 'break' : 'continue'];
			}
			elseif ($statement instanceof ast_loop_statement) {
				$row = ['kind' => $statement instanceof ast_do_while_statement ? 'do_while' : 'while',
					'condition' => self::value($statement->condition), 'body' => self::body($statement->body)];
				if ($statement instanceof ast_for_statement) {
					$row['kind'] = 'for';
					$row['initializer'] = self::binding($statement->initializer);
					$row['update'] = self::binding($statement->update);
				}
				$statements[] = $row;
			}
			elseif ($statement instanceof ast_if_statement) {
				$statements[] = ['kind' => 'if', 'arms' => array_map(static fn(ast_if_arm $arm): array => [
					'condition' => $arm->condition === null ? null : self::value($arm->condition),
					'body' => self::body($arm->body)], $statement->arms)];
			}
			elseif ($statement instanceof ast_echo_statement) {
				$statements[] = ['kind' => 'echo', 'values' => array_map(static fn(ast_expression $value): array => $value instanceof ast_string_expression
					? ['type' => 'string_literal', 'bytes_hex' => bin2hex($value->bytes())] : self::value($value), $statement->values)];
			}
			elseif (($statement instanceof ast_local_declaration) || ($statement instanceof ast_storage_write)) {
				$statements[] = self::binding($statement);
			}
			elseif (($statement instanceof ast_return_statement) && ($statement->value !== null)) {
				$statements[] = ['kind' => 'return', 'conversion' => value_conversion::name($statement->conversion()),
					'value' => self::value($statement->value)];
			}
			elseif ($statement instanceof ast_return_statement) {
				$statements[] = ['kind' => 'return', 'value' => null];
			}
			elseif ($statement instanceof ast_field_assignment) {
				$statements[] = ['kind' => 'field_write', 'field' => $statement->target instanceof ast_name_expression ? $statement->target->name : $statement->target->field, 'value' => self::value($statement->value)];
			}
			elseif ($statement instanceof ast_index_assignment) {
				$statements[] = ['kind' => 'keyed_write', 'receiver' => self::value($statement->receiver),
					'index' => self::value($statement->index), 'value' => self::value($statement->value),
					'conversion' => value_conversion::name($statement->conversion())];
			}
			elseif ($statement instanceof ast_append_statement) {
				$statements[] = ['kind' => 'append', 'receiver' => self::value($statement->receiver),
					'value' => self::value($statement->value), 'conversion' => value_conversion::name($statement->conversion())];
			}
			elseif ($statement instanceof ast_call_statement) {
				$statements[] = ['kind' => 'call', 'target' => $statement->value->target_name(),
					'return_type' => $statement->value->type_def()->name,
					'arguments' => array_map(self::value(...), $statement->value->cardinality === null && $statement->value->function_def() instanceof runtime_function
					? $statement->value->runtime_operands() : $statement->value->arguments)];
			}
		}
		return ['statements' => $statements,
			'work' => $program instanceof ast_function_body ? ['statements' => $program->statement_visits, 'expressions' => $program->expression_visits,
				'name_lookups' => $program->name_lookups] : null];
	}

	/** @return mixed[] Declaration/write facts shared by body inspection and for headers. */
	private static function binding(ast_local_declaration|ast_storage_write $statement): array /** array<string,mixed> */
	{
		if ($statement instanceof ast_local_declaration) {
			return ['kind' => 'local', 'id' => $statement->id,
				'type' => $statement->type_def()->name, 'initializer' => self::value($statement->initializer)];
		}
		if ($statement instanceof ast_property_update || $statement instanceof ast_index_update) {
			return ['kind' => $statement instanceof ast_index_update ? 'indexed_update' : 'property_update', 'target' => self::value($statement->target),
				'operator' => $statement->operator->name, 'postfix' => $statement->postfix];
		}
		if ($statement instanceof ast_update_statement) {
			return ['kind' => 'update', 'id' => $statement->local_def()->storage_id(),
				'operator' => $statement->operator->name, 'postfix' => $statement->postfix];
		}
		$row = ($statement instanceof ast_index_compound ? ['kind' => 'indexed_compound', 'target' => self::value($statement->target)]
			: ($statement instanceof ast_property_compound
			? ($statement->target instanceof ast_field_expression ? ['kind' => 'field_compound', 'target' => self::value($statement->target)]
				: ['kind' => 'static_compound', 'target' => $statement->target->declaration()->symbol_key()])
			: ['kind' => 'assignment', 'id' => $statement->local_def()->storage_id()])) + [
			'conversion' => value_conversion::name($statement->conversion()), 'operator' => $statement->operator?->name,
			'value' => self::value($statement->value)];
		if ($statement->operator !== null) {
			$row['execution_type'] = $statement->execution_type()->name;
			$row['left_conversion'] = value_conversion::name($statement->left_conversion());
			$row['write_back'] = value_conversion::name($statement->write_back());
		}
		return $row;
	}

	/**
	 * Integers use decimal strings and floats use exact hexadecimal literals, preserving precision and signed zero.
	 *
	 * @return mixed[]
	 */
	private static function value(ast_expression $value): array /** array<string,mixed> */
	{
		$row = ['type' => $value->type_def()->name,
			'constant' => self::scalar($value->constant()), 'constant_known' => $value->constant_known()];
		if ($value instanceof ast_construction_expression) {
			$row['arguments'] = array_map(self::value(...), $value->arguments);
		}
		if ($value instanceof ast_interpolated_string) {
			$row['parts'] = array_map(self::value(...), $value->parts);
		}
		if ($value instanceof ast_interpolation_name) {
			$row['conversion'] = value_conversion::name($value->conversion());
		}
		if ($value instanceof ast_cast_expression) {
			$row['conversion'] = value_conversion::name($value->conversion());
			$row['operand'] = self::value($value->operand);
		}
		if ($value instanceof ast_unary_expression) {
			$row['operator'] = $value->operator->name;
			$row['operand'] = self::value($value->operand);
		}
		if ($value instanceof ast_ownership_expression) {
			$row['operator'] = $value->lock ? 'lock' : 'weak';
			$row['operand'] = self::value($value->operand);
		}
		if (($value instanceof ast_not_expression) || ($value instanceof ast_isset_expression)) {
			$row['operator'] = $value instanceof ast_isset_expression ? 'isset' : 'logical_not';
			$row['operand'] = self::value($value->operand);
		}
		if ($value instanceof ast_field_expression) {
			$row['field'] = $value->field;
			$row['receiver'] = self::value($value->receiver);
		}
		if ($value instanceof ast_index_expression) {
			$row['receiver'] = self::value($value->receiver);
			$row['index'] = self::value($value->index);
		}
		if ($value instanceof ast_binary_expression) {
			if (!$value->operator->lazy()) {
				$row['execution_type'] = $value->execution_type()->name;
				$row['left_conversion'] = value_conversion::name($value->left_conversion);
				$row['right_conversion'] = value_conversion::name($value->right_conversion);
			}
			$row['operator'] = $value->operator->name;
			$row['left'] = self::value($value->left);
			$row['right'] = self::value($value->right);
		}
		if ($value instanceof ast_call_expression) {
			$row['target'] = $value->target_name();
			$row['arguments'] = array_map(self::value(...), $value->cardinality === null && $value->function_def() instanceof runtime_function
					? $value->runtime_operands() : $value->arguments);
		}
		if ($value instanceof ast_string_expression) {
			$row['bytes_hex'] = bin2hex($value->bytes());
		}
		if ($value instanceof ast_name_expression) {
			$declaration = $value->declaration();
			$row['binding'] = $declaration instanceof ast_local_binding
				? $declaration->storage_id() : $declaration->symbol_key();
		}
		return $row;
	}
	/** Preserve scalar identities and exact numeric values in the JSON inspection view. */
	private static function scalar(int|float|bool|null|constant_evaluation $value /** int64|float64|bool|null|constant_evaluation */): string|bool|null
	{
		if (is_float($value)) {
			return type_float64::instance()->cpp_constant($value);
		}
		return $value === constant_evaluation::unavailable ? null : (is_int($value) ? (string) $value : $value);
	}

}

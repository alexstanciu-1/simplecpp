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
				'body_locals' => count($function->optional_body()?->locals ?? [])];
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
		$body = $program->body();
		$result = $body === null ? ['statements' => [], 'work' => ['statements' => 0, 'expressions' => 0, 'name_lookups' => 0]] : self::body($body);
		$result['native_int_digits'] = $program->target->digits;
		$result['functions'] = [];
		$result['globals'] = array_map(self::global_row(...), $program->file->globals());
		$result['constants'] = array_map(static fn(ast_constant_declaration $constant): array => [
			'name' => $constant->name(), 'type' => $constant->type_def()->name,
			'conversion' => value_conversion::name($constant->initialization()), 'initializer' => self::value($constant->initializer),
		], $program->file->constants());
		foreach ($program->file->implementations() as $function) {
			$result['functions'][] = ['name' => $function->name(), 'called_type' => $function->instance?->called->declaration()->name(), 'return_type' => $function->return_type_def()->name,
				'parameters' => array_map(static fn(ast_parameter_declaration $parameter): array => [
					'name' => $parameter->short_name, 'type' => $parameter->type_def()->name, 'id' => $parameter->storage_id(),
					'passing' => $parameter->mode->name, 'default_conversion' => $parameter->default_value === null ? null : value_conversion::name($parameter->default_conversion()),
				], $function->parameters), 'body' => $function->optional_body() === null ? null : self::body($function->body())];
		}
		return $result;
	}

	/** Inspect the converted global initializer in both whole-program and per-file reports. @return mixed[] */
	public static function global_row(ast_global_declaration $global): array /** array<string,mixed> */
	{
		$row = ['name' => $global->name(), 'type' => $global->type_def()->name];
		if ($global->initializer === null) $row['initializer'] = null;
		else self::write_scalar($row, 'initializer', $global->type_def()->evaluate_constant($global->initialization(), $global->initializer));
		return $row;
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
			if ($statement instanceof ast_loop_transfer) {
				$statements[] = ['kind' => $statement instanceof ast_break_statement ? 'break' : 'continue'] + ($statement->numbered() ? ['depth' => $statement->depth()] : []);
			}
			elseif ($statement instanceof ast_foreach_statement) {
				$statements[] = ['kind' => 'foreach', 'source' => self::value($statement->source_value),
					'key' => $statement->key?->short_name, 'value' => $statement->value->short_name,
					'body' => self::body($statement->body)];
			}
			elseif ($statement instanceof ast_loop_statement) {
				if ($statement instanceof ast_for_statement) {
					$row = ['kind' => 'for', 'initializers' => array_map(self::binding(...), $statement->initializers),
						'conditions' => array_map(self::value(...), $statement->conditions),
						'updates' => array_map(self::binding(...), $statement->updates), 'body' => self::body($statement->body)];
				} else $row = ['kind' => $statement instanceof ast_do_while_statement ? 'do_while' : 'while',
					'condition' => self::value($statement->condition), 'body' => self::body($statement->body)];
				if ($statement instanceof ast_while_else_statement) $row['else'] = self::body($statement->otherwise);
				$statements[] = $row;
			}
			elseif ($statement instanceof ast_rethrow_statement) $statements[] = ['kind' => 'rethrow'];
			elseif ($statement instanceof ast_throw_statement) {
				$statements[] = self::value($statement->operation);
			}
			elseif ($statement instanceof ast_try_statement) {
				$row = ['kind' => 'try', 'body' => self::body($statement->body)];
				$row['handlers'] = array_map(static fn(ast_catch_clause $handler): array => [
					'catch_type' => $handler->binding->type_def()->name, 'binding' => $handler->binding->short_name,
					'body' => self::body($handler->body)], $statement->handlers);
				if ($statement->finalizer !== null) $row['finally'] = self::body($statement->finalizer);
				$statements[] = $row;
			}
			elseif ($statement instanceof ast_block_statement) {
				$statements[] = ['kind' => 'block', 'body' => self::body($statement->body)];
			}
			elseif ($statement instanceof ast_branch_statement) {
				$statements[] = ($statement instanceof ast_switch_statement ? ['kind' => 'switch', 'subject' => self::value($statement->subject)] : ['kind' => 'if']) + [ 'arms' => array_map(static fn(ast_branch_arm $arm): array => [
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
				// Inspection also accepts a nested block directly. Recover its enclosing return destination.
				$owner = $program;
				while (!($owner instanceof ast_function_body)) $owner = $owner->parent;
				$code = $statement->conversion();
				$statements[] = ['kind' => 'return', 'conversion' => $owner->parent->is_implicit_entry
					? exit_conversion::from($code)->name : value_conversion::name($code),
					'value' => self::value($statement->value)];
			}
			elseif ($statement instanceof ast_return_statement) {
				$statements[] = ['kind' => 'return', 'value' => null];
			}
			elseif ($statement instanceof ast_field_assignment) {
				$row = ['kind' => 'field_write', 'field' => $statement->target instanceof ast_name_expression ? $statement->target->name : $statement->target->field, 'value' => self::value($statement->value)];
				if ($statement->target instanceof ast_qualified_data) $row['target'] = self::value($statement->target);
				$statements[] = $row;
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
			elseif ($statement instanceof ast_discard_statement) {
				$statements[] = ['kind' => 'discard', 'value' => self::value($statement->value)];
			}
			elseif ($statement instanceof ast_base_statement) {
				$statements[] = ['kind' => 'base_initialization', 'value' => self::value($statement->value)];
			}
			elseif ($statement instanceof ast_call_statement) {
				$call = $statement->value;
				$signature = $call->has_direct_signature() ? $statement->function_def() : null;
				$statements[] = ['kind' => 'call', 'target' => $signature?->name() ?? $call->target_name(),
					'return_type' => ($signature?->return_type_def() ?? $call->type_def())->name,
					'arguments' => array_map(self::value(...), $signature instanceof runtime_function ? $call->runtime_operands() : $call->arguments)];
			}
		}
		return ['statements' => $statements,
			'work' => $program instanceof ast_function_body ? ['statements' => $program->statement_visits, 'expressions' => $program->expression_visits,
				'name_lookups' => $program->name_lookups] : null];
	}

	/** @return mixed[] Declaration/write facts shared by body inspection and for headers. */
	private static function binding(ast_local_declaration|ast_storage_write|ast_call_statement $statement): array /** array<string,mixed> */
	{
		if ($statement instanceof ast_call_statement) return ['kind' => 'call', 'value' => self::value($statement->value)];
		if ($statement instanceof ast_local_declaration) {
			return ['kind' => 'local', 'id' => $statement->id,
				'type' => $statement->type_def()->name, 'initializer' => self::value($statement->initializer)];
		}
		if ($statement instanceof ast_property_update || $statement instanceof ast_index_update) {
			return ['kind' => $statement instanceof ast_index_update ? 'indexed_update' : 'property_update', 'target' => self::value($statement->target),
				'operator' => $statement->operator->name, 'postfix' => $statement->postfix];
		}
		if ($statement instanceof ast_reset_statement) {
			return ['kind' => $statement::OPERATION, 'id' => $statement->local_def()->storage_id()];
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
		if ($statement->calculates_value()) {
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
		if ($value instanceof ast_throw_expression) {
			return ['kind' => 'throw', 'value' => self::value($value->operand)];
		}
		$row = ['type' => $value->type_def()->name];
		if ($value instanceof ast_await_expression) $row += ['kind' => 'await', 'operand' => self::value($value->operand)];
		self::write_scalar($row, 'constant', $value->constant());
		$row['constant_known'] = $value->constant_known();
		if ($value instanceof ast_construction_expression) {
			$row['arguments'] = array_map(self::value(...), $value->arguments);
		}
		if ($value instanceof ast_keyed_collection_expression) {
			$entries = [];
			foreach ($value->entries as $entry) $entries[] = ['key' => self::value($entry->key), 'value' => self::value($entry->value)];
			$row['entries'] = $entries;
		}
		if ($value instanceof ast_collection_expression) {
			$row['elements'] = array_map(self::value(...), $value->elements);
		}
		if ($value instanceof ast_interpolated_string) {
			$row['parts'] = array_map(self::value(...), $value->parts);
		}
		if ($value->parent instanceof ast_interpolated_string && !($value instanceof ast_interpolation_text)) {
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
		if ($value instanceof ast_update_expression) {
			$row += ['kind' => $value->update->postfix ? 'postfix_update' : 'prefix_update',
				'id' => $value->update->local_def()->storage_id(),
				'operator' => $value->update->operator->name];
		}
		if ($value instanceof ast_condition_assignment) {
			$row += ['kind' => 'condition_assignment', 'target' => $value->target->local_def()->storage_id(),
				'conversion' => value_conversion::name($value->conversion), 'value' => self::value($value->value)];
		}
		if ($value instanceof ast_ternary_expression) {
			$row += ['condition' => self::value($value->condition), 'when_true' => $value->when_true === null ? null : self::value($value->when_true), 'when_false' => self::value($value->when_false)];
		}
		if ($value instanceof ast_match_expression) {
			$row['subject'] = self::value($value->subject);
			$row['arms'] = array_map(static fn(ast_match_arm $arm): array => (count($arm->labels) > 1
				? ['labels' => array_map(self::value(...), $arm->labels)]
				: ['label' => $arm->labels === [] ? null : self::value($arm->labels[0])]) + ['result' => self::value($arm->result)], $value->arms);
		}
		if ($value instanceof ast_object_access_expression) {
			$row['operator'] = $value->test ? 'instanceof' : 'object_cast';
			$row['target'] = $value->target->spelling();
			$row['dynamic_check'] = $value->dynamic_check();
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
		if ($value instanceof ast_nullsafe_member) {
			$row['operand'] = self::value($value->operand);
			if ($value->has_suffix()) {
				$row['ordinary_suffix'] = true;
				$row['guarded_end'] = $value->guarded_member->end;
			}
		}
		if ($value instanceof ast_field_expression) {
			$row['field'] = $value->field;
			$row['receiver'] = self::value($value->receiver);
		}
		if ($value instanceof ast_index_expression) {
			$row['receiver'] = self::value($value->receiver);
			$row['index'] = self::value($value->index);
		}
		if ($value instanceof ast_comparison_chain) $row['links'] = array_map(self::value(...), $value->links);

		if ($value instanceof ast_key_comparison && $value->tests_key()) {
			$row['operator'] = $value->operator === binary_operator::identical ? 'key_missing' : 'key_present';
			$row['receiver'] = self::value($value->left->receiver);
			$row['index'] = self::value($value->left->index);
			return $row;
		}
		if ($value instanceof ast_binary_expression) {
			if (!$value->operator->lazy()) {
				$row['execution_type'] = $value->execution_type()->name;
				$row['left_conversion'] = value_conversion::name($value->left_conversion);
				$row['right_conversion'] = value_conversion::name($value->right_conversion);
			}
			$row['operator'] = $value->operator->name;
			if (!($value instanceof ast_continued_comparison)) $row['left'] = self::value($value->left);
			$row['right'] = self::value($value->right);
		}
		if ($value instanceof ast_lambda_expression) {
			$row['body'] = $value->body instanceof ast_block ? self::body($value->body) : self::value($value->body);
			if ($value->type_def()->borrows_receiver) $row['receiver_capture'] = true;
			if (count($value->captures) === 1) $row['capture'] = self::value($value->captures[0]->initializer);
			elseif ($value->captures !== []) $row['captures'] = array_map(static fn(ast_closure_capture $capture) => self::value($capture->initializer), $value->captures);
			foreach ($value->captures as $capture) {
				if ($capture->by_reference) $row['reference_captures'][] = $capture->short_name;
			}
		}
		if ($value instanceof ast_callable_reference) $row['target'] = $value->name;
		if (isset($value->named)) {
			$row['labels'] = $value->named->labels;
			$row['parameter_slots'] = $value->named->slots;
		}
		if ($value instanceof ast_call_expression) {
			$row['target'] = $value->target_name();
			if ($value->ancestor_dispatch) $row['dispatch'] = 'ancestor';
			$row['arguments'] = array_map(self::value(...), $value->runtime_binding() !== null
					? $value->runtime_operands() : $value->arguments);
		}
		if ($value instanceof ast_string_expression) {
			$row['bytes_hex'] = bin2hex($value->bytes());
		}
		if ($value instanceof ast_name_expression) {
			if ($value instanceof ast_qualified_data) $row['receiver'] = self::value($value->receiver);
			$declaration = $value->declaration();
			$row['binding'] = $declaration instanceof ast_local_binding
				? $declaration->storage_id() : $declaration->symbol_key();
		}
		return $row;
	}
	/** Write the JSON field directly; its numeric strings must round-trip exactly. @param mixed[] $row */
	private static function write_scalar(array &$row, string $key,
		int|float|bool|null|constant_evaluation $value /** int64|float64|bool|null|constant_evaluation */): void
	{
		if (is_int($value)) $row[$key] = (string)$value;
		elseif (is_float($value)) $row[$key] = type_float64::instance()->cpp_constant($value);
		elseif (is_bool($value)) $row[$key] = $value;
		else $row[$key] = null;
	}
}

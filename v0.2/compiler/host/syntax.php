<?php

declare(strict_types=1);
namespace scpp\compiler;

/** CLI inspection owns presentation; syntax records do not depend on host serialization. */
final class Syntax_Inspection
{
	/**
	 * Inspect executable statements separately from source-owned function definitions.
	 *
	 * @return mixed[]
	 */
	public static function file(ast_file_node $file): array /** array<string,mixed> */
	{
		$rows = [];
		foreach (($file->main()?->body()->statements ?? []) as $statement) {
			$rows[] = self::statement($statement);
		}
		$functions = [];
		foreach ($file->functions() as $function)
		{
			$body = array_map(self::statement(...), $function->optional_body()?->statements ?? []);
			$functions[] = ['name' => self::name($function, $function->short_name, $function->name_position()),
				'parameters' => array_map(static fn(ast_parameter_declaration $parameter): array => [
					'name' => $parameter->short_name, 'type' => $parameter->type_reference->spelling(),
					'default' => $parameter->default_value?->source_text(), 'passing' => $parameter->mode->name,
				], $function->parameters),
				'return_type' => self::name($function, $function->return_type_reference?->spelling(), $function->return_type_position()),
				'qualified_name' => $function->name(), 'namespace' => $function->namespace,
				'body' => $body, 'header' => ['start' => $function->byte_start(), 'end' => $function->lexical_root()->byte_at($function->header_end)],
				'body_span' => $function->optional_body() === null ? null : self::span($function->body())];
			if ($function->is_private) $functions[array_key_last($functions)]['visibility'] = 'private';
			if ($function->is_protected) $functions[array_key_last($functions)]['visibility'] = 'protected';
		}
		$result = ['namespace' => $file->scope->namespace, 'flavor' => $file->tokens->source->flavor->value, 'statements' => $rows, 'functions' => $functions];
		foreach ($file->declaration_nodes as $node) {
			if ($node instanceof ast_enum_declaration) {
				$result['enums'][] = ['name' => $node->name(), 'backing' => $node->backing?->spelling(),
					'cases' => array_map(static fn(ast_enum_case $case): string => $case->initializer->source_text(), $node->cases)];
			}
			if ($node instanceof ast_record_declaration) {
				$category = match ($node->kind) {
					record_kind::class_type => 'classes',
					record_kind::interface_type => 'interfaces',
					default => 'structs',
				};
				$result[$category][] = ['name' => $node->name(), 'fields' => array_map(static fn(ast_instance_field $field): string =>
					$field->type_reference->spelling(), $node->fields), 'methods' => array_keys($node->methods)];
				if ($node->is_final) $result[$category][array_key_last($result[$category])]['final'] = true;
				if ($node->interface_references !== []) {
					$relation = $node->kind === record_kind::interface_type ? 'extends' : 'implements';
					$names = array_map(static fn(ast_type_reference $type): string => $type->spelling(), $node->interface_references);
					$result[$category][array_key_last($result[$category])][$relation] = count($names) === 1 ? $names[0] : $names;
				}
				if ($node->base_reference !== null) $result[$category][array_key_last($result[$category])]['base'] = $node->base_reference->spelling();
			}
		}
		$result['imports'] = array_map(static fn(ast_import $import): array => [
			'kind' => $import->kind->name, 'target' => $import->target,
			'local_name' => $import->local_name, 'span' => self::span($import),
		], $file->imports);
		$result['globals'] = array_map(static fn(ast_global_declaration $global): array => [
			'name' => $global->short_name, 'qualified_name' => $global->name(), 'type' => $global->type_reference?->spelling(),
			'initializer' => self::expression($global->initializer), 'span' => self::span($global),
		], $file->globals());
		$result['constants'] = array_map(static fn(ast_constant_declaration $constant): array => [
			'name' => $constant->short_name, 'qualified_name' => $constant->name(), 'type' => $constant->type_reference?->spelling(),
			'initializer' => self::expression($constant->initializer), 'span' => self::span($constant),
		], $file->constants());
		if ($file->diagnostics !== []) {
			$result['diagnostics'] = array_map(static fn(diagnostic $error): string => $error->getMessage(),
				$file->diagnostics);
		}
		return $result;
	}

	/** @return mixed[] */
	private static function span(ast_node $span): array /** array{start:uint32,end:uint32} */
	{
		return ['start' => $span->byte_start(), 'end' => $span->byte_end()];
	}

	/**
	 * $position: token position.
	 *
	 * @return mixed[]
	 */
	private static function name(ast_node $owner, string $name, int $position /** uint32 */, ?int $end /** uint32 */ = null): array /** array{text:string,span:array{start:uint32,end:uint32}} */
	{
		$token = $owner->lexical_root()->tokens->tokens[$position];
		$last = $owner->lexical_root()->tokens->tokens[($end ?? ($position + 1)) - 1];
		return ['text' => $name, 'span' => ['start' => $token->offset, 'end' => $last->offset + $last->length]];
	}

	/**
	 * Serialize the supported expression shape without consuming prepared facts.
	 *
	 * Return: tagged expression JSON fields.
	 *
	 * @return mixed[]
	 */
	private static function expression(ast_expression $expression): array /** array<string,mixed> */
	{
		if ($expression instanceof ast_await_expression) return ['kind' => 'await', 'operand' => self::expression($expression->operand), 'span' => self::span($expression)];
		if ($expression instanceof ast_throw_expression) {
			return ['kind' => 'throw', 'span' => self::span($expression), 'value' => self::expression($expression->operand)];
		}
		if ($expression instanceof ast_type_marker) return ['kind' => 'type_marker', 'name' => $expression->name];
		if ($expression instanceof ast_this_expression) return ['kind' => 'this'];
		if ($expression instanceof ast_base_initialization) {
			return ['kind' => 'base_initialization', 'explicit' => $expression->explicit, 'target' => $expression->target?->spelling(),
				'arguments' => array_map(self::expression(...), $expression->arguments)];
		}
		if ($expression instanceof ast_construction_expression) {
			$row = $expression instanceof ast_new_expression ? ['kind' => 'new', 'target' => $expression->target->name] : ['kind' => 'struct_default'];
			if ($expression->arguments !== []) $row['arguments'] = array_map(self::expression(...), $expression->arguments);
			return $row;
		}
		if ($expression instanceof ast_called_type_name) {
			return ['kind' => 'late_static_data', 'name' => $expression->name, 'span' => self::span($expression)];
		}
		if ($expression instanceof ast_qualified_data) {
			return ['kind' => 'qualified_static_data', 'receiver' => self::expression($expression->receiver),
				'name' => $expression->name, 'span' => self::span($expression)];
		}
		if ($expression instanceof ast_field_expression) {
			return ['kind' => 'field', 'receiver' => self::expression($expression->receiver), 'name' => $expression->field];
		}
		if ($expression instanceof ast_keyed_collection_expression) {
			$entries = [];
			foreach ($expression->entries as $entry) $entries[] = ['key' => self::expression($entry->key), 'value' => self::expression($entry->value)];
			return ['kind' => 'keyed_collection', 'entries' => $entries];
		}
		if ($expression instanceof ast_collection_expression) {
			return $expression->elements === [] ? ['kind' => 'empty_collection']
				: ['kind' => 'collection', 'elements' => array_map(self::expression(...), $expression->elements)];
		}
		if ($expression instanceof ast_index_expression) {
			return ['kind' => 'index', 'receiver' => self::expression($expression->receiver), 'index' => self::expression($expression->index)];
		}
		if ($expression instanceof ast_interpolated_string) {
			return ['kind' => 'interpolated_string', 'parts' => array_map(self::expression(...), $expression->parts),
				'span' => self::span($expression)];
		}
		if ($expression instanceof ast_cast_expression) {
			return ['kind' => 'cast', 'type_name' => $expression->type_reference->spelling(),
				'operand' => self::expression($expression->operand), 'span' => self::span($expression)];
		}
		if ($expression instanceof ast_unary_expression) {
			return ['kind' => 'unary', 'operator' => $expression->operator->name,
				'operand' => self::expression($expression->operand), 'span' => self::span($expression)];
		}
		if ($expression instanceof ast_update_expression) {
			$update = $expression->update;
			$name_position = $update->postfix ? $update->start : $update->start + 1;
			return ['kind' => $update->postfix ? 'postfix_update' : 'prefix_update',
				'name' => self::name($update, $update->name, $name_position),
				'operator' => $update->operator->name, 'span' => self::span($expression)];
		}
		if ($expression instanceof ast_condition_assignment) {
			return ['kind' => 'condition_assignment', 'target' => $expression->target->name, 'value' => self::expression($expression->value),
				'span' => self::span($expression)];
		}
		if ($expression instanceof ast_ternary_expression) {
			return ['kind' => 'ternary', 'condition' => self::expression($expression->condition),
				'when_true' => self::expression($expression->when_true), 'when_false' => self::expression($expression->when_false),
				'span' => self::span($expression)];
		}
		if ($expression instanceof ast_match_expression) {
			return ['kind' => 'match', 'subject' => self::expression($expression->subject), 'arms' => array_map(static fn(ast_match_arm $arm): array => (count($arm->labels) > 1
				? ['labels' => array_map(self::expression(...), $arm->labels)]
				: ['label' => $arm->labels === [] ? null : self::expression($arm->labels[0])]) + ['result' => self::expression($arm->result)], $expression->arms), 'span' => self::span($expression)];
		}
		if ($expression instanceof ast_object_access_expression) {
			return ['kind' => $expression->test ? 'instanceof' : 'object_cast', 'target' => $expression->target->spelling(),
				'operand' => self::expression($expression->operand), 'span' => self::span($expression)];
		}
		if ($expression instanceof ast_ownership_expression) {
			return ['kind' => $expression->lock ? 'lock' : 'weak', 'operand' => self::expression($expression->operand), 'span' => self::span($expression)];
		}
		if (($expression instanceof ast_not_expression) || ($expression instanceof ast_isset_expression)) {
			return ['kind' => $expression instanceof ast_isset_expression ? 'isset' : 'logical_not', 'operand' => self::expression($expression->operand),
				'span' => self::span($expression)];
		}
		if ($expression instanceof ast_comparison_chain) {
			return ['kind' => 'comparison_chain', 'first' => self::expression($expression->links[0]->left),
				'links' => array_map(static fn(ast_binary_expression $link): array =>
					['operator' => $link->operator->name, 'right' => self::expression($link->right)], $expression->links),
				'span' => self::span($expression)];
		}
		if ($expression instanceof ast_binary_expression) {
			return ['kind' => 'binary', 'operator' => $expression->operator->name,
				'left' => self::expression($expression->left), 'right' => self::expression($expression->right),
				'span' => self::span($expression)];
		}
		if ($expression instanceof ast_lambda_expression) {
			return ['kind' => 'lambda', 'parameters' => array_map(static fn(ast_parameter_declaration $parameter): array =>
				['name' => $parameter->short_name, 'type' => $parameter->type_reference->spelling()], $expression->parameters),
				'result' => $expression->result->spelling(), 'body' => $expression->body instanceof ast_block
					? array_map(self::statement(...), $expression->body->statements) : self::expression($expression->body), 'span' => self::span($expression)];
		}
		if ($expression instanceof ast_callable_reference) {
			return ['kind' => 'callable_reference', 'name' => $expression->name, 'span' => self::span($expression)];
		}
		if ($expression instanceof ast_named_call) {
			return ['kind' => 'named_call', 'name' => $expression->name, 'labels' => $expression->labels,
				'arguments' => array_map(self::expression(...), $expression->arguments), 'span' => self::span($expression)];
		}
		if ($expression instanceof ast_call_expression) {
			if ($expression->qualifier === call_qualifier::local) {
				return ['kind' => 'local_call', 'name' => $expression->name, 'arguments' => array_map(self::expression(...), $expression->arguments),
					'span' => self::span($expression)];
			}
			if ($expression->qualifier === call_qualifier::lexical_parent || $expression->qualifier === call_qualifier::called_type) {
				return ['kind' => $expression->qualifier === call_qualifier::called_type ? 'late_static_call' : 'parent_call', 'name' => $expression->name,
					'arguments' => array_map(self::expression(...), $expression->arguments), 'span' => self::span($expression)];
			}
			if ($expression->receiver !== null) {
				return ['kind' => 'member_or_qualified_call', 'name' => $expression->name,
					'receiver' => self::expression($expression->receiver),
					'arguments' => array_map(self::expression(...), $expression->arguments), 'span' => self::span($expression)];
			}
			$name_end = $expression->start;
			while ($expression->lexical_root()->tokens->tokens[$name_end]->kind !== token_kind::lparen) {
				$name_end++;
			}
			return ['kind' => 'call', 'name' => self::name($expression, $expression->name, $expression->start,
				$name_end), 'arguments' => array_map(self::expression(...), $expression->arguments),
				'span' => self::span($expression)];
		}
		if ($expression instanceof ast_string_expression) {
			return ['kind' => 'string', 'spelling_hex' => bin2hex($expression->source_text()), 'span' => self::span($expression)];
		}
		if ($expression instanceof ast_keyword_expression) {
			return ['kind' => 'keyword_literal', 'spelling' => $expression->word, 'span' => self::span($expression)];
		}
		if ($expression instanceof ast_float_expression) {
			return ['kind' => 'floating', 'spelling' => $expression->source_text(), 'span' => self::span($expression)];
		}
		if ($expression instanceof ast_integer_expression) {
			return ['kind' => 'integer', 'spelling' => $expression->source_text(), 'span' => self::span($expression)];
		}
		if ($expression instanceof ast_name_expression) {
			return ['kind' => 'name', 'name' => ['text' => $expression->name, 'span' => self::span($expression)],
				'span' => self::span($expression)];
		}
		throw new \LogicException('Unhandled expression inspection');
	}

	/**
	 * Serialize each supported statement and its syntax children for inspection.
	 *
	 * Return: tagged statement JSON fields.
	 *
	 * @return mixed[]
	 */
	private static function statement(ast_statement $statement): array /** array<string,mixed> */
	{
		if ($statement instanceof ast_loop_transfer) {
			return ['kind' => $statement instanceof ast_break_statement ? 'break' : 'continue', 'span' => self::span($statement)] + ($statement->numbered() ? ['depth' => $statement->depth()] : []);
		}
		if ($statement instanceof ast_foreach_statement) {
			return ['kind' => 'foreach', 'span' => self::span($statement), 'source' => self::expression($statement->source_value),
				'key' => $statement->key?->short_name, 'value' => $statement->value->short_name,
				'body' => array_map(self::statement(...), $statement->body->statements)];
		}
		if ($statement instanceof ast_loop_statement) {
			if ($statement instanceof ast_for_statement) {
				$row = ['kind' => 'for', 'initializers' => array_map(self::statement(...), $statement->initializers),
					'conditions' => array_map(self::expression(...), $statement->conditions),
					'updates' => array_map(self::statement(...), $statement->updates), 'body' => array_map(self::statement(...), $statement->body->statements), 'span' => self::span($statement)];
			} else $row = ['kind' => $statement instanceof ast_do_while_statement ? 'do_while' : 'while',
				'condition' => self::expression($statement->condition), 'span' => self::span($statement),
				'body' => array_map(self::statement(...), $statement->body->statements)];
			return $row;
		}
		if ($statement instanceof ast_rethrow_statement) return ['kind' => 'rethrow', 'span' => self::span($statement)];
		if ($statement instanceof ast_throw_statement) {
			return ['kind' => 'throw', 'span' => self::span($statement), 'value' => self::expression($statement->operation->operand)];
		}
		if ($statement instanceof ast_try_statement) {
			$row = ['kind' => 'try', 'span' => self::span($statement),
				'body' => array_map(self::statement(...), $statement->body->statements)];
			$row['handlers'] = array_map(static fn(ast_catch_clause $handler): array => [
				'catch_type' => $handler->type->spelling(), 'binding' => $handler->binding->short_name,
				'body' => array_map(self::statement(...), $handler->body->statements)], $statement->handlers);
			if ($statement->finalizer !== null) $row['finally'] = array_map(self::statement(...), $statement->finalizer->statements);
			return $row;
		}
		if ($statement instanceof ast_block_statement) {
			return ['kind' => 'block', 'span' => self::span($statement), 'body' => array_map(self::statement(...), $statement->body->statements)];
		}
		if ($statement instanceof ast_branch_statement) {
			return ($statement instanceof ast_switch_statement ? ['kind' => 'switch', 'subject' => self::expression($statement->subject)] : ['kind' => 'if']) + [ 'span' => self::span($statement), 'arms' => array_map(
				static fn(ast_branch_arm $arm): array => ['condition' => $arm->condition === null ? null : self::expression($arm->condition),
					'body' => array_map(self::statement(...), $arm->body->statements)], $statement->arms)];
		}

		if ($statement instanceof ast_yield_statement) {
			return ['kind' => 'yield', 'value' => self::expression($statement->value),
				'key' => $statement->key === null ? null : self::expression($statement->key), 'span' => self::span($statement)];
		}
		if ($statement instanceof ast_echo_statement) {
			return ['kind' => 'echo', 'values' => array_map(self::expression(...), $statement->values), 'span' => self::span($statement)];
		}
		if ($statement instanceof ast_return_statement) {
			return ['kind' => 'return', 'value' => $statement->value === null ? null : self::expression($statement->value),
				'span' => self::span($statement)];
		}
		if ($statement instanceof ast_field_assignment) {
			return ['kind' => 'field_write', 'target' => self::expression($statement->target), 'value' => self::expression($statement->value)];
		}
		if ($statement instanceof ast_index_assignment) {
			return ['kind' => 'keyed_write', 'receiver' => self::expression($statement->receiver),
				'index' => self::expression($statement->index), 'value' => self::expression($statement->value)];
		}
		if ($statement instanceof ast_append_statement) {
			return ['kind' => 'append', 'receiver' => self::expression($statement->receiver), 'value' => self::expression($statement->value)];
		}
		if ($statement instanceof ast_call_statement || $statement instanceof ast_await_statement || $statement instanceof ast_base_statement) {
			return self::expression($statement->value) + ['statement_span' => self::span($statement)];
		}
		if ($statement instanceof ast_local_declaration) {
			return ['kind' => 'local', 'name' => self::name($statement, $statement->short_name, $statement instanceof ast_local_declaration ? $statement->name_position() : $statement->start),
				'type' => $statement->type_reference === null ? null : self::name($statement, $statement->type_reference?->spelling(),
					$statement->type_position()),
				'initializer' => self::expression($statement->initializer), 'span' => self::span($statement)];
		}
		if ($statement instanceof ast_property_update || $statement instanceof ast_index_update) {
			return ['kind' => $statement instanceof ast_index_update ? 'indexed_update' : 'property_update', 'target' => self::expression($statement->target),
				'operator' => $statement->operator->name, 'postfix' => $statement->postfix];
		}
		if ($statement instanceof ast_reset_statement) {
			return ['kind' => $statement::OPERATION, 'name' => self::name($statement, $statement->name, $statement->start + 2),
				'span' => self::span($statement)];
		}
		if ($statement instanceof ast_update_statement) {
			return ['kind' => 'update', 'name' => self::name($statement, $statement->name,
				$statement->start + ($statement->postfix ? 0 : 1)), 'operator' => $statement->operator->name,
				'postfix' => $statement->postfix, 'span' => self::span($statement)];
		}
		if ($statement instanceof ast_property_compound || $statement instanceof ast_index_compound) {
			return ['kind' => $statement instanceof ast_index_compound ? 'indexed_compound' : 'property_compound', 'target' => self::expression($statement->target),
				'operator' => $statement->operator->name, 'value' => self::expression($statement->value), 'span' => self::span($statement)];
		}
		if ($statement instanceof ast_assignment_statement) {
			return ['kind' => 'assignment', 'name' => self::name($statement, $statement->name, $statement instanceof ast_local_declaration ? $statement->name_position() : $statement->start),
				'intent' => $statement->intent->name, 'operator' => $statement->operator?->name, 'value' => self::expression($statement->value),
				'span' => self::span($statement)];
		}
		throw new \LogicException('Unhandled statement inspection');
	}
}

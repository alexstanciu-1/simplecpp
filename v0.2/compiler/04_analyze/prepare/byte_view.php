<?php

declare(strict_types=1);
namespace scpp\compiler;

/** One top-level length acquisition for repeated checked reads of a private value-record field. */
final class Scoped_Byte_View
{
	public static function prepare(ast_function_body $body): void
	{
		foreach ($body->locals as $local) {
			$call = $local->initializer;
			if (!($call instanceof ast_call_expression)) continue;
			$field = $call->arguments[0] ?? null;
			if (!($field instanceof ast_field_expression) || !self::length_read($field)
				|| !($field->receiver instanceof ast_name_expression)
				|| !($field->receiver->local_binding() instanceof ast_local_declaration)
				|| !($field->receiver->type_def() instanceof record_type_definition)
				|| $body->parent->return_type_def() instanceof generator_type_definition) continue;
			$loop_read = false;
			if (self::stable($body, $field, false, $loop_read) && $loop_read) $body->byte_view_length = $local;
			return;
		}
	}

	public static function length_read(ast_field_expression $field): bool
	{
		$call = $field->parent;
		$type = $field->type_def();
		return $type instanceof scoped_byte_read_type && $call instanceof ast_call_expression
			&& $call->cardinality === null && $call->receiver === null && count($call->arguments) === 1 && $call->arguments[0] === $field
			&& ($binding = $call->runtime_binding()) !== null && $type->is_byte_length($binding);
	}

	public static function indexed_read(ast_field_expression $field): bool
	{
		$index = $field->parent;
		return $index instanceof ast_index_expression && $index->receiver === $field
			&& !(($index->parent instanceof ast_index_compound || $index->parent instanceof ast_index_update)
				&& $index->parent->target === $index);
	}

	private static function stable(ast_statement_block $block, ast_field_expression $field, bool $loop, bool &$loop_read): bool
	{
		$owner = $field->receiver->local_binding();
		foreach ($block->references as $ref) {
			if (!$ref->refers_to($owner)) continue;
			$use = $ref->parent;
			if ($use instanceof ast_return_statement) continue;
			if (!($use instanceof ast_field_expression)) return false;
			$parent = $use->parent;
			if ($parent instanceof ast_field_assignment && $parent->target === $use
				&& ($use->field !== $field->field || $parent->end <= $field->start)) continue;
			if ($use === $field) continue;
			if ($use->field === $field->field && self::indexed_read($use)) {
				if ($loop && $use->start > $field->end) $loop_read = true;
				continue;
			}
			return false;
		}
		foreach ($block->statements as $statement) {
			if ($statement instanceof ast_for_statement || $statement instanceof ast_foreach_statement || $statement instanceof ast_try_statement) return false;
			if ($statement instanceof ast_storage_write && $statement->writes_to($owner)) return false;
			if ($statement instanceof ast_block_statement && !self::stable($statement->body, $field, $loop, $loop_read)) return false;
			if ($statement instanceof ast_loop_statement && !self::stable($statement->body, $field, true, $loop_read)) return false;
			if ($statement instanceof ast_branch_statement) {
				foreach ($statement->arms as $arm) {
					if (!self::stable($arm->body, $field, $loop, $loop_read)) return false;
				}
			}
		}
		return true;
	}
}

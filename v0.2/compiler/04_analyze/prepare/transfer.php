<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Body-local proof for publishing a private value immediately before returning its value-record owner. */
final class Last_Use_Transfer
{
	/** Reject noncandidate tails before inspecting references or nested blocks. */
	public static function prepare(ast_function_body $body): void
	{
		$count = count($body->statements);
		$write = $body->statements[$count - 2] ?? null;
		if (!($write instanceof ast_field_assignment) || $write->conversion !== value_conversion::identity
			|| !($write->target instanceof ast_field_expression) || !($write->value instanceof ast_name_expression)
			|| !($write->target->type_def() instanceof private_value_transfer_type)) return;

		$return = $body->statements[$count - 1];
		$receiver = $write->target->receiver;
		if (!($receiver instanceof ast_name_expression) || !($receiver->type_def() instanceof record_type_definition)
			|| !($return instanceof ast_return_statement) || !($return->value instanceof ast_name_expression)
			|| $return->conversion() !== value_conversion::identity) return;
		$result = $receiver->declaration();
		$source = $write->value->declaration();
		if (!($result instanceof ast_local_declaration) || !($source instanceof ast_local_declaration)
			|| $source === $result || !$return->value->refers_to($result)
			|| $source->type_def() !== $write->target->type_def()) return;

		$write->transfer = self::private_uses($body, $write, $source, $result);
	}

	/** Inspect the parser's lexical occurrence indexes only for eligible tails. No facts escape this body. */
	private static function private_uses(ast_statement_block $block, ast_field_assignment $write,
		ast_local_declaration $source, ast_local_declaration $result): bool
	{
		foreach ($block->references as $read) {
			$use = $read->parent;
			if ($read->refers_to($source)) {
				if ($read === $write->value || ($use instanceof ast_append_statement && $use->receiver === $read)) continue;
				if ($use instanceof ast_local_declaration && $use->initializer === $read
					&& $use->initialization() === value_conversion::identity && $use->type_def() === $source->type_def()) continue;
				if ($use instanceof ast_call_expression && $use->receiver === $read && $use->cardinality === null) {
					$binding = $use->function_def();
					if ($binding instanceof runtime_function && !$binding->retains_receiver) continue;
				}
				return false;
			}
			if ($read->refers_to($result)) {
				if ($use instanceof ast_return_statement) continue;
				if ($use instanceof ast_field_expression && $use->parent instanceof ast_field_assignment
					&& $use->parent->target === $use) continue;
				return false;
			}
		}
		foreach ($block->statements as $statement) {
			// Header bindings have their own scope and may refer to existing writable storage.
			if ($statement instanceof ast_for_statement || $statement instanceof ast_foreach_statement || $statement instanceof ast_try_statement) return false;
			if ($statement instanceof ast_loop_statement && !self::private_uses($statement->body, $write, $source, $result)) return false;
			if ($statement instanceof ast_if_statement) {
				foreach ($statement->arms as $arm) {
					if (!self::private_uses($arm->body, $write, $source, $result)) return false;
				}
			}
		}
		return true;
	}
}

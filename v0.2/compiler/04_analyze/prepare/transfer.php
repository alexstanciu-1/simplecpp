<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Prove transfers from private owned storage into a distinct value-record field. */
final class Last_Use_Transfer
{
	/** Only eligible assignments require use checks; no additional expression facts survive preparation. */
	public static function prepare(ast_function_body $body): void
	{
		// Both admitted assignments target a field of a top-level local.
		if ($body->locals === []) return;
		foreach ($body->parent->parameters as $parameter) {
			if ($parameter->mode === passing_mode::value && $parameter->type_def() instanceof private_value_transfer_type) {
				self::parameter_assignments($body);
				break;
			}
		}
		$count = count($body->statements);
		$write = $body->statements[$count - 2] ?? null;
		$result = self::destination($write);
		if ($result === null) return;
		$return = $body->statements[$count - 1];
		if (!($return instanceof ast_return_statement) || !($return->value instanceof ast_name_expression)
			|| $return->conversion() !== value_conversion::identity || !$return->value->refers_to($result)) return;
		$source = $write->value->declaration();
		if ($source instanceof ast_local_declaration && $source !== $result) {
			$write->transfer = self::private_uses($body, $write, $source, $result);
		}
	}

	/** Whole-value identity assignment from named storage to a field of a value-record local. */
	private static function destination(?ast_statement $write): ?ast_local_declaration
	{
		if (!($write instanceof ast_field_assignment) || $write->conversion !== value_conversion::identity
			|| !($write->target instanceof ast_field_expression) || !($write->value instanceof ast_name_expression)
			|| !($write->target->type_def() instanceof private_value_transfer_type)
			|| $write->value->type_def() !== $write->target->type_def()) return null;
		$receiver = $write->target->receiver;
		if (!($receiver instanceof ast_name_expression) || !($receiver->type_def() instanceof record_type_definition)) return null;
		$result = $receiver->declaration();
		return $result instanceof ast_local_declaration ? $result : null;
	}

	/** Consider straight-line parameter publications together, then walk the lexical indexes once. */
	private static function parameter_assignments(ast_function_body $body): void
	{
		if ($body->parent->return_type_def() instanceof generator_type_definition) return;
		$candidates = [];
		foreach ($body->statements as $write) {
			if (self::destination($write) === null) continue;
			$source = $write->value->declaration();
			if ($source instanceof ast_parameter_declaration && $source->mode === passing_mode::value) {
				$candidates[$source->name()] = $write;
			}
		}
		self::exclude_observed_parameters($body, $candidates);
		foreach ($candidates as $write) $write->transfer = true;
	}

	/** The field assignment must be the parameter's only use. Unknown header bindings keep the copy. @param ast_field_assignment[] $candidates */
	private static function exclude_observed_parameters(ast_statement_block $block, array &$candidates /** array<string,ast_field_assignment> */): void
	{
		if ($candidates === []) return;
		foreach ($block->references as $read) {
			$write = $candidates[$read->name] ?? null;
			if ($write !== null && $read !== $write->value && $read->refers_to($write->value->local_def())) {
				unset($candidates[$read->name]);
			}
		}
		foreach ($block->statements as $statement) {
			if ($candidates === []) return;
			if ($statement instanceof ast_for_statement || $statement instanceof ast_foreach_statement) {
				$candidates = [];
				return;
			}
			if ($statement instanceof ast_storage_write && isset($candidates[$statement->name])
				&& $statement->writes_to($candidates[$statement->name]->value->local_def())) unset($candidates[$statement->name]);
			if ($statement instanceof ast_loop_statement) self::exclude_observed_parameters($statement->body, $candidates);
			if ($statement instanceof ast_if_statement) {
				foreach ($statement->arms as $arm) self::exclude_observed_parameters($arm->body, $candidates);
			}
		}
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
				if ($use instanceof ast_field_expression && $use->type_def() instanceof scoped_byte_read_type
					&& (Scoped_Byte_View::indexed_read($use) || Scoped_Byte_View::length_read($use))) continue;
				if ($use instanceof ast_field_expression && $use->parent instanceof ast_field_assignment
					&& $use->parent->target === $use) continue;
				return false;
			}
		}
		foreach ($block->statements as $statement) {
			// Header bindings have their own scope and may refer to existing writable storage.
			if ($statement instanceof ast_for_statement || $statement instanceof ast_foreach_statement) return false;
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

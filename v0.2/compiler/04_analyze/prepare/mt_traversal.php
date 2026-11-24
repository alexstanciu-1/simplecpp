<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Enumerate only audited executable syntax children. Unknown forms fail closed. */
final class Mt_Traversal
{
	/** Return executable children in source order. Null marks unaudited syntax. @return ast_node[]|null */
	public static function children(ast_node $node): ?array /** list<ast_node>|null */
	{
		return match ($node::class) {
			ast_function_body::class, ast_block::class => $node->statements,
			ast_block_statement::class => [$node->body],
			ast_while_statement::class, ast_do_while_statement::class => [$node->condition, $node->body],
			ast_update_statement::class => [],
			ast_for_statement::class => [...$node->initializers, ...$node->conditions, ...$node->updates, $node->body],
			ast_call_expression::class => $node->arguments,
			ast_if_statement::class => $node->arms,
			ast_if_arm::class => $node->condition === null ? [$node->body] : [$node->condition, $node->body],
			ast_integer_expression::class, ast_name_expression::class, ast_float_expression::class,
			ast_keyword_expression::class, ast_break_statement::class, ast_continue_statement::class => [],
			ast_local_declaration::class => [$node->initializer],
			ast_assignment_statement::class, ast_call_statement::class => [$node->value],
			ast_return_statement::class => $node->value === null ? [] : [$node->value],
			ast_index_expression::class => [$node->receiver, $node->index],
			ast_index_assignment::class => [$node->receiver, $node->index, $node->value],
			ast_field_expression::class => [$node->receiver],
			ast_field_assignment::class, ast_property_compound::class => [$node->target, $node->value],
			ast_not_expression::class => [$node->operand],
			ast_binary_expression::class => [$node->left, $node->right],
			default => null,
		};
	}
}

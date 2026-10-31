<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Recognizes JS-flavor let declarations and writes, producing the same AST shapes as the other flavors.
 *
 * JS-flavor syntax:
 * ```text
 * let value: int = 3;
 * function ping(): void {}
 * ```
 */
final class Js_Grammar implements statement_grammar
{
	public function function_start(Parser $parser): bool
	{
		return $parser->cursor->keyword('function');
	}

	public function function_header(Parser $parser): ast_function_declaration
	{
		return $parser->named_function_header();
	}

	/** Let introduces a local with an optional type; bare writes require an existing binding. */
	public function statement(Parser $parser): ast_statement
	{
		$cursor = $parser->cursor;
		$start = $cursor->position();
		if (!$cursor->keyword('let')) {
			return $parser->assignment($start, $cursor->name(token_kind::identifier), assignment_intent::existing_binding);
		}
		$cursor->take_keyword('let');
		$name = $cursor->name(token_kind::identifier);
		$type = null;
		if ($cursor->is(token_kind::colon)) {
			$cursor->take(token_kind::colon, ':');
			$type = $parser->named_type();
		}
		return $parser->declaration($start, $name, $type);
	}
}

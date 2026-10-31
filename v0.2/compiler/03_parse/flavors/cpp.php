<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Recognizes the C++ flavor of the shared declaration and assignment syntax.
 *
 * C++-flavor syntax:
 * ```text
 * int value = 3;
 * void ping() {}
 * ```
 */
final class Cpp_Grammar implements statement_grammar
{
	public function function_start(Parser $parser): bool
	{
		return $parser->cursor->keyword('void');
	}

	/** C++ flavor places the explicit return type before the function name. */
	public function function_header(Parser $parser): ast_function_declaration
	{
		$start = $parser->cursor->position();
		$type = $parser->return_type();
		$name = $parser->cursor->name(token_kind::identifier);
		$parser->empty_parameters();
		return new ast_function_declaration($name, $type,
			$start, $parser->cursor->position());
	}

	/** A leading type or auto declares a local; a simple-name write requires an existing binding. */
	public function statement(Parser $parser): ast_statement
	{
		$cursor = $parser->cursor;
		$start = $cursor->position();
		if ($cursor->keyword('auto')) {
			$cursor->take_keyword('auto');
			return $parser->declaration($start, $cursor->name(token_kind::identifier), null);
		}
		$first = $cursor->name(token_kind::identifier);
		if ($cursor->is(token_kind::assign)) {
			return $parser->assignment($start, $first, assignment_intent::existing_binding);
		}
		return $parser->declaration($start, $cursor->name(token_kind::identifier), $first);
	}
}

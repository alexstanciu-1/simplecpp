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
		return ($parser->cursor->is(token_kind::identifier) || $parser->cursor->keyword('void')
			|| ($parser->cursor->is(token_kind::keyword) && type_registry::resolve($parser->cursor->tokens->text_at($parser->cursor->position())) !== null))
			&& ($parser->cursor->tokens->tokens[$parser->cursor->position() + 2]->kind ?? null) === token_kind::lparen
			&& ($parser->cursor->next_is(token_kind::identifier));
	}

	/** C++ flavor places the explicit return type before the function name. */
	public function function_header(Parser $parser): ast_function_declaration
	{
		$start = $parser->cursor->position();
		$type = $parser->return_type();
		$name = $parser->cursor->name(token_kind::identifier);
		$parameters = $parser->parameters();
		return new ast_function_declaration($name, $type,
			$start, $parser->cursor->position(), false, $parser->source_namespace(), $parameters);
	}

	/** A leading type or auto declares a local; a simple-name write requires an existing binding. */
	public function statement(Parser $parser, Body_Collection $collector): ast_statement
	{
		$cursor = $parser->cursor;
		$start = $cursor->position();
		if ($cursor->keyword('auto')) {
			$cursor->take_keyword('auto');
			return $parser->declaration($start, $cursor->name(token_kind::identifier), null);
		}
		$first = $parser->named_type();
		if ($cursor->is(token_kind::assign) || ($parser->compound_operator() !== null)) {
			return $parser->assignment($start, $first, assignment_intent::existing_binding);
		}
		return $parser->declaration($start, $cursor->name(token_kind::identifier), $first);
	}
}

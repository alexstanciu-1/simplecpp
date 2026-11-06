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
	/** Look past a balanced type-argument list without allocating AST nodes or changing the cursor. */
	public function function_start(Parser $parser): bool
	{
		$cursor = $parser->cursor;
		if (!$cursor->is(token_kind::identifier) && !$cursor->keyword('void')
			&& !($cursor->is(token_kind::keyword) && type_registry::resolve($cursor->tokens->text_at($cursor->position())) !== null)) {
			return false;
		}
		$position = $cursor->position() + 1;
		$tokens = $cursor->tokens->tokens;
		if (($tokens[$position]->kind ?? null) === token_kind::less) {
			$depth = 0;
			do {
				$kind = $tokens[$position]->kind ?? null;
				if ($kind === token_kind::less) {
					$depth++;
				}
				elseif ($kind === token_kind::greater) {
					$depth--;
				}
				elseif (!in_array($kind, [token_kind::identifier, token_kind::keyword, token_kind::comma], true)) {
					return false;
				}
				$position++;
			} while ($depth > 0);
		}
		return (($tokens[$position]->kind ?? null) === token_kind::identifier)
			&& (($tokens[$position + 1]->kind ?? null) === token_kind::lparen);
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
			return $parser->assignment($start, $first->name, assignment_intent::existing_binding);
		}
		return $parser->declaration($start, $cursor->name(token_kind::identifier), $first);
	}
}

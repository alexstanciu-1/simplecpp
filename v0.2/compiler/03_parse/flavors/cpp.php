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
		if (!$cursor->is(token_kind::identifier) && !$cursor->keyword('void') && !$cursor->keyword('weak')
			&& !$cursor->is(token_kind::question)
			&& !($cursor->is(token_kind::keyword) && type_registry::resolve($cursor->tokens->text_at($cursor->position())) !== null)) {
			return false;
		}
		$position = $cursor->position() + ($cursor->is(token_kind::question) ? 2 : 1);
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
		if (($tokens[$position]->kind ?? null) !== token_kind::identifier || ($tokens[$position + 1]->kind ?? null) !== token_kind::lparen) return false;
		// Type name(arguments); is a local. A function definition has a braced body after its parameter list.
		$depth = 1;
		for ($position += 2; isset($tokens[$position]) && $depth > 0; ++$position) {
			if ($tokens[$position]->kind === token_kind::lparen) ++$depth;
			elseif ($tokens[$position]->kind === token_kind::rparen) --$depth;
		}
		return ($tokens[$position]->kind ?? null) === token_kind::lbrace;
	}

	/** C++ flavor places the explicit return type before the function name. */
	public function function_header(Parser $parser, bool $static = false, bool $private = false): ast_function_declaration
	{
		$start = $parser->cursor->position();
		$type = $parser->return_type();
		$name = $parser->cursor->name(token_kind::identifier);
		$parameters = $parser->parameters();
		return new ast_function_declaration($name, $type,
			$start, $parser->cursor->position(), false, $parser->source_namespace(), $parameters, $static, $private);
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

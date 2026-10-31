<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Recognizes PHP-flavor declarations and writes while preserving their unresolved binding intent.
 *
 * PHP-flavor syntax:
 * ```text
 * $value int = 3;
 * function ping(): void {}
 * ```
 */
final class Php_Grammar implements statement_grammar
{
	public function function_start(Parser $parser): bool
	{
		return $parser->cursor->keyword('function');
	}

	public function function_header(Parser $parser): ast_function_declaration
	{
		return $parser->named_function_header();
	}

	/** Bare writes preserve unresolved binding intent; only an authored type makes a declaration. */
	public function statement(Parser $parser): ast_statement
	{
		$cursor = $parser->cursor;
		$start = $cursor->position();
		$name = $cursor->name(token_kind::variable);
		if ($cursor->is(token_kind::assign)) {
			return $parser->assignment($start, $name, assignment_intent::declare_or_assign);
		}
		return $parser->declaration($start, $name, $parser->named_type());
	}
}

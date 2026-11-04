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

	/** First bare writes declare locals; prior body-local declarations make subsequent writes assignments. */
	public function statement(Parser $parser, Body_Collection $collector): ast_statement
	{
		$cursor = $parser->cursor;
		$start = $cursor->position();
		$name = $cursor->name(token_kind::variable);
		if ($cursor->is(token_kind::assign)) {
			if (!$collector->has_local($name)) {
				return $parser->declaration($start, $name, null);
			}
			return $parser->assignment($start, $name, assignment_intent::declare_or_assign);
		}
		return $parser->declaration($start, $name, $parser->named_type());
	}
}

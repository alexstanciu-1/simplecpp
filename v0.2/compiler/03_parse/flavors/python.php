<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Recognizes Python typed declarations and existing-local writes. */
final class Python_Grammar implements statement_grammar
{
	public function function_start(Parser $parser): bool
	{
		return $parser->cursor->keyword('def');
	}

	public function function_header(Parser $parser, bool $static = false, bool $private = false, bool $protected = false, ?string $alias = null): ast_function_declaration
	{
		return $parser->named_function_header();
	}

	/** A type annotation introduces a local; bare assignment updates one. */
	public function statement(Parser $parser, Body_Collection $collector, ?token_kind $terminator = token_kind::newline): ast_statement
	{
		$cursor = $parser->cursor;
		$start = $cursor->position();
		$name = $cursor->name(token_kind::identifier);
		if (!$cursor->is(token_kind::colon)) {
			return $parser->assignment($start, $name, assignment_intent::existing_binding, $terminator);
		}
		$cursor->take(token_kind::colon, ':');
		$type = $parser->named_type();
		if (!$cursor->is(token_kind::assign)) {
			throw new diagnostic($cursor->tokens->source, $cursor->offset(), 'Python local requires an initializer');
		}
		return $parser->declaration($start, $name, $type, $terminator);
	}
}

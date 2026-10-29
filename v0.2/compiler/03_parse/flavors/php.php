<?php

declare(strict_types=1);
namespace scpp\compiler;

final class Php_Grammar implements statement_grammar
{
	/** Bare writes preserve unresolved binding intent; only an authored type makes a declaration. */
	public function statement(Parser $parser): syntax_statement
	{
		$cursor = $parser->cursor;
		$start = $cursor->offset();
		$name = $cursor->name(token_kind::variable);
		if ($cursor->is(token_kind::assign)) {
			return $parser->assignment($start, $name, assignment_intent::declare_or_assign);
		}
		return $parser->declaration($start, $name, $parser->named_type());
	}
}

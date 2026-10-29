<?php

declare(strict_types=1);
namespace scpp\compiler;

final class Cpp_Grammar implements statement_grammar
{
	/** A leading type or auto declares a local; a simple-name write requires an existing binding. */
	public function statement(Parser $parser): syntax_statement
	{
		$cursor = $parser->cursor;
		$start = $cursor->offset();
		if ($cursor->keyword('auto')) {
			$cursor->take_keyword('auto');
			return $parser->declaration($start, $cursor->name(token_kind::identifier), null);
		}
		$first = $cursor->name(token_kind::identifier);
		if ($cursor->is(token_kind::assign)) {
			return $parser->assignment($start, $first, assignment_intent::existing_binding);
		}
		return $parser->declaration($start, $cursor->name(token_kind::identifier), new named_type_syntax($first));
	}
}

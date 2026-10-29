<?php

declare(strict_types=1);
namespace scpp\compiler;

final class Js_Grammar implements statement_grammar
{
	/** Let introduces a local with an optional type; bare writes require an existing binding. */
	public function statement(Parser $parser): syntax_statement
	{
		$cursor = $parser->cursor;
		$start = $cursor->offset();
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

<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Parse explicit global storage without treating its declaration as an implicit-main statement. */
final class Global_Syntax
{
	/** Flavor spelling is normalized before creating one shared global-declaration node. */
	public static function read(Parser $parser): ast_global_declaration
	{
		$cursor = $parser->cursor;
		$start = $cursor->position();
		$cursor->take_keyword('global');
		$type = null;
		switch ($cursor->tokens->source->flavor)
		{
			case flavor::cpp:
				if ($cursor->keyword('auto')) {
					$cursor->take_keyword('auto');
				}
				else {
					$type = $parser->named_type();
				}
				$name = $cursor->name(token_kind::identifier);
				break;
			case flavor::js:
				$cursor->take_keyword('let');
				$name = $cursor->name(token_kind::identifier);
				if ($cursor->is(token_kind::colon)) {
					$cursor->take(token_kind::colon, ':');
					$type = $parser->named_type();
				}
				break;
			case flavor::php:
				$name = $cursor->name(token_kind::variable);
				if (!$cursor->is(token_kind::assign)) {
					$type = $parser->named_type();
				}
				break;
		}
		$cursor->take(token_kind::assign, '=');
		$literal_start = $cursor->position();
		if ($cursor->is(token_kind::minus) || $cursor->is(token_kind::plus)) {
			$cursor->take($cursor->is(token_kind::minus) ? token_kind::minus : token_kind::plus, 'sign');
		}
		$cursor->take(token_kind::integer, 'integer literal for global initializer');
		$initializer = new ast_integer_expression($literal_start, $cursor->position());
		$end = $cursor->take(token_kind::semicolon, '; (global initializer must be an integer literal)') + 1;
		return new ast_global_declaration($start, $end, $name, $parser->source_namespace(), $type, $initializer);
	}
}

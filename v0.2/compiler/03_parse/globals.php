<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Parse explicit global storage without treating its declaration as an implicit-main statement. */
final class Global_Syntax
{
	/** Flavor spelling is normalized before creating one shared global-declaration node. */
	public static function read(Parser $parser, bool $static = false): ast_global_declaration
	{
		$cursor = $parser->cursor;
		$start = $cursor->position() - ($static ? 1 : 0);
		if (!$static) $cursor->take_keyword('global');
		$type = null;
		switch ($cursor->tokens->source->flavor)
		{
			case flavor::cpp:
				if (!$static && $cursor->keyword('auto')) {
					$cursor->take_keyword('auto');
				}
				else {
					$type = $parser->named_type();
				}
				$name = $cursor->name(token_kind::identifier);
				break;
			case flavor::js:
				if (!$static) $cursor->take_keyword('let');
				$name = $cursor->name(token_kind::identifier);
				if ($static || $cursor->is(token_kind::colon)) {
					$cursor->take(token_kind::colon, ':');
					$type = $parser->named_type();
				}
				break;
			case flavor::php:
				if ($static && !$cursor->is(token_kind::variable)) $type = $parser->named_type();
				$name = $cursor->name(token_kind::variable);
				if ($type === null && ($static || !$cursor->is(token_kind::assign))) {
					$type = $parser->named_type();
				}
				break;
		}
		$cursor->take(token_kind::assign, '=');
		$literal_start = $cursor->position();
		if (($cursor->is(token_kind::minus) || $cursor->is(token_kind::plus)) && !$cursor->next_is(token_kind::floating)) {
			$cursor->take($cursor->is(token_kind::minus) ? token_kind::minus : token_kind::plus, 'sign');
			$cursor->take(token_kind::integer, 'integer literal for global initializer');
			$initializer = new ast_integer_expression($literal_start, $cursor->position());
		}
		else
		{
			$word = $cursor->at_end() ? '' : $cursor->tokens->text_at($cursor->position());
			if (!$cursor->is(token_kind::integer) && !$cursor->is(token_kind::floating)
				&& !(($cursor->is(token_kind::minus) || $cursor->is(token_kind::plus)) && $cursor->next_is(token_kind::floating))
				&& (type_registry::literal($word) === null)) {
				throw new diagnostic($cursor->tokens->source, $cursor->offset(), 'Expected scalar literal for global initializer');
			}
			$initializer = $parser->assignment_value();
		}
		if (!($initializer instanceof ast_integer_expression) && !($initializer instanceof ast_float_expression)
			&& !($initializer instanceof ast_keyword_expression)) {
			throw new diagnostic($cursor->tokens->source, $cursor->offset(), 'Expected scalar literal for global initializer');
		}
		$end = $cursor->take(token_kind::semicolon, '; (global initializer must be a scalar literal)') + 1;
		return new ast_global_declaration($start, $end, $name, $parser->source_namespace(), $type, $initializer);
	}
}

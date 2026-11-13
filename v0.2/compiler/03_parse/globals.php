<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Parse explicit global storage without treating its declaration as an implicit-main statement. */
final class Global_Syntax
{
	/** Flavor spelling is normalized before creating one shared global-declaration node. */
	public static function read(Parser $parser, bool $static = false, bool $private = false): ast_global_declaration
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
		$initializer = $parser->literal_initializer('Expected scalar literal for global initializer');
		if (!$static && $initializer instanceof ast_string_expression) {
			throw new diagnostic($cursor->tokens->source, $cursor->offset(), 'Expected scalar literal for global initializer');
		}
		$end = $cursor->take(token_kind::semicolon, '; (global initializer must be a scalar literal)') + 1;
		return new ast_global_declaration($start, $end, $name, $parser->source_namespace(), $type, $initializer, $private);
	}
}

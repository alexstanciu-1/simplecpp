<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Typed, literal-only constants; namespace publication shares the existing declaration pipeline. */
final class Constant_Syntax
{
	/** Normalize required flavor type/name spelling and retain one initializer node. */
	public static function read(Parser $parser, bool $private = false): ast_constant_declaration
	{
		$cursor = $parser->cursor;
		$start = $cursor->position();
		if ($cursor->keyword('static')) {
			$cursor->take_keyword('static');
			if ($cursor->at_end() || $cursor->tokens->text_at($cursor->position()) !== 'constexpr') {
				throw new diagnostic($cursor->tokens->source, $cursor->offset(), 'Expected constexpr');
			}
			$cursor->take(token_kind::identifier, 'constexpr');
		} else {
			$cursor->take_keyword('const');
		}
		if ($cursor->tokens->source->flavor === flavor::js) {
			$name = $cursor->name(token_kind::identifier);
			$cursor->take(token_kind::colon, ': (explicit constant type)');
			$type = $parser->named_type();
		}
		else {
			$type = $parser->named_type();
			$name = $cursor->name(token_kind::identifier);
		}
		$cursor->take(token_kind::assign, '=');
		$initializer = $parser->literal_initializer('Constant initializer requires a literal');
		$end = $cursor->take(token_kind::semicolon, '; (constant initializer must be a literal)') + 1;
		return new ast_constant_declaration($start, $end, $name, $parser->source_namespace(), $type, $initializer, $private);
	}
}

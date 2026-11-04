<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Parse body-level use declarations through the existing flavor path adapter; do not resolve targets. */
final class Import_Syntax
{
	/** @return ast_import[] */
	public static function read(Syntax_Cursor $cursor, Namespace_Syntax $names): array /** list<ast_import> */
	{
		$start = $cursor->position();
		$cursor->take_keyword('use');
		$kind = import_kind::symbol;
		if ($cursor->keyword('function') || $cursor->keyword('const')) {
			$kind = $cursor->keyword('function') ? import_kind::function_only : import_kind::constant_only;
			$cursor->take(token_kind::keyword, 'import kind');
		}
		$path = ltrim($names->path(true, true), '\\');
		$imports = [];
		if ($cursor->is(token_kind::lbrace))
		{
			$previous = $cursor->tokens->tokens[$cursor->position() - 1]->kind;
			if (!in_array($previous, [token_kind::backslash, token_kind::scope, token_kind::dot], true)) {
				throw new diagnostic($cursor->tokens->source, $cursor->offset(), 'Expected separator before import group');
			}
			$cursor->take(token_kind::lbrace, '{');
			do {
				$member_start = $cursor->position();
				$target = $path . '\\' . $names->path(false);
				$imports[] = self::binding($cursor, $member_start, $target, $kind);
				if (!$cursor->is(token_kind::comma)) {
					break;
				}
				$cursor->take(token_kind::comma, ',');
			} while (!$cursor->is(token_kind::rbrace));
			$cursor->take(token_kind::rbrace, '}');
		}
		else {
			$imports[] = self::binding($cursor, $start, $path, $kind);
		}
		$cursor->take(token_kind::semicolon, ';');
		return $imports;
	}

	/** $start: token position of this occurrence. */
	private static function binding(Syntax_Cursor $cursor, int $start /** uint32 */,
		string $target, import_kind $kind): ast_import
	{
		$separator = strrpos($target, '\\');
		$name = $separator === false ? $target : substr($target, $separator + 1);
		if ($cursor->keyword('as')) {
			$cursor->take_keyword('as');
			$name = $cursor->name(token_kind::identifier);
		}
		return new ast_import($start, $cursor->position(), $target, $name, $kind);
	}
}

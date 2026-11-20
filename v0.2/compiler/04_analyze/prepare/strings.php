<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Decodes the supported quoted-literal subset into exact bytes during preparation and diagnoses unsupported forms. */
final class String_Literals
{
	/** Decode a bounded literal subset; never use host interpolation or permissive C/PHP unescaping. */
	public static function decode(ast_string_expression $syntax): string
	{
		$text = $syntax->source_text();
		$part = $syntax instanceof ast_interpolation_text;
		$cpp = $part ? $syntax->source()->flavor === flavor::cpp : str_starts_with($text, 'f"');
		$js = $part ? $syntax->source()->flavor === flavor::js : $text[0] === '`';
		$single = !$part && $text[0] === "'";
		$end = strlen($text) - ($part ? 0 : 1);
		$bytes = '';
		for ($index = $part ? 0 : ($cpp ? 2 : 1); $index < $end; ++$index)
		{
			$byte = $text[$index];
			if ($cpp && ($byte === '{' || $byte === '}')) {
				// Parsing validated doubled literal braces before preparation.
				++$index;
			}
			if ($byte !== '\\') {
				$bytes .= $byte;
				continue;
			}
			$escape = $index++;
			if ($index >= $end) {
				throw new diagnostic($syntax->source(), $syntax->byte_start() + $escape,
					'Incomplete string escape');
			}
			$next = $text[$index];
			if ($single) {
				$bytes .= (($next === "'") || ($next === '\\')) ? $next : '\\' . $next;
				continue;
			}
			$decoded = self::decode_escape($next, $js);
			$following = $text[$index + 1] ?? '';
			if (($decoded === null) || (($next === '0') && ($following >= '0') && ($following <= '7'))) {
				throw new diagnostic($syntax->source(), $syntax->byte_start() + $escape,
					'Unsupported string escape');
			}
			$bytes .= $decoded;
		}
		return $bytes;
	}

	/** Decode one byte following a backslash, or return null if the escape is unsupported. */
	private static function decode_escape(string $next, bool $js): ?string
	{
		switch ($next) {
			case '\\': return '\\';
			case '"': return '"';
			case '$': return '$';
			case '`':
				if ($js) return '`';
				return null;
			case 'n': return "\n";
			case 'r': return "\r";
			case 't': return "\t";
			case '0': return "\0";
			default: return null;
		}
	}
}

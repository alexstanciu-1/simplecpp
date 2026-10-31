<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Decodes the supported quoted-literal subset into exact bytes during preparation and diagnoses unsupported forms.
 */
final class Output_Literals
{
	/** Decode a bounded literal subset; never use host interpolation or permissive C/PHP unescaping. */
	public static function decode(ast_string_expression $syntax): string
	{
		$text = $syntax->source_text();
		$single = $text[0] === "'";
		$end = strlen($text) - 1;
		$bytes = '';
		for ($index = 1; $index < $end; ++$index)
		{
			$byte = $text[$index];
			if (!$single && ($byte === '$')) {
				throw new diagnostic($syntax->source(), $syntax->byte_start() + $index,
					'Echo interpolation is not supported yet; escape the dollar or use single quotes');
			}
			if ($byte !== '\\') {
				$bytes .= $byte;
				continue;
			}
			$escape = $index++;
			if ($index >= $end) {
				throw new diagnostic($syntax->source(), $syntax->byte_start() + $escape, 'Incomplete string escape');
			}
			$next = $text[$index];
			if ($single) {
				$bytes .= (($next === "'") || ($next === '\\')) ? $next : '\\' . $next;
				continue;
			}
			$decoded = match ($next) {
				'\\' => '\\', '"' => '"', '$' => '$',
				'n' => "\n", 'r' => "\r", 't' => "\t", '0' => "\0",
				default => null,
			};
			$following = $text[$index + 1] ?? '';
			if (($decoded === null) || (($next === '0') && ($following >= '0') && ($following <= '7'))) {
				throw new diagnostic($syntax->source(), $syntax->byte_start() + $escape,
					'Unsupported echo string escape');
			}
			$bytes .= $decoded;
		}
		return $bytes;
	}
}

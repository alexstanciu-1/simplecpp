<?php
namespace scpp\compiler;

/** Immutable-by-contract public snapshot; no AST/work retention in a delivered diagnostic. */
final class compiler_diagnostic
{
	public function __construct(public string $code, public string $severity,
		public string $path, public int $line, public int $column, public string $message)
	{
	}
}

final class source_location
{
	public function __construct(public string $path, public int $line, public int $column)
	{
	}
}

/** Current source coordinates, shared by diagnostics and provider call-site context. */
final class Source_Locations
{
	public static function at(token_list $tokens, int $index): source_location
	{
		$ranges /** Storage<retained_token_range> */ = $tokens->retained_ranges;
		foreach ($ranges as $range) {
			if (($index >= $range->first) && ($index < $range->end)) {
				$index += $range->current_first - $range->first;
				break;
			}
		}
		$rows /** Storage<token> */ = $tokens->tokens;
		$offset = (int)$rows[$index]->offset;
		$line = 1; $column = 1;
		for ($position = $tokens->content_offset; $position < $offset; $position++) {
			$byte = string_byte_at($tokens->content, $position);
			if ($byte === 10) {
				$line++; $column = 1;
			}
			elseif ($byte === 13) {
				$line++; $column = 1;
				if ($position + 1 < $offset) {
					if (string_byte_at($tokens->content, $position + 1) === 10) {
						$position++;
					}
				}
			}
			else {
				$column++;
			}
		}
		return new source_location($tokens->file->path, $line, $column);
	}
}

/** Delivery renders existing records, never determines language validity. */
final class Diagnostic_Reporting
{
	public static function text(Storage $diagnostics /** Storage<compiler_diagnostic> */): string
	{
		$text = '';
		foreach ($diagnostics as $diagnostic) {
			$text .= $diagnostic->path . ':' . $diagnostic->line . ':' . $diagnostic->column
				. ': ' . $diagnostic->severity . ' [' . $diagnostic->code . '] ' . $diagnostic->message . "\n";
		}
		return $text;
	}
}

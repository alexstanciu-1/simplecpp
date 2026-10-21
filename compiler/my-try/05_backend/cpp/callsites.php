<?php
namespace scpp\compiler;

/** Physical provider context; source callable signatures retain only authored inputs. */
final class CPP_Callsites
{
	public static function for_call(call_node $syntax, cpp_generation_context $context): ?cpp_callsite
	{
		$target = weakref_get($syntax->require_call_preparation()->target);
		if (!($target instanceof runtime_function_definition)) { return null; }
		$binding = Model::$cpp_callable_bindings->function_binding(object_cast($target, runtime_function_definition::class));
		if (!$binding->source_context) { return null; }
		$context->reads_source_context = true;
		$tokens = $syntax->occurrence()->token_snapshot();
		$index = $syntax->start_token();
		$ranges /** Storage<retained_token_range> */ = $tokens->retained_ranges;
		foreach ($ranges as $range) {
			if (($index >= $range->first) && ($index < $range->end)) {
				$index += $range->current_first - $range->first;
				break;
			}
		}
		$rows /** Storage<token> */ = $tokens->tokens;
		$offset = (int)$rows[$index]->offset;
		$line = 1;
		for ($position = $tokens->content_offset; $position < $offset; $position++) {
			$byte = string_byte_at($tokens->content, $position);
			if ($byte === 10) { $line++; }
			elseif ($byte === 13) {
				$line++;
				if (($position + 1 < $offset) && (string_byte_at($tokens->content, $position + 1) === 10)) { $position++; }
			}
		}
		return new cpp_callsite($tokens->file->path, $line);
	}

	/** Whitespace-only edits can move calls without changing semantic completion versions. */
	public static function digest(collected_file $source): string
	{
		$tokens = $source->token_snapshot();
		return content_sha256(json_quote($tokens->file->path) . ':' . string_byte_slice($tokens->content,
			$tokens->content_offset, string_byte_len($tokens->content) - $tokens->content_offset));
	}

	public static function arguments(?cpp_callsite $site, string $arguments): string
	{
		if ($site === null) { return $arguments; }
		return CPP_Generator::cpp_string_literal($site->path) . ', ' . $site->line
			. ($arguments === '' ? '' : ', ' . $arguments);
	}
}

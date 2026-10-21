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
		$location = Source_Locations::at($tokens, $syntax->start_token());
		return new cpp_callsite($location->path, $location->line);
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

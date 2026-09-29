<?php

/* Publish completed per-source stages through their stable membership records. */
namespace scpp\compiler;

final class Source_Publication
{
	/** Compatibility query for external callers; work publication already holds the record. */
	public static function find_source(string $path): ?file
	{
		$record = Source_Registry::find($path);
		if ($record === null) {
			return null;
		}
		if ($record->file->changes === \scpp\compiler\SYNC_DELETED) {
			return null;
		}
		return $record->file;
	}

	/** Caller serializes publication; scope export and completed stages share one destination. */
	public static function publish_parsed(source_record $record, parsed_file $parsed): void
	{
		Scope_Publication::publish($parsed->root_scope(), Model::$global_scope);
		$record->file = $parsed->source_file();
		$record->changes = change_state::unchanged;
		$record->tokens = $parsed->tokens;
		$record->parsed = $parsed;
		$record->file->tokens = $parsed->tokens;
	}

	/** Publish appended token input or retain the mutable parse, including failed-file retry state. */
	public static function publish_stage(source_work $work, frontend_operation $operation): void
	{
		if ($operation === frontend_operation::scan)
		{
			$tokens = object_cast($work->tokens, token_list::class);
			$record = $work->record;
			if ($record->tokens !== null)
			{
				try {
					Token_Buffer::append($tokens, $record->tokens);
				}
				catch (\Throwable $error) {
					Model::$rebuild_required = true;
					throw $error;
				}
			}
			$record->tokens = $tokens;
			$record->file = $tokens->file;
			$record->file->tokens = $tokens;
		}
		if ($operation === frontend_operation::parse) {
			$work->record->parsed = $work->result;
			if ($work->state !== work_state::failed) {
				$work->record->changes = change_state::unchanged;
				$work->record->file->changes = 0;
			}
		}
	}
}

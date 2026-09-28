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

	/** Replace one complete source result; preserve prior published state on frontend failure. */
	public static function publish_update(source_work $work): void
	{
		$source = $work->source;
		$record = $work->record;
		$previous = $work->previous;
		if ($source->changes === \scpp\compiler\SYNC_DELETED)
		{
			$record->file->changes = \scpp\compiler\SYNC_DELETED;
			$record->changes = change_state::deleted;
			if ($previous !== null) {
				$old /** parsed_file */ = $previous;
				$entries /** Storage<collected_name> */ = $old->collection->entries;
				foreach ($old->collection->defined_elements as $index) {
					$entries[$index]->changes = \scpp\compiler\SYNC_DELETED;
				}
			}
			return;
		}

		$candidate = object_cast($work->result, parsed_file::class);
		$entries /** Storage<collected_name> */ = $candidate->collection->entries;
		foreach ($candidate->collection->defined_elements as $index) {
			$entries[$index]->changes = \scpp\compiler\SYNC_ADDED;
		}
		$source->changes = \scpp\compiler\SYNC_ADDED;
		if ($previous !== null)
		{
			$old /** parsed_file */ = $previous;
			Declaration_Changes::compare($old, $candidate);
			if ($record->file->changes !== \scpp\compiler\SYNC_DELETED) {
				$source->changes = $old->tokens->content === $candidate->tokens->content ? 0 : \scpp\compiler\SYNC_CHANGED;
			}
			Scope_Publication::replace_collection(Model::$global_scope, $old->collection);
		}
		self::publish_parsed($record, $candidate);
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

	/** Standalone scanning leaves parsing absent; standalone parsing reuses the exact scan. */
	public static function publish_stage(source_work $work, frontend_operation $operation): void
	{
		if ($operation === frontend_operation::scan) {
			$tokens = object_cast($work->tokens, token_list::class);
			$work->record->tokens = $tokens;
			$work->source->tokens = $tokens;
		}
		if ($operation === frontend_operation::parse) {
			self::publish_parsed($work->record, object_cast($work->result, parsed_file::class));
		}
	}
}

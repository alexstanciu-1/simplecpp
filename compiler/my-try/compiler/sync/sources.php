<?php

/* Role: turn source notifications into isolated frontend candidates. */
namespace scpp\compiler;

final class Source_Synchronization
{
	/** Reset notification flags and build private candidates in notification order. */
	public static function plan(array $paths /** vector<string> */): Source_Work_Queue
	{
		foreach (Model::sources() as $record) {
			$source = $record->file;
			$source->changes = $source->changes === \scpp\compiler\SYNC_DELETED ? \scpp\compiler\SYNC_DELETED : 0;
		}
		foreach (Model::collected_files() as $collection) {
			$entries /** Storage<collected_name> */ = $collection->entries;
			foreach ($collection->defined_elements as $position) {
				$entry = $entries[$position];
				$entry->changes = $entry->changes === \scpp\compiler\SYNC_DELETED ? \scpp\compiler\SYNC_DELETED : 0;
			}
		}
		$queue = new Source_Work_Queue();
		$seen /** hash<bool> */ = [];
		foreach ($paths as $notified)
		{
			$path = Source_Registry::normalize($notified);
			if (isset($seen[$path])) {
				continue;
			}
			$seen[$path] = true;
			$record = Source_Registry::resolve($path);
			$previous = $record->file;
			$candidate = new file();
			$candidate->path = $record->path;
			$candidate->disk_source = $previous->disk_source;
			$candidate->content = $previous->content;
			if ($candidate->disk_source) {
				if (($record->changes === change_state::deleted) || !fs_is_file($path)) {
					if (($record->changes === change_state::deleted) || ($previous->tokens !== null)) {
						$candidate->changes = \scpp\compiler\SYNC_DELETED;
					}
				}
			}
			if ($record->changes === change_state::unchanged) {
				$record->changes = change_state::changed;
			}
			$queue->enqueue($record, $candidate);
		}
		return $queue;
	}
}

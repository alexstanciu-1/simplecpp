<?php

/* Role: turn source notifications into isolated frontend candidates. */
namespace scpp\compiler;

final class Source_Synchronization
{
	/** Reset notification flags and build private candidates in notification order. */
	public static function plan(array $paths /** vector<string> */): Source_Work_Queue
	{
		foreach (Model::$modules as $input_module) {
			foreach ($input_module->files as $source) {
				$source->changes = $source->changes === \scpp\compiler\SYNC_DELETED ? \scpp\compiler\SYNC_DELETED : 0;
			}
		}
		foreach (Model::$collected_files as $collection) {
			$entries /** Storage<collected_name> */ = $collection->entries;
			foreach ($collection->defined_elements as $position) {
				$entry = $entries[$position];
				$entry->changes = $entry->changes === \scpp\compiler\SYNC_DELETED ? \scpp\compiler\SYNC_DELETED : 0;
			}
		}
		$queue = new Source_Work_Queue();
		$seen /** hash<bool> */ = [];
		foreach ($paths as $path)
		{
			if (isset($seen[$path])) {
				continue;
			}
			$seen[$path] = true;
			$owner_found = false;
			$previous = Source_Publication::find_source($path);
			foreach (Model::$modules as $input_module) {
				if ($input_module->path === fs_dirname($path)) {
					$owner_found = true;
				}
			}
			if (!$owner_found) {
				throw new \LogicException('Module membership changed: call init with the complete module list, then exec');
			}
			$candidate = new file();
			$candidate->path = $path;
			$candidate->disk_source = true;
			if ($previous !== null) {
				$old = object_cast($previous, file::class);
				$candidate->disk_source = $old->disk_source;
				$candidate->content = $old->content;
			}
			if ($candidate->disk_source)
			{
				if (!fs_is_file($path))
				{
					if ($previous !== null) {
						$old = object_cast($previous, file::class);
						if ($old->tokens !== null) {
							$candidate->changes = \scpp\compiler\SYNC_DELETED;
						}
					}
				}
			}
			$queue->enqueue($candidate);
		}
		return $queue;
	}
}

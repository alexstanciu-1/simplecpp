<?php

/* Explicit notifications supplement filesystem metadata selection without a second frontend path. */
namespace scpp\compiler;

final class Source_Synchronization
{
	/** Repeated notifications only mark existing records; the phase queue selects each source once. */
	public static function notify(array $paths /** vector<string> */): void
	{
		foreach ($paths as $notified)
		{
			$path = Source_Registry::normalize($notified);
			$record = Source_Registry::resolve($path);
			if ($record->file->disk_source) {
				if (!fs_is_file($path)) {
					$record->changes = change_state::deleted;
					continue;
				}
			}
			if ($record->changes === change_state::deleted) {
				$record->changes = change_state::added;
			}
			elseif ($record->changes === change_state::unchanged) {
				$record->changes = change_state::changed;
			}
		}
	}
}

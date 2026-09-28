<?php

/*
 * Role: discover a module's recursive PHS files.
 * Call map: Compiler::init_modules -> Module_Loader::configuration/discover.
 * Flow: sorted depth-first directory traversal -> ordered module file records.
 */
namespace scpp\compiler;

final class Module_Loader
{
	/** Resolve complete input privately; invalid configurations preserve the published session. */
	public static function configuration(Storage $inputs /** Storage<module_input> */): Keyed_Storage /** Keyed_Storage<module> */
	{
		$result /** Keyed_Storage<module> */ = new Keyed_Storage();
		$position = 0;
		foreach ($inputs as $input)
		{
			if (isset($result[$input->name])) {
				throw new \LogicException('Duplicate module key: ' . $input->name);
			}
			$resolved = fs_require_realpath($input->declared_path);
			if (!fs_is_dir($resolved)) {
				throw new \LogicException('Module root must be a directory: ' . $input->declared_path);
			}
			$candidate = new module($input->declared_path, $resolved, $input->name);
			$candidate->position = $position;
			$candidate->disk_source = true;
			$position++;
			foreach ($result as $existing) {
				if (($existing->resolved_path === $resolved) || Module_Loader::contains_path($existing, $resolved) || Module_Loader::contains_path($candidate, $existing->resolved_path)) {
					throw new \LogicException('Overlapping module roots are not supported');
				}
			}
			$result->add($candidate->name, $candidate);
		}
		return $result;
	}

	/** Update module membership directly; only a complete scan can establish deletions. */
	public static function discover(module $module): void
	{
		$revision = Compiler_Lifecycle::next_revision();
		self::scan($module, '', $revision);
		foreach ($module->sources as $source) {
			if ((int)$source->revision !== $revision) {
				$source->changes = change_state::deleted;
			}
		}
	}

	/** Compare directory ancestors, including missing files reported for deletion. */
	public static function contains_path(module $module, string $path): bool
	{
		$root = fs_dirname($module->resolved_path . "/__module_member__");
		$directory = fs_dirname($path);
		while ($directory !== $root) {
			$parent = fs_dirname($directory);
			if ($parent === $directory) {
				return false;
			}
			$directory = $parent;
		}
		return true;
	}

	/** Folders are traversal context; every file belongs to the module's relative-path index. */
	private static function scan(module $module, string $folder, int $revision): void
	{
		$path = Source_Registry::full_path($module, $folder);
		$names /** vector<string> */ = [];
		if (!take_false($names, fs_scan($path))) {
			throw new \RuntimeException('Cannot scan input folder: ' . $path);
		}
		$members /** Keyed_Storage<source_record> */ = $module->sources;
		foreach ($names as $name)
		{
			$relative = $folder . $name;
			$full_path = Source_Registry::full_path($module, $relative);
			if (fs_is_dir($full_path)) {
				if (!fs_is_link($full_path)) {
					self::scan($module, $relative . '/', $revision);
				}
				continue;
			}
			if (!string_byte_ends_with($name, '.phs') || !fs_is_file($full_path)) {
				continue;
			}
			$mtime = 0;
			$size = 0;
			if (!take_false($mtime, fs_mtime($full_path)) || !take_false($size, fs_size($full_path))) {
				throw new \RuntimeException('Cannot read file metadata: ' . $full_path);
			}
			if (!isset($members[$relative])) {
				$loaded = new file();
				$loaded->path = $relative;
				$loaded->disk_source = true;
				Source_Registry::add($module, $loaded);
			}
			$record = $members[$relative];
			$record->revision = $revision;
			if ($record->changes === change_state::deleted) {
				$record->changes = change_state::added;
			}
			elseif (($record->file->mtime !== $mtime) || ($record->file->size !== $size)) {
				if ($record->changes === change_state::unchanged) {
					$record->changes = change_state::changed;
				}
			}
		}
	}
}

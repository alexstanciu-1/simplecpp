<?php

/*
 * Role: discover a module's recursive PHS files.
 * Call map: Module_Synchronization -> Module_Loader::discover.
 * Flow: sorted depth-first directory traversal -> ordered module file records.
 */
namespace scpp\compiler;

final class Module_Loader
{
	/** Discover an already validated canonical root. */
	public static function discover(module $module): void
	{
		self::scan($module, $module->resolved_path);
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

	/** Recurse through real directories; directory symlinks must not introduce cycles. */
	private static function scan(module $module, string $path): void
	{
		$names /** vector<string> */ = [];
		if (!take_false($names, fs_scan($path))) {
			throw new \RuntimeException('Cannot scan input folder: ' . $path);
		}

		foreach ($names as $name)
		{
			$file_path = $path . '/' . $name;
			if (fs_is_dir($file_path)) {
				if (!fs_is_link($file_path)) {
					self::scan($module, $file_path);
				}
				continue;
			}

			if (!string_byte_ends_with($name, '.phs') || !fs_is_file($file_path)) {
				continue;
			}

			$loaded = new file();
			$loaded->path = $file_path;
			$loaded->disk_source = true;
			Source_Registry::add($module, $loaded);
		}
	}
}

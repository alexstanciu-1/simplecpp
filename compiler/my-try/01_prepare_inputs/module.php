<?php

/*
 * Role: discover a module's recursive PHS files.
 * Call map: Compiler::init -> Module_Loader::init.
 * Flow: sorted depth-first directory traversal -> ordered module file records.
 */
namespace scpp\compiler;

final class Module_Loader
{
	/** Collect descendant PHS files, preserving sorted depth-first directory order. */
	public static function init(module $module, string $path): void
	{
		$module->path = fs_require_realpath($path);
		foreach (Model::$modules as $existing) {
			if (($existing->path === $module->path) || self::contains_path($existing, $module->path) || self::contains_path($module, $existing->path)) {
				throw new \LogicException('Overlapping module roots are not supported');
			}
		}
		self::scan($module, $module->path);
	}

	/** Compare directory ancestors, including missing files reported for deletion. */
	public static function contains_path(module $module, string $path): bool
	{
		$root = fs_dirname($module->path . "/__module_member__");
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

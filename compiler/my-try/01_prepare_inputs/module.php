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
		$module->path = $path;
		$files /** Storage<file> */ = new Storage();
		$module->files = $files;

		self::scan($files, $path);
	}

	/** Compare directory ancestors, including missing files reported for deletion. */
	public static function contains_path(module $module, string $path): bool
	{
		$root = fs_dirname($module->path . "/__module_member__");
		$directory = fs_dirname($path);
		while (true)
		{
			if ($directory === $root) {
				return true;
			}
			$parent = fs_dirname($directory);
			if ($parent === $directory) {
				return false;
			}
			$directory = $parent;
		}
	}

	/** Recurse through real directories; directory symlinks must not introduce cycles. */
	private static function scan(Storage $files /** Storage<file> */, string $path): void
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
					self::scan($files, $file_path);
				}
				continue;
			}

			if (!string_byte_ends_with($name, '.phs') || !fs_is_file($file_path)) {
				continue;
			}

			$loaded = new file();
			$loaded->path = $file_path;
			$loaded->disk_source = true;
			$files[] = $loaded;
		}
	}
}

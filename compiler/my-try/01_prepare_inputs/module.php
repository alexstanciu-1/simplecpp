<?php

/*
 * Role: discover a module's immediate PHS files.
 * Call map: Compiler::init -> Module_Loader::init.
 * Flow: sorted directory names -> ordered module file records.
 */
namespace scpp\compiler;

final class Module_Loader
{
	/** Collect the folder's immediate PHS files in filename order. */
	public static function init(module $module, string $path): void
	{
		$module->path = $path;
		$files /** Storage<file> */ = new Storage();
		$module->files = $files;

		$names /** vector<string> */ = [];
		if (!take_false($names, fs_scan($path))) {
			throw new \RuntimeException('Cannot scan input folder: ' . $path);
		}

		foreach ($names as $name)
		{
			$file_path = $path . '/' . $name;
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

<?php

/*
 * Role: discover and load a module's immediate PHS files.
 * Call map: host -> Module_Loader::init -> File_Loader::init.
 * Flow: sorted directory names -> ordered module file records.
 */
namespace scpp\compiler;

final class Module_Loader
{
	/** Collect the folder's immediate PHS files in filename order. */
	public static function init(module $module, string $path): void
	{
		$module->path = $path;
		$module->files = new Storage();

		/** @var list<string>|false $scan_result PHP scan result, including failure. */
		$scan_result = scandir($path);
		if ($scan_result === false) {
			throw new \RuntimeException("Cannot scan input folder: $path");
		}
		$names /** vector<string> */ = $scan_result;

		foreach ($names as $name)
		{
			$file_path = $path . '/' . $name;
			if ((pathinfo($name, PATHINFO_EXTENSION) !== 'phs') || !is_file($file_path)) {
				continue;
			}

			$file = new file();
			File_Loader::init($file, $file_path);
			$module->files[] = $file;

			if (\dbg) {
				echo "file: {$file_path}\n";
			}
		}
	}
}

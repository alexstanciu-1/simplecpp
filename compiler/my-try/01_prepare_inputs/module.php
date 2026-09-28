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

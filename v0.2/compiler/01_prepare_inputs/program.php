<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Validates root-relative project inventory and entry selection before individual source files are read. */
final class Program_Loader
{
	/** Reject ambiguous logical spellings rather than guessing identity through path normalization. */
	public static function require_relative(string $path): void
	{
		if (($path === '') || str_starts_with($path, '/') || str_contains($path, '\\')
			|| str_contains($path, "\0") || str_contains($path, ':')) {
			throw new \RuntimeException('Expected a root-relative source path: ' . $path);
		}
		foreach (explode('/', $path) as $part) {
			if (in_array($part, ['', '.', '..'], true)) {
				throw new \RuntimeException('Source paths must have canonical relative spelling: ' . $path);
			}
		}
	}

	/**
	 * Existing CLI inventory is a single application module.
	 *
	 * @param flavor[] $requests
	 */
	public static function inventory(string $root, string $entry,
		array $requests /** array<string,flavor> */): compilation_session
	{
		return self::modules(['app' => ['root' => $root, 'sources' => $requests,
					'dependencies' => [], 'entry' => $entry]], 'app');
	}

	/**
	 * Same membership/graph path for caller-supplied snapshots; no disk reads or retained duplicate snapshots.
	 *
	 * @param source_file[] $sources
	 */
	public static function memory(string $entry, array $sources /** array<string,source_file> */): compilation_session
	{
		$requests = [];
		foreach ($sources as $path => $source) {
			$requests[$path] = $source->flavor;
		}
		return self::modules(['app' => ['root' => '', 'sources' => $requests,
					'dependencies' => [], 'entry' => $entry]], 'app', false);
	}

	/**
	 * Resolve a selected dependency closure privately before publishing the session.
	 * @param array<string,array{root:string,sources:array<string,flavor>,dependencies:list<string>,entry?:string,type?:string,manifest?:string,artifacts?:list<string>}> $definitions
	 * The caller selects disk validation; both paths create identical ownership records.
	 *
	 * $selected: additional activation roots.
	 *
	 * @param string[] $selected
	 */
	public static function modules(array $definitions /** array<string,array{root:string,sources:array<string,flavor>,dependencies:list<string>,entry?:string,type?:string,manifest?:string,artifacts?:list<string>}> */,
		string $entry, bool $disk = true,
		array $selected /** list<string> */ = []): compilation_session
	{
		/** @var module[] $modules */
		$modules = [];
		$active = [];
		$physical = [];
		$visit = function (string $name) use (&$visit, &$modules, &$active, &$physical, $definitions, $disk): module
		{
			if (isset($active[$name])) {
				throw new \RuntimeException('Module dependency cycle: ' . $name);
			}
			if (isset($modules[$name])) {
				return $modules[$name];
			}
			if ((preg_match('/^[A-Za-z_][A-Za-z0-9_.-]*$/D', $name) !== 1) || (!isset($definitions[$name]))) {
				throw new \RuntimeException('Unknown or invalid module: ' . $name);
			}
			$active[$name] = true;
			$input = $definitions[$name];
			$root = $disk ? realpath($input['root']) : $input['root'];
			if (($root === false) || (($disk) && (!is_dir($root)))) {
				throw new \RuntimeException('Source root is not a directory: ' . $input['root']);
			}
			$type = $input['type'] ?? 'default';
			if (!in_array($type, ['default', 'c++'], true)) {
				throw new \RuntimeException('Unknown module type: ' . $type);
			}
			$module = new module($name, $root, $type, $input['manifest'] ?? null, $input['artifacts'] ?? []);
			foreach ($input['dependencies'] as $dependency) {
				if (isset($module->dependencies[$dependency])) {
					continue;
				}
				$module->dependencies[$dependency] = $visit($dependency);
			}
			foreach ($input['sources'] as $path => $flavor)
			{
				$path = (string) $path;
				self::require_relative($path);
				$unit = new source_unit($module, $path, $flavor);
				if ($disk) {
					$resolved = self::disk_path($unit);
					if (isset($physical[$resolved])) {
						throw new \RuntimeException('Duplicate physical source: ' . $path);
					}
					$physical[$resolved] = true;
				}
				$module->sources[$path] = $unit;
			}
			if (isset($input['entry'])) {
				self::require_relative($input['entry']);
				$module->entry = $module->sources[$input['entry']]
					?? throw new \RuntimeException('Selected entry is not in the source inventory: '
					. $input['entry']);
			}
			unset($active[$name]);
			$modules[$name] = $module;
			return $module;
		};
		$main = $visit($entry);
		foreach ($selected as $name) {
			$visit($name);
		}
		if ($main->type !== 'default') {
			throw new \RuntimeException('Executable entry must be a default module');
		}
		if ($main->entry === null) {
			throw new \RuntimeException('Entry module requires an entry source: ' . $entry);
		}
		return new compilation_session($modules, $main);
	}

	/** Validate containment again at read time; module membership remains relative. */
	private static function disk_path(source_unit $unit): string
	{
		$resolved = realpath($unit->module->root . '/' . $unit->path);
		if (($resolved === false) || (!is_file($resolved))
			|| (!str_starts_with($resolved, rtrim($unit->module->root, '/') . '/'))) {
			throw new \RuntimeException('Source must exist within its root: ' . $unit->path);
		}
		return $resolved;
	}

	public static function load(source_unit $unit): source_file
	{
		return File_Loader::load(self::disk_path($unit), $unit->flavor);
	}
}

<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Filesystem metadata of a complete project invocation; no per-file reuse or dependency watches. */
final class Project_Changes
{
	/** Stat module inputs and retain their selection; no file contents are read for change detection. */
	public static function capture(compilation_session $session, string $facts, string $output): array /** array{version:int,php:string,entry:string,graph:array,files:array,units:array} */
	{
		$files = [];
		$units = [];
		clearstatcache();
		$graph = [];
		$excluded = realpath($output);
		foreach ($session->modules as $name => $module)
		{
			if ($excluded === $module->root) {
				throw new \RuntimeException('Project output must not be a module root');
			}
			self::scan($module->root, $files, $excluded);
			$sources = [];
			foreach ($module->sources as $unit) {
				$sources[$unit->path] = $unit->flavor->value;
				self::file($module->root . '/' . $unit->path, $files);
				$units[$module->root . '/' . $unit->path] = [$name, $module->root, $module->type, $unit->path, $unit->flavor->value];
			}
			$dependencies = array_keys($module->dependencies);
			sort($dependencies, SORT_STRING);
			ksort($sources, SORT_STRING);
			$graph[$name] = [$module->root, $module->type, $module->manifest, $dependencies, $sources];
			if ($module->manifest !== null) {
				self::file($module->manifest, $files);
			}
		}
		self::file($facts, $files);
		self::scan(dirname(__DIR__), $files, false, true);
		ksort($files, SORT_STRING);
		ksort($graph, SORT_STRING);
		ksort($units, SORT_STRING);
		return ['version' => 2, 'php' => PHP_VERSION, 'entry' => $session->entry_key(),
			'graph' => $graph, 'files' => $files, 'units' => $units];
	}

	/** Mark current units and retain removed units separately until the caller completes the rebuild. */
	public static function mark(compilation_session $session, array $current /** captured state */,
		?array $previous /** previous successful captured state */): array /** list<source_unit>; removed units */
	{
		foreach ($session->units() as $unit)
		{
			$path = $unit->module->root . '/' . $unit->path;
			$unit->mtime = $current['files'][$path]['mtime'];
			$unit->size = $current['files'][$path]['size'];
			$unit->status = !isset($previous['units'][$path]) ? change_status::added
				: (($previous['files'][$path] !== $current['files'][$path])
					|| ($previous['units'][$path] !== $current['units'][$path])
					? change_status::changed : change_status::none);
		}
		$removed = [];
		foreach ($previous['units'] ?? [] as $path => $identity)
		{
			if (isset($current['units'][$path])) {
				continue;
			}
			[$name, $root, $type, $relative, $flavor] = $identity;
			$owner = $session->modules[$name] ?? null;
			if (($owner === null) || ($owner->root !== $root)) {
				$owner = new module($name, $root, $type);
			}
			$unit = new source_unit($owner, $relative, flavor::from($flavor));
			$unit->status = change_status::removed;
			$unit->mtime = $previous['files'][$path]['mtime'];
			$unit->size = $previous['files'][$path]['size'];
			$removed[] = $unit;
		}
		return $removed;
	}

	/** Reject legacy/corrupt persisted baselines before constructing source-unit change records. */
	public static function valid(mixed $state): bool
	{
		if (!is_array($state) || (($state['version'] ?? null) !== 2)
			|| !is_string($state['php'] ?? null) || !is_string($state['entry'] ?? null)
			|| !is_array($state['graph'] ?? null) || !is_array($state['files'] ?? null)
			|| !is_array($state['units'] ?? null)) {
			return false;
		}
		foreach ($state['files'] as $path => $stat) {
			if (!is_string($path) || !is_array($stat) || !is_int($stat['mtime'] ?? null)
				|| !is_int($stat['size'] ?? null) || ($stat['size'] < 0)) {
				return false;
			}
		}
		foreach ($state['units'] as $path => $identity)
		{
			if (!isset($state['files'][$path]) || !is_array($identity) || !array_is_list($identity)
				|| (count($identity) !== 5)) {
				return false;
			}
			foreach ($identity as $field) {
				if (!is_string($field)) {
					return false;
				}
			}
			if ((flavor::tryFrom($identity[4]) === null) || !in_array($identity[2], ['default', 'c++'], true)) {
				return false;
			}
		}
		return true;
	}

	/** Generated runtime headers are inputs too; only build trees, VCS state and inspection inventories are excluded. */
	private static function scan(string $directory, array &$files /** array<string,array{mtime:int64,size:uint64}> */,
		string|false $excluded, bool $compiler = false): void
	{
		$entries = scandir($directory);
		if ($entries === false) {
			throw new \RuntimeException('Cannot inspect project inputs: ' . $directory);
		}
		foreach ($entries as $entry)
		{
			if (in_array($entry, ['.', '..', '.git', 'module.sources.json'], true)
				|| str_starts_with($entry, '.module-sources-')) {
				continue;
			}
			$path = $directory . '/' . $entry;
			if (is_dir($path))
			{
				if ((realpath($path) === $excluded) || (strtolower($entry) === 'build')
					|| ($compiler && ($entry === 'tests'))) {
					continue;
				}
				if (is_link($path)) {
					throw new \RuntimeException('Cannot track a symlinked input directory: ' . $path);
				}
				self::scan($path, $files, $excluded, $compiler);
			}
			elseif (is_file($path) && (!$compiler || (pathinfo($path, PATHINFO_EXTENSION) === 'php'))) {
				self::file($path, $files);
			}
		}
	}

	private static function file(string $path, array &$files /** array<string,array{mtime:int64,size:uint64}> */): void
	{
		$stat = @stat($path);
		if ($stat === false) {
			throw new \RuntimeException('Cannot stat project input: ' . $path);
		}
		$files[$path] = ['mtime' => $stat['mtime'], 'size' => $stat['size']];
	}
}

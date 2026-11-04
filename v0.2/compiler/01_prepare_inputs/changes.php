<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Filesystem metadata of a complete project invocation; no per-file reuse or dependency watches. */
final class Project_Changes
{
	/**
	 * Capture one module/source representation and a separate map for remaining inputs.
	 *
	 * Return: versioned saved input state.
	 *
	 * @return mixed[]
	 */
	public static function capture(compilation_session $session, string $facts, string $output): array /** array<string,mixed> */
	{
		clearstatcache();
		$files = [];
		$modules = [];
		$source_paths = [];
		$visited = [];
		$excluded = realpath($output);
		$dependency_roots = [];
		foreach ($session->modules as $module) {
			if (($module !== $session->entry) && ($module->root !== $session->entry->root)) {
				$dependency_roots[$module->root] = true;
			}
		}
		foreach ($session->modules as $name => $module)
		{
			if ($excluded === $module->root) {
				throw new \RuntimeException('Project output must not be a module root');
			}
			if ($module === $session->entry) {
				self::scan($module->root, $files, $excluded, $visited, false, $dependency_roots);
			}
			$sources = [];
			foreach ($module === $session->entry ? $module->sources : [] as $unit) {
				$path = $module->root . '/' . $unit->path;
				self::file($path, $files);
				$sources[$unit->path] = ['flavor' => $unit->flavor->value, ...$files[$path]];
				$source_paths[$path] = true;
			}
			$dependencies = array_keys($module->dependencies);
			sort($dependencies, \SORT_STRING);
			ksort($sources, \SORT_STRING);
			$modules[$name] = ['root' => $module->root, 'type' => $module->type,
				'manifest' => $module->manifest, 'artifacts' => $module->artifacts, 'dependencies' => $dependencies,
				'sources' => $sources];
			if (($module === $session->entry) && ($module->manifest !== null)) {
				self::file($module->manifest, $files);
			}
		}
		self::file($facts, $files);
		self::scan(dirname(__DIR__), $files, false, $visited, true, $dependency_roots);
		$other_inputs = array_diff_key($files, $source_paths);
		ksort($other_inputs, \SORT_STRING);
		ksort($modules, \SORT_STRING);
		return ['version' => 4, 'php' => \PHP_VERSION, 'entry' => $session->entry_key(),
			'modules' => $modules, 'other_inputs' => $other_inputs];
	}

	/**
	 * Derive a temporary path index for comparisons; source identity is serialized only under its
	 * module.
	 *
	 * $state: saved input state.
	 * Return: absolute path -> source identity and stat.
	 *
	 * @param mixed[]|null $state
	 * @return mixed[]
	 */
	private static function sources(?array $state /** array<string,mixed> */): array /** array<string,mixed> */
	{
		$result = [];
		foreach ($state['modules'] ?? [] as $name => $module) {
			foreach ($module['sources'] as $relative => $source) {
				$result[$module['root'] . '/' . $relative] = ['module' => $name, 'root' => $module['root'],
					'type' => $module['type'], 'path' => (string) $relative, ...$source];
			}
		}
		return $result;
	}

	/**
	 * Mark active units and retain removed units until the caller completes the rebuild.
	 *
	 * $current: captured state.
	 * $previous: previous successful state.
	 * Return: removed units.
	 *
	 * @param mixed[] $current
	 * @param mixed[]|null $previous
	 * @return source_unit[]
	 */
	public static function mark(compilation_session $session, array $current /** array<string,mixed> */,
		?array $previous /** array<string,mixed> */): array /** list<source_unit> */
	{
		$current_sources = self::sources($current);
		$previous_sources = self::sources($previous);
		foreach ($session->units() as $unit)
		{
			$path = $unit->module->root . '/' . $unit->path;
			if (!isset($current_sources[$path])) {
				continue;
			}
			$source = $current_sources[$path];
			$unit->mtime = $source['mtime'];
			$unit->size = $source['size'];
			$unit->status = !isset($previous_sources[$path]) ? change_status::added
				: ($previous_sources[$path] !== $source ? change_status::changed : change_status::none);
		}
		$removed = [];
		foreach (array_diff_key($previous_sources, $current_sources) as $source)
		{
			$owner = $session->modules[$source['module']] ?? null;
			if (($owner === null) || ($owner->root !== $source['root'])) {
				$owner = new module($source['module'], $source['root'], $source['type']);
			}
			$unit = new source_unit($owner, $source['path'], flavor::from($source['flavor']));
			$unit->status = change_status::removed;
			$unit->mtime = $source['mtime'];
			$unit->size = $source['size'];
			$removed[] = $unit;
		}
		return $removed;
	}

	/** Reject old/corrupt saved layouts before reconstructing removed source units. */
	public static function valid(mixed $state): bool
	{
		if ((!is_array($state)) || (($state['version'] ?? null) !== 4)
			|| (!is_string($state['php'] ?? null)) || (!is_string($state['entry'] ?? null))
			|| (!is_array($state['modules'] ?? null)) || (!is_array($state['other_inputs'] ?? null))) {
			return false;
		}
		foreach ($state['other_inputs'] as $path => $stat) {
			if ((!is_string($path)) || (!self::valid_stat($stat))) {
				return false;
			}
		}
		foreach ($state['modules'] as $name => $module)
		{
			if ((!is_string($name)) || (!is_array($module)) || (!is_string($module['root'] ?? null))
				|| (!in_array($module['type'] ?? null, ['default', 'c++'], true))
				|| (!is_array($module['dependencies'] ?? null)) || (!is_array($module['sources'] ?? null))) {
				return false;
			}
			foreach ($module['sources'] as $path => $source) {
				if (((!is_string($path)) && (!is_int($path))) || (!self::valid_stat($source))
					|| (!is_string($source['flavor'] ?? null)) || (flavor::tryFrom($source['flavor']) === null)) {
					return false;
				}
			}
		}
		return true;
	}

	private static function valid_stat(mixed $stat): bool
	{
		return is_array($stat) && is_int($stat['mtime'] ?? null)
			&& is_int($stat['size'] ?? null) && ($stat['size'] >= 0);
	}

	/**
	 * Scan application/compiler inputs only; declared dependency trees are excluded.
	 *
	 * $visited: scan policy + canonical directory.
	 *
	 * @param mixed[][] $files
	 * @param bool[] $visited
	 * @param bool[] $dependency_roots
	 */
	private static function scan(string $directory, array &$files /** array<string,array{mtime:int64,size:uint64}> */,
		string|false $excluded, array &$visited /** array<string,bool> */,
		bool $compiler = false, array $dependency_roots /** array<string,bool> */ = []): void
	{
		$key = ($compiler ? 'compiler:' : 'module:') . (realpath($directory) ?: $directory);
		if (isset($visited[$key])) {
			return;
		}
		$visited[$key] = true;
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
				if (isset($dependency_roots[realpath($path)]) || (realpath($path) === $excluded) || (strtolower($entry) === 'build')
					|| (($compiler) && ($entry === 'tests'))) {
					continue;
				}
				if (is_link($path)) {
					throw new \RuntimeException('Cannot track a symlinked input directory: ' . $path);
				}
				self::scan($path, $files, $excluded, $visited, $compiler, $dependency_roots);
			}
			elseif (is_file($path) && ((!$compiler) || (pathinfo($path, \PATHINFO_EXTENSION) === 'php'))) {
				self::file($path, $files);
			}
		}
	}

	/**
	 * Capture one required input stamp, rejecting missing or unreadable files.
	 *
	 * @param mixed[][] $files
	 */
	private static function file(string $path, array &$files /** array<string,array{mtime:int64,size:uint64}> */): void
	{
		if (isset($files[$path])) {
			return;
		}
		$stat = @stat($path);
		if ($stat === false) {
			throw new \RuntimeException('Cannot stat project input: ' . $path);
		}
		$files[$path] = ['mtime' => $stat['mtime'], 'size' => $stat['size']];
	}
}

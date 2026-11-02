<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Publishes a generated program and its manifest, retiring only obsolete artifacts owned by the previous manifest.
 */
final class Cpp_Bundle
{
	private const FORMAT = 'scpp-cpp-program-v1';

	/** Accept only the artifact paths owned by this bounded publication layout. */
	private static function require_path(string $path): void
	{
		if (preg_match('~\A(?:entry\.cpp|units/[a-f0-9]{64}\.cpp)\z~', $path) !== 1) {
			throw new \RuntimeException('Invalid program artifact path: ' . $path);
		}
	}

	/** Old inventory supplies cleanup ownership; a corrupt inventory never authorizes deletion. */
	public static function read(string $directory): array /** validated manifest; empty when absent */
	{
		$manifest = $directory . '/manifest.json';
		if (is_link($directory) || is_link($manifest) || is_link($directory . '/units')) {
			throw new \RuntimeException('Expected a dedicated nonsymlink program output directory');
		}
		if (!file_exists($manifest)) {
			return [];
		}
		try {
			$data = json_decode((string) file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
		}
		catch (\JsonException $error) {
			throw new \RuntimeException('Invalid program manifest: ' . $error->getMessage());
		}
		if (!is_array($data) || (($data['format'] ?? null) !== self::FORMAT)
			|| !is_array($data['translation_units'] ?? null) || !array_is_list($data['translation_units'])) {
			throw new \RuntimeException('Unsupported program manifest');
		}
		foreach ($data['translation_units'] as $path) {
			if (!is_string($path)) {
				throw new \RuntimeException('Invalid program manifest path');
			}
			self::require_path($path);
		}
		return $data;
	}

	/** Extract a validated baseline from the already loaded manifest. */
	public static function baseline(array $previous /** validated manifest */): ?array /** captured state */
	{
		$state = $previous['build']['inputs'] ?? null;
		return Project_Changes::valid($state) ? $state : null;
	}

	/** Reuse only a successful matching build whose complete generated artifacts are still intact. */
	public static function reuse(string $directory, array $state /** Project_Changes captured state */,
		array $previous /** validated manifest */): ?array /** manifest object shape */
	{
		$path = $directory . '/manifest.json';
		if (is_link($directory) || is_link($path) || is_link($directory . '/units') || !is_file($path)) {
			return null;
		}
		$data = $previous;
		if (($data === []) || (($data['build']['inputs'] ?? null) !== $state)
			|| !is_array($data['build']['artifacts'] ?? null)
			|| !is_array($data['sources'] ?? null)) {
			return null;
		}
		$expected_metadata = $data['build']['metadata'] ?? null;
		$metadata = $data;
		unset($metadata['build']);
		if ($metadata !== $expected_metadata) {
			return null;
		}
		$units = $data['translation_units'];
		if (($units === []) || (array_keys($data['build']['artifacts']) !== $units)) {
			return null;
		}
		foreach ($data['build']['artifacts'] as $unit => $expected) {
			clearstatcache(true, $directory . '/' . $unit);
			$stat = @stat($directory . '/' . $unit);
			if (is_link($directory . '/' . $unit) || !is_file($directory . '/' . $unit)
				|| ($stat === false) || (['mtime' => $stat['mtime'], 'size' => $stat['size']] !== $expected)) {
				return null;
			}
		}
		return $data;
	}

	/** Publish a complete candidate and retire only previously inventoried units; manifest is written last. */
	public static function write(string $directory, cpp_program_output $output,
		array $inputs /** list<string>; protected source and target-facts paths */, ?array $state /** Project_Changes captured state */ = null,
		?array $previous /** validated manifest; null loads it for standalone publication */ = null): void
	{
		$directory = rtrim($directory, '/');
		if (($directory === '') || is_link($directory) || is_link($directory . '/units')
			|| is_link($directory . '/manifest.json')) {
			throw new \RuntimeException('Expected a dedicated nonsymlink program output directory');
		}
		$manifest = $directory . '/manifest.json';
		$previous ??= self::read($directory);
		$old = $previous['translation_units'] ?? [];
		$owned = array_fill_keys($old, true);
		$protected = array_fill_keys(array_filter(array_map('realpath', array_merge($inputs, $output->native_sources))), true);
		$paths = array_unique(array_merge($old, array_keys($output->files)));
		foreach (array_merge(['manifest.json'], $paths) as $path)
		{
			if ($path !== 'manifest.json') {
				self::require_path($path);
			}
			$target = $directory . '/' . $path;
			if (is_link($target) || isset($protected[realpath($target)])) {
				throw new \RuntimeException('Output collides with an input or symlink: ' . $target);
			}
			if (($path !== 'manifest.json') && file_exists($target) && !isset($owned[$path])) {
				throw new \RuntimeException('Unowned program output collision: ' . $target);
			}
		}
		$data = ['format' => self::FORMAT, 'entry' => $output->entry_path,
			'sources' => (object) $output->source_units, 'translation_units' => array_keys($output->files)];
		if ($output->native_sources !== []) {
			$data['native_sources'] = $output->native_sources;
		}
		if (file_exists($manifest) && !unlink($manifest)) {
			throw new \RuntimeException('Cannot invalidate program manifest');
		}
		foreach ($output->files as $path => $contents) {
			Cpp_Publication::write($directory . '/' . $path, $contents);
		}
		foreach ($old as $path) {
			if (!isset($output->files[$path]) && file_exists($directory . '/' . $path)
				&& !unlink($directory . '/' . $path)) {
				throw new \RuntimeException('Cannot retire obsolete program unit: ' . $path);
			}
		}
		if ($state !== null)
		{
			$artifacts = [];
			foreach ($output->files as $path => $_) {
				clearstatcache(true, $directory . '/' . $path);
				$stat = stat($directory . '/' . $path);
				if ($stat === false) {
					throw new \RuntimeException('Cannot stat published artifact: ' . $path);
				}
				$artifacts[$path] = ['mtime' => $stat['mtime'], 'size' => $stat['size']];
			}
			$data['build'] = ['inputs' => $state, 'metadata' => $data, 'artifacts' => $artifacts];
		}
		$text = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n";
		Cpp_Publication::write($manifest, $text);
	}
}

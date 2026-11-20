<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Keeps the validated manifest and pins its inode until a candidate writer checks it. */
final readonly class cpp_bundle_snapshot
{
	/** @var mixed[] Validated manifest, empty when no bundle has been published. */
	public array $manifest /** array<string,mixed> */;
	/** @var resource|null Owns an open handle to the manifest that provided the inventory. */
	private mixed $handle;

	/** @param mixed[] $manifest @param resource|null $handle */
	public function __construct(array $manifest /** array<string,mixed> */, mixed $handle)
	{
		$this->manifest = $manifest;
		$this->handle = $handle;
	}

	/** Called under the exclusive bundle lock. The pinned inode cannot be reused while this handle is open. */
	public function matches(string $path): bool
	{
		if ($this->handle === null) return !file_exists($path);
		$saved = fstat($this->handle);
		$current = @stat($path);
		return $saved !== false && $current !== false
			&& $saved['dev'] === $current['dev'] && $saved['ino'] === $current['ino'];
	}

	public function __destruct()
	{
		if ($this->handle !== null) fclose($this->handle);
	}

	/** The manifest stream has one owner. */
	private function __clone()
	{
	}
}

/** Publishes a generated program and its manifest, retiring only obsolete artifacts owned by the previous manifest. */
final class Cpp_Bundle
{
	private const FORMAT = 'scpp-cpp-program-v2';

	/**
	 * Hold one read reservation until all uses of the manifest's artifacts finish.
	 * The callback receives false for an output root that has never been created.
	 */
	public static function with_reader(string $directory, \Closure $read): mixed
	{
		clearstatcache(true);
		if (!is_dir($directory) && !is_link($directory) && !file_exists($directory)) return $read(false);
		return self::with_lock($directory, false, static fn() => $read(true));
	}

	/** Serialize access on a persistent lock inode in the bundle, separate from the Ninja directory. */
	private static function with_lock(string $directory, bool $writing, \Closure $operation): mixed
	{
		clearstatcache(true);
		if (is_link($directory) || !is_dir($directory)) {
			throw new \RuntimeException('Expected a dedicated nonsymlink program output directory');
		}
		$path = $directory . '/.scpp-bundle.lock';
		if (is_link($path) || (file_exists($path) && !is_file($path))) {
			throw new \RuntimeException('Invalid program bundle lock');
		}
		$lock = @fopen($path, !$writing && is_file($path) ? 'r' : 'c');
		if ($lock === false) throw new \RuntimeException('Cannot open program bundle lock');
		try {
			if (!flock($lock, $writing ? \LOCK_EX : \LOCK_SH)) {
				throw new \RuntimeException('Cannot lock program bundle');
			}
			clearstatcache(true);
			return $operation();
		}
		finally {
			fclose($lock);
		}
	}

	/** Accept only the artifact paths owned by this bounded publication layout. */
	private static function require_path(string $path): void
	{
		if (preg_match('~\A(?:entry\.cpp|units/[a-f0-9]{64}\.(?:cpp|hpp))\z~', $path) !== 1) {
			throw new \RuntimeException('Invalid program artifact path: ' . $path);
		}
	}

	/** Read and pin the same validated manifest while the caller holds its reservation. */
	public static function snapshot(string $directory): cpp_bundle_snapshot
	{
		$manifest = $directory . '/manifest.json';
		if (is_link($directory) || is_link($manifest) || is_link($directory . '/units')) {
			throw new \RuntimeException('Expected a dedicated nonsymlink program output directory');
		}
		if (!file_exists($manifest)) return new cpp_bundle_snapshot([], null);
		$handle = is_file($manifest) ? @fopen($manifest, 'rb') : false;
		if ($handle === false) throw new \RuntimeException('Cannot open program bundle manifest');
		try {
			$text = stream_get_contents($handle);
			if ($text === false) throw new \RuntimeException('Cannot read program bundle manifest');
			return new cpp_bundle_snapshot(self::decode_manifest($text), $handle);
		}
		catch (\Throwable $error) {
			fclose($handle);
			throw $error;
		}
	}

	/** Return the validated inventory without retaining a pin. @return mixed[] */
	public static function read(string $directory): array /** array<string,mixed> */
	{
		return self::snapshot($directory)->manifest;
	}

	/** A corrupt inventory cannot authorize deletion. @return mixed[] */
	private static function decode_manifest(string $text): array /** array<string,mixed> */
	{
		try {
			$data = json_decode($text, true, 512, \JSON_THROW_ON_ERROR);
		}
		catch (\JsonException $error) {
			throw new \RuntimeException('Invalid program manifest: ' . $error->getMessage());
		}
		if ((!is_array($data)) || !in_array($data['format'] ?? null, [self::FORMAT, 'scpp-cpp-program-v1'], true)
			|| (!is_array($data['translation_units'] ?? null)) || (!array_is_list($data['translation_units']))) {
			throw new \RuntimeException('Unsupported program manifest');
		}
		if (!is_array($data['headers'] ?? []) || !array_is_list($data['headers'] ?? [])) {
			throw new \RuntimeException('Invalid header inventory');
		}
		foreach (['cpp' => $data['translation_units'], 'hpp' => $data['headers'] ?? []] as $extension => $paths) {
			foreach ($paths as $path) {
				if (!is_string($path) || !str_ends_with($path, '.' . $extension)) {
					throw new \RuntimeException('Invalid program manifest path');
				}
				self::require_path($path);
			}
		}
		return $data;
	}

	/**
	 * Extract a validated baseline from the already loaded manifest.
	 *
	 * $previous: validated manifest.
	 * Return: captured state.
	 *
	 * @param mixed[] $previous
	 * @return mixed[]|null
	 */
	public static function baseline(array $previous /** array<string,mixed> */): ?array /** array<string,mixed> */
	{
		$state = $previous['build']['inputs'] ?? null;
		return Project_Changes::valid($state) ? $state : null;
	}

	/**
	 * Reuse only a successful matching build whose complete generated artifacts are still intact.
	 *
	 * $state: Project_Changes captured state.
	 * $previous: validated manifest.
	 * Return: manifest object shape.
	 *
	 * @param mixed[] $state
	 * @param mixed[] $previous
	 * @return mixed[]|null
	 */
	public static function reuse(string $directory, array $state /** array<string,mixed> */,
		array $previous /** array<string,mixed> */): ?array /** array<string,mixed> */
	{
		$path = $directory . '/manifest.json';
		if (is_link($directory) || is_link($path) || is_link($directory . '/units') || (!is_file($path))) {
			return null;
		}
		$data = $previous;
		if (($data === []) || (($data['format'] ?? null) !== self::FORMAT) || (($data['build']['inputs'] ?? null) !== $state)
			|| (!is_array($data['build']['artifacts'] ?? null))
			|| (!is_array($data['sources'] ?? null))) {
			return null;
		}
		$expected_metadata = $data['build']['metadata'] ?? null;
		$metadata = $data;
		unset($metadata['build']);
		if ($metadata !== $expected_metadata) {
			return null;
		}
		$units = array_merge($data['translation_units'], $data['headers'] ?? []);
		if (($units === []) || (array_keys($data['build']['artifacts']) !== $units)) {
			return null;
		}
		foreach ($data['build']['artifacts'] as $unit => $expected)
		{
			clearstatcache(true, $directory . '/' . $unit);
			$stat = @stat($directory . '/' . $unit);
			if (is_link($directory . '/' . $unit) || (!is_file($directory . '/' . $unit))
				|| ($stat === false) || (['mtime' => $stat['mtime'], 'size' => $stat['size']] !== $expected)) {
				return null;
			}
		}
		return $data;
	}

	/**
	 * Publish a complete candidate and retire only previously inventoried units; manifest is written
	 * last.
	 *
	 * $inputs: protected source and target-facts paths.
	 * $state: Project_Changes captured state.
	 * $previous: pinned snapshot or validated inventory; null loads it for standalone publication.
	 *
	 * @param string[] $inputs
	 * @param mixed[]|null $state
	 * @param mixed[]|cpp_bundle_snapshot|null $previous
	 */
	public static function write(string $directory, cpp_program_output $output,
		array $inputs /** list<string> */, ?array $state /** array<string,mixed> */ = null,
		array|cpp_bundle_snapshot|null $previous /** array<string,mixed> */ = null): void
	{
		$directory = rtrim($directory, '/');
		if (($directory === '') || is_link($directory) || is_link($directory . '/units')
			|| is_link($directory . '/manifest.json')) {
			throw new \RuntimeException('Expected a dedicated nonsymlink program output directory');
		}
		if (!is_dir($directory) && !@mkdir($directory, 0777, true) && !is_dir($directory)) {
			throw new \RuntimeException('Cannot create output directory: ' . $directory);
		}
		self::with_lock($directory, true, static function () use ($directory, $output, $inputs, $state, $previous): void {
			$manifest = $directory . '/manifest.json';
			if (is_link($manifest) || is_link($directory . '/units')) {
				throw new \RuntimeException('Expected a dedicated nonsymlink program output directory');
			}
			if ($previous instanceof cpp_bundle_snapshot) {
				$matches = $previous->matches($manifest);
				$current = $previous->manifest;
			}
			else {
				$current = self::read($directory);
				$matches = $previous === null || $previous === $current;
			}
			if (!$matches) throw new \RuntimeException('Program bundle changed during compilation: retry the build');
			self::write_locked($directory, $output, $inputs, $state, $current);
		});
	}

	/** The exclusive reservation spans staging, manifest commit, and cleanup of the previous files. */
	private static function write_locked(string $directory, cpp_program_output $output,
		array $inputs /** list<string> */, ?array $state /** array<string,mixed> */,
		array $previous /** array<string,mixed> */): void
	{
		$manifest = $directory . '/manifest.json';
		$old = array_merge($previous['translation_units'] ?? [], $previous['headers'] ?? []);
		$owned = array_fill_keys($old, true);
		$protected = array_fill_keys(array_filter(array_map('realpath', array_merge($inputs, $output->native_sources))),
			true);
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
			if (file_exists($target) && !is_file($target)) {
				throw new \RuntimeException('Cannot publish C++ output: ' . $target);
			}
			if (($path !== 'manifest.json') && file_exists($target) && (!isset($owned[$path]))) {
				throw new \RuntimeException('Unowned program output collision: ' . $target);
			}
		}
		$data = ['format' => self::FORMAT, 'entry' => $output->entry_path,
			'sources' => (object) $output->source_units, 'translation_units' => $output->translation_units(), 'headers' => $output->headers()];
		if ($output->link_inputs !== []) {
			$data['link_inputs'] = $output->link_inputs;
		}
		if ($output->native_sources !== []) {
			$data['native_sources'] = $output->native_sources;
		}
		$staged = [];
		$backups = [];
		$undo = [];
		try {
			foreach ($output->files as $path => $contents) {
				$target = $directory . '/' . $path;
				$temporary = Cpp_Publication::stage($target, $contents);
				if ($temporary !== null) $staged[$target] = $temporary;
			}
			if ($state !== null) {
				$artifacts = [];
				foreach (array_keys($output->files) as $path) {
					$target = $directory . '/' . $path;
					clearstatcache(true, $target);
					$stat = @stat($staged[$target] ?? $target);
					if ($stat === false) {
						throw new \RuntimeException('Cannot stat staged artifact: ' . $path);
					}
					$artifacts[$path] = ['mtime' => $stat['mtime'], 'size' => $stat['size']];
				}
				$data['build'] = ['inputs' => $state, 'metadata' => $data, 'artifacts' => $artifacts];
			}
			$text = json_encode($data, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT) . "\n";
			$manifest_stage = Cpp_Publication::stage($manifest, $text, false);
			// Reserve old files for rollback, including files withdrawn from the new inventory.
			$withdrawn = array_diff($old, array_keys($output->files));
			$targets = array_merge(array_keys($staged),
				array_map(static fn(string $path): string => $directory . '/' . $path, $withdrawn));
			$staged[$manifest] = $manifest_stage;
			foreach ($targets as $target) {
				if (is_file($target)) {
					$backup = Cpp_Publication::reserve($target);
					$backups[$target] = $backup;
					Cpp_Publication::replace($target, $backup);
					$undo[$target] = $backup;
				}
				if (isset($staged[$target])) {
					Cpp_Publication::replace($staged[$target], $target);
					$undo[$target] ??= null;
					unset($staged[$target]);
				}
			}
			Cpp_Publication::replace($manifest_stage, $manifest);
			unset($staged[$manifest]);
		}
		catch (\Throwable $error) {
			$failed = [];
			foreach (array_reverse($undo, true) as $target => $backup) {
				$restored = $backup === null ? @unlink($target) : @rename($backup, $target);
				if (!$restored) {
					$failed[] = $backup ?? $target;
					unset($backups[$target]); // Preserve a backup when rollback could not restore it.
				}
			}
			if ($failed !== []) {
				throw new \RuntimeException('Cannot roll back program bundle: ' . implode(', ', $failed), previous: $error);
			}
			throw $error;
		}
		finally {
			foreach (array_merge(array_values($staged), array_values($backups)) as $temporary) {
				if (is_file($temporary)) @unlink($temporary);
			}
		}
	}
}

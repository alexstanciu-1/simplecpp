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
	private static function previous(string $manifest): array /** list<string> */
	{
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
		return $data['translation_units'];
	}

	/** Publish a complete candidate and retire only previously inventoried units; manifest is written last. */
	public static function write(string $directory, cpp_program_output $output,
		array $inputs /** list<string>; protected source and target-facts paths */): void
	{
		$directory = rtrim($directory, '/');
		if (($directory === '') || is_link($directory) || is_link($directory . '/units')
			|| is_link($directory . '/manifest.json')) {
			throw new \RuntimeException('Expected a dedicated nonsymlink program output directory');
		}
		$manifest = $directory . '/manifest.json';
		$old = self::previous($manifest);
		$owned = array_fill_keys($old, true);
		$protected = array_fill_keys(array_filter(array_map('realpath', $inputs)), true);
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
		$text = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n";
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
		Cpp_Publication::write($manifest, $text);
	}
}

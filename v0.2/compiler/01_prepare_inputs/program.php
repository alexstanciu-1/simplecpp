<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Validates root-relative project inventory and entry selection before individual source files are read.
 */
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

	/** Validate the complete source inventory before loading its contents. */
	public static function inventory(string $root, string $entry,
		array $requests /** array<string,flavor> */): array /** array<string,source_input> */
	{
		self::require_relative($entry);
		if (!isset($requests[$entry])) {
			throw new \RuntimeException('Selected entry is not in the source inventory: ' . $entry);
		}
		$directory = realpath($root);
		if (($directory === false) || !is_dir($directory)) {
			throw new \RuntimeException('Source root is not a directory: ' . $root);
		}
		$sources = [];
		$physical = [];
		foreach ($requests as $path => $flavor)
		{
			$path = (string) $path;
			self::require_relative($path);
			$resolved = realpath($directory . '/' . $path);
			if (($resolved === false) || !is_file($resolved)
				|| !str_starts_with($resolved, rtrim($directory, '/') . '/')) {
				throw new \RuntimeException('Source must exist within its root: ' . $path);
			}
			if (isset($physical[$resolved])) {
				throw new \RuntimeException('Duplicate physical source: ' . $path);
			}
			$physical[$resolved] = true;
			$sources[$path] = new source_input($resolved, $flavor);
		}
		return $sources;
	}
}

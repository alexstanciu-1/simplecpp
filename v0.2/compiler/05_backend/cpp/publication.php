<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Publishes one complete generated text artifact through a staged file and atomic rename. */
final class Cpp_Publication
{
	/** Preserve identical text; commit-marker writes can skip the extra content read. */
	public static function write(string $path, string $text, bool $compare_existing = true): void
	{
		$temporary = self::stage($path, $text, $compare_existing);
		if ($temporary === null) return;
		try {
			self::replace($temporary, $path);
		}
		finally {
			if (is_file($temporary)) unlink($temporary);
		}
	}

	/** Caller owns the complete staged file on return. Null means the final file is already identical. */
	public static function stage(string $path, string $text, bool $compare_existing = true): ?string
	{
		if ($compare_existing && is_file($path) && !is_link($path) && (file_get_contents($path) === $text)) {
			return null;
		}
		$directory = dirname($path);
		if ((!is_dir($directory)) && (!@mkdir($directory, 0777, true)) && !is_dir($directory)) {
			throw new \RuntimeException('Cannot create output directory: ' . $directory);
		}
		$temporary = self::reserve($path);
		try {
			if (@file_put_contents($temporary, $text) !== strlen($text)) {
				throw new \RuntimeException('Cannot write complete C++ output: ' . $path);
			}
			return $temporary;
		}
		catch (\Throwable $error) {
			@unlink($temporary);
			throw $error;
		}
	}

	/** Keep temporary files on the same filesystem as their destination. */
	public static function reserve(string $path): string
	{
		$directory = realpath(dirname($path));
		if ($directory === false) {
			throw new \RuntimeException('Cannot resolve output directory: ' . dirname($path));
		}
		$temporary = @tempnam($directory, '.scpp-entry-');
		if ($temporary === false) {
			throw new \RuntimeException('Cannot create output staging file: ' . $path);
		}
		if (dirname($temporary) !== $directory) {
			@unlink($temporary);
			throw new \RuntimeException('Cannot stage output in its destination directory: ' . $path);
		}
		return $temporary;
	}

	public static function replace(string $from, string $to): void
	{
		if (!@rename($from, $to)) throw new \RuntimeException('Cannot publish C++ output: ' . $to);
	}
}

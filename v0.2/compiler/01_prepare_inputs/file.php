<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Loads one source file into an immutable snapshot with the explicitly selected syntax flavor.
 */
final class File_Loader
{
	public static function load(string $path, flavor $flavor): source_file
	{
		if (!is_file($path)) {
			throw new \RuntimeException('Cannot read source file: ' . $path);
		}
		$content = @file_get_contents($path);
		if ($content === false) {
			throw new \RuntimeException('Cannot read source file: ' . $path);
		}
		return new source_file($path, $content, $flavor);
	}
}

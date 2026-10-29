<?php

declare(strict_types=1);
namespace scpp\compiler;

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

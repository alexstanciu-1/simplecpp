<?php

/*
 * Role: load metadata and source bytes into a file record.
 * Call map: Module_Loader::init -> File_Loader::init.
 * Filesystem calls remain PHP host operations pending conversion review.
 */
namespace scpp\compiler;

final class File_Loader
{
	/** Load the file's metadata and source content. */
	public static function init(file $file, string $path): void
	{
		clearstatcache(true, $path);
		/** @var array<int|string, int>|false $stat PHP stat result, including failure. */
		$stat = stat($path);
		if ($stat === false) {
			throw new \RuntimeException("Cannot read file metadata: $path");
		}

		/** @var string|false $read_result PHP read result, including failure. */
		$read_result = file_get_contents($path);
		if ($read_result === false) {
			throw new \RuntimeException("Cannot read file content: $path");
		}
		$content /** string */ = $read_result;

		$file->path = $path;
		$file->mtime = $stat['mtime'];
		$file->size = $stat['size'];
		$file->content = $content;
	}
}

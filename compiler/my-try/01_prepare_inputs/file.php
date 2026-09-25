<?php

/*
 * Role: load metadata and source bytes into a file record.
 * Call map: Tokenizer::tokenize -> File_Loader::init.
 * Gather metadata and bytes before assigning the loaded fields.
 */
namespace scpp\compiler;

final class File_Loader
{
	/** Populate required fields after successful reads; invalidate any previous token backlink. */
	public static function init(file $file, string $path): void
	{
		$mtime = 0;
		$size = 0;
		if (!take_false($mtime, fs_mtime($path))) {
			throw new \RuntimeException('Cannot read file metadata: ' . $path);
		}
		if (!take_false($size, fs_size($path))) {
			throw new \RuntimeException('Cannot read file metadata: ' . $path);
		}
		$content = fs_read_text($path);

		$file->path = $path;
		$file->mtime = $mtime;
		$file->size = $size;
		$file->content = $content;
		$file->tokens = null;
	}
}

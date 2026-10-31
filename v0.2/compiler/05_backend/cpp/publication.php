<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Publishes one complete generated text artifact through a staged file and atomic rename.
 */
final class Cpp_Publication
{
	/** Replace one complete artifact atomically; a failed write leaves the previous artifact intact. */
	public static function write(string $path, string $text): void
	{
		$directory = dirname($path);
		if (!is_dir($directory) && !@mkdir($directory, 0777, true)) {
			throw new \RuntimeException('Cannot create output directory: ' . $directory);
		}
		$temporary = tempnam($directory, '.scpp-entry-');
		if ($temporary === false) {
			throw new \RuntimeException('Cannot create output staging file');
		}
		try
		{
			if (@file_put_contents($temporary, $text) !== strlen($text)) {
				throw new \RuntimeException('Cannot write complete C++ output');
			}
			if (!@rename($temporary, $path)) {
				throw new \RuntimeException('Cannot publish C++ output: ' . $path);
			}
		}
		finally {
			if (is_file($temporary)) {
				unlink($temporary);
			}
		}
	}
}

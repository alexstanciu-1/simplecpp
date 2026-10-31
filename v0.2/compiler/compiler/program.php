<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Coordinates source loading, local parsing and global symbol publication before whole-program preparation.
 */
final class Program_Frontend
{
	/** Complete each source-local read/scan/parse/collect job before entering whole-program preparation. */
	public function prepare_files(string $entry_path, array $inputs /** array<string,source_input> */,
		entry_target $target): prepared_program
	{
		$files = [];
		$frontend = new Source_Frontend();
		$publication = new Symbol_Publication();
		foreach ($inputs as $path => $input) {
			$files[$path] = $frontend->collect_file($input->path, $input->flavor, $publication);
		}
		return Program_Preparation::prepare($entry_path, $files, $target, $publication->publish($files));
	}

	/** Each source is parsed and collected once before whole-program semantic admission. */
	public function prepare(program_sources $sources, entry_target $target): prepared_program
	{
		$files = [];
		$frontend = new Source_Frontend();
		$publication = new Symbol_Publication();
		foreach ($sources->sources as $path => $source) {
			$files[$path] = $frontend->collect($source, $publication);
		}
		return Program_Preparation::prepare($sources->entry_path, $files, $target, $publication->publish($files));
	}
}

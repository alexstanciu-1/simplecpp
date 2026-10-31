<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Adapts the program CLI arguments into source inventory, preparation, C++ generation and bundle publication.
 */
final class Program_Command
{
	/** Explicit CLI inventory is a bounded input adapter, not a project-config or discovery system. */
	public static function run(array $arguments /** list<string> */): int /** int32; process exit status */
	{
		if ((count($arguments) < 8) || (((count($arguments) - 6) % 2) !== 0)) {
			fwrite(STDERR, "Usage: php main.php program <root> <entry-relative> <facts.json> <output-dir> <flavor> <source-relative> [...]\n");
			return 2;
		}
		$requests = [];
		for ($index = 6; $index < count($arguments); $index += 2)
		{
			$flavor = flavor::tryFrom($arguments[$index]);
			if ($flavor === null) {
				throw new \RuntimeException('Flavor must be cpp, php or js');
			}
			$path = $arguments[$index + 1];
			Program_Loader::require_relative($path);
			if (isset($requests[$path])) {
				throw new \RuntimeException('Duplicate source inventory entry: ' . $path);
			}
			$requests[$path] = $flavor;
		}
		$sources = Program_Loader::inventory($arguments[2], $arguments[3], $requests);
		$target = Native_Target::load($arguments[4]);
		$program = (new Program_Frontend())->prepare_files($arguments[3], $sources, $target);
		$output = Cpp_Program::emit($program);
		$inputs = [$arguments[4]];
		foreach ($sources as $source) {
			$inputs[] = $source->path;
		}
		Cpp_Bundle::write($arguments[5], $output, $inputs);
		fwrite(STDOUT, json_encode(['manifest' => $arguments[5] . '/manifest.json',
			'sources' => count($sources), 'translation_units' => count($output->files)],
			JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
		return 0;
	}
}

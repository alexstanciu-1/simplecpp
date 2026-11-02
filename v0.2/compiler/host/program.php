<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Adapts the program CLI arguments into source inventory, preparation, C++ generation and bundle publication.
 */
final class Program_Command
{
	/** Load an on-disk module graph and emit compiler units plus external native inputs. */
	public static function project(array $arguments /** list<string> */): int /** int32; process exit status */
	{
		if (count($arguments) !== 5) {
			fwrite(STDERR, "Usage: php main.php project <module-dir|module.json> <facts.json> <output-dir>\n");
			return 2;
		}
		$session = (new Module_Loader())->load($arguments[2]);
		return self::publish($session, $arguments[3], $arguments[4]);
	}

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
		$session = Program_Loader::inventory($arguments[2], $arguments[3], $requests);
		return self::publish($session, $arguments[4], $arguments[5]);
	}

	/** Both input adapters publish through the same preparation and bundle path. */
	private static function publish(compilation_session $session, string $facts, string $directory): int /** int32 */
	{
		$target = Native_Target::load($facts);
		$program = (new Program_Frontend())->prepare($session, $target);
		$output = Cpp_Program::emit($program);
		$inputs = [$facts];
		foreach ($session->modules as $module) {
			if ($module->manifest !== null) {
				$inputs[] = $module->manifest;
			}
		}
		foreach ($program->files as $file) {
			$inputs[] = $file->tokens->source->path;
		}
		Cpp_Bundle::write($directory, $output, $inputs);
		fwrite(STDOUT, json_encode(['manifest' => $directory . '/manifest.json',
			'sources' => count($program->files), 'translation_units' => count($output->files)],
			JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
		return 0;
	}
}

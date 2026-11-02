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
		if ((count($arguments) !== 5) && !((count($arguments) === 6) && ($arguments[5] === '--force'))) {
			fwrite(STDERR, "Usage: php main.php project <module-dir|module.json> <facts.json> <output-dir> [--force]\n");
			return 2;
		}
		return self::report((new Project_Compiler())->run($arguments[2], $arguments[3], $arguments[4], count($arguments) === 6));
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
		return self::report((new Project_Compiler())->compile($session, $arguments[4], $arguments[5]));
	}

	/** Host presentation is separate from compilation and remains compatible with existing CLI consumers. */
	private static function report(array $result /** compilation report */): int /** int32; process exit status */
	{
		if (isset($result['changes'])) {
			$result['changes'] = (object) $result['changes'];
		}
		fwrite(STDOUT, json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
		return 0;
	}
}

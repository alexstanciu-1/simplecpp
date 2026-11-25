<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Adapts the program CLI arguments into source inventory, preparation, C++ generation and bundle publication. */
final class Program_Command
{
	/**
	 * Emit and build in this PHP process; source-only project/program commands retain their existing contract.
	 * @param string[] $arguments
	 */
	public static function build(array $arguments /** list<string> */): int /** int32 */
	{
		$options = self::options(array_slice($arguments, 7));
		if (count($arguments) < 7 || $options === null || $options['force']) {
			fwrite(\STDERR, "Usage: php main.php build <module> <facts.json> <generated-dir> <native-dir> <toolchain.json> [--optimize-function=NAME ...]\n");
			return 2;
		}
		require_once __DIR__ . '/native_build.php';
		$config = Native_Build::configuration($arguments[6]);
		$report = (new Project_Compiler())->run($arguments[2], $arguments[3], $arguments[4], false, $options['functions']);
		$report['native'] = Native_Build::run($arguments[4], $arguments[5], $config);
		self::report($report);
		return $report['native']['exit_code'] === 0 ? 0 : 1;
	}

	/**
	 * Load an on-disk module graph and emit compiler units plus external native inputs.
	 *
	 * Return: process exit status.
	 *
	 * @param string[] $arguments
	 */
	public static function project(array $arguments /** list<string> */): int /** int32 */
	{
		$options = self::options(array_slice($arguments, 5));
		if (count($arguments) < 5 || $options === null) {
			fwrite(\STDERR,
				"Usage: php main.php project <module-dir|module.json> <facts.json> <output-dir> [--force] [--optimize-function=NAME ...]\n");
			return 2;
		}
		return self::report((new Project_Compiler())->run($arguments[2], $arguments[3], $arguments[4], $options['force'], $options['functions']));
	}

	/** Parse optional body selections shared by both project entry points.
	 * @param string[] $flags
	 * @return array{force:bool,functions:list<string>}|null
	 */
	private static function options(array $flags): ?array
	{
		$force = false;
		$functions = [];
		foreach ($flags as $flag) {
			if ($flag === '--force' && !$force) $force = true;
			elseif (str_starts_with($flag, '--optimize-function=') && ($name = substr($flag, strlen('--optimize-function='))) !== '') $functions[] = $name;
			else return null;
		}
		return ['force' => $force, 'functions' => $functions];
	}

	/**
	 * Explicit CLI inventory is a bounded input adapter, not a project-config or discovery system.
	 *
	 * Return: process exit status.
	 *
	 * @param string[] $arguments
	 */
	public static function run(array $arguments /** list<string> */): int /** int32 */
	{
		if ((count($arguments) < 8) || (((count($arguments) - 6) % 2) !== 0)) {
			fwrite(\STDERR,
				"Usage: php main.php program <root> <entry-relative> <facts.json> <output-dir> <flavor> <source-relative> [...]\n");
			return 2;
		}
		$requests = [];
		for ($index = 6; $index < count($arguments); $index += 2)
		{
			$flavor = flavor::tryFrom($arguments[$index]);
			if ($flavor === null) {
				throw new \RuntimeException('Flavor must be cpp, php, js or py');
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

	/**
	 * Host presentation is separate from compilation and remains compatible with existing CLI
	 * consumers.
	 *
	 * $result: compilation report.
	 * Return: process exit status.
	 *
	 * @param mixed[] $result
	 */
	private static function report(array $result /** array<string,mixed> */): int /** int32 */
	{
		if (isset($result['changes'])) {
			$result['changes'] = (object) $result['changes'];
		}
		fwrite(\STDOUT, json_encode($result, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT) . "\n");
		return 0;
	}
}

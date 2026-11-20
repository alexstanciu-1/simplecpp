<?php

declare(strict_types=1);
namespace scpp\compiler;

require_once __DIR__ . '/qualification.php';

/** Owns the bounded Linux native-build invocation: bundle inputs, toolchain, qualification gate and Ninja. */
final class Native_Build
{
	private const CONTEXT_STAMP = '.scpp-native-context';

	/**
	 * Read an explicit host configuration; relative flag operands are interpreted in the native build directory.
	 * @return mixed[]
	 */
	public static function configuration(string $path): array /** array<string,mixed> */
	{
		$text = @file_get_contents($path);
		if ($text === false) {
			throw new \RuntimeException('Cannot read native toolchain configuration: ' . $path);
		}
		try {
			$config = json_decode($text, true, 512, \JSON_THROW_ON_ERROR);
		}
		catch (\JsonException $error) {
			throw new \RuntimeException('Invalid native toolchain configuration: ' . $error->getMessage());
		}
		if (!is_array($config)) {
			throw new \RuntimeException('Expected native toolchain configuration object');
		}
		return $config;
	}

	/**
	 * Call only after successful C++ publication. A retained compiler calls this directly, with no helper process.
	 * Return native status/output; never expose an old executable as a successful failed build.
	 * @param mixed[] $config
	 * @return mixed[]
	 */
	public static function run(string $bundle, string $directory, array $config /** array<string,mixed> */): array /** array<string,mixed> */
	{
		$bundle = realpath($bundle);
		if ($bundle === false) {
			throw new \RuntimeException('Missing generated bundle');
		}
		return Cpp_Bundle::with_reader($bundle, static function (bool $present) use ($bundle, $directory, $config): array {
			if (!$present) throw new \RuntimeException('Missing generated bundle');
			return self::run_with_bundle($bundle, $directory, $config);
		});
	}

	/** The reader reservation spans manifest interpretation and all Ninja reads. */
	private static function run_with_bundle(string $bundle, string $directory,
		array $config /** array<string,mixed> */): array /** array<string,mixed> */
	{
		$manifest = Cpp_Bundle::read($bundle);
		if (($manifest['translation_units'] ?? []) === []) {
			throw new \RuntimeException('Native build requires a published executable bundle');
		}
		$units = array_map(static fn(string $path): string => $bundle . '/' . $path, $manifest['translation_units']);
		$units = array_merge($units, self::strings($manifest['native_sources'] ?? [], 'native_sources'));
		$libraries = self::strings($manifest['link_inputs'] ?? [], 'link_inputs');
		foreach (array_merge($units, $libraries) as $path) {
			if (!str_starts_with($path, '/') || !is_file($path)) {
				throw new \RuntimeException('Missing absolute native input: ' . $path);
			}
		}
		if (array_diff(array_keys($config), ['cxx', 'flags', 'link_flags', 'cache', 'ninja', 'python', 'jobs']) !== []) {
			throw new \RuntimeException('Unknown native toolchain configuration field');
		}
		$flags = self::strings($config['flags'] ?? null, 'flags');
		$link_flags = self::strings($config['link_flags'] ?? [], 'link_flags');
		foreach ($flags as $flag) {
			if (($flag === '-include-pch') || str_starts_with($flag, '-include-pch=') || ($flag === '-fpch-preprocess')) {
				throw new \RuntimeException('PCH orchestration is deferred in the PHP native-build entry point');
			}
		}
		$jobs = $config['jobs'] ?? 4;
		if (!is_int($jobs) || ($jobs < 1) || ($jobs > 1024)) {
			throw new \RuntimeException('Native jobs must be in 1..1024');
		}
		if (!is_dir($directory) && !@mkdir($directory, 0777, true)) {
			throw new \RuntimeException('Cannot create native build directory');
		}
		$directory = realpath($directory);
		$previous = getcwd();
		if (($directory === false) || ($previous === false) || !chdir($directory)) {
			throw new \RuntimeException('Cannot enter native build directory');
		}
		try {
			return self::build($units, $libraries, $flags, $link_flags, $config, $directory, $jobs);
		}
		finally {
			chdir($previous);
		}
	}

	/**
	 * Resolve and use the same command context for cache lookup, probe and compilation. Publish only protected graphs.
	 * @param string[] $units
	 * @param string[] $libraries
	 * @param string[] $flags
	 * @param string[] $link_flags
	 * @param mixed[] $config
	 * @return mixed[]
	 */
	private static function build(array $units /** list<string> */, array $libraries /** list<string> */,
		array $flags /** list<string> */, array $link_flags /** list<string> */, array $config /** array<string,mixed> */,
		string $directory, int $jobs /** uint32 */): array /** array<string,mixed> */
	{
		$cxx = self::executable(self::string($config['cxx'] ?? null, 'cxx'));
		$ninja = self::executable(self::string($config['ninja'] ?? 'ninja', 'ninja'));
		$python = self::string($config['python'] ?? 'python3', 'python');
		$cache = self::string($config['cache'] ?? $directory . '/qualification', 'cache');
		if (!str_starts_with($cache, '/')) {
			$cache = $directory . '/' . $cache;
		}
		$probe = [$python, '-B', dirname(__DIR__, 2) . '/tools/layout_probe/qualification.py', '--cxx', $cxx, '--cache', $cache,
			'--context-stamp', self::CONTEXT_STAMP];
		foreach ($flags as $flag) {
			$probe[] = '--flag=' . $flag;
		}
		foreach ($link_flags as $flag) {
			$probe[] = '--link-flag=' . $flag;
		}
		$gate = 'build ' . self::CONTEXT_STAMP . ': qualify force-qualification';
		$lines = ['ninja_required_version = 1.10', 'rule qualify', '  command = ' . self::shell($probe),
			'  restat = 1', 'build force-qualification: phony', $gate, 'rule compile',
			'  command = ' . self::shell([$cxx, ...$flags]) . ' -MD -MF $out.d -c $in -o $out',
			'  depfile = $out.d', '  deps = gcc', 'rule link',
			'  command = ' . self::shell([$cxx, ...$flags]) . ' $in ' . self::shell($link_flags) . ' -o $out'];
		$objects = [];
		foreach (array_unique($units) as $unit) {
			$obj = 'unit-' . hash('sha256', $unit) . '.o';
			$objects[] = $obj;
			$lines[] = 'build ' . $obj . ': compile ' . self::ninja_path($unit) . ' | ' . self::CONTEXT_STAMP;
		}
		$lines[] = 'build program: link ' . implode(' ', $objects)
			. ' ' . implode(' ', array_map(self::ninja_path(...), $libraries)) . ' | ' . self::CONTEXT_STAMP;
		$lines[] = 'default program';
		$graph = implode("\n", $lines) . "\n";
		// Serializes publication and invocation for this build directory, including cold qualification.
		$lock = fopen($directory . '/.scpp-native.lock', 'c');
		if (($lock === false) || !flock($lock, \LOCK_EX)) {
			throw new \RuntimeException('Cannot lock native build directory');
		}
		$temporary = null;
		try
		{
			Cpp_Publication::write($directory . '/build.ninja', $graph);
			$start = hrtime(true);
			$key = Qualification_Cache::lookup($cxx, $flags, $link_flags, $cache);
			$lookup_ms = (hrtime(true) - $start) / 1e6;
			$invocation = 'build.ninja';
			if ($key !== null)
			{
				Cpp_Publication::write($directory . '/' . self::CONTEXT_STAMP, $key . "\n");
				$temporary = tempnam($directory, '.scpp-qualified-');
				if ($temporary === false) {
					throw new \RuntimeException('Cannot create qualified invocation');
				}
				Cpp_Publication::write($temporary, str_replace($gate . "\n", 'build ' . self::CONTEXT_STAMP . ": phony\n", $graph));
				$invocation = $temporary;
			}
			$start = hrtime(true);
			$result = self::execute([$ninja, '-j', (string) $jobs, '-f', $invocation], $directory);
			$result += ['qualification' => $key === null ? 'probe-gate' : 'in-process-cache',
				'qualification_key' => $key, 'qualification_ms' => $lookup_ms, 'native_ms' => (hrtime(true) - $start) / 1e6];
			if ($result['exit_code'] === 0) {
				$result['executable'] = $directory . '/program';
			}
			return $result;
		}
		finally {
			if (is_string($temporary) && is_file($temporary)) {
				unlink($temporary);
			}
			fclose($lock);
		}
	}

	/** Files capture both streams without pipe deadlocks; the only warm invocation is Ninja. @param string[] $command @return mixed[] */
	private static function execute(array $command /** list<string> */, string $directory): array /** array<string,mixed> */
	{
		$out = tmpfile();
		$err = tmpfile();
		if (($out === false) || ($err === false)) {
			throw new \RuntimeException('Cannot capture native build output');
		}
		try
		{
			$process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => $out, 2 => $err], $pipes, $directory);
			if (!is_resource($process)) {
				throw new \RuntimeException('Cannot launch Ninja');
			}
			$status = proc_close($process);
			rewind($out);
			rewind($err);
			return ['exit_code' => $status, 'stdout' => stream_get_contents($out), 'stderr' => stream_get_contents($err)];
		}
		finally {
			fclose($out);
			fclose($err);
		}
	}

	/** Resolve executable spelling without invoking which/version tools and without collapsing symlink identity. */
	private static function executable(string $name): string
	{
		$paths = str_contains($name, '/') ? [$name]
			: array_map(static fn(string $path): string => ($path === '' ? '.' : $path) . '/' . $name, explode(':', getenv('PATH') ?: ''));
		foreach ($paths as $path) {
			clearstatcache(true, $path);
			if (is_file($path) && is_executable($path)) {
				$absolute = str_starts_with($path, '/') ? $path : getcwd() . '/' . $path;
				return '/' . implode('/', array_filter(explode('/', $absolute),
					static fn(string $part): bool => ($part !== '') && ($part !== '.')));
			}
		}
		throw new \RuntimeException('Native executable not found: ' . $name);
	}

	/** @param string[] $arguments */
	private static function shell(array $arguments /** list<string> */): string
	{
		return str_replace('$', '$$', implode(' ', array_map('escapeshellarg', $arguments)));
	}

	private static function ninja_path(string $path): string
	{
		// Quotes/backslashes in input names are not round-tripped by the current Ninja/Make depfile parser.
		if (strpbrk($path, "\"'\\|\r\n\0") !== false) {
			throw new \RuntimeException('Unsupported Ninja input path spelling: ' . $path);
		}
		return str_replace(['$', ' ', ':'], ['$$', '$ ', '$:'], $path);
	}

	private static function string(mixed $value, string $name): string
	{
		if (!is_string($value) || ($value === '') || (strpbrk($value, "\0\r\n") !== false)) {
			throw new \RuntimeException('Expected nonempty single-line string: ' . $name);
		}
		return $value;
	}

	/** Validate host configuration and manifest lists before constructing commands or graph syntax. @return string[] */
	private static function strings(mixed $value, string $name): array /** list<string> */
	{
		if (!is_array($value) || !array_is_list($value)) {
			throw new \RuntimeException('Expected string list: ' . $name);
		}
		foreach ($value as $item) {
			self::string($item, $name);
		}
		return $value;
	}
}

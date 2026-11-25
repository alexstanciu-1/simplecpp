<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Reads Python qualification records in-process; only the cold probe owns record publication. */
final class Qualification_Cache
{
	private const CACHE_SCHEMA /** uint32 */ = 2;
	private const WEEK /** uint32 */ = 604800;
	private const ENVIRONMENT /** list<string> */ = ['PATH', 'CPATH', 'CPLUS_INCLUDE_PATH', 'C_INCLUDE_PATH',
		'GCC_EXEC_PREFIX', 'COMPILER_PATH', 'LIBRARY_PATH', 'LD_LIBRARY_PATH', 'LD_PRELOAD', 'SDKROOT',
		'CCC_OVERRIDE_OPTIONS'];

	/**
	 * Return a valid record key, or null to retain the native graph's probe gate. Never launches a process.
	 * The caller uses the same compiler, flags, environment and working directory for Ninja.
	 * @param string[] $flags
	 * @param string[] $link_flags
	 */
	public static function lookup(string $compiler, array $flags /** list<string> */,
		array $link_flags /** list<string> */, string $cache): ?string
	{
		$options = array_merge($flags, $link_flags);
		$relative = self::relative_options($options);
		if ($relative === null) {
			return null;
		}
		$environment = new \stdClass();
		foreach (self::ENVIRONMENT as $name)
		{
			$value = getenv($name);
			if ($value === false) {
				continue;
			}
			$environment->$name = $value;
			if (!in_array($name, ['CCC_OVERRIDE_OPTIONS', 'LD_PRELOAD'], true)) {
				foreach (explode(':', $value) as $path) {
					$relative = $relative || !str_starts_with($path, '/');
				}
			}
		}
		if ((($environment->LD_PRELOAD ?? '') !== '') || (($environment->CCC_OVERRIDE_OPTIONS ?? '') !== '')) {
			return null;
		}
		$source = dirname(__DIR__, 2) . '/tools/layout_probe/argument_order.cpp';
		$request = [$compiler, $source, $options, count($flags), $environment, $relative ? getcwd() : null];
		$slot = hash('sha256', self::canonical($request));
		// Nonblocking: another refresher owns the record; Ninja will wait at the protected gate.
		$lock = @fopen($cache . '/' . $slot . '.lock', 'c');
		if ($lock === false) {
			return null;
		}
		try
		{
			if (!flock($lock, \LOCK_EX | \LOCK_NB)) {
				return null;
			}
			$index = self::read($cache . '/inputs-' . $slot . '.json');
			$key = $index->key ?? null;
			if (!is_string($key) || (preg_match('/\A[0-9a-f]{64}\z/', $key) !== 1)) {
				return null;
			}
			$record = self::read($cache . '/' . $key . '.json');
			$tested = $record->tested_at ?? null;
			if ((!is_int($tested) && !is_float($tested)) || ($tested > microtime(true))
				|| (microtime(true) - $tested >= self::WEEK) || !self::valid_traces($record->traces ?? null)
				|| (($record->context->request ?? null) !== $slot)
				|| (($record->context->cache_schema ?? null) !== self::CACHE_SCHEMA)
				|| (hash('sha256', self::canonical($record->context ?? null)) !== $key)
				|| !(($index->inputs ?? null) instanceof \stdClass) || (get_object_vars($index->inputs) === [])
				|| (($record->context->inputs_sha256 ?? null) !== hash('sha256', self::canonical($index->inputs)))) {
				return null;
			}
			foreach ($index->inputs as $path => $expected) {
				if (!self::matches($path, $expected)) {
					return null;
				}
			}
			return $key;
		}
		catch (\JsonException | \ValueError | \TypeError) {
			return null;
		}
		finally {
			fclose($lock);
		}
	}

	/**
	 * Recognized options share one admission/path scan; null requires full fingerprinting, bool records cwd dependence.
	 * @param string[] $options
	 */
	private static function relative_options(array $options /** list<string> */): ?bool
	{
		$relative = false;
		for ($i = 0; $i < count($options); ++$i)
		{
			$option = $options[$i];
			if (in_array($option, ['-I', '-include-pch'], true)) {
				$path = $options[++$i] ?? '';
			}
			elseif (str_starts_with($option, '--ld-path=')) {
				$path = substr($option, 10);
			}
			elseif ($option === '-Xclang') {
				if (($options[++$i] ?? null) !== '-fno-validate-pch') {
					return null;
				}
				continue;
			}
			elseif (str_starts_with($option, '-std=') || str_starts_with($option, '-stdlib=') || in_array($option,
				['-O0', '-O1', '-O2', '-O3', '-Os', '-Oz', '-g', '-g0', '-DNDEBUG', '-Wall', '-Wextra', '-Wpedantic', '-Werror'], true)) {
				continue;
			}
			else {
				return null;
			}
			if ($path === '') {
				return null;
			}
			$relative = $relative || !str_starts_with($path, '/');
		}
		return $relative;
	}

	/** Clear PHP's stat/realpath caches for retained callers; second-resolution timestamps are accepted bootstrap debt. */
	private static function matches(string $path, mixed $expected): bool
	{
		if (!str_starts_with($path, '/') || str_contains($path, "\0")) {
			return false;
		}
		clearstatcache(true, $path);
		$stat = @stat($path);
		if ($expected === null) {
			return ($stat === false) && !file_exists($path);
		}
		if (($stat === false) || !is_array($expected) || !array_is_list($expected) || (count($expected) !== 5)) {
			return false;
		}
		foreach ($expected as $field) {
			if (!is_int($field)) {
				return false;
			}
		}
		return [$stat['dev'], $stat['ino'], $stat['size'], $stat['mtime'], $stat['ctime']]
			=== [$expected[0], $expected[1], $expected[2], self::seconds($expected[3]), self::seconds($expected[4])];
	}

	/** Floor nanoseconds, including dates before the epoch, without a floating-point precision loss. */
	private static function seconds(int $nanoseconds /** int64 */): int /** int64 */
	{
		return intdiv($nanoseconds, 1000000000) - (($nanoseconds < 0) && ($nanoseconds % 1000000000 !== 0) ? 1 : 0);
	}

	/** Malformed or unreadable records miss; no shell utilities or binary hashing on this path. */
	private static function read(string $path): ?\stdClass
	{
		$text = @file_get_contents($path);
		if ($text === false) {
			return null;
		}
		$value = json_decode($text, false, 512, \JSON_THROW_ON_ERROR);
		return $value instanceof \stdClass ? $value : null;
	}

	/** Require every current probe case exactly once, accepting either permitted observation. */
	private static function valid_traces(mixed $value): bool
	{
		if (!($value instanceof \stdClass)) {
			return false;
		}
		$traces = get_object_vars($value);
		ksort($traces);
		foreach ($traces as $trace) {
			if (!in_array($trace, ['LR', 'RL'], true)) {
				return false;
			}
		}
		return array_keys($traces) === ['divide', 'divide_zero', 'remainder', 'remainder_zero'];
	}

	/** Match Python json.dumps(sort_keys=True) for the string/integer/list/object qualification identity schema. */
	private static function canonical(mixed $value): string
	{
		if ($value instanceof \stdClass) {
			$fields = get_object_vars($value);
			ksort($fields, \SORT_STRING);
			$parts = [];
			foreach ($fields as $key => $item) {
				$parts[] = self::canonical($key) . ': ' . self::canonical($item);
			}
			return '{' . implode(', ', $parts) . '}';
		}
		if (is_array($value)) {
			return '[' . implode(', ', array_map(self::canonical(...), $value)) . ']';
		}
		return str_replace(chr(127), '\u007f', json_encode($value, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES));
	}
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/src/converter.php';
require_once __DIR__ . '/src/project_cache.php';

// CLI owns IO and incremental publication; the converter only transforms a file.
try {
	if ($argc < 3 || $argc > 4 || ($argc === 4 && $argv[3] !== '--stats')) { throw new RuntimeException('Usage: php convert.php SOURCE_DIRECTORY OUTPUT_DIRECTORY [--stats]'); }
	$source = realpath($argv[1]);
	if ($source === false || !is_dir($source)) { throw new RuntimeException('Source directory does not exist'); }
	if (!is_dir($argv[2]) && !mkdir($argv[2], 0777, true)) { throw new RuntimeException('Cannot create output directory'); }
	$output = realpath($argv[2]);
	if ($output === false || $source === $output || str_starts_with($output . '/', $source . '/') || str_starts_with($source . '/', $output . '/')) {
		throw new RuntimeException('Source and output trees must be separate');
	}
	$assertSafe = static function (string $path) use ($output): void {
		for ($current = $path; $current !== $output; $current = dirname($current)) {
			if (is_link($current)) { throw new RuntimeException('Symlink output paths are unsupported'); }
		}
	};
	$manifestPath = $output . '/.scpp-portability.json';
	$assertSafe($manifestPath);
	$previous = is_file($manifestPath) ? json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR) : ['version' => 1, 'files' => []];
	if (!is_array($previous) || ($previous['version'] ?? null) !== 1 || !isset($previous['files']) || !is_array($previous['files'])) {
		throw new RuntimeException('Invalid or unsupported conversion manifest');
	}
	foreach ($previous['files'] as $relative => $entry) {
		if (!is_string($relative) || str_contains($relative, '..') || str_starts_with($relative, '/') || str_contains($relative, '\\') || !str_ends_with($relative, '.php')) {
			throw new RuntimeException('Invalid manifest path');
		}
		if (!is_array($entry) || !is_string($entry['input'] ?? null) || !is_string($entry['output'] ?? null)
			|| !preg_match('/^[a-f0-9]{64}$/D', $entry['input']) || !preg_match('/^[a-f0-9]{64}$/D', $entry['output'])) {
			throw new RuntimeException('Invalid conversion manifest fingerprint');
		}
	}
	$map = require __DIR__ . '/function_map.php';
	$version = hash('sha256', file_get_contents(__FILE__) . file_get_contents(__DIR__ . '/src/converter.php') . file_get_contents(__DIR__ . '/src/container_type.php') . file_get_contents(__DIR__ . '/src/import_policy.php') . file_get_contents(__DIR__ . '/src/exception_policy.php') . file_get_contents(__DIR__ . '/src/declaration_index.php') . file_get_contents(__DIR__ . '/src/project_cache.php') . serialize($map));
	$converter = new scpp\portability\Converter($map);
	$cache = new scpp\portability\Project_Cache($source, $output, $map);
	$indexed = $cache->scan();
	$index = new scpp\portability\Declaration_Index($indexed);
	$files = [];
	$pending = [];
	$converted = 0;
	$reused = 0;
	foreach ($indexed as $relative => $entry) {
		$target = substr($relative, 0, -4) . '.phs';
		$assertSafe($output . '/' . $target);
		$inputHash = hash('sha256', $version . $entry['hash'] . serialize($index->dependencies($relative)));
		$old = $previous['files'][$relative] ?? null;
		if (($old['input'] ?? '') === $inputHash && is_file($output . '/' . $target) && hash_file('sha256', $output . '/' . $target) === ($old['output'] ?? '')) {
			$files[$relative] = $old;
			++$reused;
			continue;
		}
		$result = $converter->convertTokens($index->expand($relative, $cache->load(...)), $relative);
		$files[$relative] = ['input' => $inputHash, 'output' => hash('sha256', $result)];
		$pending[$target] = $result;
		++$converted;
	}
	// Conversion errors occur before any output publication.
	$cache->preflight();

	foreach ($pending as $target => $bytes) {
		$path = $output . '/' . $target;
		$assertSafe($path);
		$relative = substr($target, 0, -4) . '.php';
		if (file_exists($path) && !isset($previous['files'][$relative])) { throw new RuntimeException('Refusing to replace unowned output: ' . $target); }
	}
	$removed = array_diff_key($previous['files'] ?? [], $files);
	foreach ($removed as $relative => $old) { $assertSafe($output . '/' . substr($relative, 0, -4) . '.phs'); }
	$publish = static function (string $path, string $bytes): void {
		if (is_file($path) && file_get_contents($path) === $bytes) { return; }
		if (!is_dir(dirname($path))) { mkdir(dirname($path), 0777, true); }
		$temp = tempnam(dirname($path), '.portability-');
		try {
			if (file_put_contents($temp, $bytes) !== strlen($bytes) || !rename($temp, $path)) { throw new RuntimeException('Failed publication: ' . $path); }
		} finally { if (is_file($temp)) { unlink($temp); } }
	};
	foreach ($pending as $target => $bytes) { $publish($output . '/' . $target, $bytes); }
	foreach ($removed as $relative => $old) {
		$path = $output . '/' . substr($relative, 0, -4) . '.phs';
		if (is_file($path) && !unlink($path)) { throw new RuntimeException('Cannot remove obsolete output'); }
	}
	ksort($files);
	$publish($manifestPath, json_encode(['version' => 1, 'files' => $files], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n");
	$cache->publish($publish);
	$report = ['converted' => $converted, 'reused' => $reused, 'removed' => count($removed)];
	if ($argc === 4) { $report['cache'] = $cache->stats; }
	echo json_encode($report, JSON_THROW_ON_ERROR), "\n";
} catch (Throwable $error) {
	fwrite(STDERR, $error->getMessage() . "\n");
	exit(1);
}

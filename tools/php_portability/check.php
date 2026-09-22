<?php
declare(strict_types=1);

require_once __DIR__ . '/src/converter.php';
require_once __DIR__ . '/src/project_cache.php';

// Read-only source validation. Emission is exercised in memory and discarded;
// there is no independent approximation of the converter's accepted language.
try {
	if ($argc !== 2 && !($argc === 4 && $argv[2] === '--cache')) {
		throw new RuntimeException('Usage: php check.php SOURCE_DIRECTORY [--cache EXISTING_OUTPUT_DIRECTORY]');
	}
	$source = realpath($argv[1]);
	if ($source === false || !is_dir($source)) { throw new RuntimeException('Source directory does not exist'); }
	$output = null;
	if ($argc === 4) {
		$output = realpath($argv[3]);
		if ($output === false || !is_dir($output)) { throw new RuntimeException('Cache directory does not exist'); }
		if ($source === $output || str_starts_with($output . '/', $source . '/') || str_starts_with($source . '/', $output . '/')) {
			throw new RuntimeException('Source and cache trees must be separate');
		}
	}
	$map = require __DIR__ . '/function_map.php';
	$cache = new scpp\portability\Project_Cache($source, $output, $map);
	$files = $cache->scan();
	$index = new scpp\portability\Declaration_Index($files);
	$converter = new scpp\portability\Converter($map);
	foreach ($files as $relative => $entry) {
		// PHP lint catches compile-time PHP errors beyond token parsing, without
		// executing authored code or loading its dependencies/framework.
		$process = proc_open([PHP_BINARY, '-n', '-l', $source . '/' . $relative],
			[0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
		if (!is_resource($process)) { throw new RuntimeException($relative . ': cannot start PHP syntax check'); }
		fclose($pipes[0]);
		$stdout = stream_get_contents($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		if (proc_close($process) !== 0) {
			throw new RuntimeException($relative . ': PHP syntax check failed: ' . trim($stderr . $stdout));
		}
		$index->dependencies($relative);
		$converter->convertTokens($index->expand($relative, $cache->load(...)), $relative);
	}
	echo json_encode(['checked' => count($files), 'cache' => $cache->stats], JSON_THROW_ON_ERROR), "\n";
} catch (Throwable $error) {
	fwrite(STDERR, $error->getMessage() . "\n");
	exit(1);
}

<?php

declare(strict_types=1);

use Scpp\S2S\Transpiler;

header('Content-Type: application/json; charset=utf-8');

$response = [
	'ok' => false,
	'error' => '',
	'generator_header_display' => '',
	'generator_source_display' => '',
	'generator_error' => '',
	'generator_include_directive' => '',
	'php_output' => '',
	'php_error' => '',
	'php_exit_code' => null,
	'cpp_output' => '',
	'cpp_error' => '',
	'cpp_exit_code' => null,
	'cpp_compile_output' => '',
	'cpp_compile_error' => '',
	'cpp_compile_exit_code' => null,
	'php_ast_json' => '',
	'php_ast_source' => 'php-ast-json',
	'timing_resources' => [],
	'timing_resources_json' => '',
	'debug_json' => '',
];

try {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		throw new RuntimeException('POST required.');
	}

	$raw = file_get_contents('php://input');
	$data = json_decode($raw ?: '{}', true);
	if (!is_array($data)) {
		throw new RuntimeException('Invalid JSON request body.');
	}

	$phpCode = trim((string) ($data['php_code'] ?? ''));
	if ($phpCode === '') {
		throw new RuntimeException('PHP code is empty.');
	}

	if (!str_starts_with(ltrim($phpCode), '<?php')) {
		$phpCode = "<?php\n" . $phpCode;
	}

	if (!extension_loaded('ast')) {
		throw new RuntimeException('The php-ast extension is required for this test UI.');
	}

	$projectRoot = realpath(__DIR__ . '/../../');
	if ($projectRoot === false) {
		throw new RuntimeException('Project root not found.');
	}

	require_once $projectRoot . '/php_generator/tools/s2s/bin/bootstrap.php';

	$tempRoot = sys_get_temp_dir() . '/simplecpp_test_ui_' . bin2hex(random_bytes(8));
	if (!mkdir($tempRoot, 0777, true) && !is_dir($tempRoot)) {
		throw new RuntimeException('Failed to create temporary directory.');
	}

	$phpPath = $tempRoot . '/snippet.php';
	file_put_contents($phpPath, $phpCode);

	$timingResources = [];

	$astVersion = max(ast\get_supported_versions());
	$astStage = measureStage(static function () use ($phpCode, $astVersion): array {
		$fixture = [
			'php_version' => PHP_VERSION,
			'php_ast_extension_version' => phpversion('ast'),
			'ast_version_used' => $astVersion,
			'supported_versions' => ast\get_supported_versions(),
			'tokens' => token_get_all($phpCode),
			'ast' => ast\parse_code($phpCode, $astVersion),
		];

		return [
			'fixture' => $fixture,
			'json' => (string) json_encode($fixture, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
		];
	});
	$phpAstJson = $astStage['result']['json'];
	file_put_contents($phpPath . '.json', $phpAstJson);
	$response['php_ast_json'] = $phpAstJson;
	$timingResources['parse_ast'] = stageMetricsFromMeasured($astStage, 'PHP AST parse + tokens');

	$generatorStage = measureStage(static function () use ($phpPath): array {
		$transpiler = new Transpiler();
		$cppFile = $transpiler->transpile($phpPath);
		return ['cpp_file' => $cppFile];
	});
	$cppFile = $generatorStage['result']['cpp_file'];
	$timingResources['create_cpp_code'] = stageMetricsFromMeasured($generatorStage, 'S2S generate C++');

	$headerPath = $tempRoot . '/snippet.hpp';
	$sourcePath = $tempRoot . '/snippet.cpp';
	$headerDisplay = implode("\n", $cppFile->headerLines) . "\n";
	$sourceDisplay = implode("\n", normalizeSourceForUi($cppFile->sourceLines)) . "\n";
	file_put_contents($headerPath, $headerDisplay);
	file_put_contents($sourcePath, implode("\n", $cppFile->sourceLines) . "\n");
	$response['generator_include_directive'] = extractIncludeDirective($sourceDisplay, $headerDisplay);

	$generatorErrors = implode("\n", $cppFile->errors);
	$response['generator_error'] = $generatorErrors;
	$response['generator_header_display'] = $generatorErrors !== '' ? $generatorErrors : $headerDisplay;
	$response['generator_source_display'] = $generatorErrors !== '' ? '' : $sourceDisplay;

	$phpRun = runCommandMeasured(
		['php', __DIR__."/run_include.php", $phpPath],
		$projectRoot,
		20
	);
	$response['php_output'] = normalizeCommandOutput($phpRun['stdout']);
	$response['php_error'] = buildProcessErrorText('PHP execution', $phpRun);
	$response['php_exit_code'] = $phpRun['exit_code'];
	$timingResources['execute_php'] = externalMetricsFromRun('PHP execute', $phpRun);

	if ($generatorErrors !== '') {
		$response['cpp_compile_output'] = '';
		$response['cpp_compile_error'] = '';
		$response['cpp_compile_exit_code'] = null;
		$response['cpp_output'] = '';
		$response['cpp_error'] = $generatorErrors;
		$response['cpp_exit_code'] = null;
		$timingResources['compile_cpp'] = [
			'label' => 'C++ compile',
			'skipped' => true,
			'reason' => 'generator_failed',
		];
		$timingResources['execute_cpp'] = [
			'label' => 'C++ execute',
			'skipped' => true,
			'reason' => 'generator_failed',
		];
	} else {
		$cppBinaryPath = $tempRoot . '/snippet.out';
		$compileInputPath = $tempRoot . '/snippet.build.cpp';
		file_put_contents($compileInputPath, buildNaturalCompileUnit($cppFile->headerLines, $cppFile->sourceLines));
		$compileRun = runCommandMeasured(
			[
				'g++',
				'-std=c++23',
				'-O3',
				$compileInputPath,
				$projectRoot . '/runtime/src/runtime.cpp',
				'-I',
				$projectRoot . '/runtime/include',
				'-o',
				$cppBinaryPath,
			],
			$tempRoot,
			40
		);
		$response['cpp_compile_output'] = normalizeCommandOutput($compileRun['stdout']);
		$response['cpp_compile_error'] = buildProcessErrorText('C++ compile', $compileRun);
		$response['cpp_compile_exit_code'] = $compileRun['exit_code'];
		$timingResources['compile_cpp'] = externalMetricsFromRun('C++ compile', $compileRun);

		if ($compileRun['exit_code'] !== 0) {
			$response['cpp_error'] = $response['cpp_compile_error'];
			$response['cpp_output'] = '';
			$response['cpp_exit_code'] = null;
			$timingResources['execute_cpp'] = [
				'label' => 'C++ execute',
				'skipped' => true,
				'reason' => 'compile_failed',
			];
		} else {
			$cppRun = runCommandMeasured([$cppBinaryPath], $tempRoot, 20);
			$response['cpp_output'] = normalizeCommandOutput($cppRun['stdout']);
			$response['cpp_error'] = buildProcessErrorText('C++ execution', $cppRun);
			$response['cpp_exit_code'] = $cppRun['exit_code'];
			$timingResources['execute_cpp'] = externalMetricsFromRun('C++ execute', $cppRun);
		}
	}

	$response['timing_resources'] = $timingResources;
	$response['timing_resources_json'] = encodePrettyJson($timingResources);
	$response['debug_json'] = buildDebugJson($response, $phpCode, $response['php_ast_json']);
	$response['ok'] = true;
	cleanupDirectory($tempRoot);
	echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $throwable) {
	$errorText = formatThrowableDetails($throwable);
	$response['error'] = $errorText;
	$response['generator_header_display'] = $errorText;
	$response['generator_source_display'] = '';
	$response['generator_error'] = $errorText;
	$response['timing_resources_json'] = encodePrettyJson($response['timing_resources']);
	$response['debug_json'] = buildDebugJson($response, isset($phpCode) ? $phpCode : '', $response['php_ast_json'] ?? '');
	http_response_code(500);
	echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function normalizeSourceForUi(array $sourceLines): array
{
	if ($sourceLines === []) {
		return [];
	}

	$normalized = $sourceLines;
	if (preg_match('/^#include\s+"[^"]+\.hpp"$/', $normalized[0]) === 1) {
		$normalized[0] = '#include <scpp/runtime.hpp>';
	}

	return $normalized;
}

function buildNaturalCompileUnit(array $headerLines, array $sourceLines): string
{
	$filteredSourceLines = $sourceLines;
	if ($filteredSourceLines !== [] && preg_match('/^#include\s+"[^"]+\.hpp"$/', $filteredSourceLines[0]) === 1) {
		array_shift($filteredSourceLines);
		if ($filteredSourceLines !== [] && $filteredSourceLines[0] === '') {
			array_shift($filteredSourceLines);
		}
	}

	return implode("\n", array_merge($headerLines, [''], $filteredSourceLines)) . "\n";
}

function measureStage(callable $callback): array
{
	$memoryBefore = memory_get_usage(true);
	$peakBefore = memory_get_peak_usage(true);
	$start = microtime(true);
	$result = $callback();
	$end = microtime(true);
	$memoryAfter = memory_get_usage(true);
	$peakAfter = memory_get_peak_usage(true);

	return [
		'result' => $result,
		'wall_ms' => round(($end - $start) * 1000, 3),
		'memory_before_bytes' => $memoryBefore,
		'memory_after_bytes' => $memoryAfter,
		'memory_delta_bytes' => $memoryAfter - $memoryBefore,
		'peak_before_bytes' => $peakBefore,
		'peak_after_bytes' => $peakAfter,
		'peak_delta_bytes' => $peakAfter - $peakBefore,
	];
}

function stageMetricsFromMeasured(array $measured, string $label): array
{
	return [
		'label' => $label,
		'wall_ms' => $measured['wall_ms'],
		'memory_before_bytes' => $measured['memory_before_bytes'],
		'memory_after_bytes' => $measured['memory_after_bytes'],
		'memory_delta_bytes' => $measured['memory_delta_bytes'],
		'peak_before_bytes' => $measured['peak_before_bytes'],
		'peak_after_bytes' => $measured['peak_after_bytes'],
		'peak_delta_bytes' => $measured['peak_delta_bytes'],
	];
}

function runCommandMeasured(array $command, string $workingDirectory, int $timeoutSeconds): array
{
	$descriptorSpec = [
		0 => ['pipe', 'r'],
		1 => ['pipe', 'w'],
		2 => ['pipe', 'w'],
	];

	$timingMarker = '__SCPP_TIME__';
	$timePath = is_file('/usr/bin/time') ? '/usr/bin/time' : null;
	$processCommand = $command;
	if ($timePath !== null) {
		$escapedParts = array_map(static fn (string $part): string => escapeshellarg($part), $command);
		$escapedMarker = escapeshellarg($timingMarker . '{"elapsed_sec":"%e","max_rss_kb":"%M","user_sec":"%U","sys_sec":"%S"}');
		$processCommand = [
			'/bin/sh',
			'-lc',
			'exec ' . escapeshellarg($timePath) . ' -f ' . $escapedMarker . ' ' . implode(' ', $escapedParts),
		];
	}

	$process = proc_open($processCommand, $descriptorSpec, $pipes, $workingDirectory);
	if (!is_resource($process)) {
		throw new RuntimeException('Failed to start process: ' . implode(' ', $command));
	}

	fclose($pipes[0]);
	stream_set_blocking($pipes[1], false);
	stream_set_blocking($pipes[2], false);

	$stdout = '';
	$stderr = '';
	$timedOut = false;
	$start = microtime(true);

	while (true) {
		$status = proc_get_status($process);
		$stdout .= stream_get_contents($pipes[1]);
		$stderr .= stream_get_contents($pipes[2]);

		if ($status['running'] === false) {
			break;
		}

		if ((microtime(true) - $start) > $timeoutSeconds) {
			$timedOut = true;
			proc_terminate($process, 9);
			break;
		}

		usleep(50_000);
	}

	$stdout .= stream_get_contents($pipes[1]);
	$stderr .= stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);

	$exitCode = proc_close($process);
	$wallMs = round((microtime(true) - $start) * 1000, 3);
	if ($timedOut && $exitCode === 0) {
		$exitCode = 124;
	}

	$parsedMetrics = parseTimeMetrics($stderr, $timingMarker);

	return [
		'stdout' => $stdout,
		'stderr' => $parsedMetrics['stderr'],
		'exit_code' => $exitCode,
		'timed_out' => $timedOut,
		'wall_ms' => $wallMs,
		'user_ms' => secondsStringToMs($parsedMetrics['metrics']['user_sec'] ?? null),
		'sys_ms' => secondsStringToMs($parsedMetrics['metrics']['sys_sec'] ?? null),
		'max_rss_kb' => isset($parsedMetrics['metrics']['max_rss_kb']) ? (int) $parsedMetrics['metrics']['max_rss_kb'] : null,
	];
}

function parseTimeMetrics(string $stderr, string $marker): array
{
	$cleanStderr = rtrim($stderr, "\n");
	$metrics = [];
	$pattern = '/(?:^|\n)' . preg_quote($marker, '/') . '(\{.*\})$/s';
	if (preg_match($pattern, $cleanStderr, $matches, PREG_OFFSET_CAPTURE) === 1) {
		$json = $matches[1][0];
		$fullMatchOffset = $matches[0][1];
		$decoded = json_decode($json, true);
		if (is_array($decoded)) {
			$metrics = $decoded;
		}
		$cleanStderr = rtrim(substr($cleanStderr, 0, $fullMatchOffset), "\n");
	}

	return [
		'stderr' => $cleanStderr,
		'metrics' => $metrics,
	];
}

function secondsStringToMs(?string $seconds): ?float
{
	if ($seconds === null || $seconds === '') {
		return null;
	}

	return round(((float) $seconds) * 1000, 3);
}

function normalizeCommandOutput(string $stdout): string
{
	return $stdout;
}

function buildProcessErrorText(string $label, array $run): string
{
	$parts = [];
	if ($run['timed_out'] === true) {
		$parts[] = $label . ' timed out.';
	}
	if ($run['exit_code'] !== 0) {
		$parts[] = $label . ' exit code: ' . $run['exit_code'];
	}
	if (trim($run['stderr']) !== '') {
		$parts[] = rtrim($run['stderr']);
	}
	return implode("\n\n", $parts);
}

function externalMetricsFromRun(string $label, array $run): array
{
	return [
		'label' => $label,
		'wall_ms' => $run['wall_ms'],
		'user_ms' => $run['user_ms'],
		'sys_ms' => $run['sys_ms'],
		'max_rss_kb' => $run['max_rss_kb'],
		'exit_code' => $run['exit_code'],
		'timed_out' => $run['timed_out'],
	];
}

function cleanupDirectory(string $path): void
{
	if (!is_dir($path)) {
		return;
	}

	$items = scandir($path);
	if ($items === false) {
		return;
	}

	foreach ($items as $item) {
		if ($item === '.' || $item === '..') {
			continue;
		}
		$child = $path . '/' . $item;
		if (is_dir($child)) {
			cleanupDirectory($child);
			continue;
		}
		@unlink($child);
	}

	@rmdir($path);
}

function buildDebugJson(array $response, string $phpCode, string $phpAstJson): string
{
	$debug = [
		'source_php_code' => $phpCode,
		'php_ast_source' => $response['php_ast_source'],
		'php_ast_json' => $phpAstJson,
		's2s_generator_output' => '',
		's2s_generator_error' => buildErrorObjectFromText($response['generator_error']),
		'generated_cpp_code' => [
			'include_directive' => $response['generator_include_directive'],
			'header' => $response['generator_header_display'],
			'source' => $response['generator_source_display'],
		],
		'php_exit_code' => $response['php_exit_code'],
		'cpp_compiler_output' => $response['cpp_compile_output'] !== '' ? $response['cpp_compile_output'] : ($response['cpp_compile_error'] === '' ? 'compile ok' : ''),
		'cpp_compiler_exit_code' => $response['cpp_compile_exit_code'],
		'cpp_compiler_error' => buildErrorObjectFromText($response['cpp_compile_error']),
		'executed_cpp_output' => $response['cpp_output'],
		'cpp_exit_code' => $response['cpp_exit_code'],
		'cpp_execution_error' => buildErrorObjectFromText($response['cpp_error']),
		'php_output' => $response['php_output'],
		'php_error' => buildErrorObjectFromText($response['php_error']),
		'durations_resources' => $response['timing_resources'],
	];

	return encodePrettyJson($debug);
}

function buildErrorObjectFromText(string $text): array
{
	$text = trim($text);
	if ($text === '') {
		return [];
	}

	$object = [
		'message' => '',
		'file' => '',
		'line' => null,
		'trace' => '',
		'raw' => $text,
	];

	$lines = preg_split('/\R/', $text) ?: [];
	$object['message'] = $lines[0] ?? $text;

	if (preg_match('/^File:\s+(.+)$/m', $text, $matches) === 1) {
		$object['file'] = trim($matches[1]);
	}
	if (preg_match('/^Line:\s+(\d+)$/m', $text, $matches) === 1) {
		$object['line'] = (int) $matches[1];
	}
	if (preg_match('/Trace:\R(.*)$/s', $text, $matches) === 1) {
		$object['trace'] = rtrim($matches[1]);
	}

	return $object;
}

function extractIncludeDirective(string $sourceDisplay, string $headerDisplay): string
{
	foreach ([$sourceDisplay, $headerDisplay] as $text) {
		if (preg_match('/^#include\s+.+$/m', $text, $matches) === 1) {
			return $matches[0];
		}
	}

	return '';
}

function encodePrettyJson(array $data): string
{
	return (string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function formatThrowableDetails(Throwable $throwable): string
{
	$parts = [];
	$parts[] = $throwable->getMessage();
	$parts[] = 'File: ' . $throwable->getFile();
	$parts[] = 'Line: ' . $throwable->getLine();
	$parts[] = "Trace:\n" . $throwable->getTraceAsString();

	return implode("\n", $parts);
}

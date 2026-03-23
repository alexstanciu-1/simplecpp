<?php

declare(strict_types=1);

use Scpp\S2S\Transpiler;

// Request handler for the browser test UI.
//
// Contract:
// - accepts one PHP source string
// - generates a sidecar AST fixture on the fly
// - runs PHP directly
// - runs s2s and then compiles / executes the generated C++
// - returns quadrant-friendly fields to the UI

header('Content-Type: application/json; charset=utf-8');

$response = [
	'ok' => false,
	'error' => '',
	'generator_display' => '',
	'generator_error' => '',
	'php_output' => '',
	'php_error' => '',
	'cpp_output' => '',
	'cpp_error' => '',
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

	$astVersion = max(ast\get_supported_versions());
	$fixture = [
		'php_version' => PHP_VERSION,
		'php_ast_extension_version' => phpversion('ast'),
		'ast_version_used' => $astVersion,
		'supported_versions' => ast\get_supported_versions(),
		'tokens' => token_get_all($phpCode),
		'ast' => ast\parse_code($phpCode, $astVersion),
	];
	file_put_contents(
		$phpPath . '.json',
		json_encode($fixture, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
	);

	$transpiler = new Transpiler();
	$cppFile = $transpiler->transpile($phpPath);
	$headerPath = $tempRoot . '/snippet.hpp';
	$sourcePath = $tempRoot . '/snippet.cpp';
	file_put_contents($headerPath, implode("\n", $cppFile->headerLines) . "\n");
	file_put_contents($sourcePath, implode("\n", $cppFile->sourceLines) . "\n");

	$generatorErrors = implode("\n", $cppFile->errors);
	$response['generator_error'] = $generatorErrors;
	$response['generator_display'] = $generatorErrors !== ''
		? $generatorErrors
		: (implode("\n", $cppFile->sourceLines) . "\n");

	$phpRun = runCommand(
		['php', $phpPath],
		$projectRoot,
		20
	);
	$response['php_output'] = normalizeCommandOutput($phpRun['stdout']);
	$response['php_error'] = buildProcessErrorText('PHP execution', $phpRun);

	$cppBinaryPath = $tempRoot . '/snippet.out';
	$compileCommand = sprintf(
		'export PATH="$PATH:/usr/local/bin:/usr/bin" && exec g++ -std=c++23 %s %s -I %s -o %s',
		escapeshellarg($sourcePath),
		escapeshellarg($projectRoot . '/runtime/src/runtime.cpp'),
		escapeshellarg($projectRoot . '/runtime/include'),
		escapeshellarg($cppBinaryPath)
	);
	$compileRun = runCommand(
		['/bin/sh', '-lc', $compileCommand],
		$tempRoot,
		40
	);

	if ($compileRun['exit_code'] !== 0) {
		$response['cpp_error'] = buildProcessErrorText('C++ compile', $compileRun);
		$response['cpp_output'] = '';
	} else {
		$cppRun = runCommand([$cppBinaryPath], $tempRoot, 20);
		$response['cpp_output'] = normalizeCommandOutput($cppRun['stdout']);
		$response['cpp_error'] = buildProcessErrorText('C++ execution', $cppRun);
	}

	$response['ok'] = true;
	cleanupDirectory($tempRoot);
	echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $throwable) {
	$response['error'] = $throwable->getMessage();
	$response['generator_display'] = $throwable->getMessage();
	$response['generator_error'] = $throwable->getMessage();
	http_response_code(500);
	echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

/**
 * Executes one command with bounded runtime and separated stdout/stderr streams.
 *
 * @return array{stdout: string, stderr: string, exit_code: int, timed_out: bool}
 */
function runCommand(array $command, string $workingDirectory, int $timeoutSeconds): array
{
	$descriptorSpec = [
		0 => ['pipe', 'r'],
		1 => ['pipe', 'w'],
		2 => ['pipe', 'w'],
	];

	$process = proc_open($command, $descriptorSpec, $pipes, $workingDirectory);
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
	if ($timedOut && $exitCode === 0) {
		$exitCode = 124;
	}

	return [
		'stdout' => $stdout,
		'stderr' => $stderr,
		'exit_code' => $exitCode,
		'timed_out' => $timedOut,
	];
}

/**
 * Normalizes command stdout for direct box display.
 */
function normalizeCommandOutput(string $stdout): string
{
	return $stdout;
}

/**
 * Converts non-zero exit codes and stderr into a single UI-friendly error block.
 */
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

/**
 * Removes the temporary working directory recursively.
 */
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

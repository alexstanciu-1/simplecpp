#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Deterministic spec-driven usability harness for Simple C++ / PHP-like.
 *
 * v1 scope:
 * - template-based generation only
 * - real user path: scpp init -> scpp run
 * - PHP reference execution for deterministic cases
 * - classification + dedup + artifact storage
 */

final class HarnessConfig
{
	/** @param list<string> $enabledKinds */
	public function __construct(
		public readonly string $repoRoot,
		public readonly string $artifactsRoot,
		public readonly string $workRoot,
		public readonly string $passingRoot,
		public readonly string $quarantineRoot,
		public readonly string $templatesPath,
		public readonly int $maxAttempts,
		public readonly int $stopAfterUniqueBugFailures,
		public readonly bool $compareWithPhpWhenDeterministic,
		public readonly array $enabledKinds,
		public readonly int $initTimeoutSeconds,
		public readonly int $phpTimeoutSeconds,
		public readonly int $runTimeoutSeconds,
		public readonly string $phpBinary,
		public readonly ?string $astSoPath,
	) {
	}
}

final class HarnessResult
{
	/** @param list<string> $features */
	public function __construct(
		public string $testId,
		public string $kind,
		public string $status,
		public string $classification,
		public string $stage,
		public string $message,
		public ?string $dedupKey,
		public array $features,
		public string $artifactPath,
		public ?array $phpReference,
		public array $initResult,
		public array $scppResult,
		public ?array $runtimeComparison,
	) {
	}

	public function toArray(): array
	{
		return [
			'test_id' => $this->testId,
			'kind' => $this->kind,
			'status' => $this->status,
			'classification' => $this->classification,
			'stage' => $this->stage,
			'message' => $this->message,
			'dedup_key' => $this->dedupKey,
			'features' => $this->features,
			'artifact_path' => $this->artifactPath,
			'php_reference' => $this->phpReference,
			'init' => summarize_command_result($this->initResult),
			'scpp' => summarize_command_result($this->scppResult),
			'runtime_comparison' => $this->runtimeComparison,
		];
	}
}

main($argv);

function main(array $argv): void
{
	$repoRoot = normalize_path(dirname(__DIR__, 2));
	$args = parse_arguments(array_slice($argv, 1));
	$configPath = resolve_config_path($repoRoot, $args['config'] ?? 'tools/usability_harness/config.json');
	$config = load_config($repoRoot, $configPath, $args);
	$templates = load_templates(resolve_config_path($repoRoot, $config->templatesPath));

	prepare_artifact_roots($config);
	$selectedTemplates = select_templates($templates, $config->enabledKinds, $config->maxAttempts);
	if ($selectedTemplates === []) {
		fwrite(STDERR, "No enabled harness templates matched the current configuration.\n");
		exit(1);
	}

	$startedAt = date(DATE_ATOM);
	$results = [];
	$uniqueFailures = [];
	$attempts = 0;

	foreach ($selectedTemplates as $template) {
		if ($attempts >= $config->maxAttempts) {
			break;
		}
		$attempts++;
		$result = execute_template($config, $template);
		$results[] = $result;

		if ($result->status === 'fail' && $result->dedupKey !== null) {
			$uniqueFailures[$result->dedupKey] = [
				'classification' => $result->classification,
				'test_id' => $result->testId,
				'stage' => $result->stage,
				'message' => $result->message,
			];
		}

		$uniqueBugCount = count(array_filter($uniqueFailures, static fn (array $failure): bool => $failure['classification'] === 'bug'));
		if ($uniqueBugCount >= $config->stopAfterUniqueBugFailures) {
			break;
		}
	}

	$finishedAt = date(DATE_ATOM);
	$report = build_report($config, $configPath, $startedAt, $finishedAt, $attempts, $results, $uniqueFailures);
	write_json_file($config->artifactsRoot . '/report.json', $report);
	write_summary_file($config->artifactsRoot . '/summary.txt', $report);

	echo 'Harness completed.' . PHP_EOL;
	echo 'Report: ' . relative_path($config->repoRoot, $config->artifactsRoot . '/report.json') . PHP_EOL;
	echo 'Summary: ' . relative_path($config->repoRoot, $config->artifactsRoot . '/summary.txt') . PHP_EOL;
	echo 'Executed templates: ' . $attempts . PHP_EOL;
	echo 'Pass: ' . $report['totals']['pass'] . ', fail: ' . $report['totals']['fail'] . PHP_EOL;
	echo 'Unique bug failures: ' . $report['totals']['unique_bug_failures'] . PHP_EOL;

	exit($report['totals']['fail'] > 0 ? 2 : 0);
}

function parse_arguments(array $args): array
{
	$parsed = [];
	for ($i = 0; $i < count($args); $i++) {
		$arg = $args[$i];
		if ($arg === '--include-scenarios') {
			$parsed['include_scenarios'] = true;
			continue;
		}
		if (str_starts_with($arg, '--config=')) {
			$parsed['config'] = substr($arg, strlen('--config='));
			continue;
		}
		if (str_starts_with($arg, '--limit=')) {
			$parsed['limit'] = (int) substr($arg, strlen('--limit='));
			continue;
		}
		if (str_starts_with($arg, '--stop-after-bugs=')) {
			$parsed['stop_after_bugs'] = (int) substr($arg, strlen('--stop-after-bugs='));
			continue;
		}
		if ($arg === '--config' && isset($args[$i + 1])) {
			$parsed['config'] = $args[++$i];
			continue;
		}
		if ($arg === '--limit' && isset($args[$i + 1])) {
			$parsed['limit'] = (int) $args[++$i];
			continue;
		}
		if ($arg === '--stop-after-bugs' && isset($args[$i + 1])) {
			$parsed['stop_after_bugs'] = (int) $args[++$i];
			continue;
		}
		if (in_array($arg, ['-h', '--help'], true)) {
			print_help();
			exit(0);
		}
		fwrite(STDERR, 'Unknown argument: ' . $arg . PHP_EOL);
		exit(1);
	}
	return $parsed;
}

function print_help(): void
{
	echo "Usability harness\n";
	echo "Usage:\n";
	echo "  php tools/usability_harness/run.php [--config <path>] [--limit <n>] [--stop-after-bugs <n>] [--include-scenarios]\n";
}

function resolve_config_path(string $repoRoot, string $path): string
{
	if ($path === '') {
		return $repoRoot;
	}
	if ($path[0] === '/' || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1) {
		return normalize_path($path);
	}
	return normalize_path($repoRoot . '/' . $path);
}

function load_config(string $repoRoot, string $configPath, array $args): HarnessConfig
{
	$data = read_json_file($configPath);
	$enabledKinds = [];
	foreach (($data['enabled_kinds'] ?? ['micro']) as $kind) {
		if (is_string($kind) && $kind !== '') {
			$enabledKinds[] = $kind;
		}
	}
	if (($args['include_scenarios'] ?? false) === true && !in_array('scenario', $enabledKinds, true)) {
		$enabledKinds[] = 'scenario';
	}
	$phpBinary = resolve_php_binary();
	$astSoPath = is_file($repoRoot . '/ext/8.4-deb/ast.so') ? normalize_path($repoRoot . '/ext/8.4-deb/ast.so') : null;

	return new HarnessConfig(
		repoRoot: $repoRoot,
		artifactsRoot: resolve_config_path($repoRoot, (string) ($data['artifacts_root'] ?? 'tests/generated/usability_harness')),
		workRoot: resolve_config_path($repoRoot, (string) ($data['work_root'] ?? 'tests/generated/usability_harness/_work')),
		passingRoot: resolve_config_path($repoRoot, (string) ($data['passing_root'] ?? 'tests/generated/usability_harness/passing')),
		quarantineRoot: resolve_config_path($repoRoot, (string) ($data['quarantine_root'] ?? 'tests/generated/usability_harness/quarantine')),
		templatesPath: (string) ($data['templates_path'] ?? 'tools/usability_harness/templates/tests.json'),
		maxAttempts: max(1, (int) ($args['limit'] ?? $data['max_attempts'] ?? 20)),
		stopAfterUniqueBugFailures: max(1, (int) ($args['stop_after_bugs'] ?? $data['stop_after_unique_bug_failures'] ?? 20)),
		compareWithPhpWhenDeterministic: (bool) ($data['compare_with_php_when_deterministic'] ?? true),
		enabledKinds: array_values(array_unique($enabledKinds)),
		initTimeoutSeconds: max(1, (int) ($data['timeouts']['init_seconds'] ?? 30)),
		phpTimeoutSeconds: max(1, (int) ($data['timeouts']['php_seconds'] ?? 30)),
		runTimeoutSeconds: max(1, (int) ($data['timeouts']['run_seconds'] ?? 180)),
		phpBinary: $phpBinary,
		astSoPath: $astSoPath,
	);
}

function resolve_php_binary(): string
{
	foreach (['php8.4', PHP_BINARY] as $candidate) {
		$path = find_command_path($candidate);
		if ($path !== null) {
			return $path;
		}
	}
	return PHP_BINARY;
}

function find_command_path(string $command): ?string
{
	if ($command === '') {
		return null;
	}
	if (str_contains($command, '/')) {
		return is_file($command) ? normalize_path($command) : null;
	}
	$path = getenv('PATH');
	if (!is_string($path) || $path === '') {
		return null;
	}
	foreach (explode(PATH_SEPARATOR, $path) as $dir) {
		if ($dir === '') {
			continue;
		}
		$candidate = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $command;
		if (is_file($candidate) && is_executable($candidate)) {
			return normalize_path($candidate);
		}
	}
	return null;
}

function load_templates(string $templatesPath): array
{
	$data = read_json_file($templatesPath);
	$templates = $data['templates'] ?? null;
	if (!is_array($templates)) {
		throw new RuntimeException('Invalid templates file: templates array missing.');
	}
	return $templates;
}

function prepare_artifact_roots(HarnessConfig $config): void
{
	ensure_directory($config->artifactsRoot);
	reset_runtime_directory($config->workRoot);
	reset_runtime_directory($config->passingRoot);
	ensure_directory($config->quarantineRoot);
	foreach (['bug', 'unsupported', 'tooling', 'generator'] as $name) {
		reset_runtime_directory($config->quarantineRoot . '/' . $name);
	}
}

function reset_runtime_directory(string $path): void
{
	if (is_dir($path)) {
		remove_directory($path);
	}
	ensure_directory($path);
}

function remove_directory(string $path): void
{
	if (!is_dir($path)) {
		return;
	}
	$items = scandir($path);
	if ($items === false) {
		throw new RuntimeException('Failed to list directory: ' . $path);
	}
	foreach ($items as $item) {
		if ($item === '.' || $item === '..') {
			continue;
		}
		$child = $path . '/' . $item;
		if (is_dir($child) && !is_link($child)) {
			remove_directory($child);
			continue;
		}
		if (!unlink($child)) {
			throw new RuntimeException('Failed to remove file: ' . $child);
		}
	}
	if (!rmdir($path)) {
		throw new RuntimeException('Failed to remove directory: ' . $path);
	}
}

function select_templates(array $templates, array $enabledKinds, int $limit): array
{
	$selected = [];
	foreach ($templates as $template) {
		if (!is_array($template)) {
			continue;
		}
		$kind = (string) ($template['kind'] ?? 'micro');
		if (!in_array($kind, $enabledKinds, true)) {
			continue;
		}
		if (($template['enabled_by_default'] ?? false) !== true && $kind !== 'scenario') {
			continue;
		}
		$selected[] = $template;
		if (count($selected) >= $limit) {
			break;
		}
	}
	return $selected;
}

function execute_template(HarnessConfig $config, array $template): HarnessResult
{
	$testId = (string) ($template['id'] ?? 'unknown');
	$kind = (string) ($template['kind'] ?? 'micro');
	$features = normalize_string_list($template['features'] ?? []);
	$projectRoot = $config->workRoot . '/' . $testId;
	$artifactBase = '';
	$phpReference = null;
	$runtimeComparison = null;
	$initResult = ['exit_code' => null, 'stdout' => '', 'stderr' => '', 'timed_out' => false, 'command' => []];
	$scppResult = ['exit_code' => null, 'stdout' => '', 'stderr' => '', 'timed_out' => false, 'command' => []];

	reset_runtime_directory($projectRoot);
	materialize_template_project($projectRoot, $template);

	if (!validate_template($template)) {
		$artifactBase = store_quarantine_artifact($config, 'generator', $testId, $projectRoot, $template, null, $initResult, $scppResult, $phpReference, $runtimeComparison, 'template_validation', 'Template is missing required fields.');
		cleanup_work_project($projectRoot);
		return new HarnessResult($testId, $kind, 'fail', 'generator', 'template_validation', 'Template is missing required fields.', build_dedup_key('template_validation', 'Template is missing required fields.'), $features, $artifactBase, $phpReference, $initResult, $scppResult, $runtimeComparison);
	}

	$initResult = run_scpp_command($config, $projectRoot, ['init'], $config->initTimeoutSeconds);
	if (($initResult['exit_code'] ?? 1) !== 0) {
		$message = 'scpp init failed.';
		$class = classify_failure('scpp_init', $initResult['stderr'], $initResult['stdout']);
		$artifactBase = store_quarantine_artifact($config, $class, $testId, $projectRoot, $template, null, $initResult, $scppResult, $phpReference, $runtimeComparison, 'scpp_init', $message);
		cleanup_work_project($projectRoot);
		return new HarnessResult($testId, $kind, 'fail', $class, 'scpp_init', $message, build_dedup_key('scpp_init', extract_failure_text($initResult)), $features, $artifactBase, $phpReference, $initResult, $scppResult, $runtimeComparison);
	}

	$deterministic = ($template['deterministic'] ?? false) === true;
	if ($config->compareWithPhpWhenDeterministic && $deterministic) {
		$phpReference = run_php_reference($config, $projectRoot, $template);
		if (($phpReference['exit_code'] ?? 1) !== 0) {
			$message = 'PHP reference execution failed.';
			$artifactBase = store_quarantine_artifact($config, 'generator', $testId, $projectRoot, $template, null, $initResult, $scppResult, $phpReference, $runtimeComparison, 'php_reference', $message);
			cleanup_work_project($projectRoot);
			return new HarnessResult($testId, $kind, 'fail', 'generator', 'php_reference', $message, build_dedup_key('php_reference', extract_failure_text($phpReference)), $features, $artifactBase, $phpReference, $initResult, $scppResult, $runtimeComparison);
		}
	}

	$executionMode = (string) ($template['execution_mode'] ?? 'run');
	$scppCommand = $executionMode === 'build' ? ['build'] : ['run'];
	$scppResult = run_scpp_command($config, $projectRoot, $scppCommand, $config->runTimeoutSeconds);
	if (($scppResult['exit_code'] ?? 1) !== 0) {
		$class = classify_failure('scpp_' . $executionMode, $scppResult['stderr'], $scppResult['stdout']);
		$message = 'scpp ' . $executionMode . ' failed.';
		$artifactBase = store_quarantine_artifact($config, $class, $testId, $projectRoot, $template, null, $initResult, $scppResult, $phpReference, $runtimeComparison, 'scpp_' . $executionMode, $message);
		cleanup_work_project($projectRoot);
		return new HarnessResult($testId, $kind, 'fail', $class, 'scpp_' . $executionMode, $message, build_dedup_key('scpp_' . $executionMode, extract_failure_text($scppResult)), $features, $artifactBase, $phpReference, $initResult, $scppResult, $runtimeComparison);
	}

	$runtimeComparison = compare_runtime_outputs($template, $phpReference, $scppResult, $executionMode);
	if (($runtimeComparison['matches'] ?? false) !== true) {
		$message = 'Runtime output mismatch.';
		$artifactBase = store_quarantine_artifact($config, 'bug', $testId, $projectRoot, $template, null, $initResult, $scppResult, $phpReference, $runtimeComparison, 'output_compare', $message);
		cleanup_work_project($projectRoot);
		return new HarnessResult($testId, $kind, 'fail', 'bug', 'output_compare', $message, build_dedup_key('output_compare', (string) ($runtimeComparison['reason'] ?? 'output mismatch')), $features, $artifactBase, $phpReference, $initResult, $scppResult, $runtimeComparison);
	}

	$artifactBase = store_passing_artifact($config, $testId, $projectRoot, $template, $initResult, $scppResult, $phpReference, $runtimeComparison);
	cleanup_work_project($projectRoot);
	return new HarnessResult($testId, $kind, 'pass', 'pass', 'complete', 'Pass.', null, $features, $artifactBase, $phpReference, $initResult, $scppResult, $runtimeComparison);
}

function materialize_template_project(string $projectRoot, array $template): void
{
	$files = $template['files'] ?? null;
	if (!is_array($files)) {
		return;
	}
	foreach ($files as $relativePath => $content) {
		if (!is_string($relativePath) || $relativePath === '' || !is_string($content)) {
			continue;
		}
		$absolutePath = normalize_path($projectRoot . '/' . str_replace('\\', '/', $relativePath));
		ensure_directory(dirname($absolutePath));
		file_put_contents($absolutePath, $content);
	}
}

function validate_template(array $template): bool
{
	if (!isset($template['id']) || !is_string($template['id']) || $template['id'] === '') {
		return false;
	}
	if (!isset($template['files']) || !is_array($template['files']) || $template['files'] === []) {
		return false;
	}
	if (!isset($template['files']['main.php']) || !is_string($template['files']['main.php'])) {
		return false;
	}
	return true;
}

function run_php_reference(HarnessConfig $config, string $projectRoot, array $template): array
{
	$entrypoint = normalize_path($projectRoot . '/main.php');
	return run_command(build_php_command($config, false, [$entrypoint]), $projectRoot, $config->phpTimeoutSeconds);
}

function run_scpp_command(HarnessConfig $config, string $projectRoot, array $scppArgs, int $timeoutSeconds): array
{
	$script = normalize_path($config->repoRoot . '/bin/scpp.php');
	return run_command(build_php_command($config, true, array_merge([$script], $scppArgs)), $projectRoot, $timeoutSeconds);
}

function build_php_command(HarnessConfig $config, bool $needsAst, array $args): array
{
	$command = [$config->phpBinary];
	if ($needsAst && $config->astSoPath !== null && !is_current_php_ast_loaded()) {
		$command[] = '-dextension=' . $config->astSoPath;
	}
	foreach ($args as $arg) {
		$command[] = $arg;
	}
	return $command;
}

function is_current_php_ast_loaded(): bool
{
	return extension_loaded('ast');
}

function run_command(array $command, string $cwd, int $timeoutSeconds): array
{
	$descriptor = [
		0 => ['pipe', 'r'],
		1 => ['pipe', 'w'],
		2 => ['pipe', 'w'],
	];
	$commandString = build_shell_command($command);
	$process = proc_open($commandString, $descriptor, $pipes, $cwd);
	if (!is_resource($process)) {
		throw new RuntimeException('Failed to start command: ' . $commandString);
	}

	fclose($pipes[0]);
	stream_set_blocking($pipes[1], false);
	stream_set_blocking($pipes[2], false);

	$stdout = '';
	$stderr = '';
	$timedOut = false;
	$startedAt = microtime(true);

	while (true) {
		$stdout .= (string) stream_get_contents($pipes[1]);
		$stderr .= (string) stream_get_contents($pipes[2]);
		$status = proc_get_status($process);
		if (!is_array($status) || ($status['running'] ?? false) !== true) {
			break;
		}
		if ((microtime(true) - $startedAt) >= $timeoutSeconds) {
			$timedOut = true;
			proc_terminate($process, 9);
			break;
		}
		usleep(100000);
	}

	$stdout .= (string) stream_get_contents($pipes[1]);
	$stderr .= (string) stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);
	$exitCode = proc_close($process);
	if ($timedOut) {
		$exitCode = 124;
	}

	return [
		'command' => $command,
		'exit_code' => $exitCode,
		'stdout' => $stdout,
		'stderr' => $stderr,
		'timed_out' => $timedOut,
	];
}

function build_shell_command(array $command): string
{
	$parts = [];
	foreach ($command as $part) {
		$parts[] = escapeshellarg((string) $part);
	}
	return implode(' ', $parts);
}

function compare_runtime_outputs(array $template, ?array $phpReference, array $scppResult, string $executionMode): array
{
	if ($executionMode === 'build') {
		return [
			'matches' => true,
			'reason' => 'build-only template',
			'expected_stdout' => '',
			'actual_stdout' => '',
		];
	}

	$programStdout = extract_program_stdout((string) ($scppResult['stdout'] ?? ''));
	$expectedStdout = null;
	if ($phpReference !== null) {
		$expectedStdout = (string) ($phpReference['stdout'] ?? '');
	} elseif (isset($template['expected_stdout']) && is_string($template['expected_stdout'])) {
		$expectedStdout = $template['expected_stdout'];
	}
	if ($expectedStdout === null) {
		return [
			'matches' => true,
			'reason' => 'no deterministic expectation',
			'expected_stdout' => null,
			'actual_stdout' => $programStdout,
		];
	}
	if ($programStdout !== $expectedStdout) {
		return [
			'matches' => false,
			'reason' => 'stdout mismatch',
			'expected_stdout' => $expectedStdout,
			'actual_stdout' => $programStdout,
		];
	}
	$expectedExit = $phpReference !== null ? (int) ($phpReference['exit_code'] ?? 0) : (int) ($template['expected_exit_code'] ?? 0);
	$actualExit = (int) ($scppResult['exit_code'] ?? 0);
	if ($actualExit !== $expectedExit) {
		return [
			'matches' => false,
			'reason' => 'exit code mismatch',
			'expected_stdout' => $expectedStdout,
			'actual_stdout' => $programStdout,
			'expected_exit_code' => $expectedExit,
			'actual_exit_code' => $actualExit,
		];
	}
	return [
		'matches' => true,
		'reason' => 'matched',
		'expected_stdout' => $expectedStdout,
		'actual_stdout' => $programStdout,
		'expected_exit_code' => $expectedExit,
		'actual_exit_code' => $actualExit,
	];
}

function extract_program_stdout(string $stdout): string
{
	$needle = "Running: ";
	$position = strpos($stdout, $needle);
	if ($position === false) {
		return $stdout;
	}
	$newlinePosition = strpos($stdout, "\n", $position);
	if ($newlinePosition === false) {
		return '';
	}
	return substr($stdout, $newlinePosition + 1);
}

function classify_failure(string $stage, string $stderr, string $stdout): string
{
	$text = strtolower(trim($stderr . "\n" . $stdout));
	if ($text === '') {
		return 'tooling';
	}
	$toolingMarkers = [
		'no prism.json found',
		'run `scpp init`',
		'ninja not found',
		'no supported c++ compiler found',
		'failed to start',
		'invalid json in prism.json',
		'timed out',
	];
	foreach ($toolingMarkers as $marker) {
		if (str_contains($text, $marker)) {
			return 'tooling';
		}
	}
	$unsupportedMarkers = [
		'not yet supported',
		'unsupported',
		'todo',
		'not implemented',
		'known gap',
	];
	foreach ($unsupportedMarkers as $marker) {
		if (str_contains($text, $marker)) {
			return 'unsupported';
		}
	}
	if ($stage === 'scpp_init') {
		return 'tooling';
	}
	return 'bug';
}

function extract_failure_text(array $commandResult): string
{
	$text = trim((string) ($commandResult['stderr'] ?? ''));
	if ($text !== '') {
		return $text;
	}
	$text = trim((string) ($commandResult['stdout'] ?? ''));
	return $text === '' ? 'unknown failure' : $text;
}

function build_dedup_key(string $stage, string $message): string
{
	$normalized = strtolower($message);
	$normalized = preg_replace('/\/[A-Za-z0-9_\-\.\/]+/', '<path>', $normalized) ?? $normalized;
	$normalized = preg_replace('/\b\d+\b/', '<n>', $normalized) ?? $normalized;
	$normalized = preg_replace('/\s+/', ' ', trim($normalized)) ?? trim($normalized);
	return substr(sha1($stage . '|' . $normalized), 0, 12);
}

function store_passing_artifact(HarnessConfig $config, string $testId, string $projectRoot, array $template, array $initResult, array $scppResult, ?array $phpReference, ?array $runtimeComparison): string
{
	$featureKey = build_feature_key(normalize_string_list($template['features'] ?? []));
	$artifactRoot = $config->passingRoot . '/' . $featureKey . '/' . $testId;
	copy_selected_project_files($projectRoot, $artifactRoot);
	write_json_file($artifactRoot . '/metadata.json', [
		'test_id' => $testId,
		'kind' => $template['kind'] ?? 'micro',
		'intent' => $template['intent'] ?? '',
		'features' => $template['features'] ?? [],
		'status' => 'pass',
		'classification' => 'pass',
		'runtime_comparison' => $runtimeComparison,
	]);
	write_command_logs($artifactRoot, $initResult, $scppResult, $phpReference);
	return relative_path($config->repoRoot, $artifactRoot);
}

function store_quarantine_artifact(HarnessConfig $config, string $classification, string $testId, string $projectRoot, array $template, ?string $dedupKey, array $initResult, array $scppResult, ?array $phpReference, ?array $runtimeComparison, string $stage, string $message): string
{
	$bucket = $classification;
	$artifactName = ($dedupKey ?? 'raw') . '__' . $testId;
	$artifactRoot = $config->quarantineRoot . '/' . $bucket . '/' . $artifactName;
	copy_directory($projectRoot, $artifactRoot);
	write_json_file($artifactRoot . '/metadata.json', [
		'test_id' => $testId,
		'kind' => $template['kind'] ?? 'micro',
		'intent' => $template['intent'] ?? '',
		'features' => $template['features'] ?? [],
		'status' => 'fail',
		'classification' => $classification,
		'stage' => $stage,
		'message' => $message,
		'dedup_key' => $dedupKey,
		'runtime_comparison' => $runtimeComparison,
	]);
	write_command_logs($artifactRoot, $initResult, $scppResult, $phpReference);
	return relative_path($config->repoRoot, $artifactRoot);
}

function copy_selected_project_files(string $sourceRoot, string $targetRoot): void
{
	ensure_directory($targetRoot);
	foreach (['main.php', 'prism.json'] as $name) {
		$source = $sourceRoot . '/' . $name;
		if (is_file($source)) {
			copy_file($source, $targetRoot . '/' . $name);
		}
	}
}

function copy_directory(string $sourceRoot, string $targetRoot): void
{
	ensure_directory($targetRoot);
	$items = scandir($sourceRoot);
	if ($items === false) {
		throw new RuntimeException('Failed to list source directory: ' . $sourceRoot);
	}
	foreach ($items as $item) {
		if ($item === '.' || $item === '..') {
			continue;
		}
		$source = $sourceRoot . '/' . $item;
		$target = $targetRoot . '/' . $item;
		if (is_dir($source) && !is_link($source)) {
			copy_directory($source, $target);
			continue;
		}
		copy_file($source, $target);
	}
}

function copy_file(string $source, string $target): void
{
	ensure_directory(dirname($target));
	if (!copy($source, $target)) {
		throw new RuntimeException('Failed to copy file: ' . $source);
	}
}

function write_command_logs(string $artifactRoot, array $initResult, array $scppResult, ?array $phpReference): void
{
	write_text_file($artifactRoot . '/init.stdout.log', (string) ($initResult['stdout'] ?? ''));
	write_text_file($artifactRoot . '/init.stderr.log', (string) ($initResult['stderr'] ?? ''));
	write_text_file($artifactRoot . '/scpp.stdout.log', (string) ($scppResult['stdout'] ?? ''));
	write_text_file($artifactRoot . '/scpp.stderr.log', (string) ($scppResult['stderr'] ?? ''));
	if ($phpReference !== null) {
		write_text_file($artifactRoot . '/php.stdout.log', (string) ($phpReference['stdout'] ?? ''));
		write_text_file($artifactRoot . '/php.stderr.log', (string) ($phpReference['stderr'] ?? ''));
	}
}

function cleanup_work_project(string $projectRoot): void
{
	if (is_dir($projectRoot)) {
		remove_directory($projectRoot);
	}
}

function build_report(HarnessConfig $config, string $configPath, string $startedAt, string $finishedAt, int $attempts, array $results, array $uniqueFailures): array
{
	$totals = [
		'pass' => 0,
		'fail' => 0,
		'unique_failures' => count($uniqueFailures),
		'unique_bug_failures' => 0,
	];
	foreach ($results as $result) {
		if (!$result instanceof HarnessResult) {
			continue;
		}
		$totals[$result->status]++;
	}
	foreach ($uniqueFailures as $failure) {
		if (($failure['classification'] ?? '') === 'bug') {
			$totals['unique_bug_failures']++;
		}
	}
	return [
		'version' => 1,
		'repo_root' => relative_path($config->repoRoot, $config->repoRoot),
		'config_path' => relative_path($config->repoRoot, $configPath),
		'started_at' => $startedAt,
		'finished_at' => $finishedAt,
		'attempts' => $attempts,
		'totals' => $totals,
		'unique_failures' => $uniqueFailures,
		'results' => array_map(static fn (HarnessResult $result): array => $result->toArray(), $results),
	];
}

function write_summary_file(string $path, array $report): void
{
	$lines = [];
	$lines[] = 'Usability harness summary';
	$lines[] = 'Started: ' . (string) ($report['started_at'] ?? '');
	$lines[] = 'Finished: ' . (string) ($report['finished_at'] ?? '');
	$lines[] = 'Attempts: ' . (string) ($report['attempts'] ?? 0);
	$lines[] = 'Pass: ' . (string) (($report['totals']['pass'] ?? 0));
	$lines[] = 'Fail: ' . (string) (($report['totals']['fail'] ?? 0));
	$lines[] = 'Unique bug failures: ' . (string) (($report['totals']['unique_bug_failures'] ?? 0));
	$lines[] = '';
	$lines[] = 'Results:';
	foreach (($report['results'] ?? []) as $result) {
		$lines[] = '- ' . ($result['test_id'] ?? 'unknown') . ' => ' . ($result['status'] ?? 'unknown') . ' [' . ($result['classification'] ?? 'unknown') . ']';
	}
	write_text_file($path, implode(PHP_EOL, $lines) . PHP_EOL);
}

function summarize_command_result(array $result): array
{
	return [
		'exit_code' => $result['exit_code'] ?? null,
		'timed_out' => $result['timed_out'] ?? false,
		'command' => $result['command'] ?? [],
	];
}

function normalize_string_list(mixed $value): array
{
	if (!is_array($value)) {
		return [];
	}
	$result = [];
	foreach ($value as $item) {
		if (is_string($item) && $item !== '') {
			$result[] = $item;
		}
	}
	return array_values(array_unique($result));
}

function build_feature_key(array $features): string
{
	if ($features === []) {
		return 'no_features';
	}
	sort($features, SORT_STRING);
	return implode('__', array_map(static fn (string $feature): string => preg_replace('/[^a-z0-9_\-]+/i', '_', $feature) ?? $feature, $features));
}

function ensure_directory(string $path): void
{
	if (is_dir($path)) {
		return;
	}
	if (!mkdir($path, 0777, true) && !is_dir($path)) {
		throw new RuntimeException('Failed to create directory: ' . $path);
	}
}

function write_text_file(string $path, string $content): void
{
	ensure_directory(dirname($path));
	if (file_put_contents($path, $content) === false) {
		throw new RuntimeException('Failed to write file: ' . $path);
	}
}

function write_json_file(string $path, array $data): void
{
	$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	if (!is_string($json)) {
		throw new RuntimeException('Failed to encode JSON for: ' . $path);
	}
	write_text_file($path, $json . PHP_EOL);
}

function read_json_file(string $path): array
{
	$json = file_get_contents($path);
	if ($json === false) {
		throw new RuntimeException('Failed to read JSON file: ' . $path);
	}
	$data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
	if (!is_array($data)) {
		throw new RuntimeException('Invalid JSON object: ' . $path);
	}
	return $data;
}

function normalize_path(string $path): string
{
	$normalized = str_replace('\\', '/', $path);
	$parts = [];
	$prefix = '';
	if (preg_match('/^[A-Za-z]:\//', $normalized) === 1) {
		$prefix = substr($normalized, 0, 2);
		$normalized = substr($normalized, 2);
	}
	$absolute = str_starts_with($normalized, '/');
	foreach (explode('/', $normalized) as $part) {
		if ($part === '' || $part === '.') {
			continue;
		}
		if ($part === '..') {
			if ($parts !== []) {
				array_pop($parts);
			}
			continue;
		}
		$parts[] = $part;
	}
	$result = implode('/', $parts);
	if ($absolute) {
		$result = '/' . $result;
	}
	if ($prefix !== '') {
		$result = $prefix . $result;
	}
	return $result === '' ? ($absolute ? '/' : '.') : $result;
}

function relative_path(string $from, string $to): string
{
	$from = trim(normalize_path($from), '/');
	$to = trim(normalize_path($to), '/');
	if ($from === $to) {
		return '.';
	}
	$fromParts = $from === '' ? [] : explode('/', $from);
	$toParts = $to === '' ? [] : explode('/', $to);
	while ($fromParts !== [] && $toParts !== [] && $fromParts[0] === $toParts[0]) {
		array_shift($fromParts);
		array_shift($toParts);
	}
	return implode('/', array_merge(array_fill(0, count($fromParts), '..'), $toParts));
}

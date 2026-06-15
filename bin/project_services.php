<?php
declare(strict_types=1);

use Scpp\S2S\Stan\StanRunner;
use Scpp\S2S\Transpiler;
use Scpp\S2S\Support\S2SException;

require_once __DIR__ . '/debug/debug_plan.php';
require_once __DIR__ . '/debug/debug_session_io.php';
require_once __DIR__ . '/debug/debug_slots.php';
require_once __DIR__ . '/debug/debug_event_stream.php';
require_once __DIR__ . '/debug/debug_source_rewriter.php';
require_once __DIR__ . '/debug/debug_call_harness.php';
require_once __DIR__ . '/debug/debug_command.php';

const SCPP_VERSION = '0.1.0-dev';
const SCPP_PROJECT_CONFIG = 'prism.json';
const SCPP_STATE_FILE = 's2s_state.php';
const SCPP_STAN_STATE_FILE = 'stan_state.php';
const SCPP_STAN_STATUS_FILE = 'stan_status.json';
const SCPP_STAN_REPORT_FILE = 'stan_report.json';
const SCPP_STAN_WORKER_FILE = 'stan_worker.json';
const SCPP_STAN_REQUEST_FILE = 'stan_request.json';
const SCPP_STAN_WORKER_LOCK_FILE = 'stan_worker.lock';
const SCPP_S2S_SIGNATURE_VERSION = 2;
const SCPP_STAN_SIGNATURE_VERSION = 1;
const SCPP_CANONICAL_SOURCE_EXTENSION = 'phs';
const SCPP_COMPAT_SOURCE_EXTENSIONS = ['phs', 'php', 'jss'];

final class ScppCliException extends RuntimeException
{
	public function __construct(
		string $message,
		public readonly int $exitCode = 1,
		public readonly string $stream = 'stderr'
	) {
		parent::__construct($message);
	}
}

function scpp_write(string $message, string $stream = 'stdout'): void
{
	$handle = $stream === 'stderr' ? STDERR : STDOUT;
	fwrite($handle, $message);
}

function scpp_fail(string $message, int $exitCode = 1, string $stream = 'stderr'): never
{
	throw new ScppCliException($message, $exitCode, $stream);
}

function scpp_capture_subprocess_output_enabled(): bool
{
	return (bool) ($GLOBALS['__scpp_capture_subprocess_output'] ?? false);
}

function scpp_reset_captured_subprocess_output(): void
{
	$GLOBALS['__scpp_captured_subprocess_stdout'] = '';
	$GLOBALS['__scpp_captured_subprocess_stderr'] = '';
}

function scpp_append_captured_subprocess_output(string $stdout, string $stderr): void
{
	$GLOBALS['__scpp_captured_subprocess_stdout'] = (string) ($GLOBALS['__scpp_captured_subprocess_stdout'] ?? '') . $stdout;
	$GLOBALS['__scpp_captured_subprocess_stderr'] = (string) ($GLOBALS['__scpp_captured_subprocess_stderr'] ?? '') . $stderr;
}

/** @return array{ok:bool,output:string,error:string,exit_code:int|null} */
function scpp_run_init_service(string $projectRoot): array
{
	ob_start();
	try {
		$command = new ProjectInitCommand($projectRoot);
		$command->run();
		return [
			'ok' => true,
			'output' => (string) ob_get_clean(),
			'error' => '',
			'exit_code' => 0,
		];
	} catch (ScppCliException $exception) {
		return [
			'ok' => false,
			'output' => (string) ob_get_clean(),
			'error' => $exception->getMessage(),
			'exit_code' => $exception->exitCode,
		];
	}
}

/** @return array{ok:bool,result:?array<string,mixed>,output:string,error:string,exit_code:int|null} */
function scpp_run_build_service(string $projectRoot, string $configPath, array $options = []): array
{
	ob_start();
	$previousCapture = scpp_capture_subprocess_output_enabled();
	$GLOBALS['__scpp_capture_subprocess_output'] = true;
	scpp_reset_captured_subprocess_output();
	try {
		$result = execute_build($projectRoot, $configPath, $options);
		$output = (string) ob_get_clean() . (string) ($GLOBALS['__scpp_captured_subprocess_stdout'] ?? '');
		$error = (string) ($GLOBALS['__scpp_captured_subprocess_stderr'] ?? '');
		return [
			'ok' => true,
			'result' => $result,
			'output' => $output,
			'error' => $error,
			'exit_code' => 0,
		];
	} catch (ScppCliException $exception) {
		$output = (string) ob_get_clean() . (string) ($GLOBALS['__scpp_captured_subprocess_stdout'] ?? '');
		$error = trim((string) ($GLOBALS['__scpp_captured_subprocess_stderr'] ?? '') . $exception->getMessage());
		return [
			'ok' => false,
			'result' => null,
			'output' => $output,
			'error' => $error,
			'exit_code' => $exception->exitCode,
		];
	} finally {
		$GLOBALS['__scpp_capture_subprocess_output'] = $previousCapture;
	}
}

/** @return array{ok:bool,output:string,error:string,exit_code:int|null} */
function scpp_run_update_service(string $repoRoot, array $options = []): array
{
	ob_start();
	try {
		$command = new ScppUpdateCommand($repoRoot, $options);
		$command->run();
		return [
			'ok' => true,
			'output' => (string) ob_get_clean(),
			'error' => '',
			'exit_code' => 0,
		];
	} catch (ScppCliException $exception) {
		return [
			'ok' => false,
			'output' => (string) ob_get_clean(),
			'error' => $exception->getMessage(),
			'exit_code' => $exception->exitCode,
		];
	}
}

/** @return array{ok:bool,output:string,error:string,exit_code:int|null} */
function scpp_run_clean_service(string $projectRoot, string $configPath): array
{
	ob_start();
	try {
		$command = new ProjectCleanCommand($projectRoot, $configPath);
		$command->run();
		return [
			'ok' => true,
			'output' => (string) ob_get_clean(),
			'error' => '',
			'exit_code' => 0,
		];
	} catch (ScppCliException $exception) {
		return [
			'ok' => false,
			'output' => (string) ob_get_clean(),
			'error' => $exception->getMessage(),
			'exit_code' => $exception->exitCode,
		];
	}
}

/**
 * @param ?array{build_dir?:string,runtime_library_dir?:?string,generated_artifact_origins?:array<string,string>} $buildResult
 * @return array{command:list<string>,exit_code:int,stdout:string,stderr:string}
 */
function scpp_run_binary_service(string $workingDirectory, string $binaryPath, array $args = [], ?array $buildResult = null): array
{
	$command = array_merge([$binaryPath], array_values($args));
	$descriptor = [
		0 => ['pipe', 'r'],
		1 => ['pipe', 'w'],
		2 => ['pipe', 'w'],
	];
	$processEnv = [];
	if (is_array($buildResult)) {
		$runtimeLibraryDir = is_string($buildResult['runtime_library_dir'] ?? null) ? $buildResult['runtime_library_dir'] : null;
		$processEnv = scpp_runtime_library_process_environment($runtimeLibraryDir);
		$processEnv['SCPP_ERROR_FORMAT'] = 'json';
	}
	$process = proc_open($command, $descriptor, $pipes, $workingDirectory, scpp_build_process_environment($processEnv));
	if (!is_resource($process)) {
		throw new RuntimeException('Failed to start built program.');
	}
	fclose($pipes[0]);
	$stdout = stream_get_contents($pipes[1]);
	$stderr = stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);
	$status = proc_close($process);
	if (!is_int($status)) {
		throw new RuntimeException('Failed to read program exit status.');
	}
	$stderrText = is_string($stderr) ? $stderr : '';
	if (is_array($buildResult) && $status !== 0) {
		$runtimeDiagnostic = collect_runtime_error_diagnostic($stderrText);
		if ($runtimeDiagnostic !== null) {
			$runtimeDiagnostic = remap_runtime_diagnostic(
				$workingDirectory,
				(string) ($buildResult['build_dir'] ?? dirname($binaryPath)),
				$runtimeDiagnostic,
				is_array($buildResult['generated_artifact_origins'] ?? null) ? $buildResult['generated_artifact_origins'] : []
			);
			$configPath = normalize_path($workingDirectory . '/prism.json');
			$projectMode = null;
			if (is_file($configPath)) {
				$config = load_project_config($configPath);
				$projectMode = resolve_php_runtime_profile(resolve_runtime_build_config($config));
			}
			$remapped = trim(implode(PHP_EOL, render_runtime_failure_lines(
				$runtimeDiagnostic,
				$workingDirectory,
				false,
				true,
				$projectMode,
				true
			)));
			$rawStderr = trim(remove_runtime_error_json_lines($stderrText));
			$stderrLines = [];
			if ($rawStderr !== '') {
				$stderrLines[] = $rawStderr;
			}
			if ($remapped !== '') {
				$stderrLines[] = $remapped;
			}
			$stderrText = $stderrLines === [] ? '' : implode(PHP_EOL . PHP_EOL, $stderrLines) . PHP_EOL;
		}
	}
	return [
		'command' => $command,
		'exit_code' => $status,
		'stdout' => is_string($stdout) ? $stdout : '',
		'stderr' => $stderrText,
	];
}

/**
 * Safe self-update command for Git checkouts installed from GitHub.
 */
final class ScppUpdateCommand
{
	public function __construct(
		private readonly string $repoRoot,
		private readonly array $options = [],
	)
	{
	}

	public function run(): void
	{
		$repoRoot = normalize_path($this->repoRoot);
		$git = find_command_path(['git']);
		if ($git === null) {
			scpp_fail('Git not found. Install Git and retry `scpp update`.' . PHP_EOL, 1);
		}

		$topLevel = $this->readGitLine($git, $repoRoot, ['rev-parse', '--show-toplevel'], 'Failed to inspect the scpp Git checkout.');
		$topLevel = normalize_path($topLevel);
		if ($topLevel === '') {
			scpp_fail('Current scpp repo root is not inside a Git checkout: ' . $repoRoot . PHP_EOL, 1);
		}

		$currentBranch = $this->readGitLine($git, $topLevel, ['branch', '--show-current'], 'Failed to inspect the current Git branch.');
		if ($currentBranch === '') {
			scpp_fail('Cannot update a detached scpp checkout. Check out `main` and retry `scpp update`.' . PHP_EOL, 1);
		}
		if ($currentBranch !== 'main') {
			scpp_fail('`scpp update` updates from GitHub main and must run on branch `main`; current branch is `' . $currentBranch . '`.' . PHP_EOL, 1);
		}

		$this->readGitLine($git, $topLevel, ['remote', 'get-url', 'origin'], 'Git remote `origin` is not configured for this scpp checkout.');
		$status = $this->runGit($git, $topLevel, ['status', '--porcelain', '--untracked-files=all']);
		if ($status['exit_code'] !== 0) {
			scpp_fail('Failed to inspect the scpp checkout status.' . PHP_EOL . $this->formatGitError($status), 1);
		}
		if (trim($status['stdout']) !== '') {
			scpp_fail('Cannot update scpp because the repository has local changes. Commit, stash, or remove them before running `scpp update`.' . PHP_EOL, 1);
		}

		$before = $this->readGitLine($git, $topLevel, ['rev-parse', '--short', 'HEAD'], 'Failed to inspect the current scpp revision.');

		echo 'Updating scpp repository from origin/main' . PHP_EOL;
		echo 'Repository: ' . $topLevel . PHP_EOL;
		echo 'Current revision: ' . $before . PHP_EOL;

		$fetch = $this->runGit($git, $topLevel, ['fetch', 'origin', 'main', '--tags']);
		if ($fetch['exit_code'] !== 0) {
			scpp_fail('Failed to fetch GitHub main for scpp update.' . PHP_EOL . $this->formatGitError($fetch), 1);
		}

		$merge = $this->runGit($git, $topLevel, ['merge', '--ff-only', 'origin/main']);
		if ($merge['exit_code'] !== 0) {
			scpp_fail('Failed to fast-forward scpp to origin/main.' . PHP_EOL . $this->formatGitError($merge), 1);
		}

		$after = $this->readGitLine($git, $topLevel, ['rev-parse', '--short', 'HEAD'], 'Failed to inspect the updated scpp revision.');
		$force = (bool) ($this->options['force'] ?? false);
		if ($before === $after) {
			echo 'Already up to date.' . PHP_EOL;
			if ($force) {
				echo 'Forcing runtime rebuild after update check.' . PHP_EOL;
				scpp_build_default_runtime_matrix($topLevel, true);
			}
			return;
		}
		echo 'Updated scpp: ' . $before . ' -> ' . $after . PHP_EOL;
		scpp_build_default_runtime_matrix($topLevel, true);
	}

	/** @param list<string> $args */
	private function readGitLine(string $git, string $cwd, array $args, string $failureMessage): string
	{
		$result = $this->runGit($git, $cwd, $args);
		if ($result['exit_code'] !== 0) {
			scpp_fail($failureMessage . PHP_EOL . $this->formatGitError($result), 1);
		}
		return trim($result['stdout']);
	}

	/** @param list<string> $args @return array{exit_code:int,stdout:string,stderr:string} */
	private function runGit(string $git, string $cwd, array $args): array
	{
		$command = array_merge([$git], $args);
		$descriptor = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$process = proc_open($command, $descriptor, $pipes, $cwd, scpp_build_process_environment());
		if (!is_resource($process)) {
			scpp_fail('Failed to start Git for scpp update.' . PHP_EOL, 4);
		}
		fclose($pipes[0]);
		$stdout = stream_get_contents($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$status = proc_close($process);
		return [
			'exit_code' => is_int($status) ? $status : 4,
			'stdout' => is_string($stdout) ? $stdout : '',
			'stderr' => is_string($stderr) ? $stderr : '',
		];
	}

	/** @param array{exit_code:int,stdout:string,stderr:string} $result */
	private function formatGitError(array $result): string
	{
		$message = trim($result['stderr']);
		if ($message === '') {
			$message = trim($result['stdout']);
		}
		if ($message === '') {
			$message = 'Git exited with status ' . $result['exit_code'] . '.';
		}
		return $message . PHP_EOL;
	}
}

/**
 * Removes generated project state so the next build is a cold rebuild.
 */
final class ProjectCleanCommand
{
	public function __construct(
		private readonly string $projectRoot,
		private readonly string $configPath,
	)
	{
	}

	public function run(): void
	{
		$projectRoot = normalize_path($this->projectRoot);
		$configPath = normalize_path($this->configPath);
		$config = load_project_config($configPath);
		$projectGraph = resolve_project_dependency_graph($projectRoot, $configPath, $config);
		$contexts = build_project_contexts($projectGraph);
		$targets = [];

		foreach ($contexts as $context) {
			$contextProjectRoot = normalize_path($context['project_root']);
			$projectWorkspace = normalize_path($contextProjectRoot . '/.prism');
			$configuredDirs = [
				normalize_path($context['build_dir']),
				normalize_path($context['generated_dir']),
				normalize_path($context['cache_dir']),
			];
			$allInProjectWorkspace = true;
			foreach ($configuredDirs as $configuredDir) {
				if ($configuredDir !== $projectWorkspace && !path_is_inside($projectWorkspace, $configuredDir)) {
					$allInProjectWorkspace = false;
					break;
				}
			}

			if ($allInProjectWorkspace) {
				$targets[] = [
					'project_root' => $contextProjectRoot,
					'path' => $projectWorkspace,
				];
				continue;
			}

			foreach ($configuredDirs as $configuredDir) {
				$targets[] = [
					'project_root' => $contextProjectRoot,
					'path' => $configuredDir,
				];
			}
		}

		$targets = $this->deduplicateTargets($targets);
		echo 'Cleaning Prism++ generated state for ' . count($contexts) . ' project(s)' . PHP_EOL;

		$removed = 0;
		foreach ($targets as $target) {
			$targetProjectRoot = $target['project_root'];
			$targetPath = $target['path'];
			$this->assertSafeCleanTarget($targetProjectRoot, $targetPath);
			$label = normalize_config_path(relative_path($targetProjectRoot, $targetPath));
			if (!file_exists($targetPath) && !is_link($targetPath)) {
				echo 'Already clean: ' . $label . PHP_EOL;
				continue;
			}
			if (!is_dir($targetPath) || is_link($targetPath)) {
				scpp_fail('Refusing to clean non-directory path: ' . $targetPath . PHP_EOL, 2);
			}
			remove_directory_tree($targetPath);
			echo 'Removed: ' . $label . PHP_EOL;
			$removed++;
		}

		echo 'Clean completed: removed ' . $removed . ' director' . ($removed === 1 ? 'y' : 'ies') . PHP_EOL;
	}

	/**
	 * @param list<array{project_root:string,path:string}> $targets
	 * @return list<array{project_root:string,path:string}>
	 */
	private function deduplicateTargets(array $targets): array
	{
		$unique = [];
		foreach ($targets as $target) {
			$key = normalize_path($target['project_root']) . "\0" . normalize_path($target['path']);
			$unique[$key] = [
				'project_root' => normalize_path($target['project_root']),
				'path' => normalize_path($target['path']),
			];
		}
		$targets = array_values($unique);
		usort($targets, static fn (array $a, array $b): int => strlen($a['path']) <=> strlen($b['path']));

		$result = [];
		foreach ($targets as $target) {
			foreach ($result as $kept) {
				if ($target['project_root'] === $kept['project_root'] && path_is_inside($kept['path'], $target['path'])) {
					continue 2;
				}
			}
			$result[] = $target;
		}
		return $result;
	}

	private function assertSafeCleanTarget(string $projectRoot, string $targetPath): void
	{
		$projectRoot = normalize_path($projectRoot);
		$targetPath = normalize_path($targetPath);
		if ($targetPath === '/' || $targetPath === '.' || $targetPath === $projectRoot) {
			scpp_fail('Refusing to clean unsafe project path: ' . $targetPath . PHP_EOL, 2);
		}
		if (!path_is_inside($projectRoot, $targetPath)) {
			scpp_fail('Refusing to clean path outside project root: ' . $targetPath . PHP_EOL, 2);
		}
	}
}

/**
 * Small command object for `scpp init` so init behavior stays isolated from build logic.
 */
final class ProjectInitCommand
{
	public function __construct(
		private readonly string $projectRoot,
		private readonly string $phpProfile = 'legacy',
	)
	{
	}

	public function run(): void
	{
		$projectRoot = normalize_path($this->projectRoot);
		$phpProfile = strtolower(trim($this->phpProfile));
		if (!in_array($phpProfile, ['legacy', 'strict'], true)) {
			scpp_fail('Unsupported PHP profile `' . $this->phpProfile . '` for scpp init. Use `legacy` or `strict`.' . PHP_EOL, 1);
		}
		$configPath = $projectRoot . '/' . SCPP_PROJECT_CONFIG;
		if (is_file($configPath)) {
			scpp_fail('Project config already exists: ' . relative_or_absolute($projectRoot, $configPath) . PHP_EOL, 1);
		}

		ensure_directory($projectRoot . '/.prism');
		ensure_directory($projectRoot . '/.prism/build');
		ensure_directory($projectRoot . '/.prism/generated');
		ensure_directory($projectRoot . '/.prism/cache');

		$entrypoint = guess_entrypoint($projectRoot);
		$config = [
			'config_version' => 1,
			'project_name' => basename($projectRoot),
			'entrypoint' => $entrypoint ?? 'main.phs',
			'build_dir' => '.prism/build',
			'generated_dir' => '.prism/generated',
			'cache_dir' => '.prism/cache',
			'native_cpp_dir' => 'native_cpp',
			'dependencies' => [],
			'libraries' => [],
			'build' => [
				'backend' => 'ninja',
				'mode' => 'debug',
				'cxx' => null,
			],
			'fastcgi' => [
				'enabled' => false,
				'workers' => 1,
				'max_body_size' => 4 * 1024 * 1024,
				'max_requests' => 0,
			],
			'runtime' => [
				'languages' => [
					'php' => [
						'profile' => $phpProfile,
					],
				],
				'modules' => ['json', 'filesystem', 'datetime'],
			],
		];

		$json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		if ($json === false) {
			scpp_fail("Failed to encode project config.\n", 2);
		}
		$json .= PHP_EOL;
		if (file_put_contents($configPath, $json) === false) {
			scpp_fail('Failed to write project config: ' . relative_or_absolute($projectRoot, $configPath) . PHP_EOL, 2);
		}

		echo 'Created ' . SCPP_PROJECT_CONFIG . PHP_EOL;
		echo 'Project root: ' . $projectRoot . PHP_EOL;
		echo 'Entrypoint: ' . $config['entrypoint'] . PHP_EOL;
		echo 'PHP profile: ' . $phpProfile . PHP_EOL;
		if ($entrypoint === null) {
			echo "Note: no common entrypoint was found; edit prism.json before running scpp build.\n";
		}
	}
}

function main(array $argv): void
{
	$args = $argv;
	array_shift($args);

	if ($args === [] || in_array($args[0], ['-h', '--help'], true)) {
		print_help();
		return;
	}

	if ($args[0] === '--version') {
		print_version();
		return;
	}

	if ($args[0] === '--doctor') {
		print_doctor();
		return;
	}

	if ($args[0] === 'init') {
		handle_init(getcwd() === false ? '.' : getcwd(), array_slice($args, 1));
		return;
	}

	if ($args[0] === 'build') {
		handle_build(getcwd() === false ? '.' : getcwd(), array_slice($args, 1));
		return;
	}

	if ($args[0] === 'clean') {
		handle_clean(getcwd() === false ? '.' : getcwd());
		return;
	}

	if ($args[0] === 'update') {
		handle_update(array_slice($args, 1));
		return;
	}

	if ($args[0] === 'run') {
		handle_run(getcwd() === false ? '.' : getcwd(), array_slice($args, 1));
		return;
	}

	if ($args[0] === 'debug') {
		handle_debug(getcwd() === false ? '.' : getcwd(), array_slice($args, 1));
		return;
	}

	if ($args[0] === 'runtime-build') {
		handle_runtime_build(getcwd() === false ? '.' : getcwd(), array_slice($args, 1));
		return;
	}

	if ($args[0] === 'docs') {
		handle_docs(array_slice($args, 1));
		return;
	}

	if ($args[0] === 'stan') {
		handle_stan(getcwd() === false ? '.' : getcwd(), array_slice($args, 1));
		return;
	}

	if ($args[0] === 'stan-lsp') {
		handle_stan_lsp(getcwd() === false ? '.' : getcwd(), array_slice($args, 1));
		return;
	}

	if ($args[0] === 'error') {
		handle_error_report(getcwd() === false ? '.' : getcwd(), false);
		return;
	}

	if ($args[0] === 'full-error') {
		handle_error_report(getcwd() === false ? '.' : getcwd(), true);
		return;
	}

	if ($args[0] === 'last-run') {
		handle_last_run_report(getcwd() === false ? '.' : getcwd(), false);
		return;
	}

	if ($args[0] === 'full-last-run') {
		handle_last_run_report(getcwd() === false ? '.' : getcwd(), true);
		return;
	}

	if ($args[0] === 'explain-build') {
		handle_explain_build_report(getcwd() === false ? '.' : getcwd(), array_slice($args, 1));
		return;
	}

	if ($args[0] === 'usability-harness') {
		handle_usability_harness(getcwd() === false ? '.' : getcwd(), array_slice($args, 1));
		return;
	}

	$inputFile = $args[0];
	if (!is_file($inputFile)) {
		scpp_fail("Input file not found: {$inputFile}\n", 1);
	}

	try {
		$transpiler = new Transpiler();
		$cppFile = $transpiler->transpile($inputFile);
		echo implode(PHP_EOL, $cppFile->sourceLines) . PHP_EOL;
	} catch (S2SException $e) {
		scpp_fail($e->getMessage() . PHP_EOL, 3);
	} catch (Throwable $e) {
		scpp_fail('internal error: ' . $e->getMessage() . PHP_EOL, 4);
	}
}

function print_help(): void
{
	echo "Prism++ CLI\n";
	echo "Usage:\n";
	echo "  scpp <input.phs>\n";
	echo "  scpp init [--php-profile=legacy|strict]\n";
	echo "  scpp build [--entry=<path>] [--build-runtime] [--build-dependencies] [--no-stan] [--timings]\n";
	echo "  scpp clean\n";
	echo "  scpp update [--force]\n";
	echo "  scpp run [--entry=<path>] [--build-runtime] [--build-dependencies] [--force] [--no-stan] [--timings] [-- <args...>]\n";
	echo "  scpp debug [--format=text|json|ndjson] [--args=<json>] [--env=NAME=VALUE] [--stdin-file=<path>] [--plan-only] [--save-session=<path>] [--load-session=<path>]\n";
	echo "  scpp runtime-build [--debug|--release] [--force]\n";
	echo "  scpp stan\n";
	echo "  scpp stan worker [--once] [--idle-seconds=<n>] [--poll-interval-ms=<n>]\n";
	echo "  scpp stan-lsp document-diagnostics --path <file> [--override-source <file>] [--jsonrpc-id <id>] [--debug]\n";
	echo "  scpp stan-lsp document-symbols --path <file> [--override-source <file>] [--jsonrpc-id <id>] [--debug]\n";
	echo "  scpp stan-lsp hover --path <file> --line <n> [--column <n>] [--override-source <file>] [--jsonrpc-id <id>] [--debug]\n";
	echo "  scpp stan-lsp definition --path <file> --line <n> [--column <n>] [--override-source <file>] [--jsonrpc-id <id>] [--debug]\n";
	echo "  scpp stan-lsp references --path <file> --line <n> [--column <n>] [--override-source <file>] [--jsonrpc-id <id>] [--debug]\n";
	echo "  scpp stan-lsp serve\n";
	echo "  scpp docs [<name>]\n";
	echo "  scpp error\n";
	echo "  scpp full-error\n";
	echo "  scpp last-run\n";
	echo "  scpp full-last-run\n";
	echo "  scpp explain-build [files-transpiled|files-reused|outputs-rebuilt|entrypoint|final-output|generated-files|ninja-target]\n";
	echo "  scpp usability-harness [--config <path>] [--limit <n>] [--stop-after-bugs <n>] [--include-scenarios]\n";
	echo "  scpp build emits a FastCGI companion binary when prism.json fastcgi.enabled = true\n";
	echo "  scpp clean removes the generated project working tree for a cold rebuild\n";
	echo "  scpp update fast-forwards the scpp repository from origin/main and rebuilds the default runtime when it changes\n";
	echo "  scpp run builds first, then executes the selected output\n";
	echo "  scpp debug validates or runs a structured debug session for the current project\n";
	echo "  scpp runtime-build compiles the reusable runtime cache explicitly\n";
	echo "  scpp stan runs the advisory static-analysis front-end spike\n";
	echo "  scpp stan worker keeps per-project STAN analysis warm in the background\n";
	echo "  scpp stan-lsp emits tiny JSON-RPC/LSP-style document payloads and a minimal serve loop\n";
	echo "  scpp docs prints curated local documentation by name\n";
	echo "  scpp usability-harness generates deterministic spec-driven trial projects\n";
	echo "  scpp --help\n";
	echo "  scpp --version\n";
	echo "  scpp --doctor\n";
	echo "\n";
	echo "Compiler selection:\n";
	echo "  prism.json: build.cxx = \"clang++\" or \"g++\"\n";
	echo "  env: SCPP_CXX=clang++ scpp build\n";
}

function print_version(): void
{
	echo 'scpp ' . scpp_version() . PHP_EOL;
}

function scpp_version(): string
{
	$repoRoot = resolve_repo_root();
	$git = find_command_path(['git']);
	if ($git === null) {
		return SCPP_VERSION;
	}

	$tag = scpp_run_optional_command($repoRoot, [$git, 'describe', '--tags', '--match', 'v[0-9]*', '--abbrev=0']);
	if ($tag['exit_code'] !== 0) {
		return SCPP_VERSION;
	}

	$version = trim($tag['stdout']);
	if (preg_match('/^v([0-9][0-9A-Za-z.\-]*)$/', $version, $matches) !== 1) {
		return SCPP_VERSION;
	}
	return $matches[1];
}

function print_doctor(): void
{
	$repoRoot = resolve_repo_root();
	$entry = __FILE__;
	$phpIni = php_ini_loaded_file();
	$astLoaded = extension_loaded('ast') ? 'yes' : 'no';
	$ninja = find_command_path(['ninja']);
	$compiler = resolve_compiler(['build' => []]);
	$compilerLauncher = $compiler !== null
		? resolve_compiler_launcher($compiler['command'])
		: null;
	$projectConfig = find_project_config(getcwd() === false ? $repoRoot : getcwd());
	$git = resolve_repo_git_diagnostics($repoRoot);

	echo "scpp doctor\n";
	echo 'version: ' . scpp_version() . PHP_EOL;
	echo 'php_binary: ' . PHP_BINARY . PHP_EOL;
	echo 'php_version: ' . PHP_VERSION . PHP_EOL;
	echo 'php_ini: ' . ($phpIni === false ? '(none)' : $phpIni) . PHP_EOL;
	$configPath = dirname((string) ($GLOBALS['argv'][0] ?? __FILE__)) . DIRECTORY_SEPARATOR . 'scpp.json';
	echo 'php_ast_loaded: ' . $astLoaded . PHP_EOL;
	echo 'repo_root: ' . $repoRoot . PHP_EOL;
	echo 'entrypoint: ' . $entry . PHP_EOL;
	echo 'argv0: ' . ((string) ($GLOBALS['argv'][0] ?? __FILE__)) . PHP_EOL;
	echo 'config_path: ' . (is_file($configPath) ? $configPath : '(none)') . PHP_EOL;
	echo 'project_config: ' . ($projectConfig['config_path'] ?? '(none)') . PHP_EOL;
	echo 'git_repo: ' . $git['repo'] . PHP_EOL;
	echo 'git_branch: ' . $git['branch'] . PHP_EOL;
	echo 'git_commit: ' . $git['commit'] . PHP_EOL;
	echo 'git_origin_url: ' . $git['origin_url'] . PHP_EOL;
	echo 'git_origin_main_commit: ' . $git['origin_main_commit'] . PHP_EOL;
	echo 'git_up_to_date_with_origin_main: ' . $git['up_to_date_with_origin_main'] . PHP_EOL;
	echo 'ninja: ' . ($ninja ?? '(not found)') . PHP_EOL;
	echo 'cxx_launcher: ' . ($compilerLauncher ?? '(not found)') . PHP_EOL;
	echo 'resolved_cxx: ' . ($compiler !== null ? compiler_display_command($compiler) : '(not found)') . PHP_EOL;
	echo 'env_SCPP_CXX: ' . (getenv('SCPP_CXX') !== false ? (string) getenv('SCPP_CXX') : '(unset)') . PHP_EOL;
	echo 'env_SCPP_CXX_LAUNCHER: ' . (getenv('SCPP_CXX_LAUNCHER') !== false ? (string) getenv('SCPP_CXX_LAUNCHER') : '(unset)') . PHP_EOL;
	foreach (scpp_doctor_warnings() as $warning) {
		echo 'warning: ' . $warning . PHP_EOL;
	}
}

function handle_docs(array $args): void
{
	$docs = scpp_docs_registry();
	if ($args === [] || in_array($args[0], ['-h', '--help', 'list'], true)) {
		scpp_write(render_docs_index($docs));
		return;
	}

	$name = strtolower(trim((string) $args[0]));
	if ($name === '') {
		scpp_write(render_docs_index($docs));
		return;
	}

	$entry = $docs[$name] ?? null;
	if ($entry === null) {
		scpp_fail("Unknown docs name: {$name}\n\n" . render_docs_index($docs), 1);
	}

	$repoRoot = resolve_repo_root();
	$path = normalize_path($repoRoot . '/' . $entry['path']);
	if (!is_file($path)) {
		scpp_fail('Documentation source is missing: ' . $entry['path'] . PHP_EOL, 1);
	}

	$content = file_get_contents($path);
	if (!is_string($content)) {
		scpp_fail('Failed to read documentation source: ' . $entry['path'] . PHP_EOL, 1);
	}

	scpp_write('Doc: ' . $name . PHP_EOL);
	scpp_write('Title: ' . $entry['title'] . PHP_EOL);
	scpp_write('Source: ' . $entry['path'] . PHP_EOL);
	scpp_write(str_repeat('-', 72) . PHP_EOL);
	scpp_write(rtrim($content) . PHP_EOL);
}

/** @param list<string> $args */
function handle_stan(string $cwd, array $args = []): void
{
	if (($args[0] ?? null) === 'worker') {
		handle_stan_worker($cwd, array_slice($args, 1));
		return;
	}

	if ($args === ['--help'] || $args === ['help']) {
		scpp_write('Usage: scpp stan [--help]' . PHP_EOL);
		scpp_write('  Runs the advisory static-analysis pass for the current project.' . PHP_EOL);
		scpp_write('  Warm reuse currently depends on the project/dependency source fingerprint recorded in STAN status files.' . PHP_EOL);
		scpp_write('  See also: scpp stan worker' . PHP_EOL);
		return;
	}

	if ($args !== []) {
		scpp_fail('Unknown option for `scpp stan`: ' . $args[0] . PHP_EOL, 1);
	}

	$project = find_project_config($cwd);
	if ($project === null) {
		scpp_fail('No ' . SCPP_PROJECT_CONFIG . ' found in the current directory or any parent directory.' . PHP_EOL, 1);
	}

	$result = load_or_execute_stan_cli_result($project['project_root'], $project['config_path']);
	$output = [];
	$output[] = 'STAN advisory run completed';
	$output[] = 'Project root: ' . $result['project_root'];
	$output[] = 'PHP profile: ' . $result['php_profile'];
	$output[] = 'Source units: ' . $result['source_unit_count'];
	$output[] = 'Analyzed: ' . $result['analyzed_count'];
	$output[] = 'Reused cache: ' . $result['reused_count'];
	$output[] = 'Indexed symbols: ' . $result['symbol_count'];
	$output[] = 'Duplicate declarations: ' . $result['duplicate_count'];
	$output[] = 'Resolution warnings: ' . $result['resolution_warning_count'];
	$output[] = 'Override warnings: ' . $result['override_warning_count'];
	$output[] = 'Return-chain warnings: ' . $result['return_chain_warning_count'];
	$output[] = 'Expression-chain warnings: ' . $result['expression_chain_warning_count'];
	$output[] = 'Local type warnings: ' . $result['local_type_warning_count'];
	$output[] = 'Property type warnings: ' . $result['property_type_warning_count'];
	$output[] = 'Property read warnings: ' . $result['property_read_warning_count'];
	$output[] = 'Initialization warnings: ' . $result['initialization_warning_count'];
	$output[] = 'Call-site warnings: ' . $result['call_site_warning_count'];
	$output[] = 'Return-type warnings: ' . $result['return_type_warning_count'];
	$output[] = 'Warnings: ' . $result['warning_count'];
	$output[] = 'State: ' . normalize_config_path(relative_path($result['project_root'], $result['state_path']));
	foreach ($result['runtime_shallow_sources'] as $runtimeSource) {
		$output[] = 'Runtime shallow [' . $runtimeSource['profile'] . ']: '
			. normalize_config_path(relative_path($result['project_root'], $runtimeSource['path']))
			. ' (generated ' . $runtimeSource['generated'] . ', skipped ' . count($runtimeSource['skipped']) . ')';
	}
	foreach (($result['warning_samples'] ?? []) as $warningSample) {
		$output[] = 'Warning: ' . $warningSample;
	}
	scpp_write(implode(PHP_EOL, $output) . PHP_EOL);
}

/** @param list<string> $args */
function handle_stan_worker(string $cwd, array $args = []): void
{
	$options = [
		'once' => false,
		'idle_seconds' => scpp_stan_worker_idle_seconds(),
		'poll_interval_ms' => scpp_stan_worker_poll_interval_ms(),
	];
	foreach ($args as $arg) {
		if ($arg === '--once') {
			$options['once'] = true;
			continue;
		}
		if (str_starts_with($arg, '--idle-seconds=')) {
			$value = substr($arg, strlen('--idle-seconds='));
			if (!ctype_digit($value) || (int) $value <= 0) {
				scpp_fail('Invalid `--idle-seconds` for `scpp stan worker`: ' . $arg . PHP_EOL, 1);
			}
			$options['idle_seconds'] = max(1, (int) $value);
			continue;
		}
		if (str_starts_with($arg, '--poll-interval-ms=')) {
			$value = substr($arg, strlen('--poll-interval-ms='));
			if (!ctype_digit($value) || (int) $value <= 0) {
				scpp_fail('Invalid `--poll-interval-ms` for `scpp stan worker`: ' . $arg . PHP_EOL, 1);
			}
			$options['poll_interval_ms'] = max(10, (int) $value);
			continue;
		}
		scpp_fail('Unknown option for `scpp stan worker`: ' . $arg . PHP_EOL, 1);
	}

	$project = find_project_config($cwd);
	if ($project === null) {
		scpp_fail('No ' . SCPP_PROJECT_CONFIG . ' found in the current directory or any parent directory.' . PHP_EOL, 1);
	}

	$config = load_project_config($project['config_path']);
	$paths = build_stan_worker_paths($project['project_root'], $config);
	ensure_directory($paths['cache_dir']);

	$lockHandle = fopen($paths['lock_path'], 'c+');
	if (!is_resource($lockHandle)) {
		scpp_fail('Failed to open STAN worker lock: ' . $paths['lock_path'] . PHP_EOL, 2);
	}
	if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
		fclose($lockHandle);
		return;
	}

	$pid = getmypid();
	if (!is_int($pid) || $pid <= 0) {
		$pid = null;
	}
	$lastActivityAt = microtime(true);

	try {
		while (true) {
			$now = microtime(true);
			$requestForHeartbeat = read_json_file($paths['request_path']);
			write_stan_worker_heartbeat($paths['heartbeat_path'], [
				'pid' => $pid,
				'project_root' => normalize_path($project['project_root']),
				'last_heartbeat_at' => $now,
				'last_seen_request_at' => is_array($requestForHeartbeat) ? (float) ($requestForHeartbeat['requested_at'] ?? 0.0) : 0.0,
			]);

			$currentFingerprint = compute_stan_source_fingerprint($project['project_root'], $project['config_path']);
			$request = $requestForHeartbeat;
			$status = read_json_file($paths['status_path']);
			$publishedFingerprint = is_array($status) ? (string) ($status['source_fingerprint'] ?? '') : '';
			$requestFingerprint = is_array($request) ? (string) ($request['requested_fingerprint'] ?? '') : '';
			$requestTime = is_array($request) ? (float) ($request['requested_at'] ?? 0.0) : 0.0;
			$finishedAt = is_array($status) ? (float) ($status['finished_at'] ?? 0.0) : 0.0;
			$needsAnalysis = $publishedFingerprint !== $currentFingerprint
				|| ($requestFingerprint !== '' && $requestFingerprint === $currentFingerprint && $requestTime > $finishedAt);

			if ($needsAnalysis) {
				$lastActivityAt = $now;
				$runningStatus = [
					'project_root' => normalize_path($project['project_root']),
					'analysis_state' => 'running',
					'source_fingerprint' => $currentFingerprint,
					'requested_fingerprint' => $requestFingerprint,
					'run_id' => build_stan_worker_run_id(),
					'started_at' => $now,
					'finished_at' => $finishedAt,
					'last_activity_at' => $now,
					'compile_error_count' => 0,
					'stan_error_count' => 0,
					'stan_warning_count' => 0,
					'stan_notice_count' => 0,
					'report_path' => normalize_path($paths['report_path']),
				];
				write_json_file_atomic($paths['status_path'], $runningStatus);

				try {
					$report = build_stan_worker_report($project['project_root'], $project['config_path'], $currentFingerprint);
					write_json_file_atomic($paths['report_path'], $report);
					write_json_file_atomic($paths['status_path'], [
						'project_root' => normalize_path($project['project_root']),
						'analysis_state' => 'ready',
						'source_fingerprint' => $currentFingerprint,
						'requested_fingerprint' => $requestFingerprint,
						'run_id' => $report['run_id'],
						'started_at' => $report['started_at'],
						'finished_at' => $report['finished_at'],
						'last_activity_at' => microtime(true),
						'compile_error_count' => $report['compile_error_count'],
						'stan_error_count' => $report['stan_error_count'],
						'stan_warning_count' => $report['stan_warning_count'],
						'stan_notice_count' => $report['stan_notice_count'],
						'report_path' => normalize_path($paths['report_path']),
					]);
				} catch (Throwable $e) {
					write_json_file_atomic($paths['status_path'], [
						'project_root' => normalize_path($project['project_root']),
						'analysis_state' => 'failed',
						'source_fingerprint' => $currentFingerprint,
						'requested_fingerprint' => $requestFingerprint,
						'run_id' => build_stan_worker_run_id(),
						'started_at' => $now,
						'finished_at' => microtime(true),
						'last_activity_at' => microtime(true),
						'compile_error_count' => 0,
						'stan_error_count' => 0,
						'stan_warning_count' => 0,
						'stan_notice_count' => 0,
						'report_path' => normalize_path($paths['report_path']),
						'error' => $e->getMessage(),
					]);
				}

				if ($options['once']) {
					return;
				}

				continue;
			}

			if ($options['once']) {
				return;
			}

			if (($now - $lastActivityAt) >= $options['idle_seconds']) {
				return;
			}

			usleep($options['poll_interval_ms'] * 1000);
		}
	} finally {
		@unlink($paths['heartbeat_path']);
		flock($lockHandle, LOCK_UN);
		fclose($lockHandle);
	}
}

/** @param list<string> $args */
function handle_stan_lsp(string $cwd, array $args = []): void
{
	if ($args === []) {
		scpp_fail('Usage: scpp stan-lsp <document-diagnostics|document-symbols|hover|definition|references|serve> ...' . PHP_EOL, 1);
	}

	if ($args[0] === 'serve') {
		handle_stan_lsp_serve($cwd);
		return;
	}

	$command = $args[0];
	$path = null;
	$overrideSourcePath = null;
	$jsonrpcId = null;
	$line = null;
	$column = null;
	$debug = false;
	for ($index = 1; $index < count($args); $index++) {
		$arg = $args[$index];
		if ($arg === '--path') {
			$path = $args[$index + 1] ?? null;
			$index++;
			continue;
		}
		if ($arg === '--override-source') {
			$overrideSourcePath = $args[$index + 1] ?? null;
			$index++;
			continue;
		}
		if ($arg === '--jsonrpc-id') {
			$jsonrpcId = $args[$index + 1] ?? null;
			$index++;
			continue;
		}
		if ($arg === '--line') {
			$line = $args[$index + 1] ?? null;
			$index++;
			continue;
		}
		if ($arg === '--column') {
			$column = $args[$index + 1] ?? null;
			$index++;
			continue;
		}
		if ($arg === '--debug') {
			$debug = true;
			continue;
		}
		scpp_fail('Unknown option for `scpp stan-lsp`: ' . $arg . PHP_EOL, 1);
	}

	if (!in_array($command, ['document-diagnostics', 'document-symbols', 'hover', 'definition', 'references'], true)) {
		scpp_fail('Unknown `scpp stan-lsp` command: ' . $command . PHP_EOL, 1);
	}

	if (!is_string($path) || trim($path) === '') {
		scpp_fail('Missing required `--path` for `scpp stan-lsp ' . $command . '`.' . PHP_EOL, 1);
	}

	$project = find_project_config($cwd);
	if ($project === null) {
		scpp_fail('No ' . SCPP_PROJECT_CONFIG . ' found in the current directory or any parent directory.' . PHP_EOL, 1);
	}

	$documentPath = normalize_path(resolve_cli_input_path($cwd, $path));
	$sourceOverrides = [];
	if ($overrideSourcePath !== null) {
		$overridePath = resolve_cli_input_path($cwd, $overrideSourcePath);
		$overrideContents = file_get_contents($overridePath);
		if (!is_string($overrideContents)) {
			scpp_fail('Failed to read override source file: ' . $overridePath . PHP_EOL, 1);
		}
		$sourceOverrides[$documentPath] = $overrideContents;
	}

	if ($command === 'hover' || $command === 'definition' || $command === 'references') {
		if (!is_string($line) || !ctype_digit($line) || (int) $line <= 0) {
			scpp_fail('Missing required positive `--line` for `scpp stan-lsp ' . $command . '`.' . PHP_EOL, 1);
		}
		$resolvedColumn = (is_string($column) && ctype_digit($column) && (int) $column > 0) ? (int) $column : null;
		$payload = match ($command) {
			'hover' => execute_stan_hover($project['project_root'], $project['config_path'], $documentPath, (int) $line, $resolvedColumn, $sourceOverrides),
			'definition' => execute_stan_definition($project['project_root'], $project['config_path'], $documentPath, (int) $line, $resolvedColumn, $sourceOverrides),
			default => execute_stan_references($project['project_root'], $project['config_path'], $documentPath, (int) $line, $resolvedColumn, $sourceOverrides),
		};
	} elseif ($command === 'document-symbols') {
		$payload = execute_stan_document_symbols($project['project_root'], $project['config_path'], $documentPath, $sourceOverrides);
	} else {
		$payload = execute_stan_document_diagnostics($project['project_root'], $project['config_path'], $documentPath, $sourceOverrides);
	}
	if ($debug) {
		$payload = attach_stan_debug_metadata($payload, [
			'mode' => 'one-shot',
			'snapshot_cache' => 'miss',
			'analyzed_count' => (int) ($payload['_snapshot_debug']['analyzed_count'] ?? 0),
			'reused_count' => (int) ($payload['_snapshot_debug']['reused_count'] ?? 0),
			'source_unit_count' => (int) ($payload['_snapshot_debug']['source_unit_count'] ?? 0),
			'warning_count' => (int) ($payload['_snapshot_debug']['warning_count'] ?? ($payload['warning_count'] ?? 0)),
		]);
	}

	if ($jsonrpcId !== null) {
		scpp_write(json_encode([
			'jsonrpc' => '2.0',
			'id' => $jsonrpcId,
			'result' => $payload,
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
		return;
	}

	scpp_write(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
}

function handle_stan_lsp_serve(string $cwd): void
{
	$serverSession = new \Scpp\S2S\Stan\StanLspServerSession();

	while (($request = read_stan_lsp_message()) !== null) {
		if (!is_array($request)) {
			write_stan_lsp_message([
				'jsonrpc' => '2.0',
				'id' => null,
				'error' => ['code' => -32700, 'message' => 'Parse error'],
			]);
			continue;
		}

		$id = $request['id'] ?? null;
		$method = (string) ($request['method'] ?? '');
		$params = is_array($request['params'] ?? null) ? $request['params'] : [];
		$debug = (bool) ($params['debug'] ?? false);
		try {
			if ($method === 'initialize') {
				$project = resolve_stan_project_from_initialize($cwd, $params);
				$serverSession->initializeProject($project['project_root'], $project['config_path']);
				$result = [
					'serverInfo' => ['name' => 'scpp-stan-lsp', 'version' => '0.1'],
					'capabilities' => [
						'hoverProvider' => true,
						'definitionProvider' => true,
						'referencesProvider' => true,
						'documentSymbolProvider' => true,
						'diagnosticProvider' => [
							'interFileDependencies' => false,
							'workspaceDiagnostics' => false,
						],
						'textDocumentSync' => [
							'openClose' => true,
							'change' => 1,
						],
					],
				];
			} elseif ($method === 'shutdown') {
				$result = null;
			} elseif ($method === 'initialized') {
				continue;
			} elseif ($method === '$/cancelRequest' || $method === '$/setTrace' || $method === 'workspace/didChangeConfiguration') {
				continue;
			} elseif ($method === 'exit') {
				break;
			} elseif ($method === 'textDocument/didOpen') {
				$documentPath = resolve_stan_document_path_from_params($cwd, $params);
				ensure_stan_server_session_project($serverSession, $cwd, $params, $documentPath);
				$textDocument = is_array($params['textDocument'] ?? null) ? $params['textDocument'] : [];
				$source = (string) ($textDocument['text'] ?? '');
				$version = isset($textDocument['version']) ? (int) $textDocument['version'] : null;
				write_stan_lsp_message($serverSession->didOpen($documentPath, $source, $version, $debug));
				continue;
			} elseif ($method === 'textDocument/didChange') {
				$documentPath = resolve_stan_document_path_from_params($cwd, $params);
				ensure_stan_server_session_project($serverSession, $cwd, $params, $documentPath);
				[$source, $version] = resolve_stan_did_change_source($params);
				write_stan_lsp_message($serverSession->didChange($documentPath, $source, $version, $debug));
				continue;
			} elseif ($method === 'textDocument/didClose') {
				$documentPath = resolve_stan_document_path_from_params($cwd, $params);
				ensure_stan_server_session_project($serverSession, $cwd, $params, $documentPath);
				write_stan_lsp_message($serverSession->didClose($documentPath, $debug));
				continue;
			} elseif ($method === 'textDocument/didSave') {
				$documentPath = resolve_stan_document_path_from_params($cwd, $params);
				ensure_stan_server_session_project($serverSession, $cwd, $params, $documentPath);
				$textDocument = is_array($params['textDocument'] ?? null) ? $params['textDocument'] : [];
				$source = isset($params['text']) && is_string($params['text']) ? $params['text'] : null;
				$version = isset($textDocument['version']) ? (int) $textDocument['version'] : null;
				write_stan_lsp_message($serverSession->didSave($documentPath, $source, $version, $debug));
				continue;
			} elseif ($method === 'workspace/didChangeWatchedFiles') {
				ensure_stan_server_session_project($serverSession, $cwd, $params, null);
				$changedPaths = resolve_stan_watched_file_paths($params);
				foreach ($serverSession->didChangeWatchedFiles($changedPaths, $debug) as $notification) {
					write_stan_lsp_message($notification);
				}
				continue;
			} else {
				$documentPath = resolve_stan_document_path_from_params($cwd, $params);
				ensure_stan_server_session_project($serverSession, $cwd, $params, $documentPath);
				$sourceOverrides = resolve_stan_source_overrides_from_params($cwd, $params);
				[$requestLine, $requestColumn] = resolve_stan_position_from_params($params);
				$result = match ($method) {
					'stan/documentDiagnostics' => $serverSession->documentDiagnostics($documentPath, $sourceOverrides, $debug),
					'stan/documentSymbols' => $serverSession->documentSymbols($documentPath, $sourceOverrides, $debug),
					'stan/hover' => $serverSession->hover($documentPath, $requestLine, $requestColumn, $sourceOverrides, $debug),
					'stan/definition' => $serverSession->definition($documentPath, $requestLine, $requestColumn, $sourceOverrides, $debug),
					'stan/references' => $serverSession->references($documentPath, $requestLine, $requestColumn, $sourceOverrides, $debug),
					'textDocument/diagnostic' => build_stan_lsp_diagnostic_report($serverSession->documentDiagnostics($documentPath, $sourceOverrides, $debug)),
					'textDocument/documentSymbol' => build_stan_lsp_document_symbols($serverSession->documentSymbols($documentPath, $sourceOverrides, $debug)),
					'textDocument/hover' => build_stan_lsp_hover($serverSession->hover($documentPath, $requestLine, $requestColumn, $sourceOverrides, $debug)),
					'textDocument/definition' => build_stan_lsp_definition($serverSession->definition($documentPath, $requestLine, $requestColumn, $sourceOverrides, $debug)),
					'textDocument/references' => build_stan_lsp_references($serverSession->references($documentPath, $requestLine, $requestColumn, $sourceOverrides, $debug)),
					default => ['error' => ['code' => -32601, 'message' => 'Method not found']],
				};
			}

			if (is_array($result) && isset($result['error']) && is_array($result['error'])) {
				write_stan_lsp_message([
					'jsonrpc' => '2.0',
					'id' => $id,
					'error' => $result['error'],
				]);
				continue;
			}

			write_stan_lsp_message([
				'jsonrpc' => '2.0',
				'id' => $id,
				'result' => $result,
			]);
		} catch (Throwable $throwable) {
			write_stan_lsp_message([
				'jsonrpc' => '2.0',
				'id' => $id,
				'error' => ['code' => -32000, 'message' => $throwable->getMessage()],
			]);
		}
	}
}

/** @return array<string,mixed>|null */
function read_stan_lsp_message(): ?array
{
	$contentLength = null;
	while (($line = fgets(STDIN)) !== false) {
		$trimmed = rtrim($line, "\r\n");
		if ($trimmed === '') {
			break;
		}
		if ($contentLength === null && $trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
			$GLOBALS['scpp_stan_lsp_transport'] = 'legacy-json-lines';
			$decoded = json_decode($trimmed, true);
			return is_array($decoded) ? $decoded : [];
		}
		if (stripos($trimmed, 'Content-Length:') === 0) {
			$GLOBALS['scpp_stan_lsp_transport'] = 'lsp-stdio';
			$contentLength = (int) trim(substr($trimmed, strlen('Content-Length:')));
		}
	}

	if ($line === false && $contentLength === null) {
		return null;
	}
	if (!is_int($contentLength) || $contentLength <= 0) {
		return [];
	}

	$payload = '';
	$remaining = $contentLength;
	while ($remaining > 0) {
		$chunk = fread(STDIN, $remaining);
		if (!is_string($chunk) || $chunk === '') {
			return null;
		}
		$payload .= $chunk;
		$remaining -= strlen($chunk);
	}

	$decoded = json_decode($payload, true);
	return is_array($decoded) ? $decoded : [];
}

/** @param array<string,mixed> $message */
function write_stan_lsp_message(array $message): void
{
	$json = json_encode($message, JSON_UNESCAPED_SLASHES);
	if (!is_string($json)) {
		return;
	}
	if (($GLOBALS['scpp_stan_lsp_transport'] ?? 'legacy-json-lines') === 'lsp-stdio') {
		scpp_write('Content-Length: ' . strlen($json) . "\r\n\r\n" . $json);
		return;
	}
	scpp_write($json . PHP_EOL);
}

/** @param array<string,mixed> $params @return array{project_root:string,config_path:string} */
function resolve_stan_project_from_initialize(string $cwd, array $params): array
{
	$workspaceFolders = is_array($params['workspaceFolders'] ?? null) ? $params['workspaceFolders'] : [];
	foreach ($workspaceFolders as $workspaceFolder) {
		if (!is_array($workspaceFolder)) {
			continue;
		}
		$uri = (string) ($workspaceFolder['uri'] ?? '');
		if ($uri === '') {
			continue;
		}
		$project = find_project_config(dirname(resolve_stan_document_path_from_uri($uri)));
		if ($project !== null) {
			return $project;
		}
	}

	$rootUri = (string) ($params['rootUri'] ?? '');
	if ($rootUri !== '') {
		$project = find_project_config(resolve_stan_document_path_from_uri($rootUri));
		if ($project !== null) {
			return $project;
		}
	}

	$rootPath = (string) ($params['rootPath'] ?? '');
	if ($rootPath !== '') {
		$project = find_project_config(resolve_cli_input_path($cwd, $rootPath));
		if ($project !== null) {
			return $project;
		}
	}

	$project = find_project_config($cwd);
	if ($project !== null) {
		return $project;
	}

	throw new RuntimeException('No prism.json found for the initialized workspace.');
}

/** @param array<string,mixed> $params */
function ensure_stan_server_session_project(\Scpp\S2S\Stan\StanLspServerSession $serverSession, string $cwd, array $params, ?string $documentPath): void
{
	try {
		if ($documentPath !== null) {
			$project = find_project_config(dirname($documentPath));
			if ($project !== null) {
				$serverSession->initializeProject($project['project_root'], $project['config_path']);
				return;
			}
		}

		$project = null;
		if (isset($params['rootUri']) || isset($params['rootPath']) || isset($params['workspaceFolders'])) {
			$project = resolve_stan_project_from_initialize($cwd, $params);
		} else {
			$project = find_project_config($cwd);
		}
		if ($project === null) {
			throw new RuntimeException('No prism.json found in the current workspace.');
		}
		$serverSession->initializeProject($project['project_root'], $project['config_path']);
	} catch (RuntimeException $runtimeException) {
		throw $runtimeException;
	}
}

/** @param array<string,mixed> $params @return list<string> */
function resolve_stan_watched_file_paths(array $params): array
{
	$changes = is_array($params['changes'] ?? null) ? $params['changes'] : [];
	$paths = [];
	foreach ($changes as $change) {
		if (!is_array($change)) {
			continue;
		}
		$uri = (string) ($change['uri'] ?? '');
		if ($uri === '') {
			continue;
		}
		$paths[] = normalize_path(resolve_stan_document_path_from_uri($uri));
	}
	return $paths;
}

/** @param array<string,mixed> $params @return array{0:string,1:?int} */
function resolve_stan_did_change_source(array $params): array
{
	$textDocument = is_array($params['textDocument'] ?? null) ? $params['textDocument'] : [];
	$version = isset($textDocument['version']) ? (int) $textDocument['version'] : null;
	$contentChanges = is_array($params['contentChanges'] ?? null) ? $params['contentChanges'] : [];
	if ($contentChanges === []) {
		throw new RuntimeException('Missing contentChanges for textDocument/didChange.');
	}
	$lastChange = $contentChanges[count($contentChanges) - 1] ?? null;
	if (!is_array($lastChange) || !isset($lastChange['text']) || !is_string($lastChange['text'])) {
		throw new RuntimeException('Only full-text contentChanges are currently supported.');
	}
	return [$lastChange['text'], $version];
}

/** @param array<string,mixed> $params */
function resolve_stan_document_path_from_params(string $cwd, array $params): string
{
	$path = (string) ($params['path'] ?? '');
	if ($path !== '') {
		return normalize_path(resolve_cli_input_path($cwd, $path));
	}

	$textDocument = is_array($params['textDocument'] ?? null) ? $params['textDocument'] : [];
	$uri = (string) ($textDocument['uri'] ?? ($params['uri'] ?? ''));
	if ($uri !== '') {
		return normalize_path(resolve_stan_document_path_from_uri($uri));
	}

	throw new RuntimeException('Missing document path or uri.');
}

/** @return array{0:int,1:?int} @param array<string,mixed> $params */
function resolve_stan_position_from_params(array $params): array
{
	if (isset($params['line'])) {
		$line = max(1, (int) $params['line']);
		$column = isset($params['column']) ? max(1, (int) $params['column']) : null;
		return [$line, $column];
	}

	$position = is_array($params['position'] ?? null) ? $params['position'] : [];
	$line = isset($position['line']) ? ((int) $position['line']) + 1 : 1;
	$column = isset($position['character']) ? ((int) $position['character']) + 1 : null;
	return [max(1, $line), $column !== null ? max(1, $column) : null];
}

function resolve_stan_document_path_from_uri(string $uri): string
{
	if (str_starts_with($uri, 'file://')) {
		$path = substr($uri, strlen('file://'));
		$decoded = rawurldecode((string) $path);
		if ($decoded !== '') {
			return $decoded;
		}
	}

	return $uri;
}

/** @param array<string,mixed> $payload @return array<string,mixed> */
function build_stan_lsp_diagnostic_report(array $payload): array
{
	$items = [];
	$diagnostics = is_array($payload['diagnostics'] ?? null) ? $payload['diagnostics'] : [];
	foreach ($diagnostics as $diagnostic) {
		if (!is_array($diagnostic)) {
			continue;
		}
		$items[] = [
			'range' => stan_span_to_lsp_range($diagnostic['span'] ?? null, (int) ($diagnostic['line'] ?? 1)),
			'severity' => stan_severity_to_lsp((string) ($diagnostic['severity'] ?? 'warning')),
			'code' => (string) ($diagnostic['code'] ?? $diagnostic['kind'] ?? 'stan.unknown'),
			'source' => (string) ($diagnostic['source'] ?? 'stan'),
			'message' => (string) ($diagnostic['message'] ?? ''),
		];
	}

	$result = [
		'kind' => 'full',
		'items' => $items,
	];
	if (isset($payload['_debug']) && is_array($payload['_debug'])) {
		$result['_debug'] = $payload['_debug'];
	}
	return $result;
}

/** @param array<string,mixed> $payload @return list<array<string,mixed>> */
function build_stan_lsp_document_symbols(array $payload): array
{
	$result = [];
	$symbols = is_array($payload['symbols'] ?? null) ? $payload['symbols'] : [];
	foreach ($symbols as $symbol) {
		if (!is_array($symbol)) {
			continue;
		}
		$span = $symbol['span'] ?? null;
		$result[] = [
			'name' => (string) ($symbol['name'] ?? ''),
			'detail' => (string) ($symbol['kind'] ?? ''),
			'kind' => (int) ($symbol['lsp_kind'] ?? 13),
			'range' => stan_span_to_lsp_range($span, (int) ($symbol['line'] ?? 1)),
			'selectionRange' => stan_span_to_lsp_range($span, (int) ($symbol['line'] ?? 1)),
		];
	}
	return $result;
}

/** @param array<string,mixed> $payload @return array<string,mixed>|null */
function build_stan_lsp_hover(array $payload): ?array
{
	$hover = is_array($payload['hover'] ?? null) ? $payload['hover'] : null;
	if ($hover === null) {
		return null;
	}

	$lines = [];
	$symbol = is_array($hover['symbol'] ?? null) ? $hover['symbol'] : null;
	if ($symbol !== null) {
		$signature = trim((string) ($symbol['signature'] ?? ''));
		if ($signature !== '') {
			$lines[] = '`' . $signature . '`';
		} else {
			$name = (string) ($symbol['name'] ?? '');
			$kind = (string) ($symbol['kind'] ?? 'symbol');
			$scope = (string) ($symbol['scope'] ?? '');
			$lines[] = '`' . trim($kind . ' ' . $name . ($scope !== '' ? ' [' . $scope . ']' : '')) . '`';
		}
	}

	$diagnostics = is_array($hover['diagnostics'] ?? null) ? $hover['diagnostics'] : [];
	if ($symbol === null) {
		$messages = [];
		foreach ($diagnostics as $diagnostic) {
			if (!is_array($diagnostic)) {
				continue;
			}
			$message = trim((string) ($diagnostic['message'] ?? ''));
			if ($message === '' || in_array($message, $messages, true)) {
				continue;
			}
			$messages[] = $message;
			$lines[] = '- ' . $message;
		}
	}

	if ($lines === []) {
		return null;
	}

	$firstDiagnostic = $diagnostics[0] ?? null;
	$line = (int) ($payload['line'] ?? 1);
	return [
		'contents' => [
			'kind' => 'markdown',
			'value' => implode("\n", $lines),
		],
		'range' => stan_span_to_lsp_range(is_array($firstDiagnostic) ? ($firstDiagnostic['span'] ?? null) : null, $line),
	];
}

/** @param array<string,mixed> $payload @return array<string,mixed>|list<array<string,mixed>>|null */
function build_stan_lsp_definition(array $payload): array|null
{
	$definition = is_array($payload['definition'] ?? null) ? $payload['definition'] : null;
	if ($definition === null) {
		return null;
	}
	return stan_location_from_result($definition);
}

/** @param array<string,mixed> $payload @return list<array<string,mixed>> */
function build_stan_lsp_references(array $payload): array
{
	$result = [];
	$references = is_array($payload['references'] ?? null) ? $payload['references'] : [];
	foreach ($references as $reference) {
		if (!is_array($reference)) {
			continue;
		}
		$result[] = stan_location_from_result($reference);
	}
	return $result;
}

/** @param array<string,mixed>|null $span @return array<string,array<string,int>> */
function stan_span_to_lsp_range(array|null $span, int $fallbackLine): array
{
	$startLine = max(1, (int) ($span['start']['line'] ?? $fallbackLine));
	$startColumn = max(1, (int) ($span['start']['column'] ?? 1));
	$endLine = max(1, (int) ($span['end']['line'] ?? $startLine));
	$endColumn = max(1, (int) ($span['end']['column'] ?? $startColumn));
	return [
		'start' => ['line' => $startLine - 1, 'character' => $startColumn - 1],
		'end' => ['line' => $endLine - 1, 'character' => $endColumn - 1],
	];
}

function stan_severity_to_lsp(string $severity): int
{
	return match ($severity) {
		'error' => 1,
		'information', 'info' => 3,
		'hint' => 4,
		default => 2,
	};
}

/** @param array<string,mixed> $entry @return array<string,mixed> */
function stan_location_from_result(array $entry): array
{
	$path = normalize_path((string) ($entry['path'] ?? ''));
	return [
		'uri' => 'file://' . $path,
		'range' => stan_span_to_lsp_range($entry['span'] ?? null, (int) ($entry['line'] ?? 1)),
	];
}

/**
 * @return array<string,array{title:string,path:string}>
 */
function scpp_docs_registry(): array
{
	$entries = [
		'strict' => [
			'title' => 'PHP++ Strict Quick Learn',
			'path' => 'specs/simple_cpp_php_strict_quick_learn.md',
		],
		'php-strict' => [
			'title' => 'PHP++ Strict Quick Learn',
			'path' => 'specs/simple_cpp_php_strict_quick_learn.md',
		],
		'quick-learn' => [
			'title' => 'PHP++ Strict Quick Learn',
			'path' => 'specs/simple_cpp_php_strict_quick_learn.md',
		],
		'jss' => [
			'title' => 'JSS Quick Learn',
			'path' => 'specs/simple_cpp_jss_quick_learn.md',
		],
		'jss-guide' => [
			'title' => 'JSS Alpha Guide',
			'path' => 'docs/jss/README.md',
		],
		'jss-quick-learn' => [
			'title' => 'JSS Quick Learn',
			'path' => 'specs/simple_cpp_jss_quick_learn.md',
		],
		'jss-authoring' => [
			'title' => 'JSS Authoring Rules',
			'path' => '.agents/skills/simple-cpp-jss/references/authoring-rules.md',
		],
		'jss-diagnostics' => [
			'title' => 'JSS Validation And Diagnostics',
			'path' => '.agents/skills/simple-cpp-jss/references/validation-and-diagnostics.md',
		],
		'jss-skill' => [
			'title' => 'Simple C++ JSS Agent Skill',
			'path' => '.agents/skills/simple-cpp-jss/SKILL.md',
		],
		'build' => [
			'title' => 'Project Build v1',
			'path' => 'specs/project_build_v1.md',
		],
		'getting-started' => [
			'title' => 'Getting Started',
			'path' => 'docs/getting_started.md',
		],
		'diagnostics' => [
			'title' => 'Strict PHP++ Validation And Diagnostics',
			'path' => '.agents/skills/simple-cpp-php-strict/references/validation-and-diagnostics.md',
		],
		'profiles' => [
			'title' => 'PHP Library Profiles',
			'path' => 'specs/php/library_profiles.md',
		],
		'modules' => [
			'title' => 'Strict PHP++ Validation And Diagnostics',
			'path' => '.agents/skills/simple-cpp-php-strict/references/validation-and-diagnostics.md',
		],
		'dependencies' => [
			'title' => 'Project Build v1',
			'path' => 'specs/project_build_v1.md',
		],
		'examples' => [
			'title' => 'Strict PHP Examples',
			'path' => 'docs/examples/php/strict/README.md',
		],
		'authoring' => [
			'title' => 'Strict PHP++ Authoring Rules',
			'path' => '.agents/skills/simple-cpp-php-strict/references/authoring-rules.md',
		],
		'gotchas' => [
			'title' => 'PHP Habit Gotchas',
			'path' => '.agents/skills/simple-cpp-php-strict/references/php-habit-gotchas.md',
		],
		'skill' => [
			'title' => 'Simple C++ PHP++ Strict Agent Skill',
			'path' => '.agents/skills/simple-cpp-php-strict/SKILL.md',
		],
		'agents' => [
			'title' => 'AI Onboarding',
			'path' => 'docs/ai_onboarding/README.md',
		],
	];
	ksort($entries);
	return $entries;
}

/**
 * @param array<string,array{title:string,path:string}> $docs
 */
function render_docs_index(array $docs): string
{
	$lines = [];
	$lines[] = 'scpp docs';
	$lines[] = 'Usage: scpp docs <name>';
	$lines[] = '';
	$lines[] = 'Known docs:';
	foreach ($docs as $name => $entry) {
		$lines[] = '  ' . str_pad($name, 16) . $entry['title'] . ' (' . $entry['path'] . ')';
	}
	$lines[] = '';
	return implode(PHP_EOL, $lines);
}

/** @return array{repo:string,branch:string,commit:string,origin_url:string,origin_main_commit:string,up_to_date_with_origin_main:string} */
function resolve_repo_git_diagnostics(string $repoRoot): array
{
	$unknown = [
		'repo' => 'no',
		'branch' => '(unknown)',
		'commit' => '(unknown)',
		'origin_url' => '(unknown)',
		'origin_main_commit' => '(unknown)',
		'up_to_date_with_origin_main' => 'unknown',
	];
	$git = find_command_path(['git']);
	if ($git === null) {
		return $unknown;
	}

	$topLevel = scpp_run_optional_command($repoRoot, [$git, 'rev-parse', '--show-toplevel']);
	if ($topLevel['exit_code'] !== 0 || trim($topLevel['stdout']) === '') {
		return $unknown;
	}

	$diagnostics = $unknown;
	$diagnostics['repo'] = normalize_path(trim($topLevel['stdout']));

	$branch = scpp_run_optional_command($diagnostics['repo'], [$git, 'branch', '--show-current']);
	if ($branch['exit_code'] === 0 && trim($branch['stdout']) !== '') {
		$diagnostics['branch'] = trim($branch['stdout']);
	} else {
		$diagnostics['branch'] = '(detached)';
	}

	$commit = scpp_run_optional_command($diagnostics['repo'], [$git, 'rev-parse', '--short=12', 'HEAD']);
	if ($commit['exit_code'] === 0 && trim($commit['stdout']) !== '') {
		$diagnostics['commit'] = trim($commit['stdout']);
	}

	$origin = scpp_run_optional_command($diagnostics['repo'], [$git, 'remote', 'get-url', 'origin']);
	if ($origin['exit_code'] === 0 && trim($origin['stdout']) !== '') {
		$diagnostics['origin_url'] = trim($origin['stdout']);
	}

	$remote = scpp_run_optional_command($diagnostics['repo'], [$git, '-c', 'credential.interactive=false', 'ls-remote', 'origin', 'refs/heads/main'], [
		'GIT_TERMINAL_PROMPT' => '0',
		'GCM_INTERACTIVE' => 'Never',
	], 2.0);
	if ($remote['exit_code'] !== 0 || trim($remote['stdout']) === '') {
		return $diagnostics;
	}

	$parts = preg_split('/\s+/', trim($remote['stdout']));
	if (is_array($parts) && isset($parts[0]) && preg_match('/^[0-9a-f]{40}$/i', $parts[0]) === 1) {
		$diagnostics['origin_main_commit'] = substr(strtolower($parts[0]), 0, 12);
		if ($diagnostics['commit'] !== '(unknown)') {
			$diagnostics['up_to_date_with_origin_main'] = $diagnostics['commit'] === $diagnostics['origin_main_commit'] ? 'yes' : 'no';
		}
	}

	return $diagnostics;
}

/**
 * Run a best-effort subprocess for diagnostics without risking an indefinite hang.
 *
 * @param list<string> $command
 * @param array<string,string> $extraEnv
 * @return array{exit_code:int,stdout:string,stderr:string}
 */
function scpp_run_optional_command(string $cwd, array $command, array $extraEnv = [], ?float $timeoutSeconds = 3.0): array
{
	$descriptor = [
		0 => ['pipe', 'r'],
		1 => ['pipe', 'w'],
		2 => ['pipe', 'w'],
	];
	$process = @proc_open($command, $descriptor, $pipes, $cwd, scpp_build_process_environment($extraEnv));
	if (!is_resource($process)) {
		return [
			'exit_code' => 127,
			'stdout' => '',
			'stderr' => '',
		];
	}
	fclose($pipes[0]);
	stream_set_blocking($pipes[1], false);
	stream_set_blocking($pipes[2], false);
	$stdout = '';
	$stderr = '';
	$timedOut = false;
	$deadline = $timeoutSeconds !== null ? microtime(true) + $timeoutSeconds : null;
	while (true) {
		$read = [];
		if (is_resource($pipes[1]) && !feof($pipes[1])) {
			$read[] = $pipes[1];
		}
		if (is_resource($pipes[2]) && !feof($pipes[2])) {
			$read[] = $pipes[2];
		}

		if ($read !== []) {
			$seconds = 0;
			$micros = 200000;
			if ($deadline !== null) {
				$remaining = $deadline - microtime(true);
				if ($remaining <= 0.0) {
					$timedOut = true;
					break;
				}
				$seconds = (int) floor($remaining);
				$micros = (int) max(0, min(999999, floor(($remaining - $seconds) * 1000000)));
			}
			$write = null;
			$except = null;
			$selected = @stream_select($read, $write, $except, $seconds, $micros);
			if ($selected === false) {
				break;
			}
			foreach ($read as $stream) {
				$chunk = stream_get_contents($stream);
				if (!is_string($chunk) || $chunk === '') {
					continue;
				}
				if ($stream === $pipes[1]) {
					$stdout .= $chunk;
					continue;
				}
				$stderr .= $chunk;
			}
		}

		$status = proc_get_status($process);
		if (!is_array($status) || ($status['running'] ?? false) !== true) {
			break;
		}
	}

	if ($timedOut) {
		@proc_terminate($process);
		$stderr .= ($stderr === '' ? '' : PHP_EOL) . 'Timed out after ' . rtrim(rtrim(sprintf('%.1f', (float) $timeoutSeconds), '0'), '.') . 's';
	}

	$tailStdout = stream_get_contents($pipes[1]);
	if (is_string($tailStdout) && $tailStdout !== '') {
		$stdout .= $tailStdout;
	}
	$tailStderr = stream_get_contents($pipes[2]);
	if (is_string($tailStderr) && $tailStderr !== '') {
		$stderr .= $tailStderr;
	}
	fclose($pipes[1]);
	fclose($pipes[2]);
	$status = proc_close($process);
	return [
		'exit_code' => $timedOut ? 124 : (is_int($status) ? $status : 1),
		'stdout' => is_string($stdout) ? $stdout : '',
		'stderr' => is_string($stderr) ? $stderr : '',
	];
}

function resolve_repo_root(): string
{
	$configPath = dirname((string) ($GLOBALS['argv'][0] ?? __FILE__)) . DIRECTORY_SEPARATOR . 'scpp.json';
	if (is_file($configPath)) {
		$json = file_get_contents($configPath);
		if (is_string($json) && $json !== '') {
			try {
				$config = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
				if (is_array($config) && isset($config['repo_root']) && is_string($config['repo_root']) && $config['repo_root'] !== '') {
					return normalize_path($config['repo_root']);
				}
			} catch (JsonException) {
				// Ignore invalid launcher-side config here; later diagnostics can report it.
			}
		}
	}

	return normalize_path(dirname(__DIR__));
}

function handle_init(string $cwd, array $args = []): void
{
	$phpProfile = 'legacy';
	foreach ($args as $arg) {
		if (str_starts_with($arg, '--php-profile=')) {
			$phpProfile = substr($arg, strlen('--php-profile='));
			continue;
		}
		scpp_fail('Unknown option for `scpp init`: ' . $arg . PHP_EOL, 1);
	}
	$command = new ProjectInitCommand($cwd, $phpProfile);
	$command->run();
}

function handle_clean(string $cwd): void
{
	$project = find_project_config($cwd);
	if ($project === null) {
		scpp_fail('No ' . SCPP_PROJECT_CONFIG . ' found in the current directory or any parent directory.' . PHP_EOL . 'Run `scpp init` in the project root first.' . PHP_EOL, 1);
	}

	$command = new ProjectCleanCommand($project['project_root'], $project['config_path']);
	$command->run();
}

function handle_update(array $args = []): void
{
	$options = [
		'force' => false,
	];
	foreach ($args as $arg) {
		if ($arg === '--force') {
			$options['force'] = true;
			continue;
		}
		scpp_fail('Unknown option for `scpp update`: ' . $arg . PHP_EOL, 1);
	}
	$command = new ScppUpdateCommand(resolve_repo_root(), $options);
	$command->run();
}

function handle_run(string $cwd, array $args): void
{
	$startedAt = microtime(true);
	$project = find_project_config($cwd);
	if ($project === null) {
		scpp_fail('No ' . SCPP_PROJECT_CONFIG . ' found in the current directory or any parent directory.' . PHP_EOL . 'Run `scpp init` in the project root first.' . PHP_EOL, 1);
	}

	$parsed = parse_run_command_arguments($args);
	$runArgs = $parsed['run_args'];
	$buildResult = execute_build($project['project_root'], $project['config_path'], $parsed['build_options']);
	$command = [$buildResult['output_path']];
	foreach ($runArgs as $arg) {
		$command[] = $arg;
	}

	$descriptor = [
		0 => ['file', 'php://stdin', 'r'],
		1 => ['pipe', 'w'],
		2 => ['pipe', 'w'],
	];
	$processEnv = scpp_runtime_library_process_environment(
		is_string($buildResult['runtime_library_dir'] ?? null) ? $buildResult['runtime_library_dir'] : null
	);
	$processEnv['SCPP_ERROR_FORMAT'] = 'json';
	$process = proc_open($command, $descriptor, $pipes, $project['project_root'], scpp_build_process_environment($processEnv));
	if (!is_resource($process)) {
		scpp_fail('Failed to start built program.' . PHP_EOL, 4);
	}
	$runOutput = scpp_collect_process_output($process, $pipes);
	$status = $runOutput['status'];
	$programStdout = $runOutput['stdout'];
	$programStderr = $runOutput['stderr'];
	if ($programStdout !== '') {
		scpp_write($programStdout, 'stdout');
	}
	if (!is_int($status)) {
		scpp_fail('Failed to read program exit status.' . PHP_EOL, 4);
	}
		$runtimeDiagnostic = $status !== 0 ? collect_runtime_error_diagnostic($programStderr) : null;
		if ($runtimeDiagnostic !== null) {
			$runtimeDiagnostic = remap_runtime_diagnostic(
				$project['project_root'],
				$buildResult['build_dir'],
				$runtimeDiagnostic,
				is_array($buildResult['generated_artifact_origins'] ?? null) ? $buildResult['generated_artifact_origins'] : []
			);
		}
	if ($programStderr !== '') {
		$stderrToShow = $runtimeDiagnostic !== null ? trim(remove_runtime_error_json_lines($programStderr)) : trim($programStderr);
		if ($stderrToShow !== '') {
			scpp_write($stderrToShow . PHP_EOL, 'stderr');
		}
	}
	write_last_run_report(
		$project['project_root'],
		'run',
		$GLOBALS['argv'] ?? ['scpp', 'run'],
		$status,
		$startedAt,
		microtime(true),
		[
			'entrypoint_output' => $buildResult['output_path'],
			'runtime_library_dir' => $buildResult['runtime_library_dir'],
			'run_args' => array_values($runArgs),
			'build_explanation' => $buildResult['build_explanation'] ?? null,
		]
	);
	if ($runtimeDiagnostic !== null) {
		$finishedAt = microtime(true);
		$config = load_project_config($project['config_path']);
		$phpProfile = resolve_php_runtime_profile(resolve_runtime_build_config($config));
		$shortMessage = render_short_runtime_failure($runtimeDiagnostic, $project['project_root'], $phpProfile);
		write_last_error_report(
			$project['project_root'],
			'run',
			$GLOBALS['argv'] ?? ['scpp', 'run'],
			$status,
			$startedAt,
			$finishedAt,
			'runtime',
			(string) ($runtimeDiagnostic['code'] ?? 'runtime_error'),
			$shortMessage,
			[$runtimeDiagnostic],
			$programStdout,
			$programStderr,
			runtime_failure_guidance($runtimeDiagnostic),
			$phpProfile
		);
		scpp_write(PHP_EOL . $shortMessage, 'stderr');
	}
	exit($status);
}

/**
 * @param resource $process
 * @param array<int, resource> $pipes
 * @return array{status:int,stdout:string,stderr:string}
 */
function scpp_collect_process_output($process, array $pipes): array
{
	$stdout = '';
	$stderr = '';
	$observedExitCode = null;
	foreach ([1, 2] as $index) {
		if (is_resource($pipes[$index] ?? null)) {
			stream_set_blocking($pipes[$index], false);
		}
	}
	while (true) {
		$status = proc_get_status($process);
		$stdout .= is_resource($pipes[1] ?? null) ? (string) stream_get_contents($pipes[1]) : '';
		$stderr .= is_resource($pipes[2] ?? null) ? (string) stream_get_contents($pipes[2]) : '';
		if (($status['running'] ?? false) !== true) {
			$exitCode = $status['exitcode'] ?? null;
			$observedExitCode = is_int($exitCode) ? $exitCode : null;
			break;
		}
		usleep(10000);
	}
	$stdout .= is_resource($pipes[1] ?? null) ? (string) stream_get_contents($pipes[1]) : '';
	$stderr .= is_resource($pipes[2] ?? null) ? (string) stream_get_contents($pipes[2]) : '';
	foreach ([1, 2] as $index) {
		if (is_resource($pipes[$index] ?? null)) {
			fclose($pipes[$index]);
		}
	}
	$closedStatus = proc_close($process);
	return [
		'status' => $observedExitCode ?? (is_int($closedStatus) ? $closedStatus : 1),
		'stdout' => $stdout,
		'stderr' => $stderr,
	];
}

/** @return array<string,string> */
function scpp_runtime_library_process_environment(?string $runtimeLibraryDir): array
{
	if ($runtimeLibraryDir === null || $runtimeLibraryDir === '') {
		return [];
	}

	if (PHP_OS_FAMILY === 'Windows') {
		return [
			'PATH' => prepend_path_env_value('PATH', $runtimeLibraryDir),
		];
	}

	if (PHP_OS_FAMILY === 'Darwin') {
		return [
			'DYLD_LIBRARY_PATH' => prepend_path_env_value('DYLD_LIBRARY_PATH', $runtimeLibraryDir),
		];
	}

	return [
		'LD_LIBRARY_PATH' => prepend_path_env_value('LD_LIBRARY_PATH', $runtimeLibraryDir),
	];
}

/** @return array<string,mixed>|null */
function collect_runtime_error_diagnostic(string $stderr): ?array
{
	foreach (preg_split('/\R/', trim($stderr)) ?: [] as $line) {
		$line = trim($line);
		if ($line === '' || !str_starts_with($line, '{"error":')) {
			continue;
		}
		try {
			$data = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
		} catch (JsonException) {
			continue;
		}
		$error = is_array($data['error'] ?? null) ? $data['error'] : null;
		if ($error === null) {
			continue;
		}
		$details = is_array($error['details'] ?? null) ? $error['details'] : [];
		$trace = [];
		foreach (($error['trace'] ?? []) as $traceLine) {
			if (is_string($traceLine) && trim($traceLine) !== '') {
				$trace[] = trim($traceLine);
			}
		}
		return [
			'severity' => 'error',
			'message' => (string) ($error['message'] ?? 'Runtime error.'),
			'code' => (string) ($error['code'] ?? 'runtime_error'),
				'operation' => (string) ($details['operation'] ?? ($error['operator'] ?? ($error['component'] ?? ''))),
				'operator' => isset($error['operator']) ? (string) $error['operator'] : null,
				'generated_file' => isset($details['generated_file']) ? (string) $details['generated_file'] : null,
				'generated_line' => isset($details['generated_line']) ? (int) $details['generated_line'] : null,
				'generated_column' => isset($details['generated_column']) ? (int) $details['generated_column'] : null,
				'original_file' => null,
				'original_line' => null,
				'original_relation' => null,
				'source_file' => isset($details['source_file']) ? normalize_path((string) $details['source_file']) : null,
				'source_line' => isset($details['source_line']) ? (int) $details['source_line'] : null,
			'expression' => isset($details['expression']) ? (string) $details['expression'] : null,
			'expected_type' => isset($details['expected_type']) ? (string) $details['expected_type'] : null,
			'actual_runtime_kind' => isset($details['runtime_kind']) ? (string) $details['runtime_kind'] : null,
			'json_path' => isset($details['json_path']) ? (string) $details['json_path'] : null,
			'target_type' => isset($details['target_type']) ? (string) $details['target_type'] : null,
			'actual_kind' => isset($details['actual_kind']) ? (string) $details['actual_kind'] : null,
			'trace' => $trace,
		];
	}
	return null;
}

/** @param array<string,mixed> $diagnostic @param array<string,string> $generatedArtifactOrigins @return array<string,mixed> */
function remap_runtime_diagnostic(string $projectRoot, string $buildDir, array $diagnostic, array $generatedArtifactOrigins): array
{
	$generatedFile = trim((string) ($diagnostic['generated_file'] ?? ''));
	$generatedLine = isset($diagnostic['generated_line']) ? (int) $diagnostic['generated_line'] : 0;
	if ($generatedFile === '' || $generatedLine <= 0) {
		return $diagnostic;
	}
	$generatedAbs = resolve_diagnostic_reported_path($projectRoot, $buildDir, $generatedFile);
	if ($generatedAbs === null) {
		return $diagnostic;
	}
	$diagnostic['generated_file'] = $generatedAbs;
	$originSource = $generatedArtifactOrigins[$generatedAbs] ?? null;
	if (!is_string($originSource) || $originSource === '') {
		return $diagnostic;
	}
	$originalLine = lookup_original_line_from_generated_map($generatedAbs, $generatedLine);
	if ($originalLine <= 0) {
		return $diagnostic;
	}
	$mapEntry = lookup_generated_map_entry($generatedAbs, $generatedLine);
	$diagnostic['original_file'] = normalize_path($originSource);
	$diagnostic['original_line'] = $originalLine;
	$diagnostic['original_relation'] = is_array($mapEntry) ? (string) $mapEntry['relation'] : 'exact';
	return $diagnostic;
}

/** @return array{function:string,file:string,line:int}|null */
function parse_runtime_trace_location(string $traceLine): ?array
{
	$traceLine = trim($traceLine);
	if ($traceLine === '') {
		return null;
	}
	if (!preg_match('/^(.*?) at (.+):([0-9]+)(?:\b.*)?$/', $traceLine, $matches)) {
		return null;
	}
	$functionName = trim((string) ($matches[1] ?? ''));
	$file = trim((string) ($matches[2] ?? ''));
	$line = (int) ($matches[3] ?? 0);
	if ($file === '' || $line <= 0) {
		return null;
	}
	return [
		'function' => $functionName,
		'file' => normalize_path($file),
		'line' => $line,
	];
}

/** @return list<array{function:string,file:string,line:int}> */
function build_runtime_source_trace_frames(string $projectRoot, array $diagnostic): array
{
	$trace = is_array($diagnostic['trace'] ?? null) ? $diagnostic['trace'] : [];
	if ($trace === []) {
		return [];
	}
	$generatedArtifactOrigins = [];
	$generatedFile = is_string($diagnostic['generated_file'] ?? null) ? (string) $diagnostic['generated_file'] : '';
	$originalFile = is_string($diagnostic['original_file'] ?? null) ? (string) $diagnostic['original_file'] : '';
	if ($generatedFile !== '' && $originalFile !== '') {
		$generatedArtifactOrigins[normalize_path($generatedFile)] = normalize_path($originalFile);
	}

	$frames = [];
	$seen = [];
	foreach ($trace as $traceLine) {
		if (!is_string($traceLine)) {
			continue;
		}
		$parsed = parse_runtime_trace_location($traceLine);
		if ($parsed === null) {
			continue;
		}
		if (strpos($parsed['file'], '/.prism/generated/') === false) {
			continue;
		}
		$mapped = remap_runtime_diagnostic(
			$projectRoot,
			dirname($parsed['file']),
			[
				'generated_file' => $parsed['file'],
				'generated_line' => $parsed['line'],
			],
			$generatedArtifactOrigins
		);
		$mappedFile = is_string($mapped['original_file'] ?? null) ? (string) $mapped['original_file'] : '';
		$mappedLine = isset($mapped['original_line']) ? (int) $mapped['original_line'] : 0;
		if ($mappedFile === '' || $mappedLine <= 0) {
			continue;
		}
		$key = $mappedFile . ':' . $mappedLine;
		if (isset($seen[$key])) {
			continue;
		}
		$seen[$key] = true;
		$frames[] = [
			'function' => $parsed['function'],
			'file' => $mappedFile,
			'line' => $mappedLine,
		];
	}
	return $frames;
}

function remove_runtime_error_json_lines(string $stderr): string
{
	$lines = [];
	foreach (preg_split('/\R/', $stderr) ?: [] as $line) {
		if (str_starts_with(trim($line), '{"error":')) {
			continue;
		}
		$lines[] = $line;
	}
	return implode(PHP_EOL, $lines);
}

function scpp_runtime_source_snippet_context_requested(): bool
{
	$value = getenv('SCPP_SHOW_SOURCE_SNIPPETS');
	return is_string($value) && $value === '1';
}

function build_runtime_failure_summary(array $diagnostic): string
{
	$expression = trim((string) ($diagnostic['expression'] ?? ''));
	$expected = trim((string) ($diagnostic['expected_type'] ?? ''));
	if ($expression !== '') {
		$line = 'Cannot convert value used for ' . $expression;
		if ($expected !== '') {
			$line .= ' to required ' . $expected;
		}
		return $line . '.';
	}
	if ($expected !== '') {
		return 'Cannot convert value to required ' . $expected . '.';
	}
	return 'Cannot convert value at this typed boundary.';
}

function trim_runtime_source_snippet_line(string $line, int $limit = 160): string
{
	if ($limit <= 0) {
		return '';
	}
	if (strlen($line) <= $limit) {
		return $line;
	}
	if ($limit <= 3) {
		return substr($line, 0, $limit);
	}
	return substr($line, 0, $limit - 3) . '...';
}

function read_runtime_source_line(string $path, int $lineNumber): ?string
{
	if ($path === '' || $lineNumber <= 0 || !is_file($path) || !is_readable($path)) {
		return null;
	}
	$contents = file($path, FILE_IGNORE_NEW_LINES);
	if (!is_array($contents) || !isset($contents[$lineNumber - 1])) {
		return null;
	}
	return trim_runtime_source_snippet_line(rtrim((string) $contents[$lineNumber - 1]));
}

/** @return list<string> */
function build_runtime_source_snippet_lines(string $projectRoot, array $diagnostic): array
{
	$originalFile = is_string($diagnostic['original_file'] ?? null) ? (string) $diagnostic['original_file'] : '';
	$originalLine = isset($diagnostic['original_line']) ? (int) $diagnostic['original_line'] : 0;
	if ($originalFile === '' || $originalLine <= 0 || !is_file($originalFile) || !is_readable($originalFile)) {
		return [];
	}
	$contents = file($originalFile, FILE_IGNORE_NEW_LINES);
	if (!is_array($contents) || $contents === []) {
		return [];
	}
	$radius = scpp_runtime_source_snippet_context_requested() ? 1 : 0;
	$start = max(1, $originalLine - $radius);
	$end = min(count($contents), $originalLine + $radius);
	$lines = [$radius > 0 ? 'Around:' : 'Source:'];
	for ($lineNumber = $start; $lineNumber <= $end; $lineNumber++) {
		$rawLine = rtrim((string) ($contents[$lineNumber - 1] ?? ''));
		$trimmedLine = trim_runtime_source_snippet_line($rawLine);
		$prefix = $lineNumber === $originalLine ? '>' : ' ';
		$lines[] = sprintf('%s %d | %s', $prefix, $lineNumber, $trimmedLine);
	}
	if ($radius > 0) {
		$lines[] = 'Expanded source context shown because SCPP_SHOW_SOURCE_SNIPPETS=1.';
	}
	return $lines;
}

/** @return list<string> */
function build_runtime_trace_lines(array $diagnostic, int $maxFrames = 4): array
{
	$projectRoot = is_string($diagnostic['project_root'] ?? null) ? (string) $diagnostic['project_root'] : '';
	$sourceFrames = $projectRoot !== '' ? build_runtime_source_trace_frames($projectRoot, $diagnostic) : [];
	if ($sourceFrames === []) {
		return [];
	}
	$maxFrames = max(1, $maxFrames);
	$lines = ['Trace:'];
	foreach (array_slice($sourceFrames, 0, $maxFrames) as $frame) {
		$location = normalize_config_path(relative_path($projectRoot, $frame['file'])) . ':' . $frame['line'];
		$sourceLine = read_runtime_source_line($frame['file'], $frame['line']);
		if ($sourceLine !== null && $sourceLine !== '') {
			$lines[] = '  at ' . $location . ' | ' . $sourceLine;
			continue;
		}
		$lines[] = '  at ' . $location;
	}
	$remaining = count($sourceFrames) - min(count($sourceFrames), $maxFrames);
	if ($remaining > 0) {
		$lines[] = '  ... ' . $remaining . ' more frame(s)';
	}
	$rawTrace = is_array($diagnostic['trace'] ?? null) ? $diagnostic['trace'] : [];
	if ($rawTrace !== []) {
		$lines[] = "  More trace detail is available in 'scpp full-error'.";
	}
	return $lines;
}

/** @param array<string,mixed> $diagnostic */
function render_runtime_failure_lines(
	array $diagnostic,
	string $projectRoot,
	bool $includeStrictHint = true,
	bool $includeFollowupHints = true,
	?string $projectMode = null,
	bool $includeRuntimeMessage = true
): array
{
	$diagnostic['project_root'] = $projectRoot;
	$lines = [];
	$strictHint = $includeStrictHint ? strict_project_error_hint($projectMode) : null;
	if ($strictHint !== null && $strictHint !== '') {
		$lines[] = $strictHint;
	}
	$originalFile = is_string($diagnostic['original_file'] ?? null) ? (string) $diagnostic['original_file'] : '';
	$originalLine = isset($diagnostic['original_line']) ? (int) $diagnostic['original_line'] : 0;
	$sourceFile = is_string($diagnostic['source_file'] ?? null) ? (string) $diagnostic['source_file'] : '';
	$sourceLine = isset($diagnostic['source_line']) ? (int) $diagnostic['source_line'] : 0;
	$displayFile = $originalFile !== '' ? $originalFile : $sourceFile;
	$displayLine = $originalLine > 0 ? $originalLine : $sourceLine;
	if ($displayFile !== '' && $displayLine > 0) {
		$relation = $displayFile === $originalFile ? trim((string) ($diagnostic['original_relation'] ?? 'exact')) : 'exact';
		$prefix = $relation === 'exact' ? '' : ($relation === 'around' ? 'around ' : 'near ');
		$lines[] = 'Runtime error in ' . $prefix . normalize_config_path(relative_path($projectRoot, $displayFile)) . ':' . $displayLine;
	} else {
		$lines[] = 'Runtime error while running the built program.';
	}
	$actual = trim((string) ($diagnostic['actual_runtime_kind'] ?? ''));
	$operation = trim((string) ($diagnostic['operation'] ?? ''));
	$lines[] = build_runtime_failure_summary($diagnostic);
	foreach (build_runtime_source_snippet_lines($projectRoot, $diagnostic) as $snippetLine) {
		$lines[] = $snippetLine;
	}
	if ($actual !== '') {
		$lines[] = 'Actual runtime kind: ' . $actual;
	}
	if ($operation !== '') {
		$lines[] = 'Operation: ' . $operation;
	}
	$message = trim((string) ($diagnostic['message'] ?? ''));
	if ($includeRuntimeMessage && $message !== '') {
		$lines[] = 'Runtime message: ' . $message;
	}
	foreach (build_runtime_trace_lines($diagnostic) as $traceLine) {
		$lines[] = $traceLine;
	}
	if ($includeFollowupHints) {
		$lines[] = "Run 'scpp error' for more details.";
		$lines[] = "Run 'scpp full-error' for the saved JSON report.";
	}
	return $lines;
}

/** @param array<string,mixed> $diagnostic */
function render_short_runtime_failure(array $diagnostic, string $projectRoot, ?string $projectMode = null): string
{
	return implode(PHP_EOL, render_runtime_failure_lines($diagnostic, $projectRoot, false, true, $projectMode, false)) . PHP_EOL;
}

/** @param array<string,mixed> $diagnostic @return list<string> */
function runtime_failure_guidance(array $diagnostic): array
{
	$guidance = [
		'Check the source line reported above and stabilize mixed values before the failing cast or typed boundary.',
	];
	$actual = trim((string) ($diagnostic['actual_runtime_kind'] ?? ''));
	if ($actual !== '') {
		$guidance[] = 'Actual runtime kind was ' . $actual . '; inspect the JSON/hash shape that produced that value.';
	}
	return append_standard_report_guidance($guidance, false);
}

function prepend_path_env_value(string $name, string $path): string
{
	$existing = getenv($name);
	if (!is_string($existing) || $existing === '') {
		return $path;
	}
	return $path . PATH_SEPARATOR . $existing;
}


function handle_usability_harness(string $cwd, array $args): void
{
	$repoRoot = resolve_repo_root();
	$scriptPath = normalize_path($repoRoot . '/tools/usability_harness/run.php');
	if (!is_file($scriptPath)) {
		scpp_fail('Usability harness script not found: ' . $scriptPath . PHP_EOL, 1);
	}

	$command = build_php_script_command($repoRoot, $scriptPath, $args, true);
	$descriptor = [
		0 => ['file', 'php://stdin', 'r'],
		1 => ['file', 'php://stdout', 'w'],
		2 => ['file', 'php://stderr', 'w'],
	];
	$process = proc_open($command, $descriptor, $pipes, $cwd, scpp_build_process_environment());
	if (!is_resource($process)) {
		scpp_fail('Failed to start usability harness.' . PHP_EOL, 4);
	}
	$status = proc_close($process);
	if (!is_int($status)) {
		scpp_fail('Failed to read usability harness exit status.' . PHP_EOL, 4);
	}
	exit($status);
}

function build_php_script_command(string $repoRoot, string $scriptPath, array $args, bool $needsAst): array
{
	$phpBinary = resolve_php_cli_binary();
	$command = [$phpBinary];
	$astSoPath = resolve_bundled_ast_extension_path($repoRoot);
	if ($needsAst && $astSoPath !== null) {
		$command[] = '-dextension=' . $astSoPath;
	}
	$command[] = $scriptPath;
	foreach ($args as $arg) {
		$command[] = $arg;
	}
	return $command;
}

function resolve_bundled_ast_extension_path(string $repoRoot): ?string
{
	$candidates = [
		$repoRoot . '/ext/8.4-deb/ast.so',
		$repoRoot . '/ext/8.4-deb_php_ast.so',
		$repoRoot . '/ext/ast.so',
	];
	foreach ($candidates as $candidate) {
		$normalized = normalize_path($candidate);
		if (is_file($normalized)) {
			return $normalized;
		}
	}
	return null;
}

function resolve_php_cli_binary(): string
{
	$php84 = find_command_path(['php8.4']);
	if ($php84 !== null) {
		return $php84;
	}
	return PHP_BINARY;
}

function handle_build(string $cwd, array $args = []): void
{
	$project = find_project_config($cwd);
	if ($project === null) {
		scpp_fail('No ' . SCPP_PROJECT_CONFIG . ' found in the current directory or any parent directory.' . PHP_EOL . 'Run `scpp init` in the project root first.' . PHP_EOL, 1);
	}

	execute_build($project['project_root'], $project['config_path'], parse_build_command_arguments($args));
}

function handle_runtime_build(string $cwd, array $args = []): void
{
	$startedAt = microtime(true);
	$options = parse_runtime_build_command_arguments($args);
	$project = find_project_config($cwd);
	$config = null;
	$projectRoot = $cwd;
	if ($project !== null) {
		$config = load_project_config($project['config_path']);
		$projectRoot = $project['project_root'];
	}
	$result = scpp_build_runtime_from_config(resolve_repo_root(), $config, $projectRoot, $options['build_mode'], $options['force'], 'reuse');
	write_last_run_report(
		$projectRoot,
		'runtime-build',
		$GLOBALS['argv'] ?? ['scpp', 'runtime-build'],
		0,
		$startedAt,
		microtime(true),
		[
			'build_mode' => $result['build_mode'],
			'runtime_artifact' => $result['artifact_path'],
		]
	);
	echo 'Runtime build completed: ' . $result['artifact_path'] . PHP_EOL;
	echo 'Runtime build mode: ' . $result['build_mode'] . PHP_EOL;
}

function handle_error_report(string $cwd, bool $full): void
{
	[$project, $path, $data] = load_project_report($cwd, '.prism/last_error.json', 'error');
	if ($full) {
		scpp_write(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
		return;
	}
	$output = [];
	$diagnostics = is_array($data['diagnostics'] ?? null) ? $data['diagnostics'] : [];
	$projectMode = trim((string) ($data['project_mode'] ?? ''));
	$primaryDiagnostic = is_array($diagnostics[0] ?? null) ? $diagnostics[0] : null;
	if ($primaryDiagnostic !== null) {
		foreach (render_runtime_failure_lines($primaryDiagnostic, $project['project_root'], false, false, $projectMode) as $line) {
			$output[] = $line;
		}
	} else {
		$output[] = (string) ($data['short_message'] ?? 'Unknown error.');
	}
	if (isset($data['category'], $data['subcategory'])) {
		$output[] = 'Category: ' . $data['category'] . ' / ' . $data['subcategory'];
	}
	$output[] = 'Saved report: ' . normalize_config_path(relative_path($project['project_root'], $path));
	if (isset($data['duration_ms'])) {
		$output[] = 'Duration: ' . (int) $data['duration_ms'] . 'ms';
	}
	$guidance = is_array($data['guidance'] ?? null) ? $data['guidance'] : [];
	foreach ($guidance as $item) {
		if (is_string($item) && $item !== '') {
			$output[] = 'Next: ' . $item;
		}
	}
	if ($diagnostics === []) {
		$rawOutput = is_array($data['raw_output'] ?? null) ? $data['raw_output'] : [];
		$stderr = trim((string) ($rawOutput['stderr'] ?? ''));
		if ($stderr !== '') {
			$firstLine = preg_split('/\R/', $stderr, 2);
			if (is_array($firstLine) && isset($firstLine[0]) && trim((string) $firstLine[0]) !== '') {
				$output[] = 'Raw: ' . trim((string) $firstLine[0]);
			}
		}
	}
	scpp_write(implode(PHP_EOL, $output) . PHP_EOL);
}

function handle_last_run_report(string $cwd, bool $full): void
{
	[$project, $path, $data] = load_project_report($cwd, '.prism/last_run.json', 'last run');
	if ($full) {
		scpp_write(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
		return;
	}
	$output = [];
	$output[] = 'Last run: ' . (string) ($data['command'] ?? 'unknown');
	$output[] = 'Saved report: ' . normalize_config_path(relative_path($project['project_root'], $path));
	if (isset($data['status'], $data['exit_code'])) {
		$output[] = 'Status: ' . $data['status'] . ' (exit ' . $data['exit_code'] . ')';
	}
	if (isset($data['duration_ms'])) {
		$output[] = 'Duration: ' . $data['duration_ms'] . ' ms';
	}
	if (isset($data['started_at'])) {
		$output[] = 'Started: ' . $data['started_at'];
	}
	if (isset($data['finished_at'])) {
		$output[] = 'Finished: ' . $data['finished_at'];
	}
	$details = is_array($data['details'] ?? null) ? $data['details'] : [];
	if (isset($details['compiler'])) {
		$output[] = 'Compiler: ' . $details['compiler'];
	}
	if (isset($details['ninja_command']) && is_array($details['ninja_command'])) {
		$output[] = 'Ninja: ' . implode(' ', array_map(static fn ($value): string => (string) $value, $details['ninja_command']));
	}
	if (isset($details['output_path'])) {
		$output[] = 'Output: ' . normalize_config_path(relative_path($project['project_root'], (string) $details['output_path']));
	}
	scpp_write(implode(PHP_EOL, $output) . PHP_EOL);
}

/** @param list<string> $args */
function handle_explain_build_report(string $cwd, array $args = []): void
{
	[$project, $path, $data] = load_project_report($cwd, '.prism/last_run.json', 'last run');
	$output = [];
	$command = (string) ($data['command'] ?? 'unknown');
	$output[] = 'Explain build: ' . $command;
	$output[] = 'Saved report: ' . normalize_config_path(relative_path($project['project_root'], $path));
	if ($command !== 'build' && $command !== 'run') {
		$output[] = 'No build explanation available for this command.';
		scpp_write(implode(PHP_EOL, $output) . PHP_EOL);
		return;
	}

	$details = is_array($data['details'] ?? null) ? $data['details'] : [];
	$buildExplanation = is_array($details['build_explanation'] ?? null) ? $details['build_explanation'] : [];
	$view = strtolower(trim((string) ($args[0] ?? '')));
	foreach (render_explain_build_view_lines($buildExplanation, $view) as $line) {
		$output[] = $line;
	}
	scpp_write(implode(PHP_EOL, $output) . PHP_EOL);
}

/**
 * @return array{array{project_root:string,config_path:string},string,array<string,mixed>}
 */
function load_project_report(string $cwd, string $relativePath, string $label): array
{
	$project = find_project_config($cwd);
	if ($project === null) {
		scpp_fail('No ' . SCPP_PROJECT_CONFIG . ' found in the current directory or any parent directory.' . PHP_EOL, 1);
	}
	$path = normalize_path($project['project_root'] . '/' . ltrim($relativePath, '/'));
	if (!is_file($path)) {
		scpp_fail('No saved ' . $label . ' report found at ' . normalize_config_path(relative_path($project['project_root'], $path)) . PHP_EOL, 1);
	}
	$json = file_get_contents($path);
	if (!is_string($json) || trim($json) === '') {
		scpp_fail('Saved ' . $label . ' report is empty: ' . $path . PHP_EOL, 1);
	}
	$data = json_decode($json, true);
	if (!is_array($data)) {
		scpp_fail('Saved ' . $label . ' report is invalid JSON: ' . $path . PHP_EOL, 1);
	}
	return [$project, $path, $data];
}

/**
 * @param array{compile_runtime?:bool,compile_dependencies?:bool,force_runtime_rebuild?:bool,disable_stan?:bool,show_timings?:bool,entry_override?:?string} $options
 * @return array{project_root:string,build_dir:string,output_name:string,output_path:string,fastcgi_output_path:?string,runtime_library_dir:?string,generated_artifact_origins:array<string,string>,timing_breakdown_ms:array<string,int>,build_explanation:array<string,mixed>}
 */
function execute_build(string $projectRoot, string $configPath, array $options = []): array
{
	$startedAt = microtime(true);
	$timingMarks = [];
	$markTiming = static function (string $label) use (&$timingMarks): void {
		$timingMarks[$label] = microtime(true);
	};
	$timingMs = static function (string $from, string $to) use (&$timingMarks): int {
		if (!isset($timingMarks[$from], $timingMarks[$to])) {
			return 0;
		}
		return (int) round(max(0, ($timingMarks[$to] - $timingMarks[$from]) * 1000));
	};
	$markTiming('execute_build_start');
	$options = normalize_build_execution_options($options);
	$markTiming('options_normalized');
	$config = load_project_config($configPath);
	$sourceOverrides = normalize_source_override_map($options['source_overrides'] ?? []);
	$markTiming('config_loaded');
	if (!$options['disable_stan']) {
		execute_stan_build_preflight($projectRoot, $configPath, $config, $sourceOverrides);
	}
	$markTiming('stan_checked');
	$projectGraph = resolve_project_dependency_graph($projectRoot, $configPath, $config);
	$markTiming('project_graph_resolved');
	$entrypointAbs = resolve_build_entrypoint($projectRoot, $config, $options['entry_override']);

	$ninjaPath = find_command_path(['ninja']);
	if ($ninjaPath === null) {
		scpp_fail("Ninja not found. Install it and retry.\n" . install_hint_for_ninja() . PHP_EOL, 1);
	}

	$compiler = resolve_compiler($config);
	if ($compiler === null) {
		scpp_fail("No supported C++ compiler found.\n" . install_hint_for_compiler() . PHP_EOL, 1);
	}
	$buildMode = resolve_build_mode($config);
	if (is_string($options['build_mode'] ?? null) && $options['build_mode'] !== '') {
		$requestedBuildMode = strtolower(trim((string) $options['build_mode']));
		$buildMode = in_array($requestedBuildMode, ['debug', 'dev', 'development'], true) ? 'debug' : ($requestedBuildMode === 'release' ? 'release' : $buildMode);
	}
	$config = apply_build_runtime_module_overrides($config, $options);
	$runtimeConfig = resolve_runtime_build_config($config);

	if (is_string($options['debug_session_id'] ?? null) && $options['debug_session_id'] !== '') {
		$debugRoot = is_string($options['debug_session_root'] ?? null) && trim((string) $options['debug_session_root']) !== ''
			? normalize_config_path(trim((string) $options['debug_session_root']))
			: '.prism/debug/session';
		$config['build_dir'] = $debugRoot . '/build';
		$config['generated_dir'] = $debugRoot . '/generated';
		$config['cache_dir'] = $debugRoot . '/cache';
		foreach ($projectGraph as &$projectSpec) {
			if (normalize_path((string) ($projectSpec['project_root'] ?? '')) !== normalize_path($projectRoot)) {
				continue;
			}
			$projectSpec['config'] = $config;
			break;
		}
		unset($projectSpec);
	}

	$buildDir = normalize_path($projectRoot . '/' . normalize_config_path((string) ($config['build_dir'] ?? '.prism/build')));
	$repoRoot = resolve_repo_root();
	$fastcgiConfig = resolve_fastcgi_config($config);
	$projectContexts = build_project_contexts($projectGraph);
	$rootContext = $projectContexts[$projectRoot] ?? null;
	if (!is_array($rootContext)) {
		scpp_fail('Internal error: missing root project build context.' . PHP_EOL, 4);
	}
	$generatedDir = $rootContext['generated_dir'];
	$cacheDir = $rootContext['cache_dir'];
	$fastcgiBuild = $fastcgiConfig['enabled'] ? resolve_fastcgi_build_spec($projectRoot, $repoRoot, $buildDir, $generatedDir, $entrypointAbs, $compiler, $fastcgiConfig) : null;
	ensure_directory($buildDir);
	$usePch = array_key_exists('use_pch', $options) && is_bool($options['use_pch']) ? $options['use_pch'] : supports_compiler_pch($compiler);
	$runtimeBuildSignature = compute_runtime_build_signature($repoRoot, $compiler, $buildMode, $runtimeConfig);
	$phpProfile = resolve_php_runtime_profile($runtimeConfig);
	$sourceOverrides = is_array($options['source_overrides'] ?? null) ? normalize_source_override_map($options['source_overrides']) : [];
	$transpiler = new Transpiler(phpProfile: $phpProfile);
	$stanFrontendClassifications = $options['disable_stan'] ? [] : load_stan_frontend_classifications_for_build($rootContext['cache_dir'] . '/' . SCPP_STAN_STATE_FILE);
	$generatorSignature = compute_s2s_generator_signature($repoRoot, $phpProfile, $sourceOverrides);
	$projectLibraryFlags = resolve_project_library_link_flags($projectRoot, $projectGraph, $compiler);
	$generatedUnits = [];
	$nativeCppUnits = [];
	/** @var array<string,string> $generatedArtifactOrigins */
	$generatedArtifactOrigins = [];
	$transpiledCount = 0;
	$skippedCount = 0;
	$sourceRebuildReasons = [];

	$markTiming('source_scan_start');
	foreach ($projectContexts as $contextProjectRoot => &$projectContext) {
		ensure_directory($projectContext['generated_dir']);
		ensure_directory($projectContext['cache_dir']);
		$projectContext['state_path'] = $projectContext['cache_dir'] . '/' . SCPP_STATE_FILE;
		$projectContext['state'] = load_s2s_state($projectContext['state_path']);
		$projectContext['php_files'] = collect_project_php_files($contextProjectRoot);
		if (normalize_path($contextProjectRoot) === normalize_path($projectRoot) && !in_array($entrypointAbs, $projectContext['php_files'], true)) {
			$projectContext['php_files'][] = $entrypointAbs;
			sort($projectContext['php_files'], SORT_STRING);
		}
		$projectContext['native_cpp_files'] = collect_project_native_cpp_files($projectContext['native_cpp_dir']);
		if (normalize_path($contextProjectRoot) === normalize_path($projectRoot)) {
			foreach ((array) ($options['extra_native_cpp_files'] ?? []) as $extraNativeCppFile) {
				if (is_string($extraNativeCppFile) && $extraNativeCppFile !== '' && is_file($extraNativeCppFile)) {
					$projectContext['native_cpp_files'][] = normalize_path($extraNativeCppFile);
				}
			}
			$projectContext['native_cpp_files'] = array_values(array_unique($projectContext['native_cpp_files']));
			sort($projectContext['native_cpp_files'], SORT_STRING);
		}
		$projectContext['generated_headers'] = [];
		$projectContext['export_manifests'] = [];

		foreach ($projectContext['php_files'] as $phpPathAbs) {
			$relativePhp = normalize_config_path(relative_path($contextProjectRoot, $phpPathAbs));
			$generatedBase = build_generated_base($projectContext['generated_dir'], $relativePhp);
			$generatedHeader = $generatedBase . '.hpp';
			$generatedExportManifest = $generatedBase . '.exports.json';
			$generatedCpp = $generatedBase . '.cpp';
			$generatedArtifactOrigins[normalize_path($generatedHeader)] = normalize_path($phpPathAbs);
			$generatedArtifactOrigins[normalize_path($generatedCpp)] = normalize_path($phpPathAbs);
			$emitProgramEntry = normalize_path($contextProjectRoot) === normalize_path($projectRoot) && $phpPathAbs === $entrypointAbs;
			$meta = build_file_meta($phpPathAbs);
			$sourceOverride = array_key_exists(normalize_path($phpPathAbs), $sourceOverrides) ? (string) $sourceOverrides[normalize_path($phpPathAbs)] : null;
			$previous = is_array($projectContext['state']['files'][$relativePhp] ?? null) ? $projectContext['state']['files'][$relativePhp] : null;
			$transpileReasons = collect_transpile_reasons(
				$previous,
				$meta,
				$generatorSignature,
				$generatedHeader,
				$generatedCpp,
				$emitProgramEntry,
				$generatedExportManifest
			);
			if ($sourceOverride !== null) {
				$transpileReasons[] = 'debug source override active';
			}
			$needsTranspile = $transpileReasons !== [];

			$cppFile = null;
			if ($needsTranspile) {
				try {
					$transpilePath = $phpPathAbs;
					$transpileSourceOverride = $sourceOverride;
					if (is_jss_source_path($phpPathAbs)) {
						$jssSource = $sourceOverride ?? file_get_contents($phpPathAbs);
						if (!is_string($jssSource)) {
							scpp_fail('Failed to read JSS input: ' . $phpPathAbs . PHP_EOL, 3);
						}
						$jssClassifications = filter_stan_frontend_classifications_for_source($stanFrontendClassifications, $phpPathAbs);
						$jssPhs = $jssClassifications !== []
							? $transpiler->transpileJssToPhsWithClassifications($jssSource, $phpPathAbs, $jssClassifications)
							: $transpiler->transpileJssToPhs($jssSource);
						$jssArtifact = build_jss_intermediate_phs_path($contextProjectRoot, $relativePhp);
						write_text_file($jssArtifact, $jssPhs);
						$generatedArtifactOrigins[normalize_path($jssArtifact)] = normalize_path($phpPathAbs);
						if ($jssClassifications !== []) {
							$transpilePath = $jssArtifact;
							$transpileSourceOverride = $jssPhs;
						}
					}
					$cppFile = $transpiler->transpile($transpilePath, false, $emitProgramEntry, $transpileSourceOverride);
				} catch (S2SException $e) {
					scpp_fail($e->getMessage() . PHP_EOL, 3);
				} catch (Throwable $e) {
					scpp_fail('internal error: ' . $e->getMessage() . PHP_EOL, 4);
				}

				write_text_file($generatedHeader, implode(PHP_EOL, $cppFile->headerLines) . PHP_EOL);
				write_generated_line_map_file($generatedHeader . '.line.tsv', $cppFile->headerLineMap);
				write_text_file($generatedCpp, implode(PHP_EOL, $cppFile->sourceLines) . PHP_EOL);
				write_generated_line_map_file($generatedCpp . '.line.tsv', $cppFile->sourceLineMap);
				write_export_manifest_file($generatedExportManifest, $cppFile->exportManifest);
				$transpiledCount++;
				$sourceRebuildReasons[] = [
					'project_root' => normalize_path($contextProjectRoot),
					'path' => normalize_config_path(relative_path($contextProjectRoot, $phpPathAbs)),
					'generated_cpp' => normalize_config_path(relative_path($projectRoot, $generatedCpp)),
					'object_path' => normalize_config_path(relative_path($projectRoot, build_object_path($projectContext['build_dir'], build_project_scoped_relative_path($projectRoot, $contextProjectRoot, $relativePhp), $compiler['kind']))),
					'is_entrypoint' => $emitProgramEntry,
					'action' => 'transpiled',
					'reasons' => $transpileReasons,
				];
			} else {
				$skippedCount++;
				$sourceRebuildReasons[] = [
					'project_root' => normalize_path($contextProjectRoot),
					'path' => normalize_config_path(relative_path($contextProjectRoot, $phpPathAbs)),
					'generated_cpp' => normalize_config_path(relative_path($projectRoot, $generatedCpp)),
					'object_path' => normalize_config_path(relative_path($projectRoot, build_object_path($projectContext['build_dir'], build_project_scoped_relative_path($projectRoot, $contextProjectRoot, $relativePhp), $compiler['kind']))),
					'is_entrypoint' => $emitProgramEntry,
					'action' => 'reused',
					'reasons' => ['source metadata and generated artifacts unchanged'],
				];
			}

			$hasExportManifest = is_file($generatedExportManifest);
			$generatedUnits[] = [
				'project_root' => $contextProjectRoot,
				'relative_php' => $relativePhp,
				'generated_cpp' => $generatedCpp,
				'object_path' => build_object_path($projectContext['build_dir'], build_project_scoped_relative_path($projectRoot, $contextProjectRoot, $relativePhp), $compiler['kind']),
				'is_entrypoint' => $emitProgramEntry,
				'force_include_header' => null,
			];
			$projectContext['generated_headers'][] = $generatedHeader;
			if ($hasExportManifest) {
				$projectContext['export_manifests'][] = $generatedExportManifest;
			}

			if ($fastcgiBuild !== null && $contextProjectRoot === $projectRoot && $phpPathAbs === $entrypointAbs) {
				try {
					$fcgiCppFile = $transpiler->transpile($phpPathAbs, false, false, $sourceOverride);
				} catch (S2SException $e) {
					scpp_fail($e->getMessage() . PHP_EOL, 3);
				} catch (Throwable $e) {
					scpp_fail('internal error: ' . $e->getMessage() . PHP_EOL, 4);
				}
				$fcgiBase = build_generated_fcgi_base($generatedDir, $relativePhp);
				$generatedArtifactOrigins[normalize_path($fcgiBase . '.hpp')] = normalize_path($phpPathAbs);
				$generatedArtifactOrigins[normalize_path($fcgiBase . '.cpp')] = normalize_path($phpPathAbs);
				write_text_file($fcgiBase . '.hpp', implode(PHP_EOL, $fcgiCppFile->headerLines) . PHP_EOL);
				write_generated_line_map_file($fcgiBase . '.hpp.line.tsv', $fcgiCppFile->headerLineMap);
				write_text_file($fcgiBase . '.cpp', implode(PHP_EOL, $fcgiCppFile->sourceLines) . PHP_EOL);
				write_generated_line_map_file($fcgiBase . '.cpp.line.tsv', $fcgiCppFile->sourceLineMap);
				$fastcgiBuild['entrypoint_generated_cpp'] = normalize_config_path(relative_path($projectRoot, $fcgiBase . '.cpp'));
				$fastcgiBuild['entrypoint_object_path'] = normalize_config_path(relative_path($projectRoot, build_fcgi_object_path($buildDir, $relativePhp, $compiler['kind'])));
			}
			$projectContext['state']['files'][$relativePhp] = [
				'size' => $meta['size'],
				'mtime' => $meta['mtime'],
				'content_hash' => $meta['content_hash'],
				'generator_signature' => $generatorSignature,
				'generated_base' => normalize_config_path(relative_path($contextProjectRoot, $generatedBase)),
				'emit_program_entry' => $emitProgramEntry,
				'has_export_manifest' => $hasExportManifest,
			];
		}

		foreach ($projectContext['native_cpp_files'] as $nativeCppPath) {
			$nativeCppUnits[] = [
				'project_root' => $contextProjectRoot,
				'source_path' => $nativeCppPath,
				'object_path' => build_native_object_path(
					$projectContext['build_dir'],
					build_project_scoped_relative_path(
						$projectRoot,
						$contextProjectRoot,
						normalize_config_path(relative_path($contextProjectRoot, $nativeCppPath))
					),
					$compiler['kind']
				),
				'force_include_header' => null,
			];
		}

		$projectContext['state'] = prune_removed_state_entries(
			$contextProjectRoot,
			$projectContext['generated_dir'],
			$projectContext['state'],
			$projectContext['php_files']
		);
		$projectContext['state']['version'] = 1;
		$projectContext['state']['project_root'] = $contextProjectRoot;
		$projectContext['state']['updated_at'] = time();
		save_s2s_state($projectContext['state_path'], $projectContext['state']);
		write_project_forward_declaration_header($projectContext['generated_dir'], $projectContext['generated_headers'] ?? []);
		write_text_file($projectContext['generated_dir'] . '/__project.hpp', render_project_export_header($projectContext['generated_dir'], $projectContext['export_manifests'] ?? []));
	}
	unset($projectContext);
	$markTiming('source_scan_complete');
	write_text_file($buildDir . '/runtime_signature.txt', $runtimeBuildSignature . PHP_EOL);
	$projectUnitForceIncludes = write_project_unit_force_include_headers($projectContexts);
	foreach ($generatedUnits as &$unit) {
		$unit['force_include_header'] = $projectUnitForceIncludes[normalize_path($unit['project_root'])] ?? null;
	}
	unset($unit);
	foreach ($nativeCppUnits as &$nativeUnit) {
		$nativeUnit['force_include_header'] = $projectUnitForceIncludes[normalize_path($nativeUnit['project_root'])] ?? null;
	}
	unset($nativeUnit);

	if ($usePch) {
		write_text_file(build_app_pch_header_path($buildDir), render_app_pch_header());
		write_text_file(build_runtime_pch_header_path($buildDir), render_runtime_pch_header());
	}

	$outputName = build_output_name($entrypointAbs);
	$entryGeneratedUnit = null;
	foreach ($generatedUnits as $unit) {
		if (($unit['is_entrypoint'] ?? false) === true) {
			$entryGeneratedUnit = $unit;
			break;
		}
	}
	$runtimePlacementForInvocation = $options['compile_runtime'] ? 'local' : 'reuse';
	$effectiveBuildOptions = $options;
	if ($options['compile_runtime']) {
		scpp_build_runtime_from_config($repoRoot, $config, $projectRoot, $buildMode, $options['force_runtime_rebuild'], 'local');
		$effectiveBuildOptions['compile_runtime'] = false;
		$effectiveBuildOptions['force_runtime_rebuild'] = false;
	}
	$effectiveBuildOptions['use_pch'] = $usePch;
	$markTiming('runtime_prepare_complete');
	$buildNinja = render_build_ninja($projectRoot, $repoRoot, $buildDir, $generatedDir, $generatedUnits, $nativeCppUnits, $outputName, $compiler, $buildMode, $runtimeConfig, $projectLibraryFlags, $fastcgiBuild, $effectiveBuildOptions, $runtimePlacementForInvocation);
	$buildNinjaPath = $buildDir . '/build.ninja';
	write_text_file($buildNinjaPath, $buildNinja);
	$markTiming('build_ninja_written');
	$runtimeBuild = build_runtime_artifact_spec($repoRoot, $projectRoot, $compiler, $buildMode, $runtimeConfig, $runtimePlacementForInvocation);
	$GLOBALS['scpp_required_runtime_module_artifacts'] = [];
	if ($runtimePlacementForInvocation === 'reuse' && runtime_is_shared_release_eligible($compiler, $buildMode, $runtimeConfig)) {
		foreach (resolve_shared_runtime_bundle_specs($repoRoot, $projectRoot, $compiler, $buildMode, $runtimeConfig)['modules'] as $moduleSpec) {
			$GLOBALS['scpp_required_runtime_module_artifacts'][] = normalize_path($projectRoot . '/' . normalize_config_path((string) $moduleSpec['artifact_path']));
		}
	}
	$buildOutputs = collect_build_output_paths($generatedUnits, $nativeCppUnits, $runtimeBuild, $buildDir, $compiler['kind'], $outputName, $fastcgiBuild, $projectRoot, $effectiveBuildOptions);
	$buildOutputMtimesBefore = capture_file_mtimes($buildOutputs);
	echo 'Transpiled PHP files: ' . $transpiledCount . ', skipped unchanged: ' . $skippedCount . PHP_EOL;
	echo 'Generated Ninja file: ' . normalize_config_path(relative_path($projectRoot, $buildNinjaPath)) . PHP_EOL;
	echo 'Using compiler: ' . compiler_display_command($compiler) . ' (' . $compiler['kind'] . ')' . PHP_EOL;
	echo 'Using build mode: ' . $buildMode . PHP_EOL;
	echo 'Using repo root: ' . normalize_path($repoRoot) . PHP_EOL;
	echo 'Resolved project dependency graph: ' . count($projectGraph) . ' project(s)' . PHP_EOL;
	echo 'Runtime compilation: ' . ($options['compile_runtime'] ? 'enabled' : 'reuse existing artifact only') . PHP_EOL;
	echo 'Dependency compilation: ' . ($options['compile_dependencies'] ? 'enabled' : 'reuse existing artifacts only') . PHP_EOL;
	if ($projectLibraryFlags !== []) {
		echo 'Resolved project libraries: ' . implode(' ', $projectLibraryFlags) . PHP_EOL;
	}
	if (!$options['compile_runtime']) {
		validate_reused_runtime_artifact(
			$projectRoot,
			$runtimeBuild,
			$buildDir,
			$outputName,
			$compiler,
			$buildMode,
			$options,
			$transpiledCount,
			$skippedCount,
			$sourceRebuildReasons,
			$entrypointAbs,
			$entryGeneratedUnit,
			$startedAt,
			$phpProfile
		);
	}
	if (!$options['compile_dependencies']) {
		validate_reused_dependency_artifacts($projectRoot, $generatedUnits, $nativeCppUnits);
	}
	scrub_invalid_cached_objects_for_rebuild($projectRoot, array_merge($generatedUnits, $nativeCppUnits), $options['compile_dependencies']);

	$command = [
		$ninjaPath,
		'-C',
		normalize_config_path(relative_path($projectRoot, $buildDir)),
		'-f',
		basename($buildNinjaPath),
	];
	if (build_ninja_verbose_requested()) {
		$command[] = '-v';
	}
	$captureSubprocessOutput = scpp_capture_subprocess_output_enabled();
	$descriptor = [
		0 => ['file', 'php://stdin', 'r'],
		1 => ['pipe', 'w'],
		2 => ['pipe', 'w'],
	];
	$process = proc_open($command, $descriptor, $pipes, $projectRoot, scpp_build_process_environment());
	if (!is_resource($process)) {
		scpp_fail("Failed to start Ninja.
", 4);
	}
	$markTiming('ninja_started');
	$ninjaStdout = stream_get_contents($pipes[1]);
	$ninjaStderr = stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);
	$status = proc_close($process);
	$markTiming('ninja_finished');
	$ninjaStdout = is_string($ninjaStdout) ? $ninjaStdout : '';
	$ninjaStderr = is_string($ninjaStderr) ? $ninjaStderr : '';
	if ($captureSubprocessOutput) {
		scpp_append_captured_subprocess_output($ninjaStdout, $ninjaStderr);
	} else {
		if ($ninjaStdout !== '') {
			scpp_write($ninjaStdout, 'stdout');
		}
		if ($ninjaStderr !== '') {
			scpp_write($ninjaStderr, 'stderr');
		}
	}
	if ($status !== 0) {
		$diagnostics = collect_compiler_diagnostics($projectRoot, $buildDir, $ninjaStdout, $ninjaStderr, $generatedArtifactOrigins);
		$finishedAt = microtime(true);
		write_last_run_report(
			$projectRoot,
			'build',
			$GLOBALS['argv'] ?? ['scpp', 'build'],
			$status,
			$startedAt,
			$finishedAt,
			[
				'build_dir' => $buildDir,
				'output_name' => $outputName,
				'compiler' => compiler_display_command($compiler),
				'compiler_kind' => $compiler['kind'],
				'build_mode' => $buildMode,
				'entry_override' => $options['entry_override'],
				'compile_runtime' => $options['compile_runtime'],
				'compile_dependencies' => $options['compile_dependencies'],
				'ninja_command' => $command,
				'diagnostic_count' => count($diagnostics),
				'build_explanation' => build_explanation_details(
					$projectRoot,
					$options,
					$transpiledCount,
					$skippedCount,
					$sourceRebuildReasons,
					[],
					$status,
					$entrypointAbs,
					is_array($entryGeneratedUnit) ? (string) ($entryGeneratedUnit['generated_cpp'] ?? '') : null,
					is_array($entryGeneratedUnit) ? (string) ($entryGeneratedUnit['object_path'] ?? '') : null,
					null,
					$command
				),
			]
		);
		if ($diagnostics !== []) {
			$compileFailure = classify_compile_failure($diagnostics, $ninjaStdout, $ninjaStderr);
			$guidance = diagnostic_guidance($compileFailure['category'], $compileFailure['subcategory'], $diagnostics, $ninjaStdout, $ninjaStderr);
			$shortMessage = render_short_compiler_failure($diagnostics, $projectRoot, $phpProfile);
			write_last_error_report(
				$projectRoot,
				'build',
				$GLOBALS['argv'] ?? ['scpp', 'build'],
				$status,
				$startedAt,
				$finishedAt,
				$compileFailure['category'],
				$compileFailure['subcategory'],
				$shortMessage,
				$diagnostics,
				$ninjaStdout,
				$ninjaStderr,
				$guidance,
				$phpProfile
			);
			scpp_fail($shortMessage, $status);
		}
		$buildFailure = classify_build_failure($ninjaStdout, $ninjaStderr);
		write_last_error_report(
			$projectRoot,
			'build',
			$GLOBALS['argv'] ?? ['scpp', 'build'],
			$status,
			$startedAt,
			$finishedAt,
			$buildFailure['category'],
			$buildFailure['subcategory'],
			$buildFailure['short_message'],
			[],
			$ninjaStdout,
			$ninjaStderr,
			$buildFailure['guidance'],
			$phpProfile
		);
		$message = '';
		$strictHint = strict_project_error_hint($phpProfile);
		if ($strictHint !== null) {
			$message .= $strictHint . PHP_EOL;
		}
		$message .= $buildFailure['short_message'] . PHP_EOL;
		foreach ($buildFailure['guidance'] as $item) {
			$message .= 'Next: ' . $item . PHP_EOL;
		}
		$message .= 'Generated file: ' . normalize_config_path(relative_path($projectRoot, $buildNinjaPath)) . PHP_EOL;
		$message .= "First lines of build.ninja:" . PHP_EOL;
		foreach (preview_file_lines($buildNinjaPath, 40) as $line) {
			$message .= $line . PHP_EOL;
		}
		scpp_fail($message, $status);
	}

	$buildOutputMtimesAfter = capture_file_mtimes($buildOutputs);
	$rebuiltOutputs = detect_rebuilt_outputs($buildOutputMtimesBefore, $buildOutputMtimesAfter);
	if ($rebuiltOutputs !== []) {
		echo 'Rebuilt outputs: ' . implode(', ', array_map(static fn (string $path): string => normalize_config_path(relative_path($projectRoot, $path)), $rebuiltOutputs)) . PHP_EOL;
	} else {
		echo 'Rebuilt outputs: none (up-to-date)' . PHP_EOL;
	}
	if (!build_ninja_verbose_requested()) {
		echo 'Tip: set SCPP_NINJA_VERBOSE=1 to show full Ninja command lines.' . PHP_EOL;
	}
	$outputPath = normalize_path($buildDir . '/' . $outputName);
	$timingDetails = [
		'load_project_config_ms' => $timingMs('options_normalized', 'config_loaded'),
		'stan_preflight_ms' => $timingMs('config_loaded', 'stan_checked'),
		'resolve_project_dependency_graph_ms' => $timingMs('stan_checked', 'project_graph_resolved'),
		'collect_project_php_files_and_s2s_state_ms' => $timingMs('source_scan_start', 'source_scan_complete'),
		'render_and_write_build_ninja_ms' => $timingMs('runtime_prepare_complete', 'build_ninja_written'),
		'ninja_subprocess_ms' => $timingMs('ninja_started', 'ninja_finished'),
	];
	$reportStartedAt = microtime(true);
	write_last_run_report(
		$projectRoot,
		'build',
		$GLOBALS['argv'] ?? ['scpp', 'build'],
		0,
		$startedAt,
		$reportStartedAt,
		[
			'build_dir' => $buildDir,
			'output_name' => $outputName,
			'output_path' => $outputPath,
			'compiler' => compiler_display_command($compiler),
			'compiler_kind' => $compiler['kind'],
			'build_mode' => $buildMode,
			'entry_override' => $options['entry_override'],
			'compile_runtime' => $options['compile_runtime'],
			'compile_dependencies' => $options['compile_dependencies'],
			'transpiled_count' => $transpiledCount,
			'skipped_count' => $skippedCount,
			'rebuilt_outputs' => array_values(array_map(static fn (string $path): string => normalize_config_path(relative_path($projectRoot, $path)), $rebuiltOutputs)),
			'ninja_command' => $command,
			'timing_breakdown_ms' => $timingDetails,
			'build_explanation' => build_explanation_details(
				$projectRoot,
				$options,
				$transpiledCount,
				$skippedCount,
				$sourceRebuildReasons,
				$rebuiltOutputs,
				0,
				$entrypointAbs,
				is_array($entryGeneratedUnit) ? (string) ($entryGeneratedUnit['generated_cpp'] ?? '') : null,
				is_array($entryGeneratedUnit) ? (string) ($entryGeneratedUnit['object_path'] ?? '') : null,
				$outputPath,
				$command
			),
		]
	);
	$timingDetails['write_last_run_report_ms'] = (int) round(max(0, (microtime(true) - $reportStartedAt) * 1000));
	if ($options['show_timings']) {
		echo 'Build timing:' . PHP_EOL;
		foreach ($timingDetails as $label => $milliseconds) {
			echo '  ' . $label . ': ' . $milliseconds . ' ms' . PHP_EOL;
		}
	}
	echo 'Build completed: ' . normalize_config_path(relative_path($projectRoot, $outputPath)) . PHP_EOL;
	$fastcgiOutputPath = null;
	if ($fastcgiBuild !== null) {
		$fastcgiOutputPath = normalize_path($fastcgiBuild['output_path']);
		echo 'FastCGI build completed: ' . normalize_config_path(relative_path($projectRoot, $fastcgiOutputPath)) . PHP_EOL;
	}

	return [
		'project_root' => $projectRoot,
		'build_dir' => $buildDir,
		'output_name' => $outputName,
		'output_path' => $outputPath,
		'fastcgi_output_path' => $fastcgiOutputPath,
		'runtime_library_dir' => is_string($runtimeBuild['artifact_path'] ?? null)
			? normalize_path(dirname($projectRoot . '/' . normalize_config_path($runtimeBuild['artifact_path'])))
			: null,
		'generated_artifact_origins' => $generatedArtifactOrigins,
		'timing_breakdown_ms' => $timingDetails,
		'build_explanation' => build_explanation_details(
			$projectRoot,
			$options,
			$transpiledCount,
			$skippedCount,
			$sourceRebuildReasons,
			$rebuiltOutputs,
			0,
			$entrypointAbs,
			is_array($entryGeneratedUnit) ? (string) ($entryGeneratedUnit['generated_cpp'] ?? '') : null,
			is_array($entryGeneratedUnit) ? (string) ($entryGeneratedUnit['object_path'] ?? '') : null,
			$outputPath,
			$command
		),
	];
}

/**
 * @param list<array{project_root:string,relative_php:string,generated_cpp:string,object_path:string,is_entrypoint:bool,force_include_header:?string}> $generatedUnits
 * @param list<array{project_root:string,source_path:string,object_path:string,force_include_header:?string}> $nativeCppUnits
 */
function validate_reused_dependency_artifacts(string $projectRoot, array $generatedUnits, array $nativeCppUnits): void
{
	$rootProjectRoot = normalize_path($projectRoot);
	$problems = [];

	foreach ($generatedUnits as $unit) {
		$unitProjectRoot = normalize_path($unit['project_root']);
		if ($unitProjectRoot === $rootProjectRoot) {
			continue;
		}
		$objectPath = normalize_path($unit['object_path']);
		$generatedCpp = normalize_path($unit['generated_cpp']);
		$objectMtime = is_file($objectPath) ? filemtime($objectPath) : false;
		$generatedMtime = is_file($generatedCpp) ? filemtime($generatedCpp) : false;
		$dependencyLabel = normalize_config_path(relative_path($rootProjectRoot, $unitProjectRoot));
		$sourceLabel = normalize_config_path(relative_path($unitProjectRoot, $generatedCpp));
		if (!is_int($objectMtime)) {
			$problems[] = 'Missing reusable dependency object for ' . $dependencyLabel . ': ' . normalize_config_path(relative_path($unitProjectRoot, $objectPath)) . ' (source: ' . $sourceLabel . ')';
			continue;
		}
		if (!cached_object_looks_valid($objectPath)) {
			$problems[] = 'Invalid reusable dependency object for ' . $dependencyLabel . ': ' . normalize_config_path(relative_path($unitProjectRoot, $objectPath)) . ' is not a valid native object artifact';
			continue;
		}
		if (is_int($generatedMtime) && $objectMtime < $generatedMtime) {
			$problems[] = 'Stale reusable dependency object for ' . $dependencyLabel . ': ' . normalize_config_path(relative_path($unitProjectRoot, $objectPath)) . ' is older than ' . $sourceLabel;
		}
	}

	foreach ($nativeCppUnits as $nativeUnit) {
		$unitProjectRoot = normalize_path($nativeUnit['project_root']);
		if ($unitProjectRoot === $rootProjectRoot) {
			continue;
		}
		$objectPath = normalize_path($nativeUnit['object_path']);
		$sourcePath = normalize_path($nativeUnit['source_path']);
		$objectMtime = is_file($objectPath) ? filemtime($objectPath) : false;
		$sourceMtime = is_file($sourcePath) ? filemtime($sourcePath) : false;
		$dependencyLabel = normalize_config_path(relative_path($rootProjectRoot, $unitProjectRoot));
		$sourceLabel = normalize_config_path(relative_path($unitProjectRoot, $sourcePath));
		if (!is_int($objectMtime)) {
			$problems[] = 'Missing reusable dependency object for ' . $dependencyLabel . ': ' . normalize_config_path(relative_path($unitProjectRoot, $objectPath)) . ' (source: ' . $sourceLabel . ')';
			continue;
		}
		if (!cached_object_looks_valid($objectPath)) {
			$problems[] = 'Invalid reusable dependency object for ' . $dependencyLabel . ': ' . normalize_config_path(relative_path($unitProjectRoot, $objectPath)) . ' is not a valid native object artifact';
			continue;
		}
		if (is_int($sourceMtime) && $objectMtime < $sourceMtime) {
			$problems[] = 'Stale reusable dependency object for ' . $dependencyLabel . ': ' . normalize_config_path(relative_path($unitProjectRoot, $objectPath)) . ' is older than ' . $sourceLabel;
		}
	}

	if ($problems === []) {
		return;
	}

	$message = 'Dependency compilation is in reuse-only mode, but reusable dependency artifacts are missing or stale.' . PHP_EOL;
	foreach ($problems as $problem) {
		$message .= '- ' . $problem . PHP_EOL;
	}
	$message .= 'Next: Re-run with --build-dependencies to rebuild dependency artifacts.' . PHP_EOL;
	scpp_fail($message, 2);
}

/**
 * @param list<array{project_root:string,relative_php?:string,generated_cpp?:string,source_path?:string,object_path:string,is_entrypoint?:bool,force_include_header:?string}> $units
 */
function scrub_invalid_cached_objects_for_rebuild(string $projectRoot, array $units, bool $compileDependencies): void
{
	$rootProjectRoot = normalize_path($projectRoot);
	$problems = [];

	foreach ($units as $unit) {
		$objectPath = normalize_path($unit['object_path']);
		if (!is_file($objectPath) || cached_object_looks_valid($objectPath)) {
			continue;
		}

		$unitProjectRoot = normalize_path((string) ($unit['project_root'] ?? $rootProjectRoot));
		$isDependencyUnit = $unitProjectRoot !== $rootProjectRoot;
		if ($isDependencyUnit && !$compileDependencies) {
			$dependencyLabel = normalize_config_path(relative_path($rootProjectRoot, $unitProjectRoot));
			$problems[] = 'Invalid reusable dependency object for ' . $dependencyLabel . ': ' . normalize_config_path(relative_path($unitProjectRoot, $objectPath)) . ' is not a valid native object artifact';
			continue;
		}

		delete_file_if_exists($objectPath);
	}

	if ($problems === []) {
		return;
	}

	$message = 'Dependency compilation is in reuse-only mode, but reusable dependency artifacts are invalid.' . PHP_EOL;
	foreach ($problems as $problem) {
		$message .= '- ' . $problem . PHP_EOL;
	}
	$message .= 'Next: Re-run with --build-dependencies to rebuild dependency artifacts.' . PHP_EOL;
	scpp_fail($message, 2);
}

function cached_object_looks_valid(string $path): bool
{
	$size = filesize($path);
	if (!is_int($size) || $size < 4) {
		return false;
	}

	$handle = fopen($path, 'rb');
	if ($handle === false) {
		return false;
	}
	$header = fread($handle, 8);
	fclose($handle);
	if (!is_string($header) || strlen($header) < 4) {
		return false;
	}

	if (strncmp($header, "\x7F" . 'ELF', 4) === 0) {
		return true;
	}

	$machOMagics = [
		"\xFE\xED\xFA\xCE",
		"\xCE\xFA\xED\xFE",
		"\xFE\xED\xFA\xCF",
		"\xCF\xFA\xED\xFE",
		"\xCA\xFE\xBA\xBE",
		"\xBE\xBA\xFE\xCA",
	];
	foreach ($machOMagics as $magic) {
		if (strncmp($header, $magic, 4) === 0) {
			return true;
		}
	}

	$coffMachinePrefixes = [
		"\x4C\x01",
		"\x64\x86",
		"\x00\x02",
		"\x66\xAA",
	];
	foreach ($coffMachinePrefixes as $prefix) {
		if (strncmp($header, $prefix, 2) === 0) {
			return true;
		}
	}

	return false;
}

/**
 * @param array{kind:string,source_path:string,artifact_path:string,object_path:?string,archiver:?string} $runtimeBuild
 * @param array{compile_runtime:bool,compile_dependencies:bool,force_runtime_rebuild:bool,entry_override:?string} $options
 * @param list<array<string,mixed>> $sourceRebuildReasons
 * @param ?array<string,mixed> $entryGeneratedUnit
 */
function validate_reused_runtime_artifact(
	string $projectRoot,
	array $runtimeBuild,
	string $buildDir,
	string $outputName,
	array $compiler,
	string $buildMode,
	array $options,
	int $transpiledCount,
	int $skippedCount,
	array $sourceRebuildReasons,
	string $entrypointAbs,
	?array $entryGeneratedUnit,
	float $startedAt,
	string $phpProfile,
): void {
	$artifactPath = normalize_path($projectRoot . '/' . normalize_config_path($runtimeBuild['artifact_path']));
	$requiredArtifacts = [$artifactPath];
	if (isset($GLOBALS['scpp_required_runtime_module_artifacts']) && is_array($GLOBALS['scpp_required_runtime_module_artifacts'])) {
		foreach ($GLOBALS['scpp_required_runtime_module_artifacts'] as $moduleArtifact) {
			if (is_string($moduleArtifact) && $moduleArtifact !== '') {
				$requiredArtifacts[] = normalize_path($moduleArtifact);
			}
		}
	}
	$missingArtifact = null;
	foreach ($requiredArtifacts as $requiredArtifact) {
		if (!is_file($requiredArtifact)) {
			$missingArtifact = $requiredArtifact;
			break;
		}
	}
	if ($missingArtifact === null) {
		return;
	}

	$finishedAt = microtime(true);
	$guidance = append_standard_report_guidance([
		'This build is reusing runtime artifacts by default.',
		"Run 'scpp runtime-build' to rebuild the reusable runtime artifact.",
		"Retry with 'scpp build --build-runtime' if you want the build command to refresh the runtime now.",
	], false);

	write_last_run_report(
		$projectRoot,
		'build',
		$GLOBALS['argv'] ?? ['scpp', 'build'],
		2,
		$startedAt,
		$finishedAt,
		[
			'build_dir' => $buildDir,
			'output_name' => $outputName,
			'compiler' => compiler_display_command($compiler),
			'compiler_kind' => $compiler['kind'],
			'build_mode' => $buildMode,
			'entry_override' => $options['entry_override'],
			'compile_runtime' => $options['compile_runtime'],
			'compile_dependencies' => $options['compile_dependencies'],
			'diagnostic_count' => 0,
			'preflight_failure' => 'missing_runtime_artifact',
			'runtime_artifact' => $missingArtifact,
			'build_explanation' => build_explanation_details(
				$projectRoot,
				$options,
				$transpiledCount,
				$skippedCount,
				$sourceRebuildReasons,
				[],
				2,
				$entrypointAbs,
				is_array($entryGeneratedUnit) ? (string) ($entryGeneratedUnit['generated_cpp'] ?? '') : null,
				is_array($entryGeneratedUnit) ? (string) ($entryGeneratedUnit['object_path'] ?? '') : null,
				null,
				[]
			),
		]
	);
	write_last_error_report(
		$projectRoot,
		'build',
		$GLOBALS['argv'] ?? ['scpp', 'build'],
		2,
		$startedAt,
		$finishedAt,
		'runtime_cache',
		'missing_runtime_artifact',
		'Required runtime artifact is missing.',
		[],
		'',
		'',
		$guidance,
		$phpProfile
	);

	$message = '';
	$strictHint = strict_project_error_hint($phpProfile);
	if ($strictHint !== null) {
		$message .= $strictHint . PHP_EOL;
	}
	$message .= 'Required runtime artifact is missing.' . PHP_EOL;
	$message .= 'Expected runtime artifact: ' . $missingArtifact . PHP_EOL;
	foreach ($guidance as $line) {
		$message .= 'Next: ' . $line . PHP_EOL;
	}
	scpp_fail($message, 2);
}

function normalize_run_arguments(array $args): array
{
	if ($args === []) {
		return [];
	}
	$separatorIndex = array_search('--', $args, true);
	if ($separatorIndex === false) {
		return $args;
	}
	return array_slice($args, $separatorIndex + 1);
}

/** @param array{compile_runtime?:bool,compile_dependencies?:bool,force_runtime_rebuild?:bool,disable_stan?:bool,show_timings?:bool,entry_override?:?string,debug_session_id?:?string,debug_session_root?:?string,source_overrides?:?array<string,string>,build_mode?:?string,use_pch?:?bool,extra_native_cpp_files?:?array<int,string>,append_runtime_modules?:?array<int,string>} $options @return array{compile_runtime:bool,compile_dependencies:bool,force_runtime_rebuild:bool,disable_stan:bool,show_timings:bool,entry_override:?string,debug_session_id:?string,debug_session_root:?string,source_overrides:array<string,string>,build_mode:?string,use_pch:?bool,extra_native_cpp_files:list<string>,append_runtime_modules:list<string>} */
function normalize_build_execution_options(array $options): array
{
	return [
		'compile_runtime' => (bool) ($options['compile_runtime'] ?? false),
		'compile_dependencies' => (bool) ($options['compile_dependencies'] ?? false),
		'force_runtime_rebuild' => (bool) ($options['force_runtime_rebuild'] ?? false),
		'disable_stan' => (bool) ($options['disable_stan'] ?? false),
		'show_timings' => (bool) ($options['show_timings'] ?? false),
		'entry_override' => isset($options['entry_override']) && is_string($options['entry_override']) && trim($options['entry_override']) !== ''
			? normalize_config_path(trim((string) $options['entry_override']))
			: null,
		'debug_session_id' => isset($options['debug_session_id']) && is_string($options['debug_session_id']) && trim($options['debug_session_id']) !== ''
			? trim((string) $options['debug_session_id'])
			: null,
		'debug_session_root' => isset($options['debug_session_root']) && is_string($options['debug_session_root']) && trim($options['debug_session_root']) !== ''
			? normalize_config_path(trim((string) $options['debug_session_root']))
			: null,
		'source_overrides' => is_array($options['source_overrides'] ?? null)
			? normalize_source_override_map($options['source_overrides'])
			: [],
		'build_mode' => isset($options['build_mode']) && is_string($options['build_mode']) && trim($options['build_mode']) !== ''
			? trim((string) $options['build_mode'])
			: null,
		'use_pch' => array_key_exists('use_pch', $options) ? (bool) $options['use_pch'] : null,
		'extra_native_cpp_files' => is_array($options['extra_native_cpp_files'] ?? null)
			? array_values(array_filter(array_map(
				static fn (mixed $value): string => is_string($value) ? normalize_path($value) : '',
				$options['extra_native_cpp_files']
			), static fn (string $value): bool => $value !== ''))
			: [],
		'append_runtime_modules' => is_array($options['append_runtime_modules'] ?? null)
			? array_values(array_filter(array_map(
				static fn (mixed $value): string => strtolower(trim((string) $value)),
				$options['append_runtime_modules']
			), static fn (string $value): bool => $value !== ''))
			: [],
	];
}

/** @param list<string> $args @return array{compile_runtime:bool,compile_dependencies:bool,force_runtime_rebuild:bool,disable_stan:bool,show_timings:bool,entry_override:?string} */
function parse_build_command_arguments(array $args): array
{
	$options = [
		'compile_runtime' => false,
		'compile_dependencies' => false,
		'force_runtime_rebuild' => false,
		'disable_stan' => false,
		'show_timings' => false,
		'entry_override' => null,
	];
	foreach ($args as $arg) {
		if (str_starts_with($arg, '--entry=')) {
			$options['entry_override'] = normalize_config_path(substr($arg, strlen('--entry=')));
			continue;
		}
		if ($arg === '--build-runtime') {
			$options['compile_runtime'] = true;
			continue;
		}
		if ($arg === '--build-dependencies') {
			$options['compile_dependencies'] = true;
			continue;
		}
		if ($arg === '--force') {
			$options['compile_runtime'] = true;
			$options['force_runtime_rebuild'] = true;
			continue;
		}
		if ($arg === '--no-stan') {
			$options['disable_stan'] = true;
			continue;
		}
		if ($arg === '--timings') {
			$options['show_timings'] = true;
			continue;
		}
		scpp_fail('Unknown option for `scpp build`: ' . $arg . PHP_EOL, 1);
	}
	return $options;
}

/** @param list<string> $args @return array{build_options:array{compile_runtime:bool,compile_dependencies:bool,force_runtime_rebuild:bool,disable_stan:bool,show_timings:bool,entry_override:?string},run_args:list<string>} */
function parse_run_command_arguments(array $args): array
{
	$buildOptions = [
		'compile_runtime' => false,
		'compile_dependencies' => false,
		'force_runtime_rebuild' => false,
		'disable_stan' => false,
		'show_timings' => false,
		'entry_override' => null,
	];
	$runArgs = [];
	$inRunArgs = false;
	foreach ($args as $arg) {
		if ($inRunArgs) {
			$runArgs[] = $arg;
			continue;
		}
		if ($arg === '--') {
			$inRunArgs = true;
			continue;
		}
		if (str_starts_with($arg, '--entry=')) {
			$buildOptions['entry_override'] = normalize_config_path(substr($arg, strlen('--entry=')));
			continue;
		}
		if ($arg === '--build-runtime') {
			$buildOptions['compile_runtime'] = true;
			continue;
		}
		if ($arg === '--build-dependencies') {
			$buildOptions['compile_dependencies'] = true;
			continue;
		}
		if ($arg === '--force') {
			$buildOptions['compile_runtime'] = true;
			$buildOptions['force_runtime_rebuild'] = true;
			continue;
		}
		if ($arg === '--no-stan') {
			$buildOptions['disable_stan'] = true;
			continue;
		}
		if ($arg === '--timings') {
			$buildOptions['show_timings'] = true;
			continue;
		}
		$runArgs[] = $arg;
		$inRunArgs = true;
	}
	return [
		'build_options' => $buildOptions,
		'run_args' => normalize_run_arguments($runArgs),
	];
}

/** @param array<string,mixed> $config */
function resolve_build_entrypoint(string $projectRoot, array $config, ?string $entryOverride = null): string
{
	$entrypoint = $entryOverride !== null
		? normalize_config_path($entryOverride)
		: normalize_config_path((string) ($config['entrypoint'] ?? ''));
	if ($entrypoint === '') {
		$label = $entryOverride !== null ? '--entry' : '`entrypoint` in ' . SCPP_PROJECT_CONFIG;
		scpp_fail('Missing ' . $label . PHP_EOL, 1);
	}

	$entrypointAbs = normalize_path($projectRoot . '/' . $entrypoint);
	if (!is_file($entrypointAbs)) {
		$label = $entryOverride !== null ? 'Requested entrypoint not found: ' : 'Configured entrypoint not found: ';
		scpp_fail($label . $entrypoint . PHP_EOL, 1);
	}

	if (!path_is_inside($projectRoot, $entrypointAbs)) {
		scpp_fail('Entrypoint must stay inside the project root: ' . $entrypoint . PHP_EOL, 1);
	}

	return $entrypointAbs;
}

/** @param list<string> $args @return array{build_mode:string,force:bool} */
function parse_runtime_build_command_arguments(array $args): array
{
	$options = [
		'build_mode' => 'debug',
		'force' => false,
	];
	foreach ($args as $arg) {
		if ($arg === '--debug') {
			$options['build_mode'] = 'debug';
			continue;
		}
		if ($arg === '--release') {
			$options['build_mode'] = 'release';
			continue;
		}
		if ($arg === '--force') {
			$options['force'] = true;
			continue;
		}
		scpp_fail('Unknown option for `scpp runtime-build`: ' . $arg . PHP_EOL, 1);
	}
	return $options;
}


/**
 * @param list<array{project_root:string,relative_php:string,generated_cpp:string,object_path:string,is_entrypoint:bool}> $generatedUnits
 * @param list<array{project_root:string,source_path:string,object_path:string}> $nativeCppUnits
 * @param array{kind:string,source_path:string,artifact_path:string,object_path:?string,archiver:?string,link_flags?:list<string>,rpath_dir?:?string} $runtimeBuild
 * @return list<string>
 */
/**
 * @param array{compile_runtime:bool,compile_dependencies:bool} $options
 */
function collect_build_output_paths(array $generatedUnits, array $nativeCppUnits, array $runtimeBuild, string $buildDir, string $compilerKind, string $outputName, ?array $fastcgiBuild = null, ?string $rootProjectRoot = null, array $options = ['compile_runtime' => true, 'compile_dependencies' => true]): array
{
	$usePch = array_key_exists('use_pch', $options) ? (bool) $options['use_pch'] : supports_compiler_pch(['kind' => $compilerKind]);
	$paths = [];
	foreach ($generatedUnits as $unit) {
		if (!$options['compile_dependencies'] && $rootProjectRoot !== null && normalize_path($unit['project_root']) !== normalize_path($rootProjectRoot)) {
			continue;
		}
		$paths[] = normalize_path($unit['object_path']);
	}
	foreach ($nativeCppUnits as $nativeUnit) {
		if (!$options['compile_dependencies'] && $rootProjectRoot !== null && normalize_path($nativeUnit['project_root']) !== normalize_path($rootProjectRoot)) {
			continue;
		}
		$paths[] = normalize_path($nativeUnit['object_path']);
	}
	$runtimeObjectPath = $runtimeBuild['object_path'] ?? null;
	if ($options['compile_runtime'] && is_string($runtimeObjectPath) && $runtimeObjectPath !== '') {
		$paths[] = normalize_path($runtimeObjectPath);
	}
	if ($options['compile_runtime']) {
		$paths[] = normalize_path($runtimeBuild['artifact_path']);
	}
	if (isset($GLOBALS['scpp_required_runtime_module_artifacts']) && is_array($GLOBALS['scpp_required_runtime_module_artifacts'])) {
		foreach ($GLOBALS['scpp_required_runtime_module_artifacts'] as $moduleArtifact) {
			if (is_string($moduleArtifact) && $moduleArtifact !== '') {
				$paths[] = normalize_path($moduleArtifact);
			}
		}
	}
	if ($usePch) {
		$paths[] = normalize_path(build_app_pch_artifact_path($buildDir, $compilerKind));
		if ($options['compile_runtime']) {
			$paths[] = normalize_path(build_runtime_pch_artifact_path($buildDir, $compilerKind));
		}
	}
	$paths[] = normalize_path($buildDir . '/' . $outputName);
	if (is_array($fastcgiBuild)) {
		if (is_string($fastcgiBuild['entrypoint_object_path'] ?? null) && $fastcgiBuild['entrypoint_object_path'] !== '') {
			$paths[] = normalize_path($fastcgiBuild['entrypoint_object_path']);
		}
		$paths[] = normalize_path($fastcgiBuild['main_object_path']);
		$paths[] = normalize_path($fastcgiBuild['output_path']);
	}
	return array_values(array_unique($paths));
}

/** @param list<string> $paths @return array<string,int|null> */
function capture_file_mtimes(array $paths): array
{
	$result = [];
	foreach ($paths as $path) {
		$mtime = is_file($path) ? filemtime($path) : false;
		$result[$path] = is_int($mtime) ? $mtime : null;
	}
	return $result;
}

/** @param array<string,int|null> $before @param array<string,int|null> $after @return list<string> */
function detect_rebuilt_outputs(array $before, array $after): array
{
	$rebuilt = [];
	foreach ($after as $path => $afterMtime) {
		$beforeMtime = $before[$path] ?? null;
		if ($afterMtime === null) {
			continue;
		}
		if ($beforeMtime === null || $afterMtime > $beforeMtime) {
			$rebuilt[] = $path;
		}
	}
	sort($rebuilt, SORT_STRING);
	return $rebuilt;
}

/** @param array<string,mixed> $manifest */
function write_export_manifest_file(string $path, array $manifest): void
{
	$namespaces = is_array($manifest['namespaces'] ?? null) ? $manifest['namespaces'] : [];
	if ($namespaces === []) {
		delete_file_if_exists($path);
		return;
	}
	$json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	if ($json === false) {
		scpp_fail('Failed to encode export manifest: ' . $path . PHP_EOL, 2);
	}
	write_text_file($path, $json . PHP_EOL);
}

/** @param list<string> $exportManifestPaths */
function render_project_export_header(string $generatedDir, array $exportManifestPaths): string
{
	$forwardHeader = normalize_path($generatedDir . '/__project_fwd.hpp');
	$lines = ['#pragma once', '', '#include <scpp/lang/php.hpp>'];
	if (is_file($forwardHeader)) {
		$lines[] = '#include "__project_fwd.hpp"';
	}
	$seenHeaders = [];
	$headerPaths = [];
	foreach (array_values(array_unique($exportManifestPaths)) as $manifestPath) {
		$headerPath = export_manifest_header_path($manifestPath);
		if ($headerPath === null) {
			continue;
		}
		$headerPaths[] = normalize_path($headerPath);
	}
	foreach (sort_project_unit_include_headers(array_values(array_unique($headerPaths))) as $headerPath) {
		$includeTarget = normalize_config_path(relative_path($generatedDir, $headerPath));
		if ($includeTarget === '' || isset($seenHeaders[$includeTarget])) {
			continue;
		}
		$seenHeaders[$includeTarget] = true;
		$lines[] = '#include "' . $includeTarget . '"';
	}
	$lines[] = '';
	return implode(PHP_EOL, $lines) . PHP_EOL;
}

function export_manifest_header_path(string $manifestPath): ?string
{
	if (!str_ends_with($manifestPath, '.exports.json')) {
		return null;
	}
	return substr($manifestPath, 0, -strlen('.exports.json')) . '.hpp';
}

/** @return array<string,mixed> */
function load_export_manifest(string $path): array
{
	$json = file_get_contents($path);
	if ($json === false) {
		scpp_fail('Failed to read export manifest: ' . $path . PHP_EOL, 2);
	}
	try {
		$data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
	} catch (JsonException $e) {
		scpp_fail('Invalid JSON in export manifest ' . $path . ': ' . $e->getMessage() . PHP_EOL, 2);
	}
	if (!is_array($data)) {
		scpp_fail('Invalid export manifest shape in ' . $path . PHP_EOL, 2);
	}
	return $data;
}

/**
 * @param array<string, array{
 *   project_root:string,
 *   config_path:string,
 *   config:array<string,mixed>,
 *   build_dir:string,
 *   generated_dir:string,
 *   cache_dir:string,
 *   native_cpp_dir:string,
 *   dependency_roots:list<string>,
 *   state_path?:string,
 *   state?:array<string,mixed>,
 *   php_files?:list<string>,
 *   native_cpp_files?:list<string>,
 *   generated_headers?:list<string>,
 *   export_manifests?:list<string>
 * }> $projectContexts
 * @return array<string,string>
 */
function write_project_unit_force_include_headers(array $projectContexts): array
{
	$headers = [];
	foreach ($projectContexts as $projectRoot => $projectContext) {
		$dependencyHeaders = [];
		$localHeaders = [];
		foreach (($projectContext['generated_headers'] ?? []) as $generatedHeader) {
			if (!is_string($generatedHeader) || $generatedHeader === '') {
				continue;
			}
			$localHeaders[] = normalize_path($generatedHeader);
		}
		foreach (collect_transitive_project_dependency_roots($projectRoot, $projectContexts) as $dependencyRoot) {
			$dependencyContext = $projectContexts[$dependencyRoot] ?? null;
			if (!is_array($dependencyContext)) {
				continue;
			}
			$dependencyHeaders[] = normalize_path($dependencyContext['generated_dir'] . '/__project.hpp');
		}
		$includeHeaders = array_merge(
			array_values(array_unique($dependencyHeaders)),
			sort_project_unit_include_headers(array_values(array_unique($localHeaders)))
		);
		if ($includeHeaders === []) {
			continue;
		}
		$headerPath = normalize_path($projectContext['generated_dir'] . '/__project_units.hpp');
		$lines = ['#pragma once', ''];
		$forwardHeader = normalize_path($projectContext['generated_dir'] . '/__project_fwd.hpp');
		if (is_file($forwardHeader)) {
			$lines[] = '#include "__project_fwd.hpp"';
		}
		foreach ($includeHeaders as $includeHeader) {
			$lines[] = '#include "' . normalize_config_path(relative_path(dirname($headerPath), $includeHeader)) . '"';
		}
		$lines[] = '';
		write_text_file($headerPath, implode(PHP_EOL, $lines) . PHP_EOL);
		$headers[normalize_path($projectRoot)] = $headerPath;
	}
	return $headers;
}

/** @param list<string> $headerPaths */
function write_project_forward_declaration_header(string $generatedDir, array $headerPaths): void
{
	$declarations = collect_project_header_declarations($headerPaths);
	$headerPath = normalize_path($generatedDir . '/__project_fwd.hpp');
	$lines = ['#pragma once', ''];
	foreach ($declarations as $namespace => $classNames) {
		if ($classNames === []) {
			continue;
		}
		$lines[] = 'namespace ' . $namespace . ' {';
		foreach ($classNames as $className) {
			$lines[] = 'class ' . $className . ';';
		}
		$lines[] = '}';
		$lines[] = '';
	}
	write_text_file($headerPath, implode(PHP_EOL, $lines) . PHP_EOL);
}

/**
 * @param list<string> $headerPaths
 * @return array<string,list<string>>
 */
function collect_project_header_declarations(array $headerPaths): array
{
	$declarations = [];
	foreach ($headerPaths as $headerPath) {
		foreach (read_project_header_class_metadata($headerPath)['classes'] as $class) {
			$namespace = $class['namespace'];
			$name = $class['name'];
			if (!isset($declarations[$namespace])) {
				$declarations[$namespace] = [];
			}
			$declarations[$namespace][$name] = $name;
		}
	}
	ksort($declarations, SORT_STRING);
	foreach ($declarations as &$classNames) {
		ksort($classNames, SORT_STRING);
		$classNames = array_values($classNames);
	}
	unset($classNames);
	return $declarations;
}

/**
 * @param list<string> $includeHeaders
 * @return list<string>
 */
function sort_project_unit_include_headers(array $includeHeaders): array
{
	$knownClasses = [];
	$headerClasses = [];
	foreach ($includeHeaders as $headerPath) {
		if (!is_file($headerPath)) {
			continue;
		}
		$metadata = read_project_header_class_metadata($headerPath);
		foreach ($metadata['classes'] as $class) {
			$key = $class['namespace'] . '::' . $class['name'];
			$knownClasses[$key] = $headerPath;
			$headerClasses[$headerPath][] = $class;
		}
	}

	$dependencies = [];
	foreach ($includeHeaders as $headerPath) {
		$dependencies[$headerPath] = [];
		foreach (($headerClasses[$headerPath] ?? []) as $class) {
			$parent = $class['parent'];
			if ($parent === null || $parent === '') {
				continue;
			}
			$parentKey = resolve_project_header_class_reference($parent, $class['namespace']);
			$parentHeader = $knownClasses[$parentKey] ?? null;
			if ($parentHeader === null || $parentHeader === $headerPath) {
				continue;
			}
			$dependencies[$headerPath][$parentHeader] = $parentHeader;
		}
	}

	$ordered = [];
	$temporary = [];
	$permanent = [];
	foreach ($includeHeaders as $headerPath) {
		visit_project_unit_include_header($headerPath, $dependencies, $temporary, $permanent, $ordered);
	}
	return $ordered;
}

/**
 * @param array<string,array<string,string>> $dependencies
 * @param array<string,bool> $temporary
 * @param array<string,bool> $permanent
 * @param list<string> $ordered
 */
function visit_project_unit_include_header(string $headerPath, array $dependencies, array &$temporary, array &$permanent, array &$ordered): void
{
	if (isset($permanent[$headerPath])) {
		return;
	}
	if (isset($temporary[$headerPath])) {
		return;
	}
	$temporary[$headerPath] = true;
	foreach (($dependencies[$headerPath] ?? []) as $dependencyHeader) {
		visit_project_unit_include_header($dependencyHeader, $dependencies, $temporary, $permanent, $ordered);
	}
	unset($temporary[$headerPath]);
	$permanent[$headerPath] = true;
	$ordered[] = $headerPath;
}

/** @return array{classes:list<array{namespace:string,name:string,parent:?string}>} */
function read_project_header_class_metadata(string $headerPath): array
{
	$contents = @file_get_contents($headerPath);
	if (!is_string($contents)) {
		return ['classes' => []];
	}
	if (preg_match('/^namespace\s+([A-Za-z_][A-Za-z0-9_:]*)\s*\{/m', $contents, $namespaceMatch) !== 1) {
		return ['classes' => []];
	}
	$namespace = $namespaceMatch[1];
	$classes = [];
	if (preg_match_all('/^class\s+([A-Za-z_][A-Za-z0-9_]*)(?:\s*:\s*public\s+([A-Za-z_][A-Za-z0-9_:]*))?\s*([;{])/m', $contents, $matches, PREG_SET_ORDER) !== false) {
		foreach ($matches as $match) {
			if (($match[3] ?? '') !== '{') {
				continue;
			}
			$classes[] = [
				'namespace' => $namespace,
				'name' => $match[1],
				'parent' => isset($match[2]) && $match[2] !== '' ? $match[2] : null,
			];
		}
	}
	return ['classes' => $classes];
}

function resolve_project_header_class_reference(string $classReference, string $currentNamespace): string
{
	$reference = ltrim($classReference, ':');
	if (str_starts_with($reference, 'scpp::')) {
		return $reference;
	}
	if (str_contains($reference, '::')) {
		return 'scpp::' . $reference;
	}
	return $currentNamespace . '::' . $reference;
}

/**
 * @param array<string, array{
 *   project_root:string,
 *   config_path:string,
 *   config:array<string,mixed>,
 *   build_dir:string,
 *   generated_dir:string,
 *   cache_dir:string,
 *   native_cpp_dir:string,
 *   dependency_roots:list<string>,
 *   state_path?:string,
 *   state?:array<string,mixed>,
 *   php_files?:list<string>,
 *   native_cpp_files?:list<string>,
 *   export_headers?:list<string>
 * }> $projectContexts
 * @return list<string>
 */
function collect_transitive_project_dependency_roots(string $projectRoot, array $projectContexts): array
{
	$ordered = [];
	$visited = [];
	$walk = static function (string $currentRoot) use (&$walk, &$ordered, &$visited, $projectContexts): void {
		$currentContext = $projectContexts[$currentRoot] ?? null;
		if (!is_array($currentContext)) {
			return;
		}
		foreach (is_array($currentContext['dependency_roots'] ?? null) ? $currentContext['dependency_roots'] : [] as $dependencyRoot) {
			$normalizedDependencyRoot = normalize_path($dependencyRoot);
			if (isset($visited[$normalizedDependencyRoot])) {
				continue;
			}
			$visited[$normalizedDependencyRoot] = true;
			$walk($normalizedDependencyRoot);
			$ordered[] = $normalizedDependencyRoot;
		}
	};
	$walk(normalize_path($projectRoot));
	return $ordered;
}

/**
 * @param list<array{
 *   project_root:string,
 *   config_path:string,
 *   config:array<string,mixed>,
 *   dependency_roots:list<string>
 * }> $projectGraph
 * @param array{command:string,kind:string,launcher:?string,linker_flags:list<string>,archiver:?string} $compiler
 * @return list<string>
 */
function resolve_project_library_link_flags(string $rootProjectRoot, array $projectGraph, array $compiler): array
{
	$flags = [];
	foreach ($projectGraph as $projectSpec) {
		$contextProjectRoot = normalize_path($projectSpec['project_root']);
		$projectConfig = is_array($projectSpec['config'] ?? null) ? $projectSpec['config'] : [];
		$libraries = is_array($projectConfig['libraries'] ?? null) ? $projectConfig['libraries'] : [];
		foreach ($libraries as $library) {
			if (!is_string($library) || trim($library) === '') {
				continue;
			}
			foreach (resolve_library_reference_flags($rootProjectRoot, $contextProjectRoot, trim($library), $compiler['kind']) as $flag) {
				if (!in_array($flag, $flags, true)) {
					$flags[] = $flag;
				}
			}
		}
	}
	return $flags;
}

/** @return list<string> */
function resolve_library_reference_flags(string $rootProjectRoot, string $contextProjectRoot, string $library, string $compilerKind): array
{
	if ($library === '') {
		return [];
	}
	if (str_starts_with($library, '-')) {
		return [$library];
	}
	if (looks_like_library_path($library)) {
		$resolvedPath = normalize_path($contextProjectRoot . '/' . $library);
		if (is_absolute_path($library)) {
			$resolvedPath = normalize_path($library);
		}
		if (!is_file($resolvedPath)) {
			scpp_fail('Configured library path not found: ' . $library . ' (resolved to ' . $resolvedPath . ')' . PHP_EOL, 2);
		}
		return [normalize_config_path(relative_path($rootProjectRoot, $resolvedPath))];
	}
	if ($compilerKind === 'msvc') {
		return [preg_match('/\.lib$/i', $library) === 1 ? $library : $library . '.lib'];
	}
	return [str_starts_with($library, '-l') ? $library : '-l' . $library];
}

function looks_like_library_path(string $library): bool
{
	if ($library === '') {
		return false;
	}
	if (is_absolute_path($library)) {
		return true;
	}
	if (str_contains($library, '/') || str_contains($library, '\\')) {
		return true;
	}
	$lower = strtolower($library);
	return str_ends_with($lower, '.a')
		|| str_ends_with($lower, '.so')
		|| str_ends_with($lower, '.dylib')
		|| str_ends_with($lower, '.lib');
}

/**
 * @param list<array{
 *   project_root:string,
 *   config_path:string,
 *   config:array<string,mixed>,
 *   dependency_roots:list<string>
 * }> $projectGraph
 * @return array<string, array{
 *   project_root:string,
 *   config_path:string,
 *   config:array<string,mixed>,
 *   build_dir:string,
 *   generated_dir:string,
 *   cache_dir:string,
 *   native_cpp_dir:string,
 *   dependency_roots:list<string>,
 *   state_path?:string,
 *   state?:array<string,mixed>,
 *   php_files?:list<string>,
 *   native_cpp_files?:list<string>
 * }>
 */
function build_project_contexts(array $projectGraph): array
{
	$contexts = [];
	foreach ($projectGraph as $projectSpec) {
		$contextProjectRoot = normalize_path($projectSpec['project_root']);
		$projectConfig = $projectSpec['config'];
		$contexts[$contextProjectRoot] = [
			'project_root' => $contextProjectRoot,
			'config_path' => normalize_path($projectSpec['config_path']),
			'config' => $projectConfig,
			'build_dir' => normalize_path($contextProjectRoot . '/' . normalize_config_path((string) ($projectConfig['build_dir'] ?? '.prism/build'))),
			'generated_dir' => normalize_path($contextProjectRoot . '/' . normalize_config_path((string) ($projectConfig['generated_dir'] ?? '.prism/generated'))),
			'cache_dir' => normalize_path($contextProjectRoot . '/' . normalize_config_path((string) ($projectConfig['cache_dir'] ?? '.prism/cache'))),
			'native_cpp_dir' => normalize_path($contextProjectRoot . '/' . normalize_config_path((string) ($projectConfig['native_cpp_dir'] ?? 'native_cpp'))),
			'dependency_roots' => is_array($projectSpec['dependency_roots'] ?? null) ? $projectSpec['dependency_roots'] : [],
		];
	}
	return $contexts;
}

function build_project_scoped_relative_path(string $rootProjectRoot, string $contextProjectRoot, string $relativePath): string
{
	$normalizedRelativePath = normalize_config_path($relativePath);
	if ($contextProjectRoot === $rootProjectRoot) {
		return $normalizedRelativePath;
	}
	return '__deps/' . md5(normalize_path($contextProjectRoot)) . '/' . $normalizedRelativePath;
}

function build_ninja_verbose_requested(): bool
{
	$value = getenv('SCPP_NINJA_VERBOSE');
	if (!is_string($value)) {
		return false;
	}
	$value = trim($value);
	if ($value === '') {
		return false;
	}
	return !in_array(strtolower($value), ['0', 'false', 'no', 'off'], true);
}

/** @return array{project_root:string,config_path:string}|null */
function find_project_config(string $startDir): ?array
{
	$current = normalize_path($startDir);
	while (true) {
		$configPath = $current . '/' . SCPP_PROJECT_CONFIG;
		if (is_file($configPath)) {
			return [
				'project_root' => $current,
				'config_path' => $configPath,
			];
		}

		$parent = dirname($current);
		if ($parent === $current) {
			return null;
		}
		$current = $parent;
	}
}

/** @return array<string,mixed> */
function load_project_config(string $configPath): array
{
	$json = file_get_contents($configPath);
	if ($json === false) {
		scpp_fail('Failed to read project config: ' . $configPath . PHP_EOL, 2);
	}

	try {
		$config = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
	} catch (JsonException $e) {
		scpp_fail('Invalid JSON in ' . SCPP_PROJECT_CONFIG . ': ' . $e->getMessage() . PHP_EOL, 2);
	}

	if (!is_array($config)) {
		scpp_fail('Invalid project config shape in ' . SCPP_PROJECT_CONFIG . PHP_EOL, 2);
	}

	warn_on_absolute_project_paths($config, $configPath);
	$config['dependencies'] = resolve_project_dependency_config($config);
	$config['libraries'] = resolve_project_library_config($config);
	$config['runtime'] = resolve_runtime_build_config($config);
	return $config;
}

function warn_on_absolute_project_paths(array $config, string $configPath): void
{
	$configLabel = relative_or_absolute(dirname($configPath), $configPath);
	$pathFields = [
		'entrypoint',
		'build_dir',
		'generated_dir',
		'cache_dir',
		'native_cpp_dir',
	];
	foreach ($pathFields as $field) {
		$value = $config[$field] ?? null;
		if (!is_string($value)) {
			continue;
		}
		$trimmed = trim($value);
		if ($trimmed === '' || !is_absolute_path($trimmed)) {
			continue;
		}
		scpp_write(
			'Warning: `' . $field . '` in ' . SCPP_PROJECT_CONFIG . ' is absolute (`' . $trimmed . '`). Prefer project-relative paths in ' . $configLabel . '.' . PHP_EOL,
			'stderr'
		);
	}

	$dependenciesRaw = $config['dependencies'] ?? [];
	if (is_array($dependenciesRaw)) {
		foreach ($dependenciesRaw as $index => $dependency) {
			if (!is_string($dependency)) {
				continue;
			}
			$trimmed = trim($dependency);
			if ($trimmed === '' || !is_absolute_path($trimmed)) {
				continue;
			}
			scpp_write(
				'Warning: dependency at index ' . $index . ' in ' . SCPP_PROJECT_CONFIG . ' is absolute (`' . $trimmed . '`). Prefer project-relative dependency paths in ' . $configLabel . '.' . PHP_EOL,
				'stderr'
			);
		}
	}

	$librariesRaw = $config['libraries'] ?? [];
	if (is_array($librariesRaw)) {
		foreach ($librariesRaw as $index => $library) {
			if (!is_string($library)) {
				continue;
			}
			$trimmed = trim($library);
			if ($trimmed === '' || !is_absolute_path($trimmed)) {
				continue;
			}
			scpp_write(
				'Warning: library at index ' . $index . ' in ' . SCPP_PROJECT_CONFIG . ' is absolute (`' . $trimmed . '`). Prefer relative project paths or linker-visible library names in ' . $configLabel . '.' . PHP_EOL,
				'stderr'
			);
		}
	}
}

/**
 * @return list<array{
 *   project_root:string,
 *   config_path:string,
 *   config:array<string,mixed>,
 *   dependency_roots:list<string>
 * }>
 */
function resolve_project_dependency_graph(string $projectRoot, string $configPath, ?array $config = null): array
{
	$resolved = [];
	$stack = [];
	resolve_project_dependency_node(
		normalize_path($projectRoot),
		normalize_path($configPath),
		$config,
		$resolved,
		$stack
	);
	return array_values($resolved);
}

/**
 * @param array<string, array{
 *   project_root:string,
 *   config_path:string,
 *   config:array<string,mixed>,
 *   dependency_roots:list<string>
 * }> $resolved
 * @param list<string> $stack
 */
function resolve_project_dependency_node(
	string $projectRoot,
	string $configPath,
	?array $config,
	array &$resolved,
	array &$stack
): void {
	if (isset($resolved[$projectRoot])) {
		return;
	}

	$cycleIndex = array_search($projectRoot, $stack, true);
	if ($cycleIndex !== false) {
		$cycle = array_slice($stack, $cycleIndex);
		$cycle[] = $projectRoot;
		scpp_fail(
			'Dependency cycle detected in ' . SCPP_PROJECT_CONFIG . ': '
			. implode(' -> ', array_map(static fn (string $path): string => normalize_path($path), $cycle))
			. PHP_EOL,
			2
		);
	}

	$normalizedConfigPath = normalize_path($configPath);
	$loadedConfig = $config ?? load_project_config($normalizedConfigPath);
	$stack[] = $projectRoot;
	$dependencyRoots = [];
	foreach ($loadedConfig['dependencies'] as $dependencyPath) {
		$dependencySpec = resolve_declared_project_dependency($projectRoot, $dependencyPath);
		$dependencyRoot = $dependencySpec['project_root'];
		if ($dependencyRoot === $projectRoot) {
			scpp_fail(
				'Project dependency resolves back to the same project root: '
				. normalize_path($projectRoot)
				. ' via `'
				. $dependencyPath
				. '`'
				. PHP_EOL,
				2
			);
		}
		if (!in_array($dependencyRoot, $dependencyRoots, true)) {
			$dependencyRoots[] = $dependencyRoot;
		}
		resolve_project_dependency_node(
			$dependencyRoot,
			$dependencySpec['config_path'],
			null,
			$resolved,
			$stack
		);
	}
	array_pop($stack);

	$resolved[$projectRoot] = [
		'project_root' => $projectRoot,
		'config_path' => $normalizedConfigPath,
		'config' => $loadedConfig,
		'dependency_roots' => $dependencyRoots,
	];
}

/** @return array{project_root:string,config_path:string} */
function resolve_declared_project_dependency(string $projectRoot, string $dependencyPath): array
{
	$normalizedDependencyPath = normalize_config_path($dependencyPath);
	$candidatePath = normalize_path($projectRoot . '/' . $normalizedDependencyPath);

	if (is_dir($candidatePath)) {
		$resolvedProjectRoot = realpath($candidatePath);
		if (!is_string($resolvedProjectRoot) || $resolvedProjectRoot === '') {
			scpp_fail('Failed to resolve dependency project path: ' . $candidatePath . PHP_EOL, 2);
		}
		$resolvedProjectRoot = normalize_path($resolvedProjectRoot);
		$configPath = normalize_path($resolvedProjectRoot . '/' . SCPP_PROJECT_CONFIG);
		if (!is_file($configPath)) {
			scpp_fail(
				'Dependency project is missing ' . SCPP_PROJECT_CONFIG . ': '
				. $resolvedProjectRoot
				. PHP_EOL,
				2
			);
		}
		return [
			'project_root' => $resolvedProjectRoot,
			'config_path' => $configPath,
		];
	}

	if (is_file($candidatePath) && basename($candidatePath) === SCPP_PROJECT_CONFIG) {
		$resolvedConfigPath = realpath($candidatePath);
		if (!is_string($resolvedConfigPath) || $resolvedConfigPath === '') {
			scpp_fail('Failed to resolve dependency config path: ' . $candidatePath . PHP_EOL, 2);
		}
		$resolvedConfigPath = normalize_path($resolvedConfigPath);
		return [
			'project_root' => normalize_path(dirname($resolvedConfigPath)),
			'config_path' => $resolvedConfigPath,
		];
	}

	scpp_fail(
		'Declared dependency path not found or not a Prism project: `'
		. $dependencyPath
		. '` from '
		. normalize_path($projectRoot)
		. PHP_EOL,
		2
	);
}

/** @return list<string> */
function resolve_project_dependency_config(array $config): array
{
	$dependenciesRaw = $config['dependencies'] ?? [];
	if (!is_array($dependenciesRaw)) {
		scpp_fail('Invalid dependency config in ' . SCPP_PROJECT_CONFIG . '; expected dependencies as an array of project paths.' . PHP_EOL, 2);
	}

	$dependencies = [];
	foreach ($dependenciesRaw as $index => $dependency) {
		if (!is_string($dependency)) {
			scpp_fail('Invalid dependency entry at index ' . $index . ' in ' . SCPP_PROJECT_CONFIG . '; expected a string project path.' . PHP_EOL, 2);
		}
		$normalized = normalize_config_path(trim($dependency));
		if ($normalized === '') {
			scpp_fail('Invalid dependency entry at index ' . $index . ' in ' . SCPP_PROJECT_CONFIG . '; dependency paths must not be empty.' . PHP_EOL, 2);
		}
		$dependencies[] = $normalized;
	}

	return array_values(array_unique($dependencies));
}

/** @return list<string> */
function resolve_project_library_config(array $config): array
{
	$librariesRaw = $config['libraries'] ?? [];
	if (!is_array($librariesRaw)) {
		scpp_fail('Invalid library config in ' . SCPP_PROJECT_CONFIG . '; expected libraries as an array of linker-visible names or paths.' . PHP_EOL, 2);
	}

	$libraries = [];
	foreach ($librariesRaw as $index => $library) {
		if (!is_string($library)) {
			scpp_fail('Invalid library entry at index ' . $index . ' in ' . SCPP_PROJECT_CONFIG . '; expected a string library name or path.' . PHP_EOL, 2);
		}
		$normalized = trim($library);
		if ($normalized === '') {
			scpp_fail('Invalid library entry at index ' . $index . ' in ' . SCPP_PROJECT_CONFIG . '; library names must not be empty.' . PHP_EOL, 2);
		}
		$libraries[] = $normalized;
	}

	return array_values(array_unique($libraries));
}

/**
 * @return array{
 *   languages:list<string>,
 *   language_profiles:array<string, array{profile:string}>,
 *   modules:list<string>
 * }
 */
function resolve_runtime_build_config(array $config): array
{
	$runtime = is_array($config['runtime'] ?? null) ? $config['runtime'] : [];
	$languagesRaw = $runtime['languages'] ?? ['php'];
	$profilesRaw = is_array($runtime['language_profiles'] ?? null) ? $runtime['language_profiles'] : [];
	$modules = $runtime['modules'] ?? ['json', 'filesystem', 'datetime'];
	if (!is_array($languagesRaw) || !is_array($modules)) {
		scpp_fail('Invalid runtime config in ' . SCPP_PROJECT_CONFIG . '; expected runtime.languages as either a list or object, and runtime.modules as an array.' . PHP_EOL, 2);
	}

	$languageProfiles = [];
	if (array_is_list($languagesRaw)) {
		$languages = array_values(array_unique(array_map(static fn ($value): string => strtolower(trim((string) $value)), $languagesRaw)));
		foreach ($languages as $language) {
			if ($language !== '') {
				$languageProfile = is_array($profilesRaw[$language] ?? null) ? $profilesRaw[$language] : [];
				$profile = strtolower(trim((string) ($languageProfile['profile'] ?? 'legacy')));
				if (!in_array($profile, ['legacy', 'strict'], true)) {
					scpp_fail('Unsupported runtime language profile `' . $profile . '` for `' . $language . '` in ' . SCPP_PROJECT_CONFIG . PHP_EOL, 2);
				}
				$languageProfiles[$language] = ['profile' => $profile];
			}
		}
	} else {
		$languages = [];
		foreach ($languagesRaw as $language => $languageConfig) {
			$normalizedLanguage = strtolower(trim((string) $language));
			if ($normalizedLanguage === '') {
				continue;
			}
			if (!is_array($languageConfig)) {
				scpp_fail('Invalid runtime language config for `' . $normalizedLanguage . '` in ' . SCPP_PROJECT_CONFIG . '; expected an object with at least a profile field.' . PHP_EOL, 2);
			}
			$profile = strtolower(trim((string) ($languageConfig['profile'] ?? 'legacy')));
			if (!in_array($profile, ['legacy', 'strict'], true)) {
				scpp_fail('Unsupported runtime language profile `' . $profile . '` for `' . $normalizedLanguage . '` in ' . SCPP_PROJECT_CONFIG . PHP_EOL, 2);
			}
			$languages[] = $normalizedLanguage;
			$languageProfiles[$normalizedLanguage] = ['profile' => $profile];
		}
		$languages = array_values(array_unique($languages));
	}

	$modules = array_values(array_unique(array_map(static fn ($value): string => strtolower(trim((string) $value)), $modules)));
	$languages = array_values(array_filter($languages, static fn (string $value): bool => $value !== ''));
	$modules = array_values(array_filter($modules, static fn (string $value): bool => $value !== ''));
	$allowedLanguages = ['php'];
	$allowedModules = ['json', 'filesystem', 'datetime', 'mysqli', 'regex', 'curl', 'tasks'];
	foreach ($languages as $language) {
		if (!in_array($language, $allowedLanguages, true)) {
			scpp_fail('Unsupported runtime language `' . $language . '` in ' . SCPP_PROJECT_CONFIG . PHP_EOL, 2);
		}
	}
	foreach ($modules as $module) {
		if (!in_array($module, $allowedModules, true)) {
			scpp_fail('Unsupported runtime module `' . $module . '` in ' . SCPP_PROJECT_CONFIG . PHP_EOL, 2);
		}
	}
	if (!in_array('php', $languages, true)) {
		scpp_fail('Current scpp build requires runtime.languages to include `php` because PHP is the active source language.' . PHP_EOL, 2);
	}
	return [
		'languages' => $languages,
		'language_profiles' => $languageProfiles,
		'modules' => $modules,
	];
}

/** @param array<string,mixed> $config @param array<string,mixed> $options @return array<string,mixed> */
function apply_build_runtime_module_overrides(array $config, array $options): array
{
	$appendModules = is_array($options['append_runtime_modules'] ?? null) ? $options['append_runtime_modules'] : [];
	if ($appendModules === []) {
		return $config;
	}

	$runtime = is_array($config['runtime'] ?? null) ? $config['runtime'] : [];
	$modules = is_array($runtime['modules'] ?? null) ? $runtime['modules'] : default_runtime_modules();
	foreach ($appendModules as $module) {
		$moduleName = strtolower(trim((string) $module));
		if ($moduleName === '') {
			continue;
		}
		if (!in_array($moduleName, $modules, true)) {
			$modules[] = $moduleName;
		}
	}
	$runtime['modules'] = $modules;
	$config['runtime'] = $runtime;
	return $config;
}

function guess_entrypoint(string $projectRoot): ?string
{
	$candidates = [
		'main.phs',
		'src/main.phs',
		'app/main.phs',
		'index.phs',
		'src/index.phs',
		'main.php',
		'src/main.php',
		'app/main.php',
		'index.php',
		'src/index.php',
		'main.jss',
		'src/main.jss',
		'app/main.jss',
		'index.jss',
		'src/index.jss',
	];
	foreach ($candidates as $candidate) {
		if (is_file($projectRoot . '/' . $candidate)) {
			return $candidate;
		}
	}
	return null;
}

/** @return list<string> */
function scpp_source_extensions(): array
{
	return SCPP_COMPAT_SOURCE_EXTENSIONS;
}

/** @return list<string> */
function scpp_stan_source_extensions(): array
{
	return ['phs', 'php', 'jss'];
}

function is_supported_source_extension(string $extension): bool
{
	return in_array(strtolower($extension), scpp_source_extensions(), true);
}

function is_stan_source_extension(string $extension): bool
{
	return in_array(strtolower($extension), scpp_stan_source_extensions(), true);
}

function strip_supported_source_extension(string $path): string
{
	$normalized = normalize_config_path($path);
	foreach (scpp_source_extensions() as $extension) {
		$suffix = '.' . $extension;
		if (str_ends_with(strtolower($normalized), $suffix)) {
			return substr($normalized, 0, -strlen($suffix));
		}
	}
	return $normalized;
}

function ensure_directory(string $dir): void
{
	if (is_dir($dir)) {
		return;
	}
	if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
		scpp_fail('Failed to create directory: ' . $dir . PHP_EOL, 2);
	}
}

function remove_directory_tree(string $path): void
{
	$path = normalize_path($path);
	if (!is_dir($path) || is_link($path)) {
		scpp_fail('Cannot remove directory: ' . $path . PHP_EOL, 2);
	}
	$items = scandir($path);
	if ($items === false) {
		scpp_fail('Failed to read directory while cleaning: ' . $path . PHP_EOL, 2);
	}
	foreach ($items as $item) {
		if ($item === '.' || $item === '..') {
			continue;
		}
		$child = $path . '/' . $item;
		if (is_dir($child) && !is_link($child)) {
			remove_directory_tree($child);
			continue;
		}
		if (!@unlink($child)) {
			scpp_fail('Failed to remove file while cleaning: ' . $child . PHP_EOL, 2);
		}
	}
	if (!@rmdir($path)) {
		scpp_fail('Failed to remove directory while cleaning: ' . $path . PHP_EOL, 2);
	}
}

function write_text_file(string $path, string $contents): void
{
	$dir = dirname($path);
	if (!is_dir($dir)) {
		ensure_directory($dir);
	}
	if (is_file($path)) {
		$existing = file_get_contents($path);
		if (is_string($existing) && $existing === $contents) {
			return;
		}
	}
	if (file_put_contents($path, $contents) === false) {
		scpp_fail('Failed to write file: ' . $path . PHP_EOL, 2);
	}
}

/** @param array<string,mixed> $data */
function write_json_file_atomic(string $path, array $data): void
{
	$dir = dirname($path);
	if (!is_dir($dir)) {
		ensure_directory($dir);
	}
	$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	if (!is_string($json)) {
		scpp_fail('Failed to encode JSON file: ' . $path . PHP_EOL, 2);
	}
	$tmpPath = $path . '.tmp.' . bin2hex(random_bytes(4));
	if (file_put_contents($tmpPath, $json . PHP_EOL) === false) {
		scpp_fail('Failed to write temp JSON file: ' . $tmpPath . PHP_EOL, 2);
	}
	if (!@rename($tmpPath, $path)) {
		@unlink($tmpPath);
		scpp_fail('Failed to publish JSON file: ' . $path . PHP_EOL, 2);
	}
}

/** @return array<string,mixed>|null */
function read_json_file(string $path): ?array
{
	if (!is_file($path)) {
		return null;
	}
	$json = file_get_contents($path);
	if (!is_string($json) || trim($json) === '') {
		return null;
	}
	$data = json_decode($json, true);
	return is_array($data) ? $data : null;
}

/** @param list<array{line:int,relation:string}> $lineMap */
function write_generated_line_map_file(string $path, array $lineMap): void
{
	$lines = ["generated_line\toriginal_line\trelation"];
	foreach ($lineMap as $index => $row) {
		$originalLine = is_array($row) ? (int) ($row['line'] ?? 0) : 0;
		$relation = is_array($row) ? (string) ($row['relation'] ?? 'around') : 'around';
		if (!in_array($relation, ['exact', 'above', 'below', 'around'], true)) {
			$relation = 'around';
		}
		$lines[] = (string) ($index + 1) . "\t" . (string) max(1, $originalLine) . "\t" . $relation;
	}
	write_text_file($path, implode(PHP_EOL, $lines) . PHP_EOL);
}

/**
 * @param array<string,mixed>|null $previous
 * @param array{size:int,mtime:int,content_hash:string} $meta
 * @return list<string>
 */
function collect_transpile_reasons(
	?array $previous,
	array $meta,
	string $generatorSignature,
	string $generatedHeader,
	string $generatedCpp,
	bool $emitProgramEntry,
	string $generatedExportManifest,
): array {
	$reasons = [];
	if (!is_array($previous)) {
		$reasons[] = 'new source file';
		return $reasons;
	}
	if (!isset($previous['size'], $previous['mtime'], $previous['content_hash'], $previous['generator_signature'])) {
		$reasons[] = 'cached source metadata incomplete';
	}
	if ((string) ($previous['generator_signature'] ?? '') !== $generatorSignature) {
		$reasons[] = 'generator signature changed';
	}
	if ((int) ($previous['size'] ?? -1) !== $meta['size']) {
		$reasons[] = 'source file size changed';
	}
	if ((string) ($previous['content_hash'] ?? '') !== $meta['content_hash']) {
		$reasons[] = 'source file content changed';
	}
	if (!is_file($generatedHeader)) {
		$reasons[] = 'generated header missing';
	}
	if (!is_file($generatedCpp)) {
		$reasons[] = 'generated source missing';
	}
	if ((bool) ($previous['emit_program_entry'] ?? false) !== $emitProgramEntry) {
		$reasons[] = $emitProgramEntry
			? 'file became the selected entrypoint'
			: 'file is no longer the selected entrypoint';
	}
	if (!$emitProgramEntry && is_file($generatedCpp) && generated_cpp_contains_program_entry($generatedCpp)) {
		$reasons[] = 'generated source still contains a stale program entry';
	}
	if ((bool) ($previous['has_export_manifest'] ?? false) && !is_file($generatedExportManifest)) {
		$reasons[] = 'generated export manifest missing';
	}
	return $reasons;
}

/**
 * @param array{compile_runtime?:bool,compile_dependencies?:bool,force_runtime_rebuild?:bool,entry_override?:?string} $options
 * @param list<array<string,mixed>> $sourceRebuildReasons
 * @param list<string> $rebuiltOutputs
 * @return array<string,mixed>
 */
function build_explanation_details(
	string $projectRoot,
	array $options,
	int $transpiledCount,
	int $skippedCount,
	array $sourceRebuildReasons,
	array $rebuiltOutputs,
	int $exitCode,
	?string $entrySourcePath = null,
	?string $entryGeneratedCppPath = null,
	?string $entryObjectPath = null,
	?string $outputPath = null,
	array $ninjaCommand = [],
): array {
	$entrySourcePath = is_string($entrySourcePath) && trim($entrySourcePath) !== '' ? $entrySourcePath : null;
	$entryGeneratedCppPath = is_string($entryGeneratedCppPath) && trim($entryGeneratedCppPath) !== '' ? $entryGeneratedCppPath : null;
	$entryObjectPath = is_string($entryObjectPath) && trim($entryObjectPath) !== '' ? $entryObjectPath : null;
	$outputPath = is_string($outputPath) && trim($outputPath) !== '' ? $outputPath : null;

	$runtimeReasons = $options['compile_runtime']
		? [($options['force_runtime_rebuild'] ?? false) ? 'runtime rebuild forced for this build' : 'runtime compilation requested for this build']
		: ['reusing existing runtime artifact by default'];
	$dependencyReasons = $options['compile_dependencies']
		? ['dependency compilation requested for this build']
		: ['reusing dependency artifacts by default'];

	$rebuilt = [];
	foreach ($rebuiltOutputs as $path) {
		$rebuilt[] = normalize_config_path(relative_path($projectRoot, $path));
	}

	return [
		'status' => $exitCode === 0 ? 'success' : 'failure',
		'transpiled_count' => $transpiledCount,
		'skipped_count' => $skippedCount,
		'output_path' => $outputPath !== null ? normalize_config_path(relative_path($projectRoot, $outputPath)) : null,
		'ninja_command' => array_values(array_map(static fn ($value): string => (string) $value, $ninjaCommand)),
		'entrypoint' => [
			'source_path' => $entrySourcePath !== null ? normalize_config_path(relative_path($projectRoot, $entrySourcePath)) : null,
			'generated_cpp' => $entryGeneratedCppPath !== null ? normalize_config_path(relative_path($projectRoot, $entryGeneratedCppPath)) : null,
			'object_path' => $entryObjectPath !== null ? normalize_config_path(relative_path($projectRoot, $entryObjectPath)) : null,
		],
		'runtime' => [
			'action' => $options['compile_runtime'] ? 'rebuild' : 'reuse',
			'reasons' => $runtimeReasons,
		],
		'dependencies' => [
			'action' => $options['compile_dependencies'] ? 'rebuild' : 'reuse',
			'reasons' => $dependencyReasons,
		],
		'sources' => array_values($sourceRebuildReasons),
		'rebuilt_outputs' => $rebuilt,
	];
}

/**
 * @param array<string,mixed> $details
 * @return list<string>
 */
function render_build_explanation_lines(array $details): array
{
	$lines = [];
	$lines[] = 'Build status: ' . (string) ($details['status'] ?? 'unknown');
	$lines[] = 'PHP transpile decisions: ' . (int) ($details['transpiled_count'] ?? 0) . ' transpiled, ' . (int) ($details['skipped_count'] ?? 0) . ' reused';

	$runtime = is_array($details['runtime'] ?? null) ? $details['runtime'] : [];
	$runtimeReasons = is_array($runtime['reasons'] ?? null) ? $runtime['reasons'] : [];
	$lines[] = 'Runtime: ' . (string) ($runtime['action'] ?? 'unknown') . format_reason_suffix($runtimeReasons);

	$dependencies = is_array($details['dependencies'] ?? null) ? $details['dependencies'] : [];
	$dependencyReasons = is_array($dependencies['reasons'] ?? null) ? $dependencies['reasons'] : [];
	$lines[] = 'Dependencies: ' . (string) ($dependencies['action'] ?? 'unknown') . format_reason_suffix($dependencyReasons);

	$sources = is_array($details['sources'] ?? null) ? $details['sources'] : [];
	$transpiled = [];
	$reused = [];
	foreach ($sources as $source) {
		if (!is_array($source)) {
			continue;
		}
		$path = (string) ($source['path'] ?? '(unknown)');
		$reasons = is_array($source['reasons'] ?? null) ? $source['reasons'] : [];
		$line = $path . ' -> ' . (string) ($source['action'] ?? 'unknown') . format_reason_suffix($reasons);
		if (($source['action'] ?? '') === 'transpiled') {
			$transpiled[] = $line;
			continue;
		}
		$reused[] = $line;
	}

	if ($transpiled === []) {
		$lines[] = 'Sources transpiled: none';
	} else {
		$lines[] = 'Sources transpiled:';
		foreach ($transpiled as $line) {
			$lines[] = '  - ' . $line;
		}
	}

	if ($reused === []) {
		$lines[] = 'Sources reused: none';
	} else {
		$lines[] = 'Sources reused:';
		foreach ($reused as $line) {
			$lines[] = '  - ' . $line;
		}
	}

	$rebuiltOutputs = is_array($details['rebuilt_outputs'] ?? null) ? $details['rebuilt_outputs'] : [];
	if ($rebuiltOutputs === []) {
		$lines[] = 'Outputs rebuilt: none (up-to-date)';
	} else {
		$lines[] = 'Outputs rebuilt: ' . implode(', ', array_map(static fn ($value): string => (string) $value, $rebuiltOutputs));
	}

	return $lines;
}

/**
 * @param array<string,mixed> $details
 * @return list<string>
 */
function render_explain_build_view_lines(array $details, string $view): array
{
	if ($view === '' || $view === 'summary') {
		$lines = render_build_explanation_lines($details);
		foreach (render_explain_build_ninja_hint_lines($details) as $line) {
			$lines[] = $line;
		}
		return $lines;
	}

	$sources = is_array($details['sources'] ?? null) ? $details['sources'] : [];
	$entrypoint = is_array($details['entrypoint'] ?? null) ? $details['entrypoint'] : [];

	if ($view === 'files-transpiled') {
		$lines = [];
		foreach ($sources as $source) {
			if (!is_array($source) || (string) ($source['action'] ?? '') !== 'transpiled') {
				continue;
			}
			$path = (string) ($source['path'] ?? '(unknown)');
			$lines[] = $path . format_reason_suffix(is_array($source['reasons'] ?? null) ? $source['reasons'] : []);
		}
		return $lines === [] ? ['Files transpiled: none'] : array_merge(['Files transpiled:'], array_map(static fn (string $line): string => '  - ' . $line, $lines));
	}

	if ($view === 'files-reused') {
		$lines = [];
		foreach ($sources as $source) {
			if (!is_array($source) || (string) ($source['action'] ?? '') !== 'reused') {
				continue;
			}
			$path = (string) ($source['path'] ?? '(unknown)');
			$lines[] = $path . format_reason_suffix(is_array($source['reasons'] ?? null) ? $source['reasons'] : []);
		}
		return $lines === [] ? ['Files reused: none'] : array_merge(['Files reused:'], array_map(static fn (string $line): string => '  - ' . $line, $lines));
	}

	if ($view === 'outputs-rebuilt') {
		$rebuiltOutputs = is_array($details['rebuilt_outputs'] ?? null) ? $details['rebuilt_outputs'] : [];
		if ($rebuiltOutputs === []) {
			return ['Outputs rebuilt: none (up-to-date)'];
		}
		$lines = ['Outputs rebuilt:'];
		foreach ($rebuiltOutputs as $value) {
			if (!is_string($value) || trim($value) === '') {
				continue;
			}
			$lines[] = '  - ' . $value;
		}
		return $lines;
	}

	if ($view === 'entrypoint') {
		$entrySource = (string) ($entrypoint['source_path'] ?? '');
		if ($entrySource === '') {
			return ['Entrypoint: unavailable'];
		}
		$lines = ['Entrypoint: ' . $entrySource];
		$generated = trim((string) ($entrypoint['generated_cpp'] ?? ''));
		$object = trim((string) ($entrypoint['object_path'] ?? ''));
		if ($generated !== '') {
			$lines[] = 'Generated C++: ' . $generated;
		}
		if ($object !== '') {
			$lines[] = 'Object: ' . $object;
		}
		return $lines;
	}

	if ($view === 'final-output') {
		$outputPath = trim((string) ($details['output_path'] ?? ''));
		return ['Final output: ' . ($outputPath !== '' ? $outputPath : 'unavailable')];
	}

	if ($view === 'generated-files') {
		$lines = [];
		foreach ($sources as $source) {
			if (!is_array($source)) {
				continue;
			}
			$path = (string) ($source['path'] ?? '(unknown)');
			$generated = trim((string) ($source['generated_cpp'] ?? ''));
			$object = trim((string) ($source['object_path'] ?? ''));
			$line = $path;
			if ($generated !== '') {
				$line .= ' -> ' . $generated;
			}
			if ($object !== '') {
				$line .= ' -> ' . $object;
			}
			$lines[] = $line;
		}
		return $lines === [] ? ['Generated files: none'] : array_merge(['Generated files:'], array_map(static fn (string $line): string => '  - ' . $line, $lines));
	}

	if ($view === 'ninja-target') {
		return render_explain_build_ninja_hint_lines($details);
	}

	scpp_fail(
		'Unknown explain-build view `' . $view . '`. Use one of: files-transpiled, files-reused, outputs-rebuilt, entrypoint, final-output, generated-files, ninja-target.' . PHP_EOL,
		1
	);
}

/**
 * @param array<string,mixed> $details
 * @return list<string>
 */
function render_explain_build_ninja_hint_lines(array $details): array
{
	$entrypoint = is_array($details['entrypoint'] ?? null) ? $details['entrypoint'] : [];
	$outputPath = trim((string) ($details['output_path'] ?? ''));
	$ninjaCommand = is_array($details['ninja_command'] ?? null) ? $details['ninja_command'] : [];

	$target = '';
	$buildDir = '';
	foreach ($ninjaCommand as $index => $value) {
		if (!is_string($value)) {
			continue;
		}
		if ($value === '-C' && isset($ninjaCommand[$index + 1]) && is_string($ninjaCommand[$index + 1])) {
			$buildDir = (string) $ninjaCommand[$index + 1];
		}
	}

	$entrySource = trim((string) ($entrypoint['source_path'] ?? ''));
	if ($entrySource !== '') {
		$target = pathinfo($entrySource, PATHINFO_FILENAME);
	}
	if ($target === '' && $outputPath !== '') {
		$target = pathinfo($outputPath, PATHINFO_FILENAME);
	}

	$lines = [];
	if ($target !== '' && $buildDir !== '') {
		$lines[] = 'Direct Ninja target: ' . $target;
		$lines[] = 'Direct Ninja debug command: ninja -C ' . $buildDir . ' -d explain ' . $target;
	}
	if ($outputPath !== '') {
		$lines[] = 'Warning: `' . $outputPath . '` is the built executable path, not a Ninja target name.';
	}
	if ($target !== '' && $outputPath !== '') {
		$lines[] = 'Use `' . $target . '` as the Ninja target, not `' . $outputPath . '`.';
	}
	return $lines === [] ? ['Ninja target hint: unavailable'] : $lines;
}

/** @param list<mixed> $reasons */
function format_reason_suffix(array $reasons): string
{
	$clean = [];
	foreach ($reasons as $reason) {
		if (!is_string($reason)) {
			continue;
		}
		$reason = trim($reason);
		if ($reason === '') {
			continue;
		}
		$clean[] = $reason;
	}
	if ($clean === []) {
		return '';
	}
	return ' (' . implode('; ', $clean) . ')';
}

function write_last_error_report(
	string $projectRoot,
	string $command,
	array $argv,
	int $exitCode,
	float $startedAt,
	float $finishedAt,
	string $category,
	string $subcategory,
	string $shortMessage,
	array $diagnostics,
	string $stdout,
	string $stderr,
	array $guidance = [],
	?string $projectMode = null,
): void {
	$payload = [
		'version' => 1,
		'command' => $command,
		'argv' => array_values(array_map(static fn ($value): string => (string) $value, $argv)),
		'cwd' => getcwd() === false ? $projectRoot : normalize_path((string) getcwd()),
		'project_root' => normalize_path($projectRoot),
		'project_mode' => $projectMode,
		'category' => $category,
		'subcategory' => $subcategory,
		'short_message' => rtrim($shortMessage),
		'exit_code' => $exitCode,
		'started_at' => gmdate('c', (int) floor($startedAt)),
		'finished_at' => gmdate('c', (int) floor($finishedAt)),
		'duration_ms' => (int) round(max(0, ($finishedAt - $startedAt) * 1000)),
		'guidance' => array_values($guidance !== [] ? $guidance : [
			"Run 'scpp error' for a compact explanation.",
			"Run 'scpp full-error' for the full saved report.",
		]),
		'diagnostics' => array_values(array_map(static function (array $diagnostic): array {
			return [
				'severity' => (string) ($diagnostic['severity'] ?? ''),
				'message' => (string) ($diagnostic['message'] ?? ''),
				'generated_file' => isset($diagnostic['generated_file']) ? normalize_path((string) $diagnostic['generated_file']) : null,
				'generated_line' => isset($diagnostic['generated_line']) ? (int) $diagnostic['generated_line'] : null,
				'generated_column' => isset($diagnostic['generated_column']) ? (is_int($diagnostic['generated_column']) ? $diagnostic['generated_column'] : null) : null,
				'original_file' => isset($diagnostic['original_file']) ? normalize_path((string) $diagnostic['original_file']) : null,
				'original_line' => isset($diagnostic['original_line']) ? (int) $diagnostic['original_line'] : null,
				'source_file' => isset($diagnostic['source_file']) ? normalize_path((string) $diagnostic['source_file']) : null,
				'source_line' => isset($diagnostic['source_line']) ? (int) $diagnostic['source_line'] : null,
				'expression' => isset($diagnostic['expression']) ? (string) $diagnostic['expression'] : null,
				'expected_type' => isset($diagnostic['expected_type']) ? (string) $diagnostic['expected_type'] : null,
				'actual_runtime_kind' => isset($diagnostic['actual_runtime_kind']) ? (string) $diagnostic['actual_runtime_kind'] : null,
				'operation' => isset($diagnostic['operation']) ? (string) $diagnostic['operation'] : null,
				'code' => isset($diagnostic['code']) ? (string) $diagnostic['code'] : null,
				'trace' => array_values(array_filter(
					is_array($diagnostic['trace'] ?? null) ? $diagnostic['trace'] : [],
					static fn ($line): bool => is_string($line) && trim($line) !== ''
				)),
			];
		}, $diagnostics)),
		'raw_output' => [
			'stdout' => $stdout,
			'stderr' => $stderr,
		],
	];
	$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	if ($json === false) {
		return;
	}
	write_text_file(normalize_path($projectRoot . '/.prism/last_error.json'), $json . PHP_EOL);
}

function write_last_run_report(
	string $projectRoot,
	string $command,
	array $argv,
	int $exitCode,
	float $startedAt,
	float $finishedAt,
	array $details,
): void {
	$payload = [
		'version' => 1,
		'command' => $command,
		'argv' => array_values(array_map(static fn ($value): string => (string) $value, $argv)),
		'cwd' => getcwd() === false ? $projectRoot : normalize_path((string) getcwd()),
		'project_root' => normalize_path($projectRoot),
		'status' => $exitCode === 0 ? 'success' : 'failure',
		'exit_code' => $exitCode,
		'started_at' => gmdate('c', (int) floor($startedAt)),
		'finished_at' => gmdate('c', (int) floor($finishedAt)),
		'duration_ms' => (int) round(max(0, ($finishedAt - $startedAt) * 1000)),
		'details' => $details,
	];
	$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	if ($json === false) {
		return;
	}
	write_text_file(normalize_path($projectRoot . '/.prism/last_run.json'), $json . PHP_EOL);
}

/**
 * @param list<array<string,mixed>> $diagnostics
 * @return list<string>
 */
function diagnostic_guidance(string $category, string $subcategory, array $diagnostics, string $stdout, string $stderr): array
{
	$guidance = [];
	if ($category === 'compile') {
		$first = is_array($diagnostics[0] ?? null) ? $diagnostics[0] : null;
		if ($first !== null) {
			$originalFile = $first['original_file'] ?? null;
			$originalLine = isset($first['original_line']) ? (int) $first['original_line'] : 0;
			if (is_string($originalFile) && $originalFile !== '' && $originalLine > 0) {
				$guidance[] = 'Check the original source first: ' . basename($originalFile) . ':' . $originalLine . '.';
			}
		}
		$guidance = array_merge($guidance, compile_failure_guidance($subcategory, $diagnostics));
		return append_standard_report_guidance($guidance, true);
	}

	return append_standard_report_guidance(build_failure_guidance($subcategory, $stdout, $stderr), false);
}

/**
 * @param list<string> $guidance
 * @return list<string>
 */
function append_standard_report_guidance(array $guidance, bool $remapped): array
{
	$guidance[] = $remapped
		? "Run 'scpp error' for the remapped summary."
		: "Run 'scpp error' for the saved summary.";
	$guidance[] = "Run 'scpp full-error' for the full saved report.";
	$out = [];
	foreach ($guidance as $item) {
		$item = trim($item);
		if ($item === '' || in_array($item, $out, true)) {
			continue;
		}
		$out[] = $item;
	}
	return $out;
}

/**
 * @return array{category:string,subcategory:string,short_message:string,guidance:list<string>}
 */
function classify_build_failure(string $stdout, string $stderr): array
{
	$combined = $stderr . ($stderr !== '' && $stdout !== '' ? "\n" : '') . $stdout;
	if (
		(
			preg_match('/missing and no known rule to make it/i', $combined) === 1
			|| preg_match('/required runtime artifact is missing/i', $combined) === 1
			|| preg_match('/expected runtime artifact:/i', $combined) === 1
		)
		&& preg_match('/libruntime\.(so|a|dylib|dll)/i', $combined) === 1
	) {
		return [
			'category' => 'runtime_cache',
			'subcategory' => 'missing_runtime_artifact',
			'short_message' => 'Required runtime artifact is missing.',
			'guidance' => append_standard_report_guidance(build_failure_guidance('missing_runtime_artifact', $stdout, $stderr), false),
		];
	}
	if (preg_match('/(sscache|ccache).*(error|failed|cannot|could not)/i', $combined) === 1) {
		return [
			'category' => 'launcher',
			'subcategory' => 'launcher_failure',
			'short_message' => 'Compiler launcher failed before the build completed.',
			'guidance' => append_standard_report_guidance(build_failure_guidance('launcher_failure', $stdout, $stderr), false),
		];
	}
	if (preg_match('/(permission denied|read-only file system|operation not permitted)/i', $combined) === 1) {
		return [
			'category' => 'filesystem',
			'subcategory' => 'permission_denied',
			'short_message' => 'Build failed because the project or cache path is not writable.',
			'guidance' => append_standard_report_guidance(build_failure_guidance('permission_denied', $stdout, $stderr), false),
		];
	}
	if (preg_match('/(ninja: fatal:|ninja: error:.*(not found|unknown target|loading build|lexing|parse))/i', $combined) === 1) {
		return [
			'category' => 'ninja_backend',
			'subcategory' => 'ninja_backend_error',
			'short_message' => 'Ninja failed before the compiler completed the build.',
			'guidance' => append_standard_report_guidance(build_failure_guidance('ninja_backend_error', $stdout, $stderr), false),
		];
	}
	return [
		'category' => 'ninja_backend',
		'subcategory' => 'build_failed',
		'short_message' => 'Ninja build failed.',
		'guidance' => append_standard_report_guidance(build_failure_guidance('build_failed', $stdout, $stderr), false),
	];
}

/**
 * @return list<string>
 */
function build_failure_guidance(string $subcategory, string $stdout, string $stderr): array
{
	return match ($subcategory) {
		'missing_runtime_artifact' => [
			"Run 'scpp runtime-build' to rebuild the reusable runtime artifact.",
			"Retry with 'scpp build --build-runtime' if you want the build command to refresh the runtime now.",
		],
		'launcher_failure' => [
			'Check your compiler launcher configuration, then retry.',
			'If you are using sscache or ccache, try disabling it once to confirm the root cause.',
		],
		'permission_denied' => [
			'Check write access for the project .prism folder and any runtime/cache directories.',
			'Retry from a writable workspace after fixing permissions or mount mode.',
		],
		'ninja_backend_error' => [
			'Inspect the saved raw output for the backend failure details.',
			'Retry after regenerating the build with a clean workspace if the Ninja graph looks stale.',
		],
		default => [
			'Inspect the saved raw output first to see whether the failure came from Ninja, the compiler, or the environment.',
		],
	};
}

/**
 * @param list<array<string,mixed>> $diagnostics
 * @return array{category:string,subcategory:string}
 */
function classify_compile_failure(array $diagnostics, string $stdout, string $stderr): array
{
	$messages = [];
	foreach ($diagnostics as $diagnostic) {
		if (!is_array($diagnostic)) {
			continue;
		}
		$message = trim((string) ($diagnostic['message'] ?? ''));
		if ($message !== '') {
			$messages[] = $message;
		}
	}
	$combined = strtolower(implode("\n", $messages));
	if ($combined === '') {
		$combined = strtolower($stderr . "\n" . $stdout);
	}

	if (preg_match('/(was not declared in this scope|undeclared identifier|no member named|not a member of)/', $combined) === 1) {
		return ['category' => 'compile', 'subcategory' => 'missing_symbol'];
	}
	if (preg_match('/(cannot convert|no matching function for call|invalid conversion|conversion from|could not convert|candidate function not viable|incompatible)/', $combined) === 1) {
		return ['category' => 'compile', 'subcategory' => 'type_mismatch'];
	}
	if (preg_match('/(fatal error: .*no such file or directory|cannot open include file|file not found)/', $combined) === 1) {
		return ['category' => 'compile', 'subcategory' => 'missing_header'];
	}
	if (preg_match('/(expected .* before|expected unqualified-id|expected [^\\n]*;|syntax error|parse error|does not name a type)/', $combined) === 1) {
		return ['category' => 'compile', 'subcategory' => 'syntax_or_lowering'];
	}
	if (preg_match('/(static assertion failed|assertion.*failed|concept.*not satisfied)/', $combined) === 1) {
		return ['category' => 'compile', 'subcategory' => 'contract_violation'];
	}
	return ['category' => 'compile', 'subcategory' => 'generated_compile_error'];
}

/**
 * @param list<array<string,mixed>> $diagnostics
 * @return list<string>
 */
function compile_failure_guidance(string $subcategory, array $diagnostics): array
{
	$first = is_array($diagnostics[0] ?? null) ? $diagnostics[0] : null;
	$originHint = null;
	if ($first !== null) {
		$originalFile = $first['original_file'] ?? null;
		$originalLine = isset($first['original_line']) ? (int) $first['original_line'] : 0;
		if (is_string($originalFile) && $originalFile !== '' && $originalLine > 0) {
			$originHint = basename($originalFile) . ':' . $originalLine;
		}
	}
	return match ($subcategory) {
		'missing_symbol' => [
			$originHint !== null
				? 'Check for a misspelled name or missing declaration near ' . $originHint . '.'
				: 'Check for a misspelled name or missing declaration near the mapped source line.',
			'If the code looks valid in source, inspect the saved remapped diagnostics for lowering context.',
		],
		'type_mismatch' => [
			'Check the types at the mapped source line and stabilize mixed values earlier if needed.',
			'Look for an argument, return value, or assignment that now needs a more explicit type shape.',
		],
		'missing_header' => [
			'Check whether the generated dependency or include should exist before the compile step.',
			'If this came from generated code, inspect the saved report to see which generated file triggered it.',
		],
		'syntax_or_lowering' => [
			'Check the mapped source line for a construct that may not lower cleanly into the supported subset.',
			'If the source looks valid, inspect the generated location in the saved report for lowering clues.',
		],
		'contract_violation' => [
			'Check the mapped source line for a runtime or template contract that the generated code is violating.',
			'The saved report usually has the closest generated location if more context is needed.',
		],
		default => [
			'Check the mapped source line first, then inspect the generated location if the source-level cause is still unclear.',
		],
	};
}

/**
 * @param array<string,string> $generatedArtifactOrigins
 */
function collect_compiler_diagnostics(string $projectRoot, string $buildDir, string $stdout, string $stderr, array $generatedArtifactOrigins): array
{
	$output = trim($stderr . ($stderr !== '' && $stdout !== '' ? PHP_EOL : '') . $stdout);
	if ($output === '') {
		return [];
	}

	$results = [];
	foreach (preg_split('/\R/', $output) ?: [] as $line) {
		foreach (parse_compiler_diagnostic_line($line) as $diagnostic) {
			$generatedAbs = resolve_diagnostic_reported_path($projectRoot, $buildDir, $diagnostic['file']);
			if ($generatedAbs === null) {
				continue;
			}
			$originSource = $generatedArtifactOrigins[$generatedAbs] ?? null;
			if (!is_string($originSource) || $originSource === '') {
				continue;
			}
			$mapEntry = lookup_generated_map_entry($generatedAbs, $diagnostic['line']);
			$originalLine = is_array($mapEntry) ? (int) $mapEntry['line'] : 0;
			if ($originalLine <= 0) {
				continue;
			}
			$key = $generatedAbs . ':' . $diagnostic['line'] . ':' . ($diagnostic['column'] ?? 0) . ':' . $diagnostic['severity'] . ':' . $diagnostic['message'];
			if (isset($results[$key])) {
				continue;
			}
			$results[$key] = [
				'severity' => $diagnostic['severity'],
				'message' => $diagnostic['message'],
				'generated_file' => $generatedAbs,
				'generated_line' => $diagnostic['line'],
				'generated_column' => $diagnostic['column'],
				'original_file' => normalize_path($originSource),
				'original_line' => $originalLine,
				'original_relation' => is_array($mapEntry) ? (string) $mapEntry['relation'] : 'exact',
			];
		}
	}

	return array_values($results);
}

function render_short_compiler_failure(array $diagnostics, string $projectRoot, ?string $projectMode = null): string
{
	$lines = [];
	$strictHint = strict_project_error_hint($projectMode);
	if ($strictHint !== null) {
		$lines[] = $strictHint;
	}
	$primary = array_slice($diagnostics, 0, 3);
	foreach ($primary as $diagnostic) {
		$originLabel = normalize_config_path(relative_path($projectRoot, $diagnostic['original_file']));
		$generatedLabel = normalize_config_path(relative_path($projectRoot, $diagnostic['generated_file']));
		$relation = is_string($diagnostic['original_relation'] ?? null) ? (string) $diagnostic['original_relation'] : 'exact';
		$originPrefix = $relation === 'exact' ? '' : ($relation === 'around' ? 'around ' : 'near ');
		$line = 'Compile ' . $diagnostic['severity'] . ' in ' . $originPrefix . $originLabel . ':' . $diagnostic['original_line'];
		if ($diagnostic['message'] !== '') {
			$line .= ': ' . $diagnostic['message'];
		}
		$lines[] = $line;
		$generatedLocation = 'Generated location: ' . $generatedLabel . ':' . $diagnostic['generated_line'];
		if ($diagnostic['generated_column'] !== null) {
			$generatedLocation .= ':' . $diagnostic['generated_column'];
		}
		$lines[] = $generatedLocation;
	}
	if (count($diagnostics) > count($primary)) {
		$lines[] = 'Additional remapped diagnostics: ' . (count($diagnostics) - count($primary));
	}
	$lines[] = 'Raw compiler excerpt:';
	foreach (build_raw_output_excerpt_from_diagnostics($diagnostics) as $excerptLine) {
		$lines[] = $excerptLine;
	}
	$lines[] = "Run 'scpp error' for more details.";
	$lines[] = "Run 'scpp full-error' for the saved JSON report.";
	return implode(PHP_EOL, $lines) . PHP_EOL;
}

function strict_project_error_hint(?string $projectMode): ?string
{
	return $projectMode === 'strict'
		? 'Project mode: strict. Prefer supported Prism++ patterns over standard PHP assumptions.'
		: null;
}

function build_raw_output_excerpt_from_diagnostics(array $diagnostics): array
{
	$out = [];
	foreach (array_slice($diagnostics, 0, 3) as $diagnostic) {
		$line = basename($diagnostic['generated_file']) . ':' . $diagnostic['generated_line'];
		if ($diagnostic['generated_column'] !== null) {
			$line .= ':' . $diagnostic['generated_column'];
		}
		$line .= ': ' . $diagnostic['severity'] . ': ' . $diagnostic['message'];
		$out[] = $line;
	}
	return $out;
}

/**
 * @return list<array{file:string,line:int,column:?int,severity:string,message:string}>
 */
function parse_compiler_diagnostic_line(string $line): array
{
	$line = trim($line);
	if ($line === '' || str_starts_with($line, 'In file included from ')) {
		return [];
	}
	$jsonDiagnostics = parse_json_compiler_diagnostics($line);
	if ($jsonDiagnostics !== []) {
		return $jsonDiagnostics;
	}
	if (preg_match('/^(.+?):(\d+):(\d+):\s+(fatal error|error|warning|note):\s+(.*)$/', $line, $matches) === 1) {
		return [[
			'file' => trim((string) $matches[1]),
			'line' => (int) $matches[2],
			'column' => (int) $matches[3],
			'severity' => (string) $matches[4],
			'message' => trim((string) $matches[5]),
		]];
	}
	if (preg_match('/^(.+?):(\d+):\s+(fatal error|error|warning|note):\s+(.*)$/', $line, $matches) === 1) {
		return [[
			'file' => trim((string) $matches[1]),
			'line' => (int) $matches[2],
			'column' => null,
			'severity' => (string) $matches[3],
			'message' => trim((string) $matches[4]),
		]];
	}
	if (preg_match('/^(.+?)\((\d+),(\d+)\):\s+(fatal error|error|warning|note):\s+(.*)$/', $line, $matches) === 1) {
		return [[
			'file' => trim((string) $matches[1]),
			'line' => (int) $matches[2],
			'column' => (int) $matches[3],
			'severity' => (string) $matches[4],
			'message' => trim((string) $matches[5]),
		]];
	}
	if (preg_match('/^(.+?)\((\d+)\):\s+(fatal error|error|warning|note):\s+(.*)$/', $line, $matches) === 1) {
		return [[
			'file' => trim((string) $matches[1]),
			'line' => (int) $matches[2],
			'column' => null,
			'severity' => (string) $matches[3],
			'message' => trim((string) $matches[4]),
		]];
	}
	if (preg_match('/^(.+?)\((\d+),(\d+)\)\s*:\s*(fatal error|error|warning|note)\s*[A-Z]?\d*\s*:\s*(.*)$/', $line, $matches) === 1) {
		return [[
			'file' => trim((string) $matches[1]),
			'line' => (int) $matches[2],
			'column' => (int) $matches[3],
			'severity' => (string) $matches[4],
			'message' => trim((string) $matches[5]),
		]];
	}
	if (preg_match('/^(.+?)\((\d+)\)\s*:\s*(fatal error|error|warning|note)\s*[A-Z]?\d*\s*:\s*(.*)$/', $line, $matches) === 1) {
		return [[
			'file' => trim((string) $matches[1]),
			'line' => (int) $matches[2],
			'column' => null,
			'severity' => (string) $matches[3],
			'message' => trim((string) $matches[4]),
		]];
	}
	if (preg_match('/^(.+?):(\d+):(\d+):\s+(.*)$/', $line, $matches) === 1 && str_contains($matches[4], 'error')) {
		return [[
			'file' => trim((string) $matches[1]),
			'line' => (int) $matches[2],
			'column' => (int) $matches[3],
			'severity' => 'error',
			'message' => trim((string) $matches[4]),
		]];
	}
	return [];
}

/**
 * @return list<array{file:string,line:int,column:?int,severity:string,message:string}>
 */
function parse_json_compiler_diagnostics(string $line): array
{
	if ($line === '' || ($line[0] !== '{' && $line[0] !== '[')) {
		return [];
	}
	$decoded = json_decode($line, true);
	if (!is_array($decoded)) {
		return [];
	}
	$entries = array_is_list($decoded) ? $decoded : [$decoded];
	$results = [];
	foreach ($entries as $entry) {
		if (!is_array($entry)) {
			continue;
		}
		$results = array_merge($results, flatten_json_diagnostic_entry($entry));
	}
	return $results;
}

/**
 * @return list<array{file:string,line:int,column:?int,severity:string,message:string}>
 */
function flatten_json_diagnostic_entry(array $entry): array
{
	$results = [];
	$kind = strtolower((string) ($entry['kind'] ?? $entry['level'] ?? ''));
	$message = trim((string) ($entry['message'] ?? ''));
	$locations = $entry['locations'] ?? null;
	if ($message !== '' && is_array($locations) && $locations !== []) {
		$caret = $locations[0]['caret'] ?? null;
		if (is_array($caret)) {
			$file = trim((string) ($caret['file'] ?? ''));
			$line = (int) ($caret['line'] ?? 0);
			$column = isset($caret['column']) ? (int) $caret['column'] : null;
			if ($file !== '' && $line > 0) {
				$results[] = [
					'file' => $file,
					'line' => $line,
					'column' => $column !== null && $column > 0 ? $column : null,
					'severity' => normalize_json_diagnostic_severity($kind),
					'message' => $message,
				];
			}
		}
	}
	$children = $entry['children'] ?? null;
	if (is_array($children)) {
		foreach ($children as $child) {
			if (is_array($child)) {
				$results = array_merge($results, flatten_json_diagnostic_entry($child));
			}
		}
	}
	return $results;
}

function normalize_json_diagnostic_severity(string $kind): string
{
	return match ($kind) {
		'warning' => 'warning',
		'note' => 'note',
		'fatal error' => 'fatal error',
		default => 'error',
	};
}

function resolve_diagnostic_reported_path(string $projectRoot, string $buildDir, string $reportedPath): ?string
{
	$reportedPath = trim($reportedPath);
	if ($reportedPath === '') {
		return null;
	}
	if (is_absolute_path($reportedPath)) {
		return normalize_path($reportedPath);
	}
	return normalize_path($buildDir . '/' . normalize_config_path($reportedPath));
}

/** @return array{line:int,relation:string}|null */
function lookup_generated_map_entry(string $generatedArtifactPath, int $generatedLine): ?array
{
	if ($generatedLine <= 0) {
		return null;
	}
	$mapPath = $generatedArtifactPath . '.line.tsv';
	$rows = @file($mapPath, FILE_IGNORE_NEW_LINES);
	if (!is_array($rows) || count($rows) < 2) {
		return null;
	}
	foreach (array_slice($rows, 1) as $row) {
		if (!is_string($row) || $row === '') {
			continue;
		}
		$parts = explode("\t", $row);
		if (count($parts) < 2) {
			continue;
		}
		if ((int) $parts[0] !== $generatedLine) {
			continue;
		}
		$relation = isset($parts[2]) ? trim((string) $parts[2]) : 'exact';
		if (!in_array($relation, ['exact', 'above', 'below', 'around'], true)) {
			$relation = 'around';
		}
		return [
			'line' => max(1, (int) $parts[1]),
			'relation' => $relation,
		];
	}
	return null;
}

function lookup_original_line_from_generated_map(string $generatedArtifactPath, int $generatedLine): int
{
	$entry = lookup_generated_map_entry($generatedArtifactPath, $generatedLine);
	return is_array($entry) ? (int) $entry['line'] : 0;
}

function build_generated_base(string $generatedDir, string $relativePhp): string
{
	$trimmed = strip_supported_source_extension($relativePhp);
	if (!is_string($trimmed) || $trimmed === '') {
		$trimmed = 'entry';
	}
	return $generatedDir . '/' . $trimmed;
}

function build_jss_intermediate_phs_path(string $projectRoot, string $relativeSource): string
{
	$trimmed = strip_supported_source_extension($relativeSource);
	if (!is_string($trimmed) || $trimmed === '') {
		$trimmed = 'entry';
	}
	return normalize_path($projectRoot . '/.prism/jss/' . $trimmed . '.phs');
}

function is_jss_source_path(string $path): bool
{
	return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'jss';
}

/** @return array<string,array<string,mixed>> */
function load_stan_frontend_classifications_for_build(string $statePath): array
{
	$state = load_s2s_state($statePath);
	$classifications = $state['frontend_classifications'] ?? [];
	return is_array($classifications) ? $classifications : [];
}

/** @param array<string,array<string,mixed>> $classifications @return array<string,array<string,mixed>> */
function filter_stan_frontend_classifications_for_source(array $classifications, string $sourcePath): array
{
	$normalizedSource = normalize_path($sourcePath);
	$filtered = [];
	foreach ($classifications as $id => $classification) {
		if (!is_array($classification)) {
			continue;
		}
		$classificationPath = (string) ($classification['path'] ?? '');
		if ($classificationPath === '' || normalize_path($classificationPath) !== $normalizedSource) {
			continue;
		}
		$filtered[(string) $id] = $classification;
	}
	return $filtered;
}

function build_output_name(string $entrypointAbs): string
{
	$base = pathinfo($entrypointAbs, PATHINFO_FILENAME);
	if ($base === '') {
		$base = 'app';
	}
	if (PHP_OS_FAMILY === 'Windows') {
		return $base . '.exe';
	}
	return $base;
}

function build_app_pch_header_path(string $buildDir): string
{
	return $buildDir . '/app_pch.hpp';
}

function build_app_pch_artifact_path(string $buildDir, string $compilerKind): string
{
	$headerPath = build_app_pch_header_path($buildDir);
	if ($compilerKind === 'msvc') {
		return $buildDir . '/app_pch.pch';
	}

	return $headerPath . '.gch';
}

function render_app_pch_header(): string
{
	return "#include <scpp/runtime.hpp>\n";
}

function build_runtime_pch_header_path(string $buildDir): string
{
	return $buildDir . '/runtime_pch.hpp';
}

function build_runtime_pch_artifact_path(string $buildDir, string $compilerKind): string
{
	$headerPath = build_runtime_pch_header_path($buildDir);
	if ($compilerKind === 'msvc') {
		return $buildDir . '/runtime_pch.pch';
	}

	return $headerPath . '.gch';
}

function render_runtime_pch_header(): string
{
	return "#include <scpp/lang/php.hpp>\n";
}

/** @param array{command:string,kind:string,launcher?:?string} $compiler */
function supports_compiler_pch(array $compiler): bool
{
	return $compiler['kind'] === 'gnu_like';
}

/** @return array{size:int,mtime:int,content_hash:string} */
function build_file_meta(string $path): array
{
	$size = filesize($path);
	$mtime = filemtime($path);
	$contentHash = hash_file('sha256', $path);
	if ($size === false || $mtime === false || $contentHash === false) {
		scpp_fail('Failed to stat file: ' . $path . PHP_EOL, 2);
	}
	return [
		'size' => (int) $size,
		'mtime' => (int) $mtime,
		'content_hash' => $contentHash,
	];
}

/** @return list<string> */
function collect_project_php_files(string $projectRoot): array
{
	$files = [];
	$byStem = [];
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($projectRoot, FilesystemIterator::SKIP_DOTS)
	);
	foreach ($iterator as $fileInfo) {
		if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
			continue;
		}
		$path = normalize_path($fileInfo->getPathname());
		$relative = normalize_config_path(relative_path($projectRoot, $path));
		if ($relative === SCPP_PROJECT_CONFIG || str_starts_with($relative, '.prism/')) {
			continue;
		}
		$extension = strtolower($fileInfo->getExtension());
		if (!is_supported_source_extension($extension)) {
			continue;
		}
		$stem = strip_supported_source_extension($relative);
		if (isset($byStem[$stem])) {
			scpp_fail(
				'Conflicting source files detected: `'
				. $byStem[$stem]
				. '` and `'
				. $relative
				. '` share the same basename. Keep only one of the .'
				. SCPP_CANONICAL_SOURCE_EXTENSION
				. ' or supported source variants.'
				. PHP_EOL,
				1
			);
		}
		$byStem[$stem] = $relative;
		$files[] = $path;
	}
	sort($files, SORT_STRING);
	return $files;
}

/** @return list<string> */
function collect_project_stan_source_files(string $projectRoot): array
{
	$files = [];
	foreach (collect_project_php_files($projectRoot) as $path) {
		if (is_stan_source_extension(pathinfo($path, PATHINFO_EXTENSION))) {
			$files[] = $path;
		}
	}
	return $files;
}

/** @return string */
function compute_s2s_generator_signature(string $repoRoot, string $phpProfile = 'legacy', array $sourceOverrides = []): string
{
	$parts = [
		'version:' . SCPP_S2S_SIGNATURE_VERSION,
		'php_profile:' . strtolower(trim($phpProfile)),
		'source_overrides:' . ($sourceOverrides === [] ? 'none' : hash('sha256', json_encode($sourceOverrides, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))),
	];

	$files = [
		$repoRoot . '/bin/scpp.php',
		$repoRoot . '/generators/php/src/Transpiler.php',
		$repoRoot . '/generators/php/src/Jss/JssToken.php',
		$repoRoot . '/generators/php/src/Jss/JssNode.php',
		$repoRoot . '/generators/php/src/Jss/JssTokenizer.php',
		$repoRoot . '/generators/php/src/Jss/JssParser.php',
		$repoRoot . '/generators/php/src/Frontend/FrontendCallSurfaceInterface.php',
		$repoRoot . '/generators/php/src/Jss/JssEmitter.php',
		$repoRoot . '/generators/php/src/Jss/JssCallSurface.php',
		$repoRoot . '/generators/php/src/Jss/JssSummaryExtractor.php',
		$repoRoot . '/generators/php/src/Jss/JssSemanticValidator.php',
		$repoRoot . '/generators/php/src/Jss/JssTranspiler.php',
		$repoRoot . '/generators/php/src/Stan/StanPhpRuntimeFunctionCatalog.php',
		$repoRoot . '/generators/php/src/Stan/StanTakeContractResolver.php',
		$repoRoot . '/generators/php/src/Stan/StanFrontendClassifier.php',
		$repoRoot . '/generators/php/src/Generator/Generator.php',
		$repoRoot . '/generators/php/specs/php_runtime_symbols_legacy.json',
		$repoRoot . '/generators/php/specs/php_runtime_symbols_strict.json',
	];

	foreach ($files as $file) {
		if (!is_file($file)) {
			$parts[] = 'missing:' . normalize_config_path($file);
			continue;
		}
		$hash = hash_file('sha256', $file);
		$parts[] = normalize_config_path($file) . ':' . ($hash === false ? 'hash-failed' : $hash);
	}

	return hash('sha256', implode("\n", $parts));
}

/** @param array<string,string> $sourceOverrides @return array<string,string> */
function normalize_source_override_map(array $sourceOverrides): array
{
	$normalized = [];
	foreach ($sourceOverrides as $path => $contents) {
		if (!is_string($path) || !is_string($contents)) {
			continue;
		}
		$normalized[normalize_path($path)] = $contents;
	}
	ksort($normalized, SORT_STRING);
	return $normalized;
}

function load_s2s_state(string $statePath): array
{
	if (!is_file($statePath)) {
		return ['version' => 1, 'files' => []];
	}

	$state = require $statePath;
	if (!is_array($state)) {
		return ['version' => 1, 'files' => []];
	}
	if (!isset($state['files']) || !is_array($state['files'])) {
		$state['files'] = [];
	}
	return $state;
}

function save_s2s_state(string $statePath, array $state): void
{
	$contents = "<?php\nreturn " . var_export($state, true) . ";\n";
	write_text_file($statePath, $contents);
}

/** @param array<string,mixed> $config @return array{cache_dir:string,status_path:string,report_path:string,heartbeat_path:string,request_path:string,lock_path:string} */
function build_stan_worker_paths(string $projectRoot, array $config): array
{
	$cacheDir = normalize_path($projectRoot . '/' . normalize_config_path((string) ($config['cache_dir'] ?? '.prism/cache')));
	return [
		'cache_dir' => $cacheDir,
		'status_path' => $cacheDir . '/' . SCPP_STAN_STATUS_FILE,
		'report_path' => $cacheDir . '/' . SCPP_STAN_REPORT_FILE,
		'heartbeat_path' => $cacheDir . '/' . SCPP_STAN_WORKER_FILE,
		'request_path' => $cacheDir . '/' . SCPP_STAN_REQUEST_FILE,
		'lock_path' => $cacheDir . '/' . SCPP_STAN_WORKER_LOCK_FILE,
	];
}

function scpp_stan_worker_idle_seconds(): int
{
	$value = getenv('SCPP_STAN_WORKER_IDLE_SECONDS');
	if (is_string($value) && ctype_digit($value) && (int) $value > 0) {
		return max(1, (int) $value);
	}
	return 900;
}

function scpp_stan_worker_poll_interval_ms(): int
{
	$value = getenv('SCPP_STAN_WORKER_POLL_INTERVAL_MS');
	if (is_string($value) && ctype_digit($value) && (int) $value > 0) {
		return max(10, (int) $value);
	}
	return 250;
}

function scpp_stan_build_wait_seconds(): float
{
	$value = getenv('SCPP_STAN_BUILD_WAIT_SECONDS');
	if (is_string($value) && is_numeric($value)) {
		$float = (float) $value;
		if ($float > 0.0) {
			return $float;
		}
	}
	return 10.0;
}

function build_stan_worker_run_id(): string
{
	return 'stan-run-' . bin2hex(random_bytes(8));
}

function compute_stan_source_fingerprint(string $projectRoot, string $configPath): string
{
	$parts = [];
	$parts[] = 'implementation:' . compute_stan_implementation_fingerprint(resolve_repo_root());
	foreach (collect_stan_fingerprint_units($projectRoot, $configPath) as $unit) {
		$configHash = is_file($unit['config_path']) ? hash_file('sha256', $unit['config_path']) : false;
		$parts[] = normalize_path($unit['config_path']) . ':' . ($configHash === false ? 'missing' : $configHash);
		foreach ($unit['source_files'] as $path) {
			$hash = hash_file('sha256', $path);
			$parts[] = normalize_path($path) . ':' . ($hash === false ? 'hash-failed' : $hash);
		}
	}
	return hash('sha256', implode("\n", $parts));
}

function compute_stan_implementation_fingerprint(string $repoRoot): string
{
	$files = [
		$repoRoot . '/bin/project_services.php',
		$repoRoot . '/generators/php/src/Jss/JssCallSurface.php',
		$repoRoot . '/generators/php/src/Jss/JssFileSummaryBuilder.php',
		$repoRoot . '/generators/php/src/Jss/JssFrontendRequestFactory.php',
		$repoRoot . '/generators/php/src/Jss/JssNode.php',
		$repoRoot . '/generators/php/src/Jss/JssParser.php',
		$repoRoot . '/generators/php/src/Jss/JssSummaryExtractor.php',
		$repoRoot . '/generators/php/src/Jss/JssTokenizer.php',
		$repoRoot . '/generators/php/src/Stan/StanFrontendClassifier.php',
		$repoRoot . '/generators/php/src/Stan/StanSemanticPass.php',
		$repoRoot . '/generators/php/src/Stan/StanSourceCatalogBuilder.php',
		$repoRoot . '/generators/php/src/Stan/StanSymbolIndexBuilder.php',
		$repoRoot . '/generators/php/src/Stan/StanWorkspaceContextBuilder.php',
		$repoRoot . '/generators/php/src/Stan/StanWorkspaceSession.php',
	];
	$parts = ['version:' . SCPP_STAN_SIGNATURE_VERSION];
	foreach ($files as $file) {
		$hash = is_file($file) ? hash_file('sha256', $file) : false;
		$parts[] = normalize_config_path($file) . ':' . ($hash === false ? 'missing' : $hash);
	}
	return hash('sha256', implode("\n", $parts));
}

/** @return list<array{project_root:string,config_path:string,source_files:list<string>}> */
function collect_stan_fingerprint_units(string $projectRoot, string $configPath): array
{
	$units = [];
	foreach (resolve_project_dependency_graph($projectRoot, $configPath) as $projectSpec) {
		if (!is_array($projectSpec)) {
			continue;
		}
		$depRoot = normalize_path((string) ($projectSpec['project_root'] ?? ''));
		$depConfigPath = normalize_path((string) ($projectSpec['config_path'] ?? ''));
		if ($depRoot === '' || $depConfigPath === '') {
			continue;
		}
		$sourceFiles = collect_project_stan_source_files($depRoot);
		sort($sourceFiles, SORT_STRING);
		$units[] = [
			'project_root' => $depRoot,
			'config_path' => $depConfigPath,
			'source_files' => $sourceFiles,
		];
	}
	usort(
		$units,
		static fn (array $a, array $b): int => strcmp($a['project_root'], $b['project_root'])
	);
	return $units;
}

/** @param array<string,mixed> $heartbeat */
function stan_worker_heartbeat_is_live(?array $heartbeat): bool
{
	if (!is_array($heartbeat)) {
		return false;
	}
	$lastHeartbeatAt = (float) ($heartbeat['last_heartbeat_at'] ?? 0.0);
	return $lastHeartbeatAt > 0.0 && (microtime(true) - $lastHeartbeatAt) <= 5.0;
}

/** @param array<string,mixed> $payload */
function write_stan_worker_heartbeat(string $heartbeatPath, array $payload): void
{
	write_json_file_atomic($heartbeatPath, $payload);
}

/** @param array<string,mixed> $diagnostic @return 'compile-errors'|'stan-errors'|'stan-warnings'|'stan-notices' */
function classify_stan_build_bucket(array $diagnostic): string
{
	$kind = (string) ($diagnostic['kind'] ?? '');
	if ($kind === 'frontend_classification') {
		$code = (string) ($diagnostic['code'] ?? '');
		if (in_array($code, ['frontend_member_access', 'frontend_binary_plus', 'frontend_take_contract'], true)) {
			return 'compile-errors';
		}
		return 'stan-warnings';
	}
	if (in_array($kind, [
		'duplicate_declaration',
		'unresolved_call',
		'unresolved_static_call',
		'unresolved_method_call',
		'unresolved_property_write',
		'unresolved_property_read',
	], true)) {
		return 'compile-errors';
	}
	if (in_array($kind, [
		'unresolved_dependency',
		'ambiguous_dependency',
		'override_declaration',
		'argument_type_mismatch',
		'argument_count_mismatch',
		'missing_return',
		'static_instance_misuse',
		'invalid_property_read',
	], true)) {
		return 'stan-errors';
	}
	return 'stan-warnings';
}

/** @param list<array<string,mixed>> $diagnostics @return array{compile_error_count:int,stan_error_count:int,stan_warning_count:int,stan_notice_count:int,diagnostics:list<array<string,mixed>>} */
function classify_stan_build_diagnostics(array $diagnostics): array
{
	$result = [
		'compile_error_count' => 0,
		'stan_error_count' => 0,
		'stan_warning_count' => 0,
		'stan_notice_count' => 0,
		'diagnostics' => [],
	];
	foreach ($diagnostics as $diagnostic) {
		if (!is_array($diagnostic)) {
			continue;
		}
		$bucket = classify_stan_build_bucket($diagnostic);
		if ($bucket === 'compile-errors') {
			$result['compile_error_count']++;
		} elseif ($bucket === 'stan-errors') {
			$result['stan_error_count']++;
		} elseif ($bucket === 'stan-warnings') {
			$result['stan_warning_count']++;
		} else {
			$result['stan_notice_count']++;
		}
		$diagnostic['build_bucket'] = $bucket;
		$result['diagnostics'][] = $diagnostic;
	}
	return $result;
}

/** @return array<string,mixed> */
function build_stan_worker_report(string $projectRoot, string $configPath, string $sourceFingerprint): array
{
	$session = new \Scpp\S2S\Stan\StanWorkspaceSession();
	$startedAt = microtime(true);
	$snapshot = $session->createBridgeSnapshot($projectRoot, $configPath, []);
	$diagnosticResult = $session->buildDiagnosticsResultFromSnapshot($snapshot);
	$classified = classify_stan_build_diagnostics(is_array($diagnosticResult['diagnostics'] ?? null) ? $diagnosticResult['diagnostics'] : []);
	$finishedAt = microtime(true);
	return [
		'project_root' => normalize_path((string) ($diagnosticResult['project_root'] ?? $projectRoot)),
		'php_profile' => (string) ($diagnosticResult['php_profile'] ?? ''),
		'source_fingerprint' => $sourceFingerprint,
		'run_id' => build_stan_worker_run_id(),
		'started_at' => $startedAt,
		'finished_at' => $finishedAt,
		'source_unit_count' => (int) ($diagnosticResult['source_unit_count'] ?? 0),
		'analyzed_count' => (int) ($diagnosticResult['analyzed_count'] ?? 0),
		'reused_count' => (int) ($diagnosticResult['reused_count'] ?? 0),
		'warning_count' => (int) ($diagnosticResult['warning_count'] ?? 0),
		'warning_samples' => is_array($diagnosticResult['warning_samples'] ?? null) ? $diagnosticResult['warning_samples'] : [],
		'compile_error_count' => $classified['compile_error_count'],
		'stan_error_count' => $classified['stan_error_count'],
		'stan_warning_count' => $classified['stan_warning_count'],
		'stan_notice_count' => $classified['stan_notice_count'],
		'diagnostics' => $classified['diagnostics'],
	];
}

/** @param array<string,mixed> $status */
function stan_status_matches_fingerprint(?array $status, string $sourceFingerprint): bool
{
	return is_array($status)
		&& (string) ($status['analysis_state'] ?? '') === 'ready'
		&& (string) ($status['source_fingerprint'] ?? '') === $sourceFingerprint;
}

function spawn_stan_worker_process(string $projectRoot): void
{
	$phpBinary = PHP_BINARY;
	$scriptPath = resolve_repo_root() . '/bin/scpp.php';
	$command = implode(' ', [
		escapeshellarg($phpBinary),
		escapeshellarg($scriptPath),
		'stan',
		'worker',
	]);
	$descriptor = [
		0 => ['file', '/dev/null', 'r'],
		1 => ['file', '/dev/null', 'a'],
		2 => ['file', '/dev/null', 'a'],
	];
	$process = proc_open(['/bin/sh', '-c', $command . ' >/dev/null 2>&1 &'], $descriptor, $pipes, $projectRoot, scpp_build_process_environment());
	if (!is_resource($process)) {
		scpp_fail('Failed to start STAN worker.' . PHP_EOL, 2);
	}
	proc_close($process);
}

/** @param array<string,mixed> $report */
function render_stan_compile_error_lines(array $report): array
{
	$lines = [];
	$lines[] = 'STAN pre-build check failed: ' . (int) ($report['compile_error_count'] ?? 0) . ' compile-errors';
	$lines[] = '';
	$diagnostics = is_array($report['diagnostics'] ?? null) ? $report['diagnostics'] : [];
	foreach ($diagnostics as $diagnostic) {
		if (!is_array($diagnostic) || (string) ($diagnostic['build_bucket'] ?? '') !== 'compile-errors') {
			continue;
		}
		$lines[] = '[compile-error] ' . (string) ($diagnostic['message'] ?? 'Unknown STAN compile error.');
		$path = (string) ($diagnostic['path'] ?? '');
		$line = (int) ($diagnostic['line'] ?? 0);
		if ($path !== '' && $line > 0) {
			$lines[] = '  at ' . normalize_config_path(relative_path((string) ($report['project_root'] ?? ''), $path)) . ':' . $line;
		}
		$lines[] = '';
	}
	$lines[] = 'Build stopped before C++ generation/compilation.';
	$lines[] = 'To build without STAN, run `scpp build --no-stan`.';
	$lines[] = 'Run `scpp stan` for the full static-analysis report.';
	return $lines;
}

/** @param array<string,mixed> $report */
function maybe_print_stan_advisory_summary(array $report): void
{
	$stanErrors = (int) ($report['stan_error_count'] ?? 0);
	$stanWarnings = (int) ($report['stan_warning_count'] ?? 0);
	$stanNotices = (int) ($report['stan_notice_count'] ?? 0);
	if ($stanErrors === 0 && $stanWarnings === 0 && $stanNotices === 0) {
		return;
	}
	echo 'Static Analysis: '
		. $stanErrors . ' errors, '
		. $stanWarnings . ' warnings, '
		. $stanNotices . ' notices.'
		. ' Run `scpp stan` for more details.'
		. PHP_EOL;
}

/** @param array<string,mixed> $config @param array<string,string> $sourceOverrides @return array<string,mixed> */
function execute_stan_build_preflight(string $projectRoot, string $configPath, array $config, array $sourceOverrides = []): array
{
	if ($sourceOverrides !== []) {
		$report = build_stan_override_report($projectRoot, $configPath, $sourceOverrides);
		if ((int) ($report['compile_error_count'] ?? 0) > 0) {
			scpp_fail(implode(PHP_EOL, render_stan_compile_error_lines($report)) . PHP_EOL, 1);
		}
		maybe_print_stan_advisory_summary($report);
		return $report;
	}
	$paths = build_stan_worker_paths($projectRoot, $config);
	ensure_directory($paths['cache_dir']);
	$sourceFingerprint = compute_stan_source_fingerprint($projectRoot, $configPath);
	$status = read_json_file($paths['status_path']);

	if (!stan_status_matches_fingerprint($status, $sourceFingerprint)) {
		$heartbeat = read_json_file($paths['heartbeat_path']);
		if (!stan_worker_heartbeat_is_live($heartbeat)) {
			$report = build_stan_worker_report($projectRoot, $configPath, $sourceFingerprint);
			write_json_file_atomic($paths['report_path'], $report);
			write_json_file_atomic($paths['status_path'], [
				'project_root' => normalize_path($projectRoot),
				'analysis_state' => 'ready',
				'source_fingerprint' => $sourceFingerprint,
				'requested_fingerprint' => $sourceFingerprint,
				'run_id' => $report['run_id'],
				'started_at' => $report['started_at'],
				'finished_at' => $report['finished_at'],
				'last_activity_at' => microtime(true),
				'compile_error_count' => $report['compile_error_count'],
				'stan_error_count' => $report['stan_error_count'],
				'stan_warning_count' => $report['stan_warning_count'],
				'stan_notice_count' => $report['stan_notice_count'],
				'report_path' => normalize_path($paths['report_path']),
			]);
			$status = read_json_file($paths['status_path']);
		} else {
			write_json_file_atomic($paths['request_path'], [
				'requested_at' => microtime(true),
				'requested_fingerprint' => $sourceFingerprint,
				'reason' => 'build',
			]);

			$deadline = microtime(true) + scpp_stan_build_wait_seconds();
			while (microtime(true) < $deadline) {
				usleep(100000);
				$status = read_json_file($paths['status_path']);
				if (stan_status_matches_fingerprint($status, $sourceFingerprint)) {
					break;
				}
				if (is_array($status) && (string) ($status['analysis_state'] ?? '') === 'failed' && (string) ($status['source_fingerprint'] ?? '') === $sourceFingerprint) {
					$error = trim((string) ($status['error'] ?? 'STAN worker failed.'));
					scpp_fail('STAN pre-build check failed: ' . $error . PHP_EOL, 2);
				}
			}
		}
	}

	$status = read_json_file($paths['status_path']);
	if (!stan_status_matches_fingerprint($status, $sourceFingerprint)) {
		scpp_fail('STAN pre-build check timed out while waiting for fresh analysis state.' . PHP_EOL, 2);
	}
	$report = read_json_file($paths['report_path']);
	if (!is_array($report) || (string) ($report['source_fingerprint'] ?? '') !== $sourceFingerprint) {
		scpp_fail('STAN pre-build check did not publish a usable report for the current source state.' . PHP_EOL, 2);
	}
	if ((int) ($report['compile_error_count'] ?? 0) > 0) {
		scpp_fail(implode(PHP_EOL, render_stan_compile_error_lines($report)) . PHP_EOL, 1);
	}
	maybe_print_stan_advisory_summary($report);
	return $report;
}

/** @param array<string,string> $sourceOverrides @return array<string,mixed> */
function build_stan_override_report(string $projectRoot, string $configPath, array $sourceOverrides): array
{
	$session = new \Scpp\S2S\Stan\StanWorkspaceSession();
	$startedAt = microtime(true);
	$snapshot = $session->createBridgeSnapshot($projectRoot, $configPath, $sourceOverrides);
	$diagnosticResult = $session->buildDiagnosticsResultFromSnapshot($snapshot);
	$classified = classify_stan_build_diagnostics(is_array($diagnosticResult['diagnostics'] ?? null) ? $diagnosticResult['diagnostics'] : []);
	$finishedAt = microtime(true);
	return [
		'project_root' => normalize_path((string) ($diagnosticResult['project_root'] ?? $projectRoot)),
		'php_profile' => (string) ($diagnosticResult['php_profile'] ?? ''),
		'source_fingerprint' => 'debug-override',
		'run_id' => build_stan_worker_run_id(),
		'started_at' => $startedAt,
		'finished_at' => $finishedAt,
		'source_unit_count' => (int) ($diagnosticResult['source_unit_count'] ?? 0),
		'analyzed_count' => (int) ($diagnosticResult['analyzed_count'] ?? 0),
		'reused_count' => (int) ($diagnosticResult['reused_count'] ?? 0),
		'warning_count' => (int) ($diagnosticResult['warning_count'] ?? 0),
		'warning_samples' => is_array($diagnosticResult['warning_samples'] ?? null) ? $diagnosticResult['warning_samples'] : [],
		'compile_error_count' => $classified['compile_error_count'],
		'stan_error_count' => $classified['stan_error_count'],
		'stan_warning_count' => $classified['stan_warning_count'],
		'stan_notice_count' => $classified['stan_notice_count'],
		'diagnostics' => $classified['diagnostics'],
	];
}

/** @return array{project_root:string,php_profile:string,source_unit_count:int,analyzed_count:int,reused_count:int,warning_count:int,duplicate_count:int,resolution_warning_count:int,override_warning_count:int,symbol_count:int,state_path:string,runtime_shallow_sources:list<array{profile:string,path:string,generated:int,skipped:list<string>}>} */
function execute_stan(string $projectRoot, string $configPath): array
{

	return (new StanRunner())->run($projectRoot, $configPath);
}

/** @return array<string,mixed> */
function load_or_execute_stan_cli_result(string $projectRoot, string $configPath): array
{
	$config = load_project_config($configPath);
	$paths = build_stan_worker_paths($projectRoot, $config);
	$sourceFingerprint = compute_stan_source_fingerprint($projectRoot, $configPath);
	$status = read_json_file($paths['status_path']);
	$report = read_json_file($paths['report_path']);
	if (stan_status_matches_fingerprint($status, $sourceFingerprint) && is_array($report) && (string) ($report['source_fingerprint'] ?? '') === $sourceFingerprint) {
		return build_stan_cli_result_from_report($projectRoot, $configPath, $report);
	}
	$report = build_stan_worker_report($projectRoot, $configPath, $sourceFingerprint);
	write_json_file_atomic($paths['report_path'], $report);
	write_json_file_atomic($paths['status_path'], [
		'project_root' => normalize_path($projectRoot),
		'analysis_state' => 'ready',
		'source_fingerprint' => $sourceFingerprint,
		'requested_fingerprint' => $sourceFingerprint,
		'run_id' => $report['run_id'],
		'started_at' => $report['started_at'],
		'finished_at' => $report['finished_at'],
		'last_activity_at' => microtime(true),
		'compile_error_count' => $report['compile_error_count'],
		'stan_error_count' => $report['stan_error_count'],
		'stan_warning_count' => $report['stan_warning_count'],
		'stan_notice_count' => $report['stan_notice_count'],
		'report_path' => normalize_path($paths['report_path']),
	]);
	return build_stan_cli_result_from_report($projectRoot, $configPath, $report);
}

/** @param array<string,mixed> $report @return array<string,mixed> */
function build_stan_cli_result_from_report(string $projectRoot, string $configPath, array $report): array
{
	$diagnostics = is_array($report['diagnostics'] ?? null) ? $report['diagnostics'] : [];
	$counts = [
		'duplicate_count' => 0,
		'resolution_warning_count' => 0,
		'override_warning_count' => 0,
		'return_chain_warning_count' => 0,
		'expression_chain_warning_count' => 0,
		'local_type_warning_count' => 0,
		'property_type_warning_count' => 0,
		'property_read_warning_count' => 0,
		'initialization_warning_count' => 0,
		'call_site_warning_count' => 0,
		'return_type_warning_count' => 0,
	];
	foreach ($diagnostics as $diagnostic) {
		if (!is_array($diagnostic)) {
			continue;
		}
		$kind = (string) ($diagnostic['kind'] ?? '');
		if ($kind === 'duplicate_declaration') {
			$counts['duplicate_count']++;
		} elseif ($kind === 'unresolved_dependency' || $kind === 'ambiguous_dependency') {
			$counts['resolution_warning_count']++;
		} elseif ($kind === 'override_declaration') {
			$counts['override_warning_count']++;
		} elseif ($kind === 'return_chain_resolution_warning') {
			$counts['return_chain_warning_count']++;
		} elseif ($kind === 'expression_chain_resolution_warning') {
			$counts['expression_chain_warning_count']++;
		} elseif ($kind === 'local_type_morph_warning') {
			$counts['local_type_warning_count']++;
		} elseif ($kind === 'property_type_morph_warning') {
			$counts['property_type_warning_count']++;
		} elseif ($kind === 'unresolved_property_read' || $kind === 'invalid_property_read') {
			$counts['property_read_warning_count']++;
		} elseif ($kind === 'initialization_warning') {
			$counts['initialization_warning_count']++;
		} elseif (in_array($kind, ['unresolved_call', 'unresolved_static_call', 'unresolved_method_call', 'argument_count_mismatch', 'argument_type_mismatch', 'static_instance_misuse', 'unresolved_property_write'], true)) {
			$counts['call_site_warning_count']++;
		} elseif ($kind === 'return_type_mismatch' || $kind === 'missing_return') {
			$counts['return_type_warning_count']++;
		}
	}

	return [
		'project_root' => normalize_path((string) ($report['project_root'] ?? $projectRoot)),
		'php_profile' => (string) ($report['php_profile'] ?? resolve_php_runtime_profile(resolve_runtime_build_config(load_project_config($configPath)))),
		'source_unit_count' => (int) ($report['source_unit_count'] ?? 0),
		'analyzed_count' => (int) ($report['analyzed_count'] ?? 0),
		'reused_count' => (int) ($report['reused_count'] ?? 0),
		'warning_count' => (int) ($report['warning_count'] ?? 0),
		'duplicate_count' => $counts['duplicate_count'],
		'resolution_warning_count' => $counts['resolution_warning_count'],
		'override_warning_count' => $counts['override_warning_count'],
		'return_chain_warning_count' => $counts['return_chain_warning_count'],
		'expression_chain_warning_count' => $counts['expression_chain_warning_count'],
		'local_type_warning_count' => $counts['local_type_warning_count'],
		'property_type_warning_count' => $counts['property_type_warning_count'],
		'property_read_warning_count' => $counts['property_read_warning_count'],
		'initialization_warning_count' => $counts['initialization_warning_count'],
		'call_site_warning_count' => $counts['call_site_warning_count'],
		'return_type_warning_count' => $counts['return_type_warning_count'],
		'symbol_count' => 0,
		'state_path' => normalize_path($projectRoot . '/' . normalize_config_path((string) (load_project_config($configPath)['cache_dir'] ?? '.prism/cache')) . '/' . SCPP_STAN_STATE_FILE),
		'runtime_shallow_sources' => [],
		'warning_samples' => is_array($report['warning_samples'] ?? null) ? $report['warning_samples'] : [],
	];
}

/** @param array<string,string> $sourceOverrides @return array<string,mixed> */
function execute_stan_document_diagnostics(string $projectRoot, string $configPath, string $documentPath, array $sourceOverrides = []): array
{
	$session = new \Scpp\S2S\Stan\StanWorkspaceSession();
	$snapshot = $session->createBridgeSnapshot($projectRoot, $configPath, $sourceOverrides);
	return build_stan_document_diagnostics_from_snapshot($session, $snapshot, $documentPath);
}

/** @param array<string,mixed> $snapshot @return array<string,mixed> */
function build_stan_document_diagnostics_from_snapshot(\Scpp\S2S\Stan\StanWorkspaceSession $session, array $snapshot, string $documentPath): array
{
	$result = $session->buildDiagnosticsResultFromSnapshot($snapshot);
	$normalizedPath = normalize_path($documentPath);
	$diagnosticsByPath = is_array($result['diagnostics_by_path'] ?? null) ? $result['diagnostics_by_path'] : [];
	$diagnostics = is_array($diagnosticsByPath[$normalizedPath] ?? null) ? $diagnosticsByPath[$normalizedPath] : [];

	return [
		'project_root' => normalize_path((string) ($result['project_root'] ?? '')),
		'php_profile' => (string) ($result['php_profile'] ?? ''),
		'path' => $normalizedPath,
		'uri' => 'file://' . $normalizedPath,
		'warning_count' => count($diagnostics),
		'diagnostics' => $diagnostics,
		'_snapshot_debug' => $snapshot['debug'] ?? null,
	];
}

/** @param array<string,string> $sourceOverrides @return array<string,mixed> */
function execute_stan_document_symbols(string $projectRoot, string $configPath, string $documentPath, array $sourceOverrides = []): array
{
	$session = new \Scpp\S2S\Stan\StanWorkspaceSession();
	$snapshot = $session->createBridgeSnapshot($projectRoot, $configPath, $sourceOverrides);
	$result = $session->buildDocumentSymbolsResultFromSnapshot($snapshot, $documentPath);
	$result['_snapshot_debug'] = $snapshot['debug'] ?? null;
	return $result;
}

/** @param array<string,mixed> $snapshot @return array<string,mixed> */
function build_stan_document_symbols_from_snapshot(\Scpp\S2S\Stan\StanWorkspaceSession $session, array $snapshot, string $documentPath): array
{
	$result = $session->buildDocumentSymbolsResultFromSnapshot($snapshot, $documentPath);
	$result['_snapshot_debug'] = $snapshot['debug'] ?? null;
	return $result;
}

/** @param array<string,string> $sourceOverrides @return array<string,mixed> */
function execute_stan_hover(string $projectRoot, string $configPath, string $documentPath, int $line, ?int $column = null, array $sourceOverrides = []): array
{
	$session = new \Scpp\S2S\Stan\StanWorkspaceSession();
	$snapshot = $session->createBridgeSnapshot($projectRoot, $configPath, $sourceOverrides);
	$result = $session->buildHoverResultFromSnapshot($snapshot, $documentPath, $line, $column);
	$result['_snapshot_debug'] = $snapshot['debug'] ?? null;
	return $result;
}

/** @param array<string,mixed> $snapshot @return array<string,mixed> */
function build_stan_hover_from_snapshot(\Scpp\S2S\Stan\StanWorkspaceSession $session, array $snapshot, string $documentPath, int $line, ?int $column = null): array
{
	$result = $session->buildHoverResultFromSnapshot($snapshot, $documentPath, $line, $column);
	$result['_snapshot_debug'] = $snapshot['debug'] ?? null;
	return $result;
}

/** @param array<string,string> $sourceOverrides @return array<string,mixed> */
function execute_stan_definition(string $projectRoot, string $configPath, string $documentPath, int $line, ?int $column = null, array $sourceOverrides = []): array
{
	$session = new \Scpp\S2S\Stan\StanWorkspaceSession();
	$snapshot = $session->createBridgeSnapshot($projectRoot, $configPath, $sourceOverrides);
	$result = $session->buildDefinitionResultFromSnapshot($snapshot, $documentPath, $line, $column);
	$result['_snapshot_debug'] = $snapshot['debug'] ?? null;
	return $result;
}

/** @param array<string,mixed> $snapshot @return array<string,mixed> */
function build_stan_definition_from_snapshot(\Scpp\S2S\Stan\StanWorkspaceSession $session, array $snapshot, string $documentPath, int $line, ?int $column = null): array
{
	$result = $session->buildDefinitionResultFromSnapshot($snapshot, $documentPath, $line, $column);
	$result['_snapshot_debug'] = $snapshot['debug'] ?? null;
	return $result;
}

/** @param array<string,string> $sourceOverrides @return array<string,mixed> */
function execute_stan_references(string $projectRoot, string $configPath, string $documentPath, int $line, ?int $column = null, array $sourceOverrides = []): array
{
	$session = new \Scpp\S2S\Stan\StanWorkspaceSession();
	$snapshot = $session->createBridgeSnapshot($projectRoot, $configPath, $sourceOverrides);
	$result = $session->buildReferencesResultFromSnapshot($snapshot, $documentPath, $line, $column);
	$result['_snapshot_debug'] = $snapshot['debug'] ?? null;
	return $result;
}

/** @param array<string,mixed> $snapshot @return array<string,mixed> */
function build_stan_references_from_snapshot(\Scpp\S2S\Stan\StanWorkspaceSession $session, array $snapshot, string $documentPath, int $line, ?int $column = null): array
{
	$result = $session->buildReferencesResultFromSnapshot($snapshot, $documentPath, $line, $column);
	$result['_snapshot_debug'] = $snapshot['debug'] ?? null;
	return $result;
}

function resolve_cli_input_path(string $cwd, string $path): string
{
	if ($path === '') {
		return $cwd;
	}
	if ($path[0] === '/' || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1) {
		return $path;
	}
	return normalize_path($cwd . '/' . $path);
}

/** @param array<string,mixed> $params @return array<string,string> */
function resolve_stan_source_overrides_from_params(string $cwd, array $params): array
{
	$overrides = [];
	$path = null;
	if (isset($params['path']) && is_string($params['path']) && $params['path'] !== '') {
		$path = normalize_path(resolve_cli_input_path($cwd, $params['path']));
	} else {
		$textDocument = is_array($params['textDocument'] ?? null) ? $params['textDocument'] : [];
		$uri = (string) ($textDocument['uri'] ?? ($params['uri'] ?? ''));
		if ($uri !== '') {
			$path = normalize_path(resolve_stan_document_path_from_uri($uri));
		}
	}
	if ($path !== null && isset($params['source']) && is_string($params['source'])) {
		$overrides[$path] = $params['source'];
	}
	return $overrides;
}

/** @param array<string,string> $sourceOverrides */
function build_stan_lsp_snapshot_cache_key(string $projectRoot, string $configPath, array $sourceOverrides): string
{
	$parts = [
		normalize_path($projectRoot),
		normalize_path($configPath),
	];
	ksort($sourceOverrides, SORT_STRING);
	foreach ($sourceOverrides as $path => $contents) {
		$parts[] = normalize_path($path) . ':' . hash('sha256', $contents);
	}
	return hash('sha256', implode("\n", $parts));
}

/** @param array<string,mixed> $payload @param array<string,mixed> $debugMeta @return array<string,mixed> */
function attach_stan_debug_metadata(array $payload, array $debugMeta): array
{
	unset($payload['_snapshot_debug']);
	$payload['_debug'] = $debugMeta;
	return $payload;
}

function generated_cpp_contains_program_entry(string $path): bool
{
	$contents = @file_get_contents($path);
	return is_string($contents) && preg_match('/^int\s+main\s*\(/m', $contents) === 1;
}

/** @param list<string> $phpFiles */
function prune_removed_state_entries(string $projectRoot, string $generatedDir, array $state, array $phpFiles): array
{
	$current = [];
	foreach ($phpFiles as $phpPathAbs) {
		$current[normalize_config_path(relative_path($projectRoot, $phpPathAbs))] = true;
	}

	$files = is_array($state['files'] ?? null) ? $state['files'] : [];
	foreach (array_keys($files) as $relativePhp) {
		if (isset($current[$relativePhp])) {
			continue;
		}
		unset($files[$relativePhp]);
		$generatedBase = build_generated_base($generatedDir, $relativePhp);
		delete_file_if_exists($generatedBase . '.hpp');
		delete_file_if_exists($generatedBase . '.cpp');
	}
	$state['files'] = $files;
	return $state;
}

function delete_file_if_exists(string $path): void
{
	if (is_file($path)) {
		@unlink($path);
	}
}

function replace_file_atomically(string $tmpPath, string $finalPath, string $failureMessage): void
{
	if (is_file($finalPath) && !@unlink($finalPath)) {
		@unlink($tmpPath);
		scpp_fail($failureMessage . ': ' . $finalPath . PHP_EOL, 2);
	}
	if (!@rename($tmpPath, $finalPath)) {
		@unlink($tmpPath);
		scpp_fail($failureMessage . ': ' . $finalPath . PHP_EOL, 2);
	}
}

function build_object_path(string $buildDir, string $relativePhp, string $compilerKind): string
{
	$trimmed = strip_supported_source_extension($relativePhp);
	if (!is_string($trimmed) || $trimmed === '') {
		$trimmed = 'entry';
	}
	return $buildDir . '/' . $trimmed . '.' . object_extension($compilerKind);
}

function build_generated_fcgi_base(string $generatedDir, string $relativePhp): string
{
	$trimmed = strip_supported_source_extension($relativePhp);
	if (!is_string($trimmed) || $trimmed === '') {
		$trimmed = 'entry';
	}
	return $generatedDir . '/' . $trimmed . '__fcgi';
}

function build_fcgi_object_path(string $buildDir, string $relativePhp, string $compilerKind): string
{
	$trimmed = strip_supported_source_extension($relativePhp);
	if (!is_string($trimmed) || $trimmed === '') {
		$trimmed = 'entry';
	}
	return $buildDir . '/' . $trimmed . '__fcgi.' . object_extension($compilerKind);
}

function build_native_object_path(string $buildDir, string $nativeCppPath, string $compilerKind): string
{
	$sanitized = preg_replace('/[^A-Za-z0-9_.\/-]+/', '_', normalize_config_path($nativeCppPath));
	$sanitized = str_replace(['../', '..\\'], '', (string) $sanitized);
	return $buildDir . '/native/' . preg_replace('/\.cpp$/i', '', $sanitized) . '.' . object_extension($compilerKind);
}

/** @return list<string> */
function collect_project_native_cpp_files(string $nativeCppDir): array
{
	if (!is_dir($nativeCppDir)) {
		return [];
	}
	$files = [];
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($nativeCppDir, FilesystemIterator::SKIP_DOTS)
	);
	foreach ($iterator as $fileInfo) {
		if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
			continue;
		}
		if (strcasecmp($fileInfo->getExtension(), 'cpp') !== 0) {
			continue;
		}
		$files[] = normalize_path($fileInfo->getPathname());
	}
	sort($files, SORT_STRING);
	return $files;
}

/** @return array{enabled:bool,workers:int,max_body_size:int,max_requests:int} */
function resolve_fastcgi_config(array $config): array
{
	$fcgi = is_array($config['fastcgi'] ?? null) ? $config['fastcgi'] : [];
	$enabled = (bool) ($fcgi['enabled'] ?? false);
	$workers = max(1, (int) ($fcgi['workers'] ?? 1));
	$maxBodySize = max(1, (int) ($fcgi['max_body_size'] ?? (4 * 1024 * 1024)));
	$maxRequests = max(0, (int) ($fcgi['max_requests'] ?? 0));
	return [
		'enabled' => $enabled,
		'workers' => $workers,
		'max_body_size' => $maxBodySize,
		'max_requests' => $maxRequests,
	];
}

/** @return array{source_path:string,main_object_path:string,output_path:string,cxxflags:list<string>,ldflags:list<string>,entrypoint_generated_cpp:string,entrypoint_object_path:string} */
function resolve_fastcgi_build_spec(string $projectRoot, string $repoRoot, string $buildDir, string $generatedDir, string $entrypointAbs, array $compiler, array $fastcgiConfig): array
{
	$cxxflags = [];
	$ldflags = [];
	$pkgConfig = find_command_path(['pkg-config']);
	if ($pkgConfig !== null) {
		$cflagsOutput = shell_exec(escapeshellarg($pkgConfig) . ' --cflags fcgi 2>/dev/null');
		$libsOutput = shell_exec(escapeshellarg($pkgConfig) . ' --libs fcgi 2>/dev/null');
		if (is_string($cflagsOutput) && trim($cflagsOutput) !== '') {
			$cxxflags = split_shell_tokens($cflagsOutput);
		}
		if (is_string($libsOutput) && trim($libsOutput) !== '') {
			$ldflags = split_shell_tokens($libsOutput);
		}
	}
	if ($ldflags === []) {
		$ldflags = ['-lfcgi', '-lpthread'];
	}
	$cxxflags[] = '-DSCPP_FCGI_DEFAULT_WORKERS=' . max(1, (int) $fastcgiConfig['workers']);
	$cxxflags[] = '-DSCPP_FCGI_DEFAULT_MAX_BODY_SIZE=' . max(1, (int) $fastcgiConfig['max_body_size']);
	$cxxflags[] = '-DSCPP_FCGI_DEFAULT_MAX_REQUESTS=' . max(0, (int) $fastcgiConfig['max_requests']);
	return [
		'source_path' => normalize_config_path(relative_path($projectRoot, $repoRoot . '/runtime/include/hosts/fastcgi/fastcgi_main.cpp')),
		'main_object_path' => normalize_config_path(relative_path($projectRoot, $buildDir . '/fastcgi_main.' . object_extension($compiler['kind']))),
		'output_path' => normalize_path($buildDir . '/' . build_fastcgi_output_name($entrypointAbs)),
		'cxxflags' => $cxxflags,
		'ldflags' => $ldflags,
		'entrypoint_generated_cpp' => '',
		'entrypoint_object_path' => '',
	];
}

/** @return list<string> */
function split_shell_tokens(string $value): array
{
	$value = trim($value);
	if ($value === '') {
		return [];
	}
	$parts = preg_split('/\s+/', $value);
	if (!is_array($parts)) {
		return [];
	}
	return array_values(array_filter(array_map('trim', $parts), static fn (string $part): bool => $part !== ''));
}

function build_fastcgi_output_name(string $entrypointAbs): string
{
	$base = pathinfo($entrypointAbs, PATHINFO_FILENAME);
	if ($base === '') {
		$base = 'app';
	}
	if (PHP_OS_FAMILY === 'Windows') {
		return $base . '_fcgi.exe';
	}
	return $base . '_fcgi';
}

/** @return array{command:string,kind:string,launcher:?string,linker_flags:list<string>,archiver:?string}|null */
function resolve_compiler(array $config): ?array
{
	$override = resolve_compiler_command_override($config);
	if ($override !== null) {
		return build_compiler_spec($override);
	}

	return detect_default_compiler();
}

function resolve_build_mode(array $config): string
{
	$mode = $config['build']['mode'] ?? 'debug';
	if (!is_string($mode)) {
		scpp_fail('Invalid build.mode in ' . SCPP_PROJECT_CONFIG . '; expected a string.' . PHP_EOL, 1);
	}

	$normalized = strtolower(trim($mode));
	if ($normalized === '') {
		return 'debug';
	}
	if (in_array($normalized, ['debug', 'dev', 'development'], true)) {
		return 'debug';
	}
	if ($normalized === 'release') {
		return 'release';
	}

	scpp_fail('Unsupported build.mode `' . $mode . '` in ' . SCPP_PROJECT_CONFIG . '; expected `debug`, `dev`, `development`, or `release`.' . PHP_EOL, 1);
}


function resolve_compiler_command_override(array $config): ?string
{
	$envOverride = getenv('SCPP_CXX');
	if ($envOverride !== false && trim((string) $envOverride) !== '') {
		return trim((string) $envOverride);
	}

	$configOverride = $config['build']['cxx'] ?? null;
	if (is_string($configOverride) && trim($configOverride) !== '') {
		return trim($configOverride);
	}

	return null;
}

/** @return array{command:string,kind:string,launcher:?string,linker_flags:list<string>,archiver:?string} */
function build_compiler_spec(string $command): array
{
	$path = find_command_path([$command]);
	if ($path === null && !preg_match('/[\\\/]/', $command)) {
		scpp_fail('Configured compiler not found in PATH: ' . $command . PHP_EOL, 1);
	}

	$kind = compiler_kind_from_command($command);
	return [
		'command' => $command,
		'kind' => $kind,
		'launcher' => resolve_compiler_launcher($command),
		'linker_flags' => detect_fast_linker_flags($kind),
		'archiver' => detect_archiver_command($kind),
	];
}

function compiler_kind_from_command(string $command): string
{
	$base = strtolower(basename(str_replace('\\', '/', $command)));
	if (preg_match('/^cl(\.exe)?$/', $base) === 1) {
		return 'msvc';
	}
	return 'gnu_like';
}

function resolve_compiler_launcher(string $compilerCommand): ?string
{
	$envOverride = getenv('SCPP_CXX_LAUNCHER');
	if ($envOverride !== false) {
		$trimmed = trim((string) $envOverride);
		if ($trimmed === '') {
			return null;
		}
		$path = find_command_path([$trimmed]);
		if ($path === null && !preg_match('/[\\\/]/', $trimmed)) {
			scpp_fail('Configured compiler launcher not found in PATH: ' . $trimmed . PHP_EOL, 1);
		}
		if (!compiler_launcher_is_usable($trimmed, $compilerCommand)) {
			scpp_fail('Configured compiler launcher is not usable: ' . $trimmed . ' with compiler ' . $compilerCommand . PHP_EOL, 1);
		}
		return $trimmed;
	}

	return detect_compiler_launcher($compilerCommand);
}

/** @return array{command:string,kind:string,launcher:?string,linker_flags:list<string>,archiver:?string}|null */
function detect_default_compiler(): ?array
{
	$candidates = match (PHP_OS_FAMILY) {
		'Windows' => ['g++', 'clang++', 'cl'],
		'Darwin' => ['clang++', 'g++', 'c++'],
		default => ['g++', 'clang++', 'c++'],
	};

	foreach ($candidates as $candidate) {
		$path = find_command_path([$candidate]);
		if ($path === null) {
			continue;
		}
		return build_compiler_spec($candidate);
	}

	return null;
}

function detect_compiler_launcher(string $compilerCommand): ?string
{
	$launcher = find_command_path(['sccache']);
	if ($launcher === null) {
		return null;
	}

	return compiler_launcher_is_usable($launcher, $compilerCommand) ? $launcher : null;
}

function compiler_launcher_is_usable(string $launcherCommand, string $compilerCommand): bool
{
	$result = scpp_run_optional_command(getcwd() === false ? '.' : getcwd(), [$launcherCommand, $compilerCommand, '--version'], [], 2.0);
	return $result['exit_code'] === 0;
}

function detect_archiver_command(string $compilerKind): ?string
{
	if ($compilerKind !== 'gnu_like') {
		return null;
	}

	return find_command_path(['ar', 'llvm-ar']);
}

/** @return list<string> */
function detect_fast_linker_flags(string $compilerKind): array
{
	if ($compilerKind !== 'gnu_like' || PHP_OS_FAMILY === 'Windows') {
		return [];
	}

	if (PHP_OS_FAMILY === 'Linux') {
		$moldPath = find_command_path(['ld.mold', 'mold']);
		if ($moldPath !== null) {
			return ['-fuse-ld=mold'];
		}
	}

	$lldPath = find_command_path(['ld.lld', 'lld']);
	if ($lldPath !== null) {
		return ['-fuse-ld=lld'];
	}

	return [];
}

/** @param array{command:string,kind:string,launcher?:?string,linker_flags?:list<string>,archiver?:?string} $compiler */
function compiler_display_command(array $compiler): string
{
	$launcher = $compiler['launcher'] ?? null;
	$parts = [];
	if (is_string($launcher) && $launcher !== '') {
		$parts[] = basename(str_replace('\\', '/', $launcher));
	}
	$parts[] = $compiler['command'];
	$linkerFlags = is_array($compiler['linker_flags'] ?? null) ? $compiler['linker_flags'] : [];
	foreach ($linkerFlags as $flag) {
		$parts[] = $flag;
	}
	return implode(' ', $parts);
}

/**
 * @param list<array{project_root:string,relative_php:string,generated_cpp:string,object_path:string,is_entrypoint:bool,force_include_header:?string}> $generatedUnits
 * @param list<array{project_root:string,source_path:string,object_path:string,force_include_header:?string}> $nativeCppUnits
 * @param list<string> $projectLibraryFlags
 */
/**
 * @param array{compile_runtime:bool,compile_dependencies:bool} $options
 * @param 'reuse'|'local' $runtimePlacement
 */
function render_build_ninja(string $projectRoot, string $repoRoot, string $buildDir, string $generatedDir, array $generatedUnits, array $nativeCppUnits, string $outputName, array $compiler, string $buildMode, array $runtimeConfig, array $projectLibraryFlags = [], ?array $fastcgiBuild = null, array $options = ['compile_runtime' => true, 'compile_dependencies' => true], string $runtimePlacement = 'reuse'): string
{
	$usePch = array_key_exists('use_pch', $options) ? (bool) $options['use_pch'] : supports_compiler_pch($compiler);
	$generatedIncludeDir = build_ninja_relative_path($projectRoot, $buildDir, $generatedDir);
	$runtimeIncludeDir = build_ninja_relative_path($projectRoot, $buildDir, $repoRoot . '/runtime/include');
	$output = build_ninja_relative_path($projectRoot, $buildDir, $buildDir . '/' . $outputName);
	$appPchHeader = build_ninja_relative_path($projectRoot, $buildDir, build_app_pch_header_path($buildDir));
	$appPchArtifact = build_ninja_relative_path($projectRoot, $buildDir, build_app_pch_artifact_path($buildDir, $compiler['kind']));
	$runtimePchHeader = build_ninja_relative_path($projectRoot, $buildDir, build_runtime_pch_header_path($buildDir));
	$runtimePchArtifact = build_ninja_relative_path($projectRoot, $buildDir, build_runtime_pch_artifact_path($buildDir, $compiler['kind']));
	$compilerCommand = $compiler['command'];
	$compilerLauncher = $compiler['launcher'] ?? null;
	$wrapNinjaCommand = static function (string $command) use ($compiler): string {
		return wrap_windows_gnu_like_ninja_command($compiler, $command);
	};
	$linkerFlags = is_array($compiler['linker_flags'] ?? null) ? $compiler['linker_flags'] : [];
	$runtimeBuild = build_runtime_artifact_spec($repoRoot, $projectRoot, $compiler, $buildMode, $runtimeConfig, $runtimePlacement);
	$sharedRuntimeModules = ($runtimePlacement === 'reuse' && runtime_is_shared_release_eligible($compiler, $buildMode, $runtimeConfig))
		? resolve_shared_runtime_bundle_specs($repoRoot, $projectRoot, $compiler, $buildMode, $runtimeConfig)['modules']
		: [];
	$runtimeSignatureStamp = build_ninja_relative_path($projectRoot, $buildDir, $buildDir . '/runtime_signature.txt');
	$runtimeLinkFlags = $options['compile_runtime'] && is_array($runtimeBuild['link_flags'] ?? null) ? $runtimeBuild['link_flags'] : [];
	$runtimeExtraCxxFlags = $options['compile_runtime'] && is_array($runtimeBuild['extra_cxxflags'] ?? null) ? $runtimeBuild['extra_cxxflags'] : [];
	$appRuntimeCxxFlags = is_array($runtimeBuild['extra_cxxflags'] ?? null) ? $runtimeBuild['extra_cxxflags'] : [];
	$fastcgiCxxFlags = is_array($fastcgiBuild['cxxflags'] ?? null) ? $fastcgiBuild['cxxflags'] : [];
	$fastcgiLdFlags = is_array($fastcgiBuild['ldflags'] ?? null) ? $fastcgiBuild['ldflags'] : [];
	$baseLinkFlags = $linkerFlags;
	$binaryLinkFlags = array_merge($baseLinkFlags, $projectLibraryFlags);
	if (is_string($runtimeBuild['rpath_dir'] ?? null) && $runtimeBuild['rpath_dir'] !== '') {
		$binaryLinkFlags[] = '-Wl,-rpath,' . ninja_escape_path($runtimeBuild['rpath_dir']);
	}
	foreach ($sharedRuntimeModules as $moduleSpec) {
		if (is_string($moduleSpec['rpath_dir'] ?? null) && $moduleSpec['rpath_dir'] !== '') {
			$binaryLinkFlags[] = '-Wl,-rpath,' . ninja_escape_path((string) $moduleSpec['rpath_dir']);
		}
	}
	$binaryLinkFlags = array_values(array_unique($binaryLinkFlags));

	$lines = [];
	$lines[] = 'cxx = ' . $compilerCommand;
	if (is_string($compilerLauncher) && $compilerLauncher !== '') {
		$lines[] = 'cxx_launcher = ' . $compilerLauncher;
	}
	$lines[] = 'cxxflags = ' . build_compiler_flags($compiler['kind'], $buildMode, $runtimeIncludeDir, $generatedIncludeDir) . ($appRuntimeCxxFlags !== [] ? ' ' . implode(' ', $appRuntimeCxxFlags) : '');
	$lines[] = 'runtime_cxxflags = ' . build_runtime_compiler_flags($compiler['kind'], $buildMode, $runtimeIncludeDir) . ($runtimeExtraCxxFlags !== [] ? ' ' . implode(' ', $runtimeExtraCxxFlags) : '');
	$lines[] = 'base_ldflags = ' . implode(' ', $baseLinkFlags);
	$lines[] = 'ldflags = ' . implode(' ', $binaryLinkFlags);
	if ($fastcgiBuild !== null) {
		$lines[] = 'fcgi_cxxflags = ' . implode(' ', $fastcgiCxxFlags);
		$lines[] = 'fcgi_ldflags = ' . implode(' ', array_merge($binaryLinkFlags, $fastcgiLdFlags));
	}
	if ($runtimeLinkFlags !== []) {
		$lines[] = 'runtime_ldflags = ' . implode(' ', $runtimeLinkFlags);
	}
	if ($usePch) {
		$lines[] = 'app_pch_header = ' . $appPchHeader;
		$lines[] = 'app_pchflags = -Winvalid-pch -include $app_pch_header';
		if ($options['compile_runtime']) {
			$lines[] = 'runtime_pch_header = ' . $runtimePchHeader;
			$lines[] = 'runtime_pchflags = -Winvalid-pch -include $runtime_pch_header';
		}
	}
	$lines[] = '';
	if ($usePch) {
		$lines[] = 'rule compile_pch_app';
		$lines[] = '  command = ' . $wrapNinjaCommand(compiler_invocation_prefix($compiler) . ' $cxx $cxxflags -MMD -MF $out.d -x c++-header $in -o $out');
		$lines[] = '  depfile = $out.d';
		$lines[] = '  deps = gcc';
		$lines[] = '  description = PCH $out';
		$lines[] = '';
		if ($options['compile_runtime']) {
			$lines[] = 'rule compile_pch_runtime';
			$lines[] = '  command = ' . $wrapNinjaCommand(compiler_invocation_prefix($compiler) . ' $cxx $runtime_cxxflags -MMD -MF $out.d -x c++-header $in -o $out');
			$lines[] = '  depfile = $out.d';
			$lines[] = '  deps = gcc';
			$lines[] = '  description = PCH $out';
			$lines[] = '';
		}
	}
	if ($options['compile_runtime']) {
		$lines[] = 'rule compile_runtime_fallback';
		if ($compiler['kind'] === 'msvc') {
			$lines[] = '  command = ' . $wrapNinjaCommand(compiler_invocation_prefix($compiler) . ' $cxx $runtime_cxxflags /c $in /Fo$out');
		} else {
			$lines[] = '  command = ' . $wrapNinjaCommand(compiler_invocation_prefix($compiler) . ' $cxx $runtime_cxxflags' . ($usePch ? ' $runtime_pchflags' : '') . ' -MMD -MF $out.d -c $in -o $out');
			$lines[] = '  depfile = $out.d';
			$lines[] = '  deps = gcc';
		}
		$lines[] = '  description = CXX $out';
		$lines[] = '';
		if ($runtimeBuild['kind'] === 'shared') {
			$lines[] = 'rule compile_runtime';
			$lines[] = '  command = ' . $wrapNinjaCommand(compiler_invocation_prefix($compiler) . ' $cxx $runtime_cxxflags' . ($usePch ? ' $runtime_pchflags' : '') . ' -MMD -MF $out.d -c $in -o $out');
			$lines[] = '  depfile = $out.d';
			$lines[] = '  deps = gcc';
			$lines[] = '  description = CXX $out';
			$lines[] = '';
			$lines[] = 'rule link_runtime_shared';
			$lines[] = '  command = ' . $wrapNinjaCommand(compiler_invocation_prefix($compiler) . ' $cxx $base_ldflags $runtime_ldflags $in -o $out');
			$lines[] = '  description = LINK $out';
			$lines[] = '';
		}
	}
	$lines[] = 'rule compile';
	if ($compiler['kind'] === 'msvc') {
		$lines[] = '  command = ' . $wrapNinjaCommand(compiler_invocation_prefix($compiler) . ' $cxx $cxxflags $more_cxxflags /c $in /Fo$out');
	} else {
		$lines[] = '  command = ' . $wrapNinjaCommand(compiler_invocation_prefix($compiler) . ' $cxx $cxxflags $more_cxxflags' . ($usePch ? ' $app_pchflags' : '') . ' -MMD -MF $out.d -c $in -o $out');
		$lines[] = '  depfile = $out.d';
		$lines[] = '  deps = gcc';
	}
	$lines[] = '  description = CXX $out';
	$lines[] = '';
	$lines[] = 'rule link';
	if ($compiler['kind'] === 'msvc') {
		$lines[] = '  command = ' . $wrapNinjaCommand(compiler_invocation_prefix($compiler) . ' $cxx /nologo $in /Fe$out');
	} else {
		$lines[] = '  command = ' . $wrapNinjaCommand(compiler_invocation_prefix($compiler) . ' $cxx $ldflags $in -o $out');
	}
	$lines[] = '  description = LINK $out';
	$lines[] = '';
	if ($fastcgiBuild !== null) {
		$lines[] = 'rule compile_fcgi';
		$lines[] = '  command = ' . $wrapNinjaCommand(compiler_invocation_prefix($compiler) . ' $cxx $cxxflags $fcgi_cxxflags' . ($usePch ? ' $app_pchflags' : '') . ' -MMD -MF $out.d -c $in -o $out');
		$lines[] = '  depfile = $out.d';
		$lines[] = '  deps = gcc';
		$lines[] = '  description = CXX $out';
		$lines[] = '';
		$lines[] = 'rule link_fcgi';
		$lines[] = '  command = ' . $wrapNinjaCommand(compiler_invocation_prefix($compiler) . ' $cxx $fcgi_ldflags $in -o $out');
		$lines[] = '  description = LINK $out';
		$lines[] = '';
	}

	$objectPaths = [];
	if ($usePch) {
		$lines[] = 'build ' . ninja_escape_path($appPchArtifact) . ': compile_pch_app ' . ninja_escape_path($appPchHeader);
		if ($options['compile_runtime']) {
			$lines[] = 'build ' . ninja_escape_path($runtimePchArtifact) . ': compile_pch_runtime ' . ninja_escape_path($runtimePchHeader);
		}
	}
	foreach ($generatedUnits as $unit) {
		if (!$options['compile_dependencies'] && normalize_path($unit['project_root']) !== normalize_path($projectRoot)) {
			$objectPaths[] = ninja_escape_path(build_ninja_relative_path($projectRoot, $buildDir, $unit['object_path']));
			continue;
		}
		$generatedCpp = build_ninja_relative_path($projectRoot, $buildDir, $unit['generated_cpp']);
		$objectPath = build_ninja_relative_path($projectRoot, $buildDir, $unit['object_path']);
		$implicitDeps = [ninja_escape_path($runtimeSignatureStamp)];
		if ($usePch) {
			$implicitDeps[] = ninja_escape_path($appPchArtifact);
		}
		$lines[] = 'build ' . ninja_escape_path($objectPath) . ': compile ' . ninja_escape_path($generatedCpp) . ' | ' . implode(' ', $implicitDeps);
		$unitForceIncludeHeader = is_string($unit['force_include_header'] ?? null) ? $unit['force_include_header'] : null;
		if ($unitForceIncludeHeader !== null && $unitForceIncludeHeader !== '') {
			$lines[] = '  more_cxxflags = ' . build_force_include_flags($compiler['kind'], [build_ninja_relative_path($projectRoot, $buildDir, $unitForceIncludeHeader)]);
		}
		$objectPaths[] = ninja_escape_path($objectPath);
	}
	foreach ($nativeCppUnits as $nativeUnit) {
		if (!$options['compile_dependencies'] && normalize_path($nativeUnit['project_root']) !== normalize_path($projectRoot)) {
			$objectPaths[] = ninja_escape_path(build_ninja_relative_path($projectRoot, $buildDir, $nativeUnit['object_path']));
			continue;
		}
		$nativeRelative = build_ninja_relative_path($projectRoot, $buildDir, $nativeUnit['source_path']);
		$nativeObject = build_ninja_relative_path($projectRoot, $buildDir, $nativeUnit['object_path']);
		$implicitDeps = [ninja_escape_path($runtimeSignatureStamp)];
		if ($usePch) {
			$implicitDeps[] = ninja_escape_path($appPchArtifact);
		}
		$lines[] = 'build ' . ninja_escape_path($nativeObject) . ': compile ' . ninja_escape_path($nativeRelative) . ' | ' . implode(' ', $implicitDeps);
		$unitForceIncludeHeader = is_string($nativeUnit['force_include_header'] ?? null) ? $nativeUnit['force_include_header'] : null;
		if ($unitForceIncludeHeader !== null && $unitForceIncludeHeader !== '') {
			$lines[] = '  more_cxxflags = ' . build_force_include_flags($compiler['kind'], [build_ninja_relative_path($projectRoot, $buildDir, $unitForceIncludeHeader)]);
		}
		$objectPaths[] = ninja_escape_path($nativeObject);
	}
	if ($options['compile_runtime']) {
		$runtimeArtifactPath = build_ninja_relative_path($projectRoot, $buildDir, $runtimeBuild['artifact_path']);
		if ($runtimeBuild['kind'] === 'shared') {
			$runtimeObjectPath = build_ninja_relative_path($projectRoot, $buildDir, $runtimeBuild['object_path']);
			$runtimeSourcePath = build_ninja_relative_path($projectRoot, $buildDir, $runtimeBuild['source_path']);
			$runtimeImplicitDeps = [ninja_escape_path($runtimeSignatureStamp)];
			if ($usePch) {
				$runtimeImplicitDeps[] = ninja_escape_path($runtimePchArtifact);
			}
			$lines[] = 'build ' . ninja_escape_path($runtimeObjectPath) . ': compile_runtime ' . ninja_escape_path($runtimeSourcePath) . ' | ' . implode(' ', $runtimeImplicitDeps);
			$lines[] = 'build ' . ninja_escape_path($runtimeArtifactPath) . ': link_runtime_shared ' . ninja_escape_path($runtimeObjectPath);
			$objectPaths[] = ninja_escape_path($runtimeArtifactPath);
		} else {
			$runtimeSourcePath = build_ninja_relative_path($projectRoot, $buildDir, $runtimeBuild['source_path']);
			$runtimeImplicitDeps = [ninja_escape_path($runtimeSignatureStamp)];
			if ($usePch) {
				$runtimeImplicitDeps[] = ninja_escape_path($runtimePchArtifact);
			}
			$lines[] = 'build ' . ninja_escape_path($runtimeArtifactPath) . ': compile_runtime_fallback ' . ninja_escape_path($runtimeSourcePath) . ' | ' . implode(' ', $runtimeImplicitDeps);
			$objectPaths[] = ninja_escape_path($runtimeArtifactPath);
		}
	} else {
		$objectPaths[] = ninja_escape_path(build_ninja_relative_path($projectRoot, $buildDir, $runtimeBuild['artifact_path']));
		foreach ($sharedRuntimeModules as $moduleSpec) {
			$objectPaths[] = ninja_escape_path(build_ninja_relative_path($projectRoot, $buildDir, (string) $moduleSpec['artifact_path']));
		}
	}
	$lines[] = '';
	$lines[] = 'build ' . ninja_escape_path($output) . ': link ' . implode(' ', $objectPaths);
	if ($fastcgiBuild !== null) {
		$fcgiObjects = [];
		foreach ($generatedUnits as $unit) {
			if (($unit['is_entrypoint'] ?? false) === true && ($fastcgiBuild['entrypoint_generated_cpp'] ?? '') !== '' && ($fastcgiBuild['entrypoint_object_path'] ?? '') !== '') {
				continue;
			}
			$fcgiObjects[] = ninja_escape_path(build_ninja_relative_path($projectRoot, $buildDir, $unit['object_path']));
		}
		foreach ($nativeCppUnits as $nativeUnit) {
			$fcgiObjects[] = ninja_escape_path(build_ninja_relative_path($projectRoot, $buildDir, $nativeUnit['object_path']));
		}
		if (($fastcgiBuild['entrypoint_generated_cpp'] ?? '') !== '' && ($fastcgiBuild['entrypoint_object_path'] ?? '') !== '') {
			$fcgiGeneratedCpp = build_ninja_relative_path($projectRoot, $buildDir, $fastcgiBuild['entrypoint_generated_cpp']);
			$fcgiGeneratedObject = build_ninja_relative_path($projectRoot, $buildDir, $fastcgiBuild['entrypoint_object_path']);
			$implicitDeps = [ninja_escape_path($runtimeSignatureStamp)];
			if ($usePch) {
				$implicitDeps[] = ninja_escape_path($appPchArtifact);
			}
			$lines[] = 'build ' . ninja_escape_path($fcgiGeneratedObject) . ': compile ' . ninja_escape_path($fcgiGeneratedCpp) . ' | ' . implode(' ', $implicitDeps);
			$fcgiObjects[] = ninja_escape_path($fcgiGeneratedObject);
		}
		$fcgiMainSource = build_ninja_relative_path($projectRoot, $buildDir, $fastcgiBuild['source_path']);
		$fcgiMainObject = build_ninja_relative_path($projectRoot, $buildDir, $fastcgiBuild['main_object_path']);
		$fcgiMainImplicitDeps = [ninja_escape_path($runtimeSignatureStamp)];
		if ($usePch) {
			$fcgiMainImplicitDeps[] = ninja_escape_path($appPchArtifact);
		}
		$lines[] = 'build ' . ninja_escape_path($fcgiMainObject) . ': compile_fcgi ' . ninja_escape_path($fcgiMainSource) . ' | ' . implode(' ', $fcgiMainImplicitDeps);
		$fcgiObjects[] = ninja_escape_path($fcgiMainObject);
		$fcgiObjects[] = ninja_escape_path(build_ninja_relative_path($projectRoot, $buildDir, $runtimeBuild['artifact_path']));
		foreach ($sharedRuntimeModules as $moduleSpec) {
			$fcgiObjects[] = ninja_escape_path(build_ninja_relative_path($projectRoot, $buildDir, (string) $moduleSpec['artifact_path']));
		}
		$lines[] = 'build ' . ninja_escape_path(build_ninja_relative_path($projectRoot, $buildDir, $fastcgiBuild['output_path'])) . ': link_fcgi ' . implode(' ', $fcgiObjects);
	}
	$lines[] = '';
	$defaults = [ninja_escape_path($output)];
	if ($fastcgiBuild !== null) {
		$defaults[] = ninja_escape_path(normalize_config_path(relative_path($projectRoot, $fastcgiBuild['output_path'])));
	}
	$lines[] = 'default ' . implode(' ', $defaults);
	return implode(PHP_EOL, $lines) . PHP_EOL;
}

/** @param array{command:string,kind:string,launcher?:?string} $compiler */
function wrap_windows_gnu_like_ninja_command(array $compiler, string $command): string
{
	if (PHP_OS_FAMILY !== 'Windows' || $compiler['kind'] !== 'gnu_like') {
		return $command;
	}

	$fallbackDir = scpp_pick_windows_safe_temp_dir(scpp_build_process_environment_snapshot());
	if ($fallbackDir === null) {
		return $command;
	}

	$temp = str_replace('/', '\\', $fallbackDir);
	$escapedTemp = str_replace('"', '""', $temp);
	$escapedCommand = str_replace('"', '""', $command);

	return 'cmd /c "set TMP=' . $escapedTemp
		. ' && set TEMP=' . $escapedTemp
		. ' && set TMPDIR=' . $escapedTemp
		. ' && ' . $escapedCommand . '"';
}

function render_runtime_composition_source(array $runtimeConfig): string
{
	$languages = is_array($runtimeConfig['languages'] ?? null) ? $runtimeConfig['languages'] : ['php'];
	$modules = is_array($runtimeConfig['modules'] ?? null) ? $runtimeConfig['modules'] : ['json', 'filesystem', 'datetime'];
	$phpProfile = resolve_php_runtime_profile($runtimeConfig);
	$lines = [
		'#include "core/runtime.cpp"',
	];
	if (in_array('json', $modules, true)) {
		$lines[] = '#include "modules/json/json.cpp"';
	}
	if (in_array('datetime', $modules, true)) {
		$lines[] = '#include "modules/datetime/datetime.cpp"';
	}
	if (in_array('mysqli', $modules, true)) {
		$lines[] = '#include "modules/mysql/mysql_module.cpp"';
	}
	if (in_array('regex', $modules, true)) {
		$lines[] = '#include "modules/regex/regex.cpp"';
	}
	if (in_array('curl', $modules, true)) {
		$lines[] = '#include "modules/curl/curl.cpp"';
	}
	if (in_array('tasks', $modules, true)) {
		$lines[] = '#include "modules/tasks/tasks.cpp"';
	}
	if (in_array('php', $languages, true) && ($phpProfile === 'legacy' || $phpProfile === 'strict')) {
		if (in_array('filesystem', $modules, true)) {
			$lines[] = '#include "lang/php/php_filesystem.cpp"';
		}
		if (in_array('json', $modules, true)) {
			$lines[] = '#include "lang/php/php_json.cpp"';
		}
		if (in_array('datetime', $modules, true)) {
			$lines[] = '#include "lang/php/php_datetime.cpp"';
		}
		if (in_array('mysqli', $modules, true)) {
			$lines[] = '#include "lang/php/php_mysqli.cpp"';
		}
		if (in_array('regex', $modules, true)) {
			$lines[] = '#include "lang/php/php_regex.cpp"';
		}
	}
	return implode(PHP_EOL, $lines) . PHP_EOL;
}

function render_shared_release_base_runtime_composition_source(array $runtimeConfig): string
{
	$languages = is_array($runtimeConfig['languages'] ?? null) ? $runtimeConfig['languages'] : ['php'];
	$modules = is_array($runtimeConfig['modules'] ?? null) ? $runtimeConfig['modules'] : default_runtime_modules();
	$phpProfile = resolve_php_runtime_profile($runtimeConfig);
	$lines = [
		'#include "core/runtime.cpp"',
	];
	if (in_array('json', $modules, true)) {
		$lines[] = '#include "modules/json/json.cpp"';
	}
	if (in_array('datetime', $modules, true)) {
		$lines[] = '#include "modules/datetime/datetime.cpp"';
	}
	if (in_array('php', $languages, true) && ($phpProfile === 'legacy' || $phpProfile === 'strict')) {
		if (in_array('filesystem', $modules, true)) {
			$lines[] = '#include "lang/php/php_filesystem.cpp"';
		}
		if (in_array('json', $modules, true)) {
			$lines[] = '#include "lang/php/php_json.cpp"';
		}
		if (in_array('datetime', $modules, true)) {
			$lines[] = '#include "lang/php/php_datetime.cpp"';
		}
	}
	return implode(PHP_EOL, $lines) . PHP_EOL;
}

function render_shared_release_module_composition_source(array $runtimeConfig, string $moduleName): string
{
	$phpProfile = resolve_php_runtime_profile($runtimeConfig);
	$lines = [];
	if ($moduleName === 'mysqli') {
		$lines[] = '#include "modules/mysql/mysql_module.cpp"';
		if ($phpProfile === 'legacy' || $phpProfile === 'strict') {
			$lines[] = '#include "lang/php/php_mysqli.cpp"';
		}
	} elseif ($moduleName === 'regex') {
		$lines[] = '#include "modules/regex/regex.cpp"';
		if ($phpProfile === 'legacy' || $phpProfile === 'strict') {
			$lines[] = '#include "lang/php/php_regex.cpp"';
		}
	} elseif ($moduleName === 'curl') {
		$lines[] = '#include "modules/curl/curl.cpp"';
	} elseif ($moduleName === 'tasks') {
		$lines[] = '#include "modules/tasks/tasks.cpp"';
	}
	return implode(PHP_EOL, $lines) . PHP_EOL;
}

function resolve_php_runtime_profile(array $runtimeConfig): string
{
	$profiles = is_array($runtimeConfig['language_profiles'] ?? null) ? $runtimeConfig['language_profiles'] : [];
	$phpProfile = $profiles['php']['profile'] ?? 'legacy';
	$normalized = strtolower(trim((string) $phpProfile));
	return in_array($normalized, ['legacy', 'strict'], true) ? $normalized : 'legacy';
}

function resolve_runtime_family(array $runtimeConfig): string
{
	return 'php-' . resolve_php_runtime_profile($runtimeConfig);
}

/** @return list<string> */
function default_runtime_modules(): array
{
	return ['json', 'filesystem', 'datetime'];
}

/** @return list<string> */
function shared_optional_runtime_modules(): array
{
	return ['mysqli', 'regex', 'curl', 'tasks'];
}

function runtime_build_mode_is_shared_release_supported(string $buildMode): bool
{
	return in_array($buildMode, ['debug', 'release'], true);
}

function runtime_family_is_shared_release_supported(string $family): bool
{
	return in_array($family, ['php-legacy', 'php-strict'], true);
}

function runtime_config_uses_default_release_modules(array $runtimeConfig): bool
{
	$languages = array_values(is_array($runtimeConfig['languages'] ?? null) ? $runtimeConfig['languages'] : []);
	sort($languages, SORT_STRING);
	$expectedLanguages = ['php'];
	if ($languages !== $expectedLanguages) {
		return false;
	}

	$modules = array_values(is_array($runtimeConfig['modules'] ?? null) ? $runtimeConfig['modules'] : default_runtime_modules());
	sort($modules, SORT_STRING);
	$expectedModules = default_runtime_modules();
	sort($expectedModules, SORT_STRING);
	return $modules === $expectedModules;
}

function runtime_config_uses_shared_release_module_policy(array $runtimeConfig): bool
{
	$languages = array_values(is_array($runtimeConfig['languages'] ?? null) ? $runtimeConfig['languages'] : []);
	sort($languages, SORT_STRING);
	if ($languages !== ['php']) {
		return false;
	}

	$modules = array_values(is_array($runtimeConfig['modules'] ?? null) ? $runtimeConfig['modules'] : default_runtime_modules());
	sort($modules, SORT_STRING);
	$required = default_runtime_modules();
	sort($required, SORT_STRING);
	foreach ($required as $module) {
		if (!in_array($module, $modules, true)) {
			return false;
		}
	}

	$allowed = array_merge($required, shared_optional_runtime_modules());
	sort($allowed, SORT_STRING);
	foreach ($modules as $module) {
		if (!in_array($module, $allowed, true)) {
			return false;
		}
	}
	return true;
}

function compiler_matches_default_release_compiler(array $compiler): bool
{
	$defaultCompiler = detect_default_compiler();
	if ($defaultCompiler === null) {
		return false;
	}

	return $compiler['command'] === $defaultCompiler['command']
		&& $compiler['kind'] === $defaultCompiler['kind']
		&& ($compiler['linker_flags'] ?? []) === ($defaultCompiler['linker_flags'] ?? []);
}

function runtime_is_shared_release_eligible(array $compiler, string $buildMode, array $runtimeConfig): bool
{
	return compiler_matches_default_release_compiler($compiler)
		&& runtime_build_mode_is_shared_release_supported($buildMode)
		&& runtime_family_is_shared_release_supported(resolve_runtime_family($runtimeConfig))
		&& runtime_config_uses_shared_release_module_policy($runtimeConfig);
}

/** @return array{enabled:bool,cflags:list<string>,ldflags:list<string>,compile_defines:list<string>} */
function resolve_runtime_mysqli_build_spec(): array
{
	$pkgConfig = find_command_path(['pkg-config']);
	if ($pkgConfig === null) {
		return [
			'enabled' => false,
			'cflags' => [],
			'ldflags' => [],
			'compile_defines' => ['-DSCPP_HAS_MYSQLI=0'],
		];
	}
	foreach (['libmariadb', 'mariadb', 'mysqlclient'] as $packageName) {
		$cflagsOutput = shell_exec(escapeshellarg($pkgConfig) . ' --cflags ' . escapeshellarg($packageName) . ' 2>/dev/null');
		$libsOutput = shell_exec(escapeshellarg($pkgConfig) . ' --libs ' . escapeshellarg($packageName) . ' 2>/dev/null');
		if (!is_string($libsOutput) || trim($libsOutput) === '') {
			continue;
		}
		return [
			'enabled' => true,
			'cflags' => is_string($cflagsOutput) ? split_shell_tokens($cflagsOutput) : [],
			'ldflags' => split_shell_tokens($libsOutput),
			'compile_defines' => ['-DSCPP_HAS_MYSQLI=1'],
		];
	}
	return [
		'enabled' => false,
		'cflags' => [],
		'ldflags' => [],
		'compile_defines' => ['-DSCPP_HAS_MYSQLI=0'],
	];
}

/** @return array{enabled:bool,cflags:list<string>,ldflags:list<string>,compile_defines:list<string>} */
function resolve_runtime_regex_build_spec(): array
{
	$pkgConfig = find_command_path(['pkg-config']);
	if ($pkgConfig === null) {
		return [
			'enabled' => false,
			'cflags' => [],
			'ldflags' => [],
			'compile_defines' => ['-DSCPP_HAS_REGEX=0'],
		];
	}

	$cflagsOutput = shell_exec(escapeshellarg($pkgConfig) . ' --cflags libpcre2-8 2>/dev/null');
	$libsOutput = shell_exec(escapeshellarg($pkgConfig) . ' --libs libpcre2-8 2>/dev/null');
	if (!is_string($libsOutput) || trim($libsOutput) === '') {
		return [
			'enabled' => false,
			'cflags' => [],
			'ldflags' => [],
			'compile_defines' => ['-DSCPP_HAS_REGEX=0'],
		];
	}

	return [
		'enabled' => true,
		'cflags' => is_string($cflagsOutput) ? split_shell_tokens($cflagsOutput) : [],
		'ldflags' => split_shell_tokens($libsOutput),
		'compile_defines' => ['-DSCPP_HAS_REGEX=1'],
	];
}

/** @return array{enabled:bool,cflags:list<string>,ldflags:list<string>,compile_defines:list<string>} */
function resolve_runtime_curl_build_spec(): array
{
	$pkgConfig = find_command_path(['pkg-config']);
	if ($pkgConfig !== null) {
		$cflagsOutput = shell_exec(escapeshellarg($pkgConfig) . ' --cflags libcurl 2>/dev/null');
		$libsOutput = shell_exec(escapeshellarg($pkgConfig) . ' --libs libcurl 2>/dev/null');
		if (is_string($libsOutput) && trim($libsOutput) !== '') {
			return [
				'enabled' => true,
				'cflags' => is_string($cflagsOutput) ? split_shell_tokens($cflagsOutput) : [],
				'ldflags' => split_shell_tokens($libsOutput),
				'compile_defines' => ['-DSCPP_HAS_CURL=1'],
			];
		}
	}

	$curlConfig = find_command_path(['curl-config']);
	if ($curlConfig !== null) {
		$cflagsOutput = shell_exec(escapeshellarg($curlConfig) . ' --cflags 2>/dev/null');
		$libsOutput = shell_exec(escapeshellarg($curlConfig) . ' --libs 2>/dev/null');
		if (is_string($libsOutput) && trim($libsOutput) !== '') {
			return [
				'enabled' => true,
				'cflags' => is_string($cflagsOutput) ? split_shell_tokens($cflagsOutput) : [],
				'ldflags' => split_shell_tokens($libsOutput),
				'compile_defines' => ['-DSCPP_HAS_CURL=1'],
			];
		}
	}

	return [
		'enabled' => false,
		'cflags' => [],
		'ldflags' => [],
		'compile_defines' => ['-DSCPP_HAS_CURL=0'],
	];
}

function build_runtime_compiler_flags(string $compilerKind, string $buildMode, string $runtimeIncludeDir): string
{
	if ($compilerKind === 'msvc') {
		$flags = [
			'/nologo',
			'/std:c++latest',
			'/EHsc',
			'/Zc:__cplusplus',
			'/W4',
		];
		if ($buildMode === 'release') {
			$flags[] = '/O2';
			$flags[] = '/DNDEBUG';
		} else {
			$flags[] = '/Od';
			$flags[] = '/Z7';
		}
		$flags[] = '/I' . $runtimeIncludeDir;
		return implode(' ', $flags);
	}

	$flags = [
		'-std=c++23',
		'-fPIC',
	];
	if ($buildMode === 'release') {
		$flags[] = '-O3';
		$flags[] = '-DNDEBUG';
	} else {
		$flags[] = '-O0';
		$flags[] = '-g1';
		$flags[] = '-pipe';
	}
	$flags[] = '-I' . $runtimeIncludeDir;
	return implode(' ', $flags);
}

/**
 * @param array{command:string,kind:string,launcher?:?string,linker_flags?:list<string>,archiver?:?string} $compiler
 * @param 'reuse'|'shared'|'local' $runtimePlacement
 * @return array{kind:string,source_path:string,artifact_path:string,object_path:?string,archiver:?string}
 */
function build_runtime_artifact_spec(string $repoRoot, string $projectRoot, array $compiler, string $buildMode, array $runtimeConfig, string $runtimePlacement = 'reuse'): array
{
	$family = resolve_runtime_family($runtimeConfig);
	$localSignature = compute_runtime_build_signature($repoRoot, $compiler, $buildMode, $runtimeConfig);
	$useSharedReleaseRuntime = $runtimePlacement === 'shared'
		|| ($runtimePlacement === 'reuse' && runtime_is_shared_release_eligible($compiler, $buildMode, $runtimeConfig));
	$runtimeCacheDir = $useSharedReleaseRuntime
		? normalize_path($repoRoot . '/.prism/runtime/release/' . $family . '/' . $buildMode)
		: normalize_path($projectRoot . '/.prism/runtime/project/' . $family . '/' . $localSignature);

	$compositionSource = $runtimeCacheDir . '/runtime_build.cpp';
	$sourcePath = normalize_config_path(relative_path($projectRoot, $compositionSource));
	$modules = is_array($runtimeConfig['modules'] ?? null) ? $runtimeConfig['modules'] : ['json', 'filesystem', 'datetime'];
	$extraCxxFlags = [];
	$extraLinkFlags = [];
	if (in_array('php', is_array($runtimeConfig['languages'] ?? null) ? $runtimeConfig['languages'] : ['php'], true)) {
		$extraCxxFlags[] = '-DSCPP_LANGUAGE_TARGET_PHP=1';
	}
	if (in_array('mysqli', $modules, true)) {
		$mysqliBuild = resolve_runtime_mysqli_build_spec();
		if (!$mysqliBuild['enabled']) {
			scpp_fail('Runtime module `mysqli` is enabled in ' . SCPP_PROJECT_CONFIG . ' but no supported MariaDB/MySQL Connector/C pkg-config package was found (tried: libmariadb, mariadb, mysqlclient).' . PHP_EOL, 1);
		}
		$extraCxxFlags = array_merge($extraCxxFlags, $mysqliBuild['compile_defines'], $mysqliBuild['cflags']);
		$extraLinkFlags = array_merge($extraLinkFlags, $mysqliBuild['ldflags']);
	} else {
		$extraCxxFlags[] = '-DSCPP_HAS_MYSQLI=0';
	}
	if (in_array('regex', $modules, true)) {
		$regexBuild = resolve_runtime_regex_build_spec();
		if (!$regexBuild['enabled']) {
			scpp_fail('Runtime module `regex` is enabled in ' . SCPP_PROJECT_CONFIG . ' but no supported PCRE2 pkg-config package was found (tried: libpcre2-8).' . PHP_EOL, 1);
		}
		$extraCxxFlags = array_merge($extraCxxFlags, $regexBuild['compile_defines'], $regexBuild['cflags']);
		$extraLinkFlags = array_merge($extraLinkFlags, $regexBuild['ldflags']);
	} else {
		$extraCxxFlags[] = '-DSCPP_HAS_REGEX=0';
	}
	if (in_array('curl', $modules, true)) {
		$curlBuild = resolve_runtime_curl_build_spec();
		if (!$curlBuild['enabled']) {
			scpp_fail('Runtime module `curl` is enabled in ' . SCPP_PROJECT_CONFIG . ' but no supported libcurl development environment was found. Tried pkg-config `libcurl` and `curl-config`; install libcurl dev files or disable the module.' . PHP_EOL, 1);
		}
		$extraCxxFlags = array_merge($extraCxxFlags, $curlBuild['compile_defines'], $curlBuild['cflags']);
		$extraLinkFlags = array_merge($extraLinkFlags, $curlBuild['ldflags']);
	} else {
		$extraCxxFlags[] = '-DSCPP_HAS_CURL=0';
	}
	if (in_array('tasks', $modules, true)) {
		$extraCxxFlags[] = '-DSCPP_HAS_TASKS=1';
	} else {
		$extraCxxFlags[] = '-DSCPP_HAS_TASKS=0';
	}

	if ($compiler['kind'] === 'gnu_like') {
		$libraryName = PHP_OS_FAMILY === 'Darwin' ? 'libruntime.dylib' : 'libruntime.so';
		$linkFlags = ['-shared'];
		if (PHP_OS_FAMILY === 'Darwin') {
			$linkFlags[] = '-Wl,-install_name,@rpath/' . $libraryName;
		} elseif (PHP_OS_FAMILY === 'Linux') {
			$linkFlags[] = '-Wl,-soname,' . $libraryName;
		}
		$linkFlags = array_merge($linkFlags, $extraLinkFlags);

		return [
			'kind' => 'shared',
			'source_path' => $sourcePath,
			'artifact_path' => normalize_config_path(relative_path($projectRoot, $runtimeCacheDir . '/' . $libraryName)),
			'object_path' => normalize_config_path(relative_path($projectRoot, $runtimeCacheDir . '/runtime.o')),
			'archiver' => null,
			'link_flags' => $linkFlags,
			'rpath_dir' => $runtimeCacheDir,
			'extra_cxxflags' => $extraCxxFlags,
		];
	}

	return [
		'kind' => 'object',
		'source_path' => $sourcePath,
		'artifact_path' => normalize_config_path(relative_path($projectRoot, $runtimeCacheDir . '/runtime.' . object_extension($compiler['kind']))),
		'object_path' => null,
		'archiver' => null,
		'link_flags' => $extraLinkFlags,
		'rpath_dir' => null,
		'extra_cxxflags' => $extraCxxFlags,
	];
}

/** @return list<string> */
function resolve_requested_shared_runtime_modules(array $runtimeConfig): array
{
	$modules = array_values(is_array($runtimeConfig['modules'] ?? null) ? $runtimeConfig['modules'] : default_runtime_modules());
	$requested = [];
	foreach (shared_optional_runtime_modules() as $moduleName) {
		if (in_array($moduleName, $modules, true)) {
			$requested[] = $moduleName;
		}
	}
	return $requested;
}

/**
 * @param array{command:string,kind:string,launcher?:?string,linker_flags?:list<string>,archiver?:?string} $compiler
 * @return array{kind:string,module_name:string,source_path:string,artifact_path:string,object_path:?string,archiver:?string,link_flags?:list<string>,rpath_dir:?string,extra_cxxflags?:list<string>}
 */
function build_runtime_module_artifact_spec(string $repoRoot, string $projectRoot, array $compiler, string $buildMode, array $runtimeConfig, string $moduleName): array
{
	$family = resolve_runtime_family($runtimeConfig);
	$runtimeCacheDir = normalize_path($repoRoot . '/.prism/runtime/release/' . $family . '/' . $buildMode . '/modules/' . $moduleName);
	$compositionSource = $runtimeCacheDir . '/runtime_module_' . $moduleName . '.cpp';
	$sourcePath = normalize_config_path(relative_path($projectRoot, $compositionSource));
	$extraCxxFlags = [];
	$extraLinkFlags = [];
	if (in_array('php', is_array($runtimeConfig['languages'] ?? null) ? $runtimeConfig['languages'] : ['php'], true)) {
		$extraCxxFlags[] = '-DSCPP_LANGUAGE_TARGET_PHP=1';
	}
	if ($moduleName === 'mysqli') {
		$mysqliBuild = resolve_runtime_mysqli_build_spec();
		if (!$mysqliBuild['enabled']) {
			scpp_fail('Runtime module `mysqli` is enabled in ' . SCPP_PROJECT_CONFIG . ' but no supported MariaDB/MySQL Connector/C pkg-config package was found (tried: libmariadb, mariadb, mysqlclient).' . PHP_EOL, 1);
		}
		$extraCxxFlags = array_merge($extraCxxFlags, $mysqliBuild['compile_defines'], $mysqliBuild['cflags']);
		$extraLinkFlags = array_merge($extraLinkFlags, $mysqliBuild['ldflags']);
	} elseif ($moduleName === 'regex') {
		$regexBuild = resolve_runtime_regex_build_spec();
		if (!$regexBuild['enabled']) {
			scpp_fail('Runtime module `regex` is enabled in ' . SCPP_PROJECT_CONFIG . ' but no supported PCRE2 pkg-config package was found (tried: libpcre2-8).' . PHP_EOL, 1);
		}
		$extraCxxFlags = array_merge($extraCxxFlags, $regexBuild['compile_defines'], $regexBuild['cflags']);
		$extraLinkFlags = array_merge($extraLinkFlags, $regexBuild['ldflags']);
	} elseif ($moduleName === 'curl') {
		$curlBuild = resolve_runtime_curl_build_spec();
		if (!$curlBuild['enabled']) {
			scpp_fail('Runtime module `curl` is enabled in ' . SCPP_PROJECT_CONFIG . ' but no supported libcurl development environment was found. Tried pkg-config `libcurl` and `curl-config`; install libcurl dev files or disable the module.' . PHP_EOL, 1);
		}
		$extraCxxFlags = array_merge($extraCxxFlags, $curlBuild['compile_defines'], $curlBuild['cflags']);
		$extraLinkFlags = array_merge($extraLinkFlags, $curlBuild['ldflags']);
	} elseif ($moduleName === 'tasks') {
		$extraCxxFlags[] = '-DSCPP_HAS_TASKS=1';
	}

	if ($compiler['kind'] === 'gnu_like') {
		$libraryName = 'libruntime_module_' . $moduleName . (PHP_OS_FAMILY === 'Darwin' ? '.dylib' : '.so');
		$linkFlags = ['-shared'];
		if (PHP_OS_FAMILY === 'Darwin') {
			$linkFlags[] = '-Wl,-install_name,@rpath/' . $libraryName;
		} elseif (PHP_OS_FAMILY === 'Linux') {
			$linkFlags[] = '-Wl,-soname,' . $libraryName;
		}
		$linkFlags = array_merge($linkFlags, $extraLinkFlags);
		return [
			'kind' => 'shared',
			'module_name' => $moduleName,
			'source_path' => $sourcePath,
			'artifact_path' => normalize_config_path(relative_path($projectRoot, $runtimeCacheDir . '/' . $libraryName)),
			'object_path' => normalize_config_path(relative_path($projectRoot, $runtimeCacheDir . '/runtime_module_' . $moduleName . '.o')),
			'archiver' => null,
			'link_flags' => $linkFlags,
			'rpath_dir' => $runtimeCacheDir,
			'extra_cxxflags' => $extraCxxFlags,
		];
	}

	return [
		'kind' => 'object',
		'module_name' => $moduleName,
		'source_path' => $sourcePath,
		'artifact_path' => normalize_config_path(relative_path($projectRoot, $runtimeCacheDir . '/runtime_module_' . $moduleName . '.' . object_extension($compiler['kind']))),
		'object_path' => null,
		'archiver' => null,
		'link_flags' => $extraLinkFlags,
		'rpath_dir' => null,
		'extra_cxxflags' => $extraCxxFlags,
	];
}

/**
 * @param array{command:string,kind:string,launcher?:?string,linker_flags?:list<string>,archiver?:?string} $compiler
 */
function compute_runtime_build_signature(string $repoRoot, array $compiler, string $buildMode, array $runtimeConfig): string
{
	$parts = [
		'runtime-v4',
		'kind:' . $compiler['kind'],
		'command:' . $compiler['command'],
		'mode:' . $buildMode,
		'launcher:' . (is_string($compiler['launcher'] ?? null) ? basename(str_replace('\\', '/', $compiler['launcher'])) : ''),
		'archiver:' . (is_string($compiler['archiver'] ?? null) ? basename(str_replace('\\', '/', $compiler['archiver'])) : ''),
		'linker_flags:' . implode(' ', is_array($compiler['linker_flags'] ?? null) ? $compiler['linker_flags'] : []),
		'runtime_languages:' . implode(',', is_array($runtimeConfig['languages'] ?? null) ? $runtimeConfig['languages'] : []),
		'php_profile:' . resolve_php_runtime_profile($runtimeConfig),
		'runtime_modules:' . implode(',', is_array($runtimeConfig['modules'] ?? null) ? $runtimeConfig['modules'] : []),
	];

	sort($parts, SORT_STRING);
	return substr(hash('sha256', implode("\n", $parts)), 0, 16);
}

/**
 * @return array{base:array<string,mixed>,modules:list<array<string,mixed>>}
 */
function resolve_shared_runtime_bundle_specs(string $repoRoot, string $projectRoot, array $compiler, string $buildMode, array $runtimeConfig): array
{
	$baseConfig = $runtimeConfig;
	$baseConfig['modules'] = default_runtime_modules();
	$base = build_runtime_artifact_spec($repoRoot, $projectRoot, $compiler, $buildMode, $baseConfig, 'shared');
	$modules = [];
	foreach (resolve_requested_shared_runtime_modules($runtimeConfig) as $moduleName) {
		$modules[] = build_runtime_module_artifact_spec($repoRoot, $projectRoot, $compiler, $buildMode, $runtimeConfig, $moduleName);
	}
	return [
		'base' => $base,
		'modules' => $modules,
	];
}

/**
 * @param array<string,mixed> $artifactSpec
 */
function scpp_compile_runtime_artifact_spec(string $repoRoot, string $projectRoot, array $compiler, string $buildMode, array $artifactSpec, string $sourceContents, bool $force): string
{
	$artifactPath = normalize_path($projectRoot . '/' . normalize_config_path((string) $artifactSpec['artifact_path']));
	$objectPath = is_string($artifactSpec['object_path'] ?? null) && $artifactSpec['object_path'] !== ''
		? normalize_path($projectRoot . '/' . normalize_config_path((string) $artifactSpec['object_path']))
		: null;
	$sourcePath = normalize_path($projectRoot . '/' . normalize_config_path((string) $artifactSpec['source_path']));
	$lockPath = $artifactPath . '.lock';
	$runtimeCacheDir = dirname($artifactPath);
	ensure_directory($runtimeCacheDir);
	write_text_file($sourcePath, $sourceContents);
	$lockHandle = fopen($lockPath, 'c+');
	if ($lockHandle === false) {
		scpp_fail('Failed to create runtime build lock: ' . $lockPath . PHP_EOL, 2);
	}

	try {
		if (!flock($lockHandle, LOCK_EX)) {
			scpp_fail('Failed to lock runtime build: ' . $lockPath . PHP_EOL, 2);
		}

		if (!$force && is_file($artifactPath)) {
			echo 'Runtime artifact already up to date: ' . normalize_config_path(relative_path($projectRoot, $artifactPath)) . PHP_EOL;
			return normalize_config_path(relative_path($projectRoot, $artifactPath));
		}

		if ($force) {
			delete_file_if_exists($artifactPath);
			if (is_string($objectPath)) {
				delete_file_if_exists($objectPath);
			}
		}

		$compileFlags = split_shell_tokens(build_runtime_compiler_flags($compiler['kind'], $buildMode, normalize_path($repoRoot . '/runtime/include')));
		$extraCxxFlags = is_array($artifactSpec['extra_cxxflags'] ?? null) ? $artifactSpec['extra_cxxflags'] : [];
		$compileCommand = array_merge(
			scpp_compiler_command_prefix($compiler),
			[$compiler['command']],
			$compileFlags,
			$extraCxxFlags
		);

		if ($compiler['kind'] === 'gnu_like' && is_string($objectPath)) {
			$tmpObjectPath = $objectPath . '.tmp.' . bin2hex(random_bytes(4));
			$compileCommand = array_merge($compileCommand, ['-c', $sourcePath, '-o', $tmpObjectPath]);
			scpp_run_or_fail_process($compileCommand, $projectRoot, 'Failed to compile runtime object.');
			replace_file_atomically($tmpObjectPath, $objectPath, 'Failed to publish runtime object');

			$linkFlags = is_array($compiler['linker_flags'] ?? null) ? $compiler['linker_flags'] : [];
			$runtimeLinkFlags = is_array($artifactSpec['link_flags'] ?? null) ? $artifactSpec['link_flags'] : [];
			$tmpArtifactPath = $artifactPath . '.tmp.' . bin2hex(random_bytes(4));
			$linkOutputPath = $tmpArtifactPath;
			$publishLinkedArtifactAtomically = true;
			// On Windows, PE import tables record the output DLL basename. Linking the
			// shared runtime to a random temp filename causes executables to depend on
			// that temp name permanently, so emit the stable final DLL name directly.
			if (PHP_OS_FAMILY === 'Windows' && (($artifactSpec['kind'] ?? '') === 'shared')) {
				$linkOutputPath = $artifactPath;
				$publishLinkedArtifactAtomically = false;
				delete_file_if_exists($artifactPath);
			}
			$linkCommand = array_merge(
				scpp_compiler_command_prefix($compiler),
				[$compiler['command']],
				$linkFlags,
				$runtimeLinkFlags,
				[$objectPath, '-o', $linkOutputPath]
			);
			scpp_run_or_fail_process($linkCommand, $projectRoot, 'Failed to link runtime artifact.');
			if ($publishLinkedArtifactAtomically) {
				replace_file_atomically($tmpArtifactPath, $artifactPath, 'Failed to publish runtime artifact');
			}
		} else {
			$tmpArtifactPath = $artifactPath . '.tmp.' . bin2hex(random_bytes(4));
			$compileCommand = array_merge($compileCommand, ['-c', $sourcePath, '-o', $tmpArtifactPath]);
			scpp_run_or_fail_process($compileCommand, $projectRoot, 'Failed to compile runtime artifact.');
			replace_file_atomically($tmpArtifactPath, $artifactPath, 'Failed to publish runtime artifact');
		}
	} finally {
		flock($lockHandle, LOCK_UN);
		fclose($lockHandle);
	}

	return normalize_config_path(relative_path($projectRoot, $artifactPath));
}

/** @return list<array{artifact_path:string,build_mode:string,runtime_family:string}> */
function scpp_build_default_runtime_matrix(string $repoRoot, bool $force = false): array
{
	$results = [];
	foreach (['legacy', 'strict'] as $profile) {
		foreach (['debug', 'release'] as $buildMode) {
			$runtimeConfig = [
				'languages' => ['php'],
				'language_profiles' => [
					'php' => ['profile' => $profile],
				],
				'modules' => array_merge(default_runtime_modules(), shared_optional_runtime_modules()),
			];
			$results[] = scpp_build_runtime_from_config($repoRoot, ['runtime' => $runtimeConfig], $repoRoot, $buildMode, $force, 'shared');
		}
	}
	return $results;
}

/**
 * @param ?array<string,mixed> $config
 * @param 'reuse'|'shared'|'local' $runtimePlacement
 * @return array{artifact_path:string,build_mode:string,runtime_family:string}
 */
function scpp_build_runtime_from_config(string $repoRoot, ?array $config, string $projectRoot, string $buildMode = 'debug', bool $force = false, string $runtimePlacement = 'local'): array
{
	$repoRoot = normalize_path($repoRoot);
	$projectRoot = normalize_path($projectRoot);
	$config = is_array($config) ? $config : [];
	$compiler = resolve_compiler($config);
	if ($compiler === null) {
		scpp_fail("No supported C++ compiler found.\n" . install_hint_for_compiler() . PHP_EOL, 1);
	}
	$runtimeConfig = resolve_runtime_build_config($config);
	if ($runtimePlacement === 'shared') {
		$bundle = resolve_shared_runtime_bundle_specs($repoRoot, $projectRoot, $compiler, $buildMode, $runtimeConfig);
		$artifactPath = scpp_compile_runtime_artifact_spec($repoRoot, $projectRoot, $compiler, $buildMode, $bundle['base'], render_shared_release_base_runtime_composition_source($runtimeConfig), $force);
		foreach ($bundle['modules'] as $moduleSpec) {
			scpp_compile_runtime_artifact_spec($repoRoot, $projectRoot, $compiler, $buildMode, $moduleSpec, render_shared_release_module_composition_source($runtimeConfig, (string) $moduleSpec['module_name']), $force);
		}
	} else {
		$runtimeBuild = build_runtime_artifact_spec($repoRoot, $projectRoot, $compiler, $buildMode, $runtimeConfig, $runtimePlacement);
		$artifactPath = scpp_compile_runtime_artifact_spec($repoRoot, $projectRoot, $compiler, $buildMode, $runtimeBuild, render_runtime_composition_source($runtimeConfig), $force);
	}

	return [
		'artifact_path' => $artifactPath,
		'build_mode' => $buildMode,
		'runtime_family' => resolve_runtime_family($runtimeConfig),
	];
}

/** @return list<string> */
function scpp_compiler_command_prefix(array $compiler): array
{
	$launcher = $compiler['launcher'] ?? null;
	if (!is_string($launcher) || $launcher === '') {
		return [];
	}
	return [$launcher];
}

/** @param list<string> $command */
function scpp_run_or_fail_process(array $command, string $cwd, string $failureMessage): void
{
	$descriptor = [
		0 => ['pipe', 'r'],
		1 => ['pipe', 'w'],
		2 => ['pipe', 'w'],
	];
	$process = proc_open($command, $descriptor, $pipes, $cwd, scpp_build_process_environment());
	if (!is_resource($process)) {
		scpp_fail($failureMessage . PHP_EOL, 4);
	}
	fclose($pipes[0]);
	$stdout = stream_get_contents($pipes[1]);
	$stderr = stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);
	$status = proc_close($process);
	if ($status !== 0) {
		$message = trim((is_string($stderr) ? $stderr : '') . PHP_EOL . (is_string($stdout) ? $stdout : ''));
		if ($message === '') {
			$message = 'Process exited with status ' . $status . '.';
		}
		scpp_fail($failureMessage . PHP_EOL . $message . PHP_EOL, is_int($status) ? $status : 1);
	}
}

/** @param list<string> $forceIncludeHeaders */
function build_compiler_flags(string $compilerKind, string $buildMode, string $runtimeIncludeDir, string $generatedIncludeDir, array $forceIncludeHeaders = []): string
{
	if ($compilerKind === 'msvc') {
		$flags = [
			'/nologo',
			'/std:c++latest',
			'/EHsc',
			'/Zc:__cplusplus',
			'/W4',
		];
		if ($buildMode === 'release') {
			$flags[] = '/O2';
			$flags[] = '/DNDEBUG';
		} else {
			$flags[] = '/Od';
			$flags[] = '/Z7';
		}
		$flags[] = '/I' . $runtimeIncludeDir;
		$flags[] = '/I' . $generatedIncludeDir;
		foreach ($forceIncludeHeaders as $header) {
			$flags[] = '/FI' . $header;
		}
		return implode(' ', $flags);
	}

	$flags = [
		'-std=c++23',
		'-fPIC',
	];
	if ($buildMode === 'release') {
		$flags[] = '-O3';
		$flags[] = '-DNDEBUG';
	} else {
		$flags[] = '-O0';
		$flags[] = '-g1';
		$flags[] = '-pipe';
	}
	$flags[] = '-I' . $runtimeIncludeDir;
	$flags[] = '-I' . $generatedIncludeDir;
	$forceIncludeFlags = build_force_include_flags($compilerKind, $forceIncludeHeaders);
	if ($forceIncludeFlags !== '') {
		$flags[] = $forceIncludeFlags;
	}
	return implode(' ', $flags);
}

/** @param list<string> $forceIncludeHeaders */
function build_force_include_flags(string $compilerKind, array $forceIncludeHeaders): string
{
	if ($forceIncludeHeaders === []) {
		return '';
	}
	$flags = [];
	foreach ($forceIncludeHeaders as $header) {
		if ($header === '') {
			continue;
		}
		if ($compilerKind === 'msvc') {
			$flags[] = '/FI' . $header;
			continue;
		}
		$flags[] = '-include';
		$flags[] = $header;
	}
	return implode(' ', $flags);
}

/** @param array{command:string,kind:string,launcher?:?string} $compiler */
function compiler_invocation_prefix(array $compiler): string
{
	$launcher = $compiler['launcher'] ?? null;
	if (!is_string($launcher) || $launcher === '') {
		return '';
	}

	return '$cxx_launcher ';
}

/** @return array<string,string> */
function scpp_build_process_environment(array $extra = []): array
{
	$env = [];
	foreach ([getenv(), $_ENV, $_SERVER] as $source) {
		if (!is_array($source)) {
			continue;
		}
		foreach ($source as $key => $value) {
			if (!is_string($key) || $key == '') {
				continue;
			}
			if (is_array($value) || is_object($value) || $value === null) {
				continue;
			}
			$env[$key] = (string) $value;
		}
	}
	foreach ($extra as $key => $value) {
		if (!is_string($key) || $key == '') {
			continue;
		}
		if (is_array($value) || is_object($value) || $value === null) {
			continue;
		}
		$env[$key] = (string) $value;
	}
	$env['PATH'] = scpp_effective_path_env($env['PATH'] ?? null);
	$env = scpp_normalize_windows_temp_environment($env);
	return $env;
}

function scpp_effective_path_env(?string $pathEnv = null): string
{
	$pathEnv = is_string($pathEnv) ? trim($pathEnv) : '';
	$dirs = [];
	if ($pathEnv !== '') {
		$dirs = array_filter(explode(PATH_SEPARATOR, $pathEnv), static fn (string $dir): bool => $dir !== '');
	}
	if (PHP_OS_FAMILY !== 'Windows') {
		$dirs = array_merge($dirs, [
			'/usr/local/sbin',
			'/usr/local/bin',
			'/usr/sbin',
			'/usr/bin',
			'/sbin',
			'/bin',
			'/usr/games',
			'/usr/local/games',
			'/snap/bin',
		]);
	}
	$normalized = [];
	foreach ($dirs as $dir) {
		$dir = trim($dir);
		if ($dir === '') {
			continue;
		}
		$normalized[] = rtrim($dir, "\/");
	}
	$normalized = array_values(array_unique($normalized));
	return implode(PATH_SEPARATOR, $normalized);
}

/** @return list<string> */
function scpp_doctor_warnings(?string $pathEnv = null, ?string $osFamily = null): array
{
	$warnings = [];
	$windowsMsysWarning = scpp_detect_windows_msys2_path_warning($pathEnv, $osFamily);
	if ($windowsMsysWarning !== null) {
		$warnings[] = $windowsMsysWarning;
	}
	$windowsTempWarning = scpp_detect_windows_temp_directory_warning(null, $osFamily);
	if ($windowsTempWarning !== null) {
		$warnings[] = $windowsTempWarning;
	}
	return $warnings;
}

function scpp_detect_windows_msys2_path_warning(?string $pathEnv = null, ?string $osFamily = null): ?string
{
	$osFamily = is_string($osFamily) && $osFamily !== '' ? $osFamily : PHP_OS_FAMILY;
	if ($osFamily !== 'Windows') {
		return null;
	}

	$separator = $osFamily === 'Windows' ? ';' : PATH_SEPARATOR;
	$pathEnv = is_string($pathEnv) && $pathEnv !== ''
		? trim($pathEnv)
		: scpp_effective_path_env(getenv('PATH') === false ? null : (string) getenv('PATH'));
	if ($pathEnv === '') {
		return null;
	}

	$dirs = array_values(array_filter(explode($separator, $pathEnv), static fn (string $dir): bool => $dir !== ''));
	$hasMsysMingw64Bin = false;
	$hasMsysUsrBin = false;

	foreach ($dirs as $dir) {
		$normalized = strtolower(rtrim(normalize_path($dir), '/'));
		if ($normalized === 'c:/msys64/mingw64/bin') {
			$hasMsysMingw64Bin = true;
			continue;
		}
		if ($normalized === 'c:/msys64/usr/bin') {
			$hasMsysUsrBin = true;
		}
	}

	if (!$hasMsysMingw64Bin || $hasMsysUsrBin) {
		return null;
	}

	return 'Windows PATH contains C:/msys64/mingw64/bin without C:/msys64/usr/bin; MinGW compiler frontends can fail to start with entry-point errors such as cc1plus.exe. Add C:/msys64/usr/bin before C:/msys64/mingw64/bin.';
}

/** @param array<string,string>|null $env */
function scpp_detect_windows_temp_directory_warning(?array $env = null, ?string $osFamily = null): ?string
{
	$status = scpp_windows_temp_directory_status($env, $osFamily);
	if ($status === null || $status['warning'] === null) {
		return null;
	}
	return $status['warning'];
}

/** @param array<string,string> $env
 *  @return array<string,string>
 */
function scpp_normalize_windows_temp_environment(array $env, ?string $osFamily = null): array
{
	$status = scpp_windows_temp_directory_status($env, $osFamily);
	if ($status === null) {
		return $env;
	}
	$forceMsysFallback = scpp_windows_shell_requires_temp_fallback($env, $osFamily);
	if ($status['warning'] === null && !$forceMsysFallback) {
		return $env;
	}
	if ($status['fallback_dir'] === null) {
		return $env;
	}
	$env['TMP'] = $status['fallback_dir'];
	$env['TEMP'] = $status['fallback_dir'];
	return $env;
}

/** @param array<string,string>|null $env
 *  @return array{effective_dir:string|null,warning:string|null,fallback_dir:string|null}|null
 */
function scpp_windows_temp_directory_status(?array $env = null, ?string $osFamily = null): ?array
{
	$osFamily = is_string($osFamily) && $osFamily !== '' ? $osFamily : PHP_OS_FAMILY;
	if ($osFamily !== 'Windows') {
		return null;
	}

	$env = is_array($env) ? $env : scpp_build_process_environment_snapshot();
	$candidates = [];
	foreach (['TMP', 'TEMP', 'TMPDIR'] as $key) {
		$value = trim((string) ($env[$key] ?? ''));
		if ($value !== '') {
			$candidates[] = $value;
		}
	}
	$effectiveDir = $candidates[0] ?? trim((string) sys_get_temp_dir());
	$effectiveDir = $effectiveDir !== '' ? rtrim(normalize_path($effectiveDir), '/') : null;
	if ($effectiveDir === null || $effectiveDir === '') {
		$effectiveDir = null;
	}

	$problem = null;
	if ($effectiveDir === null) {
		$problem = 'Windows temp directory is unset.';
	} elseif (!is_dir($effectiveDir)) {
		$problem = 'Windows temp directory does not exist: ' . $effectiveDir . '.';
	} elseif (!is_writable($effectiveDir)) {
		$problem = 'Windows temp directory is not writable: ' . $effectiveDir . '.';
	} elseif (preg_match('#^c:/windows(?:/|$)#i', $effectiveDir) === 1) {
		$problem = 'Windows temp directory resolves under C:/Windows: ' . $effectiveDir . '.';
	}

	if ($problem === null) {
		return [
			'effective_dir' => $effectiveDir,
			'warning' => null,
			'fallback_dir' => null,
		];
	}

	$fallbackDir = scpp_pick_windows_safe_temp_dir($env);
	$warning = $problem
		. ' MinGW compiler frontends can fail with errors such as "Cannot create temporary file in C:\\WINDOWS".';
	if ($fallbackDir !== null) {
		$warning .= ' scpp will use ' . $fallbackDir . ' for child TMP/TEMP.';
	} else {
		$warning .= ' Configure TMP/TEMP to a writable user temp directory.';
	}

	return [
		'effective_dir' => $effectiveDir,
		'warning' => $warning,
		'fallback_dir' => $fallbackDir,
	];
}

/** @param array<string,string> $env */
function scpp_windows_shell_requires_temp_fallback(array $env, ?string $osFamily = null): bool
{
	$osFamily = is_string($osFamily) && $osFamily !== '' ? $osFamily : PHP_OS_FAMILY;
	if ($osFamily !== 'Windows') {
		return false;
	}

	$msystem = strtoupper(trim((string) ($env['MSYSTEM'] ?? '')));
	if ($msystem !== '') {
		return true;
	}

	$shell = strtolower(normalize_path((string) ($env['SHELL'] ?? '')));
	if ($shell !== '' && str_ends_with($shell, '/bash.exe')) {
		return true;
	}

	$termProgram = strtolower(trim((string) ($env['TERM_PROGRAM'] ?? '')));
	if (str_contains($termProgram, 'git')) {
		return true;
	}

	return false;
}

/** @return array<string,string> */
function scpp_build_process_environment_snapshot(): array
{
	$env = [];
	foreach ([getenv(), $_ENV, $_SERVER] as $source) {
		if (!is_array($source)) {
			continue;
		}
		foreach ($source as $key => $value) {
			if (!is_string($key) || $key === '') {
				continue;
			}
			if (is_array($value) || is_object($value) || $value === null) {
				continue;
			}
			$env[$key] = (string) $value;
		}
	}
	return $env;
}

/** @param array<string,string> $env */
function scpp_pick_windows_safe_temp_dir(array $env): ?string
{
	$candidates = [];

	$localAppData = trim((string) ($env['LOCALAPPDATA'] ?? getenv('LOCALAPPDATA') ?: ''));
	if ($localAppData !== '') {
		$candidates[] = normalize_path($localAppData . '/Temp');
	}

	$userProfile = trim((string) ($env['USERPROFILE'] ?? getenv('USERPROFILE') ?: ''));
	if ($userProfile !== '') {
		$candidates[] = normalize_path($userProfile . '/AppData/Local/Temp');
	}

	$repoRoot = resolve_repo_root();
	$candidates[] = normalize_path($repoRoot . '/.prism/tmp/windows');

	foreach ($candidates as $candidate) {
		if ($candidate === '') {
			continue;
		}
		if (!is_dir($candidate) && !@mkdir($candidate, 0777, true) && !is_dir($candidate)) {
			continue;
		}
		if (is_writable($candidate)) {
			return $candidate;
		}
	}

	return null;
}

function object_extension(string $kind): string
{
	return $kind === 'msvc' ? 'obj' : 'o';
}

/** @param list<string> $commands */
function find_command_path(array $commands): ?string
{
	$pathEnv = scpp_effective_path_env(getenv('PATH') === false ? null : (string) getenv('PATH'));
	if ($pathEnv === '') {
		return null;
	}

	$dirs = array_filter(explode(PATH_SEPARATOR, $pathEnv), static fn ($dir): bool => $dir !== '');
	$extensions = [''];
	if (PHP_OS_FAMILY === 'Windows') {
		$pathext = getenv('PATHEXT');
		$extensions = $pathext === false || $pathext === ''
			? ['.exe', '.cmd', '.bat', '.com', '']
			: array_merge(explode(';', strtolower($pathext)), ['']);
		$extensions = array_values(array_unique($extensions));
	}

	foreach ($commands as $command) {
		if ($command === '') {
			continue;
		}

		if (preg_match('#[\\/]#', $command)) {
			if (is_executable($command) || is_file($command)) {
				return normalize_path($command);
			}
			continue;
		}

		foreach ($dirs as $dir) {
			$dir = rtrim($dir, "\\/");
			foreach ($extensions as $extension) {
				$candidate = $dir . DIRECTORY_SEPARATOR . $command;
				if ($extension !== '' && !str_ends_with(strtolower($candidate), $extension)) {
					$candidate .= $extension;
				}
				if (is_file($candidate)) {
					return normalize_path($candidate);
				}
			}
		}
	}

	return null;
}

function normalize_path(string $path): string
{
	$path = str_replace('\\', '/', $path);
	$parts = [];
	$prefix = '';

	if (preg_match('#^[A-Za-z]:#', $path) === 1) {
		$prefix = substr($path, 0, 2);
		$path = substr($path, 2);
	}

	$absolute = str_starts_with($path, '/');
	foreach (explode('/', $path) as $part) {
		if ($part === '' || $part === '.') {
			continue;
		}
		if ($part === '..') {
			if ($parts !== [] && end($parts) !== '..') {
				array_pop($parts);
			} elseif (!$absolute) {
				$parts[] = '..';
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
		$result = $prefix . ($result === '' || $result[0] !== '/' ? '/' : '') . $result;
	}
	if ($result === '') {
		return $absolute ? '/' : ($prefix !== '' ? $prefix . '/' : '.');
	}
	return $result;
}

function path_is_inside(string $parent, string $child): bool
{
	$parent = rtrim(normalize_path($parent), '/');
	$child = normalize_path($child);
	if ($parent === '') {
		return false;
	}
	return str_starts_with($child, $parent . '/');
}

function is_absolute_path(string $path): bool
{
	$trimmed = trim($path);
	if ($trimmed === '') {
		return false;
	}
	$normalized = str_replace('\\', '/', $trimmed);
	return str_starts_with($normalized, '/')
		|| preg_match('#^[A-Za-z]:/#', $normalized) === 1
		|| str_starts_with($normalized, '//');
}

function normalize_config_path(string $path): string
{
	return ltrim(str_replace('\\', '/', trim($path)), '/');
}

function ninja_escape_path(string $path): string
{
	// Ninja uses ':' as a separator in build/default statements.
	// Windows absolute paths contain a drive-letter colon (e.g., "D:/foo")
	// which must be escaped as "D$:/foo".
	return preg_replace('/^([A-Za-z]):/', '$1$:', $path);
}

function relative_path(string $from, string $to): string
{
	$from = normalize_path($from);
	$to = normalize_path($to);

	$fromParts = array_values(array_filter(explode('/', trim($from, '/')), static fn ($part): bool => $part !== ''));
	$toParts = array_values(array_filter(explode('/', trim($to, '/')), static fn ($part): bool => $part !== ''));

	if (preg_match('#^[A-Za-z]:#', $from) === 1 || preg_match('#^[A-Za-z]:#', $to) === 1) {
		$fromDrive = strtoupper(substr($from, 0, 2));
		$toDrive = strtoupper(substr($to, 0, 2));
		if ($fromDrive !== $toDrive) {
			return $to;
		}
		if ($fromParts !== []) {
			$fromParts[0] = preg_replace('#^[A-Za-z]:#', '', $fromParts[0]) ?? $fromParts[0];
		}
		if ($toParts !== []) {
			$toParts[0] = preg_replace('#^[A-Za-z]:#', '', $toParts[0]) ?? $toParts[0];
		}
	}

	$length = min(count($fromParts), count($toParts));
	$common = 0;
	while ($common < $length && $fromParts[$common] === $toParts[$common]) {
		$common++;
	}

	$up = array_fill(0, count($fromParts) - $common, '..');
	$down = array_slice($toParts, $common);
	$relative = implode('/', array_merge($up, $down));
	return $relative === '' ? '.' : $relative;
}

function relative_or_absolute(string $projectRoot, string $path): string
{
	$relative = relative_path($projectRoot, $path);
	return str_starts_with($relative, '..') ? normalize_path($path) : normalize_config_path($relative);
}

function build_ninja_relative_path(string $projectRoot, string $buildDir, string $path): string
{
	$absolutePath = is_absolute_path($path)
		? normalize_path($path)
		: normalize_path($projectRoot . '/' . normalize_config_path($path));
	return normalize_config_path(relative_path($buildDir, $absolutePath));
}

function install_hint_for_ninja(): string
{
	return match (PHP_OS_FAMILY) {
		'Windows' => 'Windows: winget install Ninja-build.Ninja',
		'Darwin' => 'macOS: brew install ninja',
		default => 'Ubuntu/Debian: sudo apt update && sudo apt install ninja-build',
	};
}

function install_hint_for_compiler(): string
{
	return match (PHP_OS_FAMILY) {
		'Windows' => 'Windows: install g++/clang++ or use an MSVC Developer Command Prompt, or set build.cxx in prism.json.',
		'Darwin' => 'macOS: install Xcode Command Line Tools or set build.cxx in prism.json.',
		default => 'Ubuntu/Debian: sudo apt update && sudo apt install g++, or set build.cxx in prism.json.',
	};
}

/** @return list<string> */
function preview_file_lines(string $path, int $maxLines): array
{
	$lines = @file($path, FILE_IGNORE_NEW_LINES);
	if (!is_array($lines)) {
		return ['  (failed to read generated file preview)'];
	}
	$out = [];
	foreach (array_slice($lines, 0, $maxLines) as $index => $line) {
		$out[] = sprintf('%3d: %s', $index + 1, $line);
	}
	return $out;
}

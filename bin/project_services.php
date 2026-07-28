<?php
declare(strict_types=1);

use Scpp\S2S\Stan\StanRunner;
use Scpp\S2S\Stan\StanDependencyResolver;
use Scpp\S2S\Stan\StanFilePass;
use Scpp\S2S\Stan\StanSourceCatalogBuilder;
use Scpp\S2S\Stan\StanStateStore;
use Scpp\S2S\Stan\StanSymbolIndexBuilder;
use Scpp\S2S\Transpiler;
use Scpp\S2S\Analysis\DeclarationKindCatalogBuilder;
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
const SCPP_PROJECT_UNIT_DEPENDENCY_STATE_FILE = 'project_unit_dependency_state.php';
const SCPP_PROJECT_UNIT_DEPENDENCY_SUMMARY_FILE = 'project_unit_dependency_summary.php';
const SCPP_BUILD_PLANNER_STATE_FILE = 'build_planner_state.json';
const SCPP_DECLARED_TYPE_KIND_CATALOG_FILE = 'declared_type_kind_catalog.json';
const SCPP_STAN_STATUS_FILE = 'stan_status.json';
const SCPP_STAN_REPORT_FILE = 'stan_report.json';
const SCPP_STAN_WORKER_FILE = 'stan_worker.json';
const SCPP_STAN_REQUEST_FILE = 'stan_request.json';
const SCPP_STAN_WORKER_LOCK_FILE = 'stan_worker.lock';
const SCPP_S2S_SIGNATURE_VERSION = 2;
const SCPP_STAN_SIGNATURE_VERSION = 1;
const SCPP_PROJECT_UNIT_DEPENDENCY_SIGNATURE_VERSION = 1;
const SCPP_CANONICAL_SOURCE_EXTENSION = 'phs';
const SCPP_COMPAT_SOURCE_EXTENSIONS = ['phs', 'php', 'jss'];
const SCPP_EXPLAIN_PROJECT_UNIT_HEADER_LIMIT = 20;
const SCPP_EXPLAIN_PROJECT_UNIT_SUMMARY_LIMIT = 50;

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
			$configuredDirs = collect_project_clean_dirs($contextProjectRoot, $context['config']);
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

	if ($args[0] === 'build-benchmark') {
		handle_build_benchmark(getcwd() === false ? '.' : getcwd(), array_slice($args, 1));
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
	echo "  scpp build [--entry=<path>] [--mode=debug|release] [--build-runtime] [--build-dependencies] [--no-stan] [--timings]\n";
	echo "  scpp build-benchmark [--entry=<path>] [--build-runtime] [--no-stan] [--keep-workdir] [--private-source=<path>] [--public-source=<path>] [--coordinator-source=<path>] [--release-source=<path>]\n";
	echo "  scpp clean\n";
	echo "  scpp update [--force]\n";
	echo "  scpp run [--entry=<path>] [--mode=debug|release] [--build-runtime] [--build-dependencies] [--force] [--no-stan] [--timings] [-- <args...>]\n";
	echo "  scpp debug [--format=text|json|ndjson] [--args=<json>] [--env=NAME=VALUE] [--stdin-file=<path>] [--plan-only] [--save-session=<path>] [--load-session=<path>]\n";
	echo "  scpp runtime-build [--debug|--release] [--force]\n";
	echo "  scpp stan\n";
	echo "  scpp stan worker [--once] [--idle-seconds=<n>] [--poll-interval-ms=<n>] [--debounce-ms=<ms>]\n";
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
	echo "  scpp explain-build [files-transpiled|files-reused|outputs-rebuilt|rebuild-fanout|generated-artifacts|ninja-explain|action-identity|object-cache|build-planner|grouping|project-units|project-unit <source>|modules|module <name>|entrypoint|final-output|generated-files|ninja-target]\n";
	echo "  scpp usability-harness [--config <path>] [--limit <n>] [--stop-after-bugs <n>] [--include-scenarios]\n";
	echo "  scpp build emits a FastCGI companion binary when prism.json fastcgi.enabled = true\n";
	echo "  scpp build-benchmark writes .prism/build_invalidation_benchmark.json from an isolated benchmark work tree\n";
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
	echo "Diagnostics:\n";
	echo "  env: SCPP_NINJA_EXPLAIN=1 scpp build --timings\n";
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
		'debounce_ms' => scpp_stan_worker_debounce_ms(),
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
		if (str_starts_with($arg, '--debounce-ms=')) {
			$value = substr($arg, strlen('--debounce-ms='));
			if (!ctype_digit($value)) {
				scpp_fail('Invalid `--debounce-ms` for `scpp stan worker`: ' . $arg . PHP_EOL, 1);
			}
			$options['debounce_ms'] = max(0, (int) $value);
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
	$pendingFingerprint = null;
	$pendingSince = 0.0;

	try {
		while (true) {
			$now = microtime(true);
			$requestForHeartbeat = read_json_file($paths['request_path']);
			$currentFingerprint = compute_stan_source_fingerprint($project['project_root'], $project['config_path']);
			$request = $requestForHeartbeat;
			$status = read_json_file($paths['status_path']);
			$publishedFingerprint = is_array($status) ? (string) ($status['source_fingerprint'] ?? '') : '';
			$requestFingerprint = is_array($request) ? (string) ($request['requested_fingerprint'] ?? '') : '';
			$requestTime = is_array($request) ? (float) ($request['requested_at'] ?? 0.0) : 0.0;
			$finishedAt = is_array($status) ? (float) ($status['finished_at'] ?? 0.0) : 0.0;
			$proactiveNeedsAnalysis = $publishedFingerprint !== $currentFingerprint;
			$explicitRequestNeedsAnalysis = $requestFingerprint !== '' && $requestFingerprint === $currentFingerprint && $requestTime > $finishedAt;
			$needsAnalysis = $proactiveNeedsAnalysis || $explicitRequestNeedsAnalysis;

			if (!$needsAnalysis) {
				$pendingFingerprint = null;
				$pendingSince = 0.0;
			} elseif (
				!$explicitRequestNeedsAnalysis
				&& is_array($status)
				&& $publishedFingerprint !== ''
				&& $options['debounce_ms'] > 0
			) {
				if ($pendingFingerprint !== $currentFingerprint) {
					$pendingFingerprint = $currentFingerprint;
					$pendingSince = $now;
				}
				$elapsedDebounceMs = (int) round(max(0.0, ($now - $pendingSince) * 1000.0));
				write_stan_worker_heartbeat($paths['heartbeat_path'], [
					'pid' => $pid,
					'project_root' => normalize_path($project['project_root']),
					'last_heartbeat_at' => $now,
					'last_seen_request_at' => is_array($requestForHeartbeat) ? (float) ($requestForHeartbeat['requested_at'] ?? 0.0) : 0.0,
					'pending_fingerprint' => $pendingFingerprint,
					'debounce_ms' => $options['debounce_ms'],
				]);
				if ($elapsedDebounceMs < $options['debounce_ms']) {
					if ($options['once']) {
						usleep(min($options['poll_interval_ms'], max(10, $options['debounce_ms'] - $elapsedDebounceMs)) * 1000);
					} else {
						usleep($options['poll_interval_ms'] * 1000);
					}
					continue;
				}
			}

			write_stan_worker_heartbeat($paths['heartbeat_path'], [
				'pid' => $pid,
				'project_root' => normalize_path($project['project_root']),
				'last_heartbeat_at' => $now,
				'last_seen_request_at' => is_array($requestForHeartbeat) ? (float) ($requestForHeartbeat['requested_at'] ?? 0.0) : 0.0,
				'pending_fingerprint' => $pendingFingerprint,
				'debounce_ms' => $options['debounce_ms'],
			]);

			if ($needsAnalysis) {
				$lastActivityAt = $now;
				$pendingFingerprint = null;
				$pendingSince = 0.0;
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
					$report = write_stan_report_file_atomic($paths['report_path'], $report);
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
		'ui-webview' => [
			'title' => 'UI And WebView Preview',
			'path' => 'docs/ui_webview_preview.md',
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
	$nativeSignalDiagnostic = $runtimeDiagnostic === null && $status !== 0 ? classify_native_signal_exit($status) : null;
	if ($nativeSignalDiagnostic !== null) {
		scpp_write($nativeSignalDiagnostic . PHP_EOL, 'stderr');
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
			'native_signal_diagnostic' => $nativeSignalDiagnostic,
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

function classify_native_signal_exit(int $status): ?string
{
	$signal = null;
	if ($status > 128 && $status < 192) {
		$signal = $status - 128;
	} elseif ($status < 0) {
		$signal = abs($status);
	}
	if ($signal === null) {
		return null;
	}
	$name = match ($signal) {
		11 => 'SIGSEGV',
		6 => 'SIGABRT',
		4 => 'SIGILL',
		default => 'signal ' . $signal,
	};
	$lines = [
		'Program terminated with ' . $name . '.',
	];
	if ($signal === 11) {
		$lines[] = 'This may indicate native stack exhaustion from deep or infinite recursion, or another native memory-safety failure.';
	}
	$lines[] = "Run 'scpp full-last-run' for the saved run report.";
	return implode(PHP_EOL, $lines);
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
			'function' => isset($details['function']) ? (string) $details['function'] : null,
			'max_call_depth' => isset($details['max_call_depth']) ? (string) $details['max_call_depth'] : null,
			'container' => isset($details['container']) ? (string) $details['container'] : null,
			'index' => isset($details['index']) ? (string) $details['index'] : null,
			'size' => isset($details['size']) ? (string) $details['size'] : null,
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
	if ((string) ($diagnostic['code'] ?? '') === 'max_call_depth_exceeded') {
		$function = trim((string) ($diagnostic['function'] ?? ''));
		$limit = trim((string) ($diagnostic['max_call_depth'] ?? ''));
		$line = 'Maximum call depth exceeded';
		if ($function !== '') {
			$line .= ' while calling `' . $function . '`';
		}
		if ($limit !== '') {
			$line .= ' (limit ' . $limit . ')';
		}
		return $line . '.';
	}
	if ((string) ($diagnostic['code'] ?? '') === 'bounds_error') {
		$container = trim((string) ($diagnostic['container'] ?? 'container'));
		$index = trim((string) ($diagnostic['index'] ?? ''));
		$size = trim((string) ($diagnostic['size'] ?? ''));
		$line = ucfirst($container) . ' index is out of bounds';
		if ($index !== '') {
			$line .= ' (index ' . $index;
			if ($size !== '') {
				$line .= ', size ' . $size;
			}
			$line .= ')';
		}
		return $line . '.';
	}
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

/** @param list<string> $args */
function handle_build_benchmark(string $cwd, array $args = []): void
{
	$project = find_project_config($cwd);
	if ($project === null) {
		scpp_fail('No ' . SCPP_PROJECT_CONFIG . ' found in the current directory or any parent directory.' . PHP_EOL . 'Run `scpp init` in the project root first.' . PHP_EOL, 1);
	}

	$report = run_build_invalidation_benchmark(
		$project['project_root'],
		$project['config_path'],
		parse_build_benchmark_command_arguments($args)
	);
	scpp_write(implode(PHP_EOL, render_build_benchmark_summary_lines($report)) . PHP_EOL);
	if ((int) ($report['failed_scenario_count'] ?? 0) > 0) {
		scpp_fail('Build benchmark completed with failed scenarios. See .prism/build_invalidation_benchmark.json for details.' . PHP_EOL, 1);
	}
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
			'runtime_metadata' => $result['metadata_path'],
		]
	);
	echo 'Runtime build completed: ' . $result['artifact_path'] . PHP_EOL;
	echo 'Runtime build mode: ' . $result['build_mode'] . PHP_EOL;
	echo 'Runtime metadata export: ' . $result['metadata_path'] . PHP_EOL;
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
	foreach (render_explain_build_view_lines($buildExplanation, $view, array_slice($args, 1)) as $line) {
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
	$stanPreflightReport = null;
	$markTiming('config_loaded');
	if (!$options['disable_stan']) {
		$stanPreflightReport = execute_stan_build_preflight($projectRoot, $configPath, $config, $sourceOverrides);
	}
	$markTiming('stan_checked');
	$useFreshStanState = !$options['disable_stan']
		&& is_array($stanPreflightReport)
		&& (string) ($stanPreflightReport['analysis_mode'] ?? 'full') === 'full';
	$buildMode = resolve_build_mode($config);
	$modeExplicit = is_string($options['build_mode'] ?? null) && trim((string) $options['build_mode']) !== '';
	if ($modeExplicit) {
		$buildMode = normalize_build_mode_name((string) $options['build_mode'], 'build option mode');
	}
	$config = apply_build_profile_to_config($config, $buildMode, $modeExplicit);
	$projectGraph = resolve_project_dependency_graph($projectRoot, $configPath, $config);
	$projectGraph = apply_build_profile_to_project_graph($projectGraph, $buildMode, $modeExplicit);
	$markTiming('project_graph_resolved');
	$markTiming('pre_source_setup_start');
	$entrypointAbs = resolve_build_entrypoint($projectRoot, $config, $options['entry_override']);

	$ninjaPath = find_command_path(['ninja']);
	if ($ninjaPath === null) {
		scpp_fail("Ninja not found. Install it and retry.\n" . install_hint_for_ninja() . PHP_EOL, 1);
	}

	$compiler = resolve_compiler($config);
	if ($compiler === null) {
		scpp_fail("No supported C++ compiler found.\n" . install_hint_for_compiler() . PHP_EOL, 1);
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
	$buildGroupingPolicy = resolve_build_grouping_policy(is_array($rootContext['config'] ?? null) ? $rootContext['config'] : $config, $buildMode);
	$objectCacheConfig = resolve_build_object_cache_config(is_array($rootContext['config'] ?? null) ? $rootContext['config'] : $config);
	$objectActionIdentityConfig = resolve_build_object_action_identity_config(is_array($rootContext['config'] ?? null) ? $rootContext['config'] : $config);
	$projectUnitScopedPacksConfig = resolve_build_project_unit_scoped_packs_config(is_array($rootContext['config'] ?? null) ? $rootContext['config'] : $config);
	$generatedDir = $rootContext['generated_dir'];
	$cacheDir = $rootContext['cache_dir'];
	$markTiming('source_inventory_start');
	$projectContexts = collect_project_context_php_file_inventory($projectRoot, $entrypointAbs, $projectContexts);
	$markTiming('source_inventory_complete');
	$buildPlannerLoadStartedAt = microtime(true);
	$previousBuildPlannerState = load_build_planner_state($cacheDir . '/' . SCPP_BUILD_PLANNER_STATE_FILE);
	$buildPlannerLoadMs = (int) round(max(0.0, (microtime(true) - $buildPlannerLoadStartedAt) * 1000.0));
	$buildPlannerStats = create_build_planner_stats($projectRoot, $cacheDir, $previousBuildPlannerState, $buildPlannerLoadMs);
	$fastcgiBuild = $fastcgiConfig['enabled'] ? resolve_fastcgi_build_spec($projectRoot, $repoRoot, $buildDir, $generatedDir, $entrypointAbs, $compiler, $fastcgiConfig) : null;
	ensure_directory($buildDir);
	$usePch = array_key_exists('use_pch', $options) && is_bool($options['use_pch']) ? $options['use_pch'] : supports_compiler_pch($compiler);
	$runtimeBuildSignature = compute_runtime_build_signature($repoRoot, $compiler, $buildMode, $runtimeConfig);
	$phpProfile = resolve_php_runtime_profile($runtimeConfig);
	$sourceOverrides = is_array($options['source_overrides'] ?? null) ? normalize_source_override_map($options['source_overrides']) : [];
	$markTiming('declared_type_catalog_start');
	$declaredTypeKinds = build_s2s_declared_type_kind_catalog_cached($projectRoot, $repoRoot, $cacheDir, $projectGraph, $sourceOverrides, $previousBuildPlannerState, $projectContexts);
	$markTiming('declared_type_catalog_complete');
	$transpiler = new Transpiler(phpProfile: $phpProfile);
	$transpiler->setDeclaredTypeKinds($declaredTypeKinds);
	$stanFrontendClassifications = $useFreshStanState ? load_stan_frontend_classifications_for_build($rootContext['cache_dir'] . '/' . SCPP_STAN_STATE_FILE) : [];
	$generatorSignature = compute_s2s_generator_signature($repoRoot, $phpProfile, $sourceOverrides, $declaredTypeKinds);
	$projectUnitDependencySignature = compute_project_unit_dependency_summary_signature($repoRoot, $phpProfile);
	$projectLibraryFlags = resolve_project_library_link_flags($projectRoot, $projectGraph, $compiler);
	$markTiming('pre_source_setup_complete');
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
		$projectContext['php_files'] = is_array($projectContext['php_files'] ?? null)
			? normalize_path_list($projectContext['php_files'])
			: collect_project_php_files($contextProjectRoot);
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
			$meta = build_file_meta_with_planner_state($phpPathAbs, $contextProjectRoot, $relativePhp, $previousBuildPlannerState, $buildPlannerStats);
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
					$message = $e->getMessage();
					if (is_jss_source_path($phpPathAbs)) {
						$message = normalize_config_path($relativePhp) . ': ' . $message;
					}
					scpp_fail($message . PHP_EOL, 3);
				} catch (Throwable $e) {
					scpp_fail('internal error: ' . $e->getMessage() . PHP_EOL, 4);
				}

				$generatedHeaderContents = implode(PHP_EOL, $cppFile->headerLines) . PHP_EOL;
				$generatedCppContents = implode(PHP_EOL, $cppFile->sourceLines) . PHP_EOL;
				$generatedInterfaceHash = hash('sha256', $generatedHeaderContents);
				$generatedImplementationHash = hash('sha256', $generatedCppContents);
				$generatedArtifactChanges = summarize_generated_artifact_hash_changes($previous, $generatedInterfaceHash, $generatedImplementationHash);

				$generatedHeaderWriteStatus = write_text_file_with_status($generatedHeader, $generatedHeaderContents);
				write_generated_line_map_file($generatedHeader . '.line.tsv', $cppFile->headerLineMap);
				$generatedCppWriteStatus = write_text_file_with_status($generatedCpp, $generatedCppContents);
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
					'generated_artifacts' => with_generated_artifact_write_statuses($generatedArtifactChanges, $generatedHeaderWriteStatus, $generatedCppWriteStatus),
				];
			} else {
				$previousInterfaceHash = is_array($previous) && preg_match('/^[0-9a-f]{64}$/', (string) ($previous['generated_interface_hash'] ?? '')) === 1
					? (string) $previous['generated_interface_hash']
					: null;
				$previousImplementationHash = is_array($previous) && preg_match('/^[0-9a-f]{64}$/', (string) ($previous['generated_implementation_hash'] ?? '')) === 1
					? (string) $previous['generated_implementation_hash']
					: null;
				$generatedInterfaceHash = $previousInterfaceHash ?? existing_file_sha256($generatedHeader) ?? '';
				$generatedImplementationHash = $previousImplementationHash ?? existing_file_sha256($generatedCpp) ?? '';
				$skippedCount++;
				$sourceRebuildReasons[] = [
					'project_root' => normalize_path($contextProjectRoot),
					'path' => normalize_config_path(relative_path($contextProjectRoot, $phpPathAbs)),
					'generated_cpp' => normalize_config_path(relative_path($projectRoot, $generatedCpp)),
					'object_path' => normalize_config_path(relative_path($projectRoot, build_object_path($projectContext['build_dir'], build_project_scoped_relative_path($projectRoot, $contextProjectRoot, $relativePhp), $compiler['kind']))),
					'is_entrypoint' => $emitProgramEntry,
					'action' => 'reused',
					'reasons' => ['source metadata and generated artifacts unchanged'],
					'generated_artifacts' => with_generated_artifact_write_statuses(summarize_generated_artifact_hash_changes($previous, $generatedInterfaceHash, $generatedImplementationHash), 'preserved', 'preserved'),
				];
			}

			$hasExportManifest = is_file($generatedExportManifest);
			$generatedUnits[] = [
				'project_root' => $contextProjectRoot,
				'relative_php' => $relativePhp,
				'generated_header' => $generatedHeader,
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
				'ctime' => $meta['ctime'] ?? 0,
				'content_hash' => $meta['content_hash'],
				'generator_signature' => $generatorSignature,
				'generated_base' => normalize_config_path(relative_path($contextProjectRoot, $generatedBase)),
				'generated_interface_hash' => $generatedInterfaceHash ?? '',
				'generated_implementation_hash' => $generatedImplementationHash ?? '',
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
	$markTiming('project_unit_analysis_start');
	$markTiming('project_unit_dependency_state_start');
	if (!$useFreshStanState) {
		write_project_unit_dependency_summary_state($projectRoot, $projectContexts, $sourceOverrides, $projectUnitDependencySignature);
	}
	$markTiming('project_unit_dependency_state_complete');
	validate_runtime_module_symbol_usage($projectRoot, $generatedUnits, $runtimeConfig);
	write_text_file($buildDir . '/runtime_signature.txt', $runtimeBuildSignature . PHP_EOL);
	$markTiming('project_unit_summary_artifact_load_start');
	$projectUnitDependencySummaryFreshness = collect_project_unit_dependency_summary_freshness($projectRoot, $projectContexts, $sourceOverrides, $projectUnitDependencySignature, $useFreshStanState);
	$cachedProjectUnitSummaryArtifact = load_fresh_project_unit_dependency_summary_artifact($projectRoot, $projectContexts, $projectUnitDependencySummaryFreshness);
	$cachedProjectUnitDependencySummaries = is_array($cachedProjectUnitSummaryArtifact)
		? (is_array($cachedProjectUnitSummaryArtifact['dependency_summaries'] ?? null) ? $cachedProjectUnitSummaryArtifact['dependency_summaries'] : null)
		: null;
	$markTiming('project_unit_summary_artifact_load_complete');
	$markTiming('project_unit_pack_write_start');
	$projectUnitPackStateBefore = capture_project_unit_pack_header_state($projectContexts);
	$projectUnitForceIncludes = write_project_unit_force_include_headers($projectContexts);
	foreach ($generatedUnits as &$unit) {
		$unit['force_include_header'] = $projectUnitForceIncludes[normalize_path($unit['project_root'])] ?? null;
	}
	unset($unit);
	if ((bool) ($projectUnitScopedPacksConfig['enabled'] ?? false)) {
		$projectUnitDependencySummaries = apply_project_unit_scoped_force_include_candidates($projectRoot, $projectContexts, $generatedUnits, $useFreshStanState, $cachedProjectUnitDependencySummaries);
	} else {
		$projectUnitDependencySummaries = $cachedProjectUnitDependencySummaries ?? collect_project_unit_dependency_summaries($projectRoot, $projectContexts, $generatedUnits, $useFreshStanState);
		$projectUnitDependencySummaries = apply_project_unit_active_force_include_headers_to_dependency_summaries($projectRoot, $generatedUnits, $projectUnitDependencySummaries);
	}
	$markTiming('project_unit_pack_write_complete');
	foreach ($nativeCppUnits as &$nativeUnit) {
		$nativeUnit['force_include_header'] = $projectUnitForceIncludes[normalize_path($nativeUnit['project_root'])] ?? null;
	}
	unset($nativeUnit);
	cleanup_project_unit_pack_headers($projectContexts, $generatedUnits, $nativeCppUnits, $projectUnitForceIncludes);
	$projectUnitPackChanges = compare_project_unit_pack_header_state($projectRoot, $projectUnitPackStateBefore, capture_project_unit_pack_header_state($projectContexts));
	$markTiming('project_unit_report_start');
	$projectUnitForceIncludeReport = collect_project_unit_force_include_report($projectRoot, $projectContexts, $generatedUnits, $nativeCppUnits, $useFreshStanState, $projectUnitPackChanges, $projectUnitDependencySummaries);
	$projectUnitDependencySummaryArtifact = is_array($cachedProjectUnitSummaryArtifact) && is_array($cachedProjectUnitSummaryArtifact['artifact'] ?? null)
		? normalize_project_unit_dependency_summary_artifact_info($cachedProjectUnitSummaryArtifact['artifact'])
		: write_project_unit_dependency_summary_artifact($projectRoot, $projectContexts, $projectUnitForceIncludeReport, $projectUnitDependencySummaryFreshness);
	$projectUnitForceIncludeReport['dependency_summary_artifact'] = $projectUnitDependencySummaryArtifact;
	$markTiming('project_unit_report_complete');
	$markTiming('project_unit_analysis_complete');
	$markTiming('build_grouping_analysis_start');
	$buildGroupingPolicy = apply_auto_build_grouping_evidence($projectRoot, $buildGroupingPolicy, $generatedUnits);
	$projectModuleReport = collect_project_module_report($projectRoot, $projectContexts, $generatedUnits, $projectUnitForceIncludeReport);
	$buildGroupingPolicy = apply_project_module_build_grouping_evidence($projectRoot, $buildGroupingPolicy, $projectModuleReport, $generatedUnits);
	apply_grouped_generated_object_edges($projectRoot, $buildDir, $generatedDir, $buildGroupingPolicy, $generatedUnits, $sourceRebuildReasons, $compiler['kind']);
	$markTiming('build_grouping_analysis_complete');
	$markTiming('build_planner_write_start');
	$buildPlannerReport = write_build_planner_state_report(
		$projectRoot,
		$cacheDir,
		$previousBuildPlannerState,
		$buildPlannerStats,
		$projectGraph,
		$projectContexts,
		$generatedUnits,
		$nativeCppUnits,
		$projectModuleReport,
		is_array($stanPreflightReport) ? $stanPreflightReport : null,
		$timingMs('source_scan_start', 'source_scan_complete')
	);
	$markTiming('build_planner_write_complete');

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
	$buildNinja = render_build_ninja($projectRoot, $repoRoot, $buildDir, $generatedDir, $generatedUnits, $nativeCppUnits, $outputName, $compiler, $buildMode, $runtimeConfig, $projectLibraryFlags, $fastcgiBuild, $effectiveBuildOptions, $runtimePlacementForInvocation, $projectModuleReport);
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
	$markTiming('pre_ninja_diagnostics_start');
	$preNinjaActionIdentityReason = object_action_identity_pre_ninja_capture_reason($objectCacheConfig);
	$objectActionIdentityReport = $preNinjaActionIdentityReason !== null
		? collect_object_action_identity_report(
			$projectRoot,
			$repoRoot,
			$buildDir,
			$generatedDir,
			$generatedUnits,
			$nativeCppUnits,
			$compiler,
			$buildMode,
			$runtimeConfig,
			$effectiveBuildOptions,
			$runtimePlacementForInvocation,
			$projectModuleReport,
			$preNinjaActionIdentityReason
		)
		: create_object_action_identity_placeholder_report('default lightweight build report');
	echo 'Transpiled PHP files: ' . $transpiledCount . ', skipped unchanged: ' . $skippedCount . PHP_EOL;
	echo 'Generated Ninja file: ' . normalize_config_path(relative_path($projectRoot, $buildNinjaPath)) . PHP_EOL;
	echo 'Using compiler: ' . compiler_display_command($compiler) . ' (' . $compiler['kind'] . ')' . PHP_EOL;
	echo 'Using build mode: ' . $buildMode . PHP_EOL;
	echo 'Using build grouping policy: ' . (string) ($buildGroupingPolicy['policy'] ?? 'incremental') . ' (' . (string) ($buildGroupingPolicy['status'] ?? 'report_only') . ')' . PHP_EOL;
	echo 'Object cache: ' . ((bool) ($objectCacheConfig['enabled'] ?? false) ? 'enabled' : 'disabled') . ' (' . (string) ($objectCacheConfig['source'] ?? 'default disabled') . ')' . PHP_EOL;
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
			$phpProfile,
			$projectUnitForceIncludeReport,
			$rootContext,
			collect_build_grouping_report($projectRoot, $buildGroupingPolicy, $generatedUnits, $nativeCppUnits, []),
			$projectModuleReport
		);
	}
	if (!$options['compile_dependencies']) {
		validate_reused_dependency_artifacts($projectRoot, $generatedUnits, $nativeCppUnits);
	}
	$objectCacheReport = restore_object_action_cache_entries($projectRoot, $cacheDir, $objectCacheConfig, $objectActionIdentityReport);
	scrub_invalid_cached_objects_for_rebuild($projectRoot, array_merge($generatedUnits, $nativeCppUnits), $options['compile_dependencies']);
	if (object_cache_report_has_restored_objects($objectCacheReport)) {
		$buildNinja = render_build_ninja($projectRoot, $repoRoot, $buildDir, $generatedDir, $generatedUnits, $nativeCppUnits, $outputName, $compiler, $buildMode, $runtimeConfig, $projectLibraryFlags, $fastcgiBuild, $effectiveBuildOptions, $runtimePlacementForInvocation, $projectModuleReport, $objectCacheReport);
		write_text_file($buildNinjaPath, $buildNinja);
	}
	$markTiming('pre_ninja_diagnostics_complete');
	$buildOutputMtimesBefore = capture_file_mtimes($buildOutputs);

	$command = [
		$ninjaPath,
		'-C',
		normalize_config_path(relative_path($projectRoot, $buildDir)),
		'-f',
		basename($buildNinjaPath),
	];
	if (build_ninja_explain_requested()) {
		$command[] = '-d';
		$command[] = 'explain';
	}
	if (build_ninja_verbose_requested()) {
		$command[] = '-v';
	}
	$ninjaJobs = getenv('SCPP_NINJA_JOBS');
	// Keep compiler self-builds bounded and predictable unless explicitly overridden.
	if (!is_string($ninjaJobs) || preg_match('/^[1-9][0-9]*$/', $ninjaJobs) !== 1) {
		$ninjaJobs = '16';
	}
	if (preg_match('/^[1-9][0-9]*$/', $ninjaJobs) === 1) {
		$command[] = '-j';
		$command[] = $ninjaJobs;
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
	$markTiming('post_ninja_diagnostics_start');
	$ninjaStdout = is_string($ninjaStdout) ? $ninjaStdout : '';
	$ninjaStderr = is_string($ninjaStderr) ? $ninjaStderr : '';
	$ninjaExplainReport = collect_ninja_explain_report($projectRoot, $buildDir, $ninjaStdout, $ninjaStderr, $generatedUnits, $nativeCppUnits);
	if (build_ninja_explain_probe_requested() && !build_ninja_explain_raw_requested()) {
		$ninjaStdout = strip_ninja_explain_lines($ninjaStdout);
		$ninjaStderr = strip_ninja_explain_lines($ninjaStderr);
	}
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
		$failureBuildGroupingReport = collect_build_grouping_report($projectRoot, $buildGroupingPolicy, $generatedUnits, $nativeCppUnits, []);
		$objectActionIdentityReport = collect_object_action_identity_report(
			$projectRoot,
			$repoRoot,
			$buildDir,
			$generatedDir,
			$generatedUnits,
			$nativeCppUnits,
			$compiler,
			$buildMode,
			$runtimeConfig,
			$effectiveBuildOptions,
			$runtimePlacementForInvocation,
			$projectModuleReport,
			'Ninja failure diagnostics'
		);
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
				'generated_artifact_writes' => summarize_generated_artifact_write_report($sourceRebuildReasons),
				'build_planner' => $buildPlannerReport,
				'ninja_explain' => $ninjaExplainReport,
				'object_action_identity' => $objectActionIdentityReport,
				'object_cache' => $objectCacheReport,
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
					$command,
					$runtimeConfig,
					$projectUnitForceIncludeReport,
					[],
					$buildMode,
					$rootContext,
					$failureBuildGroupingReport,
					$projectModuleReport,
					$ninjaExplainReport,
					$objectActionIdentityReport,
					$objectCacheReport,
					$buildPlannerReport
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
	$rebuildFanout = summarize_build_rebuild_fanout($projectRoot, $generatedUnits, $nativeCppUnits, $runtimeBuild, $rebuiltOutputs, $projectUnitPackChanges);
	$buildGroupingReport = collect_build_grouping_report($projectRoot, $buildGroupingPolicy, $generatedUnits, $nativeCppUnits, $rebuildFanout);
	$postNinjaActionIdentityReason = object_action_identity_success_capture_reason($objectCacheConfig, $objectActionIdentityConfig);
	if ($postNinjaActionIdentityReason !== null) {
		$objectActionIdentityReport = collect_object_action_identity_report(
			$projectRoot,
			$repoRoot,
			$buildDir,
			$generatedDir,
			$generatedUnits,
			$nativeCppUnits,
			$compiler,
			$buildMode,
			$runtimeConfig,
			$effectiveBuildOptions,
			$runtimePlacementForInvocation,
			$projectModuleReport,
			$postNinjaActionIdentityReason
		);
	}
	$objectCacheReport = store_object_action_cache_entries($projectRoot, $cacheDir, $objectCacheConfig, $objectActionIdentityReport, $objectCacheReport);
	$markTiming('post_ninja_diagnostics_complete');
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
		'pre_source_setup_ms' => $timingMs('pre_source_setup_start', 'pre_source_setup_complete'),
		'source_inventory_ms' => $timingMs('source_inventory_start', 'source_inventory_complete'),
		'declared_type_catalog_ms' => $timingMs('declared_type_catalog_start', 'declared_type_catalog_complete'),
		'collect_project_php_files_and_s2s_state_ms' => $timingMs('source_scan_start', 'source_scan_complete'),
		'project_unit_analysis_ms' => $timingMs('project_unit_analysis_start', 'project_unit_analysis_complete'),
		'project_unit_dependency_state_ms' => $timingMs('project_unit_dependency_state_start', 'project_unit_dependency_state_complete'),
		'project_unit_summary_artifact_load_ms' => $timingMs('project_unit_summary_artifact_load_start', 'project_unit_summary_artifact_load_complete'),
		'project_unit_pack_write_ms' => $timingMs('project_unit_pack_write_start', 'project_unit_pack_write_complete'),
		'project_unit_report_ms' => $timingMs('project_unit_report_start', 'project_unit_report_complete'),
		'build_grouping_analysis_ms' => $timingMs('build_grouping_analysis_start', 'build_grouping_analysis_complete'),
		'build_planner_write_ms' => $timingMs('build_planner_write_start', 'build_planner_write_complete'),
		'render_and_write_build_ninja_ms' => $timingMs('runtime_prepare_complete', 'build_ninja_written'),
		'pre_ninja_diagnostics_ms' => $timingMs('pre_ninja_diagnostics_start', 'pre_ninja_diagnostics_complete'),
		'ninja_subprocess_ms' => $timingMs('ninja_started', 'ninja_finished'),
		'post_ninja_diagnostics_ms' => $timingMs('post_ninja_diagnostics_start', 'post_ninja_diagnostics_complete'),
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
			'generated_artifact_writes' => summarize_generated_artifact_write_report($sourceRebuildReasons),
			'build_planner' => $buildPlannerReport,
			'ninja_explain' => $ninjaExplainReport,
			'object_action_identity' => $objectActionIdentityReport,
			'object_cache' => $objectCacheReport,
			'rebuilt_outputs' => array_values(array_map(static fn (string $path): string => normalize_config_path(relative_path($projectRoot, $path)), $rebuiltOutputs)),
			'rebuild_fanout' => $rebuildFanout,
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
				$command,
				$runtimeConfig,
				$projectUnitForceIncludeReport,
				$rebuildFanout,
				$buildMode,
				$rootContext,
				$buildGroupingReport,
				$projectModuleReport,
				$ninjaExplainReport,
				$objectActionIdentityReport,
				$objectCacheReport,
				$buildPlannerReport
			),
		]
	);
	$timingDetails['write_last_run_report_ms'] = (int) round(max(0, (microtime(true) - $reportStartedAt) * 1000));
	if (!$options['disable_stan'] && $sourceOverrides === []) {
		maybe_autostart_stan_worker($projectRoot, build_stan_worker_paths($projectRoot, load_project_config($configPath)));
	}
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
			$command,
			$runtimeConfig,
			$projectUnitForceIncludeReport,
			$rebuildFanout,
			$buildMode,
			$rootContext,
			$buildGroupingReport,
			$projectModuleReport,
			$ninjaExplainReport,
			$objectActionIdentityReport,
			$objectCacheReport,
			$buildPlannerReport
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
	array $projectUnitForceIncludeReport = [],
	array $rootContext = [],
	array $buildGroupingReport = [],
	array $projectModuleReport = [],
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
	$runtimeBuildCommand = $buildMode === 'release'
		? 'scpp runtime-build --release'
		: 'scpp runtime-build --debug';
	$guidance = append_standard_report_guidance([
		'This build is reusing runtime artifacts by default.',
		"Run '" . $runtimeBuildCommand . "' to rebuild the reusable " . $buildMode . " runtime artifact.",
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
			'generated_artifact_writes' => summarize_generated_artifact_write_report($sourceRebuildReasons),
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
				[],
				[],
				$projectUnitForceIncludeReport,
				[],
				$buildMode,
				$rootContext,
				$buildGroupingReport,
				$projectModuleReport
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

/** @param list<string> $args @return array{compile_runtime:bool,compile_dependencies:bool,force_runtime_rebuild:bool,disable_stan:bool,show_timings:bool,entry_override:?string,build_mode:?string} */
function parse_build_command_arguments(array $args): array
{
	$options = [
		'compile_runtime' => false,
		'compile_dependencies' => false,
		'force_runtime_rebuild' => false,
		'disable_stan' => false,
		'show_timings' => false,
		'entry_override' => null,
		'build_mode' => null,
	];
	foreach ($args as $arg) {
		if (str_starts_with($arg, '--entry=')) {
			$options['entry_override'] = normalize_config_path(substr($arg, strlen('--entry=')));
			continue;
		}
		if (str_starts_with($arg, '--mode=')) {
			$options['build_mode'] = normalize_build_mode_name(substr($arg, strlen('--mode=')), '--mode');
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

/** @param list<string> $args @return array{compile_runtime:bool,disable_stan:bool,show_timings:bool,keep_workdir:bool,entry_override:?string,private_source:?string,public_source:?string,coordinator_source:?string,release_source:?string} */
function parse_build_benchmark_command_arguments(array $args): array
{
	$options = [
		'compile_runtime' => false,
		'disable_stan' => false,
		'show_timings' => false,
		'keep_workdir' => false,
		'entry_override' => null,
		'private_source' => null,
		'public_source' => null,
		'coordinator_source' => null,
		'release_source' => null,
	];
	foreach ($args as $arg) {
		if (str_starts_with($arg, '--entry=')) {
			$options['entry_override'] = normalize_config_path(substr($arg, strlen('--entry=')));
			continue;
		}
		if (str_starts_with($arg, '--private-source=')) {
			$options['private_source'] = normalize_build_benchmark_source_selector(substr($arg, strlen('--private-source=')), '--private-source');
			continue;
		}
		if (str_starts_with($arg, '--public-source=')) {
			$options['public_source'] = normalize_build_benchmark_source_selector(substr($arg, strlen('--public-source=')), '--public-source');
			continue;
		}
		if (str_starts_with($arg, '--coordinator-source=')) {
			$options['coordinator_source'] = normalize_build_benchmark_source_selector(substr($arg, strlen('--coordinator-source=')), '--coordinator-source');
			continue;
		}
		if (str_starts_with($arg, '--release-source=')) {
			$options['release_source'] = normalize_build_benchmark_source_selector(substr($arg, strlen('--release-source=')), '--release-source');
			continue;
		}
		if ($arg === '--build-runtime') {
			$options['compile_runtime'] = true;
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
		if ($arg === '--keep-workdir') {
			$options['keep_workdir'] = true;
			continue;
		}
		scpp_fail('Unknown option for `scpp build-benchmark`: ' . $arg . PHP_EOL, 1);
	}
	return $options;
}

function normalize_build_benchmark_source_selector(string $value, string $label): string
{
	$value = trim(str_replace('\\', '/', $value));
	if ($value === '') {
		scpp_fail('Empty value for ' . $label . PHP_EOL, 1);
	}
	if (is_absolute_path($value)) {
		scpp_fail($label . ' must be project-relative, not absolute: ' . $value . PHP_EOL, 1);
	}
	if (str_contains($value, "\0")) {
		scpp_fail($label . ' contains an invalid NUL byte.' . PHP_EOL, 1);
	}
	return normalize_config_path($value);
}

/** @param array{compile_runtime:bool,disable_stan:bool,show_timings:bool,keep_workdir:bool,entry_override:?string,private_source:?string,public_source:?string,coordinator_source:?string,release_source:?string} $options @return array<string,mixed> */
function run_build_invalidation_benchmark(string $projectRoot, string $configPath, array $options): array
{
	$startedAt = microtime(true);
	$projectRoot = normalize_path($projectRoot);
	$configPath = normalize_path($configPath);
	$configRelative = normalize_config_path(relative_path($projectRoot, $configPath));
	$benchmarkId = gmdate('Ymd_His') . '_' . bin2hex(random_bytes(4));
	$benchmarkRoot = normalize_path($projectRoot . '/.prism/build_benchmarks/' . $benchmarkId);
	$seedRoot = normalize_path($benchmarkRoot . '/seed');
	$scenariosRoot = normalize_path($benchmarkRoot . '/scenarios');
	$reportPath = normalize_path($projectRoot . '/.prism/build_invalidation_benchmark.json');

	$report = [
		'version' => 1,
		'kind' => 'build_invalidation_benchmark',
		'benchmark_id' => $benchmarkId,
		'status' => 'running',
		'project_root' => $projectRoot,
		'config_path' => normalize_config_path(relative_path($projectRoot, $configPath)),
		'report_path' => normalize_config_path(relative_path($projectRoot, $reportPath)),
		'work_dir' => normalize_config_path(relative_path($projectRoot, $benchmarkRoot)),
		'work_dir_retained' => (bool) $options['keep_workdir'],
		'started_at' => gmdate('c', (int) floor($startedAt)),
		'options' => [
			'entry_override' => $options['entry_override'],
			'compile_runtime' => (bool) $options['compile_runtime'],
			'disable_stan' => (bool) $options['disable_stan'],
			'show_timings' => (bool) $options['show_timings'],
			'selectors' => [
				'private_source' => $options['private_source'],
				'public_source' => $options['public_source'],
				'coordinator_source' => $options['coordinator_source'],
				'release_source' => $options['release_source'],
			],
		],
		'seed' => null,
		'scenarios' => [],
		'measured_scenario_count' => 0,
		'skipped_scenario_count' => 0,
		'failed_scenario_count' => 0,
	];

	ensure_directory($benchmarkRoot);
	copy_directory_tree_for_build_benchmark($projectRoot, $seedRoot, false);
	$seedConfigPath = normalize_path($seedRoot . '/' . $configRelative);

	$debugSeedOptions = [
		'compile_runtime' => (bool) $options['compile_runtime'],
		'compile_dependencies' => false,
		'force_runtime_rebuild' => false,
		'disable_stan' => (bool) $options['disable_stan'],
		'show_timings' => false,
		'entry_override' => $options['entry_override'],
		'build_mode' => 'debug',
	];
	$debugBuildOptions = $debugSeedOptions;
	$debugBuildOptions['compile_runtime'] = false;

	$seedRun = run_build_benchmark_build($seedRoot, $seedConfigPath, $debugSeedOptions);
	$report['seed'] = build_benchmark_seed_summary('debug_seed', 'debug', $seedRun);
	if (!$seedRun['ok']) {
		$report['status'] = 'failed';
		$report['failed_scenario_count'] = 1;
		$report['finished_at'] = gmdate('c');
		$report['duration_ms'] = (int) round(max(0, (microtime(true) - $startedAt) * 1000));
		write_json_file_atomic($reportPath, $report);
		if (!$options['keep_workdir']) {
			remove_directory_tree($benchmarkRoot);
		}
		return $report;
	}

	$scenarios = [];
	$scenarios[] = measure_build_benchmark_scenario(
		'warm_no_change',
		$seedRoot,
		$seedConfigPath,
		$debugBuildOptions,
		'debug',
		null,
		null
	);

	$editScenarios = [
		[
			'name' => 'private_body_edit',
			'selector' => $options['private_source'],
			'mutation_kind' => 'private_body',
			'build_mode' => 'debug',
		],
		[
			'name' => 'public_surface_edit',
			'selector' => $options['public_source'],
			'mutation_kind' => 'public_surface',
			'build_mode' => 'debug',
		],
		[
			'name' => 'broad_coordinator_edit',
			'selector' => $options['coordinator_source'],
			'mutation_kind' => 'coordinator',
			'build_mode' => 'debug',
		],
		[
			'name' => 'release_o3_hot_edit',
			'selector' => $options['release_source'],
			'mutation_kind' => 'release_hot',
			'build_mode' => 'release',
		],
	];

	foreach ($editScenarios as $scenario) {
		$name = (string) $scenario['name'];
		$selector = is_string($scenario['selector']) ? $scenario['selector'] : null;
		$buildMode = (string) $scenario['build_mode'];
		if ($selector === null) {
			$scenarios[] = skipped_build_benchmark_scenario($name, $buildMode, 'source selector not provided');
			continue;
		}

		$scenarioRoot = normalize_path($scenariosRoot . '/' . $name);
		copy_directory_tree_for_build_benchmark($seedRoot, $scenarioRoot, true);
		$scenarioConfigPath = normalize_path($scenarioRoot . '/' . $configRelative);
		$buildOptions = $debugBuildOptions;
		if ($buildMode === 'release') {
			$releaseSeedOptions = $debugSeedOptions;
			$releaseSeedOptions['build_mode'] = 'release';
			$releaseSeed = run_build_benchmark_build($scenarioRoot, $scenarioConfigPath, $releaseSeedOptions);
			if (!$releaseSeed['ok']) {
				$scenarios[] = failed_build_benchmark_seed_scenario($name, $buildMode, $releaseSeed);
				continue;
			}
			$buildOptions = $debugBuildOptions;
			$buildOptions['build_mode'] = 'release';
		}

		$mutation = apply_build_benchmark_source_mutation(
			$scenarioRoot,
			$selector,
			(string) $scenario['mutation_kind'],
			bin2hex(random_bytes(4))
		);
		if (!($mutation['ok'] ?? false)) {
			$scenarios[] = skipped_build_benchmark_scenario($name, $buildMode, (string) ($mutation['skip_reason'] ?? 'mutation skipped'), $selector);
			continue;
		}

		$scenarios[] = measure_build_benchmark_scenario(
			$name,
			$scenarioRoot,
			$scenarioConfigPath,
			$buildOptions,
			$buildMode,
			is_array($mutation) ? $mutation : null,
			$selector
		);
	}

	$report['scenarios'] = $scenarios;
	foreach ($scenarios as $scenario) {
		$status = (string) ($scenario['status'] ?? '');
		if ($status === 'measured') {
			$report['measured_scenario_count']++;
		} elseif ($status === 'skipped') {
			$report['skipped_scenario_count']++;
		} else {
			$report['failed_scenario_count']++;
		}
	}
	$report['status'] = (int) $report['failed_scenario_count'] === 0 ? 'success' : 'failed';
	$report['finished_at'] = gmdate('c');
	$report['duration_ms'] = (int) round(max(0, (microtime(true) - $startedAt) * 1000));
	write_json_file_atomic($reportPath, $report);
	if (!$options['keep_workdir']) {
		remove_directory_tree($benchmarkRoot);
	}
	return $report;
}

function copy_directory_tree_for_build_benchmark(string $source, string $destination, bool $includePrism): void
{
	$source = normalize_path($source);
	$destination = normalize_path($destination);
	if (!is_dir($source)) {
		scpp_fail('Build benchmark source directory not found: ' . $source . PHP_EOL, 2);
	}
	if (file_exists($destination)) {
		scpp_fail('Build benchmark destination already exists: ' . $destination . PHP_EOL, 2);
	}
	ensure_directory($destination);
	$items = scandir($source);
	if ($items === false) {
		scpp_fail('Failed to read build benchmark source directory: ' . $source . PHP_EOL, 2);
	}
	foreach ($items as $item) {
		if ($item === '.' || $item === '..' || $item === '.git') {
			continue;
		}
		if (!$includePrism && $item === '.prism') {
			continue;
		}
		$childSource = $source . '/' . $item;
		$childDestination = $destination . '/' . $item;
		if (is_link($childSource)) {
			continue;
		}
		if (is_dir($childSource)) {
			copy_directory_tree_for_build_benchmark($childSource, $childDestination, $includePrism);
			continue;
		}
		if (is_file($childSource) && !@copy($childSource, $childDestination)) {
			scpp_fail('Failed to copy build benchmark file: ' . $childSource . PHP_EOL, 2);
		}
	}
}

/** @param array<string,mixed> $buildOptions @return array<string,mixed> */
function run_build_benchmark_build(string $projectRoot, string $configPath, array $buildOptions): array
{
	$startedAt = microtime(true);
	$previousProbe = getenv('SCPP_NINJA_EXPLAIN_PROBE');
	putenv('SCPP_NINJA_EXPLAIN_PROBE=1');
	try {
		$build = scpp_run_build_service($projectRoot, $configPath, $buildOptions);
	} finally {
		if ($previousProbe === false) {
			putenv('SCPP_NINJA_EXPLAIN_PROBE');
		} else {
			putenv('SCPP_NINJA_EXPLAIN_PROBE=' . $previousProbe);
		}
	}
	$finishedAt = microtime(true);
	$lastRun = read_json_file(normalize_path($projectRoot . '/.prism/last_run.json'));
	return [
		'ok' => (bool) ($build['ok'] ?? false),
		'exit_code' => (int) ($build['exit_code'] ?? 1),
		'wall_ms' => (int) round(max(0, ($finishedAt - $startedAt) * 1000)),
		'output' => (string) ($build['output'] ?? ''),
		'error' => (string) ($build['error'] ?? ''),
		'last_run' => is_array($lastRun) ? $lastRun : [],
		'result' => is_array($build['result'] ?? null) ? $build['result'] : [],
	];
}

/** @param array<string,mixed> $run @return array<string,mixed> */
function build_benchmark_seed_summary(string $name, string $buildMode, array $run): array
{
	return [
		'name' => $name,
		'status' => (bool) ($run['ok'] ?? false) ? 'success' : 'build_failed',
		'build_mode' => $buildMode,
		'exit_code' => (int) ($run['exit_code'] ?? 1),
		'wall_ms' => (int) ($run['wall_ms'] ?? 0),
		'metrics' => build_benchmark_metrics_from_run($run),
		'failure' => (bool) ($run['ok'] ?? false) ? null : build_benchmark_failure_summary($run),
	];
}

/** @param array<string,mixed> $buildOptions @param array<string,mixed>|null $mutation @return array<string,mixed> */
function measure_build_benchmark_scenario(
	string $name,
	string $projectRoot,
	string $configPath,
	array $buildOptions,
	string $buildMode,
	?array $mutation,
	?string $sourceSelector
): array {
	$run = run_build_benchmark_build($projectRoot, $configPath, $buildOptions);
	return [
		'name' => $name,
		'status' => (bool) ($run['ok'] ?? false) ? 'measured' : 'build_failed',
		'build_mode' => $buildMode,
		'source_selector' => $sourceSelector,
		'mutation' => $mutation,
		'exit_code' => (int) ($run['exit_code'] ?? 1),
		'metrics' => build_benchmark_metrics_from_run($run),
		'failure' => (bool) ($run['ok'] ?? false) ? null : build_benchmark_failure_summary($run),
	];
}

/** @return array<string,mixed> */
function skipped_build_benchmark_scenario(string $name, string $buildMode, string $reason, ?string $sourceSelector = null): array
{
	return [
		'name' => $name,
		'status' => 'skipped',
		'build_mode' => $buildMode,
		'source_selector' => $sourceSelector,
		'skip_reason' => $reason,
		'metrics' => null,
	];
}

/** @param array<string,mixed> $seedRun @return array<string,mixed> */
function failed_build_benchmark_seed_scenario(string $name, string $buildMode, array $seedRun): array
{
	return [
		'name' => $name,
		'status' => 'build_failed',
		'build_mode' => $buildMode,
		'source_selector' => null,
		'phase' => 'seed',
		'metrics' => build_benchmark_metrics_from_run($seedRun),
		'failure' => build_benchmark_failure_summary($seedRun),
	];
}

/** @param array<string,mixed> $run @return array<string,mixed> */
function build_benchmark_metrics_from_run(array $run): array
{
	$lastRun = is_array($run['last_run'] ?? null) ? $run['last_run'] : [];
	$details = is_array($lastRun['details'] ?? null) ? $lastRun['details'] : [];
	$result = is_array($run['result'] ?? null) ? $run['result'] : [];
	$explanation = is_array($details['build_explanation'] ?? null)
		? $details['build_explanation']
		: (is_array($result['build_explanation'] ?? null) ? $result['build_explanation'] : []);
	$timings = is_array($details['timing_breakdown_ms'] ?? null)
		? $details['timing_breakdown_ms']
		: (is_array($result['timing_breakdown_ms'] ?? null) ? $result['timing_breakdown_ms'] : []);
	$grouping = is_array($explanation['build_grouping'] ?? null) ? $explanation['build_grouping'] : [];
	$modules = is_array($explanation['project_modules'] ?? null) ? $explanation['project_modules'] : [];
	$ninjaExplain = is_array($explanation['ninja_explain'] ?? null) ? $explanation['ninja_explain'] : [];
	$transpiledCount = (int) ($explanation['transpiled_count'] ?? ($details['transpiled_count'] ?? 0));
	$skippedCount = (int) ($explanation['skipped_count'] ?? ($details['skipped_count'] ?? 0));
	return [
		'duration_ms' => (int) ($lastRun['duration_ms'] ?? 0),
		'wall_ms' => (int) ($run['wall_ms'] ?? 0),
		'ninja_subprocess_ms' => (int) ($timings['ninja_subprocess_ms'] ?? 0),
		'transpiled_count' => $transpiledCount,
		'reused_count' => $skippedCount,
		'skipped_count' => $skippedCount,
		'generated_artifact_writes' => is_array($explanation['generated_artifact_writes'] ?? null) ? $explanation['generated_artifact_writes'] : [],
		'object_fanout' => is_array($explanation['rebuild_fanout'] ?? null) ? $explanation['rebuild_fanout'] : [],
		'grouping_fanout' => [
			'total_groups' => (int) ($grouping['total_groups'] ?? 0),
			'changed_group_count' => (int) ($grouping['changed_group_count'] ?? 0),
			'object_fanout' => is_array($grouping['object_fanout'] ?? null) ? $grouping['object_fanout'] : [],
		],
		'module_cache_status_counts' => is_array($modules['cache_status_counts'] ?? null) ? $modules['cache_status_counts'] : [],
		'ninja_explain' => [
			'enabled' => (bool) ($ninjaExplain['enabled'] ?? false),
			'source' => (string) ($ninjaExplain['source'] ?? ''),
			'line_count' => count(is_array($ninjaExplain['lines'] ?? null) ? $ninjaExplain['lines'] : []),
			'object_explanation_count' => count(is_array($ninjaExplain['object_explanations'] ?? null) ? $ninjaExplain['object_explanations'] : []),
		],
	];
}

/** @param array<string,mixed> $run @return array<string,mixed> */
function build_benchmark_failure_summary(array $run): array
{
	return [
		'exit_code' => (int) ($run['exit_code'] ?? 1),
		'stdout_tail' => build_benchmark_tail_lines((string) ($run['output'] ?? ''), 20),
		'stderr_tail' => build_benchmark_tail_lines((string) ($run['error'] ?? ''), 20),
	];
}

/** @return list<string> */
function build_benchmark_tail_lines(string $text, int $limit): array
{
	$lines = preg_split('/\R/', trim($text));
	$lines = is_array($lines) ? array_values(array_filter($lines, static fn (string $line): bool => $line !== '')) : [];
	if (count($lines) <= $limit) {
		return $lines;
	}
	return array_slice($lines, -$limit);
}

/** @return array<string,mixed> */
function apply_build_benchmark_source_mutation(string $projectRoot, string $sourceSelector, string $kind, string $token): array
{
	$projectRoot = normalize_path($projectRoot);
	$sourceSelector = normalize_config_path($sourceSelector);
	$sourcePath = normalize_path($projectRoot . '/' . $sourceSelector);
	if (!path_is_inside($projectRoot, $sourcePath)) {
		scpp_fail('Build benchmark source selector escapes the project root: ' . $sourceSelector . PHP_EOL, 1);
	}
	if (!is_file($sourcePath)) {
		return [
			'ok' => false,
			'skip_reason' => 'selected source not found: ' . $sourceSelector,
		];
	}
	$before = file_get_contents($sourcePath);
	if (!is_string($before)) {
		return [
			'ok' => false,
			'skip_reason' => 'selected source could not be read: ' . $sourceSelector,
		];
	}

	$mutation = build_benchmark_mutated_source($before, $sourcePath, $kind, $token);
	if (!($mutation['changed'] ?? false) || !is_string($mutation['contents'] ?? null)) {
		return [
			'ok' => false,
			'skip_reason' => (string) ($mutation['skip_reason'] ?? 'no safe mutation was available for selected source'),
		];
	}
	$after = (string) $mutation['contents'];
	write_text_file($sourcePath, $after);
	return [
		'ok' => true,
		'kind' => $kind,
		'source_path' => $sourceSelector,
		'strategy' => (string) ($mutation['strategy'] ?? 'unknown'),
		'description' => (string) ($mutation['description'] ?? ''),
		'bytes_before' => strlen($before),
		'bytes_after' => strlen($after),
		'sha256_before' => hash('sha256', $before),
		'sha256_after' => hash('sha256', $after),
	];
}

/** @return array<string,mixed> */
function build_benchmark_mutated_source(string $contents, string $sourcePath, string $kind, string $token): array
{
	if ($kind === 'public_surface') {
		return build_benchmark_mutate_public_surface($contents, $sourcePath, $token);
	}
	if ($kind === 'coordinator') {
		return build_benchmark_mutate_coordinator($contents, $sourcePath, $token);
	}
	if ($kind === 'release_hot') {
		return build_benchmark_mutate_private_like_source($contents, $token, 'SCPP_BENCH_RELEASE_EDIT_', 'release private-like literal edit');
	}
	return build_benchmark_mutate_private_like_source($contents, $token, 'SCPP_BENCH_PRIVATE_EDIT_', 'private-like literal edit');
}

/** @return array<string,mixed> */
function build_benchmark_mutate_private_like_source(string $contents, string $token, string $markerPrefix, string $description): array
{
	$count = 0;
	$markerPattern = '/' . preg_quote($markerPrefix, '/') . '[0-9A-Za-z_]+/';
	$changed = preg_replace($markerPattern, $markerPrefix . $token, $contents, 1, $count);
	if (is_string($changed) && $count > 0 && $changed !== $contents) {
		return [
			'changed' => true,
			'contents' => $changed,
			'strategy' => 'marker_replacement',
			'description' => $description . ' via ' . $markerPrefix . '* marker',
		];
	}

	$count = 0;
	$changed = preg_replace_callback(
		'/"((?:\\\\.|[^"\\\\])*)"/',
		static fn (array $matches): string => '"' . (string) $matches[1] . ' scpp-bench-' . $token . '"',
		$contents,
		1,
		$count
	);
	if (is_string($changed) && $count > 0 && $changed !== $contents) {
		return [
			'changed' => true,
			'contents' => $changed,
			'strategy' => 'string_literal_edit',
			'description' => $description . ' by changing the first string literal',
		];
	}

	$count = 0;
	$changed = preg_replace_callback(
		'/\b([0-9]+)\b/',
		static fn (array $matches): string => (string) (((int) $matches[1]) + 1),
		$contents,
		1,
		$count
	);
	if (is_string($changed) && $count > 0 && $changed !== $contents) {
		return [
			'changed' => true,
			'contents' => $changed,
			'strategy' => 'integer_literal_edit',
			'description' => $description . ' by changing the first integer literal',
		];
	}

	return [
		'changed' => false,
		'skip_reason' => 'no marker, string literal, or integer literal was found for a private-like edit',
	];
}

/** @return array<string,mixed> */
function build_benchmark_mutate_public_surface(string $contents, string $sourcePath, string $token): array
{
	$count = 0;
	$changed = preg_replace('/SCPP_BENCH_PUBLIC_EDIT_[0-9A-Za-z_]+/', 'SCPP_BENCH_PUBLIC_EDIT_' . $token, $contents, 1, $count);
	if (is_string($changed) && $count > 0 && $changed !== $contents) {
		return [
			'changed' => true,
			'contents' => $changed,
			'strategy' => 'marker_replacement',
			'description' => 'public surface marker replacement',
		];
	}

	$extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
	if ($extension !== 'phs' && $extension !== 'php') {
		return [
			'changed' => false,
			'skip_reason' => 'public surface fallback only supports PHP/PHS sources',
		];
	}
	$className = 'ScppBenchmarkPublicProbe' . preg_replace('/[^A-Za-z0-9_]/', '', $token);
	$after = rtrim($contents) . "\n\nclass " . $className . " {\n\tpublic function value(): int {\n\t\treturn 1;\n\t}\n}\n";
	return [
		'changed' => true,
		'contents' => $after,
		'strategy' => 'append_public_class',
		'description' => 'public surface edit by appending a small generated class',
	];
}

/** @return array<string,mixed> */
function build_benchmark_mutate_coordinator(string $contents, string $sourcePath, string $token): array
{
	$count = 0;
	$changed = preg_replace('/SCPP_BENCH_COORDINATOR_EDIT_[0-9A-Za-z_]+/', 'SCPP_BENCH_COORDINATOR_EDIT_' . $token, $contents, 1, $count);
	if (is_string($changed) && $count > 0 && $changed !== $contents) {
		return [
			'changed' => true,
			'contents' => $changed,
			'strategy' => 'marker_replacement',
			'description' => 'coordinator marker replacement',
		];
	}

	$extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
	if ($extension !== 'phs' && $extension !== 'php') {
		return [
			'changed' => false,
			'skip_reason' => 'coordinator fallback only supports PHP/PHS sources',
		];
	}
	return [
		'changed' => true,
		'contents' => rtrim($contents) . "\n\necho \"\";\n",
		'strategy' => 'append_empty_echo',
		'description' => 'coordinator edit by appending a no-output top-level statement',
	];
}

/** @return list<string> */
function render_build_benchmark_summary_lines(array $report): array
{
	$lines = [];
	$lines[] = 'Build benchmark: ' . (string) ($report['status'] ?? 'unknown');
	$lines[] = 'Saved report: ' . (string) ($report['report_path'] ?? '.prism/build_invalidation_benchmark.json');
	$lines[] = 'Work dir: ' . (string) ($report['work_dir'] ?? '.prism/build_benchmarks') . ((bool) ($report['work_dir_retained'] ?? false) ? ' (retained)' : ' (removed)');
	$lines[] = 'Scenarios:';
	foreach (is_array($report['scenarios'] ?? null) ? $report['scenarios'] : [] as $scenario) {
		if (!is_array($scenario)) {
			continue;
		}
		$name = (string) ($scenario['name'] ?? 'unknown');
		$status = (string) ($scenario['status'] ?? 'unknown');
		if ($status === 'skipped') {
			$lines[] = '  ' . $name . ': skipped - ' . (string) ($scenario['skip_reason'] ?? 'no reason recorded');
			continue;
		}
		$metrics = is_array($scenario['metrics'] ?? null) ? $scenario['metrics'] : [];
		$fanout = is_array($metrics['object_fanout'] ?? null) ? $metrics['object_fanout'] : [];
		$grouping = is_array($metrics['grouping_fanout'] ?? null) ? $metrics['grouping_fanout'] : [];
		$lines[] = sprintf(
			'  %s: %s, wall=%dms, ninja=%dms, transpiled=%d, reused=%d, rebuilt_objects=%d, changed_groups=%d',
			$name,
			$status,
			(int) ($metrics['wall_ms'] ?? 0),
			(int) ($metrics['ninja_subprocess_ms'] ?? 0),
			(int) ($metrics['transpiled_count'] ?? 0),
			(int) ($metrics['reused_count'] ?? 0),
			(int) ($fanout['rebuilt_object_count'] ?? 0),
			(int) ($grouping['changed_group_count'] ?? 0)
		);
	}
	return $lines;
}

/** @param list<string> $args @return array{build_options:array{compile_runtime:bool,compile_dependencies:bool,force_runtime_rebuild:bool,disable_stan:bool,show_timings:bool,entry_override:?string,build_mode:?string},run_args:list<string>} */
function parse_run_command_arguments(array $args): array
{
	$buildOptions = [
		'compile_runtime' => false,
		'compile_dependencies' => false,
		'force_runtime_rebuild' => false,
		'disable_stan' => false,
		'show_timings' => false,
		'entry_override' => null,
		'build_mode' => null,
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
		if (str_starts_with($arg, '--mode=')) {
			$buildOptions['build_mode'] = normalize_build_mode_name(substr($arg, strlen('--mode=')), '--mode');
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

/**
 * @param list<array{project_root:string,relative_php:string,generated_header?:string,generated_cpp:string,object_path:string,is_entrypoint:bool,force_include_header:?string}> $generatedUnits
 * @param list<array{project_root:string,source_path:string,object_path:string,force_include_header:?string}> $nativeCppUnits
 * @param array<string,mixed> $runtimeBuild
 * @param list<string> $rebuiltOutputs
 * @return array<string,mixed>
 */
function summarize_build_rebuild_fanout(string $projectRoot, array $generatedUnits, array $nativeCppUnits, array $runtimeBuild, array $rebuiltOutputs, array $projectUnitPackChanges): array
{
	$rebuiltSet = [];
	foreach ($rebuiltOutputs as $output) {
		$output = normalize_path($output);
		if ($output !== '') {
			$rebuiltSet[$output] = true;
		}
	}
	$generatedObjects = [];
	foreach ($generatedUnits as $unit) {
		$objectPath = normalize_path((string) ($unit['object_path'] ?? ''));
		if ($objectPath !== '' && isset($rebuiltSet[$objectPath])) {
			$generatedObjects[] = normalize_config_path(relative_path($projectRoot, $objectPath));
		}
	}
	$generatedObjects = array_values(array_unique($generatedObjects));
	sort($generatedObjects, SORT_STRING);
	$nativeObjects = [];
	foreach ($nativeCppUnits as $unit) {
		$objectPath = normalize_path((string) ($unit['object_path'] ?? ''));
		if ($objectPath !== '' && isset($rebuiltSet[$objectPath])) {
			$nativeObjects[] = normalize_config_path(relative_path($projectRoot, $objectPath));
		}
	}
	$nativeObjects = array_values(array_unique($nativeObjects));
	sort($nativeObjects, SORT_STRING);
	$runtimeObjects = [];
	$runtimeObjectPath = normalize_path((string) ($runtimeBuild['object_path'] ?? ''));
	if ($runtimeObjectPath !== '' && isset($rebuiltSet[$runtimeObjectPath])) {
		$runtimeObjects[] = normalize_config_path(relative_path($projectRoot, $runtimeObjectPath));
	}
	$knownObjects = [];
	foreach (array_merge($generatedObjects, $nativeObjects, $runtimeObjects) as $object) {
		$knownObjects[normalize_path($projectRoot . '/' . $object)] = true;
	}
	$otherOutputs = [];
	foreach ($rebuiltSet as $output => $_present) {
		if (isset($knownObjects[$output])) {
			continue;
		}
		$otherOutputs[] = normalize_config_path(relative_path($projectRoot, $output));
	}
	sort($otherOutputs, SORT_STRING);
	$packChanges = normalize_project_unit_pack_changes($projectUnitPackChanges);
	return [
		'rebuilt_output_count' => count($rebuiltSet),
		'rebuilt_object_count' => count($generatedObjects) + count($nativeObjects) + count($runtimeObjects),
		'rebuilt_generated_object_count' => count($generatedObjects),
		'rebuilt_native_object_count' => count($nativeObjects),
		'rebuilt_runtime_object_count' => count($runtimeObjects),
		'rebuilt_generated_objects' => $generatedObjects,
		'rebuilt_native_objects' => $nativeObjects,
		'rebuilt_runtime_objects' => array_values(array_unique($runtimeObjects)),
		'rebuilt_other_outputs' => array_values(array_unique($otherOutputs)),
		'changed_project_unit_pack_count' => (int) ($packChanges['changed_count'] ?? 0),
		'removed_project_unit_pack_count' => (int) ($packChanges['removed_count'] ?? 0),
		'changed_project_unit_pack_headers' => $packChanges['changed_headers'] ?? [],
		'removed_project_unit_pack_headers' => $packChanges['removed_headers'] ?? [],
		'ninja_no_work' => count($rebuiltSet) === 0,
	];
}

/** @return array<string,mixed> */
function normalize_build_rebuild_fanout(array $fanout): array
{
	return [
		'rebuilt_output_count' => max(0, (int) ($fanout['rebuilt_output_count'] ?? 0)),
		'rebuilt_object_count' => max(0, (int) ($fanout['rebuilt_object_count'] ?? 0)),
		'rebuilt_generated_object_count' => max(0, (int) ($fanout['rebuilt_generated_object_count'] ?? 0)),
		'rebuilt_native_object_count' => max(0, (int) ($fanout['rebuilt_native_object_count'] ?? 0)),
		'rebuilt_runtime_object_count' => max(0, (int) ($fanout['rebuilt_runtime_object_count'] ?? 0)),
		'rebuilt_generated_objects' => normalize_string_list($fanout['rebuilt_generated_objects'] ?? []),
		'rebuilt_native_objects' => normalize_string_list($fanout['rebuilt_native_objects'] ?? []),
		'rebuilt_runtime_objects' => normalize_string_list($fanout['rebuilt_runtime_objects'] ?? []),
		'rebuilt_other_outputs' => normalize_string_list($fanout['rebuilt_other_outputs'] ?? []),
		'changed_project_unit_pack_count' => max(0, (int) ($fanout['changed_project_unit_pack_count'] ?? 0)),
		'removed_project_unit_pack_count' => max(0, (int) ($fanout['removed_project_unit_pack_count'] ?? 0)),
		'changed_project_unit_pack_headers' => normalize_string_list($fanout['changed_project_unit_pack_headers'] ?? []),
		'removed_project_unit_pack_headers' => normalize_string_list($fanout['removed_project_unit_pack_headers'] ?? []),
		'ninja_no_work' => (bool) ($fanout['ninja_no_work'] ?? false),
	];
}

/** @param list<array<string,mixed>> $generatedUnits @param list<array<string,mixed>> $nativeCppUnits @return array<string,mixed> */
function collect_ninja_explain_report(string $projectRoot, string $buildDir, string $stdout, string $stderr, array $generatedUnits, array $nativeCppUnits): array
{
	$enabled = build_ninja_explain_requested();
	$source = build_ninja_explain_source_label();
	if (!$enabled) {
		return normalize_ninja_explain_report([
			'enabled' => false,
			'source' => '',
			'lines' => [],
			'object_explanations' => [],
		]);
	}

	$lookup = build_ninja_explain_object_lookup($projectRoot, $buildDir, $generatedUnits, $nativeCppUnits);
	$rows = [];
	$objectExplanations = [];
	$rawLines = preg_split('/\R/', $stderr . "\n" . $stdout);
	foreach (is_array($rawLines) ? $rawLines : [] as $line) {
		$line = trim((string) $line);
		if ($line === '' || !str_starts_with($line, 'ninja explain:')) {
			continue;
		}
		$message = trim(substr($line, strlen('ninja explain:')));
		if ($message === '') {
			continue;
		}
		$objects = match_ninja_explain_objects($message, $lookup);
		$rows[] = [
			'message' => $message,
			'objects' => $objects,
		];
		foreach ($objects as $objectPath) {
			if (!isset($objectExplanations[$objectPath])) {
				$objectExplanations[$objectPath] = [];
			}
			$objectExplanations[$objectPath][] = $message;
		}
	}

	return normalize_ninja_explain_report([
		'enabled' => true,
		'source' => $source,
		'lines' => $rows,
		'object_explanations' => $objectExplanations,
	]);
}

/** @param list<array<string,mixed>> $generatedUnits @param list<array<string,mixed>> $nativeCppUnits @return array<string,string> */
function build_ninja_explain_object_lookup(string $projectRoot, string $buildDir, array $generatedUnits, array $nativeCppUnits): array
{
	$lookup = [];
	foreach (array_merge($generatedUnits, $nativeCppUnits) as $unit) {
		if (!is_array($unit)) {
			continue;
		}
		$objectPath = normalize_path((string) ($unit['object_path'] ?? ''));
		if ($objectPath === '') {
			continue;
		}
		$projectRelative = normalize_config_path(relative_path($projectRoot, $objectPath));
		$buildRelative = normalize_config_path(relative_path($buildDir, $objectPath));
		foreach ([$projectRelative, $buildRelative] as $key) {
			if ($key !== '' && $key !== '.') {
				$lookup[$key] = $projectRelative;
			}
		}
	}
	uksort($lookup, static fn (string $left, string $right): int => strlen($right) <=> strlen($left));
	return $lookup;
}

/** @param array<string,string> $lookup @return list<string> */
function match_ninja_explain_objects(string $message, array $lookup): array
{
	$matches = [];
	foreach ($lookup as $needle => $objectPath) {
		if ($needle === '') {
			continue;
		}
		$pattern = '/(^|[\s,;:()\[\]])' . preg_quote($needle, '/') . '($|[\s,;:()\[\]])/';
		if (preg_match($pattern, $message) === 1) {
			$matches[$objectPath] = true;
		}
	}
	$result = array_keys($matches);
	sort($result, SORT_STRING);
	return $result;
}

/** @return array<string,mixed> */
function normalize_ninja_explain_report(array $report): array
{
	$rows = [];
	$objectExplanations = [];
	foreach (is_array($report['lines'] ?? null) ? $report['lines'] : [] as $row) {
		if (!is_array($row)) {
			continue;
		}
		$message = trim((string) ($row['message'] ?? ''));
		if ($message === '') {
			continue;
		}
		$objects = normalize_string_list($row['objects'] ?? []);
		$rows[] = [
			'message' => $message,
			'objects' => $objects,
		];
		foreach ($objects as $objectPath) {
			if (!isset($objectExplanations[$objectPath])) {
				$objectExplanations[$objectPath] = [];
			}
			$objectExplanations[$objectPath][] = $message;
		}
	}
	foreach (is_array($report['object_explanations'] ?? null) ? $report['object_explanations'] : [] as $objectPath => $messages) {
		if (!is_string($objectPath) || trim($objectPath) === '') {
			continue;
		}
		if (!isset($objectExplanations[$objectPath])) {
			$objectExplanations[$objectPath] = [];
		}
		foreach (normalize_string_list($messages) as $message) {
			$objectExplanations[$objectPath][] = $message;
		}
	}
	ksort($objectExplanations, SORT_STRING);
	foreach ($objectExplanations as $objectPath => $messages) {
		$objectExplanations[$objectPath] = normalize_string_list($messages);
	}
	return [
		'enabled' => (bool) ($report['enabled'] ?? false),
		'source' => trim((string) ($report['source'] ?? '')),
		'line_count' => count($rows),
		'object_count' => count($objectExplanations),
		'lines' => $rows,
		'object_explanations' => $objectExplanations,
	];
}

/**
 * @param list<array<string,mixed>> $generatedUnits
 * @param list<array<string,mixed>> $nativeCppUnits
 * @return array<string,mixed>
 */
function collect_object_action_identity_report(
	string $projectRoot,
	string $repoRoot,
	string $buildDir,
	string $generatedDir,
	array $generatedUnits,
	array $nativeCppUnits,
	array $compiler,
	string $buildMode,
	array $runtimeConfig,
	array $options,
	string $runtimePlacement,
	array $projectModuleReport = [],
	string $captureReason = 'requested'
): array {
	$normalizedProjectRoot = normalize_path($projectRoot);
	$usePch = array_key_exists('use_pch', $options) ? (bool) $options['use_pch'] : supports_compiler_pch($compiler);
	$generatedIncludeDir = build_ninja_relative_path($projectRoot, $buildDir, $generatedDir);
	$runtimeIncludeDir = build_ninja_relative_path($projectRoot, $buildDir, $repoRoot . '/runtime/include');
	$runtimeBuild = build_runtime_artifact_spec($repoRoot, $projectRoot, $compiler, $buildMode, $runtimeConfig, $runtimePlacement);
	$appRuntimeCxxFlags = is_array($runtimeBuild['extra_cxxflags'] ?? null) ? $runtimeBuild['extra_cxxflags'] : [];
	$clangTimeTraceFlags = resolve_clang_time_trace_flags($compiler);
	$appCxxFlags = trim(build_compiler_flags((string) ($compiler['kind'] ?? ''), $buildMode, $runtimeIncludeDir, $generatedIncludeDir) . ($appRuntimeCxxFlags !== [] ? ' ' . implode(' ', $appRuntimeCxxFlags) : '') . ($clangTimeTraceFlags !== [] ? ' ' . implode(' ', $clangTimeTraceFlags) : ''));
	$runtimeSignature = normalize_path($buildDir . '/runtime_signature.txt');
	$appPchArtifact = normalize_path(build_app_pch_artifact_path($buildDir, (string) ($compiler['kind'] ?? 'gnu_like')));
	$appPchHeader = normalize_path(build_app_pch_header_path($buildDir));
	$projectModuleCompileSurfaceDeps = collect_project_module_compile_surface_deps($projectModuleReport);
	$environment = collect_object_action_identity_environment();

	$generatedUnitsByObject = [];
	foreach ($generatedUnits as $unit) {
		if (!is_array($unit)) {
			continue;
		}
		$objectPath = normalize_path((string) ($unit['object_path'] ?? ''));
		if ($objectPath !== '') {
			$generatedUnitsByObject[$objectPath][] = $unit;
		}
	}

	$actions = [];
	$skippedDependencyObjectCount = 0;
	$emittedGeneratedObjects = [];
	foreach ($generatedUnits as $unit) {
		if (!is_array($unit)) {
			continue;
		}
		$unitProjectRoot = normalize_path((string) ($unit['project_root'] ?? ''));
		if (!($options['compile_dependencies'] ?? true) && $unitProjectRoot !== $normalizedProjectRoot) {
			$skippedDependencyObjectCount++;
			continue;
		}
		$objectPath = normalize_path((string) ($unit['object_path'] ?? ''));
		if ($objectPath === '' || isset($emittedGeneratedObjects[$objectPath])) {
			continue;
		}
		$emittedGeneratedObjects[$objectPath] = true;
		$members = $generatedUnitsByObject[$objectPath] ?? [$unit];
		$compileSource = is_string($unit['compile_source_path'] ?? null) && trim((string) $unit['compile_source_path']) !== ''
			? normalize_path((string) $unit['compile_source_path'])
			: normalize_path((string) ($unit['generated_cpp'] ?? ''));
		$implicitInputs = [$runtimeSignature];
		if ($usePch) {
			$implicitInputs[] = $appPchArtifact;
		}
		$logicalInputs = [$compileSource, $runtimeSignature];
		if ($usePch) {
			$logicalInputs[] = $appPchHeader;
			$logicalInputs[] = $appPchArtifact;
		}
		$forceIncludeHeaders = [];
		$moduleSurfaceInputs = [];
		$memberSources = [];
		$generatedInputs = [$compileSource];
		foreach ($members as $member) {
			if (!is_array($member)) {
				continue;
			}
			$memberProjectRoot = normalize_path((string) ($member['project_root'] ?? ''));
			$projectLabel = project_context_report_label($projectRoot, $memberProjectRoot);
			$relativePhp = normalize_config_path((string) ($member['relative_php'] ?? ''));
			if ($relativePhp !== '') {
				$memberSources[] = project_module_source_label($projectLabel, $relativePhp);
			}
			$memberGeneratedCpp = normalize_path((string) ($member['generated_cpp'] ?? ''));
			if ($memberGeneratedCpp !== '') {
				$generatedInputs[] = $memberGeneratedCpp;
				if ($memberGeneratedCpp !== $compileSource) {
					$implicitInputs[] = $memberGeneratedCpp;
				}
			}
			$memberGeneratedHeader = normalize_path((string) ($member['generated_header'] ?? ''));
			if ($memberGeneratedHeader !== '') {
				$generatedInputs[] = $memberGeneratedHeader;
				$implicitInputs[] = $memberGeneratedHeader;
				$logicalInputs[] = $memberGeneratedHeader;
			}
			$sourceKey = project_module_source_key($projectLabel, $relativePhp);
			foreach ($projectModuleCompileSurfaceDeps[$sourceKey] ?? [] as $moduleSurfaceDep) {
				$moduleSurfacePath = is_absolute_path($moduleSurfaceDep)
					? normalize_path($moduleSurfaceDep)
					: normalize_path($projectRoot . '/' . normalize_config_path($moduleSurfaceDep));
				$implicitInputs[] = $moduleSurfacePath;
				$moduleSurfaceInputs[] = $moduleSurfacePath;
			}
			$forceIncludeHeader = is_string($member['force_include_header'] ?? null) ? normalize_path((string) $member['force_include_header']) : '';
			if ($forceIncludeHeader !== '') {
				$forceIncludeHeaders[] = $forceIncludeHeader;
				$logicalInputs[] = $forceIncludeHeader;
			}
		}
		$logicalInputs = array_merge($logicalInputs, $generatedInputs, $implicitInputs, $moduleSurfaceInputs);
		$moreCxxFlags = build_force_include_flags((string) ($compiler['kind'] ?? ''), array_map(
			static fn (string $path): string => build_ninja_relative_path($projectRoot, $buildDir, $path),
			normalize_path_list($forceIncludeHeaders)
		));
		$actions[] = build_object_action_identity_row(
			$projectRoot,
			'generated',
			$objectPath,
			$compileSource,
			$memberSources,
			$generatedInputs,
			$implicitInputs,
			$forceIncludeHeaders,
			$moduleSurfaceInputs,
			$compiler,
			$buildMode,
			$appCxxFlags,
			$moreCxxFlags,
			$environment,
			$logicalInputs
		);
	}

	foreach ($nativeCppUnits as $nativeUnit) {
		if (!is_array($nativeUnit)) {
			continue;
		}
		$unitProjectRoot = normalize_path((string) ($nativeUnit['project_root'] ?? ''));
		if (!($options['compile_dependencies'] ?? true) && $unitProjectRoot !== $normalizedProjectRoot) {
			$skippedDependencyObjectCount++;
			continue;
		}
		$objectPath = normalize_path((string) ($nativeUnit['object_path'] ?? ''));
		$sourcePath = normalize_path((string) ($nativeUnit['source_path'] ?? ''));
		if ($objectPath === '' || $sourcePath === '') {
			continue;
		}
		$implicitInputs = [$runtimeSignature];
		$logicalInputs = [$sourcePath, $runtimeSignature];
		if ($usePch) {
			$implicitInputs[] = $appPchArtifact;
			$logicalInputs[] = $appPchHeader;
			$logicalInputs[] = $appPchArtifact;
		}
		$forceIncludeHeaders = [];
		$forceIncludeHeader = is_string($nativeUnit['force_include_header'] ?? null) ? normalize_path((string) $nativeUnit['force_include_header']) : '';
		if ($forceIncludeHeader !== '') {
			$forceIncludeHeaders[] = $forceIncludeHeader;
			$logicalInputs[] = $forceIncludeHeader;
		}
		$projectLabel = project_context_report_label($projectRoot, $unitProjectRoot);
		$sourceLabel = project_module_source_label($projectLabel, normalize_config_path(relative_path($unitProjectRoot, $sourcePath)));
		$moreCxxFlags = build_force_include_flags((string) ($compiler['kind'] ?? ''), array_map(
			static fn (string $path): string => build_ninja_relative_path($projectRoot, $buildDir, $path),
			normalize_path_list($forceIncludeHeaders)
		));
		$actions[] = build_object_action_identity_row(
			$projectRoot,
			'native',
			$objectPath,
			$sourcePath,
			[$sourceLabel],
			[$sourcePath],
			$implicitInputs,
			$forceIncludeHeaders,
			[],
			$compiler,
			$buildMode,
			$appCxxFlags,
			$moreCxxFlags,
			$environment,
			$logicalInputs
		);
	}

	return normalize_object_action_identity_report([
		'schema_version' => 1,
		'algorithm' => 'sha256:scpp-object-action-v1',
		'capture_mode' => 'full',
		'capture_reason' => $captureReason,
		'skipped_dependency_object_count' => $skippedDependencyObjectCount,
		'actions' => $actions,
	]);
}

/**
 * @param list<string> $memberSources
 * @param list<string> $generatedInputs
 * @param list<string> $implicitInputs
 * @param list<string> $forceIncludeHeaders
 * @param list<string> $moduleSurfaceInputs
 * @param list<string> $logicalInputs
 * @param array<string,string> $environment
 * @return array<string,mixed>
 */
function build_object_action_identity_row(
	string $projectRoot,
	string $kind,
	string $objectPath,
	string $primaryInput,
	array $memberSources,
	array $generatedInputs,
	array $implicitInputs,
	array $forceIncludeHeaders,
	array $moduleSurfaceInputs,
	array $compiler,
	string $buildMode,
	string $cxxFlags,
	string $moreCxxFlags,
	array $environment,
	array $logicalInputs
): array {
	$objectPath = normalize_path($objectPath);
	$primaryInput = normalize_path($primaryInput);
	$inputFingerprints = collect_object_action_input_fingerprints($projectRoot, $logicalInputs);
	$commandPayload = [
		'compiler' => compiler_display_command($compiler),
		'compiler_kind' => (string) ($compiler['kind'] ?? ''),
		'compiler_launcher' => is_string($compiler['launcher'] ?? null) ? (string) $compiler['launcher'] : '',
		'invocation_prefix' => compiler_invocation_prefix($compiler),
		'build_mode' => normalize_build_mode_name($buildMode, 'object action build mode'),
		'cxxflags' => $cxxFlags,
		'more_cxxflags' => $moreCxxFlags,
		'rule' => 'compile',
	];
	$inputHash = hash_project_module_payload([
		'version' => 1,
		'inputs' => $inputFingerprints,
	]);
	$commandHash = hash_project_module_payload([
		'version' => 1,
		'command' => $commandPayload,
		'environment' => $environment,
	]);
	$payload = [
		'version' => 1,
		'kind' => $kind,
		'object_path' => normalize_config_path(relative_path($projectRoot, $objectPath)),
		'primary_input' => normalize_config_path(relative_path($projectRoot, $primaryInput)),
		'member_sources' => normalize_string_list($memberSources),
		'generated_inputs' => relative_path_list($projectRoot, $generatedInputs),
		'implicit_inputs' => relative_path_list($projectRoot, $implicitInputs),
		'force_include_headers' => relative_path_list($projectRoot, $forceIncludeHeaders),
		'module_surface_inputs' => relative_path_list($projectRoot, $moduleSurfaceInputs),
		'command_hash' => $commandHash,
		'input_hash' => $inputHash,
	];
	return [
		'kind' => $kind,
		'object_path' => normalize_config_path(relative_path($projectRoot, $objectPath)),
		'action_key' => hash_project_module_payload($payload),
		'command_hash' => $commandHash,
		'input_hash' => $inputHash,
		'output_hash' => existing_file_sha256($objectPath) ?? '',
		'compiler' => (string) ($commandPayload['compiler'] ?? ''),
		'compiler_kind' => (string) ($commandPayload['compiler_kind'] ?? ''),
		'build_mode' => (string) ($commandPayload['build_mode'] ?? ''),
		'primary_input' => normalize_config_path(relative_path($projectRoot, $primaryInput)),
		'member_sources' => normalize_string_list($memberSources),
		'generated_inputs' => relative_path_list($projectRoot, $generatedInputs),
		'implicit_inputs' => relative_path_list($projectRoot, $implicitInputs),
		'force_include_headers' => relative_path_list($projectRoot, $forceIncludeHeaders),
		'module_surface_inputs' => relative_path_list($projectRoot, $moduleSurfaceInputs),
		'command' => $commandPayload,
		'environment' => $environment,
		'input_fingerprints' => $inputFingerprints,
	];
}

/** @return array<string,string> */
function collect_object_action_identity_environment(): array
{
	$processEnvironment = scpp_build_process_environment();
	$keys = ['PATH', 'CC', 'CXX', 'CXXFLAGS', 'CPPFLAGS', 'TMP', 'TEMP', 'TMPDIR'];
	$result = [];
	foreach ($keys as $key) {
		if (is_string($processEnvironment[$key] ?? null) && trim((string) $processEnvironment[$key]) !== '') {
			$result[$key] = (string) $processEnvironment[$key];
		}
	}
	ksort($result, SORT_STRING);
	return $result;
}

/** @param list<string> $paths @return list<array{path:string,exists:bool,sha256:string}> */
function collect_object_action_input_fingerprints(string $projectRoot, array $paths): array
{
	$rows = [];
	foreach (normalize_path_list($paths) as $path) {
		$rows[] = [
			'path' => normalize_config_path(relative_path($projectRoot, $path)),
			'exists' => is_file($path),
			'sha256' => existing_file_sha256($path) ?? '',
		];
	}
	usort($rows, static fn (array $left, array $right): int => strcmp((string) ($left['path'] ?? ''), (string) ($right['path'] ?? '')));
	return $rows;
}

/** @param list<string> $paths @return list<string> */
function normalize_path_list(array $paths): array
{
	$result = [];
	foreach ($paths as $path) {
		if (!is_string($path)) {
			continue;
		}
		$normalized = normalize_path($path);
		if ($normalized !== '') {
			$result[] = $normalized;
		}
	}
	sort($result, SORT_STRING);
	return array_values(array_unique($result));
}

/** @param list<string> $paths @return list<string> */
function relative_path_list(string $projectRoot, array $paths): array
{
	$result = [];
	foreach (normalize_path_list($paths) as $path) {
		$result[] = normalize_config_path(relative_path($projectRoot, $path));
	}
	sort($result, SORT_STRING);
	return array_values(array_unique($result));
}

/** @return array<string,mixed> */
function normalize_object_action_identity_report(array $report): array
{
	$actions = normalize_object_action_identity_rows(is_array($report['actions'] ?? null) ? $report['actions'] : []);
	$captureMode = strtolower(trim((string) ($report['capture_mode'] ?? '')));
	if (!in_array($captureMode, ['off', 'full'], true)) {
		$captureMode = $actions !== [] ? 'full' : 'off';
	}
	$captureReason = trim((string) ($report['capture_reason'] ?? ''));
	if ($captureReason === '') {
		$captureReason = $captureMode === 'full' ? 'legacy or requested full report' : 'not captured';
	}
	$generatedCount = 0;
	$nativeCount = 0;
	foreach ($actions as $action) {
		if (($action['kind'] ?? '') === 'generated') {
			$generatedCount++;
		} elseif (($action['kind'] ?? '') === 'native') {
			$nativeCount++;
		}
	}
	return [
		'schema_version' => max(1, (int) ($report['schema_version'] ?? 1)),
		'algorithm' => trim((string) ($report['algorithm'] ?? 'sha256:scpp-object-action-v1')),
		'capture_mode' => $captureMode,
		'capture_reason' => $captureReason,
		'total_action_count' => count($actions),
		'generated_action_count' => $generatedCount,
		'native_action_count' => $nativeCount,
		'skipped_dependency_object_count' => max(0, (int) ($report['skipped_dependency_object_count'] ?? 0)),
		'actions' => $actions,
	];
}

/** @return list<array<string,mixed>> */
function normalize_object_action_identity_rows(array $rows): array
{
	$result = [];
	foreach ($rows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$objectPath = normalize_config_path((string) ($row['object_path'] ?? ''));
		if ($objectPath === '') {
			continue;
		}
		$kind = trim((string) ($row['kind'] ?? 'generated'));
		if (!in_array($kind, ['generated', 'native'], true)) {
			$kind = 'generated';
		}
		$result[] = [
			'kind' => $kind,
			'object_path' => $objectPath,
			'action_key' => trim((string) ($row['action_key'] ?? '')),
			'command_hash' => trim((string) ($row['command_hash'] ?? '')),
			'input_hash' => trim((string) ($row['input_hash'] ?? '')),
			'output_hash' => trim((string) ($row['output_hash'] ?? '')),
			'compiler' => trim((string) ($row['compiler'] ?? '')),
			'compiler_kind' => trim((string) ($row['compiler_kind'] ?? '')),
			'build_mode' => trim((string) ($row['build_mode'] ?? '')),
			'primary_input' => normalize_config_path((string) ($row['primary_input'] ?? '')),
			'member_sources' => normalize_string_list($row['member_sources'] ?? []),
			'generated_inputs' => normalize_string_list($row['generated_inputs'] ?? []),
			'implicit_inputs' => normalize_string_list($row['implicit_inputs'] ?? []),
			'force_include_headers' => normalize_string_list($row['force_include_headers'] ?? []),
			'module_surface_inputs' => normalize_string_list($row['module_surface_inputs'] ?? []),
			'command' => is_array($row['command'] ?? null) ? $row['command'] : [],
			'environment' => normalize_string_map(is_array($row['environment'] ?? null) ? $row['environment'] : []),
			'input_fingerprints' => normalize_object_action_input_fingerprint_rows(is_array($row['input_fingerprints'] ?? null) ? $row['input_fingerprints'] : []),
		];
	}
	usort($result, static fn (array $left, array $right): int => strcmp((string) ($left['object_path'] ?? ''), (string) ($right['object_path'] ?? '')));
	return $result;
}

/** @return array<string,string> */
function normalize_string_map(array $values): array
{
	$result = [];
	foreach ($values as $key => $value) {
		if (is_string($key) && trim($key) !== '' && is_scalar($value)) {
			$result[$key] = (string) $value;
		}
	}
	ksort($result, SORT_STRING);
	return $result;
}

/** @return list<array{path:string,exists:bool,sha256:string}> */
function normalize_object_action_input_fingerprint_rows(array $rows): array
{
	$result = [];
	foreach ($rows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$path = normalize_config_path((string) ($row['path'] ?? ''));
		if ($path === '') {
			continue;
		}
		$result[] = [
			'path' => $path,
			'exists' => (bool) ($row['exists'] ?? false),
			'sha256' => trim((string) ($row['sha256'] ?? '')),
		];
	}
	usort($result, static fn (array $left, array $right): int => strcmp((string) ($left['path'] ?? ''), (string) ($right['path'] ?? '')));
	return $result;
}

/** @return array{enabled:bool,source:string} */
function resolve_build_object_cache_config(array $config): array
{
	$build = is_array($config['build'] ?? null) ? $config['build'] : [];
	if (!array_key_exists('object_cache', $build)) {
		return [
			'enabled' => false,
			'source' => 'default disabled',
		];
	}
	if (!is_bool($build['object_cache'])) {
		scpp_fail('Invalid build.object_cache in ' . SCPP_PROJECT_CONFIG . ': expected a boolean.' . PHP_EOL, 2);
	}
	return [
		'enabled' => (bool) $build['object_cache'],
		'source' => 'build.object_cache',
	];
}

/** @return array{enabled:bool,source:string} */
function resolve_build_object_action_identity_config(array $config): array
{
	$env = getenv('SCPP_OBJECT_ACTION_IDENTITY');
	if (is_string($env) && trim($env) !== '') {
		$value = strtolower(trim($env));
		if (in_array($value, ['1', 'true', 'yes', 'on', 'full'], true)) {
			return [
				'enabled' => true,
				'source' => 'SCPP_OBJECT_ACTION_IDENTITY',
			];
		}
		if (in_array($value, ['0', 'false', 'no', 'off', 'none'], true)) {
			return [
				'enabled' => false,
				'source' => 'SCPP_OBJECT_ACTION_IDENTITY',
			];
		}
		scpp_fail('Invalid SCPP_OBJECT_ACTION_IDENTITY value `' . $env . '`; expected `full`, `on`, `1`, `off`, or `0`.' . PHP_EOL, 2);
	}

	$build = is_array($config['build'] ?? null) ? $config['build'] : [];
	if (!array_key_exists('object_action_identity', $build)) {
		return [
			'enabled' => false,
			'source' => 'default lightweight report',
		];
	}
	if (!is_bool($build['object_action_identity'])) {
		scpp_fail('Invalid build.object_action_identity in ' . SCPP_PROJECT_CONFIG . ': expected a boolean.' . PHP_EOL, 2);
	}
	return [
		'enabled' => (bool) $build['object_action_identity'],
		'source' => 'build.object_action_identity',
	];
}

/** @param array{enabled:bool,source:string} $objectCacheConfig */
function object_action_identity_pre_ninja_capture_reason(array $objectCacheConfig): ?string
{
	if ((bool) ($objectCacheConfig['enabled'] ?? false)) {
		return 'build.object_cache pre-Ninja restore';
	}
	return null;
}

/**
 * @param array{enabled:bool,source:string} $objectCacheConfig
 * @param array{enabled:bool,source:string} $objectActionIdentityConfig
 */
function object_action_identity_success_capture_reason(array $objectCacheConfig, array $objectActionIdentityConfig): ?string
{
	if ((bool) ($objectCacheConfig['enabled'] ?? false)) {
		return 'build.object_cache post-Ninja store';
	}
	if ((bool) ($objectActionIdentityConfig['enabled'] ?? false)) {
		return (string) ($objectActionIdentityConfig['source'] ?? 'object action identity opt-in');
	}
	if (build_ninja_explain_requested()) {
		return build_ninja_explain_source_label() . ' diagnostics';
	}
	if (build_ninja_explain_probe_requested()) {
		return build_ninja_explain_source_label() . ' diagnostics';
	}
	return null;
}

/** @return array<string,mixed> */
function create_object_action_identity_placeholder_report(string $reason): array
{
	return normalize_object_action_identity_report([
		'schema_version' => 1,
		'algorithm' => 'sha256:scpp-object-action-v1',
		'capture_mode' => 'off',
		'capture_reason' => $reason,
		'skipped_dependency_object_count' => 0,
		'actions' => [],
	]);
}

/** @return array{enabled:bool,source:string} */
function resolve_build_project_unit_scoped_packs_config(array $config): array
{
	$env = getenv('SCPP_PROJECT_UNIT_SCOPED_PACKS');
	if (is_string($env) && trim($env) !== '') {
		$value = strtolower(trim($env));
		if (in_array($value, ['1', 'true', 'yes', 'on'], true)) {
			return [
				'enabled' => true,
				'source' => 'SCPP_PROJECT_UNIT_SCOPED_PACKS',
			];
		}
		if (in_array($value, ['0', 'false', 'no', 'off'], true)) {
			return [
				'enabled' => false,
				'source' => 'SCPP_PROJECT_UNIT_SCOPED_PACKS',
			];
		}
		scpp_fail('Invalid SCPP_PROJECT_UNIT_SCOPED_PACKS value `' . $env . '`; expected `on`, `1`, `off`, or `0`.' . PHP_EOL, 2);
	}

	$build = is_array($config['build'] ?? null) ? $config['build'] : [];
	if (!array_key_exists('project_unit_scoped_packs', $build)) {
		return [
			'enabled' => false,
			'source' => 'default broad project-unit packs',
		];
	}
	if (!is_bool($build['project_unit_scoped_packs'])) {
		scpp_fail('Invalid build.project_unit_scoped_packs in ' . SCPP_PROJECT_CONFIG . ': expected a boolean.' . PHP_EOL, 2);
	}
	return [
		'enabled' => (bool) $build['project_unit_scoped_packs'],
		'source' => 'build.project_unit_scoped_packs',
	];
}

/**
 * @param array{enabled:bool,source:string} $config
 * @return array<string,mixed>
 */
function restore_object_action_cache_entries(string $projectRoot, string $cacheDir, array $config, array $objectActionIdentityReport): array
{
	$enabled = (bool) ($config['enabled'] ?? false);
	$report = normalize_object_cache_report([
		'enabled' => $enabled,
		'status' => $enabled ? 'enabled' : 'disabled',
		'source' => (string) ($config['source'] ?? 'default disabled'),
		'cache_dir' => normalize_config_path(relative_path($projectRoot, $cacheDir . '/object_actions')),
		'restore' => [
			'rows' => [],
		],
		'store' => [
			'rows' => [],
		],
	]);
	if (!$enabled) {
		return $report;
	}

	$rows = [];
	foreach (normalize_object_action_identity_rows(is_array($objectActionIdentityReport['actions'] ?? null) ? $objectActionIdentityReport['actions'] : []) as $action) {
		$rows[] = restore_object_action_cache_entry($projectRoot, $cacheDir, $action);
	}
	$report['restore'] = normalize_object_cache_restore_report(['rows' => $rows]);
	return normalize_object_cache_report($report);
}

/**
 * @param array<string,mixed> $action
 * @return array<string,mixed>
 */
function restore_object_action_cache_entry(string $projectRoot, string $cacheDir, array $action): array
{
	$objectPath = normalize_config_path((string) ($action['object_path'] ?? ''));
	$actionKey = trim((string) ($action['action_key'] ?? ''));
	$row = [
		'object_path' => $objectPath,
		'action_key' => $actionKey,
		'status' => 'skipped',
		'reason' => '',
		'cache_object' => '',
		'output_hash' => '',
	];
	if (!object_action_cache_key_is_valid($actionKey)) {
		$row['reason'] = 'invalid action key';
		return $row;
	}
	$objectAbs = object_action_project_output_path($projectRoot, $objectPath);
	if ($objectAbs === null) {
		$row['reason'] = 'object path outside project root';
		return $row;
	}
	$paths = build_object_action_cache_entry_paths($projectRoot, $cacheDir, $actionKey, $objectPath);
	$row['cache_object'] = $paths['object_path_relative'];
	if (!is_file($paths['object_path']) || !is_file($paths['metadata_path'])) {
		$row['status'] = 'miss';
		$row['reason'] = 'cache entry missing';
		return $row;
	}
	$metadata = read_json_file($paths['metadata_path']);
	if (!object_action_cache_metadata_matches($metadata, $action)) {
		$row['reason'] = 'cache metadata does not match action identity';
		return $row;
	}
	if (!cached_object_looks_valid($paths['object_path'])) {
		$row['reason'] = 'cached object is not a valid native object artifact';
		return $row;
	}
	$cachedHash = existing_file_sha256($paths['object_path']) ?? '';
	$expectedHash = trim((string) ($metadata['output_hash'] ?? ''));
	if ($cachedHash === '' || $expectedHash === '' || !hash_equals($expectedHash, $cachedHash)) {
		$row['reason'] = 'cached object hash does not match metadata';
		return $row;
	}
	$row['output_hash'] = $cachedHash;
	$currentHash = existing_file_sha256($objectAbs) ?? '';
	if ($currentHash !== '' && hash_equals($cachedHash, $currentHash)) {
		$row['status'] = 'already_current';
		$row['reason'] = 'object already matches cached output';
		return $row;
	}
	copy_file_atomically($paths['object_path'], $objectAbs, 'Failed to restore object cache entry');
	touch_restored_object_after_inputs($projectRoot, $objectAbs, $action);
	$row['status'] = 'restored';
	$row['reason'] = 'restored object from local action cache';
	return $row;
}

/**
 * @param array{enabled:bool,source:string} $config
 * @return array<string,mixed>
 */
function store_object_action_cache_entries(string $projectRoot, string $cacheDir, array $config, array $objectActionIdentityReport, array $existingReport = []): array
{
	$report = normalize_object_cache_report($existingReport);
	if (!(bool) ($config['enabled'] ?? false)) {
		return $report;
	}
	$rows = [];
	foreach (normalize_object_action_identity_rows(is_array($objectActionIdentityReport['actions'] ?? null) ? $objectActionIdentityReport['actions'] : []) as $action) {
		$rows[] = store_object_action_cache_entry($projectRoot, $cacheDir, $action);
	}
	$report['store'] = normalize_object_cache_store_report(['rows' => $rows]);
	return normalize_object_cache_report($report);
}

/**
 * @param array<string,mixed> $action
 * @return array<string,mixed>
 */
function store_object_action_cache_entry(string $projectRoot, string $cacheDir, array $action): array
{
	$objectPath = normalize_config_path((string) ($action['object_path'] ?? ''));
	$actionKey = trim((string) ($action['action_key'] ?? ''));
	$row = [
		'object_path' => $objectPath,
		'action_key' => $actionKey,
		'status' => 'skipped',
		'reason' => '',
		'cache_object' => '',
		'output_hash' => trim((string) ($action['output_hash'] ?? '')),
	];
	if (!object_action_cache_key_is_valid($actionKey)) {
		$row['reason'] = 'invalid action key';
		return $row;
	}
	$objectAbs = object_action_project_output_path($projectRoot, $objectPath);
	if ($objectAbs === null) {
		$row['reason'] = 'object path outside project root';
		return $row;
	}
	if (!is_file($objectAbs)) {
		$row['reason'] = 'object output missing';
		return $row;
	}
	if (!cached_object_looks_valid($objectAbs)) {
		$row['reason'] = 'object output is not a valid native object artifact';
		return $row;
	}
	$outputHash = existing_file_sha256($objectAbs) ?? '';
	if ($outputHash === '') {
		$row['reason'] = 'object output hash unavailable';
		return $row;
	}
	$row['output_hash'] = $outputHash;
	$paths = build_object_action_cache_entry_paths($projectRoot, $cacheDir, $actionKey, $objectPath);
	$row['cache_object'] = $paths['object_path_relative'];
	$metadata = build_object_action_cache_metadata($action, $outputHash);
	$cachedHash = existing_file_sha256($paths['object_path']) ?? '';
	$cachedMetadata = read_json_file($paths['metadata_path']);
	if ($cachedHash !== '' && hash_equals($outputHash, $cachedHash) && object_action_cache_metadata_matches($cachedMetadata, $action)) {
		$row['status'] = 'preserved';
		$row['reason'] = 'cache entry already matches object output';
		return $row;
	}
	copy_file_atomically($objectAbs, $paths['object_path'], 'Failed to store object cache entry');
	write_json_file_atomic($paths['metadata_path'], $metadata);
	$row['status'] = 'stored';
	$row['reason'] = 'stored object output in local action cache';
	return $row;
}

/** @return array<string,mixed> */
function build_object_action_cache_metadata(array $action, string $outputHash): array
{
	return [
		'schema_version' => 1,
		'action_key' => trim((string) ($action['action_key'] ?? '')),
		'kind' => trim((string) ($action['kind'] ?? '')),
		'object_path' => normalize_config_path((string) ($action['object_path'] ?? '')),
		'command_hash' => trim((string) ($action['command_hash'] ?? '')),
		'input_hash' => trim((string) ($action['input_hash'] ?? '')),
		'output_hash' => $outputHash,
		'stored_at' => time(),
	];
}

function object_action_cache_metadata_matches(?array $metadata, array $action): bool
{
	if (!is_array($metadata)) {
		return false;
	}
	foreach (['action_key', 'command_hash', 'input_hash'] as $key) {
		if (!hash_equals(trim((string) ($action[$key] ?? '')), trim((string) ($metadata[$key] ?? '')))) {
			return false;
		}
	}
	return true;
}

function object_action_cache_key_is_valid(string $actionKey): bool
{
	return preg_match('/^[0-9a-f]{64}$/', $actionKey) === 1;
}

function object_action_project_output_path(string $projectRoot, string $objectPath): ?string
{
	$relative = normalize_config_path($objectPath);
	if ($relative === '' || $relative === '..' || str_starts_with($relative, '../')) {
		return null;
	}
	$projectRoot = normalize_path($projectRoot);
	$absolute = normalize_path($projectRoot . '/' . $relative);
	if ($absolute !== $projectRoot && !str_starts_with($absolute, $projectRoot . '/')) {
		return null;
	}
	return $absolute;
}

/** @return array{object_path:string,metadata_path:string,object_path_relative:string,metadata_path_relative:string} */
function build_object_action_cache_entry_paths(string $projectRoot, string $cacheDir, string $actionKey, string $objectPath): array
{
	$extension = pathinfo($objectPath, PATHINFO_EXTENSION);
	if (!is_string($extension) || trim($extension) === '') {
		$extension = 'object';
	}
	$extension = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $extension);
	$extension = is_string($extension) && $extension !== '' ? $extension : 'object';
	$base = normalize_path($cacheDir . '/object_actions/' . substr($actionKey, 0, 2) . '/' . $actionKey);
	$objectCachePath = $base . '.' . $extension;
	$metadataPath = $base . '.json';
	return [
		'object_path' => $objectCachePath,
		'metadata_path' => $metadataPath,
		'object_path_relative' => normalize_config_path(relative_path($projectRoot, $objectCachePath)),
		'metadata_path_relative' => normalize_config_path(relative_path($projectRoot, $metadataPath)),
	];
}

function copy_file_atomically(string $sourcePath, string $destinationPath, string $failureMessage): void
{
	$destinationDir = dirname($destinationPath);
	if (!is_dir($destinationDir)) {
		ensure_directory($destinationDir);
	}
	$tmpPath = $destinationPath . '.tmp.' . bin2hex(random_bytes(4));
	if (!@copy($sourcePath, $tmpPath)) {
		@unlink($tmpPath);
		scpp_fail($failureMessage . ': ' . $destinationPath . PHP_EOL, 2);
	}
	replace_file_atomically($tmpPath, $destinationPath, $failureMessage);
	@touch($destinationPath);
}

/** @param array<string,mixed> $action */
function touch_restored_object_after_inputs(string $projectRoot, string $objectPath, array $action): void
{
	$mtime = time();
	foreach (normalize_object_action_input_fingerprint_rows(is_array($action['input_fingerprints'] ?? null) ? $action['input_fingerprints'] : []) as $fingerprint) {
		$inputPath = object_action_project_output_path($projectRoot, (string) ($fingerprint['path'] ?? ''));
		if ($inputPath === null || !is_file($inputPath)) {
			continue;
		}
		$inputMtime = filemtime($inputPath);
		if (is_int($inputMtime)) {
			$mtime = max($mtime, $inputMtime + 1);
		}
	}
	@touch($objectPath, $mtime);
}

/** @return array<string,mixed> */
function normalize_object_cache_report(array $report): array
{
	$enabled = (bool) ($report['enabled'] ?? false);
	return [
		'enabled' => $enabled,
		'status' => $enabled ? 'enabled' : 'disabled',
		'source' => trim((string) ($report['source'] ?? ($enabled ? 'build.object_cache' : 'default disabled'))),
		'cache_dir' => normalize_config_path((string) ($report['cache_dir'] ?? '')),
		'restore' => normalize_object_cache_restore_report(is_array($report['restore'] ?? null) ? $report['restore'] : []),
		'store' => normalize_object_cache_store_report(is_array($report['store'] ?? null) ? $report['store'] : []),
	];
}

/** @return array<string,mixed> */
function normalize_object_cache_restore_report(array $report): array
{
	$rows = normalize_object_cache_rows(is_array($report['rows'] ?? null) ? $report['rows'] : []);
	$counts = object_cache_status_counts($rows);
	return [
		'action_count' => count($rows),
		'hit_count' => (int) ($counts['restored'] ?? 0) + (int) ($counts['already_current'] ?? 0),
		'miss_count' => (int) ($counts['miss'] ?? 0),
		'restored_count' => (int) ($counts['restored'] ?? 0),
		'already_current_count' => (int) ($counts['already_current'] ?? 0),
		'skipped_count' => (int) ($counts['skipped'] ?? 0),
		'rows' => $rows,
	];
}

/** @return array<string,mixed> */
function normalize_object_cache_store_report(array $report): array
{
	$rows = normalize_object_cache_rows(is_array($report['rows'] ?? null) ? $report['rows'] : []);
	$counts = object_cache_status_counts($rows);
	return [
		'action_count' => count($rows),
		'stored_count' => (int) ($counts['stored'] ?? 0),
		'preserved_count' => (int) ($counts['preserved'] ?? 0),
		'skipped_count' => (int) ($counts['skipped'] ?? 0),
		'rows' => $rows,
	];
}

/** @return list<array<string,mixed>> */
function normalize_object_cache_rows(array $rows): array
{
	$result = [];
	foreach ($rows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$objectPath = normalize_config_path((string) ($row['object_path'] ?? ''));
		$actionKey = trim((string) ($row['action_key'] ?? ''));
		if ($objectPath === '' && $actionKey === '') {
			continue;
		}
		$result[] = [
			'object_path' => $objectPath,
			'action_key' => $actionKey,
			'status' => trim((string) ($row['status'] ?? 'skipped')),
			'reason' => trim((string) ($row['reason'] ?? '')),
			'cache_object' => normalize_config_path((string) ($row['cache_object'] ?? '')),
			'output_hash' => trim((string) ($row['output_hash'] ?? '')),
		];
	}
	usort($result, static fn (array $left, array $right): int => strcmp((string) ($left['object_path'] ?? ''), (string) ($right['object_path'] ?? '')));
	return $result;
}

/** @return array<string,int> */
function object_cache_status_counts(array $rows): array
{
	$counts = [];
	foreach ($rows as $row) {
		$status = trim((string) ($row['status'] ?? 'skipped'));
		if ($status === '') {
			$status = 'skipped';
		}
		$counts[$status] = (int) ($counts[$status] ?? 0) + 1;
	}
	return $counts;
}

function object_cache_report_has_restored_objects(array $report): bool
{
	$restore = is_array($report['restore'] ?? null) ? $report['restore'] : [];
	return (int) ($restore['restored_count'] ?? 0) > 0;
}

/** @return array<string,string> */
function object_cache_restored_cache_inputs_by_object(array $report): array
{
	$restore = is_array($report['restore'] ?? null) ? $report['restore'] : [];
	$result = [];
	foreach (normalize_object_cache_rows(is_array($restore['rows'] ?? null) ? $restore['rows'] : []) as $row) {
		if (($row['status'] ?? '') !== 'restored') {
			continue;
		}
		$objectPath = normalize_config_path((string) ($row['object_path'] ?? ''));
		$cacheObject = normalize_config_path((string) ($row['cache_object'] ?? ''));
		if ($objectPath !== '' && $cacheObject !== '') {
			$result[$objectPath] = $cacheObject;
		}
	}
	ksort($result, SORT_STRING);
	return $result;
}

function strip_ninja_explain_lines(string $text): string
{
	if ($text === '') {
		return '';
	}
	$kept = [];
	foreach (preg_split('/\R/', $text) ?: [] as $line) {
		if (!str_starts_with(trim((string) $line), 'ninja explain:')) {
			$kept[] = (string) $line;
		}
	}
	return $kept === [] ? '' : rtrim(implode(PHP_EOL, $kept), PHP_EOL) . PHP_EOL;
}

/** @return array<string,mixed> */
function resolve_build_grouping_policy(array $config, string $buildMode): array
{
	$build = is_array($config['build'] ?? null) ? $config['build'] : [];
	$rawPolicy = $build['grouping_policy'] ?? null;
	$source = 'implicit ' . normalize_build_mode_name($buildMode, 'build grouping mode') . ' default';
	if ($rawPolicy === null) {
		$policy = normalize_build_mode_name($buildMode, 'build grouping mode') === 'release' ? 'release' : 'incremental';
	} elseif (is_string($rawPolicy)) {
		$policy = strtolower(trim($rawPolicy));
		$source = 'build.grouping_policy';
	} else {
		scpp_fail('Invalid build.grouping_policy in ' . SCPP_PROJECT_CONFIG . ': expected a string.' . PHP_EOL, 2);
	}

	$allowed = ['incremental', 'isolated', 'package', 'folder', 'manual', 'module', 'release', 'auto', 'none'];
	if (!in_array($policy, $allowed, true)) {
		scpp_fail('Invalid build.grouping_policy `' . $policy . '` in ' . SCPP_PROJECT_CONFIG . '. Use one of: ' . implode(', ', $allowed) . '.' . PHP_EOL, 2);
	}

	$notes = [
		'default compile graph emits one generated/native object per source',
		'policy membership is deterministic; grouped compile edges require explicit opt-in',
	];
	$manualGroups = [];
	if ($policy === 'manual') {
		$manualGroups = normalize_build_grouping_manual_config($build['grouping'] ?? null);
		$notes[] = 'manual group maps are report-only; unassigned root sources are reported as isolated groups';
	}
	$rawGroupingCompile = $build['grouping_compile'] ?? false;
	if (!is_bool($rawGroupingCompile)) {
		scpp_fail('Invalid build.grouping_compile in ' . SCPP_PROJECT_CONFIG . ': expected a boolean.' . PHP_EOL, 2);
	}
	$buildModeName = normalize_build_mode_name($buildMode, 'build grouping mode');
	$releaseGroupingPolicies = ['folder', 'package', 'module', 'release', 'auto'];
	if ($rawGroupingCompile && $policy !== 'manual' && !in_array($policy, $releaseGroupingPolicies, true)) {
		scpp_fail('Invalid build.grouping_compile in ' . SCPP_PROJECT_CONFIG . ': grouped generated object edges currently require build.grouping_policy = "manual", "folder", "package", "module", "release", or "auto".' . PHP_EOL, 2);
	}
	if ($rawGroupingCompile && $policy !== 'manual' && $buildModeName !== 'release') {
		scpp_fail('Invalid build.grouping_compile in ' . SCPP_PROJECT_CONFIG . ': non-manual grouped generated object edges require release build mode.' . PHP_EOL, 2);
	}
	$generatedGroupingEnabled = $rawGroupingCompile && ($policy === 'manual' || in_array($policy, $releaseGroupingPolicies, true));
	if ($generatedGroupingEnabled && $policy === 'manual') {
		$notes[] = 'manual groups with at least two generated root sources emit one grouped generated object edge';
		$notes[] = 'native C++ units and unassigned sources still compile as per-source objects';
	} elseif ($generatedGroupingEnabled && $policy === 'module') {
		$notes[] = 'release-mode module grouping emits grouped generated object edges for explicit project module sources';
		$notes[] = 'entrypoints, native C++ units, dependency sources, unassigned sources, and singleton module groups still compile as per-source objects';
	} elseif ($generatedGroupingEnabled) {
		$notes[] = 'release-mode folder/package/release/auto grouping emits grouped generated object edges for root generated sources';
		$notes[] = 'entrypoints, native C++ units, dependency sources, and singleton groups still compile as per-source objects';
	}

	return [
		'policy' => $policy,
		'source' => $source,
		'status' => $generatedGroupingEnabled ? 'active_generated_edges' : 'report_only',
		'build_mode' => $buildModeName,
		'compile_unit_strategy' => $generatedGroupingEnabled
			? ($policy === 'manual' ? 'manual_grouped_generated_objects' : ($policy === 'module' ? 'module_grouped_generated_objects' : 'release_grouped_generated_objects'))
			: 'per_source_objects',
		'native_strategy' => 'per_source_objects_with_project_unit_broad_fallback',
		'deterministic' => true,
		'allowed_policies' => $allowed,
		'notes' => $notes,
		'manual_groups' => $manualGroups,
		'generated_grouping_enabled' => $generatedGroupingEnabled,
	];
}

/** @return list<array{id:string,name:string,sources:list<string>}> */
function normalize_build_grouping_manual_config(mixed $rawGrouping): array
{
	if (!is_array($rawGrouping) || array_is_list($rawGrouping)) {
		scpp_fail('Invalid build.grouping in ' . SCPP_PROJECT_CONFIG . ': manual grouping requires an object mapping group names to source lists.' . PHP_EOL, 2);
	}
	if ($rawGrouping === []) {
		scpp_fail('Invalid build.grouping in ' . SCPP_PROJECT_CONFIG . ': manual grouping requires at least one group.' . PHP_EOL, 2);
	}

	$groups = [];
	$sourceOwners = [];
	foreach ($rawGrouping as $rawName => $rawSources) {
		$name = trim((string) $rawName);
		if ($name === '') {
			scpp_fail('Invalid build.grouping in ' . SCPP_PROJECT_CONFIG . ': manual group names must not be empty.' . PHP_EOL, 2);
		}
		if (!is_array($rawSources) || !array_is_list($rawSources)) {
			scpp_fail('Invalid build.grouping.' . $name . ' in ' . SCPP_PROJECT_CONFIG . ': expected a list of project-relative source paths.' . PHP_EOL, 2);
		}
		$sources = [];
		foreach ($rawSources as $index => $rawSource) {
			if (!is_string($rawSource)) {
				scpp_fail('Invalid build.grouping.' . $name . '[' . (int) $index . '] in ' . SCPP_PROJECT_CONFIG . ': expected a source path string.' . PHP_EOL, 2);
			}
			$source = normalize_build_grouping_source_path($rawSource, 'build.grouping.' . $name . '[' . (int) $index . ']');
			if (isset($sourceOwners[$source]) && $sourceOwners[$source] !== $name) {
				scpp_fail('Duplicate manual build grouping source `' . $source . '` in ' . SCPP_PROJECT_CONFIG . ': already assigned to group `' . $sourceOwners[$source] . '`.' . PHP_EOL, 2);
			}
			if (isset($sourceOwners[$source]) && $sourceOwners[$source] === $name) {
				scpp_fail('Duplicate manual build grouping source `' . $source . '` in group `' . $name . '`.' . PHP_EOL, 2);
			}
			$sourceOwners[$source] = $name;
			$sources[] = $source;
		}
		if ($sources === []) {
			scpp_fail('Invalid build.grouping.' . $name . ' in ' . SCPP_PROJECT_CONFIG . ': manual groups must include at least one source.' . PHP_EOL, 2);
		}
		sort($sources, SORT_STRING);
		$groups[] = [
			'id' => build_grouping_manual_group_id($name),
			'name' => $name,
			'sources' => $sources,
		];
	}
	usort($groups, static fn (array $left, array $right): int => strcmp((string) ($left['id'] ?? ''), (string) ($right['id'] ?? '')));
	return $groups;
}

function normalize_build_grouping_source_path(string $rawSource, string $label): string
{
	$source = trim(str_replace('\\', '/', $rawSource));
	if ($source === '') {
		scpp_fail('Invalid ' . $label . ' in ' . SCPP_PROJECT_CONFIG . ': source path must not be empty.' . PHP_EOL, 2);
	}
	if (is_absolute_path($source)) {
		scpp_fail('Invalid ' . $label . ' in ' . SCPP_PROJECT_CONFIG . ': source path must be project-relative.' . PHP_EOL, 2);
	}
	if (str_contains($source, "\0")) {
		scpp_fail('Invalid ' . $label . ' in ' . SCPP_PROJECT_CONFIG . ': source path contains a NUL byte.' . PHP_EOL, 2);
	}
	$source = normalize_config_path(normalize_path($source));
	if ($source === '..' || str_starts_with($source, '../')) {
		scpp_fail('Invalid ' . $label . ' in ' . SCPP_PROJECT_CONFIG . ': source path escapes the project root.' . PHP_EOL, 2);
	}
	return $source;
}

function build_grouping_manual_group_id(string $name): string
{
	$id = strtolower(trim($name));
	$id = preg_replace('/[^a-z0-9_.-]+/', '-', $id);
	$id = trim(is_string($id) ? $id : '', '-');
	if ($id === '') {
		$id = substr(hash('sha256', $name), 0, 12);
	}
	return 'manual:root:' . $id;
}

/**
 * @param array<string,mixed> $policy
 * @param list<array<string,mixed>> $generatedUnits
 * @return array<string,mixed>
 */
function apply_auto_build_grouping_evidence(string $projectRoot, array $policy, array $generatedUnits): array
{
	if ((string) ($policy['policy'] ?? '') !== 'auto') {
		return $policy;
	}
	$buildMode = normalize_build_mode_name((string) ($policy['build_mode'] ?? 'debug'), 'auto build grouping mode');
	$previous = collect_previous_auto_build_grouping_evidence($projectRoot);
	$rootGeneratedUnits = [];
	foreach ($generatedUnits as $unit) {
		if (normalize_path((string) ($unit['project_root'] ?? '')) !== normalize_path($projectRoot)) {
			continue;
		}
		$relativeSource = normalize_config_path((string) ($unit['relative_php'] ?? ''));
		if ($relativeSource === '') {
			continue;
		}
		$rootGeneratedUnits[] = $unit;
	}
	$generatedCount = count($rootGeneratedUnits);
	[$selectedPolicy, $selectionReasons] = select_auto_build_grouping_policy($buildMode, $previous, $generatedCount);
	$policy['auto_evidence'] = [
		'source' => (string) ($previous['source'] ?? 'none'),
		'selected_policy' => $selectedPolicy,
		'reasons' => $selectionReasons,
		'prior_ninja_subprocess_ms' => (int) ($previous['prior_ninja_subprocess_ms'] ?? 0),
		'prior_rebuilt_object_count' => (int) ($previous['prior_rebuilt_object_count'] ?? 0),
		'prior_rebuilt_generated_object_count' => (int) ($previous['prior_rebuilt_generated_object_count'] ?? 0),
		'prior_changed_group_count' => (int) ($previous['prior_changed_group_count'] ?? 0),
		'root_generated_source_count' => $generatedCount,
	];
	$policy['auto_source_decisions'] = collect_auto_build_grouping_source_decisions($projectRoot, $buildMode, $selectedPolicy, $previous, $rootGeneratedUnits);
	if ($selectedPolicy !== 'incremental') {
		$notes = normalize_string_list($policy['notes'] ?? []);
		$notes[] = 'auto selected ' . $selectedPolicy . ' grouping from saved build evidence';
		$policy['notes'] = $notes;
	}
	return $policy;
}

/** @return array<string,mixed> */
function collect_previous_auto_build_grouping_evidence(string $projectRoot): array
{
	$lastRun = read_json_file(normalize_path($projectRoot . '/.prism/last_run.json'));
	if (!is_array($lastRun)) {
		return [
			'source' => 'none',
			'previous_volatile_sources' => [],
			'previous_object_paths_by_source' => [],
		];
	}
	$details = is_array($lastRun['details'] ?? null) ? $lastRun['details'] : [];
	$timings = is_array($details['timing_breakdown_ms'] ?? null) ? $details['timing_breakdown_ms'] : [];
	$explanation = is_array($details['build_explanation'] ?? null) ? $details['build_explanation'] : [];
	$fanout = is_array($explanation['rebuild_fanout'] ?? null) ? $explanation['rebuild_fanout'] : (is_array($details['rebuild_fanout'] ?? null) ? $details['rebuild_fanout'] : []);
	$grouping = is_array($explanation['build_grouping'] ?? null) ? $explanation['build_grouping'] : [];
	$volatileSources = [];
	$objectPathsBySource = [];
	foreach (is_array($explanation['sources'] ?? null) ? $explanation['sources'] : [] as $source) {
		if (!is_array($source)) {
			continue;
		}
		$path = normalize_config_path((string) ($source['path'] ?? ''));
		if ($path === '') {
			continue;
		}
		$artifacts = is_array($source['generated_artifacts'] ?? null) ? $source['generated_artifacts'] : [];
		$firstRecorded = (bool) ($artifacts['interface_first_recorded'] ?? false) || (bool) ($artifacts['implementation_first_recorded'] ?? false);
		$changed = (bool) ($artifacts['interface_changed'] ?? false) || (bool) ($artifacts['implementation_changed'] ?? false);
		if ($changed && !$firstRecorded) {
			$volatileSources[$path] = true;
		}
		$objectPath = normalize_config_path((string) ($source['object_path'] ?? ''));
		if ($objectPath !== '') {
			$objectPathsBySource[$path] = $objectPath;
		}
	}
	return [
		'source' => '.prism/last_run.json',
		'prior_ninja_subprocess_ms' => max(0, (int) ($timings['ninja_subprocess_ms'] ?? 0)),
		'prior_rebuilt_object_count' => max(0, (int) ($fanout['rebuilt_object_count'] ?? 0)),
		'prior_rebuilt_generated_object_count' => max(0, (int) ($fanout['rebuilt_generated_object_count'] ?? 0)),
		'prior_changed_group_count' => max(0, (int) ($grouping['changed_group_count'] ?? 0)),
		'previous_volatile_sources' => $volatileSources,
		'previous_object_paths_by_source' => $objectPathsBySource,
	];
}

/** @param array<string,mixed> $previous @return array{0:string,1:list<string>} */
function select_auto_build_grouping_policy(string $buildMode, array $previous, int $generatedCount): array
{
	if ($buildMode !== 'release') {
		return ['incremental', ['debug builds keep per-source grouping for edit isolation']];
	}
	if ((string) ($previous['source'] ?? 'none') === 'none') {
		return ['folder', ['no prior build evidence; starting with stable folder grouping']];
	}
	$priorGeneratedFanout = max(0, (int) ($previous['prior_rebuilt_generated_object_count'] ?? 0));
	$priorNinjaMs = max(0, (int) ($previous['prior_ninja_subprocess_ms'] ?? 0));
	if ($priorGeneratedFanout > 0 && $priorGeneratedFanout <= max(2, (int) ceil(max(1, $generatedCount) / 4))) {
		return ['folder', ['prior build showed narrow generated-object fanout; preserving folder granularity']];
	}
	if ($generatedCount >= 50 || $priorNinjaMs >= 2000) {
		return ['release', ['large release build evidence favors fewer generated compile units']];
	}
	return ['package', ['moderate release build evidence favors package grouping']];
}

/**
 * @param array<string,mixed> $previous
 * @param list<array<string,mixed>> $generatedUnits
 * @return list<array<string,mixed>>
 */
function collect_auto_build_grouping_source_decisions(string $projectRoot, string $buildMode, string $selectedPolicy, array $previous, array $generatedUnits): array
{
	$previousVolatileSources = is_array($previous['previous_volatile_sources'] ?? null) ? $previous['previous_volatile_sources'] : [];
	$previousObjectPathsBySource = is_array($previous['previous_object_paths_by_source'] ?? null) ? $previous['previous_object_paths_by_source'] : [];
	$rows = [];
	foreach ($generatedUnits as $unit) {
		$source = normalize_config_path((string) ($unit['relative_php'] ?? ''));
		if ($source === '') {
			continue;
		}
		$objectSizeBytes = detect_auto_grouping_object_size($projectRoot, $unit, normalize_config_path((string) ($previousObjectPathsBySource[$source] ?? '')));
		if ($selectedPolicy === 'incremental') {
			$rows[] = build_auto_grouping_source_decision($source, 'isolated', '', '', 'debug auto policy keeps generated source isolated', $objectSizeBytes);
			continue;
		}
		if ((bool) ($unit['is_entrypoint'] ?? false)) {
			$rows[] = build_auto_grouping_source_decision($source, 'isolated', '', '', 'entrypoint generated source stays isolated', $objectSizeBytes);
			continue;
		}
		if ($objectSizeBytes >= 8 * 1024 * 1024) {
			$rows[] = build_auto_grouping_source_decision($source, 'isolated', '', '', 'previous object size is at least 8 MiB', $objectSizeBytes);
			continue;
		}
		if (isset($previousVolatileSources[$source])) {
			$rows[] = build_auto_grouping_source_decision($source, 'isolated', '', '', 'previous build changed this source generated artifacts', $objectSizeBytes);
			continue;
		}
		$group = build_grouping_group_key($selectedPolicy, $buildMode, 'generated', '.', $source, null);
		$rows[] = build_auto_grouping_source_decision(
			$source,
			'grouped',
			(string) ($group['id'] ?? ''),
			(string) ($group['label'] ?? ''),
			'selected ' . $selectedPolicy . ' grouping from saved evidence',
			$objectSizeBytes
		);
	}
	$groupCounts = [];
	foreach ($rows as $row) {
		if ((string) ($row['decision'] ?? '') !== 'grouped') {
			continue;
		}
		$groupId = trim((string) ($row['group_id'] ?? ''));
		if ($groupId !== '') {
			$groupCounts[$groupId] = ($groupCounts[$groupId] ?? 0) + 1;
		}
	}
	foreach ($rows as &$row) {
		if ((string) ($row['decision'] ?? '') !== 'grouped') {
			continue;
		}
		$groupId = trim((string) ($row['group_id'] ?? ''));
		if ($groupId === '' || (int) ($groupCounts[$groupId] ?? 0) >= 2) {
			continue;
		}
		$row['decision'] = 'isolated';
		$row['group_id'] = '';
		$row['group_label'] = '';
		$row['reason'] = 'auto group has fewer than two generated sources';
	}
	unset($row);
	usort($rows, static fn (array $left, array $right): int => strcmp((string) ($left['source'] ?? ''), (string) ($right['source'] ?? '')));
	return $rows;
}

/** @param array<string,mixed> $unit */
function detect_auto_grouping_object_size(string $projectRoot, array $unit, string $previousObjectPath): int
{
	$candidates = [];
	$objectPath = normalize_path((string) ($unit['object_path'] ?? ''));
	if ($objectPath !== '') {
		$candidates[] = $objectPath;
	}
	if ($previousObjectPath !== '') {
		$candidates[] = is_absolute_path($previousObjectPath)
			? normalize_path($previousObjectPath)
			: normalize_path($projectRoot . '/' . $previousObjectPath);
	}
	foreach (array_values(array_unique($candidates)) as $candidate) {
		if (is_file($candidate)) {
			$size = filesize($candidate);
			if (is_int($size) && $size >= 0) {
				return $size;
			}
		}
	}
	return 0;
}

/** @return array{source:string,decision:string,group_id:string,group_label:string,reason:string,object_size_bytes:int} */
function build_auto_grouping_source_decision(string $source, string $decision, string $groupId, string $groupLabel, string $reason, int $objectSizeBytes): array
{
	return [
		'source' => normalize_config_path($source),
		'decision' => $decision,
		'group_id' => $groupId,
		'group_label' => $groupLabel,
		'reason' => $reason,
		'object_size_bytes' => max(0, $objectSizeBytes),
	];
}

/** @return array<string,mixed> */
function apply_project_module_build_grouping_evidence(string $projectRoot, array $policy, array $projectModuleReport, array $generatedUnits): array
{
	if ((string) ($policy['policy'] ?? '') !== 'module') {
		return $policy;
	}
	$moduleGroups = collect_project_module_generated_group_rows($projectRoot, $projectModuleReport, $generatedUnits);
	$assignedSources = [];
	foreach ($moduleGroups as $group) {
		foreach (normalize_string_list($group['sources'] ?? []) as $source) {
			$assignedSources[$source] = true;
		}
	}
	$eligibleRootSources = collect_root_generated_grouping_sources($projectRoot, $generatedUnits);
	$unassignedSources = [];
	foreach (array_keys($eligibleRootSources) as $source) {
		if (!isset($assignedSources[$source])) {
			$unassignedSources[] = $source;
		}
	}
	sort($unassignedSources, SORT_STRING);
	$notes = normalize_string_list($policy['notes'] ?? []);
	$moduleReport = normalize_project_module_report($projectModuleReport);
	if ((bool) ($moduleReport['configured'] ?? false)) {
		$notes[] = 'module grouping uses explicit project_modules membership';
		$notes[] = 'module grouping excludes entrypoints from grouped generated object edges';
	} else {
		$notes[] = 'module grouping found no configured project_modules; generated sources stay isolated';
	}
	$policy['notes'] = normalize_string_list($notes);
	$policy['module_groups'] = $moduleGroups;
	$policy['module_group_count'] = count($moduleGroups);
	$policy['module_assigned_source_count'] = count($assignedSources);
	$policy['module_unassigned_source_count'] = count($unassignedSources);
	$policy['module_unassigned_sources'] = $unassignedSources;
	return $policy;
}

/** @return array<string,bool> */
function collect_root_generated_grouping_sources(string $projectRoot, array $generatedUnits): array
{
	$rootProjectRoot = normalize_path($projectRoot);
	$sources = [];
	foreach ($generatedUnits as $unit) {
		if (normalize_path((string) ($unit['project_root'] ?? '')) !== $rootProjectRoot) {
			continue;
		}
		if ((bool) ($unit['is_entrypoint'] ?? false)) {
			continue;
		}
		$source = normalize_config_path((string) ($unit['relative_php'] ?? ''));
		if ($source !== '') {
			$sources[$source] = true;
		}
	}
	ksort($sources, SORT_STRING);
	return $sources;
}

/** @return list<array{id:string,name:string,project_root:string,sources:list<string>}> */
function collect_project_module_generated_group_rows(string $projectRoot, array $projectModuleReport, array $generatedUnits): array
{
	$eligibleRootSources = collect_root_generated_grouping_sources($projectRoot, $generatedUnits);
	if ($eligibleRootSources === []) {
		return [];
	}
	$report = normalize_project_module_report($projectModuleReport);
	if (!(bool) ($report['configured'] ?? false)) {
		return [];
	}
	$groups = [];
	foreach (is_array($report['modules'] ?? null) ? $report['modules'] : [] as $module) {
		if (!is_array($module)) {
			continue;
		}
		$moduleName = trim((string) ($module['name'] ?? ''));
		$projectLabel = trim((string) ($module['project_root'] ?? '.'));
		if ($moduleName === '' || ($projectLabel !== '' && $projectLabel !== '.')) {
			continue;
		}
		$sources = [];
		foreach (normalize_project_module_source_rows(is_array($module['sources'] ?? null) ? $module['sources'] : []) as $sourceRow) {
			$source = normalize_config_path((string) ($sourceRow['source'] ?? ''));
			if ($source !== '' && isset($eligibleRootSources[$source])) {
				$sources[] = $source;
			}
		}
		$sources = normalize_string_list($sources);
		if ($sources === []) {
			continue;
		}
		$groups[] = [
			'id' => build_grouping_module_group_id('.', $moduleName),
			'name' => $moduleName,
			'project_root' => '.',
			'sources' => $sources,
		];
	}
	usort($groups, static fn (array $left, array $right): int => strcmp((string) ($left['id'] ?? ''), (string) ($right['id'] ?? '')));
	return $groups;
}

function build_grouping_module_group_id(string $projectLabel, string $moduleName): string
{
	$projectKey = trim($projectLabel) === '.' || trim($projectLabel) === '' ? 'root' : trim($projectLabel);
	$projectKey = strtolower(trim(preg_replace('/[^A-Za-z0-9_.-]+/', '-', $projectKey) ?? ''));
	$moduleKey = strtolower(trim(preg_replace('/[^A-Za-z0-9_.-]+/', '-', $moduleName) ?? ''));
	if ($projectKey === '') {
		$projectKey = 'project';
	}
	if ($moduleKey === '') {
		$moduleKey = substr(hash('sha256', $moduleName), 0, 12);
	}
	return 'module:' . $projectKey . ':' . $moduleKey;
}

/**
 * @param array<string,mixed> $policy
 * @param list<array<string,mixed>> $generatedUnits
 * @param list<array<string,mixed>> $sourceRebuildReasons
 */
function apply_grouped_generated_object_edges(string $projectRoot, string $buildDir, string $generatedDir, array $policy, array &$generatedUnits, array &$sourceRebuildReasons, string $compilerKind): void
{
	if (!((bool) ($policy['generated_grouping_enabled'] ?? false))) {
		return;
	}
	$policyName = (string) ($policy['policy'] ?? '');
	if ($policyName === 'manual') {
		$groups = normalize_build_grouping_manual_group_rows(is_array($policy['manual_groups'] ?? null) ? $policy['manual_groups'] : []);
	} elseif ($policyName === 'module') {
		$groups = normalize_build_grouping_module_group_rows(is_array($policy['module_groups'] ?? null) ? $policy['module_groups'] : []);
	} else {
		$groups = collect_release_generated_group_rows($projectRoot, $policy, $generatedUnits);
	}
	if ($groups === []) {
		return;
	}
	$rootProjectRoot = normalize_path($projectRoot);
	$indexesBySource = [];
	foreach ($generatedUnits as $index => $unit) {
		if (normalize_path((string) ($unit['project_root'] ?? '')) !== $rootProjectRoot) {
			continue;
		}
		$relativePhp = normalize_config_path((string) ($unit['relative_php'] ?? ''));
		if ($relativePhp !== '') {
			$indexesBySource[$relativePhp] = $index;
		}
	}

	foreach ($groups as $group) {
		$memberIndexes = [];
		foreach (normalize_string_list($group['sources'] ?? []) as $source) {
			if (isset($indexesBySource[$source])) {
				$memberIndexes[] = $indexesBySource[$source];
			}
		}
		$memberIndexes = array_values(array_unique($memberIndexes));
		if (count($memberIndexes) < 2) {
			continue;
		}
		usort($memberIndexes, static function (int $left, int $right) use ($generatedUnits): int {
			return strcmp((string) ($generatedUnits[$left]['relative_php'] ?? ''), (string) ($generatedUnits[$right]['relative_php'] ?? ''));
		});
		$memberSources = [];
		foreach ($memberIndexes as $memberIndex) {
			$memberSources[] = normalize_config_path((string) ($generatedUnits[$memberIndex]['relative_php'] ?? ''));
		}
		$groupHash = substr(hash('sha256', (string) ($group['id'] ?? '') . "\n" . implode("\n", $memberSources)), 0, 16);
		$groupSourcePath = normalize_path($generatedDir . '/__build_groups/' . $groupHash . '.cpp');
		$groupObjectPath = normalize_path($buildDir . '/__build_groups/' . $groupHash . '.' . object_extension($compilerKind));
		$memberUnits = [];
		foreach ($memberIndexes as $memberIndex) {
			$memberUnits[] = $generatedUnits[$memberIndex];
		}
		write_text_file($groupSourcePath, render_grouped_generated_cpp_source($groupSourcePath, $group, $memberUnits));
		foreach ($memberIndexes as $memberIndex) {
			$generatedUnits[$memberIndex]['object_path'] = $groupObjectPath;
			$generatedUnits[$memberIndex]['compile_source_path'] = $groupSourcePath;
			$generatedUnits[$memberIndex]['build_group_id'] = (string) ($group['id'] ?? '');
			$generatedUnits[$memberIndex]['build_group_label'] = (string) ($group['name'] ?? '');
			$generatedUnits[$memberIndex]['build_group_source_count'] = count($memberIndexes);
		}
		foreach ($sourceRebuildReasons as &$sourceRow) {
			if (normalize_path((string) ($sourceRow['project_root'] ?? '')) !== $rootProjectRoot) {
				continue;
			}
			$sourcePath = normalize_config_path((string) ($sourceRow['path'] ?? ''));
			if (!in_array($sourcePath, $memberSources, true)) {
				continue;
			}
			$sourceRow['object_path'] = normalize_config_path(relative_path($projectRoot, $groupObjectPath));
			$sourceRow['build_group_id'] = (string) ($group['id'] ?? '');
			$sourceRow['build_group_label'] = (string) ($group['name'] ?? '');
			$sourceRow['build_group_source_count'] = count($memberIndexes);
		}
		unset($sourceRow);
	}
}

/**
 * @param array<string,mixed> $policy
 * @param list<array<string,mixed>> $generatedUnits
 * @return list<array{id:string,name:string,sources:list<string>}>
 */
function collect_release_generated_group_rows(string $projectRoot, array $policy, array $generatedUnits): array
{
	$policyName = (string) ($policy['policy'] ?? '');
	if (!in_array($policyName, ['folder', 'package', 'release', 'auto'], true)) {
		return [];
	}
	if ($policyName === 'auto') {
		$rowsByGroup = [];
		foreach (normalize_auto_build_grouping_source_decisions(is_array($policy['auto_source_decisions'] ?? null) ? $policy['auto_source_decisions'] : []) as $decision) {
			if ((string) ($decision['decision'] ?? '') !== 'grouped') {
				continue;
			}
			$groupId = trim((string) ($decision['group_id'] ?? ''));
			$source = normalize_config_path((string) ($decision['source'] ?? ''));
			if ($groupId === '' || $source === '') {
				continue;
			}
			if (!isset($rowsByGroup[$groupId])) {
				$rowsByGroup[$groupId] = [
					'id' => $groupId,
					'name' => (string) ($decision['group_label'] ?? $groupId),
					'sources' => [],
				];
			}
			$rowsByGroup[$groupId]['sources'][] = $source;
		}
		$rows = [];
		foreach ($rowsByGroup as $group) {
			$sources = normalize_string_list($group['sources'] ?? []);
			if (count($sources) < 2) {
				continue;
			}
			$rows[] = [
				'id' => (string) ($group['id'] ?? ''),
				'name' => (string) ($group['name'] ?? ''),
				'sources' => $sources,
			];
		}
		usort($rows, static fn (array $left, array $right): int => strcmp((string) ($left['id'] ?? ''), (string) ($right['id'] ?? '')));
		return $rows;
	}
	$rootProjectRoot = normalize_path($projectRoot);
	$groups = [];
	foreach ($generatedUnits as $unit) {
		if (normalize_path((string) ($unit['project_root'] ?? '')) !== $rootProjectRoot) {
			continue;
		}
		if ((bool) ($unit['is_entrypoint'] ?? false)) {
			continue;
		}
		$relativeSource = normalize_config_path((string) ($unit['relative_php'] ?? ''));
		if ($relativeSource === '') {
			continue;
		}
		$group = build_grouping_group_key(
			$policyName,
			(string) ($policy['build_mode'] ?? 'release'),
			'generated',
			'.',
			$relativeSource,
			null
		);
		$groupId = (string) ($group['id'] ?? '');
		if ($groupId === '') {
			continue;
		}
		if (!isset($groups[$groupId])) {
			$groups[$groupId] = [
				'id' => $groupId,
				'name' => (string) ($group['label'] ?? $groupId),
				'sources' => [],
			];
		}
		$groups[$groupId]['sources'][] = $relativeSource;
	}
	$rows = [];
	foreach ($groups as $group) {
		$sources = normalize_string_list($group['sources'] ?? []);
		if (count($sources) < 2) {
			continue;
		}
		$rows[] = [
			'id' => (string) ($group['id'] ?? ''),
			'name' => (string) ($group['name'] ?? ''),
			'sources' => $sources,
		];
	}
	usort($rows, static fn (array $left, array $right): int => strcmp((string) ($left['id'] ?? ''), (string) ($right['id'] ?? '')));
	return $rows;
}

/**
 * @param array<string,mixed> $group
 * @param list<array<string,mixed>> $memberUnits
 */
function render_grouped_generated_cpp_source(string $groupSourcePath, array $group, array $memberUnits): string
{
	$lines = [];
	$lines[] = '// Generated by scpp build grouping.';
	$lines[] = '// Group: ' . (string) ($group['name'] ?? '');
	foreach ($memberUnits as $unit) {
		$generatedCpp = normalize_path((string) ($unit['generated_cpp'] ?? ''));
		if ($generatedCpp === '') {
			continue;
		}
		$includePath = normalize_config_path(relative_path(dirname($groupSourcePath), $generatedCpp));
		$includePath = str_replace('"', '\\"', $includePath);
		$lines[] = '#include "' . $includePath . '"';
	}
	return implode(PHP_EOL, $lines) . PHP_EOL;
}

/**
 * @param list<array{project_root:string,relative_php:string,generated_header?:string,generated_cpp:string,object_path:string,is_entrypoint:bool,force_include_header:?string}> $generatedUnits
 * @param list<array{project_root:string,source_path:string,object_path:string,force_include_header:?string}> $nativeCppUnits
 * @return array<string,mixed>
 */
function collect_build_grouping_report(string $projectRoot, array $policy, array $generatedUnits, array $nativeCppUnits, array $rebuildFanout = []): array
{
	$policy = normalize_build_grouping_report($policy);
	$fanout = normalize_build_rebuild_fanout($rebuildFanout);
	$rebuiltObjects = [];
	foreach (array_merge($fanout['rebuilt_generated_objects'], $fanout['rebuilt_native_objects']) as $objectPath) {
		$rebuiltObjects[normalize_config_path((string) $objectPath)] = true;
	}

	$manualGroupsBySource = [];
	$manualGroups = is_array($policy['manual_groups'] ?? null) ? $policy['manual_groups'] : [];
	foreach ($manualGroups as $manualGroup) {
		if (!is_array($manualGroup)) {
			continue;
		}
		foreach (normalize_string_list($manualGroup['sources'] ?? []) as $source) {
			$manualGroupsBySource[$source] = $manualGroup;
		}
	}
	$moduleGroupsBySource = [];
	$moduleGroups = normalize_build_grouping_module_group_rows(is_array($policy['module_groups'] ?? null) ? $policy['module_groups'] : []);
	if ((string) ($policy['policy'] ?? '') === 'module') {
		foreach ($moduleGroups as $moduleGroup) {
			foreach (normalize_string_list($moduleGroup['sources'] ?? []) as $source) {
				$moduleGroupsBySource[$source] = $moduleGroup;
			}
		}
	}

	$knownRootSources = [];
	foreach ($generatedUnits as $unit) {
		$unitProjectRoot = normalize_path((string) ($unit['project_root'] ?? ''));
		$relativeSource = normalize_config_path((string) ($unit['relative_php'] ?? ''));
		if ($unitProjectRoot === normalize_path($projectRoot) && $relativeSource !== '') {
			$knownRootSources[$relativeSource] = true;
		}
	}
	foreach ($nativeCppUnits as $unit) {
		$unitProjectRoot = normalize_path((string) ($unit['project_root'] ?? ''));
		$sourcePath = normalize_path((string) ($unit['source_path'] ?? ''));
		if ($unitProjectRoot === normalize_path($projectRoot) && $sourcePath !== '') {
			$knownRootSources[normalize_config_path(relative_path($unitProjectRoot, $sourcePath))] = true;
		}
	}
	foreach (array_keys($manualGroupsBySource) as $manualSource) {
		if (!isset($knownRootSources[$manualSource])) {
			scpp_fail('Invalid build.grouping in ' . SCPP_PROJECT_CONFIG . ': manual source `' . $manualSource . '` is not a generated or native source in the root project.' . PHP_EOL, 2);
		}
	}
	$autoDecisionsBySource = [];
	if ((string) ($policy['policy'] ?? '') === 'auto') {
		foreach (normalize_auto_build_grouping_source_decisions(is_array($policy['auto_source_decisions'] ?? null) ? $policy['auto_source_decisions'] : []) as $decision) {
			$source = normalize_config_path((string) ($decision['source'] ?? ''));
			if ($source !== '') {
				$autoDecisionsBySource[$source] = $decision;
			}
		}
	}

	$groups = [];
	$addUnit = static function (string $kind, string $unitProjectRoot, string $relativeSource, string $objectPath) use (&$groups, $projectRoot, $policy, $rebuiltObjects, $manualGroupsBySource, $moduleGroupsBySource, $autoDecisionsBySource): void {
		$projectLabel = project_context_report_label($projectRoot, $unitProjectRoot);
		$manualGroup = null;
		if ((string) ($policy['policy'] ?? '') === 'manual' && normalize_path($unitProjectRoot) === normalize_path($projectRoot)) {
			$manualGroup = is_array($manualGroupsBySource[$relativeSource] ?? null) ? $manualGroupsBySource[$relativeSource] : null;
		}
		$group = null;
		if ((string) ($policy['policy'] ?? '') === 'module' && normalize_path($unitProjectRoot) === normalize_path($projectRoot) && $kind === 'generated') {
			$moduleGroup = is_array($moduleGroupsBySource[$relativeSource] ?? null) ? $moduleGroupsBySource[$relativeSource] : null;
			if ($moduleGroup !== null) {
				$group = [
					'id' => trim((string) ($moduleGroup['id'] ?? '')),
					'label' => trim((string) ($moduleGroup['name'] ?? '')),
					'kind' => 'project_module',
				];
			}
		}
		if ((string) ($policy['policy'] ?? '') === 'auto' && normalize_path($unitProjectRoot) === normalize_path($projectRoot) && $kind === 'generated') {
			$decision = is_array($autoDecisionsBySource[$relativeSource] ?? null) ? $autoDecisionsBySource[$relativeSource] : null;
			if ($decision !== null && (string) ($decision['decision'] ?? '') === 'grouped' && trim((string) ($decision['group_id'] ?? '')) !== '') {
				$group = [
					'id' => trim((string) ($decision['group_id'] ?? '')),
					'label' => trim((string) ($decision['group_label'] ?? '')),
					'kind' => 'auto_group',
				];
			} elseif ($decision !== null && (string) ($decision['decision'] ?? '') === 'isolated') {
				$group = build_grouping_group_key('incremental', (string) ($policy['build_mode'] ?? 'debug'), $kind, $projectLabel, $relativeSource, null);
			}
		}
		if ($group === null) {
			$group = build_grouping_group_key((string) ($policy['policy'] ?? 'incremental'), (string) ($policy['build_mode'] ?? 'debug'), $kind, $projectLabel, $relativeSource, $manualGroup);
		}
		$objectLabel = normalize_config_path(relative_path($projectRoot, normalize_path($objectPath)));
		if (!isset($groups[$group['id']])) {
			$groups[$group['id']] = [
				'id' => $group['id'],
				'label' => $group['label'],
				'kind' => $group['kind'],
				'project_root' => $projectLabel,
				'generated_sources' => [],
				'native_sources' => [],
				'objects' => [],
				'rebuilt_objects' => [],
				'changed' => false,
			];
		}
		if ($kind === 'generated') {
			$groups[$group['id']]['generated_sources'][] = normalize_config_path($relativeSource);
		} else {
			$groups[$group['id']]['native_sources'][] = normalize_config_path($relativeSource);
		}
		if ($objectLabel !== '') {
			$groups[$group['id']]['objects'][] = $objectLabel;
			if (isset($rebuiltObjects[$objectLabel])) {
				$groups[$group['id']]['rebuilt_objects'][] = $objectLabel;
				$groups[$group['id']]['changed'] = true;
			}
		}
	};

	foreach ($generatedUnits as $unit) {
		$unitProjectRoot = normalize_path((string) ($unit['project_root'] ?? ''));
		$relativeSource = normalize_config_path((string) ($unit['relative_php'] ?? ''));
		$objectPath = normalize_path((string) ($unit['object_path'] ?? ''));
		if ($unitProjectRoot === '' || $relativeSource === '' || $objectPath === '') {
			continue;
		}
		$addUnit('generated', $unitProjectRoot, $relativeSource, $objectPath);
	}
	foreach ($nativeCppUnits as $unit) {
		$unitProjectRoot = normalize_path((string) ($unit['project_root'] ?? ''));
		$sourcePath = normalize_path((string) ($unit['source_path'] ?? ''));
		$objectPath = normalize_path((string) ($unit['object_path'] ?? ''));
		if ($unitProjectRoot === '' || $sourcePath === '' || $objectPath === '') {
			continue;
		}
		$addUnit('native', $unitProjectRoot, normalize_config_path(relative_path($unitProjectRoot, $sourcePath)), $objectPath);
	}

	$groupRows = [];
	$changedGroups = [];
	foreach ($groups as $group) {
		$group['generated_sources'] = normalize_string_list($group['generated_sources'] ?? []);
		$group['native_sources'] = normalize_string_list($group['native_sources'] ?? []);
		$group['objects'] = normalize_string_list($group['objects'] ?? []);
		$group['rebuilt_objects'] = normalize_string_list($group['rebuilt_objects'] ?? []);
		$group['object_count'] = count($group['objects']);
		$group['rebuilt_object_count'] = count($group['rebuilt_objects']);
		$group['generated_source_count'] = count($group['generated_sources']);
		$group['native_source_count'] = count($group['native_sources']);
		if ((bool) ($group['changed'] ?? false)) {
			$changedGroups[] = (string) ($group['id'] ?? '');
		}
		$groupRows[] = $group;
	}
	usort($groupRows, static fn (array $left, array $right): int => strcmp((string) ($left['id'] ?? ''), (string) ($right['id'] ?? '')));
	sort($changedGroups, SORT_STRING);

	$policy['groups'] = $groupRows;
	$policy['total_groups'] = count($groupRows);
	$policy['changed_groups'] = array_values(array_unique(array_filter($changedGroups, static fn (string $value): bool => $value !== '')));
	$policy['changed_group_count'] = count($policy['changed_groups']);
	$policy['object_fanout'] = [
		'rebuilt_object_count' => (int) ($fanout['rebuilt_object_count'] ?? 0),
		'rebuilt_generated_object_count' => (int) ($fanout['rebuilt_generated_object_count'] ?? 0),
		'rebuilt_native_object_count' => (int) ($fanout['rebuilt_native_object_count'] ?? 0),
	];
	$includeChangedGroupReasons = (string) ($policy['policy'] ?? '') === 'manual' || (bool) ($policy['generated_grouping_enabled'] ?? false);
	if ($includeChangedGroupReasons) {
		$changedGroupReasons = [];
		foreach ($groupRows as $group) {
			if (!((bool) ($group['changed'] ?? false))) {
				continue;
			}
			$groupId = trim((string) ($group['id'] ?? ''));
			if ($groupId === '') {
				continue;
			}
			$reasons = [];
			foreach (normalize_string_list($group['rebuilt_objects'] ?? []) as $objectPath) {
				$reasons[] = 'rebuilt object ' . $objectPath;
			}
			$changedGroupReasons[] = [
				'group_id' => $groupId,
				'reasons' => $reasons,
			];
		}
		$policy['changed_group_reasons'] = $changedGroupReasons;
	}
	if ((string) ($policy['policy'] ?? '') === 'manual') {
		$unassignedRootSources = [];
		foreach (array_keys($knownRootSources) as $source) {
			if (!isset($manualGroupsBySource[$source])) {
				$unassignedRootSources[] = $source;
			}
		}
		sort($unassignedRootSources, SORT_STRING);
		$policy['manual_group_count'] = count($manualGroups);
		$policy['manual_assigned_source_count'] = count($manualGroupsBySource);
		$policy['manual_unassigned_source_count'] = count($unassignedRootSources);
		$policy['manual_unassigned_sources'] = $unassignedRootSources;
	}
	if ((string) ($policy['policy'] ?? '') === 'module') {
		$policy['module_groups'] = $moduleGroups;
		$policy['module_group_count'] = max(0, (int) ($policy['module_group_count'] ?? count($moduleGroups)));
		$policy['module_assigned_source_count'] = max(0, (int) ($policy['module_assigned_source_count'] ?? count($moduleGroupsBySource)));
		$policy['module_unassigned_source_count'] = max(0, (int) ($policy['module_unassigned_source_count'] ?? 0));
		$policy['module_unassigned_sources'] = normalize_string_list($policy['module_unassigned_sources'] ?? []);
	}
	return normalize_build_grouping_report($policy);
}

/** @param array<string,mixed>|null $manualGroup @return array{id:string,label:string,kind:string} */
function build_grouping_group_key(string $policy, string $buildMode, string $kind, string $projectLabel, string $relativeSource, ?array $manualGroup = null): array
{
	if ($policy === 'manual' && $manualGroup !== null) {
		return [
			'id' => (string) ($manualGroup['id'] ?? build_grouping_manual_group_id((string) ($manualGroup['name'] ?? 'manual'))),
			'label' => (string) ($manualGroup['name'] ?? ''),
			'kind' => 'manual',
		];
	}

	$shape = $policy;
	if ($policy === 'auto') {
		$shape = $buildMode === 'release' ? 'release' : 'incremental';
	} elseif ($policy === 'none' || $policy === 'isolated' || $policy === 'manual') {
		$shape = 'incremental';
	}

	$projectKey = $projectLabel === '.' ? 'root' : $projectLabel;
	if ($shape === 'release') {
		return [
			'id' => 'release:' . $projectKey,
			'label' => $projectLabel,
			'kind' => 'release_project',
		];
	}
	if ($shape === 'folder') {
		$folder = dirname($relativeSource);
		$folder = $folder === '.' ? '.' : normalize_config_path($folder);
		return [
			'id' => 'folder:' . $projectKey . ':' . $folder,
			'label' => $folder,
			'kind' => 'folder',
		];
	}
	if ($shape === 'package') {
		$parts = explode('/', normalize_config_path($relativeSource));
		$package = count($parts) > 1 ? (string) $parts[0] : '.';
		return [
			'id' => 'package:' . $projectKey . ':' . $package,
			'label' => $package,
			'kind' => 'package',
		];
	}

	return [
		'id' => 'source:' . $projectKey . ':' . $kind . ':' . normalize_config_path($relativeSource),
		'label' => normalize_config_path($relativeSource),
		'kind' => $kind . '_source',
	];
}

/** @return array<string,mixed> */
function normalize_build_grouping_report(array $report): array
{
	$groups = [];
	foreach (is_array($report['groups'] ?? null) ? $report['groups'] : [] as $group) {
		if (!is_array($group)) {
			continue;
		}
		$groups[] = [
			'id' => trim((string) ($group['id'] ?? '')),
			'label' => trim((string) ($group['label'] ?? '')),
			'kind' => trim((string) ($group['kind'] ?? '')),
			'project_root' => trim((string) ($group['project_root'] ?? '.')),
			'generated_sources' => normalize_string_list($group['generated_sources'] ?? []),
			'native_sources' => normalize_string_list($group['native_sources'] ?? []),
			'objects' => normalize_string_list($group['objects'] ?? []),
			'rebuilt_objects' => normalize_string_list($group['rebuilt_objects'] ?? []),
			'object_count' => max(0, (int) ($group['object_count'] ?? count(is_array($group['objects'] ?? null) ? $group['objects'] : []))),
			'rebuilt_object_count' => max(0, (int) ($group['rebuilt_object_count'] ?? count(is_array($group['rebuilt_objects'] ?? null) ? $group['rebuilt_objects'] : []))),
			'generated_source_count' => max(0, (int) ($group['generated_source_count'] ?? count(is_array($group['generated_sources'] ?? null) ? $group['generated_sources'] : []))),
			'native_source_count' => max(0, (int) ($group['native_source_count'] ?? count(is_array($group['native_sources'] ?? null) ? $group['native_sources'] : []))),
			'changed' => (bool) ($group['changed'] ?? false),
		];
	}
	usort($groups, static fn (array $left, array $right): int => strcmp((string) ($left['id'] ?? ''), (string) ($right['id'] ?? '')));
	$fanout = is_array($report['object_fanout'] ?? null) ? $report['object_fanout'] : [];
	$manualGroups = normalize_build_grouping_manual_group_rows(is_array($report['manual_groups'] ?? null) ? $report['manual_groups'] : []);
	$manualSourceCount = 0;
	foreach ($manualGroups as $manualGroup) {
		$manualSourceCount += count(is_array($manualGroup['sources'] ?? null) ? $manualGroup['sources'] : []);
	}
	$moduleGroups = normalize_build_grouping_module_group_rows(is_array($report['module_groups'] ?? null) ? $report['module_groups'] : []);
	$moduleSourceCount = 0;
	foreach ($moduleGroups as $moduleGroup) {
		$moduleSourceCount += count(is_array($moduleGroup['sources'] ?? null) ? $moduleGroup['sources'] : []);
	}
	$policy = trim((string) ($report['policy'] ?? ''));
	$generatedGroupingEnabled = (bool) ($report['generated_grouping_enabled'] ?? false);
	$normalized = [
		'policy' => $policy,
		'source' => trim((string) ($report['source'] ?? '')),
		'status' => trim((string) ($report['status'] ?? '')),
		'build_mode' => trim((string) ($report['build_mode'] ?? '')),
		'compile_unit_strategy' => trim((string) ($report['compile_unit_strategy'] ?? '')),
		'native_strategy' => trim((string) ($report['native_strategy'] ?? '')),
		'deterministic' => (bool) ($report['deterministic'] ?? false),
		'allowed_policies' => normalize_string_list($report['allowed_policies'] ?? []),
		'notes' => normalize_string_list($report['notes'] ?? []),
		'total_groups' => max(0, (int) ($report['total_groups'] ?? count($groups))),
		'changed_group_count' => max(0, (int) ($report['changed_group_count'] ?? 0)),
		'changed_groups' => normalize_string_list($report['changed_groups'] ?? []),
		'object_fanout' => [
			'rebuilt_object_count' => max(0, (int) ($fanout['rebuilt_object_count'] ?? 0)),
			'rebuilt_generated_object_count' => max(0, (int) ($fanout['rebuilt_generated_object_count'] ?? 0)),
			'rebuilt_native_object_count' => max(0, (int) ($fanout['rebuilt_native_object_count'] ?? 0)),
		],
		'groups' => $groups,
	];
	if ($policy === 'manual') {
		$normalized['manual_groups'] = $manualGroups;
		$normalized['manual_group_count'] = max(0, (int) ($report['manual_group_count'] ?? count($manualGroups)));
		$normalized['manual_assigned_source_count'] = max(0, (int) ($report['manual_assigned_source_count'] ?? $manualSourceCount));
		$normalized['manual_unassigned_source_count'] = max(0, (int) ($report['manual_unassigned_source_count'] ?? 0));
		$normalized['manual_unassigned_sources'] = normalize_string_list($report['manual_unassigned_sources'] ?? []);
	}
	if ($policy === 'module') {
		$normalized['module_groups'] = $moduleGroups;
		$normalized['module_group_count'] = max(0, (int) ($report['module_group_count'] ?? count($moduleGroups)));
		$normalized['module_assigned_source_count'] = max(0, (int) ($report['module_assigned_source_count'] ?? $moduleSourceCount));
		$normalized['module_unassigned_source_count'] = max(0, (int) ($report['module_unassigned_source_count'] ?? 0));
		$normalized['module_unassigned_sources'] = normalize_string_list($report['module_unassigned_sources'] ?? []);
	}
	if ($policy === 'manual' || $generatedGroupingEnabled) {
		$normalized['generated_grouping_enabled'] = $generatedGroupingEnabled;
		$changedGroupReasons = [];
		foreach (is_array($report['changed_group_reasons'] ?? null) ? $report['changed_group_reasons'] : [] as $row) {
			if (!is_array($row)) {
				continue;
			}
			$groupId = trim((string) ($row['group_id'] ?? ''));
			if ($groupId === '') {
				continue;
			}
			$changedGroupReasons[] = [
				'group_id' => $groupId,
				'reasons' => normalize_string_list($row['reasons'] ?? []),
			];
		}
		$normalized['changed_group_reasons'] = $changedGroupReasons;
	}
	if ($policy === 'auto') {
		$normalized['auto_evidence'] = normalize_auto_build_grouping_evidence(is_array($report['auto_evidence'] ?? null) ? $report['auto_evidence'] : []);
		$normalized['auto_source_decisions'] = normalize_auto_build_grouping_source_decisions(is_array($report['auto_source_decisions'] ?? null) ? $report['auto_source_decisions'] : []);
	}
	return $normalized;
}

/** @return array<string,mixed> */
function normalize_auto_build_grouping_evidence(array $evidence): array
{
	$selectedPolicy = trim((string) ($evidence['selected_policy'] ?? ''));
	if (!in_array($selectedPolicy, ['incremental', 'folder', 'package', 'release'], true)) {
		$selectedPolicy = 'incremental';
	}
	return [
		'source' => trim((string) ($evidence['source'] ?? 'none')),
		'selected_policy' => $selectedPolicy,
		'reasons' => normalize_string_list($evidence['reasons'] ?? []),
		'prior_ninja_subprocess_ms' => max(0, (int) ($evidence['prior_ninja_subprocess_ms'] ?? 0)),
		'prior_rebuilt_object_count' => max(0, (int) ($evidence['prior_rebuilt_object_count'] ?? 0)),
		'prior_rebuilt_generated_object_count' => max(0, (int) ($evidence['prior_rebuilt_generated_object_count'] ?? 0)),
		'prior_changed_group_count' => max(0, (int) ($evidence['prior_changed_group_count'] ?? 0)),
		'root_generated_source_count' => max(0, (int) ($evidence['root_generated_source_count'] ?? 0)),
	];
}

/** @return list<array{source:string,decision:string,group_id:string,group_label:string,reason:string,object_size_bytes:int}> */
function normalize_auto_build_grouping_source_decisions(array $decisions): array
{
	$rows = [];
	foreach ($decisions as $decision) {
		if (!is_array($decision)) {
			continue;
		}
		$source = normalize_config_path((string) ($decision['source'] ?? ''));
		if ($source === '') {
			continue;
		}
		$decisionKind = trim((string) ($decision['decision'] ?? 'isolated'));
		if (!in_array($decisionKind, ['grouped', 'isolated'], true)) {
			$decisionKind = 'isolated';
		}
		$rows[] = [
			'source' => $source,
			'decision' => $decisionKind,
			'group_id' => trim((string) ($decision['group_id'] ?? '')),
			'group_label' => trim((string) ($decision['group_label'] ?? '')),
			'reason' => trim((string) ($decision['reason'] ?? '')),
			'object_size_bytes' => max(0, (int) ($decision['object_size_bytes'] ?? 0)),
		];
	}
	usort($rows, static fn (array $left, array $right): int => strcmp((string) ($left['source'] ?? ''), (string) ($right['source'] ?? '')));
	return $rows;
}

/** @param list<array<string,mixed>> $groups @return list<array{id:string,name:string,sources:list<string>}> */
function normalize_build_grouping_manual_group_rows(array $groups): array
{
	$normalized = [];
	foreach ($groups as $group) {
		if (!is_array($group)) {
			continue;
		}
		$name = trim((string) ($group['name'] ?? ($group['label'] ?? '')));
		if ($name === '') {
			continue;
		}
		$sources = normalize_string_list($group['sources'] ?? []);
		$normalized[] = [
			'id' => trim((string) ($group['id'] ?? build_grouping_manual_group_id($name))),
			'name' => $name,
			'sources' => $sources,
		];
	}
	usort($normalized, static fn (array $left, array $right): int => strcmp((string) ($left['id'] ?? ''), (string) ($right['id'] ?? '')));
	return $normalized;
}

/** @return list<array{id:string,name:string,project_root:string,sources:list<string>}> */
function normalize_build_grouping_module_group_rows(array $groups): array
{
	$normalized = [];
	foreach ($groups as $group) {
		if (!is_array($group)) {
			continue;
		}
		$name = trim((string) ($group['name'] ?? ($group['label'] ?? '')));
		$projectLabel = trim((string) ($group['project_root'] ?? '.'));
		if ($projectLabel === '') {
			$projectLabel = '.';
		}
		if ($name === '') {
			continue;
		}
		$sources = normalize_string_list($group['sources'] ?? []);
		if ($sources === []) {
			continue;
		}
		$normalized[] = [
			'id' => trim((string) ($group['id'] ?? build_grouping_module_group_id($projectLabel, $name))),
			'name' => $name,
			'project_root' => $projectLabel,
			'sources' => $sources,
		];
	}
	usort($normalized, static fn (array $left, array $right): int => strcmp((string) ($left['id'] ?? ''), (string) ($right['id'] ?? '')));
	return $normalized;
}

/**
 * @param array<string,array<string,mixed>> $projectContexts
 * @param list<array{project_root:string,relative_php:string,generated_header?:string,generated_cpp:string,object_path:string,is_entrypoint:bool,force_include_header:?string}> $generatedUnits
 * @param list<array{project_root:string,source_path:string,object_path:string,force_include_header:?string}> $nativeCppUnits
 * @param list<array<string,mixed>>|null $dependencySummaries
 * @return array<string,mixed>
 */
function collect_project_unit_force_include_report(string $projectRoot, array $projectContexts, array $generatedUnits, array $nativeCppUnits, bool $useStanDependencyState = true, array $packChanges = [], ?array $dependencySummaries = null): array
{
	$units = array_merge($generatedUnits, $nativeCppUnits);
	$headerCounts = [];
	foreach ($units as $unit) {
		$header = is_string($unit['force_include_header'] ?? null) ? normalize_path($unit['force_include_header']) : '';
		if ($header === '') {
			continue;
		}
		$headerCounts[$header] = ($headerCounts[$header] ?? 0) + 1;
	}
	ksort($headerCounts, SORT_STRING);

	$headers = [];
	foreach ($headerCounts as $header => $unitCount) {
		$contents = is_file($header) ? file_get_contents($header) : null;
		$contents = is_string($contents) ? $contents : '';
		$headers[] = [
			'path' => normalize_config_path(relative_path($projectRoot, $header)),
			'unit_count' => $unitCount,
			'line_count' => $contents === '' ? 0 : substr_count($contents, PHP_EOL),
			'byte_count' => strlen($contents),
			'mode' => project_unit_force_include_header_mode($header),
		];
	}

	$dependencySummaries = $dependencySummaries ?? collect_project_unit_dependency_summaries($projectRoot, $projectContexts, $generatedUnits, $useStanDependencyState);
	$statusCounts = summarize_project_unit_dependency_status_counts($dependencySummaries);
	$candidateBlockerCounts = summarize_project_unit_candidate_blocker_counts($dependencySummaries);
	$nativeUnitCount = count($nativeCppUnits);

	return [
		'total_units' => count($units),
		'units_with_force_include' => array_sum($headerCounts),
		'distinct_headers' => count($headers),
		'active_scoped_units' => $statusCounts['active_scoped_units'],
		'active_broad_fallback_units' => $statusCounts['active_broad_fallback_units'],
		'candidate_scoped_units' => $statusCounts['candidate_scoped_units'],
		'candidate_blocked_units' => $statusCounts['candidate_blocked_units'],
		'candidate_blocker_counts' => $candidateBlockerCounts,
		'native_units' => $nativeUnitCount,
		'native_broad_fallback_units' => $nativeUnitCount,
		'native_policy' => [
			'status' => $nativeUnitCount > 0 ? 'broad_fallback_without_dependency_manifest' : 'not_applicable',
			'reason' => $nativeUnitCount > 0
				? 'native C++ project-unit dependencies are not modeled; native units use broad-equivalent packs'
				: 'no native C++ units in this build',
		],
		'headers' => $headers,
		'pack_changes' => normalize_project_unit_pack_changes($packChanges),
		'dependency_summary_artifact' => normalize_project_unit_dependency_summary_artifact_info([]),
		'dependency_summaries' => $dependencySummaries,
	];
}

/**
 * @return array{changed_headers:list<string>,removed_headers:list<string>,changed_count:int,removed_count:int}
 */
function normalize_project_unit_pack_changes(array $packChanges): array
{
	$changedHeaders = normalize_string_list($packChanges['changed_headers'] ?? []);
	$removedHeaders = normalize_string_list($packChanges['removed_headers'] ?? []);
	return [
		'changed_headers' => $changedHeaders,
		'removed_headers' => $removedHeaders,
		'changed_count' => max(0, (int) ($packChanges['changed_count'] ?? count($changedHeaders))),
		'removed_count' => max(0, (int) ($packChanges['removed_count'] ?? count($removedHeaders))),
	];
}

/**
 * @param list<array<string,mixed>> $dependencySummaries
 * @return array{active_scoped_units:int,active_broad_fallback_units:int,candidate_scoped_units:int,candidate_blocked_units:int}
 */
function summarize_project_unit_dependency_status_counts(array $dependencySummaries): array
{
	$counts = [
		'active_scoped_units' => 0,
		'active_broad_fallback_units' => 0,
		'candidate_scoped_units' => 0,
		'candidate_blocked_units' => 0,
	];
	foreach ($dependencySummaries as $summary) {
		if (!is_array($summary)) {
			continue;
		}
		if (($summary['status'] ?? null) === 'scoped') {
			$counts['active_scoped_units']++;
		} else {
			$counts['active_broad_fallback_units']++;
		}
		if (($summary['candidate_status'] ?? null) === 'candidate_scoped') {
			$counts['candidate_scoped_units']++;
		} else {
			$counts['candidate_blocked_units']++;
		}
	}
	return $counts;
}

/**
 * @param list<array<string,mixed>> $dependencySummaries
 * @return list<array{reason:string,unit_count:int}>
 */
function summarize_project_unit_candidate_blocker_counts(array $dependencySummaries): array
{
	$counts = [];
	foreach ($dependencySummaries as $summary) {
		if (!is_array($summary)) {
			continue;
		}
		foreach (normalize_string_list($summary['candidate_blocking_reasons'] ?? []) as $reason) {
			$counts[$reason] = ($counts[$reason] ?? 0) + 1;
		}
	}
	$rows = [];
	foreach ($counts as $reason => $unitCount) {
		$rows[] = [
			'reason' => $reason,
			'unit_count' => $unitCount,
		];
	}
	usort($rows, static function (array $left, array $right): int {
		$byCount = ((int) ($right['unit_count'] ?? 0)) <=> ((int) ($left['unit_count'] ?? 0));
		return $byCount !== 0 ? $byCount : strcmp((string) ($left['reason'] ?? ''), (string) ($right['reason'] ?? ''));
	});
	return $rows;
}

/**
 * @param array<string,array<string,mixed>> $projectContexts
 * @param list<array{project_root:string,relative_php:string,generated_header?:string,generated_cpp:string,object_path:string,is_entrypoint:bool,force_include_header:?string}> $generatedUnits
 * @param list<array<string,mixed>>|null $dependencySummaries
 * @return list<array<string,mixed>>
 */
function apply_project_unit_scoped_force_include_candidates(string $projectRoot, array $projectContexts, array &$generatedUnits, bool $useStanDependencyState = true, ?array $dependencySummaries = null): array
{
	$normalizedProjectRoot = normalize_path($projectRoot);
	$summaries = $dependencySummaries ?? collect_project_unit_dependency_summaries($normalizedProjectRoot, $projectContexts, $generatedUnits, $useStanDependencyState);
	$scopedPackBySourceKey = [];
	foreach ($summaries as $summary) {
		if (!is_array($summary) || !project_unit_scoped_pack_is_activation_eligible($summary)) {
			continue;
		}
		$sourceKey = trim((string) ($summary['source_key'] ?? ''));
		$candidatePackHeader = trim((string) ($summary['candidate_pack_header'] ?? ''));
		$candidateHeaders = is_array($summary['candidate_scoped_headers'] ?? null) ? $summary['candidate_scoped_headers'] : [];
		if ($sourceKey === '' || $candidatePackHeader === '' || $candidateHeaders === []) {
			continue;
		}
		$packHeaderPath = normalize_path($normalizedProjectRoot . '/' . $candidatePackHeader);
		$includeHeaders = [];
		foreach ($candidateHeaders as $candidateHeader) {
			$header = normalize_path($normalizedProjectRoot . '/' . trim((string) $candidateHeader));
			if ($header !== '') {
				$includeHeaders[] = $header;
			}
		}
		$includeHeaders = array_values(array_unique($includeHeaders));
		if ($includeHeaders === []) {
			continue;
		}
		$includeHeaders = sort_project_unit_include_headers($includeHeaders);
		write_text_file($packHeaderPath, render_project_unit_force_include_header($packHeaderPath, '', $includeHeaders));
		$scopedPackBySourceKey[$sourceKey] = $packHeaderPath;
	}
	if ($scopedPackBySourceKey === []) {
		return $summaries;
	}
	foreach ($generatedUnits as &$unit) {
		$unitProjectRoot = normalize_path((string) ($unit['project_root'] ?? ''));
		$relativePhp = normalize_config_path((string) ($unit['relative_php'] ?? ''));
		$sourcePath = normalize_path($unitProjectRoot . '/' . $relativePhp);
		$sourceKey = project_unit_stan_source_key($normalizedProjectRoot, $sourcePath);
		if (isset($scopedPackBySourceKey[$sourceKey])) {
			$unit['force_include_header'] = $scopedPackBySourceKey[$sourceKey];
		}
	}
	unset($unit);
	foreach ($summaries as &$summary) {
		if (!is_array($summary)) {
			continue;
		}
		$sourceKey = trim((string) ($summary['source_key'] ?? ''));
		if ($sourceKey === '' || !isset($scopedPackBySourceKey[$sourceKey])) {
			continue;
		}
		$summary['force_include_header'] = normalize_config_path(relative_path($normalizedProjectRoot, $scopedPackBySourceKey[$sourceKey]));
		$summary['status'] = 'scoped';
	}
	unset($summary);
	return $summaries;
}

/**
 * @param list<array{project_root:string,relative_php:string,generated_header?:string,generated_cpp:string,object_path:string,is_entrypoint:bool,force_include_header:?string}> $generatedUnits
 * @param list<array<string,mixed>> $summaries
 * @return list<array<string,mixed>>
 */
function apply_project_unit_active_force_include_headers_to_dependency_summaries(string $projectRoot, array $generatedUnits, array $summaries): array
{
	$normalizedProjectRoot = normalize_path($projectRoot);
	$headerBySourceKey = [];
	foreach ($generatedUnits as $unit) {
		$unitProjectRoot = normalize_path((string) ($unit['project_root'] ?? ''));
		$relativePhp = normalize_config_path((string) ($unit['relative_php'] ?? ''));
		if ($unitProjectRoot === '' || $relativePhp === '') {
			continue;
		}
		$sourceKey = project_unit_stan_source_key($normalizedProjectRoot, normalize_path($unitProjectRoot . '/' . $relativePhp));
		$forceIncludeHeader = is_string($unit['force_include_header'] ?? null) ? normalize_path($unit['force_include_header']) : '';
		$headerBySourceKey[$sourceKey] = $forceIncludeHeader !== ''
			? normalize_config_path(relative_path($normalizedProjectRoot, $forceIncludeHeader))
			: '';
	}
	foreach ($summaries as &$summary) {
		if (!is_array($summary)) {
			continue;
		}
		$sourceKey = trim((string) ($summary['source_key'] ?? ''));
		if ($sourceKey === '' || !array_key_exists($sourceKey, $headerBySourceKey)) {
			continue;
		}
		$forceIncludeHeader = $headerBySourceKey[$sourceKey];
		$summary['force_include_header'] = $forceIncludeHeader;
		$summary['status'] = $forceIncludeHeader !== ''
			&& $forceIncludeHeader === trim((string) ($summary['candidate_pack_header'] ?? ''))
			&& project_unit_scoped_pack_is_activation_eligible($summary)
				? 'scoped'
				: 'fallback_broad';
	}
	unset($summary);
	return $summaries;
}

/** @param array<string,mixed> $summary */
function project_unit_scoped_pack_is_activation_eligible(array $summary): bool
{
	$candidatePackHeader = trim((string) ($summary['candidate_pack_header'] ?? ''));
	$candidateHeaders = is_array($summary['candidate_scoped_headers'] ?? null) ? $summary['candidate_scoped_headers'] : [];
	if ($candidatePackHeader === '' || $candidateHeaders === []) {
		return false;
	}

	foreach (normalize_string_list($summary['candidate_blocking_reasons'] ?? []) as $reason) {
		if (project_unit_scoped_pack_blocker_is_hard($reason)) {
			return false;
		}
	}
	return true;
}

function project_unit_scoped_pack_blocker_is_hard(string $reason): bool
{
	return $reason === 'project unit dependency state unavailable'
		|| $reason === 'source summary unavailable'
		|| $reason === 'own generated header unavailable'
		|| str_starts_with($reason, 'unresolved dependency key `')
		|| str_contains($reason, 'unmodeled dependency evidence');
}

/**
 * @param array<string,array<string,mixed>> $projectContexts
 * @param list<array{project_root:string,relative_php:string,generated_header?:string,generated_cpp:string,object_path:string,is_entrypoint:bool,force_include_header:?string}> $generatedUnits
 * @param list<array{project_root:string,source_path:string,object_path:string,force_include_header:?string}> $nativeCppUnits
 * @param array<string,string> $projectUnitForceIncludes
 */
function cleanup_project_unit_pack_headers(array $projectContexts, array $generatedUnits, array $nativeCppUnits, array $projectUnitForceIncludes): void
{
	$activeHeadersByProjectRoot = [];
	foreach ($projectContexts as $projectRoot => $projectContext) {
		$normalizedProjectRoot = normalize_path($projectRoot);
		$activeHeadersByProjectRoot[$normalizedProjectRoot] = [];
		$broadPackHeader = $projectUnitForceIncludes[$normalizedProjectRoot] ?? null;
		if (is_string($broadPackHeader) && $broadPackHeader !== '') {
			$activeHeadersByProjectRoot[$normalizedProjectRoot][normalize_path($broadPackHeader)] = true;
		}
		if (is_string($projectContext['generated_dir'] ?? null)) {
			$activeHeadersByProjectRoot[$normalizedProjectRoot][normalize_path($projectContext['generated_dir'] . '/__project_units/broad.hpp')] = true;
		}
	}

	foreach (array_merge($generatedUnits, $nativeCppUnits) as $unit) {
		$unitProjectRoot = normalize_path((string) ($unit['project_root'] ?? ''));
		$forceIncludeHeader = is_string($unit['force_include_header'] ?? null) ? normalize_path($unit['force_include_header']) : '';
		if ($unitProjectRoot === '' || $forceIncludeHeader === '' || !isset($activeHeadersByProjectRoot[$unitProjectRoot])) {
			continue;
		}
		$activeHeadersByProjectRoot[$unitProjectRoot][$forceIncludeHeader] = true;
	}

	foreach ($projectContexts as $projectRoot => $projectContext) {
		$normalizedProjectRoot = normalize_path($projectRoot);
		if (!is_string($projectContext['generated_dir'] ?? null)) {
			continue;
		}
		$packDir = normalize_path($projectContext['generated_dir'] . '/__project_units');
		if (!is_dir($packDir)) {
			continue;
		}
		$activeHeaders = $activeHeadersByProjectRoot[$normalizedProjectRoot] ?? [];
		$items = scandir($packDir);
		if ($items === false) {
			scpp_fail('Failed to read project unit pack directory: ' . $packDir . PHP_EOL, 2);
		}
		foreach ($items as $item) {
			if (!project_unit_pack_header_filename_is_build_owned($item)) {
				continue;
			}
			$headerPath = normalize_path($packDir . '/' . $item);
			if (isset($activeHeaders[$headerPath])) {
				continue;
			}
			if (is_file($headerPath) && !@unlink($headerPath)) {
				scpp_fail('Failed to remove stale project unit pack header: ' . $headerPath . PHP_EOL, 2);
			}
		}
		write_project_unit_pack_manifest($normalizedProjectRoot, $packDir, array_keys($activeHeaders));
	}
}

function project_unit_pack_header_filename_is_build_owned(string $filename): bool
{
	return preg_match('/^(?:scoped-)?[0-9a-f]{16}\.hpp$/', $filename) === 1;
}

/**
 * @param array<string,array<string,mixed>> $projectContexts
 * @return array<string,string>
 */
function capture_project_unit_pack_header_state(array $projectContexts): array
{
	$state = [];
	foreach ($projectContexts as $projectContext) {
		if (!is_array($projectContext) || !is_string($projectContext['generated_dir'] ?? null)) {
			continue;
		}
		$generatedDir = normalize_path($projectContext['generated_dir']);
		foreach ([$generatedDir . '/__project_units.hpp', $generatedDir . '/__project_units/broad.hpp'] as $path) {
			$path = normalize_path($path);
			if (is_file($path)) {
				$state[$path] = hash_file('sha256', $path) ?: '';
			}
		}
		$packDir = normalize_path($generatedDir . '/__project_units');
		if (!is_dir($packDir)) {
			continue;
		}
		$items = scandir($packDir);
		if ($items === false) {
			continue;
		}
		foreach ($items as $item) {
			if ($item === '.' || $item === '..') {
				continue;
			}
			if (!project_unit_pack_header_filename_is_build_owned($item)) {
				continue;
			}
			$path = normalize_path($packDir . '/' . $item);
			if (is_file($path)) {
				$state[$path] = hash_file('sha256', $path) ?: '';
			}
		}
	}
	ksort($state, SORT_STRING);
	return $state;
}

/**
 * @param array<string,string> $before
 * @param array<string,string> $after
 * @return array{changed_headers:list<string>,removed_headers:list<string>,changed_count:int,removed_count:int}
 */
function compare_project_unit_pack_header_state(string $projectRoot, array $before, array $after): array
{
	$changed = [];
	foreach ($after as $path => $hash) {
		if (($before[$path] ?? null) !== $hash) {
			$changed[] = normalize_config_path(relative_path($projectRoot, (string) $path));
		}
	}
	$removed = [];
	foreach ($before as $path => $_hash) {
		if (!array_key_exists($path, $after)) {
			$removed[] = normalize_config_path(relative_path($projectRoot, (string) $path));
		}
	}
	sort($changed, SORT_STRING);
	sort($removed, SORT_STRING);
	return [
		'changed_headers' => array_values(array_unique($changed)),
		'removed_headers' => array_values(array_unique($removed)),
		'changed_count' => count(array_unique($changed)),
		'removed_count' => count(array_unique($removed)),
	];
}

/** @param list<string> $activeHeaderPaths */
function write_project_unit_pack_manifest(string $projectRoot, string $packDir, array $activeHeaderPaths): void
{
	$headers = [];
	foreach ($activeHeaderPaths as $headerPath) {
		$normalizedHeaderPath = normalize_path($headerPath);
		if (normalize_path(dirname($normalizedHeaderPath)) !== $packDir) {
			continue;
		}
		if (!is_file($normalizedHeaderPath)) {
			continue;
		}
		$headers[] = normalize_config_path(relative_path($projectRoot, $normalizedHeaderPath));
	}
	sort($headers, SORT_STRING);
	$manifest = [
		'version' => 1,
		'pack_headers' => array_values(array_unique($headers)),
	];
	$json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	if (!is_string($json)) {
		scpp_fail('Failed to encode project unit pack manifest: ' . $packDir . PHP_EOL, 2);
	}
	write_text_file(normalize_path($packDir . '/manifest.json'), $json . PHP_EOL);
}

/**
 * @param array<string,array<string,mixed>> $projectContexts
 * @param list<array{project_root:string,relative_php:string,generated_header?:string,generated_cpp:string,object_path:string,is_entrypoint:bool,force_include_header:?string}> $generatedUnits
 * @return list<array<string,mixed>>
 */
function collect_project_unit_dependency_summaries(string $projectRoot, array $projectContexts, array $generatedUnits, bool $useStanDependencyState = true): array
{
	$normalizedProjectRoot = normalize_path($projectRoot);
	$rootContext = $projectContexts[$normalizedProjectRoot] ?? null;
	$stanStatePath = is_array($rootContext) && is_string($rootContext['cache_dir'] ?? null)
		? normalize_path($rootContext['cache_dir'] . '/' . SCPP_STAN_STATE_FILE)
		: '';
	$buildDependencyStatePath = is_array($rootContext) && is_string($rootContext['cache_dir'] ?? null)
		? normalize_path($rootContext['cache_dir'] . '/' . SCPP_PROJECT_UNIT_DEPENDENCY_STATE_FILE)
		: '';
	$stanDependencyState = $useStanDependencyState ? load_project_unit_dependency_state_from_stan_state($stanStatePath) : null;
	$buildDependencyState = $useStanDependencyState ? null : load_project_unit_dependency_state_from_build_state($buildDependencyStatePath);
	$hasStanDependencyState = $stanDependencyState !== null;
	$stanDependencyKeys = is_array($stanDependencyState['dependency_keys'] ?? null) ? $stanDependencyState['dependency_keys'] : [];
	$stanFileSummaries = is_array($stanDependencyState['file_summaries'] ?? null) ? $stanDependencyState['file_summaries'] : [];
	$buildDependencyKeys = is_array($buildDependencyState['dependency_keys'] ?? null) ? $buildDependencyState['dependency_keys'] : [];
	$buildFileSummaries = is_array($buildDependencyState['file_summaries'] ?? null) ? $buildDependencyState['file_summaries'] : [];
	$dependencyResolver = new StanDependencyResolver();
	$symbolIndexBuilder = new StanSymbolIndexBuilder();
	$stanDependencyLookup = $stanFileSummaries !== [] ? $dependencyResolver->buildResolutionLookup($symbolIndexBuilder->build($stanFileSummaries)) : [];
	$buildDependencyLookup = $buildFileSummaries !== [] ? $dependencyResolver->buildResolutionLookup($symbolIndexBuilder->build($buildFileSummaries)) : [];

	$sourceKeyToHeader = [];
	foreach ($generatedUnits as $unit) {
		$sourceProjectRoot = normalize_path((string) ($unit['project_root'] ?? ''));
		$sourcePath = normalize_path($sourceProjectRoot . '/' . (string) ($unit['relative_php'] ?? ''));
		$generatedHeader = normalize_path((string) ($unit['generated_header'] ?? ''));
		if ($sourceProjectRoot === '' || $sourcePath === '' || $generatedHeader === '') {
			continue;
		}
		$sourceKeyToHeader[project_unit_stan_source_key($normalizedProjectRoot, $sourcePath)] = [
			'project_root' => $sourceProjectRoot,
			'header' => $generatedHeader,
		];
	}
	$stanDependencyCategoriesBySource = collect_project_unit_dependency_category_rows_by_source($normalizedProjectRoot, $stanFileSummaries, $stanDependencyLookup, $sourceKeyToHeader);
	$buildDependencyCategoriesBySource = collect_project_unit_dependency_category_rows_by_source($normalizedProjectRoot, $buildFileSummaries, $buildDependencyLookup, $sourceKeyToHeader);
	$stanPublicDependencyKeys = build_project_unit_public_dependency_key_map_from_category_rows($stanDependencyCategoriesBySource);
	$buildPublicDependencyKeys = build_project_unit_public_dependency_key_map_from_category_rows($buildDependencyCategoriesBySource);

	$summaries = [];
	foreach ($generatedUnits as $unit) {
		$unitProjectRoot = normalize_path((string) ($unit['project_root'] ?? ''));
		$relativePhp = normalize_config_path((string) ($unit['relative_php'] ?? ''));
		$sourcePath = normalize_path($unitProjectRoot . '/' . $relativePhp);
		$sourceKey = project_unit_stan_source_key($normalizedProjectRoot, $sourcePath);
		$dependencyKeySource = array_key_exists($sourceKey, $stanDependencyKeys) ? 'stan' : (array_key_exists($sourceKey, $buildDependencyKeys) ? 'build' : 'none');
		$dependencyKeySourceMap = $dependencyKeySource === 'stan' ? $stanDependencyKeys : $buildDependencyKeys;
		$dependencyKeys = array_values(array_filter(
			array_map(static fn ($value): string => trim((string) $value), is_array($dependencyKeySourceMap[$sourceKey] ?? null) ? $dependencyKeySourceMap[$sourceKey] : []),
			static fn (string $value): bool => $value !== ''
		));
		sort($dependencyKeys, SORT_STRING);
		$projectHeaderDependencyKeys = array_values(array_filter(
			$dependencyKeys,
			static fn (string $dependencyKey): bool => project_unit_dependency_key_requires_project_header_resolution($dependencyKey)
		));

		$directLocalHeaders = [];
		$directLocalHeaderPaths = [];
		$unresolvedDependencyKeys = [];
		foreach ($projectHeaderDependencyKeys as $dependencyKey) {
			$dependencyHeader = is_array($sourceKeyToHeader[$dependencyKey] ?? null) ? $sourceKeyToHeader[$dependencyKey] : null;
			if ($dependencyHeader !== null && normalize_path((string) ($dependencyHeader['project_root'] ?? '')) !== $unitProjectRoot) {
				continue;
			}
			$header = is_array($dependencyHeader) ? normalize_path((string) ($dependencyHeader['header'] ?? '')) : '';
			if ($header !== '') {
				$directLocalHeaders[$header] = normalize_config_path(relative_path($normalizedProjectRoot, $header));
				$directLocalHeaderPaths[$header] = $header;
				continue;
			}
			$unresolvedDependencyKeys[] = $dependencyKey;
		}
		$publicDependencyKeyMap = $dependencyKeySource === 'stan' ? $stanPublicDependencyKeys : ($dependencyKeySource === 'build' ? $buildPublicDependencyKeys : []);
		$scopedLocalHeaderPaths = collect_project_unit_scoped_same_project_header_paths($projectHeaderDependencyKeys, $publicDependencyKeyMap, $sourceKeyToHeader, $unitProjectRoot);
		$scopedLocalHeaders = array_map(
			static fn (string $header): string => normalize_config_path(relative_path($normalizedProjectRoot, $header)),
			array_values($scopedLocalHeaderPaths)
		);

		$dependencyExportHeaders = [];
		$dependencyExportHeaderPaths = [];
		foreach (collect_transitive_project_dependency_roots($unitProjectRoot, $projectContexts) as $dependencyRoot) {
			$dependencyContext = $projectContexts[$dependencyRoot] ?? null;
			if (!is_array($dependencyContext) || !is_string($dependencyContext['generated_dir'] ?? null)) {
				continue;
			}
			$dependencyExportHeader = normalize_path($dependencyContext['generated_dir'] . '/__project.hpp');
			$dependencyExportHeaderPaths[$dependencyExportHeader] = $dependencyExportHeader;
			$dependencyExportHeaders[] = normalize_config_path(relative_path($normalizedProjectRoot, $dependencyExportHeader));
		}
		sort($dependencyExportHeaders, SORT_STRING);

		$unitContext = $projectContexts[$unitProjectRoot] ?? null;
		$unitGeneratedDir = is_array($unitContext) && is_string($unitContext['generated_dir'] ?? null)
			? normalize_path($unitContext['generated_dir'])
			: normalize_path(dirname((string) ($unit['generated_header'] ?? '')));
		$forwardHeader = normalize_path($unitGeneratedDir . '/__project_fwd.hpp');
		$ownHeader = normalize_path((string) ($unit['generated_header'] ?? ''));
		$candidateHeaderPaths = build_project_unit_candidate_scoped_header_paths(
			$dependencyExportHeaderPaths,
			is_file($forwardHeader) ? $forwardHeader : null,
			array_values($scopedLocalHeaderPaths),
			$ownHeader
		);
		$candidateHeaders = array_map(
			static fn (string $header): string => normalize_config_path(relative_path($normalizedProjectRoot, $header)),
			$candidateHeaderPaths
		);
		$candidateHash = $candidateHeaderPaths === [] ? '' : project_unit_candidate_scoped_pack_hash($candidateHeaders);
		$candidatePackHeader = $candidateHash === ''
			? ''
			: normalize_config_path(relative_path($normalizedProjectRoot, normalize_path($unitGeneratedDir . '/__project_units/scoped-' . $candidateHash . '.hpp')));
		$sourceSummary = is_array($stanFileSummaries[$sourceKey] ?? null)
			? $stanFileSummaries[$sourceKey]
			: (is_array($buildFileSummaries[$sourceKey] ?? null) ? $buildFileSummaries[$sourceKey] : null);
		$dependencyCategories = $dependencyKeySource === 'stan'
			? ($stanDependencyCategoriesBySource[$sourceKey] ?? [])
			: ($dependencyKeySource === 'build'
				? ($buildDependencyCategoriesBySource[$sourceKey] ?? [])
				: collect_project_unit_dependency_category_rows($normalizedProjectRoot, $sourceKey, null, [], $sourceKeyToHeader));
		$hasDependencyStateForSource = $dependencyKeySource !== 'none';
		$candidate = classify_project_unit_scoped_candidate($hasDependencyStateForSource, $sourceSummary, $unresolvedDependencyKeys, $ownHeader, $dependencyCategories);
		$forceIncludeHeader = normalize_config_path(relative_path($normalizedProjectRoot, normalize_path((string) ($unit['force_include_header'] ?? ''))));
		$status = project_unit_scoped_pack_is_activation_eligible([
			'candidate_pack_header' => $candidatePackHeader,
			'candidate_scoped_headers' => $candidateHeaders,
			'candidate_blocking_reasons' => $candidate['blocking_reasons'],
		]) && $forceIncludeHeader !== '' && $forceIncludeHeader === $candidatePackHeader
			? 'scoped'
			: 'fallback_broad';

		$reasons = ['Scoped project-unit packs activate when dependency evidence is present and no hard blocker is reported'];
		if (!$hasStanDependencyState) {
			$reasons[] = 'STAN dependency state unavailable for this build';
			if ($buildDependencyState !== null) {
				$reasons[] = 'build-owned project unit dependency summary available';
			}
		} elseif (!$hasDependencyStateForSource) {
			$reasons[] = 'project unit dependency state unavailable for this source';
		} elseif ($dependencyKeys === []) {
			$reasons[] = 'no direct project unit dependency keys recorded';
		} else {
			foreach ($dependencyKeys as $dependencyKey) {
				$reasons[] = strtoupper($dependencyKeySource) . ' dependency key: ' . $dependencyKey;
			}
		}

		$summaries[] = [
			'source' => normalize_config_path(relative_path($unitProjectRoot, $sourcePath)),
			'source_key' => $sourceKey,
			'project_root' => normalize_config_path(relative_path($normalizedProjectRoot, $unitProjectRoot)),
			'generated_header' => normalize_config_path(relative_path($normalizedProjectRoot, normalize_path((string) ($unit['generated_header'] ?? '')))),
			'force_include_header' => $forceIncludeHeader,
			'status' => $status,
			'candidate_status' => $candidate['status'],
			'candidate_scoped_headers' => $candidateHeaders,
			'candidate_pack_hash' => $candidateHash,
			'candidate_pack_header' => $candidatePackHeader,
			'candidate_blocking_reasons' => $candidate['blocking_reasons'],
			'direct_source_dependencies' => $projectHeaderDependencyKeys,
			'direct_local_headers' => array_values($directLocalHeaders),
			'scoped_local_headers' => array_values(array_unique($scopedLocalHeaders)),
			'dependency_export_headers' => array_values(array_unique($dependencyExportHeaders)),
			'unresolved_dependency_keys' => $unresolvedDependencyKeys,
			'dependency_categories' => $dependencyCategories,
			'reasons' => $reasons,
		];
	}

	usort($summaries, static fn (array $left, array $right): int => strcmp((string) ($left['source_key'] ?? ''), (string) ($right['source_key'] ?? '')));
	return $summaries;
}

/**
 * @param list<string> $dependencyKeys
 * @param array<string,list<string>> $dependencyKeyMap
 * @param array<string,array{project_root:string,header:string}> $sourceKeyToHeader
 * @return array<string,string>
 */
function collect_project_unit_scoped_same_project_header_paths(array $dependencyKeys, array $dependencyKeyMap, array $sourceKeyToHeader, string $unitProjectRoot): array
{
	$seen = [];
	$visiting = [];
	$headers = [];
	$visit = static function (string $dependencyKey) use (&$visit, &$seen, &$visiting, &$headers, $dependencyKeyMap, $sourceKeyToHeader, $unitProjectRoot): void {
		$dependencyKey = trim((string) $dependencyKey);
		if ($dependencyKey === '' || isset($seen[$dependencyKey])) {
			return;
		}
		if (isset($visiting[$dependencyKey])) {
			return;
		}
		$visiting[$dependencyKey] = true;
		$transitiveDependencyKeys = normalize_string_list($dependencyKeyMap[$dependencyKey] ?? []);
		sort($transitiveDependencyKeys, SORT_STRING);
		foreach ($transitiveDependencyKeys as $transitiveDependencyKey) {
			$visit($transitiveDependencyKey);
		}
		unset($visiting[$dependencyKey]);
		$seen[$dependencyKey] = true;
		$dependencyHeader = is_array($sourceKeyToHeader[$dependencyKey] ?? null) ? $sourceKeyToHeader[$dependencyKey] : null;
		if ($dependencyHeader === null || normalize_path((string) ($dependencyHeader['project_root'] ?? '')) !== $unitProjectRoot) {
			return;
		}
		$header = normalize_path((string) ($dependencyHeader['header'] ?? ''));
		if ($header !== '') {
			$headers[$header] = $header;
		}
	};
	$rootDependencyKeys = normalize_string_list($dependencyKeys);
	sort($rootDependencyKeys, SORT_STRING);
	foreach ($rootDependencyKeys as $dependencyKey) {
		$visit($dependencyKey);
	}
	return $headers;
}

/**
 * @param array<string,array<string,mixed>> $fileSummaries
 * @param array<string,list<array<string,mixed>>> $resolutionLookup
 * @param array<string,array{project_root:string,header:string}> $sourceKeyToHeader
 * @return array<string,list<string>>
 */
function build_project_unit_public_dependency_key_map(string $projectRoot, array $fileSummaries, array $resolutionLookup, array $sourceKeyToHeader): array
{
	return build_project_unit_public_dependency_key_map_from_category_rows(
		collect_project_unit_dependency_category_rows_by_source($projectRoot, $fileSummaries, $resolutionLookup, $sourceKeyToHeader)
	);
}

/**
 * @param array<string,array<string,mixed>> $fileSummaries
 * @param array<string,list<array<string,mixed>>> $resolutionLookup
 * @param array<string,array{project_root:string,header:string}> $sourceKeyToHeader
 * @return array<string,list<array<string,mixed>>>
 */
function collect_project_unit_dependency_category_rows_by_source(string $projectRoot, array $fileSummaries, array $resolutionLookup, array $sourceKeyToHeader): array
{
	$rowsBySource = [];
	foreach ($fileSummaries as $sourceKey => $sourceSummary) {
		if (!is_string($sourceKey) || !is_array($sourceSummary)) {
			continue;
		}
		$rowsBySource[$sourceKey] = collect_project_unit_dependency_category_rows($projectRoot, $sourceKey, $sourceSummary, $resolutionLookup, $sourceKeyToHeader);
	}
	ksort($rowsBySource, SORT_STRING);
	return $rowsBySource;
}

/**
 * @param array<string,list<array<string,mixed>>> $rowsBySource
 * @return array<string,list<string>>
 */
function build_project_unit_public_dependency_key_map_from_category_rows(array $rowsBySource): array
{
	$map = [];
	foreach ($rowsBySource as $sourceKey => $rows) {
		if (!is_string($sourceKey)) {
			continue;
		}
		$dependencies = [];
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			if (!project_unit_dependency_kind_affects_public_header((string) ($row['kind'] ?? ''))) {
				continue;
			}
			foreach (normalize_string_list($row['source_dependencies'] ?? []) as $dependencyKey) {
				if (project_unit_dependency_key_requires_project_header_resolution($dependencyKey)) {
					$dependencies[$dependencyKey] = true;
				}
			}
		}
		$keys = array_keys($dependencies);
		sort($keys, SORT_STRING);
		$map[$sourceKey] = $keys;
	}
	ksort($map, SORT_STRING);
	return $map;
}

function project_unit_dependency_kind_affects_public_header(string $kind): bool
{
	return in_array($kind, [
		'extends',
		'implements',
		'function_param_type',
		'function_return_type',
		'method_param_type',
		'method_return_type',
		'property_type',
		'class_constant_value',
		'constant_value',
		'use',
		'enum_backing_type',
	], true);
}

function project_unit_dependency_key_requires_project_header_resolution(string $dependencyKey): bool
{
	return !project_unit_dependency_key_is_runtime_shallow($dependencyKey);
}

function project_unit_dependency_key_is_runtime_shallow(string $dependencyKey): bool
{
	$dependencyKey = normalize_config_path(trim($dependencyKey));
	if (!str_starts_with($dependencyKey, '@external/')) {
		return false;
	}
	return preg_match('/\/runtime_symbols_(?:legacy|strict)\.(?:php|phs)$/', $dependencyKey) === 1;
}

/**
 * @param array<string,mixed>|null $sourceSummary
 * @param array<string,list<array<string,mixed>>> $resolutionLookup
 * @param array<string,array{project_root:string,header:string}> $sourceKeyToHeader
 * @return list<array<string,mixed>>
 */
function collect_project_unit_dependency_category_rows(string $projectRoot, string $sourceKey, ?array $sourceSummary, array $resolutionLookup, array $sourceKeyToHeader): array
{
	if ($sourceSummary === null) {
		return [[
			'category' => 'missing summary',
			'kind' => '',
			'target' => '',
			'owner' => '',
			'resolution' => 'missing_summary',
			'source_dependencies' => [],
		]];
	}
	$rows = [];
	$resolver = new StanDependencyResolver();
	foreach (is_array($sourceSummary['dependencies'] ?? null) ? $sourceSummary['dependencies'] : [] as $dependency) {
		if (!is_array($dependency)) {
			continue;
		}
		$kind = trim((string) ($dependency['kind'] ?? ''));
		$target = trim((string) ($dependency['target'] ?? ''));
		$owner = isset($dependency['owner']) && is_string($dependency['owner']) ? trim($dependency['owner']) : '';
		if ($kind === '' || $target === '') {
			continue;
		}
		$matches = $resolutionLookup === [] ? [] : $resolver->resolveDependencyTarget($kind, $target, $resolutionLookup);
		if ($matches === []) {
			$rows[] = [
				'category' => 'unresolved symbol',
				'kind' => $kind,
				'target' => $target,
				'owner' => $owner,
				'resolution' => 'unresolved_symbol',
				'source_dependencies' => [],
			];
			continue;
		}
		$sourceDependencies = [];
		$hasMissingHeader = false;
		foreach ($matches as $symbol) {
			$path = normalize_path((string) ($symbol['path'] ?? ''));
			if ($path === '') {
				continue;
			}
			$sourceDependency = project_unit_stan_source_key($projectRoot, $path);
			if ($sourceDependency === $sourceKey) {
				continue;
			}
			$sourceDependencies[$sourceDependency] = true;
			if (!isset($sourceKeyToHeader[$sourceDependency])) {
				$hasMissingHeader = true;
			}
		}
		$resolvedSourceDependencies = array_keys($sourceDependencies);
		sort($resolvedSourceDependencies, SORT_STRING);
		if ($resolvedSourceDependencies === []) {
			continue;
		}
		$rows[] = [
			'category' => project_unit_dependency_category_for_kind($kind),
			'kind' => $kind,
			'target' => $target,
			'owner' => $owner,
			'resolution' => count($matches) > 1 ? 'ambiguous_symbol' : ($hasMissingHeader ? 'unresolved_dependency_key' : 'resolved'),
			'source_dependencies' => $resolvedSourceDependencies,
		];
	}
	return normalize_project_unit_dependency_category_rows($rows);
}

function project_unit_dependency_category_for_kind(string $kind): string
{
	if ($kind === 'extends' || $kind === 'implements') {
		return 'inheritance';
	}
	if ($kind === 'function_param_type' || $kind === 'function_return_type') {
		return 'function signature';
	}
	if ($kind === 'method_param_type' || $kind === 'method_return_type') {
		return 'method signature';
	}
	if ($kind === 'property_type') {
		return 'property layout';
	}
	if ($kind === 'class_constant_value') {
		return 'class constant value';
	}
	if ($kind === 'constant_value') {
		return 'constant value';
	}
	if ($kind === 'function_body_call' || $kind === 'function_body_type') {
		return 'function body';
	}
	if ($kind === 'method_body_call' || $kind === 'method_body_type') {
		return 'method body';
	}
	if ($kind === 'executable_body_call' || $kind === 'executable_body_type') {
		return 'executable body';
	}
	if ($kind === 'use') {
		return 'direct type reference';
	}
	if ($kind === 'enum_backing_type') {
		return 'direct type reference';
	}
	return str_replace('_', ' ', $kind);
}

/**
 * @return array{dependency_keys:array<string,list<string>>,file_summaries:array<string,array<string,mixed>>}|null
 */
function load_project_unit_dependency_state_from_stan_state(string $statePath): ?array
{
	return load_project_unit_dependency_state_from_state_file($statePath);
}

/**
 * @return array{dependency_keys:array<string,list<string>>,file_summaries:array<string,array<string,mixed>>}|null
 */
function load_project_unit_dependency_state_from_build_state(string $statePath): ?array
{
	return load_project_unit_dependency_state_from_state_file($statePath);
}

/**
 * @return array{dependency_keys:array<string,list<string>>,file_summaries:array<string,array<string,mixed>>}|null
 */
function load_project_unit_dependency_state_from_state_file(string $statePath): ?array
{
	if ($statePath === '' || !is_file($statePath)) {
		return null;
	}
	$state = require $statePath;
	if (!is_array($state) || !is_array($state['files'] ?? null)) {
		return null;
	}
	$dependencyKeys = [];
	$fileSummaries = [];
	foreach ($state['files'] as $sourceKey => $fileState) {
		if (!is_string($sourceKey) || !is_array($fileState)) {
			continue;
		}
		$keys = [];
		foreach (is_array($fileState['dependency_keys'] ?? null) ? $fileState['dependency_keys'] : [] as $dependencyKey) {
			if (is_string($dependencyKey) && trim($dependencyKey) !== '') {
				$keys[] = trim($dependencyKey);
			}
		}
		sort($keys, SORT_STRING);
		$dependencyKeys[$sourceKey] = array_values(array_unique($keys));
		$cachePath = is_string($fileState['cache_path'] ?? null) ? normalize_path($fileState['cache_path']) : '';
		if ($cachePath === '' || !is_file($cachePath)) {
			continue;
		}
		$cacheState = require $cachePath;
		if (is_array($cacheState) && is_array($cacheState['summary'] ?? null)) {
			$fileSummaries[$sourceKey] = $cacheState['summary'];
		}
	}
	return [
		'dependency_keys' => $dependencyKeys,
		'file_summaries' => $fileSummaries,
	];
}

/**
 * @param array<string,array<string,mixed>> $projectContexts
 * @param array<string,string> $sourceOverrides
 */
function write_project_unit_dependency_summary_state(string $projectRoot, array $projectContexts, array $sourceOverrides, string $summarySignature): string
{
	$normalizedProjectRoot = normalize_path($projectRoot);
	$rootContext = $projectContexts[$normalizedProjectRoot] ?? null;
	if (!is_array($rootContext) || !is_string($rootContext['cache_dir'] ?? null)) {
		return 'unavailable';
	}
	$statePath = normalize_path($rootContext['cache_dir'] . '/' . SCPP_PROJECT_UNIT_DEPENDENCY_STATE_FILE);
	$cacheDir = normalize_path($rootContext['cache_dir'] . '/project_units/files');
	ensure_directory($cacheDir);

	$sourceUnits = (new StanSourceCatalogBuilder())->build($normalizedProjectRoot, $projectContexts, [], $sourceOverrides);
	$stateStore = new StanStateStore();
	$previousState = $stateStore->load($statePath);
	$sourceFingerprint = project_unit_dependency_state_source_fingerprint($sourceUnits);
	if (project_unit_dependency_state_is_fresh($previousState, $summarySignature, $sourceFingerprint, count($sourceUnits))) {
		return 'hit';
	}
	$filePassResult = (new StanFilePass())->analyze(
		$normalizedProjectRoot,
		$statePath,
		$cacheDir,
		$summarySignature,
		$previousState,
		$sourceUnits
	);
	$fileSummaries = is_array($filePassResult['file_summaries'] ?? null) ? $filePassResult['file_summaries'] : [];
	$symbolIndex = (new StanSymbolIndexBuilder())->build($fileSummaries);
	$dependencyKeys = (new StanDependencyResolver())->collectFileDependencyKeys($fileSummaries, $symbolIndex, $normalizedProjectRoot);
	$filesState = is_array($filePassResult['files_state'] ?? null) ? $filePassResult['files_state'] : [];
	foreach ($filesState as $sourceKey => $fileState) {
		if (!is_array($fileState)) {
			continue;
		}
		$filesState[$sourceKey]['dependency_keys'] = $dependencyKeys[$sourceKey] ?? [];
	}

	$stateStore->save($statePath, [
		'version' => 1,
		'project_root' => $normalizedProjectRoot,
		'summary_signature' => $summarySignature,
		'source_fingerprint' => $sourceFingerprint,
		'source_count' => count($sourceUnits),
		'analyzed_count' => max(0, (int) ($filePassResult['analyzed_count'] ?? 0)),
		'reused_count' => max(0, (int) ($filePassResult['reused_count'] ?? 0)),
		'updated_at' => time(),
		'files' => $filesState,
	]);
	return 'updated';
}

/**
 * @param list<Scpp\S2S\Stan\StanSourceUnit> $sourceUnits
 */
function project_unit_dependency_state_source_fingerprint(array $sourceUnits): string
{
	$sourceFingerprintParts = [];
	foreach ($sourceUnits as $sourceUnit) {
		if (!$sourceUnit instanceof \Scpp\S2S\Stan\StanSourceUnit) {
			continue;
		}
		$sourceFingerprintParts[] = normalize_path($sourceUnit->path) . ':' . (string) ($sourceUnit->meta['content_hash'] ?? '');
	}
	sort($sourceFingerprintParts, SORT_STRING);
	return hash('sha256', implode("\n", $sourceFingerprintParts));
}

function project_unit_dependency_state_is_fresh(array $state, string $summarySignature, string $sourceFingerprint, int $sourceCount): bool
{
	if ((string) ($state['summary_signature'] ?? '') !== $summarySignature) {
		return false;
	}
	if ((string) ($state['source_fingerprint'] ?? '') !== $sourceFingerprint) {
		return false;
	}
	if ((int) ($state['source_count'] ?? -1) !== $sourceCount) {
		return false;
	}
	$filesState = is_array($state['files'] ?? null) ? $state['files'] : [];
	if (count($filesState) !== $sourceCount) {
		return false;
	}
	foreach ($filesState as $fileState) {
		if (!is_array($fileState) || !array_key_exists('dependency_keys', $fileState)) {
			return false;
		}
		$cachePath = is_string($fileState['cache_path'] ?? null) ? normalize_path($fileState['cache_path']) : '';
		if ($cachePath === '' || !is_file($cachePath)) {
			return false;
		}
	}
	return true;
}

/**
 * @param array<string,array<string,mixed>> $projectContexts
 * @param array<string,string> $sourceOverrides
 * @return array<string,mixed>
 */
function collect_project_unit_dependency_summary_freshness(string $projectRoot, array $projectContexts, array $sourceOverrides, string $summarySignature, bool $usedStanDependencyState): array
{
	$normalizedProjectRoot = normalize_path($projectRoot);
	$sourceInputs = [];
	foreach ($projectContexts as $contextProjectRoot => $projectContext) {
		$contextProjectRoot = normalize_path((string) $contextProjectRoot);
		$phpFiles = is_array($projectContext['php_files'] ?? null)
			? $projectContext['php_files']
			: collect_project_php_files($contextProjectRoot);
		foreach ($phpFiles as $sourcePath) {
			$sourcePath = normalize_path((string) $sourcePath);
			if ($sourcePath === '') {
				continue;
			}
			$sourceOverride = array_key_exists($sourcePath, $sourceOverrides) ? (string) $sourceOverrides[$sourcePath] : null;
			$relativeSource = normalize_config_path(relative_path($contextProjectRoot, $sourcePath));
			$sourceState = is_array($projectContext['state']['files'][$relativeSource] ?? null) ? $projectContext['state']['files'][$relativeSource] : null;
			$meta = $sourceOverride !== null
				? ['size' => strlen($sourceOverride), 'mtime' => 0, 'content_hash' => hash('sha256', $sourceOverride)]
				: (is_array($sourceState) && preg_match('/^[0-9a-f]{64}$/', (string) ($sourceState['content_hash'] ?? '')) === 1
					? [
						'size' => max(0, (int) ($sourceState['size'] ?? 0)),
						'mtime' => max(0, (int) ($sourceState['mtime'] ?? 0)),
						'content_hash' => (string) $sourceState['content_hash'],
					]
					: (is_file($sourcePath) ? build_file_meta($sourcePath) : null));
			if (!is_array($meta)) {
				continue;
			}
			$sourceInputs[] = [
				'project_root' => normalize_config_path(relative_path($normalizedProjectRoot, $contextProjectRoot)),
				'source' => $relativeSource,
				'source_key' => project_unit_stan_source_key($normalizedProjectRoot, $sourcePath),
				'size' => (int) ($meta['size'] ?? 0),
				'mtime' => (int) ($meta['mtime'] ?? 0),
				'content_hash' => (string) ($meta['content_hash'] ?? ''),
				'source_override' => $sourceOverride !== null,
			];
		}
	}
	usort($sourceInputs, static fn (array $left, array $right): int => strcmp((string) ($left['source_key'] ?? ''), (string) ($right['source_key'] ?? '')));
	$fingerprintParts = [];
	$sourceOverridesActive = false;
	foreach ($sourceInputs as $input) {
		$sourceOverridesActive = $sourceOverridesActive || (bool) ($input['source_override'] ?? false);
		$fingerprintParts[] = implode(':', [
			(string) ($input['source_key'] ?? ''),
			(string) ($input['content_hash'] ?? ''),
			(string) ((int) ($input['size'] ?? 0)),
			((bool) ($input['source_override'] ?? false)) ? 'override' : 'file',
		]);
	}

	return [
		'summary_signature' => $summarySignature,
		'source_fingerprint' => hash('sha256', implode("\n", $fingerprintParts)),
		'source_count' => count($sourceInputs),
		'used_stan_dependency_state' => $usedStanDependencyState,
		'source_overrides_active' => $sourceOverridesActive,
		'source_inputs' => $sourceInputs,
	];
}

/**
 * @param array<string,array<string,mixed>> $projectContexts
 * @param array<string,mixed> $report
 * @param array<string,mixed> $freshness
 * @return array<string,mixed>
 */
function write_project_unit_dependency_summary_artifact(string $projectRoot, array $projectContexts, array $report, array $freshness): array
{
	$normalizedProjectRoot = normalize_path($projectRoot);
	$rootContext = $projectContexts[$normalizedProjectRoot] ?? null;
	if (!is_array($rootContext) || !is_string($rootContext['cache_dir'] ?? null)) {
		return normalize_project_unit_dependency_summary_artifact_info([]);
	}
	$artifactPath = normalize_path($rootContext['cache_dir'] . '/' . SCPP_PROJECT_UNIT_DEPENDENCY_SUMMARY_FILE);
	$freshness = normalize_project_unit_dependency_summary_freshness($freshness);
	$sourceInputsByKey = [];
	foreach (is_array($freshness['source_inputs'] ?? null) ? $freshness['source_inputs'] : [] as $input) {
		if (!is_array($input)) {
			continue;
		}
		$sourceKey = trim((string) ($input['source_key'] ?? ''));
		if ($sourceKey !== '') {
			$sourceInputsByKey[$sourceKey] = $input;
		}
	}

	$sources = [];
	foreach (normalize_project_unit_dependency_summaries(is_array($report['dependency_summaries'] ?? null) ? $report['dependency_summaries'] : []) as $summary) {
		$sourceKey = trim((string) ($summary['source_key'] ?? ''));
		if ($sourceKey === '') {
			continue;
		}
		$sources[] = [
			'source' => (string) ($summary['source'] ?? ''),
			'source_key' => $sourceKey,
			'project_root' => (string) ($summary['project_root'] ?? ''),
			'status' => (string) ($summary['status'] ?? 'fallback_broad'),
			'force_include_header' => (string) ($summary['force_include_header'] ?? ''),
			'generated_header' => (string) ($summary['generated_header'] ?? ''),
			'direct_source_keys' => normalize_string_list($summary['direct_source_dependencies'] ?? []),
			'direct_local_headers' => normalize_string_list($summary['direct_local_headers'] ?? []),
			'scoped_local_headers' => normalize_string_list($summary['scoped_local_headers'] ?? []),
			'dependency_export_headers' => normalize_string_list($summary['dependency_export_headers'] ?? []),
			'unresolved_dependency_keys' => normalize_string_list($summary['unresolved_dependency_keys'] ?? []),
			'candidate_status' => (string) ($summary['candidate_status'] ?? 'blocked_broad_fallback'),
			'candidate_blocking_reasons' => normalize_string_list($summary['candidate_blocking_reasons'] ?? []),
			'candidate_scoped_headers' => normalize_string_list($summary['candidate_scoped_headers'] ?? []),
			'candidate_pack_hash' => (string) ($summary['candidate_pack_hash'] ?? ''),
			'candidate_pack_header' => (string) ($summary['candidate_pack_header'] ?? ''),
			'dependency_categories' => normalize_project_unit_dependency_category_rows(is_array($summary['dependency_categories'] ?? null) ? $summary['dependency_categories'] : []),
			'reasons' => normalize_string_list($summary['reasons'] ?? []),
			'freshness' => is_array($sourceInputsByKey[$sourceKey] ?? null) ? $sourceInputsByKey[$sourceKey] : null,
		];
	}
	usort($sources, static fn (array $left, array $right): int => strcmp((string) ($left['source_key'] ?? ''), (string) ($right['source_key'] ?? '')));

	save_s2s_state($artifactPath, [
		'version' => 1,
		'project_root' => $normalizedProjectRoot,
		'updated_at' => time(),
		'freshness' => $freshness,
		'sources' => $sources,
	]);

	return normalize_project_unit_dependency_summary_artifact_info([
		'path' => normalize_config_path(relative_path($normalizedProjectRoot, $artifactPath)),
		'summary_signature' => (string) ($freshness['summary_signature'] ?? ''),
		'source_fingerprint' => (string) ($freshness['source_fingerprint'] ?? ''),
		'source_count' => (int) ($freshness['source_count'] ?? count($sources)),
		'used_stan_dependency_state' => (bool) ($freshness['used_stan_dependency_state'] ?? false),
		'source_overrides_active' => (bool) ($freshness['source_overrides_active'] ?? false),
	]);
}

/**
 * @param array<string,array<string,mixed>> $projectContexts
 * @return array{dependency_summaries:list<array<string,mixed>>,artifact:array<string,mixed>}|null
 */
function load_fresh_project_unit_dependency_summary_artifact(string $projectRoot, array $projectContexts, array $freshness): ?array
{
	$normalizedProjectRoot = normalize_path($projectRoot);
	$rootContext = $projectContexts[$normalizedProjectRoot] ?? null;
	if (!is_array($rootContext) || !is_string($rootContext['cache_dir'] ?? null)) {
		return null;
	}
	$artifactPath = normalize_path($rootContext['cache_dir'] . '/' . SCPP_PROJECT_UNIT_DEPENDENCY_SUMMARY_FILE);
	if (!is_file($artifactPath)) {
		return null;
	}
	$artifact = require $artifactPath;
	if (!is_array($artifact)) {
		return null;
	}
	$currentFreshness = normalize_project_unit_dependency_summary_freshness($freshness);
	$artifactFreshness = normalize_project_unit_dependency_summary_freshness(is_array($artifact['freshness'] ?? null) ? $artifact['freshness'] : []);
	foreach (['summary_signature', 'source_fingerprint', 'source_count', 'used_stan_dependency_state', 'source_overrides_active'] as $key) {
		if (($artifactFreshness[$key] ?? null) !== ($currentFreshness[$key] ?? null)) {
			return null;
		}
	}
	$sources = [];
	foreach (is_array($artifact['sources'] ?? null) ? $artifact['sources'] : [] as $source) {
		if (!is_array($source)) {
			continue;
		}
		if (!array_key_exists('direct_source_dependencies', $source) && array_key_exists('direct_source_keys', $source)) {
			$source['direct_source_dependencies'] = $source['direct_source_keys'];
		}
		$sources[] = $source;
	}
	$summaries = normalize_project_unit_dependency_summaries($sources);
	if (count($summaries) !== (int) ($currentFreshness['source_count'] ?? 0)) {
		return null;
	}
	return [
		'dependency_summaries' => $summaries,
		'artifact' => normalize_project_unit_dependency_summary_artifact_info([
			'path' => normalize_config_path(relative_path($normalizedProjectRoot, $artifactPath)),
			'summary_signature' => (string) ($currentFreshness['summary_signature'] ?? ''),
			'source_fingerprint' => (string) ($currentFreshness['source_fingerprint'] ?? ''),
			'source_count' => (int) ($currentFreshness['source_count'] ?? 0),
			'used_stan_dependency_state' => (bool) ($currentFreshness['used_stan_dependency_state'] ?? false),
			'source_overrides_active' => (bool) ($currentFreshness['source_overrides_active'] ?? false),
		]),
	];
}

/**
 * @param array<string,array<string,mixed>> $projectContexts
 * @param list<array{project_root:string,relative_php:string,generated_header?:string,generated_cpp:string,object_path:string,is_entrypoint:bool,force_include_header:?string}> $generatedUnits
 * @return array<string,mixed>
 */
function collect_project_module_report(string $projectRoot, array $projectContexts, array $generatedUnits, array $projectUnitForceIncludeReport = []): array
{
	$normalizedProjectRoot = normalize_path($projectRoot);
	$rootContext = is_array($projectContexts[$normalizedProjectRoot] ?? null) ? $projectContexts[$normalizedProjectRoot] : [];
	$rootConfig = is_array($rootContext['config'] ?? null) ? $rootContext['config'] : [];
	$dependencyValidationPolicy = normalize_project_module_dependency_policy($rootConfig, project_context_report_label($normalizedProjectRoot, $normalizedProjectRoot));
	$publicApiPolicy = normalize_project_module_public_policy($rootConfig, project_context_report_label($normalizedProjectRoot, $normalizedProjectRoot));
	$unitsByProjectRoot = [];
	foreach ($generatedUnits as $unit) {
		$unitProjectRoot = normalize_path((string) ($unit['project_root'] ?? ''));
		$relativePhp = normalize_config_path((string) ($unit['relative_php'] ?? ''));
		if ($unitProjectRoot === '' || $relativePhp === '') {
			continue;
		}
		$unitsByProjectRoot[$unitProjectRoot][$relativePhp] = $unit;
	}
	foreach ($unitsByProjectRoot as &$rows) {
		ksort($rows, SORT_STRING);
	}
	unset($rows);

	$moduleRows = [];
	$manifestArtifacts = [];
	$assignedSourceKeys = [];
	$unassignedSources = [];
	$duplicateAssignments = [];
	$generatedSourceCount = 0;

	foreach ($projectContexts as $contextProjectRoot => $projectContext) {
		$contextProjectRoot = normalize_path((string) $contextProjectRoot);
		if (!is_array($projectContext)) {
			continue;
		}
		$contextConfig = is_array($projectContext['config'] ?? null) ? $projectContext['config'] : [];
		$moduleConfigs = normalize_project_module_config_rows($contextConfig, project_context_report_label($normalizedProjectRoot, $contextProjectRoot));
		if ($moduleConfigs === []) {
			continue;
		}
		$contextUnits = $unitsByProjectRoot[$contextProjectRoot] ?? [];
		$generatedSourceCount += count($contextUnits);
		$contextModuleRows = [];
		$contextAssignments = [];
		foreach ($moduleConfigs as $moduleConfig) {
			$matchedSources = [];
			foreach ($contextUnits as $relativePhp => $unit) {
				if (project_module_matches_source($moduleConfig, (string) $relativePhp)) {
					$matchedSources[] = build_project_module_source_row($normalizedProjectRoot, $contextProjectRoot, $projectContext, $unit);
					$assignmentKey = project_context_report_label($normalizedProjectRoot, $contextProjectRoot) . "\0" . (string) $relativePhp;
					$contextAssignments[$assignmentKey][] = (string) ($moduleConfig['name'] ?? '');
					$assignedSourceKeys[$assignmentKey] = true;
				}
			}
			usort($matchedSources, static fn (array $left, array $right): int => strcmp((string) ($left['source'] ?? ''), (string) ($right['source'] ?? '')));
			$moduleRow = build_project_module_report_row($normalizedProjectRoot, $contextProjectRoot, $projectContext, $moduleConfig, $matchedSources);
			$contextModuleRows[] = $moduleRow;
		}
		$contextModuleRows = apply_project_module_consumer_rebuild_status($contextModuleRows);
		$contextModuleRows = apply_project_module_stan_summary_artifacts($normalizedProjectRoot, $contextProjectRoot, $projectContext, $contextModuleRows, $projectUnitForceIncludeReport);
		$manifestArtifact = write_project_module_manifest($normalizedProjectRoot, $contextProjectRoot, $projectContext, $contextModuleRows);
		if ($manifestArtifact !== null) {
			$manifestArtifacts[] = $manifestArtifact;
		}
		foreach ($contextModuleRows as $moduleRow) {
			$moduleRows[] = $moduleRow;
		}
		foreach ($contextUnits as $relativePhp => $_unit) {
			$key = project_context_report_label($normalizedProjectRoot, $contextProjectRoot) . "\0" . (string) $relativePhp;
			if (!isset($assignedSourceKeys[$key])) {
				$unassignedSources[] = project_module_source_label(project_context_report_label($normalizedProjectRoot, $contextProjectRoot), (string) $relativePhp);
			}
		}
		foreach ($contextAssignments as $assignmentKey => $modules) {
			$modules = normalize_string_list($modules);
			if (count($modules) <= 1) {
				continue;
			}
			[$projectLabel, $source] = explode("\0", (string) $assignmentKey, 2);
			$duplicateAssignments[] = [
				'source' => project_module_source_label($projectLabel, $source),
				'modules' => $modules,
			];
		}
	}

	usort($moduleRows, static function (array $left, array $right): int {
		$leftKey = (string) ($left['project_root'] ?? '') . "\0" . (string) ($left['name'] ?? '');
		$rightKey = (string) ($right['project_root'] ?? '') . "\0" . (string) ($right['name'] ?? '');
		return strcmp($leftKey, $rightKey);
	});
	sort($unassignedSources, SORT_STRING);
	usort($duplicateAssignments, static fn (array $left, array $right): int => strcmp((string) ($left['source'] ?? ''), (string) ($right['source'] ?? '')));

	$cacheStatusCounts = summarize_project_module_cache_status_counts($moduleRows);
	$stanSummaryCacheStatusCounts = summarize_project_module_stan_summary_cache_status_counts($moduleRows);
	$dependencyValidation = collect_project_module_dependency_validation($normalizedProjectRoot, $moduleRows, $projectUnitForceIncludeReport, $dependencyValidationPolicy);
	$moduleRows = apply_project_module_dependency_validation_to_module_rows($moduleRows, $dependencyValidation);
	$publicApiValidation = collect_project_module_public_api_validation($normalizedProjectRoot, $projectContexts, $moduleRows, $projectUnitForceIncludeReport, $publicApiPolicy);
	$moduleRows = apply_project_module_public_api_validation_to_module_rows($moduleRows, $publicApiValidation);
	if ($dependencyValidationPolicy === 'warn' && (int) ($dependencyValidation['undeclared_dependency_count'] ?? 0) > 0) {
		foreach (normalize_project_module_dependency_validation_rows(is_array($dependencyValidation['undeclared_dependencies'] ?? null) ? $dependencyValidation['undeclared_dependencies'] : []) as $violation) {
			scpp_write('Project module dependency warning: module `' . (string) ($violation['module'] ?? '') . '` uses undeclared dependency `' . (string) ($violation['dependency'] ?? '') . '` from ' . implode(', ', normalize_string_list($violation['sources'] ?? [])) . PHP_EOL, 'stderr');
		}
	}
	if ($dependencyValidationPolicy === 'fail' && (int) ($dependencyValidation['undeclared_dependency_count'] ?? 0) > 0) {
		$messages = [];
		foreach (normalize_project_module_dependency_validation_rows(is_array($dependencyValidation['undeclared_dependencies'] ?? null) ? $dependencyValidation['undeclared_dependencies'] : []) as $violation) {
			$messages[] = 'module `' . (string) ($violation['module'] ?? '') . '` uses undeclared dependency `' . (string) ($violation['dependency'] ?? '') . '` from ' . implode(', ', normalize_string_list($violation['sources'] ?? []));
		}
		scpp_fail('Project module dependency validation failed: ' . implode('; ', $messages) . PHP_EOL, 2);
	}
	if ($publicApiPolicy === 'warn' && ((int) ($publicApiValidation['private_dependency_count'] ?? 0) > 0 || (int) ($publicApiValidation['unknown_public_export_count'] ?? 0) > 0)) {
		foreach (normalize_project_module_public_api_private_dependency_rows(is_array($publicApiValidation['private_dependencies'] ?? null) ? $publicApiValidation['private_dependencies'] : []) as $violation) {
			scpp_write('Project module public API warning: module `' . (string) ($violation['module'] ?? '') . '` uses private declaration `' . (string) ($violation['target'] ?? '') . '` from module `' . (string) ($violation['dependency'] ?? '') . '` in ' . implode(', ', normalize_string_list($violation['sources'] ?? [])) . PHP_EOL, 'stderr');
		}
		foreach (normalize_project_module_public_api_unknown_export_rows(is_array($publicApiValidation['unknown_public_exports'] ?? null) ? $publicApiValidation['unknown_public_exports'] : []) as $violation) {
			scpp_write('Project module public API warning: module `' . (string) ($violation['module'] ?? '') . '` declares unknown public export `' . (string) ($violation['export'] ?? '') . '`.' . PHP_EOL, 'stderr');
		}
	}
	if ($publicApiPolicy === 'fail' && ((int) ($publicApiValidation['private_dependency_count'] ?? 0) > 0 || (int) ($publicApiValidation['unknown_public_export_count'] ?? 0) > 0)) {
		$messages = [];
		foreach (normalize_project_module_public_api_private_dependency_rows(is_array($publicApiValidation['private_dependencies'] ?? null) ? $publicApiValidation['private_dependencies'] : []) as $violation) {
			$messages[] = 'module `' . (string) ($violation['module'] ?? '') . '` uses private declaration `' . (string) ($violation['target'] ?? '') . '` from module `' . (string) ($violation['dependency'] ?? '') . '` in ' . implode(', ', normalize_string_list($violation['sources'] ?? []));
		}
		foreach (normalize_project_module_public_api_unknown_export_rows(is_array($publicApiValidation['unknown_public_exports'] ?? null) ? $publicApiValidation['unknown_public_exports'] : []) as $violation) {
			$messages[] = 'module `' . (string) ($violation['module'] ?? '') . '` declares unknown public export `' . (string) ($violation['export'] ?? '') . '`';
		}
		scpp_fail('Project module public API validation failed: ' . implode('; ', $messages) . PHP_EOL, 2);
	}
	return normalize_project_module_report([
		'configured' => $moduleRows !== [],
		'total_modules' => count($moduleRows),
		'generated_source_count' => $generatedSourceCount,
		'assigned_source_count' => count($assignedSourceKeys),
		'unassigned_source_count' => count($unassignedSources),
		'unassigned_sources' => array_values(array_unique($unassignedSources)),
		'duplicate_assignments' => $duplicateAssignments,
		'consumer_rebuild_required_count' => count(array_filter($moduleRows, static fn (array $row): bool => (bool) ($row['consumer_rebuild_required'] ?? false))),
		'cache_status_counts' => $cacheStatusCounts,
		'stan_summary_cache_status_counts' => $stanSummaryCacheStatusCounts,
		'dependency_validation' => $dependencyValidation,
		'public_api_validation' => $publicApiValidation,
		'manifest_artifacts' => normalize_string_list($manifestArtifacts),
		'modules' => $moduleRows,
	]);
}

function normalize_project_module_dependency_policy(array $config, string $projectLabel): string
{
	$rawPolicy = $config['project_module_dependency_policy'] ?? 'report';
	if (!is_string($rawPolicy)) {
		scpp_fail('Invalid project_module_dependency_policy in ' . SCPP_PROJECT_CONFIG . ' for project ' . $projectLabel . ': expected a string.' . PHP_EOL, 2);
	}
	$policy = strtolower(trim($rawPolicy));
	if ($policy === '') {
		$policy = 'report';
	}
	if (!in_array($policy, ['report', 'warn', 'fail'], true)) {
		scpp_fail('Invalid project_module_dependency_policy `' . $policy . '` in ' . SCPP_PROJECT_CONFIG . ' for project ' . $projectLabel . '. Use one of: report, warn, fail.' . PHP_EOL, 2);
	}
	return $policy;
}

function normalize_project_module_public_policy(array $config, string $projectLabel): string
{
	$rawPolicy = $config['project_module_public_policy'] ?? 'report';
	if (!is_string($rawPolicy)) {
		scpp_fail('Invalid project_module_public_policy in ' . SCPP_PROJECT_CONFIG . ' for project ' . $projectLabel . ': expected a string.' . PHP_EOL, 2);
	}
	$policy = strtolower(trim($rawPolicy));
	if ($policy === '') {
		$policy = 'report';
	}
	if (!in_array($policy, ['report', 'warn', 'fail'], true)) {
		scpp_fail('Invalid project_module_public_policy `' . $policy . '` in ' . SCPP_PROJECT_CONFIG . ' for project ' . $projectLabel . '. Use one of: report, warn, fail.' . PHP_EOL, 2);
	}
	return $policy;
}

/** @return list<array<string,mixed>> */
function normalize_project_module_config_rows(array $config, string $projectLabel): array
{
	$rawModules = $config['project_modules'] ?? [];
	if ($rawModules === null) {
		return [];
	}
	if (!is_array($rawModules)) {
		scpp_fail('Invalid project_modules in ' . SCPP_PROJECT_CONFIG . ' for project ' . $projectLabel . ': expected an array.' . PHP_EOL, 2);
	}

	$modules = [];
	foreach ($rawModules as $key => $rawModule) {
		if (!is_array($rawModule)) {
			scpp_fail('Invalid project_modules entry in ' . SCPP_PROJECT_CONFIG . ' for project ' . $projectLabel . ': expected an object.' . PHP_EOL, 2);
		}
		$name = is_string($rawModule['name'] ?? null) ? trim((string) $rawModule['name']) : '';
		if ($name === '' && is_string($key)) {
			$name = trim($key);
		}
		if ($name === '') {
			scpp_fail('Invalid project_modules entry in ' . SCPP_PROJECT_CONFIG . ' for project ' . $projectLabel . ': missing module name.' . PHP_EOL, 2);
		}
		$sources = normalize_project_module_path_list($rawModule['sources'] ?? [], 'project_modules.' . $name . '.sources', $projectLabel);
		$sourceRoots = normalize_project_module_path_list($rawModule['source_roots'] ?? ($rawModule['source_root'] ?? []), 'project_modules.' . $name . '.source_roots', $projectLabel);
		$dependencies = normalize_project_module_name_list($rawModule['dependencies'] ?? ($rawModule['deps'] ?? []), 'project_modules.' . $name . '.dependencies', $projectLabel);
		$publicExports = normalize_project_module_name_list($rawModule['public_exports'] ?? [], 'project_modules.' . $name . '.public_exports', $projectLabel);
		$modules[] = [
			'name' => $name,
			'sources' => $sources,
			'source_roots' => $sourceRoots,
			'dependencies' => $dependencies,
			'public_exports' => $publicExports,
		];
	}
	usort($modules, static fn (array $left, array $right): int => strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? '')));
	return $modules;
}

/** @return list<string> */
function normalize_project_module_path_list(mixed $values, string $field, string $projectLabel): array
{
	if (is_string($values)) {
		$values = [$values];
	}
	if (!is_array($values)) {
		scpp_fail('Invalid ' . $field . ' in ' . SCPP_PROJECT_CONFIG . ' for project ' . $projectLabel . ': expected a string array.' . PHP_EOL, 2);
	}
	$result = [];
	foreach ($values as $value) {
		if (!is_string($value)) {
			scpp_fail('Invalid ' . $field . ' in ' . SCPP_PROJECT_CONFIG . ' for project ' . $projectLabel . ': expected string paths.' . PHP_EOL, 2);
		}
		$path = trim($value);
		if ($path === '') {
			continue;
		}
		if (is_absolute_path($path)) {
			scpp_fail('Invalid ' . $field . ' path `' . $path . '` in ' . SCPP_PROJECT_CONFIG . ' for project ' . $projectLabel . ': project module paths must be project-relative.' . PHP_EOL, 2);
		}
		$normalized = normalize_config_path($path);
		if ($normalized === '..' || str_starts_with($normalized, '../') || str_contains($normalized, '/../')) {
			scpp_fail('Invalid ' . $field . ' path `' . $path . '` in ' . SCPP_PROJECT_CONFIG . ' for project ' . $projectLabel . ': project module paths must not escape the project root.' . PHP_EOL, 2);
		}
		$result[] = rtrim($normalized, '/');
	}
	sort($result, SORT_STRING);
	return array_values(array_unique($result));
}

/** @return list<string> */
function normalize_project_module_name_list(mixed $values, string $field, string $projectLabel): array
{
	if (is_string($values)) {
		$values = [$values];
	}
	if (!is_array($values)) {
		scpp_fail('Invalid ' . $field . ' in ' . SCPP_PROJECT_CONFIG . ' for project ' . $projectLabel . ': expected a string array.' . PHP_EOL, 2);
	}
	$result = [];
	foreach ($values as $value) {
		if (!is_string($value)) {
			scpp_fail('Invalid ' . $field . ' in ' . SCPP_PROJECT_CONFIG . ' for project ' . $projectLabel . ': expected string names.' . PHP_EOL, 2);
		}
		$name = trim($value);
		if ($name !== '') {
			$result[] = $name;
		}
	}
	sort($result, SORT_STRING);
	return array_values(array_unique($result));
}

/** @param array<string,mixed> $moduleConfig */
function project_module_matches_source(array $moduleConfig, string $relativeSource): bool
{
	$relativeSource = normalize_config_path($relativeSource);
	foreach (normalize_string_list($moduleConfig['sources'] ?? []) as $source) {
		if (normalize_config_path($source) === $relativeSource) {
			return true;
		}
	}
	foreach (normalize_string_list($moduleConfig['source_roots'] ?? []) as $root) {
		$root = rtrim(normalize_config_path($root), '/');
		if ($root === '' || $root === '.') {
			return true;
		}
		if ($relativeSource === $root || str_starts_with($relativeSource, $root . '/')) {
			return true;
		}
	}
	return false;
}

/** @return array<string,mixed> */
function build_project_module_source_row(string $projectRoot, string $contextProjectRoot, array $projectContext, array $unit): array
{
	$relativePhp = normalize_config_path((string) ($unit['relative_php'] ?? ''));
	$fileState = is_array($projectContext['state']['files'][$relativePhp] ?? null) ? $projectContext['state']['files'][$relativePhp] : [];
	$generatedHeader = normalize_path((string) ($unit['generated_header'] ?? ''));
	$generatedCpp = normalize_path((string) ($unit['generated_cpp'] ?? ''));
	$objectPath = normalize_path((string) ($unit['object_path'] ?? ''));
	$interfaceHash = trim((string) ($fileState['generated_interface_hash'] ?? ''));
	if ($interfaceHash === '') {
		$interfaceHash = existing_file_sha256($generatedHeader) ?? '';
	}
	$implementationHash = trim((string) ($fileState['generated_implementation_hash'] ?? ''));
	if ($implementationHash === '') {
		$implementationHash = existing_file_sha256($generatedCpp) ?? '';
	}
	return [
		'source' => $relativePhp,
		'project_root' => project_context_report_label($projectRoot, $contextProjectRoot),
		'generated_header' => $generatedHeader !== '' ? normalize_config_path(relative_path($projectRoot, $generatedHeader)) : '',
		'generated_cpp' => $generatedCpp !== '' ? normalize_config_path(relative_path($projectRoot, $generatedCpp)) : '',
		'object_path' => $objectPath !== '' ? normalize_config_path(relative_path($projectRoot, $objectPath)) : '',
		'interface_hash' => $interfaceHash,
		'implementation_hash' => $implementationHash,
	];
}

/** @param list<array<string,mixed>> $sources @return array<string,mixed> */
function build_project_module_report_row(string $projectRoot, string $contextProjectRoot, array $projectContext, array $moduleConfig, array $sources): array
{
	$name = (string) ($moduleConfig['name'] ?? '');
	$dependencies = normalize_string_list($moduleConfig['dependencies'] ?? []);
	$publicExports = normalize_string_list($moduleConfig['public_exports'] ?? []);
	$sourceRoots = normalize_string_list($moduleConfig['source_roots'] ?? []);
	$configuredSources = normalize_string_list($moduleConfig['sources'] ?? []);
	$interfaceInput = [
		'version' => 1,
		'name' => $name,
		'dependencies' => $dependencies,
		'public_exports' => $publicExports,
		'sources' => array_map(static fn (array $source): array => [
			'source' => (string) ($source['source'] ?? ''),
			'interface_hash' => (string) ($source['interface_hash'] ?? ''),
		], $sources),
	];
	$implementationInput = [
		'version' => 1,
		'name' => $name,
		'dependencies' => $dependencies,
		'public_exports' => $publicExports,
		'sources' => array_map(static fn (array $source): array => [
			'source' => (string) ($source['source'] ?? ''),
			'interface_hash' => (string) ($source['interface_hash'] ?? ''),
			'implementation_hash' => (string) ($source['implementation_hash'] ?? ''),
		], $sources),
	];
	$interfaceHash = hash_project_module_payload($interfaceInput);
	$implementationHash = hash_project_module_payload($implementationInput);
	$artifactInfo = write_project_module_surface_artifact($projectRoot, $contextProjectRoot, $projectContext, [
		'name' => $name,
		'project_root' => project_context_report_label($projectRoot, $contextProjectRoot),
		'source_roots' => $sourceRoots,
		'configured_sources' => $configuredSources,
		'dependencies' => $dependencies,
		'public_exports' => $publicExports,
		'sources' => $sources,
		'interface_hash' => $interfaceHash,
		'implementation_hash' => $implementationHash,
	]);

	return [
		'name' => $name,
		'project_root' => project_context_report_label($projectRoot, $contextProjectRoot),
		'source_roots' => $sourceRoots,
		'configured_sources' => $configuredSources,
		'dependencies' => $dependencies,
		'public_exports' => $publicExports,
		'source_count' => count($sources),
		'sources' => $sources,
		'interface_hash' => $interfaceHash,
		'implementation_hash' => $implementationHash,
		'surface_artifact' => (string) ($artifactInfo['surface_artifact'] ?? ''),
		'implementation_artifact' => (string) ($artifactInfo['implementation_artifact'] ?? ''),
		'cache_status' => (string) ($artifactInfo['cache_status'] ?? 'new'),
		'interface_changed' => (bool) ($artifactInfo['interface_changed'] ?? true),
		'implementation_changed' => (bool) ($artifactInfo['implementation_changed'] ?? true),
		'implementation_artifacts' => normalize_string_list(array_map(static fn (array $source): string => (string) ($source['generated_cpp'] ?? ''), $sources)),
		'consumer_rebuild_policy' => 'interface_hash_only',
		'consumer_rebuild_required' => false,
		'consumer_rebuild_reasons' => [],
		'unresolved_dependencies' => [],
	];
}

/** @return list<array<string,mixed>> */
function apply_project_module_consumer_rebuild_status(array $moduleRows): array
{
	$modulesByName = [];
	$interfaceChangedByName = [];
	foreach ($moduleRows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$name = trim((string) ($row['name'] ?? ''));
		if ($name === '') {
			continue;
		}
		$modulesByName[$name] = true;
		if ((bool) ($row['interface_changed'] ?? false)) {
			$interfaceChangedByName[$name] = true;
		}
	}

	foreach ($moduleRows as &$row) {
		if (!is_array($row)) {
			continue;
		}
		$changedDependencies = [];
		$unresolved = [];
		foreach (normalize_string_list($row['dependencies'] ?? []) as $dependency) {
			if (!isset($modulesByName[$dependency])) {
				$unresolved[] = $dependency;
				continue;
			}
			if (isset($interfaceChangedByName[$dependency])) {
				$changedDependencies[] = $dependency;
			}
		}
		$row['consumer_rebuild_required'] = $changedDependencies !== [];
		$row['consumer_rebuild_reasons'] = array_map(static fn (string $dependency): string => 'dependency interface changed: ' . $dependency, $changedDependencies);
		$row['unresolved_dependencies'] = $unresolved;
	}
	unset($row);
	return $moduleRows;
}

/**
 * @param list<array<string,mixed>> $moduleRows
 * @return list<array<string,mixed>>
 */
function apply_project_module_stan_summary_artifacts(string $projectRoot, string $contextProjectRoot, array $projectContext, array $moduleRows, array $projectUnitForceIncludeReport): array
{
	$dependencySummaries = normalize_project_unit_dependency_summaries(is_array($projectUnitForceIncludeReport['dependency_summaries'] ?? null) ? $projectUnitForceIncludeReport['dependency_summaries'] : []);
	$evidenceSource = project_module_dependency_evidence_source($projectUnitForceIncludeReport, $dependencySummaries);
	$dependencySummariesBySource = [];
	foreach ($dependencySummaries as $summary) {
		$projectLabel = trim((string) ($summary['project_root'] ?? '.'));
		if ($projectLabel === '') {
			$projectLabel = '.';
		}
		$source = normalize_config_path((string) ($summary['source'] ?? ''));
		if ($source === '') {
			continue;
		}
		$dependencySummariesBySource[project_module_source_key($projectLabel, $source)] = $summary;
	}

	foreach ($moduleRows as &$row) {
		if (!is_array($row)) {
			continue;
		}
		$artifactInfo = write_project_module_stan_summary_artifact(
			$projectRoot,
			$contextProjectRoot,
			$projectContext,
			$row,
			$dependencySummariesBySource,
			$projectUnitForceIncludeReport,
			$evidenceSource
		);
		$row['stan_summary_artifact'] = (string) ($artifactInfo['stan_summary_artifact'] ?? '');
		$row['stan_summary_hash'] = (string) ($artifactInfo['stan_summary_hash'] ?? '');
		$row['stan_summary_evidence_source'] = (string) ($artifactInfo['stan_summary_evidence_source'] ?? 'none');
		$row['stan_summary_cache_status'] = (string) ($artifactInfo['stan_summary_cache_status'] ?? 'unavailable');
		$row['stan_summary_cache_reasons'] = normalize_string_list($artifactInfo['stan_summary_cache_reasons'] ?? []);
	}
	unset($row);
	return $moduleRows;
}

/**
 * @param array<string,array<string,mixed>> $dependencySummariesBySource
 * @return array<string,mixed>
 */
function write_project_module_stan_summary_artifact(string $projectRoot, string $contextProjectRoot, array $projectContext, array $moduleRow, array $dependencySummariesBySource, array $projectUnitForceIncludeReport, string $evidenceSource): array
{
	$moduleName = trim((string) ($moduleRow['name'] ?? ''));
	if ($moduleName === '') {
		$moduleName = 'module';
	}
	if (!is_string($projectContext['cache_dir'] ?? null)) {
		return [
			'stan_summary_artifact' => '',
			'stan_summary_hash' => '',
			'stan_summary_evidence_source' => $evidenceSource,
			'stan_summary_cache_status' => 'unavailable',
			'stan_summary_cache_reasons' => ['project module cache directory unavailable'],
		];
	}
	if ($evidenceSource === 'none') {
		return [
			'stan_summary_artifact' => '',
			'stan_summary_hash' => '',
			'stan_summary_evidence_source' => 'none',
			'stan_summary_cache_status' => 'unavailable',
			'stan_summary_cache_reasons' => ['project-unit dependency evidence unavailable; module analysis summary artifact not written'],
		];
	}

	$projectLabel = project_context_report_label($projectRoot, $contextProjectRoot);
	$summarySources = [];
	$missingSummarySources = [];
	foreach (normalize_project_module_source_rows(is_array($moduleRow['sources'] ?? null) ? $moduleRow['sources'] : []) as $sourceRow) {
		$sourceProjectLabel = trim((string) ($sourceRow['project_root'] ?? $projectLabel));
		if ($sourceProjectLabel === '') {
			$sourceProjectLabel = '.';
		}
		$source = normalize_config_path((string) ($sourceRow['source'] ?? ''));
		if ($source === '') {
			continue;
		}
		$summary = $dependencySummariesBySource[project_module_source_key($sourceProjectLabel, $source)] ?? null;
		if (!is_array($summary)) {
			$missingSummarySources[] = project_module_source_label($sourceProjectLabel, $source);
			$summarySources[] = [
				'source' => $source,
				'source_key' => '',
				'project_root' => $sourceProjectLabel,
				'status' => 'missing_dependency_summary',
				'candidate_status' => 'unavailable',
				'direct_source_dependencies' => [],
				'direct_local_headers' => [],
				'scoped_local_headers' => [],
				'dependency_export_headers' => [],
				'unresolved_dependency_keys' => [],
				'candidate_blocking_reasons' => ['project-unit dependency summary missing for module source'],
				'candidate_scoped_headers' => [],
				'candidate_pack_hash' => '',
				'candidate_pack_header' => '',
				'dependency_categories' => [],
				'reasons' => ['project-unit dependency summary missing for module source'],
			];
			continue;
		}
		$summarySources[] = normalize_project_module_stan_summary_source_row($summary);
	}
	usort($summarySources, static function (array $left, array $right): int {
		return strcmp((string) ($left['project_root'] ?? '') . "\0" . (string) ($left['source'] ?? ''), (string) ($right['project_root'] ?? '') . "\0" . (string) ($right['source'] ?? ''));
	});
	$missingSummarySources = normalize_string_list($missingSummarySources);
	$dependencyArtifact = normalize_project_unit_dependency_summary_artifact_info(is_array($projectUnitForceIncludeReport['dependency_summary_artifact'] ?? null) ? $projectUnitForceIncludeReport['dependency_summary_artifact'] : []);
	$dependencyArtifactInput = [
		'path' => (string) ($dependencyArtifact['path'] ?? ''),
		'summary_signature' => (string) ($dependencyArtifact['summary_signature'] ?? ''),
		'source_count' => (int) ($dependencyArtifact['source_count'] ?? 0),
		'used_stan_dependency_state' => (bool) ($dependencyArtifact['used_stan_dependency_state'] ?? false),
		'source_overrides_active' => (bool) ($dependencyArtifact['source_overrides_active'] ?? false),
	];
	$hashInput = [
		'version' => 1,
		'name' => $moduleName,
		'project_root' => $projectLabel,
		'dependencies' => normalize_string_list($moduleRow['dependencies'] ?? []),
		'public_exports' => normalize_string_list($moduleRow['public_exports'] ?? []),
		'evidence_source' => $evidenceSource,
		'dependency_summary_artifact' => $dependencyArtifactInput,
		'missing_summary_sources' => $missingSummarySources,
		'sources' => $summarySources,
	];
	$summaryHash = hash_project_module_payload($hashInput);
	$artifactPath = normalize_path($projectContext['cache_dir'] . '/project_modules/' . project_module_artifact_filename($moduleName, 'stan_summary'));
	$previousArtifact = read_json_file($artifactPath);
	$previousHash = is_array($previousArtifact) ? trim((string) ($previousArtifact['stan_summary_hash'] ?? '')) : '';
	$cacheStatus = 'hit';
	$cacheReasons = [];
	if ($previousArtifact === null) {
		$cacheStatus = 'new';
		$cacheReasons[] = 'no previous module analysis summary artifact';
	} elseif ($previousHash === '') {
		$cacheStatus = 'changed';
		$cacheReasons[] = 'previous module analysis summary artifact had no hash';
	} elseif ($previousHash !== $summaryHash) {
		$cacheStatus = 'changed';
		$cacheReasons[] = 'module dependency summary hash changed';
	} else {
		$cacheReasons[] = 'module dependency summary hash unchanged';
	}
	$cacheReasons[] = $evidenceSource === 'stan'
		? 'using STAN dependency evidence'
		: 'using build-owned dependency evidence';
	if ($missingSummarySources !== []) {
		$cacheReasons[] = 'missing dependency summaries for: ' . implode(', ', $missingSummarySources);
	}

	$artifactPayload = [
		'version' => 1,
		'kind' => 'project_module_stan_summary',
		'name' => $moduleName,
		'project_root' => $projectLabel,
		'dependencies' => normalize_string_list($moduleRow['dependencies'] ?? []),
		'public_exports' => normalize_string_list($moduleRow['public_exports'] ?? []),
		'evidence_source' => $evidenceSource,
		'stan_summary_hash' => $summaryHash,
		'dependency_summary_artifact' => $dependencyArtifactInput,
		'missing_summary_sources' => $missingSummarySources,
		'sources' => $summarySources,
	];
	$json = json_encode($artifactPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	if (!is_string($json)) {
		scpp_fail('Failed to encode project module analysis summary artifact: ' . $artifactPath . PHP_EOL, 2);
	}
	write_text_file($artifactPath, $json . PHP_EOL);

	return [
		'stan_summary_artifact' => normalize_config_path(relative_path($projectRoot, $artifactPath)),
		'stan_summary_hash' => $summaryHash,
		'stan_summary_evidence_source' => $evidenceSource,
		'stan_summary_cache_status' => $cacheStatus,
		'stan_summary_cache_reasons' => $cacheReasons,
	];
}

/** @return array<string,mixed> */
function normalize_project_module_stan_summary_source_row(array $summary): array
{
	return [
		'source' => normalize_config_path((string) ($summary['source'] ?? '')),
		'source_key' => trim((string) ($summary['source_key'] ?? '')),
		'project_root' => trim((string) ($summary['project_root'] ?? '.')),
		'generated_header' => normalize_config_path((string) ($summary['generated_header'] ?? '')),
		'force_include_header' => normalize_config_path((string) ($summary['force_include_header'] ?? '')),
		'status' => trim((string) ($summary['status'] ?? 'fallback_broad')),
		'candidate_status' => trim((string) ($summary['candidate_status'] ?? 'blocked_broad_fallback')),
		'direct_source_dependencies' => normalize_string_list($summary['direct_source_dependencies'] ?? []),
		'direct_local_headers' => normalize_string_list($summary['direct_local_headers'] ?? []),
		'scoped_local_headers' => normalize_string_list($summary['scoped_local_headers'] ?? []),
		'dependency_export_headers' => normalize_string_list($summary['dependency_export_headers'] ?? []),
		'unresolved_dependency_keys' => normalize_string_list($summary['unresolved_dependency_keys'] ?? []),
		'candidate_blocking_reasons' => normalize_string_list($summary['candidate_blocking_reasons'] ?? []),
		'candidate_scoped_headers' => normalize_string_list($summary['candidate_scoped_headers'] ?? []),
		'candidate_pack_hash' => trim((string) ($summary['candidate_pack_hash'] ?? '')),
		'candidate_pack_header' => normalize_config_path((string) ($summary['candidate_pack_header'] ?? '')),
		'dependency_categories' => normalize_project_unit_dependency_category_rows(is_array($summary['dependency_categories'] ?? null) ? $summary['dependency_categories'] : []),
		'reasons' => normalize_string_list($summary['reasons'] ?? []),
	];
}

/** @param list<array<string,mixed>> $dependencySummaries */
function project_module_dependency_evidence_source(array $projectUnitForceIncludeReport, array $dependencySummaries): string
{
	if ($dependencySummaries === []) {
		return 'none';
	}
	$summaryArtifact = is_array($projectUnitForceIncludeReport['dependency_summary_artifact'] ?? null) ? $projectUnitForceIncludeReport['dependency_summary_artifact'] : [];
	return ((bool) ($summaryArtifact['used_stan_dependency_state'] ?? false)) ? 'stan' : 'build';
}

/**
 * @param list<array<string,mixed>> $moduleRows
 * @return array<string,mixed>
 */
function collect_project_module_dependency_validation(string $projectRoot, array $moduleRows, array $projectUnitForceIncludeReport, string $policy): array
{
	$moduleRows = normalize_project_module_rows($moduleRows);
	$moduleNames = [];
	$declaredByModule = [];
	$sourceToModule = [];
	foreach ($moduleRows as $module) {
		$moduleName = trim((string) ($module['name'] ?? ''));
		$projectLabel = trim((string) ($module['project_root'] ?? '.'));
		if ($moduleName === '') {
			continue;
		}
		$moduleNames[$moduleName] = true;
		$declaredByModule[$moduleName] = [];
		foreach (normalize_string_list($module['dependencies'] ?? []) as $dependency) {
			$declaredByModule[$moduleName][$dependency] = true;
		}
		foreach (normalize_project_module_source_rows(is_array($module['sources'] ?? null) ? $module['sources'] : []) as $source) {
			$sourcePath = normalize_config_path((string) ($source['source'] ?? ''));
			if ($sourcePath !== '') {
				$sourceToModule[project_module_source_key($projectLabel, $sourcePath)] = $moduleName;
			}
		}
	}
	$dependencySummaries = normalize_project_unit_dependency_summaries(is_array($projectUnitForceIncludeReport['dependency_summaries'] ?? null) ? $projectUnitForceIncludeReport['dependency_summaries'] : []);
	$evidenceSource = project_module_dependency_evidence_source($projectUnitForceIncludeReport, $dependencySummaries);
	$actualSourcesByModuleDependency = [];
	foreach ($dependencySummaries as $summary) {
		$projectLabel = trim((string) ($summary['project_root'] ?? '.'));
		if ($projectLabel === '') {
			$projectLabel = '.';
		}
		$source = normalize_config_path((string) ($summary['source'] ?? ''));
		if ($source === '') {
			continue;
		}
		$sourceModule = $sourceToModule[project_module_source_key($projectLabel, $source)] ?? null;
		if (!is_string($sourceModule) || $sourceModule === '') {
			continue;
		}
		foreach (normalize_string_list($summary['direct_source_dependencies'] ?? []) as $dependencySourceKey) {
			$dependencyModule = resolve_project_module_dependency_source_module($dependencySourceKey, $projectLabel, $sourceToModule);
			if ($dependencyModule === null || $dependencyModule === $sourceModule) {
				continue;
			}
			$actualSourcesByModuleDependency[$sourceModule][$dependencyModule][] = project_module_source_label($projectLabel, $source);
		}
	}

	$undeclared = [];
	$unusedDeclared = [];
	$inferredDependencies = [];
	foreach ($actualSourcesByModuleDependency as $moduleName => $dependencies) {
		foreach ($dependencies as $dependency => $sources) {
			$sources = normalize_string_list($sources);
			$inferredDependencies[] = [
				'module' => $moduleName,
				'dependency' => $dependency,
				'sources' => $sources,
				'message' => 'source evidence shows module `' . $moduleName . '` depends on `' . $dependency . '`',
			];
			if (!isset($declaredByModule[$moduleName][$dependency])) {
				$undeclared[] = [
					'module' => $moduleName,
					'dependency' => $dependency,
					'sources' => $sources,
					'message' => 'module `' . $moduleName . '` uses undeclared dependency `' . $dependency . '`',
				];
			}
		}
	}
	foreach ($declaredByModule as $moduleName => $dependencies) {
		foreach (array_keys($dependencies) as $dependency) {
			if (!isset($moduleNames[$dependency])) {
				continue;
			}
			if (!isset($actualSourcesByModuleDependency[$moduleName][$dependency])) {
				$unusedDeclared[] = [
					'module' => $moduleName,
					'dependency' => $dependency,
					'sources' => [],
					'message' => 'declared dependency `' . $dependency . '` has no direct source evidence for module `' . $moduleName . '`',
				];
			}
		}
	}
	usort($undeclared, static function (array $left, array $right): int {
		return strcmp((string) ($left['module'] ?? '') . "\0" . (string) ($left['dependency'] ?? ''), (string) ($right['module'] ?? '') . "\0" . (string) ($right['dependency'] ?? ''));
	});
	usort($unusedDeclared, static function (array $left, array $right): int {
		return strcmp((string) ($left['module'] ?? '') . "\0" . (string) ($left['dependency'] ?? ''), (string) ($right['module'] ?? '') . "\0" . (string) ($right['dependency'] ?? ''));
	});
	usort($inferredDependencies, static function (array $left, array $right): int {
		return strcmp((string) ($left['module'] ?? '') . "\0" . (string) ($left['dependency'] ?? ''), (string) ($right['module'] ?? '') . "\0" . (string) ($right['dependency'] ?? ''));
	});
	$status = $dependencySummaries === [] ? 'unavailable' : ($undeclared === [] ? 'ok' : 'violations');
	$notes = [];
	if ($dependencySummaries === []) {
		$notes[] = 'project-unit dependency evidence unavailable; module dependency validation did not infer source edges';
	} elseif ($evidenceSource === 'build') {
		$notes[] = 'using build-owned dependency evidence; no-STAN behavior is conservative and explicit';
	} else {
		$notes[] = 'using STAN dependency evidence';
	}
	return normalize_project_module_dependency_validation([
		'policy' => $policy,
		'status' => $status,
		'evidence_source' => $evidenceSource,
		'inferred_dependency_count' => count($inferredDependencies),
		'undeclared_dependency_count' => count($undeclared),
		'unused_declared_dependency_count' => count($unusedDeclared),
		'inferred_dependencies' => $inferredDependencies,
		'undeclared_dependencies' => $undeclared,
		'unused_declared_dependencies' => $unusedDeclared,
		'notes' => $notes,
	]);
}

/** @param array<string,string> $sourceToModule */
function resolve_project_module_dependency_source_module(string $dependencySourceKey, string $currentProjectLabel, array $sourceToModule): ?string
{
	$dependencySourceKey = normalize_config_path($dependencySourceKey);
	if ($dependencySourceKey === '' || str_starts_with($dependencySourceKey, '@external/')) {
		return null;
	}
	$currentProjectLabel = trim($currentProjectLabel);
	if ($currentProjectLabel === '') {
		$currentProjectLabel = '.';
	}
	$sameProjectKey = project_module_source_key($currentProjectLabel, $dependencySourceKey);
	if (isset($sourceToModule[$sameProjectKey])) {
		return $sourceToModule[$sameProjectKey];
	}
	foreach ($sourceToModule as $sourceKey => $moduleName) {
		if (!is_string($sourceKey) || !is_string($moduleName)) {
			continue;
		}
		[$projectLabel, $source] = array_pad(explode("\0", $sourceKey, 2), 2, '');
		$fullSource = $projectLabel === '' || $projectLabel === '.' ? $source : normalize_config_path($projectLabel . '/' . $source);
		if ($dependencySourceKey === $fullSource) {
			return $moduleName;
		}
	}
	return null;
}

/** @param list<array<string,mixed>> $moduleRows @return list<array<string,mixed>> */
function apply_project_module_dependency_validation_to_module_rows(array $moduleRows, array $validation): array
{
	$undeclaredByModule = [];
	foreach (normalize_project_module_dependency_validation_rows(is_array($validation['undeclared_dependencies'] ?? null) ? $validation['undeclared_dependencies'] : []) as $row) {
		$module = (string) ($row['module'] ?? '');
		$dependency = (string) ($row['dependency'] ?? '');
		if ($module !== '' && $dependency !== '') {
			$undeclaredByModule[$module][$dependency] = $row;
		}
	}
	$unusedByModule = [];
	foreach (normalize_project_module_dependency_validation_rows(is_array($validation['unused_declared_dependencies'] ?? null) ? $validation['unused_declared_dependencies'] : []) as $row) {
		$module = (string) ($row['module'] ?? '');
		$dependency = (string) ($row['dependency'] ?? '');
		if ($module !== '' && $dependency !== '') {
			$unusedByModule[$module][$dependency] = $row;
		}
	}
	foreach ($moduleRows as &$row) {
		if (!is_array($row)) {
			continue;
		}
		$moduleName = trim((string) ($row['name'] ?? ''));
		$undeclared = array_keys($undeclaredByModule[$moduleName] ?? []);
		$unused = array_keys($unusedByModule[$moduleName] ?? []);
		sort($undeclared, SORT_STRING);
		sort($unused, SORT_STRING);
		$row['dependency_validation_status'] = $undeclared !== [] ? 'violations' : ((string) ($validation['status'] ?? 'unavailable'));
		$row['inferred_dependencies'] = collect_project_module_inferred_dependencies_for_module($moduleName, $validation);
		$row['undeclared_dependencies'] = $undeclared;
		$row['unused_declared_dependencies'] = $unused;
	}
	unset($row);
	return $moduleRows;
}

/** @return list<string> */
function collect_project_module_inferred_dependencies_for_module(string $moduleName, array $validation): array
{
	$dependencies = [];
	foreach (normalize_project_module_dependency_validation_rows(is_array($validation['inferred_dependencies'] ?? null) ? $validation['inferred_dependencies'] : []) as $row) {
		if ((string) ($row['module'] ?? '') === $moduleName && (string) ($row['dependency'] ?? '') !== '') {
			$dependencies[] = (string) ($row['dependency'] ?? '');
		}
	}
	sort($dependencies, SORT_STRING);
	return array_values(array_unique($dependencies));
}

/**
 * @param array<string,array<string,mixed>> $projectContexts
 * @param list<array<string,mixed>> $moduleRows
 * @return array<string,mixed>
 */
function collect_project_module_public_api_validation(string $projectRoot, array $projectContexts, array $moduleRows, array $projectUnitForceIncludeReport, string $policy): array
{
	$moduleRows = normalize_project_module_rows($moduleRows);
	$dependencySummaries = normalize_project_unit_dependency_summaries(is_array($projectUnitForceIncludeReport['dependency_summaries'] ?? null) ? $projectUnitForceIncludeReport['dependency_summaries'] : []);
	$evidenceSource = project_module_dependency_evidence_source($projectUnitForceIncludeReport, $dependencySummaries);
	$fileSummaries = load_project_module_public_api_file_summaries($projectRoot, $projectContexts, $projectUnitForceIncludeReport);
	$notes = [];
	if ($dependencySummaries === []) {
		$notes[] = 'project-unit dependency evidence unavailable; module public API validation did not infer cross-module references';
	}
	if ($fileSummaries === []) {
		$notes[] = 'file summary evidence unavailable; module public API validation did not inspect declarations';
	}
	if ($dependencySummaries !== [] && $fileSummaries !== []) {
		$notes[] = $evidenceSource === 'stan'
			? 'using STAN dependency and declaration evidence'
			: 'using build-owned dependency and declaration evidence; no-STAN behavior is conservative and explicit';
	}

	$sourceToModule = [];
	$moduleDeclarations = [];
	foreach ($moduleRows as $module) {
		$moduleName = trim((string) ($module['name'] ?? ''));
		$projectLabel = trim((string) ($module['project_root'] ?? '.'));
		if ($moduleName === '') {
			continue;
		}
		$moduleDeclarations[$moduleName] = [];
		foreach (normalize_project_module_source_rows(is_array($module['sources'] ?? null) ? $module['sources'] : []) as $source) {
			$sourcePath = normalize_config_path((string) ($source['source'] ?? ''));
			if ($sourcePath === '') {
				continue;
			}
			$sourceToModule[project_module_source_key($projectLabel, $sourcePath)] = $moduleName;
			$summary = project_module_public_api_file_summary_for_source($fileSummaries, $projectLabel, $sourcePath);
			if ($summary === null) {
				continue;
			}
			foreach (collect_project_module_public_api_declarations($summary, project_module_source_label($projectLabel, $sourcePath)) as $declaration) {
				foreach (project_module_public_symbol_aliases((string) ($declaration['name'] ?? '')) as $alias => $_present) {
					$moduleDeclarations[$moduleName][$alias] = $declaration;
				}
			}
		}
	}

	$resolvedExports = [];
	$unknownExports = [];
	$publicExportAliasesByModule = [];
	foreach ($moduleRows as $module) {
		$moduleName = trim((string) ($module['name'] ?? ''));
		if ($moduleName === '') {
			continue;
		}
		$publicExportAliasesByModule[$moduleName] = [];
		foreach (normalize_string_list($module['public_exports'] ?? []) as $export) {
			foreach (project_module_public_symbol_aliases($export) as $alias => $_present) {
				$publicExportAliasesByModule[$moduleName][$alias] = true;
			}
			if ($fileSummaries === []) {
				continue;
			}
			if (project_module_public_target_matches_exports($export, $moduleDeclarations[$moduleName] ?? [])) {
				$resolvedExports[] = [
					'module' => $moduleName,
					'export' => $export,
					'message' => 'module `' . $moduleName . '` public export `' . $export . '` resolves to a declaration',
				];
			} else {
				$unknownExports[] = [
					'module' => $moduleName,
					'export' => $export,
					'message' => 'module `' . $moduleName . '` declares unknown public export `' . $export . '`',
				];
			}
		}
	}

	$privateDependencies = [];
	if ($dependencySummaries !== [] && $fileSummaries !== []) {
		foreach ($dependencySummaries as $summary) {
			$projectLabel = trim((string) ($summary['project_root'] ?? '.'));
			if ($projectLabel === '') {
				$projectLabel = '.';
			}
			$source = normalize_config_path((string) ($summary['source'] ?? ''));
			if ($source === '') {
				continue;
			}
			$sourceModule = $sourceToModule[project_module_source_key($projectLabel, $source)] ?? null;
			if (!is_string($sourceModule) || $sourceModule === '') {
				continue;
			}
			foreach (normalize_project_unit_dependency_category_rows(is_array($summary['dependency_categories'] ?? null) ? $summary['dependency_categories'] : []) as $category) {
				$target = trim((string) ($category['target'] ?? ''));
				if ($target === '') {
					continue;
				}
				foreach (normalize_string_list($category['source_dependencies'] ?? []) as $dependencySourceKey) {
					$dependencyModule = resolve_project_module_dependency_source_module($dependencySourceKey, $projectLabel, $sourceToModule);
					if ($dependencyModule === null || $dependencyModule === $sourceModule) {
						continue;
					}
					$publicAliases = $publicExportAliasesByModule[$dependencyModule] ?? [];
					if ($publicAliases === []) {
						continue;
					}
					if (project_module_public_target_matches_exports($target, $publicAliases)) {
						continue;
					}
					$key = implode("\0", [
						$sourceModule,
						$dependencyModule,
						(string) ($category['kind'] ?? ''),
						$target,
						(string) ($category['category'] ?? ''),
					]);
					if (!isset($privateDependencies[$key])) {
						$privateDependencies[$key] = [
							'module' => $sourceModule,
							'dependency' => $dependencyModule,
							'target' => $target,
							'kind' => (string) ($category['kind'] ?? ''),
							'category' => (string) ($category['category'] ?? ''),
							'sources' => [],
							'message' => 'module `' . $sourceModule . '` uses private declaration `' . $target . '` from module `' . $dependencyModule . '`',
						];
					}
					$privateDependencies[$key]['sources'][] = project_module_source_label($projectLabel, $source);
				}
			}
		}
	}

	$privateDependencies = array_values(array_map(static function (array $row): array {
		$row['sources'] = normalize_string_list($row['sources'] ?? []);
		return $row;
	}, $privateDependencies));
	usort($privateDependencies, static function (array $left, array $right): int {
		foreach (['module', 'dependency', 'target', 'kind', 'category'] as $key) {
			$compare = strcmp((string) ($left[$key] ?? ''), (string) ($right[$key] ?? ''));
			if ($compare !== 0) {
				return $compare;
			}
		}
		return strcmp(implode("\n", normalize_string_list($left['sources'] ?? [])), implode("\n", normalize_string_list($right['sources'] ?? [])));
	});
	usort($unknownExports, static function (array $left, array $right): int {
		return strcmp((string) ($left['module'] ?? '') . "\0" . (string) ($left['export'] ?? ''), (string) ($right['module'] ?? '') . "\0" . (string) ($right['export'] ?? ''));
	});
	usort($resolvedExports, static function (array $left, array $right): int {
		return strcmp((string) ($left['module'] ?? '') . "\0" . (string) ($left['export'] ?? ''), (string) ($right['module'] ?? '') . "\0" . (string) ($right['export'] ?? ''));
	});
	$status = ($dependencySummaries === [] || $fileSummaries === [])
		? 'unavailable'
		: ($privateDependencies === [] && $unknownExports === [] ? 'ok' : 'violations');

	return normalize_project_module_public_api_validation([
		'policy' => $policy,
		'status' => $status,
		'evidence_source' => $evidenceSource,
		'resolved_public_export_count' => count($resolvedExports),
		'unknown_public_export_count' => count($unknownExports),
		'private_dependency_count' => count($privateDependencies),
		'resolved_public_exports' => $resolvedExports,
		'unknown_public_exports' => $unknownExports,
		'private_dependencies' => $privateDependencies,
		'notes' => $notes,
	]);
}

/**
 * @param array<string,array<string,mixed>> $projectContexts
 * @return array<string,array<string,mixed>>
 */
function load_project_module_public_api_file_summaries(string $projectRoot, array $projectContexts, array $projectUnitForceIncludeReport): array
{
	if (is_array($projectUnitForceIncludeReport['file_summaries'] ?? null)) {
		return normalize_project_module_public_api_file_summary_map($projectUnitForceIncludeReport['file_summaries']);
	}
	$normalizedProjectRoot = normalize_path($projectRoot);
	$rootContext = is_array($projectContexts[$normalizedProjectRoot] ?? null) ? $projectContexts[$normalizedProjectRoot] : [];
	if (!is_string($rootContext['cache_dir'] ?? null)) {
		return [];
	}
	$dependencyArtifact = normalize_project_unit_dependency_summary_artifact_info(is_array($projectUnitForceIncludeReport['dependency_summary_artifact'] ?? null) ? $projectUnitForceIncludeReport['dependency_summary_artifact'] : []);
	$statePath = (bool) ($dependencyArtifact['used_stan_dependency_state'] ?? false)
		? normalize_path($rootContext['cache_dir'] . '/' . SCPP_STAN_STATE_FILE)
		: normalize_path($rootContext['cache_dir'] . '/' . SCPP_PROJECT_UNIT_DEPENDENCY_STATE_FILE);
	$state = load_project_unit_dependency_state_from_state_file($statePath);
	if (!is_array($state) || !is_array($state['file_summaries'] ?? null)) {
		return [];
	}
	return normalize_project_module_public_api_file_summary_map($state['file_summaries']);
}

/** @return array<string,array<string,mixed>> */
function normalize_project_module_public_api_file_summary_map(array $summaries): array
{
	$result = [];
	foreach ($summaries as $sourceKey => $summary) {
		if (!is_string($sourceKey) || !is_array($summary)) {
			continue;
		}
		$sourceKey = normalize_config_path(trim($sourceKey));
		if ($sourceKey === '') {
			continue;
		}
		$result[$sourceKey] = $summary;
	}
	ksort($result, SORT_STRING);
	return $result;
}

/**
 * @param array<string,array<string,mixed>> $fileSummaries
 * @return array<string,mixed>|null
 */
function project_module_public_api_file_summary_for_source(array $fileSummaries, string $projectLabel, string $source): ?array
{
	$projectLabel = trim($projectLabel);
	if ($projectLabel === '') {
		$projectLabel = '.';
	}
	$source = normalize_config_path($source);
	$candidates = [$source, project_module_source_key($projectLabel, $source)];
	if ($projectLabel !== '.') {
		$candidates[] = normalize_config_path($projectLabel . '/' . $source);
	}
	foreach (normalize_string_list($candidates) as $candidate) {
		if (is_array($fileSummaries[$candidate] ?? null)) {
			return $fileSummaries[$candidate];
		}
	}
	return null;
}

/** @return list<array{name:string,kind:string,source:string}> */
function collect_project_module_public_api_declarations(array $summary, string $sourceLabel): array
{
	$declarations = [];
	foreach (project_unit_summary_classes($summary) as $class) {
		$name = project_module_public_declaration_name($class);
		if ($name !== '') {
			$declarations[] = [
				'name' => $name,
				'kind' => trim((string) ($class['declaration_kind'] ?? 'class')),
				'source' => $sourceLabel,
			];
		}
	}
	foreach (project_unit_summary_function_buckets($summary) as $function) {
		$name = project_module_public_declaration_name($function);
		if ($name !== '') {
			$declarations[] = [
				'name' => $name,
				'kind' => 'function',
				'source' => $sourceLabel,
			];
		}
	}
	foreach (project_unit_summary_top_level_constants($summary) as $constant) {
		$name = project_module_public_declaration_name($constant);
		if ($name !== '') {
			$declarations[] = [
				'name' => $name,
				'kind' => 'constant',
				'source' => $sourceLabel,
			];
		}
	}
	usort($declarations, static function (array $left, array $right): int {
		return strcmp((string) ($left['name'] ?? '') . "\0" . (string) ($left['kind'] ?? ''), (string) ($right['name'] ?? '') . "\0" . (string) ($right['kind'] ?? ''));
	});
	return $declarations;
}

/** @param array<string,mixed> $declaration */
function project_module_public_declaration_name(array $declaration): string
{
	$name = trim((string) ($declaration['name'] ?? ''), "\\ \t\n\r\0\x0B");
	if ($name === '') {
		return '';
	}
	$namespace = trim((string) ($declaration['namespace'] ?? ''), "\\ \t\n\r\0\x0B");
	return $namespace === '' ? $name : $namespace . '\\' . $name;
}

/** @return array<string,bool> */
function project_module_public_symbol_aliases(string $symbol): array
{
	$symbol = trim($symbol, "\\ \t\n\r\0\x0B");
	if ($symbol === '') {
		return [];
	}
	$candidates = [$symbol];
	if (str_contains($symbol, '::')) {
		$candidates[] = strstr($symbol, '::', true) ?: $symbol;
	}
	$withoutMember = str_contains($symbol, '::') ? (strstr($symbol, '::', true) ?: $symbol) : $symbol;
	if (str_contains($withoutMember, '\\')) {
		$parts = explode('\\', $withoutMember);
		$last = end($parts);
		if (is_string($last) && $last !== '') {
			$candidates[] = $last;
		}
	}
	$aliases = [];
	foreach ($candidates as $candidate) {
		$normalized = project_module_public_symbol_key($candidate);
		if ($normalized !== '') {
			$aliases[$normalized] = true;
		}
	}
	ksort($aliases, SORT_STRING);
	return $aliases;
}

function project_module_public_symbol_key(string $symbol): string
{
	$symbol = trim($symbol, "\\ \t\n\r\0\x0B");
	$symbol = preg_replace('/\s+/', '', $symbol) ?? $symbol;
	return strtolower($symbol);
}

/** @param array<string,mixed>|array<string,bool> $exports */
function project_module_public_target_matches_exports(string $target, array $exports): bool
{
	foreach (project_module_public_symbol_aliases($target) as $alias => $_present) {
		if (array_key_exists($alias, $exports)) {
			return true;
		}
	}
	return false;
}

/** @param list<array<string,mixed>> $moduleRows @return list<array<string,mixed>> */
function apply_project_module_public_api_validation_to_module_rows(array $moduleRows, array $validation): array
{
	$resolvedByModule = [];
	foreach (normalize_project_module_public_api_export_rows(is_array($validation['resolved_public_exports'] ?? null) ? $validation['resolved_public_exports'] : []) as $row) {
		$module = (string) ($row['module'] ?? '');
		$export = (string) ($row['export'] ?? '');
		if ($module !== '' && $export !== '') {
			$resolvedByModule[$module][] = $export;
		}
	}
	$unknownByModule = [];
	foreach (normalize_project_module_public_api_unknown_export_rows(is_array($validation['unknown_public_exports'] ?? null) ? $validation['unknown_public_exports'] : []) as $row) {
		$module = (string) ($row['module'] ?? '');
		$export = (string) ($row['export'] ?? '');
		if ($module !== '' && $export !== '') {
			$unknownByModule[$module][] = $export;
		}
	}
	$privateByModule = [];
	foreach (normalize_project_module_public_api_private_dependency_rows(is_array($validation['private_dependencies'] ?? null) ? $validation['private_dependencies'] : []) as $row) {
		$module = (string) ($row['module'] ?? '');
		$dependency = (string) ($row['dependency'] ?? '');
		$target = (string) ($row['target'] ?? '');
		if ($module !== '' && $dependency !== '' && $target !== '') {
			$privateByModule[$module][] = $dependency . ':' . $target;
		}
	}
	foreach ($moduleRows as &$row) {
		if (!is_array($row)) {
			continue;
		}
		$moduleName = trim((string) ($row['name'] ?? ''));
		$resolved = normalize_string_list($resolvedByModule[$moduleName] ?? []);
		$unknown = normalize_string_list($unknownByModule[$moduleName] ?? []);
		$private = normalize_string_list($privateByModule[$moduleName] ?? []);
		$row['public_api_validation_status'] = ($unknown !== [] || $private !== []) ? 'violations' : ((string) ($validation['status'] ?? 'unavailable'));
		$row['resolved_public_exports'] = $resolved;
		$row['unknown_public_exports'] = $unknown;
		$row['private_dependency_violations'] = $private;
	}
	unset($row);
	return $moduleRows;
}

/** @param array<string,mixed> $payload */
function hash_project_module_payload(array $payload): string
{
	$json = json_encode($payload, JSON_UNESCAPED_SLASHES);
	if (!is_string($json)) {
		scpp_fail('Failed to encode project module hash payload.' . PHP_EOL, 2);
	}
	return hash('sha256', $json);
}

/** @param array<string,mixed> $artifact @return array<string,mixed> */
function write_project_module_surface_artifact(string $projectRoot, string $contextProjectRoot, array $projectContext, array $artifact): array
{
	if (!is_string($projectContext['cache_dir'] ?? null)) {
		return [
			'surface_artifact' => '',
			'implementation_artifact' => '',
			'cache_status' => 'unavailable',
			'interface_changed' => true,
			'implementation_changed' => true,
		];
	}
	$moduleName = (string) ($artifact['name'] ?? 'module');
	$surfacePath = normalize_path($projectContext['cache_dir'] . '/project_modules/' . project_module_artifact_filename($moduleName, 'surface'));
	$implementationPath = normalize_path($projectContext['cache_dir'] . '/project_modules/' . project_module_artifact_filename($moduleName, 'implementation'));
	$previousSurface = read_json_file($surfacePath);
	$previousImplementation = read_json_file($implementationPath);
	$previousInterfaceHash = is_array($previousSurface) ? trim((string) ($previousSurface['interface_hash'] ?? '')) : '';
	$previousImplementationHash = is_array($previousImplementation) ? trim((string) ($previousImplementation['implementation_hash'] ?? '')) : '';
	$interfaceHash = trim((string) ($artifact['interface_hash'] ?? ''));
	$implementationHash = trim((string) ($artifact['implementation_hash'] ?? ''));
	$interfaceChanged = $previousInterfaceHash === '' || $previousInterfaceHash !== $interfaceHash;
	$implementationChanged = $previousImplementationHash === '' || $previousImplementationHash !== $implementationHash;
	$cacheStatus = 'hit';
	if ($previousSurface === null || $previousImplementation === null) {
		$cacheStatus = 'new';
	} elseif ($interfaceChanged && $implementationChanged) {
		$cacheStatus = 'interface_and_implementation_changed';
	} elseif ($interfaceChanged) {
		$cacheStatus = 'interface_changed';
	} elseif ($implementationChanged) {
		$cacheStatus = 'implementation_changed';
	}

	$artifactPayload = [
		'version' => 1,
		'kind' => 'project_module_surface',
		'name' => $moduleName,
		'project_root' => project_context_report_label($projectRoot, $contextProjectRoot),
		'source_roots' => normalize_string_list($artifact['source_roots'] ?? []),
		'configured_sources' => normalize_string_list($artifact['configured_sources'] ?? []),
		'dependencies' => normalize_string_list($artifact['dependencies'] ?? []),
		'public_exports' => normalize_string_list($artifact['public_exports'] ?? []),
		'interface_hash' => $interfaceHash,
		'consumer_rebuild_policy' => 'interface_hash_only',
		'sources' => normalize_project_module_surface_source_rows(is_array($artifact['sources'] ?? null) ? $artifact['sources'] : []),
	];
	$surfaceJson = json_encode($artifactPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	if (!is_string($surfaceJson)) {
		scpp_fail('Failed to encode project module surface artifact: ' . $surfacePath . PHP_EOL, 2);
	}
	write_text_file($surfacePath, $surfaceJson . PHP_EOL);

	$implementationPayload = [
		'version' => 1,
		'kind' => 'project_module_implementation',
		'name' => $moduleName,
		'project_root' => project_context_report_label($projectRoot, $contextProjectRoot),
		'implementation_hash' => $implementationHash,
		'interface_hash' => $interfaceHash,
		'implementation_artifacts' => normalize_string_list(array_map(static fn (array $source): string => (string) ($source['generated_cpp'] ?? ''), is_array($artifact['sources'] ?? null) ? $artifact['sources'] : [])),
		'sources' => normalize_project_module_source_rows(is_array($artifact['sources'] ?? null) ? $artifact['sources'] : []),
	];
	$implementationJson = json_encode($implementationPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	if (!is_string($implementationJson)) {
		scpp_fail('Failed to encode project module implementation artifact: ' . $implementationPath . PHP_EOL, 2);
	}
	write_text_file($implementationPath, $implementationJson . PHP_EOL);

	return [
		'surface_artifact' => normalize_config_path(relative_path($projectRoot, $surfacePath)),
		'implementation_artifact' => normalize_config_path(relative_path($projectRoot, $implementationPath)),
		'cache_status' => $cacheStatus,
		'interface_changed' => $interfaceChanged,
		'implementation_changed' => $implementationChanged,
	];
}

function project_module_artifact_filename(string $moduleName, string $kind): string
{
	$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9_.-]+/', '-', $moduleName) ?? ''));
	$slug = trim($slug, '.-');
	if ($slug === '') {
		$slug = 'module';
	}
	$suffix = match ($kind) {
		'implementation' => 'implementation',
		'stan_summary', 'stan-summary' => 'stan-summary',
		default => 'surface',
	};
	return $slug . '-' . substr(hash('sha256', $moduleName), 0, 12) . '.' . $suffix . '.json';
}

/** @param list<array<string,mixed>> $moduleRows */
function write_project_module_manifest(string $projectRoot, string $contextProjectRoot, array $projectContext, array $moduleRows): ?string
{
	if (!is_string($projectContext['cache_dir'] ?? null)) {
		return null;
	}
	$manifestPath = normalize_path($projectContext['cache_dir'] . '/project_modules/manifest.json');
	$modules = [];
	foreach (normalize_project_module_rows($moduleRows) as $row) {
		$modules[] = [
			'name' => (string) ($row['name'] ?? ''),
			'project_root' => (string) ($row['project_root'] ?? ''),
			'source_count' => (int) ($row['source_count'] ?? 0),
			'source_roots' => normalize_string_list($row['source_roots'] ?? []),
			'configured_sources' => normalize_string_list($row['configured_sources'] ?? []),
			'dependencies' => normalize_string_list($row['dependencies'] ?? []),
			'public_exports' => normalize_string_list($row['public_exports'] ?? []),
			'interface_hash' => (string) ($row['interface_hash'] ?? ''),
			'implementation_hash' => (string) ($row['implementation_hash'] ?? ''),
			'surface_artifact' => (string) ($row['surface_artifact'] ?? ''),
			'implementation_artifact' => (string) ($row['implementation_artifact'] ?? ''),
			'stan_summary_artifact' => (string) ($row['stan_summary_artifact'] ?? ''),
			'stan_summary_hash' => (string) ($row['stan_summary_hash'] ?? ''),
			'stan_summary_evidence_source' => (string) ($row['stan_summary_evidence_source'] ?? 'none'),
			'stan_summary_cache_status' => (string) ($row['stan_summary_cache_status'] ?? 'unavailable'),
			'consumer_rebuild_policy' => (string) ($row['consumer_rebuild_policy'] ?? 'interface_hash_only'),
		];
	}
	$payload = [
		'version' => 1,
		'kind' => 'project_module_manifest',
		'project_root' => project_context_report_label($projectRoot, $contextProjectRoot),
		'modules' => $modules,
	];
	$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	if (!is_string($json)) {
		scpp_fail('Failed to encode project module manifest: ' . $manifestPath . PHP_EOL, 2);
	}
	write_text_file($manifestPath, $json . PHP_EOL);
	return normalize_config_path(relative_path($projectRoot, $manifestPath));
}

/** @param list<array<string,mixed>> $moduleRows @return array<string,int> */
function summarize_project_module_cache_status_counts(array $moduleRows): array
{
	$counts = [
		'hit' => 0,
		'new' => 0,
		'interface_changed' => 0,
		'implementation_changed' => 0,
		'interface_and_implementation_changed' => 0,
		'unavailable' => 0,
	];
	foreach ($moduleRows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$status = trim((string) ($row['cache_status'] ?? 'unavailable'));
		if (!array_key_exists($status, $counts)) {
			$counts[$status] = 0;
		}
		$counts[$status]++;
	}
	ksort($counts, SORT_STRING);
	return $counts;
}

/** @param list<array<string,mixed>> $moduleRows @return array<string,int> */
function summarize_project_module_stan_summary_cache_status_counts(array $moduleRows): array
{
	$counts = [
		'hit' => 0,
		'new' => 0,
		'changed' => 0,
		'unavailable' => 0,
	];
	foreach ($moduleRows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$status = trim((string) ($row['stan_summary_cache_status'] ?? 'unavailable'));
		if (!array_key_exists($status, $counts)) {
			$counts[$status] = 0;
		}
		$counts[$status]++;
	}
	ksort($counts, SORT_STRING);
	return $counts;
}

/** @return array<string,mixed> */
function normalize_project_module_report(array $report): array
{
	$modules = normalize_project_module_rows(is_array($report['modules'] ?? null) ? $report['modules'] : []);
	$cacheStatusCounts = is_array($report['cache_status_counts'] ?? null) ? $report['cache_status_counts'] : summarize_project_module_cache_status_counts($modules);
	$normalizedCacheStatusCounts = [];
	foreach ($cacheStatusCounts as $status => $count) {
		if (is_string($status) && trim($status) !== '') {
			$normalizedCacheStatusCounts[$status] = max(0, (int) $count);
		}
	}
	ksort($normalizedCacheStatusCounts, SORT_STRING);
	$stanSummaryCacheStatusCounts = is_array($report['stan_summary_cache_status_counts'] ?? null) ? $report['stan_summary_cache_status_counts'] : summarize_project_module_stan_summary_cache_status_counts($modules);
	$normalizedStanSummaryCacheStatusCounts = [];
	foreach ($stanSummaryCacheStatusCounts as $status => $count) {
		if (is_string($status) && trim($status) !== '') {
			$normalizedStanSummaryCacheStatusCounts[$status] = max(0, (int) $count);
		}
	}
	ksort($normalizedStanSummaryCacheStatusCounts, SORT_STRING);
	$duplicateAssignments = [];
	foreach (is_array($report['duplicate_assignments'] ?? null) ? $report['duplicate_assignments'] : [] as $assignment) {
		if (!is_array($assignment)) {
			continue;
		}
		$source = trim((string) ($assignment['source'] ?? ''));
		if ($source === '') {
			continue;
		}
		$duplicateAssignments[] = [
			'source' => $source,
			'modules' => normalize_string_list($assignment['modules'] ?? []),
		];
	}
	usort($duplicateAssignments, static fn (array $left, array $right): int => strcmp((string) ($left['source'] ?? ''), (string) ($right['source'] ?? '')));
	return [
		'configured' => (bool) ($report['configured'] ?? ($modules !== [])),
		'total_modules' => max(0, (int) ($report['total_modules'] ?? count($modules))),
		'generated_source_count' => max(0, (int) ($report['generated_source_count'] ?? 0)),
		'assigned_source_count' => max(0, (int) ($report['assigned_source_count'] ?? 0)),
		'unassigned_source_count' => max(0, (int) ($report['unassigned_source_count'] ?? 0)),
		'unassigned_sources' => normalize_string_list($report['unassigned_sources'] ?? []),
		'duplicate_assignments' => $duplicateAssignments,
		'consumer_rebuild_required_count' => max(0, (int) ($report['consumer_rebuild_required_count'] ?? 0)),
		'cache_status_counts' => $normalizedCacheStatusCounts,
		'stan_summary_cache_status_counts' => $normalizedStanSummaryCacheStatusCounts,
		'dependency_validation' => normalize_project_module_dependency_validation(is_array($report['dependency_validation'] ?? null) ? $report['dependency_validation'] : []),
		'public_api_validation' => normalize_project_module_public_api_validation(is_array($report['public_api_validation'] ?? null) ? $report['public_api_validation'] : []),
		'manifest_artifacts' => normalize_string_list($report['manifest_artifacts'] ?? []),
		'modules' => $modules,
	];
}

/** @return array<string,mixed> */
function normalize_project_module_dependency_validation(array $validation): array
{
	$policy = trim((string) ($validation['policy'] ?? 'report'));
	if (!in_array($policy, ['report', 'warn', 'fail'], true)) {
		$policy = 'report';
	}
	$status = trim((string) ($validation['status'] ?? 'unavailable'));
	if (!in_array($status, ['ok', 'violations', 'unavailable'], true)) {
		$status = 'unavailable';
	}
	$evidenceSource = trim((string) ($validation['evidence_source'] ?? 'none'));
	if (!in_array($evidenceSource, ['stan', 'build', 'none'], true)) {
		$evidenceSource = 'none';
	}
	$inferred = normalize_project_module_dependency_validation_rows(is_array($validation['inferred_dependencies'] ?? null) ? $validation['inferred_dependencies'] : []);
	$undeclared = normalize_project_module_dependency_validation_rows(is_array($validation['undeclared_dependencies'] ?? null) ? $validation['undeclared_dependencies'] : []);
	$unused = normalize_project_module_dependency_validation_rows(is_array($validation['unused_declared_dependencies'] ?? null) ? $validation['unused_declared_dependencies'] : []);
	return [
		'policy' => $policy,
		'status' => $status,
		'evidence_source' => $evidenceSource,
		'inferred_dependency_count' => max(0, (int) ($validation['inferred_dependency_count'] ?? count($inferred))),
		'undeclared_dependency_count' => max(0, (int) ($validation['undeclared_dependency_count'] ?? count($undeclared))),
		'unused_declared_dependency_count' => max(0, (int) ($validation['unused_declared_dependency_count'] ?? count($unused))),
		'inferred_dependencies' => $inferred,
		'undeclared_dependencies' => $undeclared,
		'unused_declared_dependencies' => $unused,
		'notes' => normalize_string_list($validation['notes'] ?? []),
	];
}

/** @return list<array{module:string,dependency:string,sources:list<string>,message:string}> */
function normalize_project_module_dependency_validation_rows(array $rows): array
{
	$result = [];
	foreach ($rows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$module = trim((string) ($row['module'] ?? ''));
		$dependency = trim((string) ($row['dependency'] ?? ''));
		if ($module === '' || $dependency === '') {
			continue;
		}
		$result[] = [
			'module' => $module,
			'dependency' => $dependency,
			'sources' => normalize_string_list($row['sources'] ?? []),
			'message' => trim((string) ($row['message'] ?? '')),
		];
	}
	usort($result, static function (array $left, array $right): int {
		return strcmp((string) ($left['module'] ?? '') . "\0" . (string) ($left['dependency'] ?? ''), (string) ($right['module'] ?? '') . "\0" . (string) ($right['dependency'] ?? ''));
	});
	return $result;
}

/** @return array<string,mixed> */
function normalize_project_module_public_api_validation(array $validation): array
{
	$policy = trim((string) ($validation['policy'] ?? 'report'));
	if (!in_array($policy, ['report', 'warn', 'fail'], true)) {
		$policy = 'report';
	}
	$status = trim((string) ($validation['status'] ?? 'unavailable'));
	if (!in_array($status, ['ok', 'violations', 'unavailable'], true)) {
		$status = 'unavailable';
	}
	$evidenceSource = trim((string) ($validation['evidence_source'] ?? 'none'));
	if (!in_array($evidenceSource, ['stan', 'build', 'none'], true)) {
		$evidenceSource = 'none';
	}
	$resolved = normalize_project_module_public_api_export_rows(is_array($validation['resolved_public_exports'] ?? null) ? $validation['resolved_public_exports'] : []);
	$unknown = normalize_project_module_public_api_unknown_export_rows(is_array($validation['unknown_public_exports'] ?? null) ? $validation['unknown_public_exports'] : []);
	$private = normalize_project_module_public_api_private_dependency_rows(is_array($validation['private_dependencies'] ?? null) ? $validation['private_dependencies'] : []);
	return [
		'policy' => $policy,
		'status' => $status,
		'evidence_source' => $evidenceSource,
		'resolved_public_export_count' => max(0, (int) ($validation['resolved_public_export_count'] ?? count($resolved))),
		'unknown_public_export_count' => max(0, (int) ($validation['unknown_public_export_count'] ?? count($unknown))),
		'private_dependency_count' => max(0, (int) ($validation['private_dependency_count'] ?? count($private))),
		'resolved_public_exports' => $resolved,
		'unknown_public_exports' => $unknown,
		'private_dependencies' => $private,
		'notes' => normalize_string_list($validation['notes'] ?? []),
	];
}

/** @return list<array{module:string,export:string,message:string}> */
function normalize_project_module_public_api_export_rows(array $rows): array
{
	$result = [];
	foreach ($rows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$module = trim((string) ($row['module'] ?? ''));
		$export = trim((string) ($row['export'] ?? ''));
		if ($module === '' || $export === '') {
			continue;
		}
		$result[] = [
			'module' => $module,
			'export' => $export,
			'message' => trim((string) ($row['message'] ?? '')),
		];
	}
	usort($result, static function (array $left, array $right): int {
		return strcmp((string) ($left['module'] ?? '') . "\0" . (string) ($left['export'] ?? ''), (string) ($right['module'] ?? '') . "\0" . (string) ($right['export'] ?? ''));
	});
	return $result;
}

/** @return list<array{module:string,export:string,message:string}> */
function normalize_project_module_public_api_unknown_export_rows(array $rows): array
{
	return normalize_project_module_public_api_export_rows($rows);
}

/** @return list<array{module:string,dependency:string,target:string,kind:string,category:string,sources:list<string>,message:string}> */
function normalize_project_module_public_api_private_dependency_rows(array $rows): array
{
	$result = [];
	foreach ($rows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$module = trim((string) ($row['module'] ?? ''));
		$dependency = trim((string) ($row['dependency'] ?? ''));
		$target = trim((string) ($row['target'] ?? ''));
		if ($module === '' || $dependency === '' || $target === '') {
			continue;
		}
		$result[] = [
			'module' => $module,
			'dependency' => $dependency,
			'target' => $target,
			'kind' => trim((string) ($row['kind'] ?? '')),
			'category' => trim((string) ($row['category'] ?? '')),
			'sources' => normalize_string_list($row['sources'] ?? []),
			'message' => trim((string) ($row['message'] ?? '')),
		];
	}
	usort($result, static function (array $left, array $right): int {
		foreach (['module', 'dependency', 'target', 'kind', 'category'] as $key) {
			$compare = strcmp((string) ($left[$key] ?? ''), (string) ($right[$key] ?? ''));
			if ($compare !== 0) {
				return $compare;
			}
		}
		return strcmp(implode("\n", normalize_string_list($left['sources'] ?? [])), implode("\n", normalize_string_list($right['sources'] ?? [])));
	});
	return $result;
}

/** @param list<array<string,mixed>> $rows @return list<array<string,mixed>> */
function normalize_project_module_rows(array $rows): array
{
	$result = [];
	foreach ($rows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$name = trim((string) ($row['name'] ?? ''));
		if ($name === '') {
			continue;
		}
		$result[] = [
			'name' => $name,
			'project_root' => trim((string) ($row['project_root'] ?? '.')),
			'source_roots' => normalize_string_list($row['source_roots'] ?? []),
			'configured_sources' => normalize_string_list($row['configured_sources'] ?? []),
			'dependencies' => normalize_string_list($row['dependencies'] ?? []),
			'public_exports' => normalize_string_list($row['public_exports'] ?? []),
			'source_count' => max(0, (int) ($row['source_count'] ?? 0)),
			'sources' => normalize_project_module_source_rows(is_array($row['sources'] ?? null) ? $row['sources'] : []),
			'interface_hash' => trim((string) ($row['interface_hash'] ?? '')),
			'implementation_hash' => trim((string) ($row['implementation_hash'] ?? '')),
			'surface_artifact' => trim((string) ($row['surface_artifact'] ?? '')),
			'implementation_artifact' => trim((string) ($row['implementation_artifact'] ?? '')),
			'stan_summary_artifact' => trim((string) ($row['stan_summary_artifact'] ?? '')),
			'stan_summary_hash' => trim((string) ($row['stan_summary_hash'] ?? '')),
			'stan_summary_evidence_source' => trim((string) ($row['stan_summary_evidence_source'] ?? 'none')),
			'stan_summary_cache_status' => trim((string) ($row['stan_summary_cache_status'] ?? 'unavailable')),
			'stan_summary_cache_reasons' => normalize_string_list($row['stan_summary_cache_reasons'] ?? []),
			'cache_status' => trim((string) ($row['cache_status'] ?? 'unavailable')),
			'interface_changed' => (bool) ($row['interface_changed'] ?? false),
			'implementation_changed' => (bool) ($row['implementation_changed'] ?? false),
			'implementation_artifacts' => normalize_string_list($row['implementation_artifacts'] ?? []),
			'consumer_rebuild_policy' => trim((string) ($row['consumer_rebuild_policy'] ?? 'interface_hash_only')),
			'consumer_rebuild_required' => (bool) ($row['consumer_rebuild_required'] ?? false),
			'consumer_rebuild_reasons' => normalize_string_list($row['consumer_rebuild_reasons'] ?? []),
			'unresolved_dependencies' => normalize_string_list($row['unresolved_dependencies'] ?? []),
			'dependency_validation_status' => trim((string) ($row['dependency_validation_status'] ?? 'unavailable')),
			'inferred_dependencies' => normalize_string_list($row['inferred_dependencies'] ?? []),
			'undeclared_dependencies' => normalize_string_list($row['undeclared_dependencies'] ?? []),
			'unused_declared_dependencies' => normalize_string_list($row['unused_declared_dependencies'] ?? []),
			'public_api_validation_status' => trim((string) ($row['public_api_validation_status'] ?? 'unavailable')),
			'resolved_public_exports' => normalize_string_list($row['resolved_public_exports'] ?? []),
			'unknown_public_exports' => normalize_string_list($row['unknown_public_exports'] ?? []),
			'private_dependency_violations' => normalize_string_list($row['private_dependency_violations'] ?? []),
		];
	}
	usort($result, static function (array $left, array $right): int {
		$leftKey = (string) ($left['project_root'] ?? '') . "\0" . (string) ($left['name'] ?? '');
		$rightKey = (string) ($right['project_root'] ?? '') . "\0" . (string) ($right['name'] ?? '');
		return strcmp($leftKey, $rightKey);
	});
	return $result;
}

/** @param list<array<string,mixed>> $rows @return list<array<string,mixed>> */
function normalize_project_module_surface_source_rows(array $rows): array
{
	$result = [];
	foreach (normalize_project_module_source_rows($rows) as $row) {
		$result[] = [
			'source' => (string) ($row['source'] ?? ''),
			'project_root' => (string) ($row['project_root'] ?? '.'),
			'generated_header' => (string) ($row['generated_header'] ?? ''),
			'interface_hash' => (string) ($row['interface_hash'] ?? ''),
		];
	}
	return $result;
}

/** @param list<array<string,mixed>> $rows @return list<array<string,mixed>> */
function normalize_project_module_source_rows(array $rows): array
{
	$result = [];
	foreach ($rows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$source = normalize_config_path((string) ($row['source'] ?? ''));
		if ($source === '') {
			continue;
		}
		$result[] = [
			'source' => $source,
			'project_root' => trim((string) ($row['project_root'] ?? '.')),
			'generated_header' => normalize_config_path((string) ($row['generated_header'] ?? '')),
			'generated_cpp' => normalize_config_path((string) ($row['generated_cpp'] ?? '')),
			'object_path' => normalize_config_path((string) ($row['object_path'] ?? '')),
			'interface_hash' => trim((string) ($row['interface_hash'] ?? '')),
			'implementation_hash' => trim((string) ($row['implementation_hash'] ?? '')),
		];
	}
	usort($result, static fn (array $left, array $right): int => strcmp((string) ($left['source'] ?? ''), (string) ($right['source'] ?? '')));
	return $result;
}

function project_context_report_label(string $projectRoot, string $contextProjectRoot): string
{
	$label = normalize_config_path(relative_path(normalize_path($projectRoot), normalize_path($contextProjectRoot)));
	return $label === '' ? '.' : $label;
}

function project_module_source_label(string $projectLabel, string $source): string
{
	$source = normalize_config_path($source);
	return $projectLabel === '' || $projectLabel === '.' ? $source : normalize_config_path($projectLabel . '/' . $source);
}

/** @return array<string,list<string>> */
function collect_project_module_compile_surface_deps(array $projectModuleReport): array
{
	$report = normalize_project_module_report($projectModuleReport);
	if (!(bool) ($report['configured'] ?? false)) {
		return [];
	}
	$surfaceByModule = [];
	foreach (is_array($report['modules'] ?? null) ? $report['modules'] : [] as $module) {
		if (!is_array($module)) {
			continue;
		}
		$name = trim((string) ($module['name'] ?? ''));
		$projectLabel = trim((string) ($module['project_root'] ?? '.'));
		$surfaceArtifact = normalize_config_path((string) ($module['surface_artifact'] ?? ''));
		if ($name === '' || $surfaceArtifact === '') {
			continue;
		}
		$surfaceByModule[project_module_identity_key($projectLabel, $name)] = $surfaceArtifact;
	}

	$depsBySource = [];
	foreach (is_array($report['modules'] ?? null) ? $report['modules'] : [] as $module) {
		if (!is_array($module)) {
			continue;
		}
		$name = trim((string) ($module['name'] ?? ''));
		$projectLabel = trim((string) ($module['project_root'] ?? '.'));
		if ($name === '') {
			continue;
		}
		$deps = [];
		$ownSurface = $surfaceByModule[project_module_identity_key($projectLabel, $name)] ?? '';
		if ($ownSurface !== '') {
			$deps[] = $ownSurface;
		}
		foreach (normalize_string_list($module['dependencies'] ?? []) as $dependency) {
			$dependencySurface = $surfaceByModule[project_module_identity_key($projectLabel, $dependency)] ?? '';
			if ($dependencySurface !== '') {
				$deps[] = $dependencySurface;
			}
		}
		$deps = normalize_string_list($deps);
		if ($deps === []) {
			continue;
		}
		foreach (normalize_project_module_source_rows(is_array($module['sources'] ?? null) ? $module['sources'] : []) as $source) {
			$sourcePath = normalize_config_path((string) ($source['source'] ?? ''));
			if ($sourcePath === '') {
				continue;
			}
			$depsBySource[project_module_source_key($projectLabel, $sourcePath)] = $deps;
		}
	}
	ksort($depsBySource, SORT_STRING);
	return $depsBySource;
}

function project_module_identity_key(string $projectLabel, string $moduleName): string
{
	$projectLabel = trim($projectLabel);
	return ($projectLabel === '' ? '.' : $projectLabel) . "\0" . trim($moduleName);
}

function project_module_source_key(string $projectLabel, string $source): string
{
	$projectLabel = trim($projectLabel);
	return ($projectLabel === '' ? '.' : $projectLabel) . "\0" . normalize_config_path($source);
}

/** @return array<string,array<string,mixed>> */
function build_project_module_source_explanation_map(array $projectModuleReport): array
{
	$report = normalize_project_module_report($projectModuleReport);
	$compileDepsBySource = collect_project_module_compile_surface_deps($report);
	$result = [];
	foreach (is_array($report['modules'] ?? null) ? $report['modules'] : [] as $module) {
		if (!is_array($module)) {
			continue;
		}
		$moduleName = trim((string) ($module['name'] ?? ''));
		$projectLabel = trim((string) ($module['project_root'] ?? '.'));
		if ($moduleName === '') {
			continue;
		}
		foreach (normalize_project_module_source_rows(is_array($module['sources'] ?? null) ? $module['sources'] : []) as $source) {
			$sourcePath = normalize_config_path((string) ($source['source'] ?? ''));
			if ($sourcePath === '') {
				continue;
			}
			$sourceKey = project_module_source_key($projectLabel, $sourcePath);
			$result[$sourceKey] = [
				'module' => $projectLabel === '' || $projectLabel === '.' ? $moduleName : $projectLabel . '/' . $moduleName,
				'surface_artifacts' => $compileDepsBySource[$sourceKey] ?? [],
				'consumer_rebuild_required' => (bool) ($module['consumer_rebuild_required'] ?? false),
				'consumer_rebuild_reasons' => normalize_string_list($module['consumer_rebuild_reasons'] ?? []),
			];
		}
	}
	ksort($result, SORT_STRING);
	return $result;
}

/**
 * @param array<string,string> $dependencyExportHeaderPaths
 * @param list<string> $directLocalHeaderPaths
 * @return list<string>
 */
function build_project_unit_candidate_scoped_header_paths(array $dependencyExportHeaderPaths, ?string $forwardHeader, array $directLocalHeaderPaths, string $ownHeader): array
{
	$dependencyHeaders = [];
	foreach (array_values($dependencyExportHeaderPaths) as $headerPath) {
		$header = normalize_path((string) $headerPath);
		if ($header !== '') {
			$dependencyHeaders[$header] = $header;
		}
	}
	ksort($dependencyHeaders, SORT_STRING);
	$headers = array_values($dependencyHeaders);
	if ($forwardHeader !== null && $forwardHeader !== '') {
		$headers[] = normalize_path($forwardHeader);
	}
	foreach (canonicalize_project_unit_candidate_scoped_header_paths(array_merge($directLocalHeaderPaths, [$ownHeader])) as $localHeader) {
		$headers[] = $localHeader;
	}
	return array_values(array_unique($headers));
}

/** @param list<string> $candidateHeaderPaths @return list<string> */
function canonicalize_project_unit_candidate_scoped_header_paths(array $candidateHeaderPaths): array
{
	$headers = [];
	foreach ($candidateHeaderPaths as $headerPath) {
		$header = normalize_path((string) $headerPath);
		if ($header !== '') {
			$headers[$header] = $header;
		}
	}
	ksort($headers, SORT_STRING);
	return sort_project_unit_include_headers(array_values($headers));
}

/** @param list<string> $candidateHeaders */
function project_unit_candidate_scoped_pack_hash(array $candidateHeaders): string
{
	return substr(hash('sha256', implode("\n", array_merge(['v1-scoped-candidate'], $candidateHeaders))), 0, 16);
}

/**
 * @param array<string,mixed>|null $sourceSummary
 * @param list<string> $unresolvedDependencyKeys
 * @param list<array<string,mixed>> $dependencyCategories
 * @return array{status:string,blocking_reasons:list<string>}
 */
function classify_project_unit_scoped_candidate(bool $hasDependencyState, ?array $sourceSummary, array $unresolvedDependencyKeys, string $ownHeader, array $dependencyCategories = []): array
{
	$blockingReasons = [];
	if (!$hasDependencyState) {
		$blockingReasons[] = 'project unit dependency state unavailable';
	}
	if ($sourceSummary === null) {
		$blockingReasons[] = 'source summary unavailable';
	}
	if ($ownHeader === '' || !is_file($ownHeader)) {
		$blockingReasons[] = 'own generated header unavailable';
	}
	foreach ($unresolvedDependencyKeys as $dependencyKey) {
		$blockingReasons[] = 'unresolved dependency key `' . $dependencyKey . '`';
	}
	if ($sourceSummary !== null) {
		$blockingReasons = array_merge($blockingReasons, collect_project_unit_scoped_candidate_summary_blockers($sourceSummary, $dependencyCategories));
	}
	$blockingReasons = normalize_string_list($blockingReasons);
	return [
		'status' => $blockingReasons === [] ? 'candidate_scoped' : 'blocked_broad_fallback',
		'blocking_reasons' => $blockingReasons,
	];
}

/** @param array<string,mixed> $summary @param list<array<string,mixed>> $dependencyCategories @return list<string> */
function collect_project_unit_scoped_candidate_summary_blockers(array $summary, array $dependencyCategories = []): array
{
	$blockers = [];
	if (normalize_string_list($summary['build_errors'] ?? []) !== []) {
		$blockers[] = 'source summary contains build errors';
	}
	$topLevelConstants = project_unit_summary_top_level_constants($summary);
	if ($topLevelConstants !== [] && !project_unit_constant_rows_are_scoped_candidate_safe($topLevelConstants, $dependencyCategories, 'constant_value')) {
		$blockers[] = 'top-level constants contain unmodeled dependency evidence';
	}
	foreach (project_unit_summary_function_buckets($summary) as $function) {
		if ((int) ($function['statement_count'] ?? 0) > 0) {
			if ((bool) ($function['is_synthetic_entrypoint'] ?? false)) {
				$blockers[] = 'executable body present';
				break;
			}
			if (!project_unit_function_body_is_scoped_candidate_safe($function, $dependencyCategories)) {
				$blockers[] = 'function body contains unmodeled dependency evidence';
			}
			break;
		}
	}
	foreach (project_unit_summary_classes($summary) as $class) {
		if (is_array($class['constants'] ?? null) && $class['constants'] !== [] && !project_unit_class_constants_are_scoped_candidate_safe($class, $dependencyCategories)) {
			$blockers[] = 'class constants contain unmodeled dependency evidence';
		}
		foreach (is_array($class['methods'] ?? null) ? $class['methods'] : [] as $method) {
			if (is_array($method) && (int) ($method['statement_count'] ?? 0) > 0) {
				if (!project_unit_method_body_is_scoped_candidate_safe($class, $method, $dependencyCategories)) {
					$blockers[] = 'method body contains unmodeled dependency evidence';
					break 2;
				}
			}
		}
	}
	return normalize_string_list($blockers);
}

/** @param array<string,mixed> $class @param list<array<string,mixed>> $dependencyCategories */
function project_unit_class_constants_are_scoped_candidate_safe(array $class, array $dependencyCategories): bool
{
	return project_unit_constant_rows_are_scoped_candidate_safe(
		is_array($class['constants'] ?? null) ? $class['constants'] : [],
		$dependencyCategories,
		'class_constant_value'
	);
}

/** @param list<array<string,mixed>> $constants @param list<array<string,mixed>> $dependencyCategories */
function project_unit_constant_rows_are_scoped_candidate_safe(array $constants, array $dependencyCategories, string $dependencyKind): bool
{
	foreach ($constants as $constant) {
		if (!is_array($constant)) {
			return false;
		}
		$descriptor = is_array($constant['value_descriptor'] ?? null) ? $constant['value_descriptor'] : null;
		if ($descriptor === null || !project_unit_constant_descriptor_is_scoped_candidate_safe($descriptor, $dependencyCategories, $dependencyKind)) {
			return false;
		}
	}
	return true;
}

/** @param array<string,mixed> $descriptor @param list<array<string,mixed>> $dependencyCategories */
function project_unit_constant_descriptor_is_scoped_candidate_safe(array $descriptor, array $dependencyCategories, string $dependencyKind): bool
{
	$kind = trim((string) ($descriptor['kind'] ?? 'unknown'));
	if ($kind === 'type') {
		return project_unit_constant_descriptor_type_is_scalar_like((string) ($descriptor['type'] ?? ''));
	}
	if ($kind === 'class_constant') {
		return project_unit_class_constant_dependency_is_resolved($descriptor, $dependencyCategories, $dependencyKind);
	}
	if ($kind === 'arithmetic') {
		return is_array($descriptor['left'] ?? null)
			&& is_array($descriptor['right'] ?? null)
			&& project_unit_constant_descriptor_is_scoped_candidate_safe($descriptor['left'], $dependencyCategories, $dependencyKind)
			&& project_unit_constant_descriptor_is_scoped_candidate_safe($descriptor['right'], $dependencyCategories, $dependencyKind);
	}
	if ($kind === 'conditional') {
		return is_array($descriptor['if_true'] ?? null)
			&& is_array($descriptor['if_false'] ?? null)
			&& project_unit_constant_descriptor_is_scoped_candidate_safe($descriptor['if_true'], $dependencyCategories, $dependencyKind)
			&& project_unit_constant_descriptor_is_scoped_candidate_safe($descriptor['if_false'], $dependencyCategories, $dependencyKind);
	}
	if ($kind === 'string_concat') {
		return is_array($descriptor['left'] ?? null)
			&& is_array($descriptor['right'] ?? null)
			&& project_unit_constant_descriptor_is_scoped_candidate_safe($descriptor['left'], $dependencyCategories, $dependencyKind)
			&& project_unit_constant_descriptor_is_scoped_candidate_safe($descriptor['right'], $dependencyCategories, $dependencyKind);
	}
	return false;
}

function project_unit_constant_descriptor_type_is_scalar_like(string $type): bool
{
	$type = strtolower(trim($type));
	return in_array($type, ['bool', 'boolean', 'false', 'float', 'int', 'integer', 'long', 'null', 'string', 'true'], true);
}

/** @param array<string,mixed> $descriptor @param list<array<string,mixed>> $dependencyCategories */
function project_unit_class_constant_dependency_is_resolved(array $descriptor, array $dependencyCategories, string $dependencyKind): bool
{
	$className = trim((string) ($descriptor['root_class'] ?? ''), "\\ \t\n\r\0\x0B");
	if ($className === '' || in_array(strtolower($className), ['parent', 'self', 'static'], true)) {
		return false;
	}
	foreach (normalize_project_unit_dependency_category_rows($dependencyCategories) as $row) {
		if (($row['kind'] ?? '') !== $dependencyKind) {
			continue;
		}
		$target = trim((string) ($row['target'] ?? ''), "\\ \t\n\r\0\x0B");
		if ($target !== $className) {
			continue;
		}
		return (string) ($row['resolution'] ?? '') === 'resolved';
	}
	return false;
}

/** @param array<string,mixed> $function @param list<array<string,mixed>> $dependencyCategories */
function project_unit_function_body_is_scoped_candidate_safe(array $function, array $dependencyCategories): bool
{
	foreach (is_array($function['call_sites'] ?? null) ? $function['call_sites'] : [] as $callSite) {
		if (!is_array($callSite)) {
			return false;
		}
		$callKind = (string) ($callSite['call_kind'] ?? '');
		if ($callKind !== 'function' && $callKind !== 'static_method') {
			return false;
		}
	}
	$owner = project_unit_summary_function_owner($function);
	return project_unit_body_dependency_rows_are_scoped_candidate_safe($dependencyCategories, 'function_body_', $owner);
}

/** @param array<string,mixed> $class @param array<string,mixed> $method @param list<array<string,mixed>> $dependencyCategories */
function project_unit_method_body_is_scoped_candidate_safe(array $class, array $method, array $dependencyCategories): bool
{
	foreach (is_array($method['call_sites'] ?? null) ? $method['call_sites'] : [] as $callSite) {
		if (!is_array($callSite)) {
			return false;
		}
		$callKind = (string) ($callSite['call_kind'] ?? '');
		if ($callKind !== 'function' && $callKind !== 'static_method') {
			return false;
		}
	}
	return project_unit_body_dependency_rows_are_scoped_candidate_safe(
		$dependencyCategories,
		'method_body_',
		project_unit_summary_method_owner($class, $method)
	);
}

/** @param list<array<string,mixed>> $dependencyCategories */
function project_unit_body_dependency_rows_are_scoped_candidate_safe(array $dependencyCategories, string $bodyKindPrefix, string $owner): bool
{
	foreach ($dependencyCategories as $row) {
		if (!is_array($row)) {
			continue;
		}
		$kind = (string) ($row['kind'] ?? '');
		if (!str_starts_with($kind, $bodyKindPrefix)) {
			continue;
		}
		$rowOwner = trim((string) ($row['owner'] ?? ''));
		if ($rowOwner !== '' && $owner !== '' && $rowOwner !== $owner) {
			continue;
		}
		if (!project_unit_body_dependency_row_is_scoped_candidate_safe($row)) {
			return false;
		}
	}
	return true;
}

/** @param array<string,mixed> $row */
function project_unit_body_dependency_row_is_scoped_candidate_safe(array $row): bool
{
	$target = (string) ($row['target'] ?? '');
	$resolution = (string) ($row['resolution'] ?? '');
	if ($resolution === 'resolved') {
		return true;
	}
	if ($resolution === 'unresolved_dependency_key') {
		if (project_unit_unresolved_body_symbol_is_runtime_shallow($target)) {
			return true;
		}
		$dependencies = normalize_string_list($row['source_dependencies'] ?? []);
		if ($dependencies === []) {
			return false;
		}
		foreach ($dependencies as $dependencyKey) {
			if (!project_unit_dependency_key_is_runtime_shallow($dependencyKey)) {
				return false;
			}
		}
		return true;
	}
	if ($resolution === 'unresolved_symbol') {
		return project_unit_unresolved_body_symbol_is_runtime_shallow($target);
	}
	return false;
}

function project_unit_unresolved_body_symbol_is_runtime_shallow(string $target): bool
{
	$target = trim($target, "\\ \t\n\r\0\x0B");
	if ($target === '') {
		return false;
	}
	$runtimeSymbols = project_unit_runtime_shallow_symbol_names();
	if (isset($runtimeSymbols[strtolower($target)])) {
		return true;
	}
	return in_array(strtolower($target), ['error', 'error_t', 'php', 'scpp', 'std'], true);
}

/** @return array<string,true> */
function project_unit_runtime_shallow_symbol_names(): array
{
	static $symbols = null;
	if (is_array($symbols)) {
		return $symbols;
	}
	$symbols = [];
	foreach ([
		resolve_repo_root() . '/runtime/generated/stan/runtime_symbols_strict.phs',
		resolve_repo_root() . '/runtime/generated/stan/runtime_symbols_legacy.php',
	] as $path) {
		$contents = is_file($path) ? file_get_contents($path) : false;
		if (!is_string($contents) || $contents === '') {
			continue;
		}
		if (preg_match_all('/^\s*function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(/m', $contents, $matches) !== false) {
			foreach ($matches[1] as $name) {
				if (is_string($name) && $name !== '') {
					$symbols[strtolower($name)] = true;
				}
			}
		}
		foreach (project_unit_extract_runtime_shallow_signature_type_names($contents) as $name) {
			$symbols[strtolower($name)] = true;
		}
	}
	return $symbols;
}

/** @return list<string> */
function project_unit_extract_runtime_shallow_signature_type_names(string $contents): array
{
	$typeNames = [];
	if (preg_match_all('/^\s*function\s+[A-Za-z_][A-Za-z0-9_]*\s*\(([^)]*)\)\s*(?::\s*([^{]+))?\s*\{/m', $contents, $matches, PREG_SET_ORDER) === false) {
		return [];
	}
	foreach ($matches as $match) {
		$signatureTypeText = trim((string) ($match[1] ?? '')) . ' ' . trim((string) ($match[2] ?? ''));
		$signatureTypeText = preg_replace('/\$[A-Za-z_][A-Za-z0-9_]*/', ' ', $signatureTypeText) ?? $signatureTypeText;
		$signatureTypeText = preg_replace('/=[^,]*/', ' ', $signatureTypeText) ?? $signatureTypeText;
		if (preg_match_all('/\b[A-Za-z_][A-Za-z0-9_]*\b/', $signatureTypeText, $typeMatches) === false) {
			continue;
		}
		foreach ($typeMatches[0] as $name) {
			if (project_unit_runtime_shallow_signature_type_name_is_builtin($name)) {
				continue;
			}
			$typeNames[$name] = $name;
		}
	}
	$result = array_values($typeNames);
	sort($result, SORT_STRING);
	return $result;
}

function project_unit_runtime_shallow_signature_type_name_is_builtin(string $name): bool
{
	return in_array(strtolower($name), [
		'array',
		'bool',
		'byte',
		'dynamic',
		'false',
		'fixed_array',
		'float',
		'hash',
		'int',
		'int8',
		'int16',
		'int32',
		'int64',
		'mixed',
		'null',
		'nullable',
		'resource',
		'result',
		'result_or_false',
		'string',
		'true',
		'uint8',
		'uint16',
		'uint32',
		'uint64',
		'vector',
		'void',
	], true);
}

/** @param array<string,mixed> $function */
function project_unit_summary_function_owner(array $function): string
{
	$name = trim((string) ($function['name'] ?? ''));
	if ($name === '') {
		return '';
	}
	$namespace = trim((string) ($function['namespace'] ?? ''));
	return $namespace === '' ? $name : $namespace . '\\' . $name;
}

/** @param array<string,mixed> $class @param array<string,mixed> $method */
function project_unit_summary_method_owner(array $class, array $method): string
{
	$className = trim((string) ($class['name'] ?? ''));
	$methodName = trim((string) ($method['name'] ?? ''));
	if ($className === '' || $methodName === '') {
		return '';
	}
	$namespace = trim((string) ($class['namespace'] ?? ''));
	$classOwner = $namespace === '' ? $className : $namespace . '\\' . $className;
	return $classOwner . '::' . $methodName;
}

/** @param array<string,mixed> $summary @return list<array<string,mixed>> */
function project_unit_summary_top_level_constants(array $summary): array
{
	$constants = [];
	foreach (is_array($summary['root_constants'] ?? null) ? $summary['root_constants'] : [] as $constant) {
		if (is_array($constant)) {
			$constants[] = $constant;
		}
	}
	foreach (is_array($summary['namespaces'] ?? null) ? $summary['namespaces'] : [] as $namespace) {
		if (!is_array($namespace)) {
			continue;
		}
		foreach (is_array($namespace['constants'] ?? null) ? $namespace['constants'] : [] as $constant) {
			if (is_array($constant)) {
				$constants[] = $constant;
			}
		}
	}
	return $constants;
}

/** @param array<string,mixed> $summary @return list<array<string,mixed>> */
function project_unit_summary_function_buckets(array $summary): array
{
	$functions = [];
	foreach (is_array($summary['root_functions'] ?? null) ? $summary['root_functions'] : [] as $function) {
		if (is_array($function)) {
			$functions[] = $function;
		}
	}
	foreach (is_array($summary['namespaces'] ?? null) ? $summary['namespaces'] : [] as $namespace) {
		if (!is_array($namespace)) {
			continue;
		}
		foreach (is_array($namespace['functions'] ?? null) ? $namespace['functions'] : [] as $function) {
			if (is_array($function)) {
				$functions[] = $function;
			}
		}
	}
	return $functions;
}

/** @param array<string,mixed> $summary @return list<array<string,mixed>> */
function project_unit_summary_classes(array $summary): array
{
	$classes = [];
	foreach (is_array($summary['root_classes'] ?? null) ? $summary['root_classes'] : [] as $class) {
		if (is_array($class)) {
			$classes[] = $class;
		}
	}
	foreach (is_array($summary['namespaces'] ?? null) ? $summary['namespaces'] : [] as $namespace) {
		if (!is_array($namespace)) {
			continue;
		}
		foreach (is_array($namespace['classes'] ?? null) ? $namespace['classes'] : [] as $class) {
			if (is_array($class)) {
				$classes[] = $class;
			}
		}
	}
	return $classes;
}

function project_unit_stan_source_key(string $projectRoot, string $sourcePath): string
{
	$normalizedProjectRoot = normalize_path($projectRoot);
	$normalizedSourcePath = normalize_path($sourcePath);
	if (path_is_inside($normalizedProjectRoot, $normalizedSourcePath)) {
		return normalize_config_path(relative_path($normalizedProjectRoot, $normalizedSourcePath));
	}
	return '@external/' . sha1($normalizedSourcePath) . '/' . basename($normalizedSourcePath);
}

function project_unit_force_include_header_mode(string $headerPath): string
{
	$normalizedHeaderPath = normalize_path($headerPath);
	if (basename($normalizedHeaderPath) === '__project_units.hpp') {
		return 'broad';
	}
	if (basename(dirname($normalizedHeaderPath)) === '__project_units') {
		if (preg_match('/^scoped-[0-9a-f]{16}\.hpp$/', basename($normalizedHeaderPath)) === 1) {
			return 'scoped';
		}
		return 'broad_equivalent_pack';
	}
	return 'scoped';
}

/** @return array<string,mixed> */
function normalize_project_unit_force_include_report(array $report): array
{
	$headers = [];
	foreach (is_array($report['headers'] ?? null) ? $report['headers'] : [] as $header) {
		if (!is_array($header)) {
			continue;
		}
		$path = trim((string) ($header['path'] ?? ''));
		if ($path === '') {
			continue;
		}
		$headers[] = [
			'path' => $path,
			'unit_count' => max(0, (int) ($header['unit_count'] ?? 0)),
			'line_count' => max(0, (int) ($header['line_count'] ?? 0)),
			'byte_count' => max(0, (int) ($header['byte_count'] ?? 0)),
			'mode' => trim((string) ($header['mode'] ?? 'unknown')),
		];
	}
	return [
		'total_units' => max(0, (int) ($report['total_units'] ?? 0)),
		'units_with_force_include' => max(0, (int) ($report['units_with_force_include'] ?? 0)),
		'distinct_headers' => max(0, (int) ($report['distinct_headers'] ?? count($headers))),
		'active_scoped_units' => max(0, (int) ($report['active_scoped_units'] ?? 0)),
		'active_broad_fallback_units' => max(0, (int) ($report['active_broad_fallback_units'] ?? 0)),
		'candidate_scoped_units' => max(0, (int) ($report['candidate_scoped_units'] ?? 0)),
		'candidate_blocked_units' => max(0, (int) ($report['candidate_blocked_units'] ?? 0)),
		'candidate_blocker_counts' => normalize_project_unit_candidate_blocker_counts(is_array($report['candidate_blocker_counts'] ?? null) ? $report['candidate_blocker_counts'] : []),
		'native_units' => max(0, (int) ($report['native_units'] ?? 0)),
		'native_broad_fallback_units' => max(0, (int) ($report['native_broad_fallback_units'] ?? 0)),
		'native_policy' => normalize_project_unit_native_policy(is_array($report['native_policy'] ?? null) ? $report['native_policy'] : []),
		'headers' => $headers,
		'pack_changes' => normalize_project_unit_pack_changes(is_array($report['pack_changes'] ?? null) ? $report['pack_changes'] : []),
		'dependency_summary_artifact' => normalize_project_unit_dependency_summary_artifact_info(is_array($report['dependency_summary_artifact'] ?? null) ? $report['dependency_summary_artifact'] : []),
		'dependency_summaries' => normalize_project_unit_dependency_summaries(is_array($report['dependency_summaries'] ?? null) ? $report['dependency_summaries'] : []),
	];
}

/**
 * @param list<array<string,mixed>> $rows
 * @return list<array{reason:string,unit_count:int}>
 */
function normalize_project_unit_candidate_blocker_counts(array $rows): array
{
	$normalized = [];
	foreach ($rows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$reason = trim((string) ($row['reason'] ?? ''));
		if ($reason === '') {
			continue;
		}
		$normalized[] = [
			'reason' => $reason,
			'unit_count' => max(0, (int) ($row['unit_count'] ?? 0)),
		];
	}
	usort($normalized, static function (array $left, array $right): int {
		$byCount = ((int) ($right['unit_count'] ?? 0)) <=> ((int) ($left['unit_count'] ?? 0));
		return $byCount !== 0 ? $byCount : strcmp((string) ($left['reason'] ?? ''), (string) ($right['reason'] ?? ''));
	});
	return $normalized;
}

/** @return array{status:string,reason:string} */
function normalize_project_unit_native_policy(array $policy): array
{
	$status = trim((string) ($policy['status'] ?? 'not_applicable'));
	if ($status === '') {
		$status = 'not_applicable';
	}
	$reason = trim((string) ($policy['reason'] ?? ''));
	if ($reason === '') {
		$reason = $status === 'not_applicable'
			? 'no native C++ units in this build'
			: 'native C++ project-unit dependencies are not modeled; native units use broad-equivalent packs';
	}
	return [
		'status' => $status,
		'reason' => $reason,
	];
}

/** @return array{path:string,summary_signature:string,source_fingerprint:string,source_count:int,used_stan_dependency_state:bool,source_overrides_active:bool} */
function normalize_project_unit_dependency_summary_artifact_info(array $artifact): array
{
	return [
		'path' => trim((string) ($artifact['path'] ?? '')),
		'summary_signature' => trim((string) ($artifact['summary_signature'] ?? '')),
		'source_fingerprint' => trim((string) ($artifact['source_fingerprint'] ?? '')),
		'source_count' => max(0, (int) ($artifact['source_count'] ?? 0)),
		'used_stan_dependency_state' => (bool) ($artifact['used_stan_dependency_state'] ?? false),
		'source_overrides_active' => (bool) ($artifact['source_overrides_active'] ?? false),
	];
}

/** @return array<string,mixed> */
function normalize_project_unit_dependency_summary_freshness(array $freshness): array
{
	$sourceInputs = [];
	foreach (is_array($freshness['source_inputs'] ?? null) ? $freshness['source_inputs'] : [] as $input) {
		if (!is_array($input)) {
			continue;
		}
		$sourceInputs[] = [
			'project_root' => normalize_config_path((string) ($input['project_root'] ?? '')),
			'source' => normalize_config_path((string) ($input['source'] ?? '')),
			'source_key' => trim((string) ($input['source_key'] ?? '')),
			'size' => max(0, (int) ($input['size'] ?? 0)),
			'mtime' => max(0, (int) ($input['mtime'] ?? 0)),
			'content_hash' => trim((string) ($input['content_hash'] ?? '')),
			'source_override' => (bool) ($input['source_override'] ?? false),
		];
	}
	usort($sourceInputs, static fn (array $left, array $right): int => strcmp((string) ($left['source_key'] ?? ''), (string) ($right['source_key'] ?? '')));
	return [
		'summary_signature' => trim((string) ($freshness['summary_signature'] ?? '')),
		'source_fingerprint' => trim((string) ($freshness['source_fingerprint'] ?? '')),
		'source_count' => max(0, (int) ($freshness['source_count'] ?? count($sourceInputs))),
		'used_stan_dependency_state' => (bool) ($freshness['used_stan_dependency_state'] ?? false),
		'source_overrides_active' => (bool) ($freshness['source_overrides_active'] ?? false),
		'source_inputs' => $sourceInputs,
	];
}

/**
 * @param list<array<string,mixed>> $summaries
 * @return list<array<string,mixed>>
 */
function normalize_project_unit_dependency_summaries(array $summaries): array
{
	$normalized = [];
	foreach ($summaries as $summary) {
		if (!is_array($summary)) {
			continue;
		}
		$source = trim((string) ($summary['source'] ?? ''));
		if ($source === '') {
			continue;
		}
		$normalized[] = [
			'source' => $source,
			'source_key' => trim((string) ($summary['source_key'] ?? '')),
			'project_root' => trim((string) ($summary['project_root'] ?? '')),
			'generated_header' => trim((string) ($summary['generated_header'] ?? '')),
			'force_include_header' => trim((string) ($summary['force_include_header'] ?? '')),
			'status' => trim((string) ($summary['status'] ?? 'fallback_broad')),
			'candidate_status' => trim((string) ($summary['candidate_status'] ?? 'blocked_broad_fallback')),
			'candidate_scoped_headers' => normalize_string_list($summary['candidate_scoped_headers'] ?? []),
			'candidate_pack_hash' => trim((string) ($summary['candidate_pack_hash'] ?? '')),
			'candidate_pack_header' => trim((string) ($summary['candidate_pack_header'] ?? '')),
			'candidate_blocking_reasons' => normalize_string_list($summary['candidate_blocking_reasons'] ?? []),
			'direct_source_dependencies' => normalize_string_list($summary['direct_source_dependencies'] ?? []),
			'direct_local_headers' => normalize_string_list($summary['direct_local_headers'] ?? []),
			'scoped_local_headers' => normalize_string_list($summary['scoped_local_headers'] ?? []),
			'dependency_export_headers' => normalize_string_list($summary['dependency_export_headers'] ?? []),
			'unresolved_dependency_keys' => normalize_string_list($summary['unresolved_dependency_keys'] ?? []),
			'dependency_categories' => normalize_project_unit_dependency_category_rows(is_array($summary['dependency_categories'] ?? null) ? $summary['dependency_categories'] : []),
			'reasons' => normalize_string_list($summary['reasons'] ?? []),
		];
	}
	return $normalized;
}

/**
 * @param list<array<string,mixed>> $rows
 * @return list<array<string,mixed>>
 */
function normalize_project_unit_dependency_category_rows(array $rows): array
{
	$normalized = [];
	foreach ($rows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$category = trim((string) ($row['category'] ?? ''));
		if ($category === '') {
			continue;
		}
		$normalized[] = [
			'category' => $category,
			'kind' => trim((string) ($row['kind'] ?? '')),
			'target' => trim((string) ($row['target'] ?? '')),
			'owner' => trim((string) ($row['owner'] ?? '')),
			'resolution' => trim((string) ($row['resolution'] ?? '')),
			'source_dependencies' => normalize_string_list($row['source_dependencies'] ?? []),
		];
	}
	usort($normalized, static function (array $left, array $right): int {
		foreach (['category', 'resolution', 'kind', 'target', 'owner'] as $key) {
			$compare = strcmp((string) ($left[$key] ?? ''), (string) ($right[$key] ?? ''));
			if ($compare !== 0) {
				return $compare;
			}
		}
		return strcmp(implode("\n", is_array($left['source_dependencies'] ?? null) ? $left['source_dependencies'] : []), implode("\n", is_array($right['source_dependencies'] ?? null) ? $right['source_dependencies'] : []));
	});
	$deduped = [];
	foreach ($normalized as $row) {
		$key = implode('|', [
			(string) ($row['category'] ?? ''),
			(string) ($row['resolution'] ?? ''),
			(string) ($row['kind'] ?? ''),
			(string) ($row['target'] ?? ''),
			(string) ($row['owner'] ?? ''),
			implode(',', is_array($row['source_dependencies'] ?? null) ? $row['source_dependencies'] : []),
		]);
		$deduped[$key] = $row;
	}
	return array_values($deduped);
}

/**
 * @param list<array<string,mixed>> $rows
 * @return list<string>
 */
function render_project_unit_dependency_category_parts(array $rows): array
{
	$bucketed = [];
	foreach (normalize_project_unit_dependency_category_rows($rows) as $row) {
		$category = (string) ($row['category'] ?? '');
		if ($category === '') {
			continue;
		}
		$resolution = (string) ($row['resolution'] ?? '');
		if ($resolution === 'unresolved_symbol') {
			$category = 'unresolved symbol';
		} elseif ($resolution === 'ambiguous_symbol') {
			$category = 'ambiguous symbol';
		} elseif ($resolution === 'unresolved_dependency_key') {
			$category = 'unresolved dependency key';
		}
		$values = is_array($row['source_dependencies'] ?? null) ? $row['source_dependencies'] : [];
		if ($values === []) {
			$target = trim((string) ($row['target'] ?? ''));
			$values = [$target !== '' ? $target : $category];
		}
		foreach ($values as $value) {
			$value = trim((string) $value);
			if ($value !== '') {
				$bucketed[$category][$value] = true;
			}
		}
	}
	$parts = [];
	ksort($bucketed, SORT_STRING);
	foreach ($bucketed as $category => $values) {
		$valueList = array_keys($values);
		sort($valueList, SORT_STRING);
		$parts[] = count($valueList) === 1 && $valueList[0] === $category
			? $category
			: $category . ': ' . implode(', ', $valueList);
	}
	return $parts;
}

/** @return list<string> */
function normalize_string_list(mixed $values): array
{
	$strings = [];
	foreach (is_array($values) ? $values : [] as $value) {
		$string = trim((string) $value);
		if ($string !== '') {
			$strings[] = $string;
		}
	}
	sort($strings, SORT_STRING);
	return array_values(array_unique($strings));
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
		$forwardHeader = normalize_path($projectContext['generated_dir'] . '/__project_fwd.hpp');
		$legacyHeaderPath = normalize_path($projectContext['generated_dir'] . '/__project_units.hpp');
		write_text_file($legacyHeaderPath, render_project_unit_force_include_header($legacyHeaderPath, $forwardHeader, $includeHeaders));

		$packDir = normalize_path($projectContext['generated_dir'] . '/__project_units');
		ensure_directory($packDir);
		$packHash = project_unit_force_include_pack_hash($projectContext['generated_dir'], $forwardHeader, $includeHeaders);
		$packHeaderPath = normalize_path($packDir . '/' . $packHash . '.hpp');
		write_text_file($packHeaderPath, render_project_unit_force_include_header($packHeaderPath, $forwardHeader, $includeHeaders));
		write_text_file(normalize_path($packDir . '/broad.hpp'), render_project_unit_force_include_header(normalize_path($packDir . '/broad.hpp'), $forwardHeader, $includeHeaders));
		$headers[normalize_path($projectRoot)] = $packHeaderPath;
	}
	return $headers;
}

/** @param list<string> $includeHeaders */
function render_project_unit_force_include_header(string $headerPath, string $forwardHeader, array $includeHeaders): string
{
	$lines = ['#pragma once', ''];
	if (is_file($forwardHeader)) {
		$lines[] = '#include "' . normalize_config_path(relative_path(dirname($headerPath), $forwardHeader)) . '"';
	}
	foreach ($includeHeaders as $includeHeader) {
		$lines[] = '#include "' . normalize_config_path(relative_path(dirname($headerPath), $includeHeader)) . '"';
	}
	$lines[] = '';
	return implode(PHP_EOL, $lines) . PHP_EOL;
}

/** @param list<string> $includeHeaders */
function project_unit_force_include_pack_hash(string $generatedDir, string $forwardHeader, array $includeHeaders): string
{
	$parts = ['v1-broad-equivalent'];
	if (is_file($forwardHeader)) {
		$parts[] = normalize_config_path(relative_path($generatedDir, $forwardHeader));
	}
	foreach ($includeHeaders as $includeHeader) {
		$parts[] = normalize_config_path(relative_path($generatedDir, $includeHeader));
	}
	return substr(hash('sha256', implode("\n", $parts)), 0, 16);
}

/** @param list<string> $headerPaths */
function write_project_forward_declaration_header(string $generatedDir, array $headerPaths): void
{
	$declarations = collect_project_header_declarations($headerPaths);
	$headerPath = normalize_path($generatedDir . '/__project_fwd.hpp');
	$lines = ['#pragma once', ''];
	foreach ($declarations as $namespace => $decls) {
		if ($decls === []) {
			continue;
		}
		$lines[] = 'namespace ' . $namespace . ' {';
		foreach ($decls as $decl) {
			if (($decl['kind'] ?? 'class') === 'enum') {
				continue;
			}
			$prefix = match ($decl['kind'] ?? 'class') {
				'struct' => 'struct ',
				'union' => 'union ',
				default => 'class ',
			};
			$lines[] = $prefix . $decl['name'] . ';';
		}
		$lines[] = '}';
		$lines[] = '';
	}
	write_text_file($headerPath, implode(PHP_EOL, $lines) . PHP_EOL);
}

/**
 * @param list<string> $headerPaths
 * @return array<string,list<array{name:string,kind:string}>>
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
			$declarations[$namespace][$name] = [
				'name' => $name,
				'kind' => $class['kind'] ?? 'class',
			];
		}
	}
	ksort($declarations, SORT_STRING);
	foreach ($declarations as &$decls) {
		ksort($decls, SORT_STRING);
		$decls = array_values($decls);
	}
	unset($decls);
	return $declarations;
}

/**
 * @param list<string> $includeHeaders
 * @return list<string>
 */
function sort_project_unit_include_headers(array $includeHeaders): array
{
	$knownClasses = [];
	$knownNames = [];
	$headerClasses = [];
	foreach ($includeHeaders as $headerPath) {
		if (!is_file($headerPath)) {
			continue;
		}
		$metadata = read_project_header_class_metadata($headerPath);
		foreach ($metadata['classes'] as $class) {
			$key = $class['namespace'] . '::' . $class['name'];
			$knownClasses[$key] = $headerPath;
			$knownNames[$class['name']][$headerPath] = $headerPath;
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
		$contents = @file_get_contents($headerPath);
		if (!is_string($contents)) {
			continue;
		}
		$contentsWithoutIncludes = preg_replace('/^\s*#\s*include\b.*$/m', '', $contents);
		if (is_string($contentsWithoutIncludes)) {
			$contents = $contentsWithoutIncludes;
		}
		foreach ($knownNames as $name => $declaringHeaders) {
			if (isset($declaringHeaders[$headerPath])) {
				continue;
			}
			if (preg_match('/\b' . preg_quote((string) $name, '/') . '\b/', $contents) !== 1) {
				continue;
			}
			foreach ($declaringHeaders as $dependencyHeader) {
				if ($dependencyHeader !== $headerPath) {
					$dependencies[$headerPath][$dependencyHeader] = $dependencyHeader;
				}
			}
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

/** @return array{classes:list<array{namespace:string,name:string,parent:?string,kind:string}>} */
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
	if (preg_match_all('/^(class|struct|union)\s+([A-Za-z_][A-Za-z0-9_]*)(?:\s*:\s*public\s+([A-Za-z_][A-Za-z0-9_:]*))?\s*([;{])/m', $contents, $matches, PREG_SET_ORDER) !== false) {
		foreach ($matches as $match) {
			if (($match[4] ?? '') !== '{') {
				continue;
			}
			$kind = match ($match[1]) {
				'struct' => 'struct',
				'union' => 'union',
				default => 'class',
			};
			$classes[] = [
				'namespace' => $namespace,
				'name' => $match[2],
				'parent' => isset($match[3]) && $match[3] !== '' ? $match[3] : null,
				'kind' => $kind,
			];
		}
	}
	if (preg_match_all('/^enum\s+class\s+([A-Za-z_][A-Za-z0-9_]*)\s*:/m', $contents, $matches, PREG_SET_ORDER) !== false) {
		foreach ($matches as $match) {
			$classes[] = [
				'namespace' => $namespace,
				'name' => $match[1],
				'parent' => null,
				'kind' => 'enum',
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

/**
 * @param list<array{
 *   project_root:string,
 *   config_path:string,
 *   config:array<string,mixed>,
 *   dependency_roots:list<string>
 * }> $projectGraph
 * @return list<array{
 *   project_root:string,
 *   config_path:string,
 *   config:array<string,mixed>,
 *   dependency_roots:list<string>
 * }>
 */
function apply_build_profile_to_project_graph(array $projectGraph, string $buildMode, bool $modeExplicit): array
{
	foreach ($projectGraph as &$projectSpec) {
		if (!is_array($projectSpec['config'] ?? null)) {
			continue;
		}
		$projectSpec['config'] = apply_build_profile_to_config($projectSpec['config'], $buildMode, $modeExplicit);
	}
	unset($projectSpec);
	return $projectGraph;
}

/** @param array<string,mixed> $config @return array<string,mixed> */
function apply_build_profile_to_config(array $config, string $buildMode, bool $modeExplicit): array
{
	$profiles = is_array($config['profiles'] ?? null) ? $config['profiles'] : [];
	$profile = is_array($profiles[$buildMode] ?? null) ? $profiles[$buildMode] : null;
	if ($profile !== null) {
		foreach (['build_dir', 'generated_dir', 'cache_dir', 'native_cpp_dir'] as $key) {
			if (is_string($profile[$key] ?? null) && trim((string) $profile[$key]) !== '') {
				$config[$key] = (string) $profile[$key];
			}
		}
		if (is_array($profile['build'] ?? null)) {
			$baseBuild = is_array($config['build'] ?? null) ? $config['build'] : [];
			$config['build'] = array_merge($baseBuild, $profile['build']);
		}
	}

	if ($modeExplicit && $profile === null) {
		$config['build_dir'] = '.prism/build/' . $buildMode;
		$config['generated_dir'] = '.prism/generated/' . $buildMode;
		$config['cache_dir'] = '.prism/cache/' . $buildMode;
	}

	$build = is_array($config['build'] ?? null) ? $config['build'] : [];
	$build['mode'] = $buildMode;
	$config['build'] = $build;
	return $config;
}

/** @param array<string,mixed> $config @return list<string> */
function collect_project_clean_dirs(string $projectRoot, array $config): array
{
	$dirs = [];
	foreach (['build_dir', 'generated_dir', 'cache_dir'] as $key) {
		$dirs[] = normalize_path($projectRoot . '/' . normalize_config_path((string) ($config[$key] ?? default_project_state_dir($key))));
	}

	$profiles = is_array($config['profiles'] ?? null) ? $config['profiles'] : [];
	foreach ($profiles as $profile) {
		if (!is_array($profile)) {
			continue;
		}
		foreach (['build_dir', 'generated_dir', 'cache_dir'] as $key) {
			if (is_string($profile[$key] ?? null) && trim((string) $profile[$key]) !== '') {
				$dirs[] = normalize_path($projectRoot . '/' . normalize_config_path((string) $profile[$key]));
			}
		}
	}

	$unique = [];
	foreach ($dirs as $dir) {
		$unique[normalize_path($dir)] = normalize_path($dir);
	}
	return array_values($unique);
}

function default_project_state_dir(string $key): string
{
	if ($key === 'generated_dir') {
		return '.prism/generated';
	}
	if ($key === 'cache_dir') {
		return '.prism/cache';
	}
	return '.prism/build';
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
	return scpp_env_truthy($value);
}

function build_ninja_explain_requested(): bool
{
	return build_ninja_explain_raw_requested() || build_ninja_explain_probe_requested();
}

function build_ninja_explain_raw_requested(): bool
{
	$value = getenv('SCPP_NINJA_EXPLAIN');
	return scpp_env_truthy($value);
}

function build_ninja_explain_probe_requested(): bool
{
	$value = getenv('SCPP_NINJA_EXPLAIN_PROBE');
	return scpp_env_truthy($value);
}

function build_ninja_explain_source_label(): string
{
	if (build_ninja_explain_raw_requested()) {
		return 'SCPP_NINJA_EXPLAIN';
	}
	if (build_ninja_explain_probe_requested()) {
		return 'SCPP_NINJA_EXPLAIN_PROBE';
	}
	return '';
}

function scpp_env_truthy(mixed $value): bool
{
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
 *   modules:list<string>,
 *   implicit_modules:array<string,string>
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
	$implicitModules = [];
	if (in_array('webview', $modules, true) && !in_array('ui', $modules, true)) {
		$modules[] = 'ui';
		$implicitModules['ui'] = 'webview';
	}
	$safety = is_array($runtime['safety'] ?? null) ? $runtime['safety'] : [];
	if (array_key_exists('tasks', $runtime) && !is_array($runtime['tasks'])) {
		scpp_fail('Invalid runtime.tasks config in ' . SCPP_PROJECT_CONFIG . '; expected an object.' . PHP_EOL, 2);
	}
	$tasks = is_array($runtime['tasks'] ?? null) ? $runtime['tasks'] : [];
	$tasksDefaultWorkerPoolSize = normalize_runtime_tasks_default_worker_pool_size($tasks['default_worker_pool_size'] ?? null);
	$allowedLanguages = ['php'];
	$allowedModules = ['json', 'filesystem', 'datetime', 'mysqli', 'regex', 'curl', 'tasks', 'ui', 'webview'];
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
	if ($tasksDefaultWorkerPoolSize > 0 && !in_array('tasks', $modules, true)) {
		scpp_fail('runtime.tasks.default_worker_pool_size requires `tasks` in runtime.modules.' . PHP_EOL, 2);
	}
	return [
		'languages' => $languages,
		'language_profiles' => $languageProfiles,
		'modules' => $modules,
		'implicit_modules' => $implicitModules,
		'safety' => $safety,
		'tasks' => [
			'default_worker_pool_size' => $tasksDefaultWorkerPoolSize,
		],
	];
}

function normalize_runtime_tasks_default_worker_pool_size(mixed $value): int
{
	if ($value === null) {
		return 0;
	}
	if (is_int($value)) {
		$workers = $value;
	} elseif (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
		$workers = (int) $value;
	} else {
		scpp_fail('Invalid runtime.tasks.default_worker_pool_size in ' . SCPP_PROJECT_CONFIG . '; expected an integer.' . PHP_EOL, 2);
	}
	if ($workers <= 0) {
		return 0;
	}
	if ($workers > 1024) {
		scpp_fail('Invalid runtime.tasks.default_worker_pool_size in ' . SCPP_PROJECT_CONFIG . '; expected a value between 0 and 1024.' . PHP_EOL, 2);
	}
	return $workers;
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

function write_text_file_with_status(string $path, string $contents): string
{
	if (is_file($path)) {
		$existing = file_get_contents($path);
		if (is_string($existing) && $existing === $contents) {
			return 'preserved';
		}
	}
	write_text_file($path, $contents);
	return 'written';
}

function existing_file_sha256(string $path): ?string
{
	if (!is_file($path)) {
		return null;
	}
	$hash = hash_file('sha256', $path);
	return is_string($hash) ? $hash : null;
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
 * @return array{interface_hash:string,implementation_hash:string,interface_changed:bool,implementation_changed:bool,interface_first_recorded:bool,implementation_first_recorded:bool,change_reason:string}
 */
function summarize_generated_artifact_hash_changes(?array $previous, string $interfaceHash, string $implementationHash): array
{
	$previousInterfaceHash = is_array($previous) ? trim((string) ($previous['generated_interface_hash'] ?? '')) : '';
	$previousImplementationHash = is_array($previous) ? trim((string) ($previous['generated_implementation_hash'] ?? '')) : '';
	$interfaceFirstRecorded = $previousInterfaceHash === '';
	$implementationFirstRecorded = $previousImplementationHash === '';
	$interfaceChanged = $previousInterfaceHash === '' || $previousInterfaceHash !== $interfaceHash;
	$implementationChanged = $previousImplementationHash === '' || $previousImplementationHash !== $implementationHash;
	$reason = 'generated artifacts unchanged';
	if ($interfaceChanged && $implementationChanged) {
		$reason = $previousInterfaceHash === '' && $previousImplementationHash === ''
			? 'generated artifact hashes first recorded'
			: 'interface and implementation changed';
	} elseif ($interfaceChanged) {
		$reason = 'interface changed';
	} elseif ($implementationChanged) {
		$reason = 'implementation changed';
	}
	return [
		'interface_hash' => $interfaceHash,
		'implementation_hash' => $implementationHash,
		'interface_changed' => $interfaceChanged,
		'implementation_changed' => $implementationChanged,
		'interface_first_recorded' => $interfaceFirstRecorded,
		'implementation_first_recorded' => $implementationFirstRecorded,
		'change_reason' => $reason,
	];
}

/** @param array<string,mixed> $changes @return array<string,mixed> */
function with_generated_artifact_write_statuses(array $changes, string $headerWriteStatus, string $sourceWriteStatus): array
{
	$changes['header_write_status'] = normalize_generated_artifact_write_status($headerWriteStatus);
	$changes['source_write_status'] = normalize_generated_artifact_write_status($sourceWriteStatus);
	return $changes;
}

function normalize_generated_artifact_write_status(mixed $status): string
{
	return is_string($status) && trim($status) === 'written' ? 'written' : 'preserved';
}

/** @param list<array<string,mixed>> $sources @return array<string,mixed> */
function summarize_generated_artifact_write_report(array $sources): array
{
	$headers = [
		'total_count' => 0,
		'written_count' => 0,
		'preserved_count' => 0,
		'first_recorded_count' => 0,
		'interface_changed_count' => 0,
	];
	$generatedSources = [
		'total_count' => 0,
		'written_count' => 0,
		'preserved_count' => 0,
		'first_recorded_count' => 0,
		'implementation_changed_count' => 0,
	];

	foreach ($sources as $source) {
		if (!is_array($source)) {
			continue;
		}
		$artifacts = is_array($source['generated_artifacts'] ?? null) ? $source['generated_artifacts'] : [];
		$action = trim((string) ($source['action'] ?? ''));
		$defaultStatus = $action === 'transpiled' ? 'written' : 'preserved';

		$headerStatus = normalize_generated_artifact_write_status($artifacts['header_write_status'] ?? $defaultStatus);
		$headers['total_count']++;
		if ($headerStatus === 'written') {
			$headers['written_count']++;
		} else {
			$headers['preserved_count']++;
		}
		$interfaceFirstRecorded = (bool) ($artifacts['interface_first_recorded'] ?? false);
		if ($interfaceFirstRecorded) {
			$headers['first_recorded_count']++;
		}
		if ((bool) ($artifacts['interface_changed'] ?? false) && !$interfaceFirstRecorded) {
			$headers['interface_changed_count']++;
		}

		$sourceStatus = normalize_generated_artifact_write_status($artifacts['source_write_status'] ?? $defaultStatus);
		$generatedSources['total_count']++;
		if ($sourceStatus === 'written') {
			$generatedSources['written_count']++;
		} else {
			$generatedSources['preserved_count']++;
		}
		$implementationFirstRecorded = (bool) ($artifacts['implementation_first_recorded'] ?? false);
		if ($implementationFirstRecorded) {
			$generatedSources['first_recorded_count']++;
		}
		if ((bool) ($artifacts['implementation_changed'] ?? false) && !$implementationFirstRecorded) {
			$generatedSources['implementation_changed_count']++;
		}
	}

	return normalize_generated_artifact_write_report([
		'headers' => $headers,
		'sources' => $generatedSources,
	]);
}

/** @return array<string,mixed> */
function normalize_generated_artifact_write_report(array $report): array
{
	$headersRaw = is_array($report['headers'] ?? null) ? $report['headers'] : [];
	$sourcesRaw = is_array($report['sources'] ?? null) ? $report['sources'] : [];
	$headers = [
		'total_count' => max(0, (int) ($headersRaw['total_count'] ?? 0)),
		'written_count' => max(0, (int) ($headersRaw['written_count'] ?? 0)),
		'preserved_count' => max(0, (int) ($headersRaw['preserved_count'] ?? 0)),
		'first_recorded_count' => max(0, (int) ($headersRaw['first_recorded_count'] ?? 0)),
		'interface_changed_count' => max(0, (int) ($headersRaw['interface_changed_count'] ?? 0)),
	];
	$generatedSources = [
		'total_count' => max(0, (int) ($sourcesRaw['total_count'] ?? 0)),
		'written_count' => max(0, (int) ($sourcesRaw['written_count'] ?? 0)),
		'preserved_count' => max(0, (int) ($sourcesRaw['preserved_count'] ?? 0)),
		'first_recorded_count' => max(0, (int) ($sourcesRaw['first_recorded_count'] ?? 0)),
		'implementation_changed_count' => max(0, (int) ($sourcesRaw['implementation_changed_count'] ?? 0)),
	];
	$totalRaw = is_array($report['total'] ?? null) ? $report['total'] : [];
	return [
		'headers' => $headers,
		'sources' => $generatedSources,
		'total' => [
			'artifact_count' => max(0, (int) ($totalRaw['artifact_count'] ?? ($headers['total_count'] + $generatedSources['total_count']))),
			'written_count' => max(0, (int) ($totalRaw['written_count'] ?? ($headers['written_count'] + $generatedSources['written_count']))),
			'preserved_count' => max(0, (int) ($totalRaw['preserved_count'] ?? ($headers['preserved_count'] + $generatedSources['preserved_count']))),
			'first_recorded_count' => max(0, (int) ($totalRaw['first_recorded_count'] ?? ($headers['first_recorded_count'] + $generatedSources['first_recorded_count']))),
			'changed_count' => max(0, (int) ($totalRaw['changed_count'] ?? ($headers['interface_changed_count'] + $generatedSources['implementation_changed_count']))),
		],
	];
}

/**
 * @param array{compile_runtime?:bool,compile_dependencies?:bool,force_runtime_rebuild?:bool,entry_override?:?string} $options
 * @param list<array<string,mixed>> $sourceRebuildReasons
 * @param list<string> $rebuiltOutputs
 * @param array<string,mixed> $rebuildFanout
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
	array $runtimeConfig = [],
	array $projectUnitForceIncludeReport = [],
	array $rebuildFanout = [],
	?string $buildMode = null,
	array $rootContext = [],
	array $buildGroupingReport = [],
	array $projectModuleReport = [],
	array $ninjaExplainReport = [],
	array $objectActionIdentityReport = [],
	array $objectCacheReport = [],
	array $buildPlannerReport = [],
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
	$projectUnitForceIncludeReport = normalize_project_unit_force_include_report($projectUnitForceIncludeReport);
	$normalizedRebuildFanout = normalize_build_rebuild_fanout($rebuildFanout);
	$projectModuleReport = normalize_project_module_report($projectModuleReport);
	$ninjaExplainReport = normalize_ninja_explain_report($ninjaExplainReport);
	$objectActionIdentityReport = normalize_object_action_identity_report($objectActionIdentityReport);
	$objectCacheReport = normalize_object_cache_report($objectCacheReport);
	$buildPlannerReport = normalize_build_planner_report($buildPlannerReport);
	$sources = annotate_build_explanation_sources_with_project_units($projectRoot, $sourceRebuildReasons, $projectUnitForceIncludeReport, $normalizedRebuildFanout, $exitCode === 0, $options, $projectModuleReport, $ninjaExplainReport, $objectActionIdentityReport);

	return [
		'status' => $exitCode === 0 ? 'success' : 'failure',
		'build_mode' => $buildMode !== null ? normalize_build_mode_name($buildMode, 'build explanation mode') : null,
		'build_roots' => [
			'build_dir' => is_string($rootContext['build_dir'] ?? null) ? normalize_config_path(relative_path($projectRoot, (string) $rootContext['build_dir'])) : null,
			'generated_dir' => is_string($rootContext['generated_dir'] ?? null) ? normalize_config_path(relative_path($projectRoot, (string) $rootContext['generated_dir'])) : null,
			'cache_dir' => is_string($rootContext['cache_dir'] ?? null) ? normalize_config_path(relative_path($projectRoot, (string) $rootContext['cache_dir'])) : null,
		],
		'build_grouping' => normalize_build_grouping_report($buildGroupingReport),
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
		'runtime_modules' => build_runtime_module_explanation($runtimeConfig),
		'sources' => $sources,
		'generated_artifact_writes' => summarize_generated_artifact_write_report($sources),
		'build_planner' => $buildPlannerReport,
		'ninja_explain' => $ninjaExplainReport,
		'object_action_identity' => $objectActionIdentityReport,
		'object_cache' => $objectCacheReport,
		'rebuilt_outputs' => $rebuilt,
		'rebuild_fanout' => $normalizedRebuildFanout,
		'project_unit_force_includes' => $projectUnitForceIncludeReport,
		'project_modules' => $projectModuleReport,
	];
}

/**
 * @param list<array<string,mixed>> $sources
 * @param array<string,mixed> $projectUnitForceIncludeReport
 * @return list<array<string,mixed>>
 */
function annotate_build_explanation_sources_with_project_units(string $projectRoot, array $sources, array $projectUnitForceIncludeReport, array $rebuildFanout = [], bool $buildSucceeded = true, array $options = [], array $projectModuleReport = [], array $ninjaExplainReport = [], array $objectActionIdentityReport = []): array
{
	$normalizedProjectRoot = normalize_path($projectRoot);
	$rebuiltGeneratedObjects = [];
	foreach (normalize_string_list($rebuildFanout['rebuilt_generated_objects'] ?? []) as $objectPath) {
		$rebuiltGeneratedObjects[$objectPath] = true;
	}
	$ninjaExplainReport = normalize_ninja_explain_report($ninjaExplainReport);
	$ninjaObjectExplanations = is_array($ninjaExplainReport['object_explanations'] ?? null) ? $ninjaExplainReport['object_explanations'] : [];
	$objectActionByObject = [];
	foreach (normalize_object_action_identity_rows(is_array($objectActionIdentityReport['actions'] ?? null) ? $objectActionIdentityReport['actions'] : []) as $actionRow) {
		$objectPath = normalize_config_path((string) ($actionRow['object_path'] ?? ''));
		if ($objectPath !== '') {
			$objectActionByObject[$objectPath] = $actionRow;
		}
	}
	$changedPackHeaders = [];
	$packChanges = is_array($projectUnitForceIncludeReport['pack_changes'] ?? null) ? $projectUnitForceIncludeReport['pack_changes'] : [];
	foreach (normalize_string_list($packChanges['changed_headers'] ?? []) as $headerPath) {
		$changedPackHeaders[$headerPath] = true;
	}
	$headerModes = [];
	foreach (is_array($projectUnitForceIncludeReport['headers'] ?? null) ? $projectUnitForceIncludeReport['headers'] : [] as $header) {
		if (!is_array($header)) {
			continue;
		}
		$path = trim((string) ($header['path'] ?? ''));
		if ($path === '') {
			continue;
		}
		$headerModes[$path] = trim((string) ($header['mode'] ?? 'unknown'));
	}

	$summaryBySource = [];
	foreach (is_array($projectUnitForceIncludeReport['dependency_summaries'] ?? null) ? $projectUnitForceIncludeReport['dependency_summaries'] : [] as $summary) {
		if (!is_array($summary)) {
			continue;
		}
		$source = normalize_config_path((string) ($summary['source'] ?? ''));
		if ($source === '') {
			continue;
		}
		$projectRootKey = normalize_config_path((string) ($summary['project_root'] ?? ''));
		$summaryBySource[$projectRootKey . "\0" . $source] = $summary;
	}
	$projectModuleSourceInfo = build_project_module_source_explanation_map($projectModuleReport);

	$annotated = [];
	foreach ($sources as $source) {
		if (!is_array($source)) {
			continue;
		}
		$sourceProjectRoot = is_string($source['project_root'] ?? null) ? normalize_path($source['project_root']) : $normalizedProjectRoot;
		$projectRootKey = normalize_config_path(relative_path($normalizedProjectRoot, $sourceProjectRoot));
		$sourcePath = normalize_config_path((string) ($source['path'] ?? ''));
		$summary = $summaryBySource[$projectRootKey . "\0" . $sourcePath] ?? null;
		if (is_array($summary)) {
			$forceIncludeHeader = trim((string) ($summary['force_include_header'] ?? ''));
			$source['project_unit_status'] = trim((string) ($summary['status'] ?? 'fallback_broad'));
			$source['project_unit_force_include_header'] = $forceIncludeHeader;
			$source['project_unit_force_include_mode'] = $headerModes[$forceIncludeHeader] ?? ($forceIncludeHeader !== '' ? project_unit_force_include_header_mode(normalize_path($normalizedProjectRoot . '/' . $forceIncludeHeader)) : '');
		}
		$moduleSource = $projectModuleSourceInfo[$projectRootKey . "\0" . $sourcePath] ?? null;
		if (is_array($moduleSource)) {
			$source['project_module'] = (string) ($moduleSource['module'] ?? '');
			$source['project_module_surface_artifacts'] = normalize_string_list($moduleSource['surface_artifacts'] ?? []);
			$source['project_module_consumer_rebuild_required'] = (bool) ($moduleSource['consumer_rebuild_required'] ?? false);
			$source['project_module_consumer_rebuild_reasons'] = normalize_string_list($moduleSource['consumer_rebuild_reasons'] ?? []);
		}
		$objectPath = normalize_config_path((string) ($source['object_path'] ?? ''));
		$source['object_rebuild_ninja_explain'] = normalize_string_list($ninjaObjectExplanations[$objectPath] ?? []);
		$objectAction = $objectActionByObject[$objectPath] ?? null;
		if (is_array($objectAction)) {
			$source['object_action_key'] = (string) ($objectAction['action_key'] ?? '');
			$source['object_action_kind'] = (string) ($objectAction['kind'] ?? '');
		}
		if (!$buildSucceeded) {
			$source['object_rebuilt'] = null;
			$source['object_rebuild_reason'] = 'object rebuild status unavailable because Ninja failed before final output mtimes were captured';
		} else {
			$source['object_rebuilt'] = $objectPath !== '' && isset($rebuiltGeneratedObjects[$objectPath]);
			$source['object_rebuild_reason'] = explain_source_object_rebuild_reason($source, $changedPackHeaders, $options);
		}
		$annotated[] = $source;
	}
	return $annotated;
}

/** @param array<string,mixed> $source @param array<string,bool> $changedPackHeaders @param array<string,mixed> $options */
function explain_source_object_rebuild_reason(array $source, array $changedPackHeaders, array $options): string
{
	if (($source['object_rebuilt'] ?? null) !== true) {
		if (($source['action'] ?? '') === 'transpiled') {
			return 'generated object remained up-to-date after content-aware writes';
		}
		return 'source and generated object reused';
	}
	$artifacts = is_array($source['generated_artifacts'] ?? null) ? $source['generated_artifacts'] : [];
	$changeReason = trim((string) ($artifacts['change_reason'] ?? ''));
	if ($changeReason !== '' && $changeReason !== 'generated artifacts unchanged' && $changeReason !== 'generated artifact hashes first recorded') {
		return $changeReason;
	}
	$moduleReasons = normalize_string_list($source['project_module_consumer_rebuild_reasons'] ?? []);
	if ($moduleReasons !== []) {
		return implode('; ', $moduleReasons);
	}
	$forceIncludeHeader = normalize_config_path((string) ($source['project_unit_force_include_header'] ?? ''));
	if ($forceIncludeHeader !== '' && isset($changedPackHeaders[$forceIncludeHeader])) {
		return 'project-unit pack changed';
	}
	if ((bool) ($options['compile_runtime'] ?? false)) {
		return 'runtime rebuild requested for this build';
	}
	if ((bool) ($options['compile_dependencies'] ?? false)) {
		return 'dependency rebuild requested for this build';
	}
	$ninjaExplain = normalize_string_list($source['object_rebuild_ninja_explain'] ?? []);
	if ($ninjaExplain !== []) {
		return 'Ninja explain: ' . $ninjaExplain[0];
	}
	return 'Ninja rebuilt the object; no source/interface/project-unit cause was recorded';
}

/**
 * @param array<string,mixed> $runtimeConfig
 * @return array{modules:list<array{name:string,implicit_reason:?string}>,webview:?array{backend:string,enabled:bool,diagnostics:list<string>}}
 */
function build_runtime_module_explanation(array $runtimeConfig): array
{
	$modules = [];
	foreach ((array) ($runtimeConfig['modules'] ?? []) as $module) {
		if (is_string($module) && trim($module) !== '') {
			$modules[] = strtolower(trim($module));
		}
	}
	$modules = array_values(array_unique($modules));

	$implicitModules = [];
	if (is_array($runtimeConfig['implicit_modules'] ?? null)) {
		foreach ($runtimeConfig['implicit_modules'] as $module => $reason) {
			if (is_string($module) && is_string($reason) && trim($module) !== '' && trim($reason) !== '') {
				$implicitModules[strtolower(trim($module))] = strtolower(trim($reason));
			}
		}
	}

	$moduleRows = [];
	foreach ($modules as $module) {
		$moduleRows[] = [
			'name' => $module,
			'implicit_reason' => $implicitModules[$module] ?? null,
		];
	}

	$webview = null;
	if (in_array('webview', $modules, true)) {
		$webviewSpec = resolve_runtime_webview_build_spec();
		$webview = [
			'backend' => (string) ($webviewSpec['backend'] ?? 'unknown'),
			'enabled' => (bool) ($webviewSpec['enabled'] ?? false),
			'diagnostics' => array_values(array_filter(
				array_map(static fn ($value): string => trim((string) $value), is_array($webviewSpec['diagnostics'] ?? null) ? $webviewSpec['diagnostics'] : []),
				static fn (string $value): bool => $value !== ''
			)),
		];
	}

	return [
		'modules' => $moduleRows,
		'webview' => $webview,
	];
}

/**
 * @param array<string,mixed> $report
 * @return list<string>
 */
function render_generated_artifact_write_report_lines(array $report): array
{
	$report = normalize_generated_artifact_write_report($report);
	$total = is_array($report['total'] ?? null) ? $report['total'] : [];
	if ((int) ($total['artifact_count'] ?? 0) === 0) {
		return ['Generated artifact writes: none'];
	}
	$headers = is_array($report['headers'] ?? null) ? $report['headers'] : [];
	$sources = is_array($report['sources'] ?? null) ? $report['sources'] : [];
	return [
		'Generated artifact writes: artifacts ' . (int) ($total['artifact_count'] ?? 0)
			. ' total, ' . (int) ($total['written_count'] ?? 0) . ' written, '
			. (int) ($total['preserved_count'] ?? 0) . ' preserved, '
			. (int) ($total['first_recorded_count'] ?? 0) . ' first-recorded, '
			. (int) ($total['changed_count'] ?? 0) . ' changed',
		'Generated headers: ' . (int) ($headers['total_count'] ?? 0)
			. ' total, ' . (int) ($headers['written_count'] ?? 0) . ' written, '
			. (int) ($headers['preserved_count'] ?? 0) . ' preserved, '
			. (int) ($headers['first_recorded_count'] ?? 0) . ' first-recorded, '
			. (int) ($headers['interface_changed_count'] ?? 0) . ' interface-changed',
		'Generated sources: ' . (int) ($sources['total_count'] ?? 0)
			. ' total, ' . (int) ($sources['written_count'] ?? 0) . ' written, '
			. (int) ($sources['preserved_count'] ?? 0) . ' preserved, '
			. (int) ($sources['first_recorded_count'] ?? 0) . ' first-recorded, '
			. (int) ($sources['implementation_changed_count'] ?? 0) . ' implementation-changed',
	];
}

/**
 * @param array<string,mixed> $report
 * @return list<string>
 */
function render_ninja_explain_report_lines(array $report, bool $includeDetails = false): array
{
	$report = normalize_ninja_explain_report($report);
	if (!(bool) ($report['enabled'] ?? false)) {
		return ['Ninja explain: not captured'];
	}
	$source = trim((string) ($report['source'] ?? ''));
	$lines = [
		'Ninja explain: captured ' . (int) ($report['line_count'] ?? 0)
			. ' line(s), ' . (int) ($report['object_count'] ?? 0)
			. ' object(s)' . ($source !== '' ? ' (' . $source . ')' : ''),
	];
	if (!$includeDetails) {
		return $lines;
	}
	$objectExplanations = is_array($report['object_explanations'] ?? null) ? $report['object_explanations'] : [];
	if ($objectExplanations !== []) {
		$lines[] = 'Ninja explained objects:';
		foreach ($objectExplanations as $objectPath => $messages) {
			$messageList = normalize_string_list($messages);
			$lines[] = '  - ' . (string) $objectPath . ': ' . ($messageList === [] ? '(no message)' : implode('; ', $messageList));
		}
		return $lines;
	}
	$rows = is_array($report['lines'] ?? null) ? $report['lines'] : [];
	if ($rows === []) {
		$lines[] = 'Ninja explain lines: none';
		return $lines;
	}
	$lines[] = 'Ninja explain lines:';
	foreach ($rows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$message = trim((string) ($row['message'] ?? ''));
		if ($message !== '') {
			$lines[] = '  - ' . $message;
		}
	}
	return $lines;
}

/**
 * @param array<string,mixed> $report
 * @return list<string>
 */
function render_object_action_identity_lines(array $report): array
{
	$report = normalize_object_action_identity_report($report);
	if ((int) ($report['total_action_count'] ?? 0) === 0 && (string) ($report['capture_mode'] ?? '') !== 'full') {
		$reason = trim((string) ($report['capture_reason'] ?? 'default lightweight report'));
		return [
			'Object action identity: not captured' . ($reason !== '' ? ' (' . $reason . ')' : ''),
			'Enable full capture with build.object_action_identity = true, SCPP_OBJECT_ACTION_IDENTITY=full, build.object_cache = true, or Ninja explain diagnostics.',
		];
	}
	$lines = [
		'Object action identity: ' . (int) ($report['total_action_count'] ?? 0)
			. ' action(s), generated ' . (int) ($report['generated_action_count'] ?? 0)
			. ', native ' . (int) ($report['native_action_count'] ?? 0)
			. ', skipped dependency objects ' . (int) ($report['skipped_dependency_object_count'] ?? 0)
			. ', algorithm ' . (string) ($report['algorithm'] ?? 'sha256:scpp-object-action-v1')
			. ', capture ' . (string) ($report['capture_mode'] ?? 'full')
			. ' (' . (string) ($report['capture_reason'] ?? 'requested') . ')',
	];
	foreach (normalize_object_action_identity_rows(is_array($report['actions'] ?? null) ? $report['actions'] : []) as $action) {
		$lines[] = '  - ' . (string) ($action['object_path'] ?? '')
			. ': ' . (string) ($action['kind'] ?? '')
			. ', key ' . (string) ($action['action_key'] ?? '')
			. ', input ' . (string) ($action['input_hash'] ?? '')
			. ', command ' . (string) ($action['command_hash'] ?? '')
			. ', output ' . (((string) ($action['output_hash'] ?? '')) !== '' ? (string) ($action['output_hash'] ?? '') : 'missing');
		$primaryInput = trim((string) ($action['primary_input'] ?? ''));
		if ($primaryInput !== '') {
			$lines[] = '    primary input: ' . $primaryInput;
		}
		$memberSources = normalize_string_list($action['member_sources'] ?? []);
		if ($memberSources !== []) {
			$lines[] = '    member sources: ' . implode(', ', $memberSources);
		}
		$forceIncludeHeaders = normalize_string_list($action['force_include_headers'] ?? []);
		if ($forceIncludeHeaders !== []) {
			$lines[] = '    force includes: ' . implode(', ', $forceIncludeHeaders);
		}
		$moduleSurfaces = normalize_string_list($action['module_surface_inputs'] ?? []);
		if ($moduleSurfaces !== []) {
			$lines[] = '    module surfaces: ' . implode(', ', $moduleSurfaces);
		}
	}
	return $lines;
}

/**
 * @param array<string,mixed> $report
 * @return list<string>
 */
function render_object_cache_lines(array $report): array
{
	$report = normalize_object_cache_report($report);
	$restore = is_array($report['restore'] ?? null) ? $report['restore'] : [];
	$store = is_array($report['store'] ?? null) ? $report['store'] : [];
	$lines = [
		'Object cache: ' . (string) ($report['status'] ?? 'disabled')
			. ' (' . (string) ($report['source'] ?? 'default disabled') . ')'
			. (((string) ($report['cache_dir'] ?? '')) !== '' ? ', cache ' . (string) ($report['cache_dir'] ?? '') : ''),
		'Object cache restore: actions ' . (int) ($restore['action_count'] ?? 0)
			. ', hits ' . (int) ($restore['hit_count'] ?? 0)
			. ', restored ' . (int) ($restore['restored_count'] ?? 0)
			. ', already-current ' . (int) ($restore['already_current_count'] ?? 0)
			. ', misses ' . (int) ($restore['miss_count'] ?? 0)
			. ', skipped ' . (int) ($restore['skipped_count'] ?? 0),
		'Object cache store: actions ' . (int) ($store['action_count'] ?? 0)
			. ', stored ' . (int) ($store['stored_count'] ?? 0)
			. ', preserved ' . (int) ($store['preserved_count'] ?? 0)
			. ', skipped ' . (int) ($store['skipped_count'] ?? 0),
	];
	$restoreRows = normalize_object_cache_rows(is_array($restore['rows'] ?? null) ? $restore['rows'] : []);
	if ($restoreRows !== []) {
		$lines[] = 'Object cache restore rows:';
		foreach ($restoreRows as $row) {
			$lines[] = '  - ' . (string) ($row['object_path'] ?? '')
				. ': ' . (string) ($row['status'] ?? 'skipped')
				. (((string) ($row['reason'] ?? '')) !== '' ? ' (' . (string) ($row['reason'] ?? '') . ')' : '');
		}
	}
	$storeRows = normalize_object_cache_rows(is_array($store['rows'] ?? null) ? $store['rows'] : []);
	if ($storeRows !== []) {
		$lines[] = 'Object cache store rows:';
		foreach ($storeRows as $row) {
			$lines[] = '  - ' . (string) ($row['object_path'] ?? '')
				. ': ' . (string) ($row['status'] ?? 'skipped')
				. (((string) ($row['reason'] ?? '')) !== '' ? ' (' . (string) ($row['reason'] ?? '') . ')' : '');
		}
	}
	return $lines;
}

/**
 * @param array<string,mixed> $report
 * @return list<string>
 */
function render_build_planner_lines(array $report): array
{
	$report = normalize_build_planner_report($report);
	$timing = is_array($report['timing_ms'] ?? null) ? $report['timing_ms'] : [];
	return [
		'Build planner warm state: ' . (string) ($report['status'] ?? 'unavailable')
			. (((string) ($report['state_path'] ?? '')) !== '' ? ', state ' . (string) ($report['state_path'] ?? '') : ''),
		'Build planner graph: projects ' . (int) ($report['project_count'] ?? 0)
			. ', sources ' . (int) ($report['source_count'] ?? 0)
			. ', native sources ' . (int) ($report['native_source_count'] ?? 0)
			. ', modules ' . (int) ($report['module_count'] ?? 0)
			. ', STAN summary ' . ((bool) ($report['stan_summary_available'] ?? false) ? 'available' : 'unavailable'),
		'Build planner source metadata: hits ' . (int) ($report['source_metadata_hit_count'] ?? 0)
			. ', misses ' . (int) ($report['source_metadata_miss_count'] ?? 0)
			. ', hash reads ' . (int) ($report['hash_read_count'] ?? 0)
			. ', hashes reused ' . (int) ($report['reused_hash_count'] ?? 0),
		'Build planner timing: load ' . (int) ($timing['load_state_ms'] ?? 0)
			. ' ms, source scan ' . (int) ($timing['source_scan_ms'] ?? 0)
			. ' ms, write ' . (int) ($timing['write_state_ms'] ?? 0) . ' ms',
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
	$buildMode = trim((string) ($details['build_mode'] ?? ''));
	if ($buildMode !== '') {
		$lines[] = 'Build mode: ' . $buildMode;
	}
	$buildRoots = is_array($details['build_roots'] ?? null) ? $details['build_roots'] : [];
	$buildDir = trim((string) ($buildRoots['build_dir'] ?? ''));
	$generatedDir = trim((string) ($buildRoots['generated_dir'] ?? ''));
	$cacheDir = trim((string) ($buildRoots['cache_dir'] ?? ''));
	if ($buildDir !== '' || $generatedDir !== '' || $cacheDir !== '') {
		$lines[] = 'Build roots: build ' . ($buildDir !== '' ? $buildDir : 'unknown')
			. ', generated ' . ($generatedDir !== '' ? $generatedDir : 'unknown')
			. ', cache ' . ($cacheDir !== '' ? $cacheDir : 'unknown');
	}
	$buildGrouping = normalize_build_grouping_report(is_array($details['build_grouping'] ?? null) ? $details['build_grouping'] : []);
	if ((string) ($buildGrouping['policy'] ?? '') !== '') {
		foreach (render_build_grouping_lines($buildGrouping, false) as $line) {
			$lines[] = $line;
		}
	}
	$objectActionIdentity = normalize_object_action_identity_report(is_array($details['object_action_identity'] ?? null) ? $details['object_action_identity'] : []);
	if ((int) ($objectActionIdentity['total_action_count'] ?? 0) > 0) {
		$lines[] = 'Object action identities: ' . (int) ($objectActionIdentity['total_action_count'] ?? 0)
			. ' action(s), generated ' . (int) ($objectActionIdentity['generated_action_count'] ?? 0)
			. ', native ' . (int) ($objectActionIdentity['native_action_count'] ?? 0)
			. ', skipped dependency objects ' . (int) ($objectActionIdentity['skipped_dependency_object_count'] ?? 0);
	} elseif ((string) ($objectActionIdentity['capture_mode'] ?? '') !== 'full') {
		$reason = trim((string) ($objectActionIdentity['capture_reason'] ?? 'default lightweight report'));
		$lines[] = 'Object action identities: not captured' . ($reason !== '' ? ' (' . $reason . ')' : '');
	}
	$objectCache = normalize_object_cache_report(is_array($details['object_cache'] ?? null) ? $details['object_cache'] : []);
	if ((bool) ($objectCache['enabled'] ?? false)) {
		$restore = is_array($objectCache['restore'] ?? null) ? $objectCache['restore'] : [];
		$store = is_array($objectCache['store'] ?? null) ? $objectCache['store'] : [];
		$lines[] = 'Object cache: enabled, restore hits ' . (int) ($restore['hit_count'] ?? 0)
			. ' (restored ' . (int) ($restore['restored_count'] ?? 0)
			. ', already-current ' . (int) ($restore['already_current_count'] ?? 0)
			. ', misses ' . (int) ($restore['miss_count'] ?? 0)
			. '), store stored ' . (int) ($store['stored_count'] ?? 0)
			. ', preserved ' . (int) ($store['preserved_count'] ?? 0)
			. ', skipped ' . (int) ($store['skipped_count'] ?? 0);
	}
	$buildPlanner = normalize_build_planner_report(is_array($details['build_planner'] ?? null) ? $details['build_planner'] : []);
	if ((string) ($buildPlanner['state_path'] ?? '') !== '') {
		$lines[] = 'Build planner warm state: ' . (string) ($buildPlanner['status'] ?? 'unavailable')
			. ', source metadata hits ' . (int) ($buildPlanner['source_metadata_hit_count'] ?? 0)
			. '/' . (int) ($buildPlanner['source_count'] ?? 0)
			. ', hash reads ' . (int) ($buildPlanner['hash_read_count'] ?? 0)
			. ', hashes reused ' . (int) ($buildPlanner['reused_hash_count'] ?? 0);
	}
	$lines[] = 'PHP transpile decisions: ' . (int) ($details['transpiled_count'] ?? 0) . ' transpiled, ' . (int) ($details['skipped_count'] ?? 0) . ' reused';
	foreach (render_generated_artifact_write_report_lines(is_array($details['generated_artifact_writes'] ?? null) ? $details['generated_artifact_writes'] : []) as $line) {
		$lines[] = $line;
	}
	$projectModules = normalize_project_module_report(is_array($details['project_modules'] ?? null) ? $details['project_modules'] : []);
	if ((bool) ($projectModules['configured'] ?? false)) {
		foreach (render_project_module_report_lines($projectModules, false) as $line) {
			$lines[] = $line;
		}
	}

	$runtime = is_array($details['runtime'] ?? null) ? $details['runtime'] : [];
	$runtimeReasons = is_array($runtime['reasons'] ?? null) ? $runtime['reasons'] : [];
	$lines[] = 'Runtime: ' . (string) ($runtime['action'] ?? 'unknown') . format_reason_suffix($runtimeReasons);
	foreach (render_runtime_module_explanation_lines(is_array($details['runtime_modules'] ?? null) ? $details['runtime_modules'] : []) as $line) {
		$lines[] = $line;
	}

	$dependencies = is_array($details['dependencies'] ?? null) ? $details['dependencies'] : [];
	$dependencyReasons = is_array($dependencies['reasons'] ?? null) ? $dependencies['reasons'] : [];
	$lines[] = 'Dependencies: ' . (string) ($dependencies['action'] ?? 'unknown') . format_reason_suffix($dependencyReasons);
	foreach (render_project_unit_force_include_lines(is_array($details['project_unit_force_includes'] ?? null) ? $details['project_unit_force_includes'] : [], false) as $line) {
		$lines[] = $line;
	}
	foreach (render_build_rebuild_fanout_lines(is_array($details['rebuild_fanout'] ?? null) ? $details['rebuild_fanout'] : []) as $line) {
		$lines[] = $line;
	}
	if ((bool) (($details['ninja_explain']['enabled'] ?? false))) {
		foreach (render_ninja_explain_report_lines(is_array($details['ninja_explain'] ?? null) ? $details['ninja_explain'] : [], false) as $line) {
			$lines[] = $line;
		}
	}

	$sources = is_array($details['sources'] ?? null) ? $details['sources'] : [];
	$transpiled = [];
	$reused = [];
	foreach ($sources as $source) {
		if (!is_array($source)) {
			continue;
		}
		$path = (string) ($source['path'] ?? '(unknown)');
		$reasons = is_array($source['reasons'] ?? null) ? $source['reasons'] : [];
		$line = $path . ' -> ' . (string) ($source['action'] ?? 'unknown') . format_reason_suffix($reasons) . format_generated_artifact_suffix($source);
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
 * @param array<string,mixed> $fanout
 * @return list<string>
 */
function render_build_rebuild_fanout_lines(array $fanout): array
{
	$fanout = normalize_build_rebuild_fanout($fanout);
	$lines = [
		'Rebuild fanout: outputs ' . (int) ($fanout['rebuilt_output_count'] ?? 0)
			. ', objects ' . (int) ($fanout['rebuilt_object_count'] ?? 0)
			. ' (generated ' . (int) ($fanout['rebuilt_generated_object_count'] ?? 0)
			. ', native ' . (int) ($fanout['rebuilt_native_object_count'] ?? 0)
			. ', runtime ' . (int) ($fanout['rebuilt_runtime_object_count'] ?? 0)
			. '), project-unit packs changed ' . (int) ($fanout['changed_project_unit_pack_count'] ?? 0)
			. ', removed ' . (int) ($fanout['removed_project_unit_pack_count'] ?? 0)
			. ', Ninja no-work ' . (($fanout['ninja_no_work'] ?? false) ? 'yes' : 'no'),
	];
	foreach ([
		'rebuilt_generated_objects' => 'rebuilt generated objects',
		'rebuilt_native_objects' => 'rebuilt native objects',
		'rebuilt_runtime_objects' => 'rebuilt runtime objects',
		'changed_project_unit_pack_headers' => 'changed project-unit pack headers',
		'removed_project_unit_pack_headers' => 'removed project-unit pack headers',
	] as $key => $label) {
		$values = normalize_string_list($fanout[$key] ?? []);
		if ($values !== []) {
			$lines[] = '  ' . $label . ': ' . implode(', ', $values);
		}
	}
	return $lines;
}

/**
 * @param array<string,mixed> $report
 * @return list<string>
 */
function render_build_grouping_lines(array $report, bool $includeGroups = false): array
{
	$report = normalize_build_grouping_report($report);
	$policy = trim((string) ($report['policy'] ?? ''));
	if ($policy === '') {
		return ['Build grouping: unavailable'];
	}
	$status = str_replace('_', '-', trim((string) ($report['status'] ?? 'report_only')));
	$source = trim((string) ($report['source'] ?? ''));
	$strategy = trim((string) ($report['compile_unit_strategy'] ?? ''));
	$lines = [
		'Build grouping: ' . $policy
			. ($source !== '' ? ' (' . $source . ', ' . $status . ')' : ' (' . $status . ')')
			. ($strategy !== '' ? ', compile units ' . str_replace('_', '-', $strategy) : ''),
	];
	$fanout = is_array($report['object_fanout'] ?? null) ? $report['object_fanout'] : [];
	$lines[] = 'Build grouping fanout: groups ' . (int) ($report['total_groups'] ?? 0)
		. ', changed ' . (int) ($report['changed_group_count'] ?? 0)
		. ', rebuilt objects ' . (int) ($fanout['rebuilt_object_count'] ?? 0)
		. ' (generated ' . (int) ($fanout['rebuilt_generated_object_count'] ?? 0)
		. ', native ' . (int) ($fanout['rebuilt_native_object_count'] ?? 0) . ')';
	if ($policy === 'manual') {
		$lines[] = 'Build grouping manual map: groups ' . (int) ($report['manual_group_count'] ?? 0)
			. ', assigned sources ' . (int) ($report['manual_assigned_source_count'] ?? 0)
			. ', unassigned root sources ' . (int) ($report['manual_unassigned_source_count'] ?? 0);
	}
	if ($policy === 'module') {
		$lines[] = 'Build grouping project modules: groups ' . (int) ($report['module_group_count'] ?? 0)
			. ', assigned sources ' . (int) ($report['module_assigned_source_count'] ?? 0)
			. ', unassigned grouped sources ' . (int) ($report['module_unassigned_source_count'] ?? 0);
	}
	if ($policy === 'auto') {
		$autoEvidence = is_array($report['auto_evidence'] ?? null) ? $report['auto_evidence'] : [];
		$selectedPolicy = trim((string) ($autoEvidence['selected_policy'] ?? 'incremental'));
		$evidenceSource = trim((string) ($autoEvidence['source'] ?? 'none'));
		$lines[] = 'Build grouping auto: selected ' . ($selectedPolicy !== '' ? $selectedPolicy : 'incremental')
			. ', evidence ' . ($evidenceSource !== '' ? $evidenceSource : 'none')
			. ', prior Ninja ' . (int) ($autoEvidence['prior_ninja_subprocess_ms'] ?? 0) . ' ms'
			. ', prior generated fanout ' . (int) ($autoEvidence['prior_rebuilt_generated_object_count'] ?? 0);
	}
	if (!$includeGroups) {
		return $lines;
	}
	$notes = normalize_string_list($report['notes'] ?? []);
	foreach ($notes as $note) {
		$lines[] = 'Build grouping note: ' . $note;
	}
	if ($policy === 'auto') {
		$reasons = normalize_string_list($report['auto_evidence']['reasons'] ?? []);
		foreach ($reasons as $reason) {
			$lines[] = 'Build grouping auto reason: ' . $reason;
		}
		$decisions = normalize_auto_build_grouping_source_decisions(is_array($report['auto_source_decisions'] ?? null) ? $report['auto_source_decisions'] : []);
		if ($decisions !== []) {
			$lines[] = 'Build grouping auto decisions:';
			foreach ($decisions as $decision) {
				$groupId = trim((string) ($decision['group_id'] ?? ''));
				$reason = trim((string) ($decision['reason'] ?? ''));
				$lines[] = '  - ' . (string) ($decision['source'] ?? '')
					. ': ' . (string) ($decision['decision'] ?? 'isolated')
					. ($groupId !== '' ? ' ' . $groupId : '')
					. ($reason !== '' ? ' (' . $reason . ')' : '');
			}
		}
	}
	$groups = is_array($report['groups'] ?? null) ? $report['groups'] : [];
	if ($groups === []) {
		$lines[] = 'Build groups: none';
		return $lines;
	}
	$lines[] = 'Build groups:';
	foreach ($groups as $group) {
		if (!is_array($group)) {
			continue;
		}
		$id = trim((string) ($group['id'] ?? ''));
		$label = trim((string) ($group['label'] ?? ''));
		$lines[] = '  - ' . ($id !== '' ? $id : ($label !== '' ? $label : '(unknown)'))
			. ': generated ' . (int) ($group['generated_source_count'] ?? 0)
			. ', native ' . (int) ($group['native_source_count'] ?? 0)
			. ', objects ' . (int) ($group['object_count'] ?? 0)
			. ', rebuilt ' . (int) ($group['rebuilt_object_count'] ?? 0)
			. ', changed ' . (((bool) ($group['changed'] ?? false)) ? 'yes' : 'no');
		$sources = array_merge(normalize_string_list($group['generated_sources'] ?? []), normalize_string_list($group['native_sources'] ?? []));
		if ($sources !== []) {
			$lines[] = '    sources: ' . implode(', ', $sources);
		}
		$objects = normalize_string_list($group['objects'] ?? []);
		if ($objects !== []) {
			$lines[] = '    objects: ' . implode(', ', $objects);
		}
	}
	return $lines;
}

/**
 * @param array<string,mixed> $report
 * @return list<string>
 */
function render_project_module_report_lines(array $report, bool $includeModules = false, ?string $moduleFilter = null): array
{
	$report = normalize_project_module_report($report);
	if (!(bool) ($report['configured'] ?? false)) {
		return ['Project modules: none configured'];
	}
	$lines = [
		'Project modules: ' . (int) ($report['total_modules'] ?? 0)
			. ' module(s), ' . (int) ($report['assigned_source_count'] ?? 0)
			. '/' . (int) ($report['generated_source_count'] ?? 0)
			. ' generated source(s) assigned, unassigned ' . (int) ($report['unassigned_source_count'] ?? 0),
	];
	$cache = is_array($report['cache_status_counts'] ?? null) ? $report['cache_status_counts'] : [];
	$lines[] = 'Project module cache: hits ' . (int) ($cache['hit'] ?? 0)
		. ', new ' . (int) ($cache['new'] ?? 0)
		. ', interface changed ' . ((int) ($cache['interface_changed'] ?? 0) + (int) ($cache['interface_and_implementation_changed'] ?? 0))
		. ', implementation changed ' . ((int) ($cache['implementation_changed'] ?? 0) + (int) ($cache['interface_and_implementation_changed'] ?? 0));
	$stanSummaryCache = is_array($report['stan_summary_cache_status_counts'] ?? null) ? $report['stan_summary_cache_status_counts'] : [];
	$lines[] = 'Project module analysis cache: hits ' . (int) ($stanSummaryCache['hit'] ?? 0)
		. ', new ' . (int) ($stanSummaryCache['new'] ?? 0)
		. ', changed ' . (int) ($stanSummaryCache['changed'] ?? 0)
		. ', unavailable ' . (int) ($stanSummaryCache['unavailable'] ?? 0);
	$lines[] = 'Project module consumer fanout: ' . (int) ($report['consumer_rebuild_required_count'] ?? 0) . ' consumer module(s) require rebuild by interface hash';
	$validation = normalize_project_module_dependency_validation(is_array($report['dependency_validation'] ?? null) ? $report['dependency_validation'] : []);
	$lines[] = 'Project module dependency validation: ' . (string) ($validation['status'] ?? 'unavailable')
		. ' (policy ' . (string) ($validation['policy'] ?? 'report')
		. ', evidence ' . (string) ($validation['evidence_source'] ?? 'none') . ')'
		. ', inferred ' . (int) ($validation['inferred_dependency_count'] ?? 0)
		. ', undeclared ' . (int) ($validation['undeclared_dependency_count'] ?? 0)
		. ', unused declared ' . (int) ($validation['unused_declared_dependency_count'] ?? 0);
	$publicApiValidation = normalize_project_module_public_api_validation(is_array($report['public_api_validation'] ?? null) ? $report['public_api_validation'] : []);
	$lines[] = 'Project module public API validation: ' . (string) ($publicApiValidation['status'] ?? 'unavailable')
		. ' (policy ' . (string) ($publicApiValidation['policy'] ?? 'report')
		. ', evidence ' . (string) ($publicApiValidation['evidence_source'] ?? 'none') . ')'
		. ', resolved exports ' . (int) ($publicApiValidation['resolved_public_export_count'] ?? 0)
		. ', unknown exports ' . (int) ($publicApiValidation['unknown_public_export_count'] ?? 0)
		. ', private deps ' . (int) ($publicApiValidation['private_dependency_count'] ?? 0);
	if (!$includeModules && $moduleFilter === null) {
		return $lines;
	}
	$manifestArtifacts = normalize_string_list($report['manifest_artifacts'] ?? []);
	if ($manifestArtifacts !== []) {
		$lines[] = 'Project module manifests: ' . implode(', ', $manifestArtifacts);
	}
	$modules = is_array($report['modules'] ?? null) ? $report['modules'] : [];
	$matched = [];
	$filter = $moduleFilter !== null ? trim($moduleFilter) : '';
	foreach ($modules as $module) {
		if (!is_array($module)) {
			continue;
		}
		$name = trim((string) ($module['name'] ?? ''));
		$projectLabel = trim((string) ($module['project_root'] ?? '.'));
		$qualifiedName = $projectLabel === '' || $projectLabel === '.' ? $name : $projectLabel . '/' . $name;
		if ($filter !== '' && $filter !== $name && $filter !== $qualifiedName) {
			continue;
		}
		$matched[] = $module;
	}
	if ($filter !== '' && $matched === []) {
		$lines[] = 'Project module ' . $filter . ': not found';
		return $lines;
	}
	if ($matched === []) {
		$lines[] = 'Project module rows: none';
		return $lines;
	}
	$lines[] = $filter !== '' ? 'Project module detail:' : 'Project module rows:';
	foreach ($matched as $module) {
		$name = trim((string) ($module['name'] ?? ''));
		$projectLabel = trim((string) ($module['project_root'] ?? '.'));
		$dependencies = normalize_string_list($module['dependencies'] ?? []);
		$surfaceArtifact = trim((string) ($module['surface_artifact'] ?? ''));
		$implementationArtifact = trim((string) ($module['implementation_artifact'] ?? ''));
		$stanSummaryArtifact = trim((string) ($module['stan_summary_artifact'] ?? ''));
		$lines[] = '  - ' . ($projectLabel === '' || $projectLabel === '.' ? $name : $projectLabel . '/' . $name)
			. ': sources ' . (int) ($module['source_count'] ?? 0)
			. ', deps ' . ($dependencies === [] ? 'none' : implode(', ', $dependencies))
			. ', cache ' . trim((string) ($module['cache_status'] ?? 'unavailable'))
			. ', analysis cache ' . trim((string) ($module['stan_summary_cache_status'] ?? 'unavailable'))
			. ', analysis evidence ' . trim((string) ($module['stan_summary_evidence_source'] ?? 'none'))
			. ', interface changed ' . (((bool) ($module['interface_changed'] ?? false)) ? 'yes' : 'no')
			. ', implementation changed ' . (((bool) ($module['implementation_changed'] ?? false)) ? 'yes' : 'no')
			. ', consumers ' . (((bool) ($module['consumer_rebuild_required'] ?? false)) ? 'rebuild' : 'stable')
			. ', validation ' . trim((string) ($module['dependency_validation_status'] ?? 'unavailable'))
			. ', public API ' . trim((string) ($module['public_api_validation_status'] ?? 'unavailable'))
			. ($surfaceArtifact !== '' ? ', artifact ' . $surfaceArtifact : '');
		if ($implementationArtifact !== '') {
			$lines[] = '    implementation artifact: ' . $implementationArtifact;
		}
		if ($stanSummaryArtifact !== '') {
			$lines[] = '    analysis summary artifact: ' . $stanSummaryArtifact;
		}
		$stanSummaryReasons = normalize_string_list($module['stan_summary_cache_reasons'] ?? []);
		foreach ($stanSummaryReasons as $reason) {
			$lines[] = '    analysis cache reason: ' . $reason;
		}
		$sourceRows = normalize_project_module_source_rows(is_array($module['sources'] ?? null) ? $module['sources'] : []);
		if ($sourceRows !== []) {
			$sourceNames = array_map(static fn (array $row): string => (string) ($row['source'] ?? ''), $sourceRows);
			$lines[] = '    sources: ' . implode(', ', normalize_string_list($sourceNames));
		}
		$implementationArtifacts = normalize_string_list($module['implementation_artifacts'] ?? []);
		if ($implementationArtifacts !== []) {
			$lines[] = '    implementation artifacts: ' . implode(', ', $implementationArtifacts);
		}
		$consumerReasons = normalize_string_list($module['consumer_rebuild_reasons'] ?? []);
		foreach ($consumerReasons as $reason) {
			$lines[] = '    consumer reason: ' . $reason;
		}
		$unresolved = normalize_string_list($module['unresolved_dependencies'] ?? []);
		if ($unresolved !== []) {
			$lines[] = '    unresolved dependencies: ' . implode(', ', $unresolved);
		}
		$inferred = normalize_string_list($module['inferred_dependencies'] ?? []);
		if ($inferred !== []) {
			$lines[] = '    inferred dependencies: ' . implode(', ', $inferred);
		}
		$undeclared = normalize_string_list($module['undeclared_dependencies'] ?? []);
		if ($undeclared !== []) {
			$lines[] = '    undeclared dependencies: ' . implode(', ', $undeclared);
		}
		$unusedDeclared = normalize_string_list($module['unused_declared_dependencies'] ?? []);
		if ($unusedDeclared !== []) {
			$lines[] = '    unused declared dependencies: ' . implode(', ', $unusedDeclared);
		}
		$resolvedPublicExports = normalize_string_list($module['resolved_public_exports'] ?? []);
		if ($resolvedPublicExports !== []) {
			$lines[] = '    resolved public exports: ' . implode(', ', $resolvedPublicExports);
		}
		$unknownPublicExports = normalize_string_list($module['unknown_public_exports'] ?? []);
		if ($unknownPublicExports !== []) {
			$lines[] = '    unknown public exports: ' . implode(', ', $unknownPublicExports);
		}
		$privateDependencyViolations = normalize_string_list($module['private_dependency_violations'] ?? []);
		if ($privateDependencyViolations !== []) {
			$lines[] = '    private dependency violations: ' . implode(', ', $privateDependencyViolations);
		}
	}
	$unassignedSources = normalize_string_list($report['unassigned_sources'] ?? []);
	if ($unassignedSources !== []) {
		$lines[] = 'Project module unassigned sources: ' . implode(', ', $unassignedSources);
	}
	$duplicateAssignments = is_array($report['duplicate_assignments'] ?? null) ? $report['duplicate_assignments'] : [];
	foreach ($duplicateAssignments as $assignment) {
		if (!is_array($assignment)) {
			continue;
		}
		$source = trim((string) ($assignment['source'] ?? ''));
		$modulesForSource = normalize_string_list($assignment['modules'] ?? []);
		if ($source !== '' && $modulesForSource !== []) {
			$lines[] = 'Project module duplicate assignment: ' . $source . ' -> ' . implode(', ', $modulesForSource);
		}
	}
	return $lines;
}

/** @param array<string,mixed> $source */
function format_generated_artifact_suffix(array $source): string
{
	$artifacts = is_array($source['generated_artifacts'] ?? null) ? $source['generated_artifacts'] : [];
	$changeReason = trim((string) ($artifacts['change_reason'] ?? ''));
	$objectReason = trim((string) ($source['object_rebuild_reason'] ?? ''));
	$objectRebuilt = (bool) ($source['object_rebuilt'] ?? false);
	$parts = [];
	if ($changeReason !== '') {
		$parts[] = $changeReason;
	}
	if ($objectReason !== '') {
		$objectState = array_key_exists('object_rebuilt', $source) && $source['object_rebuilt'] === null
			? 'unknown'
			: ($objectRebuilt ? 'rebuilt' : 'not rebuilt');
		$parts[] = 'object ' . $objectState . ': ' . $objectReason;
	}
	return $parts === [] ? '' : ' [' . implode('; ', $parts) . ']';
}

/**
 * @param array<string,mixed> $report
 * @return list<string>
 */
function render_project_unit_force_include_lines(array $report, bool $includeDependencySummaries = false, ?string $dependencySummarySource = null, bool $compactDependencySummaries = false): array
{
	$totalUnits = (int) ($report['total_units'] ?? 0);
	$unitsWithForceInclude = (int) ($report['units_with_force_include'] ?? 0);
	$distinctHeaders = (int) ($report['distinct_headers'] ?? 0);
	$headers = is_array($report['headers'] ?? null) ? $report['headers'] : [];
	if ($totalUnits === 0 && $unitsWithForceInclude === 0 && $headers === []) {
		return ['Project unit force-includes: none'];
	}

	$lines = [
		'Project unit force-includes: ' . $unitsWithForceInclude . '/' . $totalUnits . ' unit(s), ' . $distinctHeaders . ' distinct header(s)',
	];
	$activeScopedUnits = max(0, (int) ($report['active_scoped_units'] ?? 0));
	$activeBroadFallbackUnits = max(0, (int) ($report['active_broad_fallback_units'] ?? 0));
	$candidateScopedUnits = max(0, (int) ($report['candidate_scoped_units'] ?? 0));
	$candidateBlockedUnits = max(0, (int) ($report['candidate_blocked_units'] ?? 0));
	if (($activeScopedUnits + $activeBroadFallbackUnits + $candidateScopedUnits + $candidateBlockedUnits) > 0) {
		$lines[] = 'Project unit scoped fanout: active scoped ' . $activeScopedUnits
			. ', active broad fallback ' . $activeBroadFallbackUnits
			. ', candidates scoped ' . $candidateScopedUnits
			. ', candidates blocked ' . $candidateBlockedUnits;
	}
	$nativeUnits = max(0, (int) ($report['native_units'] ?? 0));
	if ($nativeUnits > 0) {
		$nativeBroadFallbackUnits = max(0, (int) ($report['native_broad_fallback_units'] ?? $nativeUnits));
		$nativePolicy = normalize_project_unit_native_policy(is_array($report['native_policy'] ?? null) ? $report['native_policy'] : []);
		$lines[] = 'Project unit native policy: ' . $nativeBroadFallbackUnits . '/' . $nativeUnits . ' native unit(s) broad fallback (' . (string) ($nativePolicy['reason'] ?? '') . ')';
	}
	$candidateBlockerCounts = normalize_project_unit_candidate_blocker_counts(is_array($report['candidate_blocker_counts'] ?? null) ? $report['candidate_blocker_counts'] : []);
	if ($candidateBlockerCounts !== []) {
		$blockerParts = [];
		foreach (array_slice($candidateBlockerCounts, 0, 5) as $row) {
			$blockerParts[] = (string) ($row['reason'] ?? '') . ' (' . (int) ($row['unit_count'] ?? 0) . ' unit(s))';
		}
		$lines[] = 'Project unit candidate blockers: ' . implode('; ', $blockerParts);
	}
	$packChanges = normalize_project_unit_pack_changes(is_array($report['pack_changes'] ?? null) ? $report['pack_changes'] : []);
	$lines[] = 'Project unit pack changes: changed ' . (int) ($packChanges['changed_count'] ?? 0) . ', removed ' . (int) ($packChanges['removed_count'] ?? 0);
	if (($includeDependencySummaries || (bool) ($report['show_pack_change_headers'] ?? false)) && (($packChanges['changed_headers'] ?? []) !== [] || ($packChanges['removed_headers'] ?? []) !== [])) {
		if (($packChanges['changed_headers'] ?? []) !== []) {
			$lines[] = '  changed pack headers: ' . implode(', ', array_map(static fn ($value): string => (string) $value, $packChanges['changed_headers']));
		}
		if (($packChanges['removed_headers'] ?? []) !== []) {
			$lines[] = '  removed pack headers: ' . implode(', ', array_map(static fn ($value): string => (string) $value, $packChanges['removed_headers']));
		}
	}
	$summaryArtifact = normalize_project_unit_dependency_summary_artifact_info(is_array($report['dependency_summary_artifact'] ?? null) ? $report['dependency_summary_artifact'] : []);
	if ($includeDependencySummaries && ($summaryArtifact['path'] ?? '') !== '') {
		$lines[] = 'Project unit dependency summary artifact: ' . (string) ($summaryArtifact['path'] ?? '')
			. ' (sources ' . (int) ($summaryArtifact['source_count'] ?? 0)
			. ', STAN ' . (($summaryArtifact['used_stan_dependency_state'] ?? false) ? 'yes' : 'no')
			. ', overrides ' . (($summaryArtifact['source_overrides_active'] ?? false) ? 'yes' : 'no') . ')';
	}
	$headerRows = count($headers) > SCPP_EXPLAIN_PROJECT_UNIT_HEADER_LIMIT
		? array_slice($headers, 0, SCPP_EXPLAIN_PROJECT_UNIT_HEADER_LIMIT)
		: $headers;
	if (count($headers) > count($headerRows)) {
		$lines[] = 'Project unit headers shown: first ' . count($headerRows) . ' of ' . count($headers) . ' header(s)';
	}
	foreach ($headerRows as $header) {
		if (!is_array($header)) {
			continue;
		}
		$path = trim((string) ($header['path'] ?? ''));
		if ($path === '') {
			continue;
		}
		$mode = trim((string) ($header['mode'] ?? 'unknown'));
		$lines[] = '  - ' . $path . ': '
			. (int) ($header['unit_count'] ?? 0) . ' unit(s), '
			. (int) ($header['line_count'] ?? 0) . ' line(s), '
			. (int) ($header['byte_count'] ?? 0) . ' byte(s), '
			. ($mode !== '' ? $mode : 'unknown');
	}
	if ($includeDependencySummaries) {
		$dependencySummaries = is_array($report['dependency_summaries'] ?? null) ? $report['dependency_summaries'] : [];
		if ($dependencySummaries === []) {
			$lines[] = 'Dependency summaries: none';
		} elseif ($dependencySummarySource !== null && trim($dependencySummarySource) !== '') {
			$matched = [];
			foreach ($dependencySummaries as $summary) {
				if (is_array($summary) && project_unit_dependency_summary_matches_filter($summary, $dependencySummarySource)) {
					$matched[] = $summary;
				}
			}
			if ($matched === []) {
				$lines[] = 'Dependency summary for ' . trim($dependencySummarySource) . ': not found';
			} else {
				$lines[] = 'Dependency summary for ' . trim($dependencySummarySource) . ':';
				foreach ($matched as $summary) {
					foreach (render_project_unit_dependency_summary_detail_lines($summary) as $line) {
						$lines[] = $line;
					}
				}
			}
		} elseif ($compactDependencySummaries) {
			$lines[] = 'Dependency summaries: ' . count($dependencySummaries) . ' unit(s)';
			$summaryRows = count($dependencySummaries) > SCPP_EXPLAIN_PROJECT_UNIT_SUMMARY_LIMIT
				? array_slice($dependencySummaries, 0, SCPP_EXPLAIN_PROJECT_UNIT_SUMMARY_LIMIT)
				: $dependencySummaries;
			if (count($dependencySummaries) > count($summaryRows)) {
				$lines[] = 'Dependency summaries shown: first ' . count($summaryRows) . ' of ' . count($dependencySummaries) . ' unit(s); use `scpp explain-build project-unit <source>` for a detailed source row';
			}
			foreach ($summaryRows as $summary) {
				if (!is_array($summary)) {
					continue;
				}
				$line = render_project_unit_dependency_summary_compact_line($summary);
				if ($line !== '') {
					$lines[] = '  - ' . $line;
				}
			}
		} else {
			$lines[] = 'Dependency summaries:';
			foreach ($dependencySummaries as $summary) {
				if (!is_array($summary)) {
					continue;
				}
				foreach (render_project_unit_dependency_summary_detail_lines($summary) as $line) {
					$lines[] = $line;
				}
			}
		}
	}
	return $lines;
}

/** @param array<string,mixed> $summary */
function project_unit_dependency_summary_matches_filter(array $summary, string $filter): bool
{
	$normalizedFilter = normalize_config_path(trim($filter));
	if ($normalizedFilter === '') {
		return false;
	}
	$source = normalize_config_path(trim((string) ($summary['source'] ?? '')));
	$projectRoot = normalize_config_path(trim((string) ($summary['project_root'] ?? '')));
	if ($source === $normalizedFilter) {
		return true;
	}
	$qualifiedSource = $projectRoot === '' || $projectRoot === '.' ? $source : normalize_config_path($projectRoot . '/' . $source);
	return $qualifiedSource === $normalizedFilter;
}

/** @param array<string,mixed> $summary */
function render_project_unit_dependency_summary_compact_line(array $summary): string
{
	$source = trim((string) ($summary['source'] ?? ''));
	if ($source === '') {
		return '';
	}
	$label = project_unit_dependency_summary_label($summary);
	$status = trim((string) ($summary['status'] ?? 'fallback_broad'));
	$candidateStatus = trim((string) ($summary['candidate_status'] ?? ''));
	$directDependencies = normalize_string_list($summary['direct_source_dependencies'] ?? []);
	$directHeaders = normalize_string_list($summary['direct_local_headers'] ?? []);
	$candidateBlockers = normalize_string_list($summary['candidate_blocking_reasons'] ?? []);
	$categoryNames = render_project_unit_dependency_category_name_list(is_array($summary['dependency_categories'] ?? null) ? $summary['dependency_categories'] : []);
	$parts = [$label . ': ' . ($status !== '' ? $status : 'fallback_broad')];
	if ($candidateStatus !== '') {
		$parts[] = 'candidate ' . $candidateStatus;
	}
	$parts[] = 'direct deps ' . count($directDependencies);
	$parts[] = 'direct headers ' . count($directHeaders);
	if ($candidateBlockers !== []) {
		$parts[] = 'blockers ' . implode(', ', $candidateBlockers);
	}
	if ($categoryNames !== []) {
		$parts[] = 'categories ' . implode(', ', $categoryNames);
	}
	return implode(', ', $parts);
}

/** @param list<array<string,mixed>> $rows @return list<string> */
function render_project_unit_dependency_category_name_list(array $rows): array
{
	$names = [];
	foreach (normalize_project_unit_dependency_category_rows($rows) as $row) {
		$category = trim((string) ($row['category'] ?? ''));
		$resolution = trim((string) ($row['resolution'] ?? ''));
		if ($resolution === 'unresolved_symbol') {
			$category = 'unresolved symbol';
		} elseif ($resolution === 'ambiguous_symbol') {
			$category = 'ambiguous symbol';
		} elseif ($resolution === 'unresolved_dependency_key') {
			$category = 'unresolved dependency key';
		}
		if ($category !== '') {
			$names[$category] = true;
		}
	}
	$result = array_keys($names);
	sort($result, SORT_STRING);
	return $result;
}

/** @param array<string,mixed> $summary @return list<string> */
function render_project_unit_dependency_summary_detail_lines(array $summary): array
{
	$source = trim((string) ($summary['source'] ?? ''));
	if ($source === '') {
		return [];
	}
	$status = trim((string) ($summary['status'] ?? 'fallback_broad'));
	$lines = ['  - ' . project_unit_dependency_summary_label($summary) . ': ' . ($status !== '' ? $status : 'fallback_broad')];
	$candidateStatus = trim((string) ($summary['candidate_status'] ?? ''));
	if ($candidateStatus !== '') {
		$lines[] = '    candidate status: ' . $candidateStatus;
	}
	$candidatePackHeader = trim((string) ($summary['candidate_pack_header'] ?? ''));
	if ($candidatePackHeader !== '') {
		$lines[] = '    candidate pack: ' . $candidatePackHeader;
	}
	$candidateHeaders = is_array($summary['candidate_scoped_headers'] ?? null) ? $summary['candidate_scoped_headers'] : [];
	if ($candidateHeaders !== []) {
		$lines[] = '    candidate scoped headers: ' . implode(', ', array_map(static fn ($value): string => (string) $value, $candidateHeaders));
	}
	$candidateBlockers = is_array($summary['candidate_blocking_reasons'] ?? null) ? $summary['candidate_blocking_reasons'] : [];
	foreach ($candidateBlockers as $blocker) {
		$message = trim((string) $blocker);
		if ($message !== '') {
			$lines[] = '    candidate blocker: ' . $message;
		}
	}
	$generatedHeader = trim((string) ($summary['generated_header'] ?? ''));
	if ($generatedHeader !== '') {
		$lines[] = '    generated header: ' . $generatedHeader;
	}
	$directDependencies = is_array($summary['direct_source_dependencies'] ?? null) ? $summary['direct_source_dependencies'] : [];
	$lines[] = '    direct source dependencies: ' . ($directDependencies === [] ? 'none' : implode(', ', array_map(static fn ($value): string => (string) $value, $directDependencies)));
	$directHeaders = is_array($summary['direct_local_headers'] ?? null) ? $summary['direct_local_headers'] : [];
	$lines[] = '    direct local headers: ' . ($directHeaders === [] ? 'none' : implode(', ', array_map(static fn ($value): string => (string) $value, $directHeaders)));
	$scopedHeaders = is_array($summary['scoped_local_headers'] ?? null) ? $summary['scoped_local_headers'] : [];
	if ($scopedHeaders !== [] && normalize_string_list($scopedHeaders) !== normalize_string_list($directHeaders)) {
		$lines[] = '    scoped local headers: ' . implode(', ', array_map(static fn ($value): string => (string) $value, $scopedHeaders));
	}
	$unresolvedKeys = is_array($summary['unresolved_dependency_keys'] ?? null) ? $summary['unresolved_dependency_keys'] : [];
	if ($unresolvedKeys !== []) {
		$lines[] = '    unresolved dependency keys: ' . implode(', ', array_map(static fn ($value): string => (string) $value, $unresolvedKeys));
	}
	$dependencyCategoryParts = render_project_unit_dependency_category_parts(is_array($summary['dependency_categories'] ?? null) ? $summary['dependency_categories'] : []);
	if ($dependencyCategoryParts !== []) {
		$lines[] = '    dependency categories: ' . implode('; ', $dependencyCategoryParts);
	}
	$reasons = is_array($summary['reasons'] ?? null) ? $summary['reasons'] : [];
	foreach ($reasons as $reason) {
		$message = trim((string) $reason);
		if ($message !== '') {
			$lines[] = '    reason: ' . $message;
		}
	}
	return $lines;
}

/** @param array<string,mixed> $summary */
function project_unit_dependency_summary_label(array $summary): string
{
	$source = trim((string) ($summary['source'] ?? ''));
	$projectRoot = trim((string) ($summary['project_root'] ?? ''));
	return $projectRoot === '' || $projectRoot === '.' ? $source : normalize_config_path($projectRoot . '/' . $source);
}

/**
 * @param array<string,mixed> $runtimeModules
 * @return list<string>
 */
function render_runtime_module_explanation_lines(array $runtimeModules): array
{
	$modules = is_array($runtimeModules['modules'] ?? null) ? $runtimeModules['modules'] : [];
	if ($modules === []) {
		return [];
	}

	$labels = [];
	foreach ($modules as $module) {
		if (!is_array($module)) {
			continue;
		}
		$name = trim((string) ($module['name'] ?? ''));
		if ($name === '') {
			continue;
		}
		$implicitReason = trim((string) ($module['implicit_reason'] ?? ''));
		$labels[] = $implicitReason !== ''
			? $name . ' (implicit via ' . $implicitReason . ')'
			: $name;
	}
	if ($labels === []) {
		return [];
	}

	$lines = ['Runtime modules: ' . implode(', ', $labels)];
	$webview = is_array($runtimeModules['webview'] ?? null) ? $runtimeModules['webview'] : null;
	if ($webview !== null) {
		$backend = trim((string) ($webview['backend'] ?? 'unknown'));
		$enabled = (bool) ($webview['enabled'] ?? false);
		$lines[] = 'WebView backend: ' . ($backend !== '' ? $backend : 'unknown') . ($enabled ? '' : ' (disabled)');
		$diagnostics = is_array($webview['diagnostics'] ?? null) ? $webview['diagnostics'] : [];
		foreach ($diagnostics as $diagnostic) {
			$message = trim((string) $diagnostic);
			if ($message !== '') {
				$lines[] = 'WebView diagnostic: ' . $message;
			}
		}
	}
	return $lines;
}

/**
 * @param array<string,mixed> $details
 * @return list<string>
 */
function render_explain_build_view_lines(array $details, string $view, array $viewArgs = []): array
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
			$lines[] = $path . format_reason_suffix(is_array($source['reasons'] ?? null) ? $source['reasons'] : []) . format_generated_artifact_suffix($source);
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
			$lines[] = $path . format_reason_suffix(is_array($source['reasons'] ?? null) ? $source['reasons'] : []) . format_generated_artifact_suffix($source);
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

	if ($view === 'rebuild-fanout') {
		return render_build_rebuild_fanout_lines(is_array($details['rebuild_fanout'] ?? null) ? $details['rebuild_fanout'] : []);
	}

	if ($view === 'generated-artifacts') {
		return render_generated_artifact_write_report_lines(is_array($details['generated_artifact_writes'] ?? null) ? $details['generated_artifact_writes'] : []);
	}

	if ($view === 'ninja-explain') {
		return render_ninja_explain_report_lines(is_array($details['ninja_explain'] ?? null) ? $details['ninja_explain'] : [], true);
	}

	if ($view === 'action-identity') {
		return render_object_action_identity_lines(is_array($details['object_action_identity'] ?? null) ? $details['object_action_identity'] : []);
	}

	if ($view === 'object-cache') {
		return render_object_cache_lines(is_array($details['object_cache'] ?? null) ? $details['object_cache'] : []);
	}

	if ($view === 'build-planner') {
		return render_build_planner_lines(is_array($details['build_planner'] ?? null) ? $details['build_planner'] : []);
	}

	if ($view === 'grouping') {
		return render_build_grouping_lines(is_array($details['build_grouping'] ?? null) ? $details['build_grouping'] : [], true);
	}

	if ($view === 'project-units') {
		return render_project_unit_force_include_lines(is_array($details['project_unit_force_includes'] ?? null) ? $details['project_unit_force_includes'] : [], true, null, true);
	}

	if ($view === 'project-unit') {
		$source = trim((string) ($viewArgs[0] ?? ''));
		if ($source === '') {
			return ['Project unit source: missing source path. Use `scpp explain-build project-unit <source>`.'];
		}
		return render_project_unit_force_include_lines(is_array($details['project_unit_force_includes'] ?? null) ? $details['project_unit_force_includes'] : [], true, $source, false);
	}

	if ($view === 'modules') {
		return render_project_module_report_lines(is_array($details['project_modules'] ?? null) ? $details['project_modules'] : [], true);
	}

	if ($view === 'module') {
		$module = trim((string) ($viewArgs[0] ?? ''));
		if ($module === '') {
			return ['Project module: missing module name. Use `scpp explain-build module <name>`.'];
		}
		return render_project_module_report_lines(is_array($details['project_modules'] ?? null) ? $details['project_modules'] : [], true, $module);
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
		$lines = render_generated_artifact_write_report_lines(is_array($details['generated_artifact_writes'] ?? null) ? $details['generated_artifact_writes'] : []);
		$fileLines = [];
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
			$projectUnitMode = trim((string) ($source['project_unit_force_include_mode'] ?? ''));
			$projectUnitHeader = trim((string) ($source['project_unit_force_include_header'] ?? ''));
			if ($projectUnitHeader !== '') {
				$line .= ' (project unit: ' . ($projectUnitMode !== '' ? $projectUnitMode . ' ' : '') . $projectUnitHeader . ')';
			}
			$projectModule = trim((string) ($source['project_module'] ?? ''));
			if ($projectModule !== '') {
				$line .= ' (project module: ' . $projectModule . ')';
			}
			$fileLines[] = $line;
		}
		return $fileLines === [] ? array_merge($lines, ['Generated files: none']) : array_merge($lines, ['Generated files:'], array_map(static fn (string $line): string => '  - ' . $line, $fileLines));
	}

	if ($view === 'ninja-target') {
		return render_explain_build_ninja_hint_lines($details);
	}

	scpp_fail(
		'Unknown explain-build view `' . $view . '`. Use one of: files-transpiled, files-reused, outputs-rebuilt, rebuild-fanout, generated-artifacts, ninja-explain, action-identity, object-cache, build-planner, grouping, project-units, project-unit <source>, modules, module <name>, entrypoint, final-output, generated-files, ninja-target.' . PHP_EOL,
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
				'source_message' => isset($diagnostic['source_message']) ? (string) $diagnostic['source_message'] : null,
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
				'container' => isset($diagnostic['container']) ? (string) $diagnostic['container'] : null,
				'index' => isset($diagnostic['index']) ? (string) $diagnostic['index'] : null,
				'size' => isset($diagnostic['size']) ? (string) $diagnostic['size'] : null,
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
				'source_message' => infer_source_compile_diagnostic_message($originSource, $originalLine, $diagnostic['message']),
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

function infer_source_compile_diagnostic_message(string $sourceFile, int $sourceLine, string $compilerMessage): ?string
{
	if ($sourceLine <= 0 || !is_file($sourceFile) || !is_readable($sourceFile)) {
		return null;
	}
	if (preg_match('/from [\x{2018}\'"]([^\x{2019}\'"]+)[\x{2019}\'"] to [\x{2018}\'"]([^\x{2019}\'"]+)[\x{2019}\'"]/u', $compilerMessage, $matches) !== 1) {
		return null;
	}
	$actual = source_type_name_from_cpp_type(trim((string) $matches[1]));
	$expected = source_type_name_from_cpp_type(trim((string) $matches[2]));
	if ($actual === '' || $expected === '') {
		return null;
	}
	$lines = file($sourceFile, FILE_IGNORE_NEW_LINES);
	if (!is_array($lines) || !isset($lines[$sourceLine - 1])) {
		return null;
	}
	$call = find_source_call_on_line(trim((string) $lines[$sourceLine - 1]));
	if ($call === null) {
		return null;
	}
	return 'argument ' . $call['argument_index'] . ' passed to ' . $call['name'] . ' expects ' . $expected . ', got ' . $actual;
}

/** @return array{name:string,argument_index:int}|null */
function find_source_call_on_line(string $source): ?array
{
	if (preg_match_all('/\b([A-Za-z_][A-Za-z0-9_]*)\s*\(([^()]*)\)/', $source, $matches, PREG_SET_ORDER) === false) {
		return null;
	}
	foreach ($matches as $match) {
		$name = (string) ($match[1] ?? '');
		if ($name === '' || in_array(strtolower($name), ['if', 'while', 'for', 'foreach', 'switch', 'match', 'isset', 'empty'], true)) {
			continue;
		}
		return ['name' => $name, 'argument_index' => infer_source_argument_index((string) ($match[2] ?? ''))];
	}
	return null;
}

function infer_source_argument_index(string $args): int
{
	$parts = split_source_argument_list($args);
	foreach ($parts as $index => $part) {
		$part = trim($part);
		if ($part === '') {
			continue;
		}
		if (preg_match('/^["\']/', $part) === 1) {
			return $index + 1;
		}
	}
	return 1;
}

/** @return list<string> */
function split_source_argument_list(string $args): array
{
	$args = trim($args);
	if ($args === '') {
		return [];
	}
	$parts = [];
	$current = '';
	$depth = 0;
	$quote = null;
	for ($i = 0, $length = strlen($args); $i < $length; $i++) {
		$ch = $args[$i];
		if ($quote !== null) {
			$current .= $ch;
			if ($ch === '\\' && $i + 1 < $length) {
				$current .= $args[++$i];
				continue;
			}
			if ($ch === $quote) {
				$quote = null;
			}
			continue;
		}
		if ($ch === '"' || $ch === "'") {
			$quote = $ch;
			$current .= $ch;
			continue;
		}
		if ($ch === '(' || $ch === '[' || $ch === '{') {
			$depth++;
		} elseif ($ch === ')' || $ch === ']' || $ch === '}') {
			$depth = max(0, $depth - 1);
		} elseif ($ch === ',' && $depth === 0) {
			$parts[] = trim($current);
			$current = '';
			continue;
		}
		$current .= $ch;
	}
	$parts[] = trim($current);
	return array_values(array_filter($parts, static fn (string $part): bool => $part !== ''));
}

function source_type_name_from_cpp_type(string $type): string
{
	$type = trim($type);
	$type = preg_replace('/\s+/', ' ', $type) ?? $type;
	$type = preg_replace('/^const\s+/', '', $type) ?? $type;
	$type = preg_replace('/[&*]+$/', '', $type) ?? $type;
	$type = trim(str_replace('scpp::', '', $type));
	$fixedIntMap = [
		'' => 'int',
		'std::int8_t' => 'int8',
		'std::int16_t' => 'int16',
		'std::int32_t' => 'int32',
		'std::int64_t' => 'int64',
		'std::uint8_t' => 'uint8',
		'std::uint16_t' => 'uint16',
		'std::uint32_t' => 'uint32',
		'std::uint64_t' => 'uint64',
	];
	if (preg_match('/^int_t(?:<\s*([^>]*)\s*>)?$/', $type, $matches) === 1) {
		$key = trim((string) ($matches[1] ?? ''));
		return $fixedIntMap[$key] ?? 'int';
	}
	$map = [
		'int_t' => 'int',
		'float_t' => 'float',
		'bool_t' => 'bool',
		'string_t' => 'string',
		'mixed_t' => 'mixed',
		'null_t' => 'null',
	];
	if (isset($map[$type])) {
		return $map[$type];
	}
	if (preg_match('/^vector_t<(.+)>$/', $type, $matches) === 1) {
		return 'vector<' . source_type_name_from_cpp_type((string) $matches[1]) . '>';
	}
	if (preg_match('/^hash_t<(.+)>$/', $type, $matches) === 1) {
		return 'hash<' . source_type_name_from_cpp_type((string) $matches[1]) . '>';
	}
	return $type;
}

/**
 * @param list<array<string,mixed>> $generatedUnits
 * @param array<string,mixed> $runtimeConfig
 */
function validate_runtime_module_symbol_usage(string $projectRoot, array $generatedUnits, array $runtimeConfig): void
{
	$modules = [];
	foreach ((array) ($runtimeConfig['modules'] ?? []) as $module) {
		if (is_string($module) && $module !== '') {
			$modules[strtolower($module)] = true;
		}
	}

	if (!isset($modules['regex'])) {
		$diagnostic = find_first_regex_runtime_symbol_usage($projectRoot, $generatedUnits);
		if ($diagnostic !== null) {
			$sourceLabel = normalize_config_path(relative_path($projectRoot, $diagnostic['source_file']));
			$message = 'Regex helper `' . $diagnostic['source_helper'] . '` requires runtime module `regex`, but `regex` is not enabled in ' . SCPP_PROJECT_CONFIG . '.' . PHP_EOL;
			$message .= 'Source: ' . $sourceLabel . ':' . $diagnostic['source_line'] . PHP_EOL;
			$message .= 'Add "regex" to `runtime.modules` and ensure the PCRE2 development files are installed.' . PHP_EOL;
			scpp_fail($message, 3);
		}
	}
}

/**
 * @param list<array<string,mixed>> $generatedUnits
 * @return array{source_file:string,source_line:int,source_helper:string}|null
 */
function find_first_regex_runtime_symbol_usage(string $projectRoot, array $generatedUnits): ?array
{
	foreach ($generatedUnits as $unit) {
		$generatedCpp = $unit['generated_cpp'] ?? null;
		if (!is_string($generatedCpp) || $generatedCpp === '' || !is_file($generatedCpp)) {
			continue;
		}
		$contents = file_get_contents($generatedCpp);
		if (!is_string($contents) || !str_contains($contents, 'regex::')) {
			continue;
		}
		if (preg_match('/\bregex::([A-Za-z_][A-Za-z0-9_]*)\s*\(/', $contents, $matches, PREG_OFFSET_CAPTURE) !== 1) {
			continue;
		}
		$offset = (int) ($matches[0][1] ?? 0);
		$generatedLine = substr_count(substr($contents, 0, $offset), "\n") + 1;
		$mapEntry = lookup_generated_map_entry($generatedCpp, $generatedLine);
		$sourceLine = is_array($mapEntry) ? (int) $mapEntry['line'] : 1;
		$sourceFile = is_string($unit['relative_php'] ?? null) && is_string($unit['project_root'] ?? null)
			? normalize_path((string) $unit['project_root'] . '/' . normalize_config_path((string) $unit['relative_php']))
			: normalize_path($projectRoot . '/main.phs');
		return [
			'source_file' => $sourceFile,
			'source_line' => max(1, $sourceLine),
			'source_helper' => regex_runtime_symbol_to_source_helper((string) ($matches[1][0] ?? '')),
		];
	}
	return null;
}

function regex_runtime_symbol_to_source_helper(string $symbol): string
{
	return match ($symbol) {
		'jit_available' => 'regex_jit_available',
		default => 'regex_' . $symbol,
	};
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
		$message = is_string($diagnostic['source_message'] ?? null) && (string) $diagnostic['source_message'] !== ''
			? (string) $diagnostic['source_message']
			: (string) ($diagnostic['message'] ?? '');
		if ($message !== '') {
			$line .= ': ' . $message;
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
	return "#include <scpp/lang/php.hpp>\n";
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

/** @return array{size:int,mtime:int,ctime:int,content_hash:string} */
function build_file_meta(string $path): array
{
	$size = filesize($path);
	$mtime = filemtime($path);
	$stat = @stat($path);
	$ctime = is_array($stat) ? ($stat['ctime'] ?? false) : false;
	$contentHash = hash_file('sha256', $path);
	if ($size === false || $mtime === false || $ctime === false || $contentHash === false) {
		scpp_fail('Failed to stat file: ' . $path . PHP_EOL, 2);
	}
	return [
		'size' => (int) $size,
		'mtime' => (int) $mtime,
		'ctime' => (int) $ctime,
		'content_hash' => $contentHash,
	];
}

/**
 * @param array<string,mixed> $previousState
 * @param array<string,mixed> $stats
 * @return array{size:int,mtime:int,ctime:int,content_hash:string}
 */
function build_file_meta_with_planner_state(string $path, string $contextProjectRoot, string $relativeSource, array $previousState, array &$stats): array
{
	$size = filesize($path);
	$mtime = filemtime($path);
	$stat = @stat($path);
	$ctime = is_array($stat) ? ($stat['ctime'] ?? false) : false;
	if ($size === false || $mtime === false || $ctime === false) {
		scpp_fail('Failed to stat file: ' . $path . PHP_EOL, 2);
	}
	$sourceKey = build_planner_source_key($contextProjectRoot, $relativeSource);
	$previousSources = is_array($previousState['source_metadata_by_key'] ?? null) ? $previousState['source_metadata_by_key'] : [];
	$previous = is_array($previousSources[$sourceKey] ?? null) ? $previousSources[$sourceKey] : null;
	$status = 'miss';
	$contentHash = '';
	if (
		is_array($previous)
		&& (int) ($previous['size'] ?? -1) === (int) $size
		&& (int) ($previous['mtime'] ?? -1) === (int) $mtime
		&& (int) ($previous['ctime'] ?? -1) === (int) $ctime
		&& preg_match('/^[0-9a-f]{64}$/', (string) ($previous['content_hash'] ?? '')) === 1
		&& build_planner_previous_source_timestamp_is_safe($previous, $previousState)
	) {
		$contentHash = (string) $previous['content_hash'];
		$status = 'hit';
		$stats['source_metadata_hit_count'] = (int) ($stats['source_metadata_hit_count'] ?? 0) + 1;
		$stats['reused_hash_count'] = (int) ($stats['reused_hash_count'] ?? 0) + 1;
	} else {
		$hash = hash_file('sha256', $path);
		if ($hash === false) {
			scpp_fail('Failed to hash file: ' . $path . PHP_EOL, 2);
		}
		$contentHash = $hash;
		$stats['source_metadata_miss_count'] = (int) ($stats['source_metadata_miss_count'] ?? 0) + 1;
		$stats['hash_read_count'] = (int) ($stats['hash_read_count'] ?? 0) + 1;
	}

	$stats['source_count'] = (int) ($stats['source_count'] ?? 0) + 1;
	$rows = is_array($stats['source_metadata_rows'] ?? null) ? $stats['source_metadata_rows'] : [];
	$rows[] = [
		'project_root' => normalize_path($contextProjectRoot),
		'source' => normalize_config_path($relativeSource),
		'source_key' => $sourceKey,
		'size' => (int) $size,
		'mtime' => (int) $mtime,
		'ctime' => (int) $ctime,
		'content_hash' => $contentHash,
		'cache_status' => $status,
	];
	$stats['source_metadata_rows'] = $rows;

	return [
		'size' => (int) $size,
		'mtime' => (int) $mtime,
		'ctime' => (int) $ctime,
		'content_hash' => $contentHash,
	];
}

/** @return array<string,mixed> */
function load_build_planner_state(string $statePath): array
{
	$state = read_json_file($statePath);
	if (!is_array($state)) {
		return normalize_build_planner_state([]);
	}
	return normalize_build_planner_state($state);
}

/** @return array<string,mixed> */
function create_build_planner_stats(string $projectRoot, string $cacheDir, array $previousState, int $loadMs): array
{
	return [
		'project_root' => normalize_path($projectRoot),
		'state_path' => normalize_path($cacheDir . '/' . SCPP_BUILD_PLANNER_STATE_FILE),
		'state_path_relative' => normalize_config_path(relative_path($projectRoot, $cacheDir . '/' . SCPP_BUILD_PLANNER_STATE_FILE)),
		'previous_signature' => trim((string) ($previousState['signature'] ?? '')),
		'previous_available' => trim((string) ($previousState['signature'] ?? '')) !== '',
		'load_ms' => max(0, $loadMs),
		'source_count' => 0,
		'source_metadata_hit_count' => 0,
		'source_metadata_miss_count' => 0,
		'hash_read_count' => 0,
		'reused_hash_count' => 0,
		'source_metadata_rows' => [],
	];
}

/**
 * @param array<string,array<string,mixed>> $projectGraph
 * @param array<string,array<string,mixed>> $projectContexts
 * @param list<array<string,mixed>> $generatedUnits
 * @param list<array<string,mixed>> $nativeCppUnits
 * @return array<string,mixed>
 */
function write_build_planner_state_report(
	string $projectRoot,
	string $cacheDir,
	array $previousState,
	array $stats,
	array $projectGraph,
	array $projectContexts,
	array $generatedUnits,
	array $nativeCppUnits,
	array $projectModuleReport,
	?array $stanPreflightReport,
	int $sourceScanMs
): array {
	$statePath = normalize_path($cacheDir . '/' . SCPP_BUILD_PLANNER_STATE_FILE);
	$sourceMetadataRows = normalize_build_planner_source_metadata_rows(is_array($stats['source_metadata_rows'] ?? null) ? $stats['source_metadata_rows'] : []);
	foreach ($sourceMetadataRows as &$sourceMetadataRow) {
		$sourceMetadataRow['cache_status'] = '';
	}
	unset($sourceMetadataRow);
	$state = normalize_build_planner_state([
		'schema_version' => 1,
		'project_root' => normalize_path($projectRoot),
		'written_at' => time(),
		'project_graph' => collect_build_planner_project_rows($projectRoot, $projectGraph, $projectContexts),
		'source_metadata' => $sourceMetadataRows,
		'native_sources' => collect_build_planner_native_source_rows($projectRoot, $nativeCppUnits),
		'module_summaries' => collect_build_planner_module_summary_rows($projectModuleReport),
		'stan_summary' => collect_build_planner_stan_summary($stanPreflightReport),
	]);
	$state['signature'] = hash_project_module_payload([
		'version' => 1,
		'project_graph' => $state['project_graph'],
		'source_metadata' => $state['source_metadata'],
		'native_sources' => $state['native_sources'],
		'module_summaries' => $state['module_summaries'],
		'stan_summary' => $state['stan_summary'],
	]);
	$writeStartedAt = microtime(true);
	write_json_file_atomic($statePath, $state);
	$writeMs = (int) round(max(0.0, (microtime(true) - $writeStartedAt) * 1000.0));
	$previousSignature = trim((string) ($previousState['signature'] ?? ''));
	$currentSignature = trim((string) ($state['signature'] ?? ''));
	$sourceCount = max(0, (int) ($stats['source_count'] ?? 0));
	$hitCount = max(0, (int) ($stats['source_metadata_hit_count'] ?? 0));
	$missCount = max(0, (int) ($stats['source_metadata_miss_count'] ?? 0));
	$status = 'new';
	if ($previousSignature !== '') {
		$status = $previousSignature === $currentSignature ? 'hit' : 'changed';
		if ($hitCount > 0 && $missCount > 0) {
			$status = 'partial';
		}
	}
	$modules = is_array($state['module_summaries'] ?? null) ? $state['module_summaries'] : [];
	$nativeSources = is_array($state['native_sources'] ?? null) ? $state['native_sources'] : [];
	$projectRows = is_array($state['project_graph'] ?? null) ? $state['project_graph'] : [];
	return normalize_build_planner_report([
		'schema_version' => 1,
		'status' => $status,
		'state_path' => normalize_config_path(relative_path($projectRoot, $statePath)),
		'previous_signature' => $previousSignature,
		'current_signature' => $currentSignature,
		'project_count' => count($projectRows),
		'source_count' => $sourceCount,
		'native_source_count' => count($nativeSources),
		'module_count' => count($modules),
		'stan_summary_available' => is_array($state['stan_summary'] ?? null) && trim((string) (($state['stan_summary'] ?? [])['source_fingerprint'] ?? '')) !== '',
		'source_metadata_hit_count' => $hitCount,
		'source_metadata_miss_count' => $missCount,
		'hash_read_count' => max(0, (int) ($stats['hash_read_count'] ?? 0)),
		'reused_hash_count' => max(0, (int) ($stats['reused_hash_count'] ?? 0)),
		'timing_ms' => [
			'load_state_ms' => max(0, (int) ($stats['load_ms'] ?? 0)),
			'source_scan_ms' => max(0, $sourceScanMs),
			'write_state_ms' => $writeMs,
		],
	]);
}

function build_planner_source_key(string $projectRoot, string $relativeSource): string
{
	return normalize_path($projectRoot) . ':' . normalize_config_path($relativeSource);
}

/**
 * @param array<string,mixed> $previousSource
 * @param array<string,mixed> $previousState
 */
function build_planner_previous_source_timestamp_is_safe(array $previousSource, array $previousState): bool
{
	$writtenAt = (int) ($previousState['written_at'] ?? 0);
	if ($writtenAt <= 0) {
		return false;
	}
	return (int) ($previousSource['mtime'] ?? 0) < $writtenAt
		&& (int) ($previousSource['ctime'] ?? 0) < $writtenAt;
}

/**
 * @param array<string,array<string,mixed>> $projectGraph
 * @param array<string,array<string,mixed>> $projectContexts
 * @return list<array<string,mixed>>
 */
function collect_build_planner_project_rows(string $rootProjectRoot, array $projectGraph, array $projectContexts): array
{
	$rows = [];
	foreach ($projectGraph as $projectRoot => $projectSpec) {
		$projectRoot = normalize_path((string) ($projectSpec['project_root'] ?? $projectRoot));
		$context = is_array($projectContexts[$projectRoot] ?? null) ? $projectContexts[$projectRoot] : [];
		$rows[] = [
			'project_root' => $projectRoot,
			'project_label' => project_context_report_label($rootProjectRoot, $projectRoot),
			'config_path' => normalize_path((string) ($projectSpec['config_path'] ?? '')),
			'build_dir' => normalize_path((string) ($context['build_dir'] ?? '')),
			'generated_dir' => normalize_path((string) ($context['generated_dir'] ?? '')),
			'cache_dir' => normalize_path((string) ($context['cache_dir'] ?? '')),
			'dependency_roots' => normalize_path_list(is_array($context['dependency_roots'] ?? null) ? $context['dependency_roots'] : []),
		];
	}
	usort($rows, static fn (array $left, array $right): int => strcmp((string) ($left['project_root'] ?? ''), (string) ($right['project_root'] ?? '')));
	return $rows;
}

/** @return list<array<string,mixed>> */
function collect_build_planner_native_source_rows(string $projectRoot, array $nativeCppUnits): array
{
	$rows = [];
	foreach ($nativeCppUnits as $unit) {
		if (!is_array($unit)) {
			continue;
		}
		$unitProjectRoot = normalize_path((string) ($unit['project_root'] ?? ''));
		$sourcePath = normalize_path((string) ($unit['source_path'] ?? ''));
		if ($sourcePath === '') {
			continue;
		}
		$rows[] = [
			'project_root' => $unitProjectRoot,
			'source' => normalize_config_path(relative_path($unitProjectRoot !== '' ? $unitProjectRoot : $projectRoot, $sourcePath)),
			'object_path' => normalize_config_path(relative_path($projectRoot, normalize_path((string) ($unit['object_path'] ?? '')))),
		];
	}
	usort($rows, static fn (array $left, array $right): int => strcmp((string) ($left['project_root'] ?? '') . ':' . (string) ($left['source'] ?? ''), (string) ($right['project_root'] ?? '') . ':' . (string) ($right['source'] ?? '')));
	return $rows;
}

/** @return list<array<string,mixed>> */
function collect_build_planner_module_summary_rows(array $projectModuleReport): array
{
	$rows = [];
	foreach (is_array($projectModuleReport['modules'] ?? null) ? $projectModuleReport['modules'] : [] as $module) {
		if (!is_array($module)) {
			continue;
		}
		$name = trim((string) ($module['name'] ?? ''));
		if ($name === '') {
			continue;
		}
		$rows[] = [
			'name' => $name,
			'project_root' => normalize_path((string) ($module['project_root'] ?? '')),
			'source_count' => max(0, (int) ($module['source_count'] ?? 0)),
			'dependencies' => normalize_string_list($module['dependencies'] ?? []),
			'interface_hash' => trim((string) ($module['interface_hash'] ?? '')),
			'implementation_hash' => trim((string) ($module['implementation_hash'] ?? '')),
			'stan_summary_hash' => trim((string) ($module['stan_summary_hash'] ?? '')),
		];
	}
	usort($rows, static fn (array $left, array $right): int => strcmp((string) ($left['project_root'] ?? '') . ':' . (string) ($left['name'] ?? ''), (string) ($right['project_root'] ?? '') . ':' . (string) ($right['name'] ?? '')));
	return $rows;
}

/** @return array<string,mixed> */
function collect_build_planner_stan_summary(?array $stanPreflightReport): array
{
	if (!is_array($stanPreflightReport)) {
		return [
			'source_fingerprint' => '',
			'analysis_mode' => 'unavailable',
			'diagnostic_count' => 0,
			'warning_count' => 0,
			'compile_error_count' => 0,
		];
	}
	return [
		'source_fingerprint' => trim((string) ($stanPreflightReport['source_fingerprint'] ?? '')),
		'analysis_mode' => trim((string) ($stanPreflightReport['analysis_mode'] ?? '')),
		'diagnostic_count' => count(is_array($stanPreflightReport['diagnostics'] ?? null) ? $stanPreflightReport['diagnostics'] : []),
		'warning_count' => max(0, (int) ($stanPreflightReport['warning_count'] ?? 0)),
		'compile_error_count' => max(0, (int) ($stanPreflightReport['compile_error_count'] ?? 0)),
	];
}

/** @return array<string,mixed> */
function normalize_build_planner_state(array $state): array
{
	$sourceRows = normalize_build_planner_source_metadata_rows(is_array($state['source_metadata'] ?? null) ? $state['source_metadata'] : []);
	$sourceByKey = [];
	foreach ($sourceRows as $row) {
		$key = (string) ($row['source_key'] ?? '');
		if ($key !== '') {
			$sourceByKey[$key] = $row;
		}
	}
	return [
		'schema_version' => max(1, (int) ($state['schema_version'] ?? 1)),
		'project_root' => normalize_path((string) ($state['project_root'] ?? '')),
		'signature' => trim((string) ($state['signature'] ?? '')),
		'written_at' => max(0, (int) ($state['written_at'] ?? 0)),
		'project_graph' => is_array($state['project_graph'] ?? null) ? $state['project_graph'] : [],
		'source_metadata' => $sourceRows,
		'source_metadata_by_key' => $sourceByKey,
		'native_sources' => is_array($state['native_sources'] ?? null) ? $state['native_sources'] : [],
		'module_summaries' => is_array($state['module_summaries'] ?? null) ? $state['module_summaries'] : [],
		'stan_summary' => is_array($state['stan_summary'] ?? null) ? $state['stan_summary'] : [],
	];
}

/** @return list<array<string,mixed>> */
function normalize_build_planner_source_metadata_rows(array $rows): array
{
	$result = [];
	foreach ($rows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$projectRoot = normalize_path((string) ($row['project_root'] ?? ''));
		$source = normalize_config_path((string) ($row['source'] ?? ''));
		if ($projectRoot === '' || $source === '') {
			continue;
		}
		$result[] = [
			'project_root' => $projectRoot,
			'source' => $source,
			'source_key' => trim((string) ($row['source_key'] ?? build_planner_source_key($projectRoot, $source))),
			'size' => max(0, (int) ($row['size'] ?? 0)),
			'mtime' => max(0, (int) ($row['mtime'] ?? 0)),
			'ctime' => max(0, (int) ($row['ctime'] ?? 0)),
			'content_hash' => trim((string) ($row['content_hash'] ?? '')),
			'cache_status' => trim((string) ($row['cache_status'] ?? '')),
		];
	}
	usort($result, static fn (array $left, array $right): int => strcmp((string) ($left['source_key'] ?? ''), (string) ($right['source_key'] ?? '')));
	return $result;
}

/** @return array<string,mixed> */
function normalize_build_planner_report(array $report): array
{
	$timing = is_array($report['timing_ms'] ?? null) ? $report['timing_ms'] : [];
	return [
		'schema_version' => max(1, (int) ($report['schema_version'] ?? 1)),
		'status' => trim((string) ($report['status'] ?? 'unavailable')),
		'state_path' => normalize_config_path((string) ($report['state_path'] ?? '')),
		'previous_signature' => trim((string) ($report['previous_signature'] ?? '')),
		'current_signature' => trim((string) ($report['current_signature'] ?? '')),
		'project_count' => max(0, (int) ($report['project_count'] ?? 0)),
		'source_count' => max(0, (int) ($report['source_count'] ?? 0)),
		'native_source_count' => max(0, (int) ($report['native_source_count'] ?? 0)),
		'module_count' => max(0, (int) ($report['module_count'] ?? 0)),
		'stan_summary_available' => (bool) ($report['stan_summary_available'] ?? false),
		'source_metadata_hit_count' => max(0, (int) ($report['source_metadata_hit_count'] ?? 0)),
		'source_metadata_miss_count' => max(0, (int) ($report['source_metadata_miss_count'] ?? 0)),
		'hash_read_count' => max(0, (int) ($report['hash_read_count'] ?? 0)),
		'reused_hash_count' => max(0, (int) ($report['reused_hash_count'] ?? 0)),
		'timing_ms' => [
			'load_state_ms' => max(0, (int) ($timing['load_state_ms'] ?? 0)),
			'source_scan_ms' => max(0, (int) ($timing['source_scan_ms'] ?? 0)),
			'write_state_ms' => max(0, (int) ($timing['write_state_ms'] ?? 0)),
		],
	];
}

/** @return list<string> */
function collect_project_php_files(string $projectRoot): array
{
	$files = [];
	$byStem = [];
	$iterator = new RecursiveIteratorIterator(
		new RecursiveCallbackFilterIterator(
			new RecursiveDirectoryIterator($projectRoot, FilesystemIterator::SKIP_DOTS),
			static function (SplFileInfo $fileInfo) use ($projectRoot): bool {
				if (!$fileInfo->isDir()) {
					return true;
				}
				return !should_prune_project_source_inventory_directory($projectRoot, $fileInfo->getPathname());
			}
		)
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

function should_prune_project_source_inventory_directory(string $projectRoot, string $path): bool
{
	$relative = normalize_config_path(relative_path($projectRoot, normalize_path($path)));
	return $relative === '.prism'
		|| str_starts_with($relative, '.prism/')
		|| $relative === '.git'
		|| str_starts_with($relative, '.git/')
		|| $relative === '.hg'
		|| str_starts_with($relative, '.hg/')
		|| $relative === '.svn'
		|| str_starts_with($relative, '.svn/');
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

/**
 * @param array<string,array<string,mixed>> $projectContexts
 * @return array<string,array<string,mixed>>
 */
function collect_project_context_php_file_inventory(string $projectRoot, string $entrypointAbs, array $projectContexts): array
{
	$normalizedProjectRoot = normalize_path($projectRoot);
	$normalizedEntrypoint = normalize_path($entrypointAbs);
	foreach ($projectContexts as $contextProjectRoot => &$projectContext) {
		$contextProjectRoot = normalize_path((string) $contextProjectRoot);
		$phpFiles = collect_project_php_files($contextProjectRoot);
		if ($contextProjectRoot === $normalizedProjectRoot && !in_array($normalizedEntrypoint, $phpFiles, true)) {
			$phpFiles[] = $normalizedEntrypoint;
			sort($phpFiles, SORT_STRING);
		}
		$projectContext['php_files'] = $phpFiles;
	}
	unset($projectContext);
	return $projectContexts;
}

/** @param array<string,array<string,mixed>> $projectGraph @param array<string,string> $sourceOverrides @return array<string,string> */
function build_s2s_declared_type_kind_catalog(array $projectGraph, array $sourceOverrides = []): array
{
	$sourcePaths = [];
	foreach ($projectGraph as $projectSpec) {
		$projectRoot = normalize_path((string) ($projectSpec['project_root'] ?? ''));
		if ($projectRoot === '') {
			continue;
		}
		foreach (collect_project_php_files($projectRoot) as $sourcePath) {
			$sourcePaths[] = normalize_path($sourcePath);
		}
	}
	$builder = new DeclarationKindCatalogBuilder();
	return $builder->buildFromSources(array_values(array_unique($sourcePaths)), $sourceOverrides);
}

/** @param array<string,array<string,mixed>> $projectGraph @param array<string,string> $sourceOverrides @param array<string,array<string,mixed>> $projectContexts @return array<string,string> */
function build_s2s_declared_type_kind_catalog_cached(string $projectRoot, string $repoRoot, string $cacheDir, array $projectGraph, array $sourceOverrides, array $previousBuildPlannerState, array $projectContexts = []): array
{
	$cachePath = normalize_path($cacheDir . '/' . SCPP_DECLARED_TYPE_KIND_CATALOG_FILE);
	$sourceInput = collect_declared_type_kind_catalog_source_input($projectGraph, $sourceOverrides, $previousBuildPlannerState, $projectContexts);
	$sourceRows = is_array($sourceInput['source_rows'] ?? null) ? $sourceInput['source_rows'] : [];
	$catalogSignature = compute_declared_type_kind_catalog_signature($repoRoot);
	$cached = read_json_file($cachePath);
	$cachedSources = is_array($cached) && (string) ($cached['catalog_signature'] ?? '') === $catalogSignature && is_array($cached['sources'] ?? null)
		? $cached['sources']
		: [];
	$cachedSourcesByKey = [];
	foreach ($cachedSources as $cachedSource) {
		if (!is_array($cachedSource)) {
			continue;
		}
		$sourceKey = trim((string) ($cachedSource['source_key'] ?? ''));
		if ($sourceKey !== '') {
			$cachedSourcesByKey[$sourceKey] = $cachedSource;
		}
	}

	$catalog = [];
	$cacheRows = [];
	$changed = !is_array($cached) || (string) ($cached['catalog_signature'] ?? '') !== $catalogSignature;
	foreach ($sourceRows as $sourceRow) {
		if (!is_array($sourceRow)) {
			continue;
		}
		$sourceKey = trim((string) ($sourceRow['source_key'] ?? ''));
		$sourcePath = normalize_path((string) ($sourceRow['source_path'] ?? ''));
		if ($sourceKey === '' || $sourcePath === '') {
			continue;
		}
		$cachedSource = is_array($cachedSourcesByKey[$sourceKey] ?? null) ? $cachedSourcesByKey[$sourceKey] : null;
		$sourceCatalog = null;
		if (
			$cachedSource !== null
			&& (string) ($cachedSource['content_hash'] ?? '') === (string) ($sourceRow['content_hash'] ?? '')
			&& (int) ($cachedSource['size'] ?? -1) === (int) ($sourceRow['size'] ?? 0)
			&& (bool) ($cachedSource['source_override'] ?? false) === (bool) ($sourceRow['source_override'] ?? false)
			&& is_array($cachedSource['declared_type_kinds'] ?? null)
		) {
			$sourceCatalog = normalize_declared_type_kind_catalog($cachedSource['declared_type_kinds']);
		} else {
			$sourceCatalog = build_s2s_declared_type_kind_catalog_for_source($sourcePath, $sourceOverrides);
			$changed = true;
		}
		foreach ($sourceCatalog as $name => $kind) {
			$catalog[$name] = $kind;
		}
		$cacheRows[] = [
			'source_key' => $sourceKey,
			'source_path' => $sourcePath,
			'content_hash' => (string) ($sourceRow['content_hash'] ?? ''),
			'size' => (int) ($sourceRow['size'] ?? 0),
			'source_override' => (bool) ($sourceRow['source_override'] ?? false),
			'declared_type_kinds' => $sourceCatalog,
		];
	}
	ksort($catalog, SORT_STRING);
	if (
		!$changed
		&& is_array($cached)
		&& (string) ($cached['source_fingerprint'] ?? '') === (string) ($sourceInput['source_fingerprint'] ?? '')
		&& (int) ($cached['source_count'] ?? -1) === (int) ($sourceInput['source_count'] ?? 0)
		&& is_array($cached['declared_type_kinds'] ?? null)
	) {
		return normalize_declared_type_kind_catalog($cached['declared_type_kinds']);
	}
	write_json_file_atomic($cachePath, [
		'schema_version' => 1,
		'project_root' => normalize_path($projectRoot),
		'catalog_signature' => $catalogSignature,
		'source_fingerprint' => (string) ($sourceInput['source_fingerprint'] ?? ''),
		'source_count' => (int) ($sourceInput['source_count'] ?? 0),
		'updated_at' => time(),
		'sources' => $cacheRows,
		'declared_type_kinds' => $catalog,
	]);
	return $catalog;
}

/** @param array<string,array<string,mixed>> $projectGraph @param array<string,string> $sourceOverrides @param array<string,array<string,mixed>> $projectContexts @return array{source_fingerprint:string,source_count:int,source_rows:list<array<string,mixed>>} */
function collect_declared_type_kind_catalog_source_input(array $projectGraph, array $sourceOverrides, array $previousBuildPlannerState, array $projectContexts = []): array
{
	$sourceRows = [];
	$seen = [];
	foreach ($projectGraph as $projectSpec) {
		$contextProjectRoot = normalize_path((string) ($projectSpec['project_root'] ?? ''));
		if ($contextProjectRoot === '') {
			continue;
		}
		$projectContext = is_array($projectContexts[$contextProjectRoot] ?? null) ? $projectContexts[$contextProjectRoot] : [];
		$phpFiles = is_array($projectContext['php_files'] ?? null)
			? normalize_path_list($projectContext['php_files'])
			: collect_project_php_files($contextProjectRoot);
		foreach ($phpFiles as $sourcePath) {
			$sourcePath = normalize_path($sourcePath);
			if ($sourcePath === '' || isset($seen[$sourcePath]) || strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)) === 'jss') {
				continue;
			}
			$seen[$sourcePath] = true;
			$relativeSource = normalize_config_path(relative_path($contextProjectRoot, $sourcePath));
			$sourceOverride = array_key_exists($sourcePath, $sourceOverrides) ? (string) $sourceOverrides[$sourcePath] : null;
			$meta = $sourceOverride !== null
				? [
					'size' => strlen($sourceOverride),
					'mtime' => 0,
					'ctime' => 0,
					'content_hash' => hash('sha256', $sourceOverride),
					'source_override' => true,
				]
				: declared_type_kind_catalog_source_meta($sourcePath, $contextProjectRoot, $relativeSource, $previousBuildPlannerState);
			$sourceRows[] = [
				'source_key' => build_planner_source_key($contextProjectRoot, $relativeSource),
				'source_path' => $sourcePath,
				'content_hash' => (string) ($meta['content_hash'] ?? ''),
				'size' => max(0, (int) ($meta['size'] ?? 0)),
				'source_override' => (bool) ($meta['source_override'] ?? false),
			];
		}
	}
	usort($sourceRows, static fn (array $left, array $right): int => strcmp((string) ($left['source_key'] ?? ''), (string) ($right['source_key'] ?? '')));
	$fingerprintParts = [];
	foreach ($sourceRows as $sourceRow) {
		$fingerprintParts[] = implode(':', [
			(string) ($sourceRow['source_key'] ?? ''),
			(string) ($sourceRow['content_hash'] ?? ''),
			(string) ((int) ($sourceRow['size'] ?? 0)),
			((bool) ($sourceRow['source_override'] ?? false)) ? 'override' : 'file',
		]);
	}
	return [
		'source_fingerprint' => hash('sha256', implode("\n", $fingerprintParts)),
		'source_count' => count($sourceRows),
		'source_rows' => $sourceRows,
	];
}

/** @param array<string,string> $sourceOverrides @return array<string,string> */
function build_s2s_declared_type_kind_catalog_for_source(string $sourcePath, array $sourceOverrides): array
{
	$builder = new DeclarationKindCatalogBuilder();
	return normalize_declared_type_kind_catalog($builder->buildFromSources([$sourcePath], $sourceOverrides));
}

/** @return array{size:int,mtime:int,ctime:int,content_hash:string,source_override:bool} */
function declared_type_kind_catalog_source_meta(string $path, string $contextProjectRoot, string $relativeSource, array $previousBuildPlannerState): array
{
	$size = filesize($path);
	$mtime = filemtime($path);
	$stat = @stat($path);
	$ctime = is_array($stat) ? ($stat['ctime'] ?? false) : false;
	if ($size === false || $mtime === false || $ctime === false) {
		scpp_fail('Failed to stat file: ' . $path . PHP_EOL, 2);
	}
	$sourceKey = build_planner_source_key($contextProjectRoot, $relativeSource);
	$previousSources = is_array($previousBuildPlannerState['source_metadata_by_key'] ?? null) ? $previousBuildPlannerState['source_metadata_by_key'] : [];
	$previous = is_array($previousSources[$sourceKey] ?? null) ? $previousSources[$sourceKey] : null;
	$contentHash = '';
	if (
		is_array($previous)
		&& (int) ($previous['size'] ?? -1) === (int) $size
		&& (int) ($previous['mtime'] ?? -1) === (int) $mtime
		&& (int) ($previous['ctime'] ?? -1) === (int) $ctime
		&& preg_match('/^[0-9a-f]{64}$/', (string) ($previous['content_hash'] ?? '')) === 1
		&& build_planner_previous_source_timestamp_is_safe($previous, $previousBuildPlannerState)
	) {
		$contentHash = (string) $previous['content_hash'];
	} else {
		$hash = hash_file('sha256', $path);
		if ($hash === false) {
			scpp_fail('Failed to hash file: ' . $path . PHP_EOL, 2);
		}
		$contentHash = $hash;
	}

	return [
		'size' => (int) $size,
		'mtime' => (int) $mtime,
		'ctime' => (int) $ctime,
		'content_hash' => $contentHash,
		'source_override' => false,
	];
}

function compute_declared_type_kind_catalog_signature(string $repoRoot): string
{
	$parts = ['version:' . SCPP_S2S_SIGNATURE_VERSION];
	foreach (declared_type_kind_catalog_signature_files($repoRoot) as $file) {
		if (!is_file($file)) {
			$parts[] = 'missing:' . normalize_config_path($file);
			continue;
		}
		$hash = hash_file('sha256', $file);
		$parts[] = normalize_config_path($file) . ':' . ($hash === false ? 'hash-failed' : $hash);
	}
	return hash('sha256', implode("\n", $parts));
}

/** @return list<string> */
function declared_type_kind_catalog_signature_files(string $repoRoot): array
{
	return [
		$repoRoot . '/generators/php/src/Analysis/DeclarationKindCatalogBuilder.php',
		$repoRoot . '/generators/php/src/Analysis/FrontEndSymbolExtractor.php',
		$repoRoot . '/generators/php/src/PreTokenizer/PreTokenizer.php',
		$repoRoot . '/generators/php/src/PreTokenizer/StructSyntaxRewriter.php',
		$repoRoot . '/generators/php/src/PreTokenizer/UnionSyntaxRewriter.php',
		$repoRoot . '/generators/php/src/PreTokenizer/EnumBackingSyntaxRewriter.php',
	];
}

/** @return array<string,string> */
function normalize_declared_type_kind_catalog(array $catalog): array
{
	$normalized = [];
	foreach ($catalog as $name => $kind) {
		if (!is_string($name) || $name === '') {
			continue;
		}
		$kind = strtolower(trim((string) $kind));
		if (!in_array($kind, ['class', 'enum', 'struct', 'union'], true)) {
			$kind = 'class';
		}
		$normalized[$name] = $kind;
	}
	ksort($normalized, SORT_STRING);
	return $normalized;
}

/** @param array<string,string> $sourceOverrides @param array<string,string> $declaredTypeKinds @return string */
function compute_s2s_generator_signature(string $repoRoot, string $phpProfile = 'legacy', array $sourceOverrides = [], array $declaredTypeKinds = []): string
{
	$parts = [
		'version:' . SCPP_S2S_SIGNATURE_VERSION,
		'php_profile:' . strtolower(trim($phpProfile)),
		'source_overrides:' . ($sourceOverrides === [] ? 'none' : hash('sha256', json_encode($sourceOverrides, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))),
		'declared_type_kinds:' . ($declaredTypeKinds === [] ? 'none' : hash('sha256', json_encode($declaredTypeKinds, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))),
	];

	$files = [
		$repoRoot . '/bin/scpp.php',
		$repoRoot . '/generators/php/src/Transpiler.php',
		$repoRoot . '/generators/php/src/Analysis/FrontEndSymbolExtractor.php',
		$repoRoot . '/generators/php/src/Analysis/DeclarationKindCatalogBuilder.php',
		$repoRoot . '/generators/php/src/PreTokenizer/PreTokenizer.php',
		$repoRoot . '/generators/php/src/PreTokenizer/StructSyntaxRewriter.php',
		$repoRoot . '/generators/php/src/PreTokenizer/UnionSyntaxRewriter.php',
		$repoRoot . '/generators/php/src/PreTokenizer/EnumBackingSyntaxRewriter.php',
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
		$repoRoot . '/generators/php/src/Lowering/TypeMapper.php',
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

function compute_project_unit_dependency_summary_signature(string $repoRoot, string $phpProfile = 'legacy'): string
{
	$parts = [
		'version:' . SCPP_PROJECT_UNIT_DEPENDENCY_SIGNATURE_VERSION,
		'php_profile:' . strtolower(trim($phpProfile)),
	];

	$files = [
		$repoRoot . '/bin/project_services.php',
		$repoRoot . '/generators/php/src/Analysis/FrontEndSymbolExtractor.php',
		$repoRoot . '/generators/php/src/Builder/IrBuilder.php',
		$repoRoot . '/generators/php/src/Jss/JssFileSummaryBuilder.php',
		$repoRoot . '/generators/php/src/Jss/JssNode.php',
		$repoRoot . '/generators/php/src/Jss/JssParser.php',
		$repoRoot . '/generators/php/src/Jss/JssSummaryExtractor.php',
		$repoRoot . '/generators/php/src/Jss/JssTokenizer.php',
		$repoRoot . '/generators/php/src/Loader/InputLoader.php',
		$repoRoot . '/generators/php/src/Stan/StanDependencyResolver.php',
		$repoRoot . '/generators/php/src/Stan/StanPathMapper.php',
		$repoRoot . '/generators/php/src/Stan/StanSourceCatalogBuilder.php',
		$repoRoot . '/generators/php/src/Stan/StanSourceMetaBuilder.php',
		$repoRoot . '/generators/php/src/Stan/StanSourceUnit.php',
		$repoRoot . '/generators/php/src/Stan/StanSymbolIndexBuilder.php',
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

function scpp_stan_worker_debounce_ms(): int
{
	$value = getenv('SCPP_STAN_WORKER_DEBOUNCE_MS');
	if (is_string($value) && ctype_digit($value)) {
		return max(0, (int) $value);
	}
	return 250;
}

function scpp_stan_worker_autostart_enabled(): bool
{
	$value = getenv('SCPP_STAN_WORKER_AUTOSTART');
	if (is_string($value) && trim($value) !== '') {
		return scpp_env_truthy($value);
	}
	return !scpp_capture_subprocess_output_enabled();
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
		$repoRoot . '/generators/php/src/Stan/StanDependencyResolver.php',
		$repoRoot . '/generators/php/src/Stan/StanDiagnosticCollector.php',
		$repoRoot . '/generators/php/src/Stan/StanDiagnosticEnricher.php',
		$repoRoot . '/generators/php/src/Stan/StanExpressionTypeResolver.php',
		$repoRoot . '/generators/php/src/Stan/StanFilePass.php',
		$repoRoot . '/generators/php/src/Stan/StanFrontendClassifier.php',
		$repoRoot . '/generators/php/src/Stan/StanPhpRuntimeFunctionCatalog.php',
		$repoRoot . '/generators/php/src/Stan/StanPositionResolver.php',
		$repoRoot . '/generators/php/src/Stan/StanResultAssembler.php',
		$repoRoot . '/generators/php/src/Stan/StanRuntimeProfilePreparer.php',
		$repoRoot . '/generators/php/src/Stan/StanSemanticPass.php',
		$repoRoot . '/generators/php/src/Stan/StanSourceMetaBuilder.php',
		$repoRoot . '/generators/php/src/Stan/StanSourceCatalogBuilder.php',
		$repoRoot . '/generators/php/src/Stan/StanSourceUnit.php',
		$repoRoot . '/generators/php/src/Stan/StanStateStore.php',
		$repoRoot . '/generators/php/src/Stan/StanSymbolIndexBuilder.php',
		$repoRoot . '/generators/php/src/Stan/StanTakeContractResolver.php',
		$repoRoot . '/generators/php/src/Stan/StanWarningPresenter.php',
		$repoRoot . '/generators/php/src/Stan/StanWorkspaceContext.php',
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
	$initializationKind = (string) ($diagnostic['initialization_kind'] ?? '');
	if (in_array($kind, [
		'duplicate_declaration',
		'unresolved_call',
		'unresolved_static_call',
		'unresolved_method_call',
		'unresolved_property_write',
		'unresolved_property_read',
		'missing_return',
		'direct_self_recursion',
		'fixed_width_integer_literal_range',
		'fixed_width_integer_assignment',
		'enum_assignment',
		'enum_comparison',
		'const_param_write',
		'unsupported_hash_key_type',
		'member_visibility_violation',
		'interface_contract_mismatch',
		'abstract_contract_mismatch',
		'struct_contract_mismatch',
		'union_contract_mismatch',
	], true)) {
		return 'compile-errors';
	}
	if ($kind === 'initialization_warning' && $initializationKind === 'maybe_uninitialized_property') {
		return 'compile-errors';
	}
	if (in_array($kind, [
		'unresolved_dependency',
		'ambiguous_dependency',
		'override_declaration',
		'argument_type_mismatch',
		'argument_count_mismatch',
		'unchecked_wrapper_boundary',
		'unchecked_wrapper_argument',
		'unchecked_wrapper_return',
		'unchecked_wrapper_property_boundary',
		'dynamic_shape_boundary',
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
	$timings = is_array($diagnosticResult['timings_ms'] ?? null) ? $diagnosticResult['timings_ms'] : [];
	return [
		'project_root' => normalize_path((string) ($diagnosticResult['project_root'] ?? $projectRoot)),
		'php_profile' => (string) ($diagnosticResult['php_profile'] ?? ''),
		'source_fingerprint' => $sourceFingerprint,
		'run_id' => build_stan_worker_run_id(),
		'analysis_mode' => (string) ($diagnosticResult['analysis_mode'] ?? 'full'),
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
		'timings_ms' => $timings,
		'diagnostics' => $classified['diagnostics'],
	];
}

/** @return array<string,mixed> */
function build_stan_build_gate_report(string $projectRoot, string $configPath, string $sourceFingerprint): array
{
	$session = new \Scpp\S2S\Stan\StanWorkspaceSession();
	$startedAt = microtime(true);
	$diagnosticResult = $session->runBuildGateDiagnostics($projectRoot, $configPath, []);
	$classified = classify_stan_build_diagnostics(is_array($diagnosticResult['diagnostics'] ?? null) ? $diagnosticResult['diagnostics'] : []);
	$finishedAt = microtime(true);
	$timings = is_array($diagnosticResult['timings_ms'] ?? null) ? $diagnosticResult['timings_ms'] : [];
	return [
		'project_root' => normalize_path((string) ($diagnosticResult['project_root'] ?? $projectRoot)),
		'php_profile' => (string) ($diagnosticResult['php_profile'] ?? ''),
		'source_fingerprint' => $sourceFingerprint,
		'run_id' => build_stan_worker_run_id(),
		'analysis_mode' => 'build_gate',
		'advisory_deferred' => true,
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
		'timings_ms' => $timings,
		'diagnostics' => $classified['diagnostics'],
	];
}

/** @param array<string,mixed> $report @return array<string,mixed> */
function write_stan_report_file_atomic(string $path, array $report): array
{
	$timings = is_array($report['timings_ms'] ?? null) ? $report['timings_ms'] : [];
	$timings['report_write_ms'] = 0;
	$report['timings_ms'] = $timings;
	$startedAt = microtime(true);
	write_json_file_atomic($path, $report);
	$report['timings_ms']['report_write_ms'] = (int) round(max(0.0, (microtime(true) - $startedAt) * 1000.0));
	write_json_file_atomic($path, $report);
	return $report;
}

/** @param array<string,mixed> $status */
function stan_status_matches_fingerprint(?array $status, string $sourceFingerprint): bool
{
	return is_array($status)
		&& (string) ($status['analysis_state'] ?? '') === 'ready'
		&& (string) ($status['source_fingerprint'] ?? '') === $sourceFingerprint;
}

function spawn_stan_worker_process(string $projectRoot): bool
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
		return false;
	}
	proc_close($process);
	return true;
}

/** @param array{heartbeat_path:string} $paths */
function maybe_autostart_stan_worker(string $projectRoot, array $paths): void
{
	if (!scpp_stan_worker_autostart_enabled()) {
		return;
	}
	$heartbeat = read_json_file($paths['heartbeat_path']);
	if (stan_worker_heartbeat_is_live($heartbeat)) {
		return;
	}
	spawn_stan_worker_process($projectRoot);
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
	$gateReport = null;

	if (!stan_status_matches_fingerprint($status, $sourceFingerprint)) {
		$heartbeat = read_json_file($paths['heartbeat_path']);
		if (!stan_worker_heartbeat_is_live($heartbeat)) {
			$gateReport = build_stan_build_gate_report($projectRoot, $configPath, $sourceFingerprint);
			maybe_autostart_stan_worker($projectRoot, $paths);
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
			if (!stan_status_matches_fingerprint($status, $sourceFingerprint)) {
				$gateReport = build_stan_build_gate_report($projectRoot, $configPath, $sourceFingerprint);
				maybe_autostart_stan_worker($projectRoot, $paths);
			}
		}
	}

	if ($gateReport !== null) {
		if ((int) ($gateReport['compile_error_count'] ?? 0) > 0) {
			scpp_fail(implode(PHP_EOL, render_stan_compile_error_lines($gateReport)) . PHP_EOL, 1);
		}
		return $gateReport;
	}

	$status = read_json_file($paths['status_path']);
	if (!stan_status_matches_fingerprint($status, $sourceFingerprint)) {
		scpp_fail('STAN pre-build check timed out while waiting for fresh analysis state.' . PHP_EOL, 2);
	}
	maybe_autostart_stan_worker($projectRoot, $paths);
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
	$timings = is_array($diagnosticResult['timings_ms'] ?? null) ? $diagnosticResult['timings_ms'] : [];
	return [
		'project_root' => normalize_path((string) ($diagnosticResult['project_root'] ?? $projectRoot)),
		'php_profile' => (string) ($diagnosticResult['php_profile'] ?? ''),
		'source_fingerprint' => 'debug-override',
		'run_id' => build_stan_worker_run_id(),
		'analysis_mode' => 'override',
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
		'timings_ms' => $timings,
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
		maybe_autostart_stan_worker($projectRoot, $paths);
		return build_stan_cli_result_from_report($projectRoot, $configPath, $report);
	}
	$report = build_stan_worker_report($projectRoot, $configPath, $sourceFingerprint);
	$report = write_stan_report_file_atomic($paths['report_path'], $report);
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
	maybe_autostart_stan_worker($projectRoot, $paths);
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
		} elseif (in_array($kind, ['local_type_morph_warning', 'fixed_width_integer_literal_range', 'fixed_width_integer_assignment', 'enum_assignment', 'enum_comparison', 'unsupported_hash_key_type'], true)) {
			$counts['local_type_warning_count']++;
		} elseif ($kind === 'property_type_morph_warning') {
			$counts['property_type_warning_count']++;
		} elseif ($kind === 'unresolved_property_read' || $kind === 'invalid_property_read') {
			$counts['property_read_warning_count']++;
		} elseif ($kind === 'initialization_warning') {
			$counts['initialization_warning_count']++;
		} elseif (in_array($kind, ['unresolved_call', 'unresolved_static_call', 'unresolved_method_call', 'argument_count_mismatch', 'argument_type_mismatch', 'unchecked_wrapper_boundary', 'unchecked_wrapper_argument', 'dynamic_shape_boundary', 'static_instance_misuse', 'member_visibility_violation', 'unresolved_property_write'], true)) {
			$counts['call_site_warning_count']++;
		} elseif ($kind === 'unchecked_wrapper_property_boundary') {
			$counts['property_type_warning_count']++;
		} elseif (in_array($kind, ['return_type_mismatch', 'missing_return', 'unchecked_wrapper_return'], true)) {
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
		'timings_ms' => is_array($report['timings_ms'] ?? null) ? $report['timings_ms'] : [],
		'analysis_mode' => (string) ($report['analysis_mode'] ?? 'full'),
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

function normalize_build_mode_name(string $mode, string $source = 'build.mode'): string
{
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

	scpp_fail('Unsupported ' . $source . ' `' . $mode . '`; expected `debug`, `dev`, `development`, or `release`.' . PHP_EOL, 1);
}

function resolve_build_mode(array $config): string
{
	$mode = $config['build']['mode'] ?? 'debug';
	if (!is_string($mode)) {
		scpp_fail('Invalid build.mode in ' . SCPP_PROJECT_CONFIG . '; expected a string.' . PHP_EOL, 1);
	}

	return normalize_build_mode_name($mode, 'build.mode in ' . SCPP_PROJECT_CONFIG);
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

/** @param array{command:string,kind:string,launcher?:?string,linker_flags?:list<string>,archiver?:?string} $compiler @return list<string> */
function resolve_clang_time_trace_flags(array $compiler): array
{
	$enabled = getenv('SCPP_CLANG_TIME_TRACE');
	if (!is_string($enabled) || trim($enabled) === '' || !scpp_env_truthy($enabled)) {
		return [];
	}
	if (($compiler['kind'] ?? '') !== 'gnu_like') {
		scpp_fail('SCPP_CLANG_TIME_TRACE requires a GNU-like clang++ compiler.' . PHP_EOL, 2);
	}
	$command = strtolower(basename(str_replace('\\', '/', (string) ($compiler['command'] ?? ''))));
	if (!str_contains($command, 'clang')) {
		scpp_fail('SCPP_CLANG_TIME_TRACE requires clang++; current compiler is `' . (string) ($compiler['command'] ?? '') . '`.' . PHP_EOL, 2);
	}
	$flags = ['-ftime-trace'];
	$granularity = getenv('SCPP_CLANG_TIME_TRACE_GRANULARITY_US');
	if (is_string($granularity) && trim($granularity) !== '') {
		$value = trim($granularity);
		if (!preg_match('/^[1-9][0-9]*$/', $value)) {
			scpp_fail('Invalid SCPP_CLANG_TIME_TRACE_GRANULARITY_US value `' . $granularity . '`; expected a positive integer microsecond threshold.' . PHP_EOL, 2);
		}
		$flags[] = '-ftime-trace-granularity=' . $value;
	}
	return $flags;
}

/** @return array{command:string,kind:string,launcher:?string,linker_flags:list<string>,archiver:?string}|null */
function detect_default_compiler(): ?array
{
	$candidates = match (PHP_OS_FAMILY) {
		'Windows' => ['g++', 'clang++', 'cl'],
		'Darwin' => ['clang++', 'g++', 'c++'],
		default => ['clang++', 'g++', 'c++'],
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
function render_build_ninja(string $projectRoot, string $repoRoot, string $buildDir, string $generatedDir, array $generatedUnits, array $nativeCppUnits, string $outputName, array $compiler, string $buildMode, array $runtimeConfig, array $projectLibraryFlags = [], ?array $fastcgiBuild = null, array $options = ['compile_runtime' => true, 'compile_dependencies' => true], string $runtimePlacement = 'reuse', array $projectModuleReport = [], array $objectCacheReport = []): string
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
	$clangTimeTraceFlags = resolve_clang_time_trace_flags($compiler);
	$fastcgiCxxFlags = is_array($fastcgiBuild['cxxflags'] ?? null) ? $fastcgiBuild['cxxflags'] : [];
	$fastcgiLdFlags = is_array($fastcgiBuild['ldflags'] ?? null) ? $fastcgiBuild['ldflags'] : [];
	$baseLinkFlags = $linkerFlags;
	$binaryLinkFlags = array_merge($baseLinkFlags, $projectLibraryFlags);
	if (($runtimeBuild['kind'] ?? null) === 'object' && is_array($runtimeBuild['link_flags'] ?? null)) {
		$binaryLinkFlags = array_merge($binaryLinkFlags, $runtimeBuild['link_flags']);
	}
	if (is_string($runtimeBuild['rpath_dir'] ?? null) && $runtimeBuild['rpath_dir'] !== '') {
		$binaryLinkFlags[] = '-Wl,-rpath,' . ninja_escape_path($runtimeBuild['rpath_dir']);
	}
	foreach ($sharedRuntimeModules as $moduleSpec) {
		if (is_string($moduleSpec['rpath_dir'] ?? null) && $moduleSpec['rpath_dir'] !== '') {
			$binaryLinkFlags[] = '-Wl,-rpath,' . ninja_escape_path((string) $moduleSpec['rpath_dir']);
		}
	}
	$binaryLinkFlags = array_values(array_unique($binaryLinkFlags));
	$projectModuleCompileSurfaceDeps = collect_project_module_compile_surface_deps($projectModuleReport);
	$restoredCacheInputsByObject = object_cache_restored_cache_inputs_by_object($objectCacheReport);

	$lines = [];
	$lines[] = 'cxx = ' . $compilerCommand;
	if (is_string($compilerLauncher) && $compilerLauncher !== '') {
		$lines[] = 'cxx_launcher = ' . $compilerLauncher;
	}
	$lines[] = 'cxxflags = ' . build_compiler_flags($compiler['kind'], $buildMode, $runtimeIncludeDir, $generatedIncludeDir) . ($appRuntimeCxxFlags !== [] ? ' ' . implode(' ', $appRuntimeCxxFlags) : '') . ($clangTimeTraceFlags !== [] ? ' ' . implode(' ', $clangTimeTraceFlags) : '');
	$lines[] = 'runtime_cxxflags = ' . build_runtime_compiler_flags($compiler['kind'], $buildMode, $runtimeIncludeDir) . ($runtimeExtraCxxFlags !== [] ? ' ' . implode(' ', $runtimeExtraCxxFlags) : '') . ($clangTimeTraceFlags !== [] ? ' ' . implode(' ', $clangTimeTraceFlags) : '');
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
		$lines[] = '  command = ' . $wrapNinjaCommand(compiler_invocation_prefix($compiler) . ' $cxx $cxxflags -MD -MF $out.d -MT $out -x c++-header $in -o $out');
		$lines[] = '  depfile = $out.d';
		$lines[] = '  deps = gcc';
		$lines[] = '  description = PCH $out';
		$lines[] = '';
		if ($options['compile_runtime']) {
			$lines[] = 'rule compile_pch_runtime';
			$lines[] = '  command = ' . $wrapNinjaCommand(compiler_invocation_prefix($compiler) . ' $cxx $runtime_cxxflags -MD -MF $out.d -MT $out -x c++-header $in -o $out');
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
			$lines[] = '  command = ' . $wrapNinjaCommand(compiler_invocation_prefix($compiler) . ' $cxx $runtime_cxxflags' . ($usePch ? ' $runtime_pchflags' : '') . ' -MMD -MF $out.d -MT $out -c $in -o $out');
			$lines[] = '  depfile = $out.d';
			$lines[] = '  deps = gcc';
		}
		$lines[] = '  description = CXX $out';
		$lines[] = '';
		if ($runtimeBuild['kind'] === 'shared') {
			$lines[] = 'rule compile_runtime';
			$lines[] = '  command = ' . $wrapNinjaCommand(compiler_invocation_prefix($compiler) . ' $cxx $runtime_cxxflags' . ($usePch ? ' $runtime_pchflags' : '') . ' -MMD -MF $out.d -MT $out -c $in -o $out');
			$lines[] = '  depfile = $out.d';
			$lines[] = '  deps = gcc';
			$lines[] = '  description = CXX $out';
			$lines[] = '';
			$lines[] = 'rule link_runtime_shared';
			$lines[] = '  command = ' . $wrapNinjaCommand(compiler_invocation_prefix($compiler) . ' $cxx $base_ldflags $in $runtime_ldflags -o $out');
			$lines[] = '  description = LINK $out';
			$lines[] = '';
		}
	}
	$lines[] = 'rule compile';
	if ($compiler['kind'] === 'msvc') {
		$lines[] = '  command = ' . $wrapNinjaCommand(compiler_invocation_prefix($compiler) . ' $cxx $cxxflags $more_cxxflags /c $in /Fo$out');
	} else {
		$lines[] = '  command = ' . $wrapNinjaCommand(compiler_invocation_prefix($compiler) . ' $cxx $cxxflags' . ($usePch ? ' $app_pchflags' : '') . ' $more_cxxflags -MMD -MF $out.d -MT $out -c $in -o $out');
		$lines[] = '  depfile = $out.d';
		$lines[] = '  deps = gcc';
	}
	$lines[] = '  description = CXX $out';
	$lines[] = '';
	$lines[] = 'rule link';
	if ($compiler['kind'] === 'msvc') {
		$lines[] = '  command = ' . $wrapNinjaCommand(compiler_invocation_prefix($compiler) . ' $cxx /nologo $in $ldflags /Fe$out');
	} else {
		$lines[] = '  command = ' . $wrapNinjaCommand(compiler_invocation_prefix($compiler) . ' $cxx $in $ldflags -o $out');
	}
	$lines[] = '  description = LINK $out';
	$lines[] = '';
	if ($fastcgiBuild !== null) {
		$lines[] = 'rule compile_fcgi';
		$lines[] = '  command = ' . $wrapNinjaCommand(compiler_invocation_prefix($compiler) . ' $cxx $cxxflags' . ($usePch ? ' $app_pchflags' : '') . ' $fcgi_cxxflags -MMD -MF $out.d -MT $out -c $in -o $out');
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
	$generatedUnitsByObject = [];
	foreach ($generatedUnits as $unit) {
		$objectPath = normalize_path((string) ($unit['object_path'] ?? ''));
		if ($objectPath !== '') {
			$generatedUnitsByObject[$objectPath][] = $unit;
		}
	}
	$emittedGeneratedObjects = [];
	foreach ($generatedUnits as $unit) {
		if (!$options['compile_dependencies'] && normalize_path($unit['project_root']) !== normalize_path($projectRoot)) {
			$objectPaths[] = ninja_escape_path(build_ninja_relative_path($projectRoot, $buildDir, $unit['object_path']));
			continue;
		}
		$objectAbs = normalize_path((string) ($unit['object_path'] ?? ''));
		$objectPath = build_ninja_relative_path($projectRoot, $buildDir, $objectAbs);
		$objectPaths[] = ninja_escape_path($objectPath);
		if (isset($emittedGeneratedObjects[$objectAbs])) {
			continue;
		}
		$emittedGeneratedObjects[$objectAbs] = true;
		$members = $generatedUnitsByObject[$objectAbs] ?? [$unit];
		$compileSource = is_string($unit['compile_source_path'] ?? null) && trim((string) $unit['compile_source_path']) !== ''
			? normalize_path((string) $unit['compile_source_path'])
			: normalize_path((string) ($unit['generated_cpp'] ?? ''));
		$generatedCpp = build_ninja_relative_path($projectRoot, $buildDir, $compileSource);
		$objectRelative = normalize_config_path(relative_path($projectRoot, $objectAbs));
		if (isset($restoredCacheInputsByObject[$objectRelative])) {
			$cacheObject = normalize_path($projectRoot . '/' . normalize_config_path($restoredCacheInputsByObject[$objectRelative]));
			$lines[] = 'build ' . ninja_escape_path($objectPath) . ': phony ' . ninja_escape_path(build_ninja_relative_path($projectRoot, $buildDir, $cacheObject));
			continue;
		}
		$implicitDeps = [ninja_escape_path($runtimeSignatureStamp)];
		if ($usePch) {
			$implicitDeps[] = ninja_escape_path($appPchArtifact);
		}
		$forceIncludeHeaders = [];
		foreach ($members as $member) {
			$memberGeneratedCpp = normalize_path((string) ($member['generated_cpp'] ?? ''));
			if ($memberGeneratedCpp !== '' && $memberGeneratedCpp !== $compileSource) {
				$implicitDeps[] = ninja_escape_path(build_ninja_relative_path($projectRoot, $buildDir, $memberGeneratedCpp));
			}
			$projectLabel = project_context_report_label($projectRoot, (string) ($member['project_root'] ?? ''));
			$sourceKey = project_module_source_key($projectLabel, (string) ($member['relative_php'] ?? ''));
			foreach ($projectModuleCompileSurfaceDeps[$sourceKey] ?? [] as $moduleSurfaceDep) {
				$moduleSurfacePath = is_absolute_path($moduleSurfaceDep)
					? normalize_path($moduleSurfaceDep)
					: normalize_path($projectRoot . '/' . normalize_config_path($moduleSurfaceDep));
				$implicitDeps[] = ninja_escape_path(build_ninja_relative_path($projectRoot, $buildDir, $moduleSurfacePath));
			}
			$unitForceIncludeHeader = is_string($member['force_include_header'] ?? null) ? $member['force_include_header'] : null;
			if ($unitForceIncludeHeader !== null && $unitForceIncludeHeader !== '') {
				$forceIncludeHeaders[] = build_ninja_relative_path($projectRoot, $buildDir, $unitForceIncludeHeader);
			}
		}
		$implicitDeps = array_values(array_unique($implicitDeps));
		$lines[] = 'build ' . ninja_escape_path($objectPath) . ': compile ' . ninja_escape_path($generatedCpp) . ' | ' . implode(' ', $implicitDeps);
		$forceIncludeHeaders = array_values(array_unique($forceIncludeHeaders));
		if ($forceIncludeHeaders !== []) {
			$lines[] = '  more_cxxflags = ' . build_force_include_flags($compiler['kind'], $forceIncludeHeaders);
		}
	}
	foreach ($nativeCppUnits as $nativeUnit) {
		if (!$options['compile_dependencies'] && normalize_path($nativeUnit['project_root']) !== normalize_path($projectRoot)) {
			$objectPaths[] = ninja_escape_path(build_ninja_relative_path($projectRoot, $buildDir, $nativeUnit['object_path']));
			continue;
		}
		$nativeRelative = build_ninja_relative_path($projectRoot, $buildDir, $nativeUnit['source_path']);
		$nativeObject = build_ninja_relative_path($projectRoot, $buildDir, $nativeUnit['object_path']);
		$nativeObjectRelative = normalize_config_path(relative_path($projectRoot, normalize_path((string) ($nativeUnit['object_path'] ?? ''))));
		if (isset($restoredCacheInputsByObject[$nativeObjectRelative])) {
			$cacheObject = normalize_path($projectRoot . '/' . normalize_config_path($restoredCacheInputsByObject[$nativeObjectRelative]));
			$lines[] = 'build ' . ninja_escape_path($nativeObject) . ': phony ' . ninja_escape_path(build_ninja_relative_path($projectRoot, $buildDir, $cacheObject));
			$objectPaths[] = ninja_escape_path($nativeObject);
			continue;
		}
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
	$objectPaths = array_values(array_unique($objectPaths));
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
		$fcgiObjects = array_values(array_unique($fcgiObjects));
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
	if (in_array('ui', $modules, true)) {
		$lines[] = '#include "modules/ui/ui.cpp"';
	}
	if (in_array('webview', $modules, true)) {
		$lines[] = '#include "modules/webview/webview.cpp"';
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
	if (runtime_tasks_default_worker_pool_size($runtimeConfig) !== 0) {
		return false;
	}

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

/** @return array{enabled:bool,cflags:list<string>,ldflags:list<string>,compile_defines:list<string>} */
function resolve_runtime_ui_build_spec(): array
{
	if (PHP_OS_FAMILY === 'Linux') {
		$pkgConfig = find_command_path(['pkg-config']);
		if ($pkgConfig === null) {
			return [
				'enabled' => false,
				'cflags' => [],
				'ldflags' => [],
				'compile_defines' => ['-DSCPP_HAS_UI=0'],
			];
		}

		$cflagsOutput = shell_exec(escapeshellarg($pkgConfig) . ' --cflags gtk+-3.0 2>/dev/null');
		$libsOutput = shell_exec(escapeshellarg($pkgConfig) . ' --libs gtk+-3.0 2>/dev/null');
		if (!is_string($libsOutput) || trim($libsOutput) === '') {
			return [
				'enabled' => false,
				'cflags' => [],
				'ldflags' => [],
				'compile_defines' => ['-DSCPP_HAS_UI=0'],
			];
		}

		return [
			'enabled' => true,
			'cflags' => is_string($cflagsOutput) ? split_shell_tokens($cflagsOutput) : [],
			'ldflags' => split_shell_tokens($libsOutput),
			'compile_defines' => ['-DSCPP_HAS_UI=1', '-DSCPP_UI_BACKEND_GTK=1'],
		];
	}

	if (PHP_OS_FAMILY === 'Darwin') {
		return [
			'enabled' => true,
			'cflags' => ['-x', 'objective-c++'],
			'ldflags' => ['-framework', 'Cocoa'],
			'compile_defines' => ['-DSCPP_HAS_UI=1', '-DSCPP_UI_BACKEND_APPKIT=1'],
		];
	}

	if (PHP_OS_FAMILY === 'Windows') {
		return [
			'enabled' => true,
			'cflags' => [],
			'ldflags' => ['user32.lib', 'gdi32.lib', 'shell32.lib', 'ole32.lib'],
			'compile_defines' => ['-DSCPP_HAS_UI=1', '-DSCPP_UI_BACKEND_WIN32=1'],
		];
	}

	return [
		'enabled' => true,
		'cflags' => [],
		'ldflags' => [],
		'compile_defines' => ['-DSCPP_HAS_UI=1'],
	];
}

/**
 * @param ?callable(list<string>):?string $commandFinder
 * @param ?callable(string):mixed $shellRunner
 * @return array{enabled:bool,backend:string,cflags:list<string>,ldflags:list<string>,compile_defines:list<string>,diagnostics:list<string>}
 */
function resolve_runtime_webview_build_spec(?string $osFamily = null, ?callable $commandFinder = null, ?callable $shellRunner = null): array
{
	$osFamily = $osFamily ?? PHP_OS_FAMILY;
	$commandFinder = $commandFinder ?? static fn (array $commands): ?string => find_command_path($commands);
	$shellRunner = $shellRunner ?? static fn (string $command): mixed => shell_exec($command);

	if ($osFamily === 'Linux') {
		$pkgConfig = $commandFinder(['pkg-config']);
		if ($pkgConfig === null) {
			return [
				'enabled' => false,
				'backend' => 'none',
				'cflags' => [],
				'ldflags' => [],
				'compile_defines' => ['-DSCPP_HAS_WEBVIEW=0'],
				'diagnostics' => [
					'WebView disabled on Linux: pkg-config was not found. Install pkg-config and WebKitGTK development files such as libwebkit2gtk-4.1-dev on Debian/Ubuntu or webkit2gtk4.1-devel on Fedora.',
				],
			];
		}

		foreach (['webkit2gtk-4.1', 'webkit2gtk-4.0'] as $packageName) {
			$cflagsOutput = $shellRunner(escapeshellarg($pkgConfig) . ' --cflags ' . escapeshellarg($packageName) . ' 2>/dev/null');
			$libsOutput = $shellRunner(escapeshellarg($pkgConfig) . ' --libs ' . escapeshellarg($packageName) . ' 2>/dev/null');
			if (!is_string($libsOutput) || trim($libsOutput) === '') {
				continue;
			}
			return [
				'enabled' => true,
				'backend' => 'webkitgtk',
				'cflags' => is_string($cflagsOutput) ? split_shell_tokens($cflagsOutput) : [],
				'ldflags' => split_shell_tokens($libsOutput),
				'compile_defines' => ['-DSCPP_HAS_WEBVIEW=1', '-DSCPP_WEBVIEW_BACKEND_WEBKITGTK=1'],
				'diagnostics' => [],
			];
		}

		return [
			'enabled' => false,
			'backend' => 'none',
			'cflags' => [],
			'ldflags' => [],
			'compile_defines' => ['-DSCPP_HAS_WEBVIEW=0'],
			'diagnostics' => [
				'WebView disabled on Linux: WebKitGTK pkg-config package webkit2gtk-4.1 or webkit2gtk-4.0 was not found. Install libwebkit2gtk-4.1-dev on Debian/Ubuntu or webkit2gtk4.1-devel on Fedora.',
			],
		];
	}

	if ($osFamily === 'Darwin') {
		return [
			'enabled' => true,
			'backend' => 'wkwebview',
			'cflags' => ['-x', 'objective-c++'],
			'ldflags' => ['-framework', 'WebKit'],
			'compile_defines' => ['-DSCPP_HAS_WEBVIEW=1', '-DSCPP_WEBVIEW_BACKEND_WKWEBVIEW=1'],
			'diagnostics' => [],
		];
	}

	if ($osFamily === 'Windows') {
		$webview2 = resolve_windows_webview2_sdk();
		return [
			'enabled' => $webview2 !== null,
			'backend' => 'webview2',
			'cflags' => $webview2 === null ? [] : ['-I' . $webview2['include_dir']],
			'ldflags' => $webview2 === null ? [] : [$webview2['loader_lib'], 'advapi32.lib', 'ole32.lib', 'uuid.lib'],
			'compile_defines' => $webview2 === null ? ['-DSCPP_HAS_WEBVIEW=0'] : ['-DSCPP_HAS_WEBVIEW=1', '-DSCPP_WEBVIEW_BACKEND_WEBVIEW2=1'],
			'diagnostics' => $webview2 === null ? [
				'WebView disabled on Windows: WebView2 SDK headers or loader library were not found. Install the Microsoft.Web.WebView2 NuGet package or set SCPP_WEBVIEW2_SDK_DIR to a restored package root; app users still only need the installed Microsoft Edge WebView2 Runtime.',
			] : [],
		];
	}

	return [
		'enabled' => true,
		'backend' => 'facade',
		'cflags' => [],
		'ldflags' => [],
		'compile_defines' => ['-DSCPP_HAS_WEBVIEW=1'],
		'diagnostics' => [],
	];
}

/** @return array{include_dir:string,loader_lib:string}|null */
function resolve_windows_webview2_sdk(): ?array
{
	$candidates = [];
	$envRoot = getenv('SCPP_WEBVIEW2_SDK_DIR');
	if (is_string($envRoot) && trim($envRoot) !== '') {
		$candidates[] = normalize_path(trim($envRoot));
	}
	$candidates[] = normalize_path(resolve_repo_root() . '/.nuget-packages/Microsoft.Web.WebView2');
	$candidates[] = normalize_path(getcwd() . '/.nuget-packages/Microsoft.Web.WebView2');

	foreach (array_values(array_unique($candidates)) as $root) {
		$includeDir = normalize_path($root . '/build/native/include');
		if (!is_dir($includeDir)) {
			continue;
		}
		foreach ([
			$root . '/build/native/x64/WebView2Loader.dll.lib',
			$root . '/build/native/x64/WebView2LoaderStatic.lib',
		] as $loaderLib) {
			$loaderLib = normalize_path($loaderLib);
			if (is_file($loaderLib)) {
				return [
					'include_dir' => $includeDir,
					'loader_lib' => $loaderLib,
				];
			}
		}
	}

	return null;
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
		$extraCxxFlags[] = '-DSCPP_TASKS_DEFAULT_WORKER_POOL_SIZE=' . runtime_tasks_default_worker_pool_size($runtimeConfig);
	} else {
		$extraCxxFlags[] = '-DSCPP_HAS_TASKS=0';
		$extraCxxFlags[] = '-DSCPP_TASKS_DEFAULT_WORKER_POOL_SIZE=0';
	}
	if (in_array('ui', $modules, true)) {
		$uiBuild = resolve_runtime_ui_build_spec();
		if (!$uiBuild['enabled']) {
			scpp_fail('Runtime module `ui` is enabled in ' . SCPP_PROJECT_CONFIG . ' but no supported GTK development environment was found. On Linux, install GTK 3 development files that provide pkg-config package `gtk+-3.0` or disable the module.' . PHP_EOL, 1);
		}
		$extraCxxFlags = array_merge($extraCxxFlags, $uiBuild['compile_defines'], $uiBuild['cflags']);
		$extraLinkFlags = array_merge($extraLinkFlags, $uiBuild['ldflags']);
	} else {
		$extraCxxFlags[] = '-DSCPP_HAS_UI=0';
	}
	if (in_array('webview', $modules, true)) {
		$webviewBuild = resolve_runtime_webview_build_spec();
		if (!$webviewBuild['enabled']) {
			scpp_fail('Runtime module `webview` is enabled in ' . SCPP_PROJECT_CONFIG . ' but no supported WebView development environment was found. On Linux, install WebKitGTK development files that provide pkg-config package `webkit2gtk-4.1` or `webkit2gtk-4.0`. On Windows, restore the Microsoft.Web.WebView2 NuGet package under the repo or project .nuget-packages directory, or set SCPP_WEBVIEW2_SDK_DIR.' . PHP_EOL, 1);
		}
		$extraCxxFlags = array_merge($extraCxxFlags, $webviewBuild['compile_defines'], $webviewBuild['cflags']);
		$extraLinkFlags = array_merge($extraLinkFlags, $webviewBuild['ldflags']);
	} else {
		$extraCxxFlags[] = '-DSCPP_HAS_WEBVIEW=0';
	}
	if (call_depth_guard_enabled($runtimeConfig, $buildMode)) {
		$extraCxxFlags[] = '-DSCPP_ENABLE_CALL_DEPTH_GUARD=1';
		$extraCxxFlags[] = '-DSCPP_MAX_CALL_DEPTH=' . call_depth_guard_limit($runtimeConfig);
	} else {
		$extraCxxFlags[] = '-DSCPP_ENABLE_CALL_DEPTH_GUARD=0';
	}
	$extraLinkFlags = adapt_windows_runtime_link_flags_for_compiler($extraLinkFlags, $compiler);

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

/** @param list<string> $flags @param array{command:string,kind:string,launcher?:?string,linker_flags?:list<string>,archiver?:?string} $compiler @return list<string> */
function adapt_windows_runtime_link_flags_for_compiler(array $flags, array $compiler): array
{
	if (PHP_OS_FAMILY !== 'Windows' || ($compiler['kind'] ?? '') !== 'gnu_like') {
		return $flags;
	}

	$adapted = [];
	foreach ($flags as $flag) {
		if (preg_match('/^([A-Za-z0-9_]+)\\.lib$/', $flag, $matches) === 1) {
			$adapted[] = '-l' . $matches[1];
		} else {
			$adapted[] = $flag;
		}
	}
	return $adapted;
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
		$extraCxxFlags[] = '-DSCPP_TASKS_DEFAULT_WORKER_POOL_SIZE=' . runtime_tasks_default_worker_pool_size($runtimeConfig);
	}
	$extraLinkFlags = adapt_windows_runtime_link_flags_for_compiler($extraLinkFlags, $compiler);

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
		'runtime-v5',
		'kind:' . $compiler['kind'],
		'command:' . $compiler['command'],
		'mode:' . $buildMode,
		'launcher:' . (is_string($compiler['launcher'] ?? null) ? basename(str_replace('\\', '/', $compiler['launcher'])) : ''),
		'archiver:' . (is_string($compiler['archiver'] ?? null) ? basename(str_replace('\\', '/', $compiler['archiver'])) : ''),
		'linker_flags:' . implode(' ', is_array($compiler['linker_flags'] ?? null) ? $compiler['linker_flags'] : []),
		'runtime_languages:' . implode(',', is_array($runtimeConfig['languages'] ?? null) ? $runtimeConfig['languages'] : []),
		'php_profile:' . resolve_php_runtime_profile($runtimeConfig),
		'runtime_modules:' . implode(',', is_array($runtimeConfig['modules'] ?? null) ? $runtimeConfig['modules'] : []),
		'tasks_default_worker_pool_size:' . runtime_tasks_default_worker_pool_size($runtimeConfig),
		'call_depth_guard:' . (call_depth_guard_enabled($runtimeConfig, $buildMode) ? '1' : '0'),
		'max_call_depth:' . call_depth_guard_limit($runtimeConfig),
	];

	sort($parts, SORT_STRING);
	return substr(hash('sha256', implode("\n", $parts)), 0, 16);
}

function call_depth_guard_enabled(array $runtimeConfig, string $buildMode): bool
{
	$safety = is_array($runtimeConfig['safety'] ?? null) ? $runtimeConfig['safety'] : [];
	if (array_key_exists('call_depth_guard', $safety)) {
		return (bool) $safety['call_depth_guard'];
	}
	return $buildMode !== 'release';
}

function call_depth_guard_limit(array $runtimeConfig): int
{
	$safety = is_array($runtimeConfig['safety'] ?? null) ? $runtimeConfig['safety'] : [];
	$value = $safety['max_call_depth'] ?? null;
	if (is_int($value)) {
		return max(1, $value);
	}
	if (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
		return max(1, (int) $value);
	}
	return 4096;
}

function runtime_tasks_default_worker_pool_size(array $runtimeConfig): int
{
	$tasks = is_array($runtimeConfig['tasks'] ?? null) ? $runtimeConfig['tasks'] : [];
	return normalize_runtime_tasks_default_worker_pool_size($tasks['default_worker_pool_size'] ?? null);
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
				[$objectPath],
				$runtimeLinkFlags,
				['-o', $linkOutputPath]
			);
			scpp_run_or_fail_process($linkCommand, $projectRoot, 'Failed to link runtime artifact.');
			if ($publishLinkedArtifactAtomically) {
				replace_file_atomically($tmpArtifactPath, $artifactPath, 'Failed to publish runtime artifact');
			}
		} else {
			$tmpArtifactPath = $artifactPath . '.tmp.' . bin2hex(random_bytes(4));
			$compileCommand = $compiler['kind'] === 'msvc'
				? array_merge($compileCommand, ['/c', $sourcePath, '/Fo' . $tmpArtifactPath])
				: array_merge($compileCommand, ['-c', $sourcePath, '-o', $tmpArtifactPath]);
			scpp_run_or_fail_process($compileCommand, $projectRoot, 'Failed to compile runtime artifact.');
			replace_file_atomically($tmpArtifactPath, $artifactPath, 'Failed to publish runtime artifact');
		}
	} finally {
		flock($lockHandle, LOCK_UN);
		fclose($lockHandle);
	}

	return normalize_config_path(relative_path($projectRoot, $artifactPath));
}

function runtime_metadata_export_path_for_artifact(string $projectRoot, string $runtimeArtifactPath): string
{
	$artifactPath = normalize_path($projectRoot . '/' . normalize_config_path($runtimeArtifactPath));
	return dirname($artifactPath) . '/metadata/runtime_metadata.json';
}

function render_runtime_metadata_export_json(string $buildMode, string $runtimeFamily): string
{
	$profile = $runtimeFamily;
	$metadata = [
		'artifact_kind' => 'simple_cpp_runtime_metadata',
		'metadata_kind' => 'simple_cpp_runtime_metadata',
		'metadata_key' => 'simple_cpp_runtime_metadata:' . $profile . ':' . $buildMode . ':m4',
		'schema_version' => 1,
		'runtime_profile' => $profile,
		'build_mode' => $buildMode,
		'metadata_version' => 1,
		'source_kind' => 'simple_cpp_export',
		'primitive_type_count' => 4,
		'primitive_types' => [
			[
				'source_type' => 'int',
				'runtime_type' => 'scpp::int_t',
				'llvm_type' => 'i64',
				'abi_storage' => 'i64',
				'abi_fragment' => 'i64',
				'bit_width' => 64,
				'signedness' => 'signed',
				'capabilities' => [
					'storage_abi_known',
					'lifetime_abi_known',
					'abi_lowerable',
					'hashable',
					'comparable',
					'copyable',
					'movable',
					'destructible',
					'layout_known',
				],
			],
			[
				'source_type' => 'int64',
				'runtime_type' => 'scpp::int_t',
				'llvm_type' => 'i64',
				'abi_storage' => 'i64',
				'abi_fragment' => 'i64',
				'bit_width' => 64,
				'signedness' => 'signed',
				'capabilities' => [
					'storage_abi_known',
					'lifetime_abi_known',
					'abi_lowerable',
					'hashable',
					'comparable',
					'copyable',
					'movable',
					'destructible',
					'layout_known',
				],
			],
			[
				'source_type' => 'bool',
				'runtime_type' => 'scpp::bool_t',
				'llvm_type' => 'i1',
				'abi_storage' => 'i1',
				'abi_fragment' => 'bool',
				'bit_width' => 1,
				'signedness' => 'unsigned',
				'capabilities' => [
					'storage_abi_known',
					'lifetime_abi_known',
					'abi_lowerable',
					'hashable',
					'comparable',
					'copyable',
					'movable',
					'destructible',
					'layout_known',
				],
			],
			[
				'source_type' => 'string',
				'runtime_type' => 'scpp::string_t',
				'llvm_type' => 'ptr',
				'abi_storage' => 'ptr',
				'abi_fragment' => 'string',
				'bit_width' => 0,
				'signedness' => 'none',
				'capabilities' => [
					'storage_abi_known',
					'lifetime_abi_known',
					'abi_lowerable',
					'hashable',
					'comparable',
					'copyable',
					'movable',
					'destructible',
					'layout_known',
				],
			],
		],
		'runtime_constant_count' => 19,
		'runtime_constants' => [
			['source_name' => 'DBG_TYPE', 'source_type' => 'int', 'llvm_type' => 'i64', 'llvm_literal' => '1', 'runtime_symbol' => 'scpp::php::DBG_TYPE'],
			['source_name' => 'DBG_VALUE', 'source_type' => 'int', 'llvm_type' => 'i64', 'llvm_literal' => '2', 'runtime_symbol' => 'scpp::php::DBG_VALUE'],
			['source_name' => 'DBG_SHAPE', 'source_type' => 'int', 'llvm_type' => 'i64', 'llvm_literal' => '4', 'runtime_symbol' => 'scpp::php::DBG_SHAPE'],
			['source_name' => 'DBG_FIELDS', 'source_type' => 'int', 'llvm_type' => 'i64', 'llvm_literal' => '8', 'runtime_symbol' => 'scpp::php::DBG_FIELDS'],
			['source_name' => 'DBG_KEYS', 'source_type' => 'int', 'llvm_type' => 'i64', 'llvm_literal' => '16', 'runtime_symbol' => 'scpp::php::DBG_KEYS'],
			['source_name' => 'DBG_LEN', 'source_type' => 'int', 'llvm_type' => 'i64', 'llvm_literal' => '32', 'runtime_symbol' => 'scpp::php::DBG_LEN'],
			['source_name' => 'DBG_SOURCE', 'source_type' => 'int', 'llvm_type' => 'i64', 'llvm_literal' => '64', 'runtime_symbol' => 'scpp::php::DBG_SOURCE'],
			['source_name' => 'DBG_CALLER', 'source_type' => 'int', 'llvm_type' => 'i64', 'llvm_literal' => '128', 'runtime_symbol' => 'scpp::php::DBG_CALLER'],
			['source_name' => 'DBG_JSON', 'source_type' => 'int', 'llvm_type' => 'i64', 'llvm_literal' => '256', 'runtime_symbol' => 'scpp::php::DBG_JSON'],
			['source_name' => 'DBG_RAW', 'source_type' => 'int', 'llvm_type' => 'i64', 'llvm_literal' => '512', 'runtime_symbol' => 'scpp::php::DBG_RAW'],
			['source_name' => 'DBG_PTR', 'source_type' => 'int', 'llvm_type' => 'i64', 'llvm_literal' => '1024', 'runtime_symbol' => 'scpp::php::DBG_PTR'],
			['source_name' => 'DBG_COMPACT', 'source_type' => 'int', 'llvm_type' => 'i64', 'llvm_literal' => '2048', 'runtime_symbol' => 'scpp::php::DBG_COMPACT'],
			['source_name' => 'DBG_DEPTH_0', 'source_type' => 'int', 'llvm_type' => 'i64', 'llvm_literal' => '65536', 'runtime_symbol' => 'scpp::php::DBG_DEPTH_0'],
			['source_name' => 'DBG_DEPTH_1', 'source_type' => 'int', 'llvm_type' => 'i64', 'llvm_literal' => '131072', 'runtime_symbol' => 'scpp::php::DBG_DEPTH_1'],
			['source_name' => 'DBG_DEPTH_2', 'source_type' => 'int', 'llvm_type' => 'i64', 'llvm_literal' => '262144', 'runtime_symbol' => 'scpp::php::DBG_DEPTH_2'],
			['source_name' => 'DBG_DEPTH_3', 'source_type' => 'int', 'llvm_type' => 'i64', 'llvm_literal' => '524288', 'runtime_symbol' => 'scpp::php::DBG_DEPTH_3'],
			['source_name' => 'DBG_DEPTH_4', 'source_type' => 'int', 'llvm_type' => 'i64', 'llvm_literal' => '1048576', 'runtime_symbol' => 'scpp::php::DBG_DEPTH_4'],
			['source_name' => 'DBG_DEPTH_5', 'source_type' => 'int', 'llvm_type' => 'i64', 'llvm_literal' => '2097152', 'runtime_symbol' => 'scpp::php::DBG_DEPTH_5'],
			['source_name' => 'DBG_DEFAULT', 'source_type' => 'int', 'llvm_type' => 'i64', 'llvm_literal' => '262343', 'runtime_symbol' => 'scpp::php::DBG_DEFAULT'],
		],
		'operator_count' => 34,
		'operators' => [
			[
				'source_key' => 'operator:+:int:int',
				'operator_id' => 'plus',
				'operator' => '+',
				'arity' => 2,
				'left_type' => 'int',
				'right_type' => 'int',
				'return_type' => 'int',
				'lowering_kind' => 'llvm_binary',
				'llvm_opcode' => 'add',
				'diagnostic_key' => 'unsupported_operator_plus',
			],
			[
				'source_key' => 'operator:-:int:int',
				'operator_id' => 'minus',
				'operator' => '-',
				'arity' => 2,
				'left_type' => 'int',
				'right_type' => 'int',
				'return_type' => 'int',
				'lowering_kind' => 'llvm_binary',
				'llvm_opcode' => 'sub',
				'diagnostic_key' => 'unsupported_operator_minus',
			],
			[
				'source_key' => 'operator:*:int:int',
				'operator_id' => 'multiply',
				'operator' => '*',
				'arity' => 2,
				'left_type' => 'int',
				'right_type' => 'int',
				'return_type' => 'int',
				'lowering_kind' => 'llvm_binary',
				'llvm_opcode' => 'mul',
				'diagnostic_key' => 'unsupported_operator_multiply',
			],
			[
				'source_key' => 'operator:/:int:int',
				'operator_id' => 'divide',
				'operator' => '/',
				'arity' => 2,
				'left_type' => 'int',
				'right_type' => 'int',
				'return_type' => 'int',
				'lowering_kind' => 'llvm_binary',
				'llvm_opcode' => 'sdiv',
				'diagnostic_key' => 'unsupported_operator_divide',
			],
			[
				'source_key' => 'operator:%:int:int',
				'operator_id' => 'modulo',
				'operator' => '%',
				'arity' => 2,
				'left_type' => 'int',
				'right_type' => 'int',
				'return_type' => 'int',
				'lowering_kind' => 'llvm_binary',
				'llvm_opcode' => 'srem',
				'diagnostic_key' => 'unsupported_operator_modulo',
			],
			[
				'source_key' => 'operator:~:int:int',
				'operator_id' => 'bitwise_not',
				'operator' => '~',
				'arity' => 1,
				'left_type' => 'int',
				'right_type' => 'int',
				'return_type' => 'int',
				'lowering_kind' => 'llvm_unary',
				'llvm_opcode' => 'xor',
				'diagnostic_key' => 'unsupported_operator_bitwise_not',
			],
			[
				'source_key' => 'operator:&:int:int',
				'operator_id' => 'bitwise_and',
				'operator' => '&',
				'arity' => 2,
				'left_type' => 'int',
				'right_type' => 'int',
				'return_type' => 'int',
				'lowering_kind' => 'llvm_binary',
				'llvm_opcode' => 'and',
				'diagnostic_key' => 'unsupported_operator_bitwise_and',
			],
			[
				'source_key' => 'operator:|:int:int',
				'operator_id' => 'bitwise_or',
				'operator' => '|',
				'arity' => 2,
				'left_type' => 'int',
				'right_type' => 'int',
				'return_type' => 'int',
				'lowering_kind' => 'llvm_binary',
				'llvm_opcode' => 'or',
				'diagnostic_key' => 'unsupported_operator_bitwise_or',
			],
			[
				'source_key' => 'operator:^:int:int',
				'operator_id' => 'bitwise_xor',
				'operator' => '^',
				'arity' => 2,
				'left_type' => 'int',
				'right_type' => 'int',
				'return_type' => 'int',
				'lowering_kind' => 'llvm_binary',
				'llvm_opcode' => 'xor',
				'diagnostic_key' => 'unsupported_operator_bitwise_xor',
			],
			[
				'source_key' => 'operator:<<:int:int',
				'operator_id' => 'shift_left',
				'operator' => '<<',
				'arity' => 2,
				'left_type' => 'int',
				'right_type' => 'int',
				'return_type' => 'int',
				'lowering_kind' => 'llvm_binary',
				'llvm_opcode' => 'shl',
				'diagnostic_key' => 'unsupported_operator_shift_left',
			],
			[
				'source_key' => 'operator:>>:int:int',
				'operator_id' => 'shift_right',
				'operator' => '>>',
				'arity' => 2,
				'left_type' => 'int',
				'right_type' => 'int',
				'return_type' => 'int',
				'lowering_kind' => 'llvm_binary',
				'llvm_opcode' => 'ashr',
				'diagnostic_key' => 'unsupported_operator_shift_right',
			],
			[
				'source_key' => 'operator:==:int:int',
				'operator_id' => 'equal',
				'operator' => '==',
				'arity' => 2,
				'left_type' => 'int',
				'right_type' => 'int',
				'return_type' => 'bool',
				'lowering_kind' => 'llvm_compare',
				'llvm_predicate' => 'eq',
				'diagnostic_key' => 'unsupported_operator_equal',
			],
			[
				'source_key' => 'operator:!=:int:int',
				'operator_id' => 'not_equal',
				'operator' => '!=',
				'arity' => 2,
				'left_type' => 'int',
				'right_type' => 'int',
				'return_type' => 'bool',
				'lowering_kind' => 'llvm_compare',
				'llvm_predicate' => 'ne',
				'diagnostic_key' => 'unsupported_operator_not_equal',
			],
			[
				'source_key' => 'operator:===:int:int',
				'operator_id' => 'identical',
				'operator' => '===',
				'arity' => 2,
				'left_type' => 'int',
				'right_type' => 'int',
				'return_type' => 'bool',
				'lowering_kind' => 'llvm_compare',
				'llvm_predicate' => 'eq',
				'diagnostic_key' => 'unsupported_operator_identical',
			],
			[
				'source_key' => 'operator:!==:int:int',
				'operator_id' => 'not_identical',
				'operator' => '!==',
				'arity' => 2,
				'left_type' => 'int',
				'right_type' => 'int',
				'return_type' => 'bool',
				'lowering_kind' => 'llvm_compare',
				'llvm_predicate' => 'ne',
				'diagnostic_key' => 'unsupported_operator_not_identical',
			],
			[
				'source_key' => 'operator:<=>:int:int',
				'operator_id' => 'spaceship',
				'operator' => '<=>',
				'arity' => 2,
				'left_type' => 'int',
				'right_type' => 'int',
				'return_type' => 'int',
				'lowering_kind' => 'runtime_call',
				'abi_symbol' => 'scpp_int_spaceship',
				'diagnostic_key' => 'unsupported_operator_spaceship',
			],
			[
				'source_key' => 'operator:===:bool:bool',
				'operator_id' => 'identical',
				'operator' => '===',
				'arity' => 2,
				'left_type' => 'bool',
				'right_type' => 'bool',
				'return_type' => 'bool',
				'lowering_kind' => 'llvm_compare',
				'llvm_predicate' => 'eq',
				'diagnostic_key' => 'unsupported_operator_identical',
			],
			[
				'source_key' => 'operator:!==:bool:bool',
				'operator_id' => 'not_identical',
				'operator' => '!==',
				'arity' => 2,
				'left_type' => 'bool',
				'right_type' => 'bool',
				'return_type' => 'bool',
				'lowering_kind' => 'llvm_compare',
				'llvm_predicate' => 'ne',
				'diagnostic_key' => 'unsupported_operator_not_identical',
			],
			[
				'source_key' => 'operator:+:string:string',
				'operator_id' => 'plus',
				'operator' => '+',
				'arity' => 2,
				'left_type' => 'string',
				'right_type' => 'string',
				'return_type' => 'string',
				'lowering_kind' => 'runtime_call',
				'abi_symbol' => 'scpp_string_concat',
				'diagnostic_key' => 'unsupported_operator_plus',
			],
			[
				'source_key' => 'operator:==:string:string',
				'operator_id' => 'equal',
				'operator' => '==',
				'arity' => 2,
				'left_type' => 'string',
				'right_type' => 'string',
				'return_type' => 'bool',
				'lowering_kind' => 'runtime_call',
				'abi_symbol' => 'scpp_string_equal',
				'diagnostic_key' => 'unsupported_operator_equal',
			],
			[
				'source_key' => 'operator:!=:string:string',
				'operator_id' => 'not_equal',
				'operator' => '!=',
				'arity' => 2,
				'left_type' => 'string',
				'right_type' => 'string',
				'return_type' => 'bool',
				'lowering_kind' => 'runtime_call',
				'abi_symbol' => 'scpp_string_not_equal',
				'diagnostic_key' => 'unsupported_operator_not_equal',
			],
			[
				'source_key' => 'operator:===:string:string',
				'operator_id' => 'identical',
				'operator' => '===',
				'arity' => 2,
				'left_type' => 'string',
				'right_type' => 'string',
				'return_type' => 'bool',
				'lowering_kind' => 'runtime_call',
				'abi_symbol' => 'scpp_string_identical',
				'diagnostic_key' => 'unsupported_operator_identical',
			],
			[
				'source_key' => 'operator:!==:string:string',
				'operator_id' => 'not_identical',
				'operator' => '!==',
				'arity' => 2,
				'left_type' => 'string',
				'right_type' => 'string',
				'return_type' => 'bool',
				'lowering_kind' => 'runtime_call',
				'abi_symbol' => 'scpp_string_not_identical',
				'diagnostic_key' => 'unsupported_operator_not_identical',
			],
			[
				'source_key' => 'operator:<:string:string',
				'operator_id' => 'less',
				'operator' => '<',
				'arity' => 2,
				'left_type' => 'string',
				'right_type' => 'string',
				'return_type' => 'bool',
				'lowering_kind' => 'runtime_call',
				'abi_symbol' => 'scpp_string_less',
				'diagnostic_key' => 'unsupported_operator_less',
			],
			[
				'source_key' => 'operator:>:string:string',
				'operator_id' => 'greater',
				'operator' => '>',
				'arity' => 2,
				'left_type' => 'string',
				'right_type' => 'string',
				'return_type' => 'bool',
				'lowering_kind' => 'runtime_call',
				'abi_symbol' => 'scpp_string_greater',
				'diagnostic_key' => 'unsupported_operator_greater',
			],
			[
				'source_key' => 'operator:<=:string:string',
				'operator_id' => 'less_equal',
				'operator' => '<=',
				'arity' => 2,
				'left_type' => 'string',
				'right_type' => 'string',
				'return_type' => 'bool',
				'lowering_kind' => 'runtime_call',
				'abi_symbol' => 'scpp_string_less_equal',
				'diagnostic_key' => 'unsupported_operator_less_equal',
			],
			[
				'source_key' => 'operator:>=:string:string',
				'operator_id' => 'greater_equal',
				'operator' => '>=',
				'arity' => 2,
				'left_type' => 'string',
				'right_type' => 'string',
				'return_type' => 'bool',
				'lowering_kind' => 'runtime_call',
				'abi_symbol' => 'scpp_string_greater_equal',
				'diagnostic_key' => 'unsupported_operator_greater_equal',
			],
			[
				'source_key' => 'operator:<:int:int',
				'operator_id' => 'less',
				'operator' => '<',
				'arity' => 2,
				'left_type' => 'int',
				'right_type' => 'int',
				'return_type' => 'bool',
				'lowering_kind' => 'llvm_compare',
				'llvm_predicate' => 'slt',
				'diagnostic_key' => 'unsupported_operator_less',
			],
			[
				'source_key' => 'operator:>:int:int',
				'operator_id' => 'greater',
				'operator' => '>',
				'arity' => 2,
				'left_type' => 'int',
				'right_type' => 'int',
				'return_type' => 'bool',
				'lowering_kind' => 'llvm_compare',
				'llvm_predicate' => 'sgt',
				'diagnostic_key' => 'unsupported_operator_greater',
			],
			[
				'source_key' => 'operator:<=:int:int',
				'operator_id' => 'less_equal',
				'operator' => '<=',
				'arity' => 2,
				'left_type' => 'int',
				'right_type' => 'int',
				'return_type' => 'bool',
				'lowering_kind' => 'llvm_compare',
				'llvm_predicate' => 'sle',
				'diagnostic_key' => 'unsupported_operator_less_equal',
			],
			[
				'source_key' => 'operator:>=:int:int',
				'operator_id' => 'greater_equal',
				'operator' => '>=',
				'arity' => 2,
				'left_type' => 'int',
				'right_type' => 'int',
				'return_type' => 'bool',
				'lowering_kind' => 'llvm_compare',
				'llvm_predicate' => 'sge',
				'diagnostic_key' => 'unsupported_operator_greater_equal',
			],
			[
				'source_key' => 'operator:&&:bool:bool',
				'operator_id' => 'and',
				'operator' => '&&',
				'arity' => 2,
				'left_type' => 'bool',
				'right_type' => 'bool',
				'return_type' => 'bool',
				'lowering_kind' => 'short_circuit',
				'diagnostic_key' => 'unsupported_operator_and',
			],
			[
				'source_key' => 'operator:||:bool:bool',
				'operator_id' => 'or',
				'operator' => '||',
				'arity' => 2,
				'left_type' => 'bool',
				'right_type' => 'bool',
				'return_type' => 'bool',
				'lowering_kind' => 'short_circuit',
				'diagnostic_key' => 'unsupported_operator_or',
			],
			[
				'source_key' => 'operator:!:bool:bool',
				'operator_id' => 'not',
				'operator' => '!',
				'arity' => 1,
				'left_type' => 'bool',
				'right_type' => 'bool',
				'return_type' => 'bool',
				'lowering_kind' => 'llvm_compare',
				'llvm_predicate' => 'eq',
				'diagnostic_key' => 'unsupported_operator_not',
			],
		],
		'conversion_count' => 6,
		'conversions' => [
			[
				'conversion_id' => 'identity:int:int',
				'from_type' => 'int',
				'to_type' => 'int',
				'conversion_kind' => 'identity',
				'explicit_allowed' => true,
				'implicit_allowed' => true,
				'lossy' => false,
				'diagnostic_key' => 'conversion_identity_int',
			],
			[
				'conversion_id' => 'identity:int64:int64',
				'from_type' => 'int64',
				'to_type' => 'int64',
				'conversion_kind' => 'identity',
				'explicit_allowed' => true,
				'implicit_allowed' => true,
				'lossy' => false,
				'diagnostic_key' => 'conversion_identity_int64',
			],
			[
				'conversion_id' => 'identity:bool:bool',
				'from_type' => 'bool',
				'to_type' => 'bool',
				'conversion_kind' => 'identity',
				'explicit_allowed' => true,
				'implicit_allowed' => true,
				'lossy' => false,
				'diagnostic_key' => 'conversion_identity_bool',
			],
			[
				'conversion_id' => 'identity:string:string',
				'from_type' => 'string',
				'to_type' => 'string',
				'conversion_kind' => 'identity',
				'explicit_allowed' => true,
				'implicit_allowed' => true,
				'lossy' => false,
				'diagnostic_key' => 'conversion_identity_string',
			],
			[
				'conversion_id' => 'integer_widen:int:int64',
				'from_type' => 'int',
				'to_type' => 'int64',
				'conversion_kind' => 'integer_widen',
				'explicit_allowed' => true,
				'implicit_allowed' => true,
				'lossy' => false,
				'diagnostic_key' => 'conversion_int_to_int64',
			],
			[
				'conversion_id' => 'integer_narrow:int64:int',
				'from_type' => 'int64',
				'to_type' => 'int',
				'conversion_kind' => 'integer_narrow',
				'explicit_allowed' => true,
				'implicit_allowed' => false,
				'lossy' => false,
				'diagnostic_key' => 'conversion_int64_to_int',
			],
		],
		'type_family_count' => 5,
		'type_families' => [
			[
				'family_key' => 'type_family:vector',
				'source_name' => 'vector',
				'family_kind' => 'callable',
				'template_param_count' => 1,
				'parameters' => [
					[
						'name' => 'T',
						'kind' => 'type',
						'constraints' => [
							'storage_abi_known',
							'lifetime_abi_known',
							'abi_lowerable',
						],
					],
				],
				'storage_abi' => 'ptr',
				'bridge_namespace' => 'scpp_bridge_vector',
				'operations' => [
					[
						'operation_id' => 'create',
						'source_name' => 'vector_create',
						'return_type' => 'self',
						'source_param_signature' => '',
						'llvm_return_type' => 'ptr',
						'llvm_param_signature' => '',
						'abi_symbol_template' => 'scpp_vector_{T_ABI}_create',
					],
					[
						'operation_id' => 'append',
						'source_name' => 'vector_append',
						'return_type' => 'void',
						'source_param_signature' => 'self, T',
						'llvm_return_type' => 'void',
						'llvm_param_signature' => 'self, T_ABI',
						'abi_symbol_template' => 'scpp_vector_{T_ABI}_push',
						'method_aliases' => ['push'],
					],
					[
						'operation_id' => 'get',
						'source_name' => 'vector_get',
						'return_type' => 'T',
						'source_param_signature' => 'self, int',
						'llvm_return_type' => 'T_ABI',
						'llvm_param_signature' => 'self, int',
						'abi_symbol_template' => 'scpp_vector_{T_ABI}_get',
						'method_aliases' => ['at'],
					],
					[
						'operation_id' => 'set',
						'source_name' => 'vector_set',
						'return_type' => 'void',
						'source_param_signature' => 'self, int, T',
						'llvm_return_type' => 'void',
						'llvm_param_signature' => 'self, int, T_ABI',
						'abi_symbol_template' => 'scpp_vector_{T_ABI}_set',
					],
					[
						'operation_id' => 'unset',
						'source_name' => 'vector_unset',
						'return_type' => 'void',
						'source_param_signature' => 'self, int',
						'llvm_return_type' => 'void',
						'llvm_param_signature' => 'self, int',
						'abi_symbol_template' => 'scpp_vector_{T_ABI}_unset',
						'method_aliases' => ['unset'],
					],
					[
						'operation_id' => 'count',
						'source_name' => 'vector_count',
						'return_type' => 'int',
						'source_param_signature' => 'self',
						'llvm_return_type' => 'i64',
						'llvm_param_signature' => 'self',
						'abi_symbol_template' => 'scpp_vector_{T_ABI}_count',
						'method_aliases' => ['size', 'count'],
					],
					[
						'operation_id' => 'empty',
						'source_name' => 'vector_empty',
						'return_type' => 'bool',
						'source_param_signature' => 'self',
						'llvm_return_type' => 'i1',
						'llvm_param_signature' => 'self',
						'abi_symbol_template' => 'scpp_vector_{T_ABI}_empty',
						'method_aliases' => ['empty'],
					],
					[
						'operation_id' => 'destroy',
						'source_name' => 'vector_destroy',
						'return_type' => 'void',
						'source_param_signature' => 'self',
						'llvm_return_type' => 'void',
						'llvm_param_signature' => 'self',
						'abi_symbol_template' => 'scpp_vector_{T_ABI}_destroy',
					],
				],
			],
			[
				'family_key' => 'type_family:hash',
				'source_name' => 'hash',
				'family_kind' => 'callable',
				'template_param_count' => 2,
				'parameters' => [
					[
						'name' => 'K',
						'kind' => 'type',
						'constraints' => [
							'storage_abi_known',
							'hashable',
							'comparable',
							'abi_lowerable',
						],
					],
					[
						'name' => 'V',
						'kind' => 'type',
						'constraints' => [
							'storage_abi_known',
							'lifetime_abi_known',
							'abi_lowerable',
						],
					],
				],
				'storage_abi' => 'ptr',
				'bridge_namespace' => 'scpp_bridge_hash',
				'operations' => [
					[
						'operation_id' => 'create',
						'source_name' => 'hash_create',
						'return_type' => 'self',
						'source_param_signature' => '',
						'llvm_return_type' => 'ptr',
						'llvm_param_signature' => '',
						'abi_symbol_template' => 'scpp_hash_{K_ABI}_{V_ABI}_create',
					],
					[
						'operation_id' => 'set',
						'source_name' => 'hash_set',
						'return_type' => 'void',
						'source_param_signature' => 'self, K, V',
						'llvm_return_type' => 'void',
						'llvm_param_signature' => 'self, K_ABI, V_ABI',
						'abi_symbol_template' => 'scpp_hash_{K_ABI}_{V_ABI}_set',
					],
					[
						'operation_id' => 'get',
						'source_name' => 'hash_get',
						'return_type' => 'V',
						'source_param_signature' => 'self, K',
						'llvm_return_type' => 'V_ABI',
						'llvm_param_signature' => 'self, K_ABI',
						'abi_symbol_template' => 'scpp_hash_{K_ABI}_{V_ABI}_get',
					],
					[
						'operation_id' => 'has',
						'source_name' => 'hash_has',
						'return_type' => 'bool',
						'source_param_signature' => 'self, K',
						'llvm_return_type' => 'i1',
						'llvm_param_signature' => 'self, K_ABI',
						'abi_symbol_template' => 'scpp_hash_{K_ABI}_{V_ABI}_has',
						'method_aliases' => ['isset'],
					],
					[
						'operation_id' => 'unset',
						'source_name' => 'hash_unset',
						'return_type' => 'void',
						'source_param_signature' => 'self, K',
						'llvm_return_type' => 'void',
						'llvm_param_signature' => 'self, K_ABI',
						'abi_symbol_template' => 'scpp_hash_{K_ABI}_{V_ABI}_unset',
						'method_aliases' => ['unset'],
					],
					[
						'operation_id' => 'count',
						'source_name' => 'hash_count',
						'return_type' => 'int',
						'source_param_signature' => 'self',
						'llvm_return_type' => 'i64',
						'llvm_param_signature' => 'self',
						'abi_symbol_template' => 'scpp_hash_{K_ABI}_{V_ABI}_count',
						'method_aliases' => ['count'],
					],
					[
						'operation_id' => 'empty',
						'source_name' => 'hash_empty',
						'return_type' => 'bool',
						'source_param_signature' => 'self',
						'llvm_return_type' => 'i1',
						'llvm_param_signature' => 'self',
						'abi_symbol_template' => 'scpp_hash_{K_ABI}_{V_ABI}_empty',
						'method_aliases' => ['empty'],
					],
				],
			],
			[
				'family_key' => 'type_family:nullable',
				'source_name' => 'nullable',
				'family_kind' => 'callable',
				'template_param_count' => 1,
				'parameters' => [
					[
						'name' => 'T',
						'kind' => 'type',
						'constraints' => [
							'storage_abi_known',
							'lifetime_abi_known',
							'abi_lowerable',
						],
					],
				],
				'storage_abi' => 'ptr',
				'bridge_namespace' => 'scpp_bridge_nullable',
				'operations' => [
					[
						'operation_id' => 'create',
						'source_name' => 'nullable_create',
						'return_type' => 'self',
						'source_param_signature' => 'T',
						'llvm_return_type' => 'ptr',
						'llvm_param_signature' => 'T_ABI',
						'abi_symbol_template' => 'scpp_nullable_{T_ABI}_create',
					],
					[
						'operation_id' => 'get',
						'source_name' => 'nullable_get',
						'return_type' => 'T',
						'source_param_signature' => 'self',
						'llvm_return_type' => 'T_ABI',
						'llvm_param_signature' => 'self',
						'abi_symbol_template' => 'scpp_nullable_{T_ABI}_get',
					],
					[
						'operation_id' => 'has',
						'source_name' => 'nullable_has',
						'return_type' => 'bool',
						'source_param_signature' => 'self',
						'llvm_return_type' => 'i1',
						'llvm_param_signature' => 'self',
						'abi_symbol_template' => 'scpp_nullable_{T_ABI}_has',
						'method_aliases' => ['isset'],
					],
					[
						'operation_id' => 'empty',
						'source_name' => 'nullable_empty',
						'return_type' => 'bool',
						'source_param_signature' => 'self',
						'llvm_return_type' => 'i1',
						'llvm_param_signature' => 'self',
						'abi_symbol_template' => 'scpp_nullable_{T_ABI}_empty',
						'method_aliases' => ['empty'],
					],
					[
						'operation_id' => 'reset',
						'source_name' => 'nullable_reset',
						'return_type' => 'void',
						'source_param_signature' => 'self',
						'llvm_return_type' => 'void',
						'llvm_param_signature' => 'self',
						'abi_symbol_template' => 'scpp_nullable_{T_ABI}_reset',
						'method_aliases' => ['unset'],
					],
					[
						'operation_id' => 'destroy',
						'source_name' => 'nullable_destroy',
						'return_type' => 'void',
						'source_param_signature' => 'self',
						'llvm_return_type' => 'void',
						'llvm_param_signature' => 'self',
						'abi_symbol_template' => 'scpp_nullable_{T_ABI}_destroy',
					],
				],
			],
			[
				'family_key' => 'type_family:shared',
				'source_name' => 'shared',
				'family_kind' => 'callable',
				'template_param_count' => 1,
				'parameters' => [
					[
						'name' => 'T',
						'kind' => 'type',
						'constraints' => [],
					],
				],
				'storage_abi' => 'ptr',
				'bridge_namespace' => 'scpp_bridge_shared',
				'operations' => [
					[
						'operation_id' => 'create',
						'source_name' => 'shared_create',
						'return_type' => 'self',
						'source_param_signature' => '',
						'llvm_return_type' => 'ptr',
						'llvm_param_signature' => '',
						'abi_symbol_template' => 'scpp_shared_{T_ABI}_create',
					],
					[
						'operation_id' => 'own',
						'source_name' => 'shared_own',
						'return_type' => 'self',
						'source_param_signature' => 'T',
						'llvm_return_type' => 'ptr',
						'llvm_param_signature' => 'T_ABI',
						'abi_symbol_template' => 'scpp_shared_{T_ABI}_own',
					],
					[
						'operation_id' => 'has',
						'source_name' => 'shared_has',
						'return_type' => 'bool',
						'source_param_signature' => 'self',
						'llvm_return_type' => 'i1',
						'llvm_param_signature' => 'self',
						'abi_symbol_template' => 'scpp_shared_{T_ABI}_has',
						'method_aliases' => ['isset'],
					],
					[
						'operation_id' => 'empty',
						'source_name' => 'shared_empty',
						'return_type' => 'bool',
						'source_param_signature' => 'self',
						'llvm_return_type' => 'i1',
						'llvm_param_signature' => 'self',
						'abi_symbol_template' => 'scpp_shared_{T_ABI}_empty',
						'method_aliases' => ['empty'],
					],
					[
						'operation_id' => 'reset',
						'source_name' => 'shared_reset',
						'return_type' => 'void',
						'source_param_signature' => 'self',
						'llvm_return_type' => 'void',
						'llvm_param_signature' => 'self',
						'abi_symbol_template' => 'scpp_shared_{T_ABI}_reset',
						'method_aliases' => ['unset'],
					],
				],
			],
			[
				'family_key' => 'type_family:fixed_array',
				'source_name' => 'fixed_array',
				'family_kind' => 'storage_only',
				'template_param_count' => 2,
				'parameters' => [
					[
						'name' => 'T',
						'kind' => 'type',
						'constraints' => [
							'storage_abi_known',
							'lifetime_abi_known',
							'abi_lowerable',
						],
					],
					[
						'name' => 'N',
						'kind' => 'non_type_int',
						'constraints' => [
							'non_negative',
						],
					],
				],
				'storage_abi' => 'inline',
				'bridge_namespace' => '',
				'operations' => [],
			],
		],
		'function_count' => 60,
		'functions' => [
			[
				'source_key' => 'call:dbg:bool',
				'abi_symbol' => 'scpp_dbg_bool',
				'source_return_type' => 'void',
				'source_param_signature' => 'bool',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'i1',
				'implementation_hash' => '0a84125b78296004166978e2a61300465176bd1b670025ffcd9eea33cf51af76',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg:int',
				'abi_symbol' => 'scpp_dbg_int',
				'source_return_type' => 'void',
				'source_param_signature' => 'int',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'i64',
				'implementation_hash' => 'e2f02fa2f6caee22d947ea770fe788b9567f29e2e2357125d18ef0a887727ca3',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg:int64',
				'abi_symbol' => 'scpp_dbg_i64',
				'source_return_type' => 'void',
				'source_param_signature' => 'int64',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'i64',
				'implementation_hash' => 'd02f408377f6ac91b3caf85e660c6683a11d3a918b2f4196980eff9e3f9e836b',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg:string',
				'abi_symbol' => 'scpp_dbg_string',
				'source_return_type' => 'void',
				'source_param_signature' => 'string',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr',
				'implementation_hash' => 'd4913b7f0211e0ff75e39742284726d2a0b8909444e3cdabdc04337a3587c61a',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg:bool:int',
				'abi_symbol' => 'scpp_dbg_flags_bool',
				'source_return_type' => 'void',
				'source_param_signature' => 'bool, int',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'i1, i64',
				'implementation_hash' => '65253de09b3ef3978c507cb008ed3fba55a45d199d55806961669a3ce5e734b1',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg:int:int',
				'abi_symbol' => 'scpp_dbg_flags_int',
				'source_return_type' => 'void',
				'source_param_signature' => 'int, int',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'i64, i64',
				'implementation_hash' => '9b8ac385edbdbfbdec48a14c342b15c5c753597187479fc56864bc2ca54c315c',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg:int64:int',
				'abi_symbol' => 'scpp_dbg_flags_i64',
				'source_return_type' => 'void',
				'source_param_signature' => 'int64, int',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'i64, i64',
				'implementation_hash' => '50a43ff1f3c4758ba6e6c4f4087f2401d41dad40e5bccc509394c1e48e73af6d',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg:string:bool',
				'abi_symbol' => 'scpp_dbg_label_bool',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, bool',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, i1',
				'implementation_hash' => 'ca4c5b281e9a6fe3133eef8542cea949f8f81ad645cfabb13640c916de515db7',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg:string:int',
				'abi_symbol' => 'scpp_dbg_label_int',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, int',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, i64',
				'implementation_hash' => '140c2a9624d87e2d31cb810d6909830b60844058912f79e08d29eb769462b3f0',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg:string:int64',
				'abi_symbol' => 'scpp_dbg_label_i64',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, int64',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, i64',
				'implementation_hash' => '89bc6e753f0d527e68305770f934e6f0d2ae80c23e38ae996c7b9e9b0090c562',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg:string:string',
				'abi_symbol' => 'scpp_dbg_label_string',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, string',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, ptr',
				'implementation_hash' => 'f702ba6715353028a715207e72c855964d32cdfcb8761c460c84e0ae19208040',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg:string:bool:int',
				'abi_symbol' => 'scpp_dbg_label_flags_bool',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, bool, int',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, i1, i64',
				'implementation_hash' => '1a710c27c9cf85ed048a1f75e546edc49c7a1643da6919ac28e76a8f0299eccc',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg:string:int:int',
				'abi_symbol' => 'scpp_dbg_label_flags_int',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, int, int',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, i64, i64',
				'implementation_hash' => '672e1b3ac19067f78bf8ac234dfe571067b5235c3a75b6ecf8adf52df039f90f',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg:string:int64:int',
				'abi_symbol' => 'scpp_dbg_label_flags_i64',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, int64, int',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, i64, i64',
				'implementation_hash' => '8f8d16060c91d0d11d563031b0bac7e679037e5ae102eac23c9d6b3a90dcae12',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg:string:string:int',
				'abi_symbol' => 'scpp_dbg_label_flags_string',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, string, int',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, ptr, i64',
				'implementation_hash' => '16a89b91ba70acefb05c1161388ce14cdb632ad007b8d1bc84e3b48db55cd47b',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg_if:string:bool',
				'abi_symbol' => 'scpp_dbg_if_bool',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, bool',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, i1',
				'implementation_hash' => '0768db61f8aeece74acc3d14e5c4e1e1cfc24593d9bbe4811ee8eb6d45014479',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg_if:string:int',
				'abi_symbol' => 'scpp_dbg_if_int',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, int',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, i64',
				'implementation_hash' => 'd2a2423e161e578fa4800dcf99384126dbc68cf18b46110f434072227835e387',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg_if:string:int64',
				'abi_symbol' => 'scpp_dbg_if_i64',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, int64',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, i64',
				'implementation_hash' => 'd49221a1e03d3e1d9b7b4dd38d8991416917e93cfa5557d0f285ebb8153f8cac',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg_if:string:string',
				'abi_symbol' => 'scpp_dbg_if_string',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, string',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, ptr',
				'implementation_hash' => '5104d0d950874651728ac9e6853704b5490152e779a364344c1a1b589b3d374e',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg_if:string:string:bool',
				'abi_symbol' => 'scpp_dbg_if_label_bool',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, string, bool',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, ptr, i1',
				'implementation_hash' => 'a41f2e392882ea0ee2fea5dc1b465d5dfdf6913db11595fee1b94de85bab796c',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg_if:string:string:int',
				'abi_symbol' => 'scpp_dbg_if_label_int',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, string, int',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, ptr, i64',
				'implementation_hash' => '792adf520781524d33ed4d93ed9a46e2943605cbd22f52b186364154eb50f7ff',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg_if:string:string:int64',
				'abi_symbol' => 'scpp_dbg_if_label_i64',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, string, int64',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, ptr, i64',
				'implementation_hash' => '56abc1a3843f5c4df9e9a21cec5bdfb133141d433e46cbaeddd65813a10402a5',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg_if:string:string:string',
				'abi_symbol' => 'scpp_dbg_if_label_string',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, string, string',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, ptr, ptr',
				'implementation_hash' => '4cbc9437dcf1de8636f7c0a1d35f6bea2fe147b0247c649f69f76e05c14c58c4',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg_if:string:string:bool:int',
				'abi_symbol' => 'scpp_dbg_if_label_flags_bool',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, string, bool, int',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, ptr, i1, i64',
				'implementation_hash' => '6dea6803e8e9e8112e2b2947223a47197fd3baecf47fdd16adc792a48affbd82',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg_if:string:string:int:int',
				'abi_symbol' => 'scpp_dbg_if_label_flags_int',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, string, int, int',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, ptr, i64, i64',
				'implementation_hash' => 'ef6616080da6d71c27574e0fd1e2bc1b8914836fcd21bd4eb7e88c6953d5c606',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg_if:string:string:int64:int',
				'abi_symbol' => 'scpp_dbg_if_label_flags_i64',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, string, int64, int',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, ptr, i64, i64',
				'implementation_hash' => '7ade5834d52882b743b8b4e1bc881d334c96f206b164835313d6a4e0123f6fa1',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg_if:string:string:string:int',
				'abi_symbol' => 'scpp_dbg_if_label_flags_string',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, string, string, int',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, ptr, ptr, i64',
				'implementation_hash' => '2da22b63089b711301c068157d54f9b4124b1ce50c86667597ba856b55c59e66',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg_set:string:bool',
				'abi_symbol' => 'scpp_dbg_set_string_bool',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, bool',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, i1',
				'implementation_hash' => 'd37bbd69c657e79473a8c44d630e29b149378249600eb7124a9b61115e7b7659',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg_unset:string:bool',
				'abi_symbol' => 'scpp_dbg_unset_string_bool',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, bool',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, i1',
				'implementation_hash' => '7defc278e62556922d76f5ecc7645bbb3e72994b1d34332fd18c4282c03f84e5',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg_at:string:int:bool',
				'abi_symbol' => 'scpp_dbg_at_bool',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, int, bool',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, i64, i1',
				'implementation_hash' => '4264afcac877ef1db96ea4a3b8e57a665f234e483e890548587c4998825bd735',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg_at:string:int:int',
				'abi_symbol' => 'scpp_dbg_at_int',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, int, int',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, i64, i64',
				'implementation_hash' => '015b2118d284a0c145d6eb411d853b927f6e68703a7bcb90baf8080f8deed44f',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg_at:string:int:int64',
				'abi_symbol' => 'scpp_dbg_at_i64',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, int, int64',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, i64, i64',
				'implementation_hash' => '4b67a63ce9dcdf268ce2bf98dc8d97a8801cad0432b8da1f67a390cdf47c22ab',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg_at:string:int:string',
				'abi_symbol' => 'scpp_dbg_at_string',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, int, string',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, i64, ptr',
				'implementation_hash' => 'f7fc24beb8783f093fb725db6113048461e334834f49e1e075c6b6e9cfff71b1',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg_if_at:string:string:int:bool',
				'abi_symbol' => 'scpp_dbg_if_at_bool',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, string, int, bool',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, ptr, i64, i1',
				'implementation_hash' => '1dc6e4939d84402cbfd692175f138dc44142ee1795475ce504da32bbe94fc24e',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg_if_at:string:string:int:int',
				'abi_symbol' => 'scpp_dbg_if_at_int',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, string, int, int',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, ptr, i64, i64',
				'implementation_hash' => '87a381feccd81ebe5b6663ea4942a2481ea150e84ca19288cf16892e43827e0e',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg_if_at:string:string:int:int64',
				'abi_symbol' => 'scpp_dbg_if_at_i64',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, string, int, int64',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, ptr, i64, i64',
				'implementation_hash' => '543a319cf588f18694ff326bb7d7532f769252437f009a5efa337f72e02a87d0',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:dbg_if_at:string:string:int:string',
				'abi_symbol' => 'scpp_dbg_if_at_string',
				'source_return_type' => 'void',
				'source_param_signature' => 'string, string, int, string',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr, ptr, i64, ptr',
				'implementation_hash' => '356b005cf06d9cd7b1b390443ca2781588e76e8db2cd609c8a510c584036c147',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:echo:bool',
				'abi_symbol' => 'scpp_echo_bool',
				'source_return_type' => 'void',
				'source_param_signature' => 'bool',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'i1',
				'implementation_hash' => '2bab1c5ff2c67609f257b8247b4bc63070d8135fbaa7ee948ec1df0bfcae67cb',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:echo:int',
				'abi_symbol' => 'scpp_echo_int',
				'source_return_type' => 'void',
				'source_param_signature' => 'int',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'i64',
				'implementation_hash' => '8e37e963422bcc0f22c601bf6e97604c31057c72bcfe4a6a0a2b05120931058b',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:echo:int64',
				'abi_symbol' => 'scpp_echo_i64',
				'source_return_type' => 'void',
				'source_param_signature' => 'int64',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'i64',
				'implementation_hash' => '63cbf2cea4230379459e4b69cafaafb6d8ee0c6fd90804a8092cb92501a6ece8',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:echo:string',
				'abi_symbol' => 'scpp_echo_string',
				'source_return_type' => 'void',
				'source_param_signature' => 'string',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr',
				'implementation_hash' => '376b7d2eb8ffd3e99c4b54853da5daa4fc0270d0f14d283c1747151e6ef597d0',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:empty:bool',
				'abi_symbol' => 'scpp_empty_bool',
				'source_return_type' => 'bool',
				'source_param_signature' => 'bool',
				'llvm_return_type' => 'i1',
				'llvm_param_signature' => 'i1',
				'implementation_hash' => '21d3c7c6512bff750c6d17252f1e4cb47ee99655be86d0aafd70abe48cf81223',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:empty:int',
				'abi_symbol' => 'scpp_empty_int',
				'source_return_type' => 'bool',
				'source_param_signature' => 'int',
				'llvm_return_type' => 'i1',
				'llvm_param_signature' => 'i64',
				'implementation_hash' => 'ac95f408112f856e3543841e55400ede208f4f597dcb90c7698b401b8e885ae4',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:empty:int64',
				'abi_symbol' => 'scpp_empty_i64',
				'source_return_type' => 'bool',
				'source_param_signature' => 'int64',
				'llvm_return_type' => 'i1',
				'llvm_param_signature' => 'i64',
				'implementation_hash' => 'c56cfb3239a43d4336e4927e615be66905f4d5f2db04d2a084a468336fb3af89',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:empty:float',
				'abi_symbol' => 'scpp_empty_float',
				'source_return_type' => 'bool',
				'source_param_signature' => 'float',
				'llvm_return_type' => 'i1',
				'llvm_param_signature' => 'float',
				'implementation_hash' => '7d4b28c99e7c53379c4d94624b0a3fd7414d90bb56cbcff2fdf9cd317786f6d1',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:empty:double',
				'abi_symbol' => 'scpp_empty_double',
				'source_return_type' => 'bool',
				'source_param_signature' => 'double',
				'llvm_return_type' => 'i1',
				'llvm_param_signature' => 'double',
				'implementation_hash' => '03a1d97d2f7c0d2dd0ecf24b9b3b3803fcb460cb8520350c922dce0812277a2f',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:empty:string',
				'abi_symbol' => 'scpp_empty_string',
				'source_return_type' => 'bool',
				'source_param_signature' => 'string',
				'llvm_return_type' => 'i1',
				'llvm_param_signature' => 'ptr',
				'implementation_hash' => 'ca7e1664f69460810b64e74efb9d096829acffaa3afd17f724027e8bf6068d8d',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:condition_truthy:string',
				'abi_symbol' => 'scpp_condition_truthy_string',
				'source_return_type' => 'bool',
				'source_param_signature' => 'string',
				'llvm_return_type' => 'i1',
				'llvm_param_signature' => 'ptr',
				'implementation_hash' => 'f59dfaa00d5905564a33127a58879f6578331c5613dfd3aa835bfc5b8107eaa5',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'operator:<=>:int:int',
				'abi_symbol' => 'scpp_int_spaceship',
				'source_return_type' => 'int',
				'source_param_signature' => 'int, int',
				'llvm_return_type' => 'i64',
				'llvm_param_signature' => 'i64, i64',
				'implementation_hash' => '5c489a3b886b1bb4a038f79fcfe35a0620b0da74b63aa6aaa0000854a00afe0d',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'operator:+:string:string',
				'abi_symbol' => 'scpp_string_concat',
				'source_return_type' => 'string',
				'source_param_signature' => 'string, string',
				'llvm_return_type' => 'ptr',
				'llvm_param_signature' => 'ptr, ptr',
				'implementation_hash' => 'a6c1df93a7cdb3278e7c3959d435cebcdf4bdbdddb76b02fc2fce4221ca0de49',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'operator:==:string:string',
				'abi_symbol' => 'scpp_string_equal',
				'source_return_type' => 'bool',
				'source_param_signature' => 'string, string',
				'llvm_return_type' => 'i1',
				'llvm_param_signature' => 'ptr, ptr',
				'implementation_hash' => 'ccf78907a12362635071a576187da44d92dfdd2221e4031d329390b6b001ca7b',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'operator:!=:string:string',
				'abi_symbol' => 'scpp_string_not_equal',
				'source_return_type' => 'bool',
				'source_param_signature' => 'string, string',
				'llvm_return_type' => 'i1',
				'llvm_param_signature' => 'ptr, ptr',
				'implementation_hash' => '26c716743826a72ef82e87e37e52062e148228e6351006a409685a3fbf663dae',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'operator:===:string:string',
				'abi_symbol' => 'scpp_string_identical',
				'source_return_type' => 'bool',
				'source_param_signature' => 'string, string',
				'llvm_return_type' => 'i1',
				'llvm_param_signature' => 'ptr, ptr',
				'implementation_hash' => '63e74bd1489a5be35e17244f93e163e9a2d87742ed3f72a0fa493c64ff773f14',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'operator:!==:string:string',
				'abi_symbol' => 'scpp_string_not_identical',
				'source_return_type' => 'bool',
				'source_param_signature' => 'string, string',
				'llvm_return_type' => 'i1',
				'llvm_param_signature' => 'ptr, ptr',
				'implementation_hash' => 'c2a89fe303de7d880d77d9a8b93dc3e567f3940c2f32fccf1379d2b37dfd86d3',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'operator:<:string:string',
				'abi_symbol' => 'scpp_string_less',
				'source_return_type' => 'bool',
				'source_param_signature' => 'string, string',
				'llvm_return_type' => 'i1',
				'llvm_param_signature' => 'ptr, ptr',
				'implementation_hash' => 'b12189c7e71c770b7859c7f8b4ff70e516c3cd565051a7074ddc6cef812ae476',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'operator:>:string:string',
				'abi_symbol' => 'scpp_string_greater',
				'source_return_type' => 'bool',
				'source_param_signature' => 'string, string',
				'llvm_return_type' => 'i1',
				'llvm_param_signature' => 'ptr, ptr',
				'implementation_hash' => '7d81111caf46772b02cb36e6a5a568e1ea84162050e80163291760eb492bf97c',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'operator:<=:string:string',
				'abi_symbol' => 'scpp_string_less_equal',
				'source_return_type' => 'bool',
				'source_param_signature' => 'string, string',
				'llvm_return_type' => 'i1',
				'llvm_param_signature' => 'ptr, ptr',
				'implementation_hash' => 'c60c178a04407922194108b59e952a90f6f39821d6ba009c63b1c162d1fa17f3',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'operator:>=:string:string',
				'abi_symbol' => 'scpp_string_greater_equal',
				'source_return_type' => 'bool',
				'source_param_signature' => 'string, string',
				'llvm_return_type' => 'i1',
				'llvm_param_signature' => 'ptr, ptr',
				'implementation_hash' => 'f944f15c40c655c54840bddc72ed49ca3c653e87936c54976302d72e9ef68c22',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:string_literal',
				'abi_symbol' => 'scpp_string_literal_create',
				'source_return_type' => 'string',
				'source_param_signature' => 'ptr',
				'llvm_return_type' => 'ptr',
				'llvm_param_signature' => 'ptr',
				'implementation_hash' => '7159f54bb5c22633d4a4713e0c745915c2e9dfc06c11778980fceba1651d1b44',
				'bridge_provider' => 'project_generated',
			],
			[
				'source_key' => 'call:string_destroy',
				'abi_symbol' => 'scpp_string_destroy',
				'source_return_type' => 'void',
				'source_param_signature' => 'string',
				'llvm_return_type' => 'void',
				'llvm_param_signature' => 'ptr',
				'implementation_hash' => '5474ddcf5ea9f9ff23e25982f84a22f2a4a9f5149f157064529b3292f7b3339a',
				'bridge_provider' => 'project_generated',
			],
		],
		'bridge_declaration_count' => 0,
		'bridge_declarations' => [],
	];

	$json = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	if (!is_string($json)) {
		scpp_fail('Failed to render runtime metadata export.' . PHP_EOL, 2);
	}
	return $json . "\n";
}

function write_runtime_metadata_export(string $projectRoot, string $runtimeArtifactPath, string $buildMode, string $runtimeFamily): string
{
	$metadataPath = runtime_metadata_export_path_for_artifact($projectRoot, $runtimeArtifactPath);
	write_text_file($metadataPath, render_runtime_metadata_export_json($buildMode, $runtimeFamily));
	return normalize_config_path(relative_path($projectRoot, $metadataPath));
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
 * @return array{artifact_path:string,metadata_path:string,build_mode:string,runtime_family:string}
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
	$runtimeFamily = resolve_runtime_family($runtimeConfig);
	$metadataPath = write_runtime_metadata_export($projectRoot, $artifactPath, $buildMode, $runtimeFamily);

	return [
		'artifact_path' => $artifactPath,
		'metadata_path' => $metadataPath,
		'build_mode' => $buildMode,
		'runtime_family' => $runtimeFamily,
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
	$pathEnv = $env['PATH'] ?? ($env['Path'] ?? null);
	$env['PATH'] = scpp_effective_path_env(is_string($pathEnv) ? $pathEnv : null);
	if (PHP_OS_FAMILY === 'Windows') {
		$env['Path'] = $env['PATH'];
	}
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

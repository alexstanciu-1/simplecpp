<?php
declare(strict_types=1);

use Scpp\S2S\Transpiler;
use Scpp\S2S\Support\S2SException;

const SCPP_VERSION = '0.1.0-dev';
const SCPP_PROJECT_CONFIG = 'prism.json';
const SCPP_STATE_FILE = 's2s_state.php';
const SCPP_S2S_SIGNATURE_VERSION = 2;
const SCPP_CANONICAL_SOURCE_EXTENSION = 'phs';
const SCPP_COMPAT_SOURCE_EXTENSIONS = ['phs', 'php'];

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

/** @return array{command:list<string>,exit_code:int,stdout:string,stderr:string} */
function scpp_run_binary_service(string $workingDirectory, string $binaryPath, array $args = []): array
{
	$command = array_merge([$binaryPath], array_values($args));
	$descriptor = [
		0 => ['pipe', 'r'],
		1 => ['pipe', 'w'],
		2 => ['pipe', 'w'],
	];
	$process = proc_open($command, $descriptor, $pipes, $workingDirectory, scpp_build_process_environment());
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
	return [
		'command' => $command,
		'exit_code' => $status,
		'stdout' => is_string($stdout) ? $stdout : '',
		'stderr' => is_string($stderr) ? $stderr : '',
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
				scpp_build_default_runtime($topLevel, true);
			}
			return;
		}
		echo 'Updated scpp: ' . $before . ' -> ' . $after . PHP_EOL;
		scpp_build_default_runtime($topLevel, true);
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
				'modules' => ['json', 'filesystem'],
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

	if ($args[0] === 'runtime-build') {
		handle_runtime_build(getcwd() === false ? '.' : getcwd(), array_slice($args, 1));
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
	echo "  scpp build [--build-runtime] [--build-dependencies]\n";
	echo "  scpp clean\n";
	echo "  scpp update [--force]\n";
	echo "  scpp run [--build-runtime] [--build-dependencies] [--force] [-- <args...>]\n";
	echo "  scpp runtime-build [--debug|--release] [--force]\n";
	echo "  scpp usability-harness [--config <path>] [--limit <n>] [--stop-after-bugs <n>] [--include-scenarios]\n";
	echo "  scpp build emits a FastCGI companion binary when prism.json fastcgi.enabled = true\n";
	echo "  scpp clean removes the generated project working tree for a cold rebuild\n";
	echo "  scpp update fast-forwards the scpp repository from origin/main and rebuilds the default runtime when it changes\n";
	echo "  scpp run builds first, then executes the primary output\n";
	echo "  scpp runtime-build compiles the reusable runtime cache explicitly\n";
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

	echo 'Running: ' . normalize_config_path(relative_path($project['project_root'], $buildResult['output_path'])) . PHP_EOL;
	$descriptor = [
		0 => ['file', 'php://stdin', 'r'],
		1 => ['file', 'php://stdout', 'w'],
		2 => ['file', 'php://stderr', 'w'],
	];
	$processEnv = [];
	if (is_string($buildResult['runtime_library_dir'] ?? null) && $buildResult['runtime_library_dir'] !== '') {
		$existingPath = getenv('PATH');
		$processEnv['PATH'] = $buildResult['runtime_library_dir']
			. PATH_SEPARATOR
			. (is_string($existingPath) ? $existingPath : '');
	}
	$process = proc_open($command, $descriptor, $pipes, $project['project_root'], scpp_build_process_environment($processEnv));
	if (!is_resource($process)) {
		scpp_fail('Failed to start built program.' . PHP_EOL, 4);
	}
	$status = proc_close($process);
	if (!is_int($status)) {
		scpp_fail('Failed to read program exit status.' . PHP_EOL, 4);
	}
	exit($status);
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
	$options = parse_runtime_build_command_arguments($args);
	$project = find_project_config($cwd);
	$config = null;
	if ($project !== null) {
		$config = load_project_config($project['config_path']);
	}
	$result = scpp_build_runtime_from_config(resolve_repo_root(), $config, $options['build_mode'], $options['force']);
	echo 'Runtime build completed: ' . $result['artifact_path'] . PHP_EOL;
	echo 'Runtime build mode: ' . $result['build_mode'] . PHP_EOL;
}

/**
 * @param array{compile_runtime?:bool,compile_dependencies?:bool,force_runtime_rebuild?:bool} $options
 * @return array{project_root:string,build_dir:string,output_name:string,output_path:string,fastcgi_output_path:?string,runtime_library_dir:?string}
 */
function execute_build(string $projectRoot, string $configPath, array $options = []): array
{
	$options = normalize_build_execution_options($options);
	$config = load_project_config($configPath);
	$projectGraph = resolve_project_dependency_graph($projectRoot, $configPath, $config);
	$entrypoint = normalize_config_path((string) ($config['entrypoint'] ?? ''));
	if ($entrypoint === '') {
		scpp_fail('Missing `entrypoint` in ' . SCPP_PROJECT_CONFIG . PHP_EOL, 1);
	}

	$entrypointAbs = normalize_path($projectRoot . '/' . $entrypoint);
	if (!is_file($entrypointAbs)) {
		scpp_fail('Configured entrypoint not found: ' . $entrypoint . PHP_EOL, 1);
	}

	$ninjaPath = find_command_path(['ninja']);
	if ($ninjaPath === null) {
		scpp_fail("Ninja not found. Install it and retry.\n" . install_hint_for_ninja() . PHP_EOL, 1);
	}

	$compiler = resolve_compiler($config);
	if ($compiler === null) {
		scpp_fail("No supported C++ compiler found.\n" . install_hint_for_compiler() . PHP_EOL, 1);
	}
	$buildMode = resolve_build_mode($config);
	$runtimeConfig = is_array($config['runtime'] ?? null) ? $config['runtime'] : resolve_runtime_build_config($config);

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
	$runtimeBuildSignature = compute_runtime_build_signature($repoRoot, $compiler, $buildMode, $runtimeConfig);
	$phpProfile = resolve_php_runtime_profile($runtimeConfig);
	$transpiler = new Transpiler(phpProfile: $phpProfile);
	$generatorSignature = compute_s2s_generator_signature($repoRoot, $phpProfile);
	$projectLibraryFlags = resolve_project_library_link_flags($projectRoot, $projectGraph, $compiler);
	$generatedUnits = [];
	$nativeCppUnits = [];
	$transpiledCount = 0;
	$skippedCount = 0;

	foreach ($projectContexts as $contextProjectRoot => &$projectContext) {
		ensure_directory($projectContext['generated_dir']);
		ensure_directory($projectContext['cache_dir']);
		$projectContext['state_path'] = $projectContext['cache_dir'] . '/' . SCPP_STATE_FILE;
		$projectContext['state'] = load_s2s_state($projectContext['state_path']);
		$projectContext['php_files'] = collect_project_php_files($contextProjectRoot);
		$projectContext['native_cpp_files'] = collect_project_native_cpp_files($projectContext['native_cpp_dir']);

		foreach ($projectContext['php_files'] as $phpPathAbs) {
			$relativePhp = normalize_config_path(relative_path($contextProjectRoot, $phpPathAbs));
			$generatedBase = build_generated_base($projectContext['generated_dir'], $relativePhp);
			$generatedHeader = $generatedBase . '.hpp';
			$generatedExportManifest = $generatedBase . '.exports.json';
			$generatedCpp = $generatedBase . '.cpp';
			$meta = build_file_meta($phpPathAbs);
			$previous = is_array($projectContext['state']['files'][$relativePhp] ?? null) ? $projectContext['state']['files'][$relativePhp] : null;
			$needsTranspile = !is_array($previous)
				|| !isset($previous['size'], $previous['mtime'], $previous['content_hash'], $previous['generator_signature'])
				|| (string) $previous['generator_signature'] !== $generatorSignature
				|| (int) $previous['size'] !== $meta['size']
				|| (string) $previous['content_hash'] !== $meta['content_hash']
				|| !is_file($generatedHeader)
				|| !is_file($generatedCpp)
				|| ((bool) ($previous['has_export_manifest'] ?? false) && !is_file($generatedExportManifest));

			$cppFile = null;
			if ($needsTranspile) {
				try {
					$cppFile = $transpiler->transpile($phpPathAbs, false, $phpPathAbs === $entrypointAbs);
				} catch (S2SException $e) {
					scpp_fail($e->getMessage() . PHP_EOL, 3);
				} catch (Throwable $e) {
					scpp_fail('internal error: ' . $e->getMessage() . PHP_EOL, 4);
				}

				write_text_file($generatedHeader, implode(PHP_EOL, $cppFile->headerLines) . PHP_EOL);
				write_text_file($generatedCpp, implode(PHP_EOL, $cppFile->sourceLines) . PHP_EOL);
				write_export_manifest_file($generatedExportManifest, $cppFile->exportManifest);
				$transpiledCount++;
			} else {
				$skippedCount++;
			}

			$hasExportManifest = is_file($generatedExportManifest);
			$generatedUnits[] = [
				'project_root' => $contextProjectRoot,
				'relative_php' => $relativePhp,
				'generated_cpp' => $generatedCpp,
				'object_path' => build_object_path($projectContext['build_dir'], build_project_scoped_relative_path($projectRoot, $contextProjectRoot, $relativePhp), $compiler['kind']),
				'is_entrypoint' => $phpPathAbs === $entrypointAbs,
				'force_include_header' => null,
			];
			if ($hasExportManifest) {
				$projectContext['export_manifests'][] = $generatedExportManifest;
			}

			if ($fastcgiBuild !== null && $contextProjectRoot === $projectRoot && $phpPathAbs === $entrypointAbs) {
				try {
					$fcgiCppFile = $transpiler->transpile($phpPathAbs, false, false);
				} catch (S2SException $e) {
					scpp_fail($e->getMessage() . PHP_EOL, 3);
				} catch (Throwable $e) {
					scpp_fail('internal error: ' . $e->getMessage() . PHP_EOL, 4);
				}
				$fcgiBase = build_generated_fcgi_base($generatedDir, $relativePhp);
				write_text_file($fcgiBase . '.hpp', implode(PHP_EOL, $fcgiCppFile->headerLines) . PHP_EOL);
				write_text_file($fcgiBase . '.cpp', implode(PHP_EOL, $fcgiCppFile->sourceLines) . PHP_EOL);
				$fastcgiBuild['entrypoint_generated_cpp'] = normalize_config_path(relative_path($projectRoot, $fcgiBase . '.cpp'));
				$fastcgiBuild['entrypoint_object_path'] = normalize_config_path(relative_path($projectRoot, build_fcgi_object_path($buildDir, $relativePhp, $compiler['kind'])));
			}
			$projectContext['state']['files'][$relativePhp] = [
				'size' => $meta['size'],
				'mtime' => $meta['mtime'],
				'content_hash' => $meta['content_hash'],
				'generator_signature' => $generatorSignature,
				'generated_base' => normalize_config_path(relative_path($contextProjectRoot, $generatedBase)),
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
		write_text_file($projectContext['generated_dir'] . '/__project.hpp', render_project_export_header($projectContext['generated_dir'], $projectContext['export_manifests'] ?? []));
	}
	unset($projectContext);
	write_text_file($buildDir . '/runtime_signature.txt', $runtimeBuildSignature . PHP_EOL);
	$projectDependencyForceIncludes = write_project_dependency_force_include_headers($projectContexts);
	foreach ($generatedUnits as &$unit) {
		$unit['force_include_header'] = $projectDependencyForceIncludes[normalize_path($unit['project_root'])] ?? null;
	}
	unset($unit);
	foreach ($nativeCppUnits as &$nativeUnit) {
		$nativeUnit['force_include_header'] = $projectDependencyForceIncludes[normalize_path($nativeUnit['project_root'])] ?? null;
	}
	unset($nativeUnit);

	if (supports_compiler_pch($compiler)) {
		write_text_file(build_app_pch_header_path($buildDir), render_app_pch_header());
		write_text_file(build_runtime_pch_header_path($buildDir), render_runtime_pch_header());
	}

	$outputName = build_output_name($entrypointAbs);
	$buildNinja = render_build_ninja($projectRoot, $repoRoot, $buildDir, $generatedDir, $generatedUnits, $nativeCppUnits, $outputName, $compiler, $buildMode, $runtimeConfig, $projectLibraryFlags, $fastcgiBuild, $options);
	$buildNinjaPath = $buildDir . '/build.ninja';
	write_text_file($buildNinjaPath, $buildNinja);
	$runtimeBuild = build_runtime_artifact_spec($repoRoot, $projectRoot, $compiler, $buildMode, $runtimeConfig);
	if ($options['force_runtime_rebuild']) {
		delete_file_if_exists(normalize_path($projectRoot . '/' . normalize_config_path($runtimeBuild['artifact_path'])));
		$runtimeObjectPath = $runtimeBuild['object_path'] ?? null;
		if (is_string($runtimeObjectPath) && $runtimeObjectPath !== '') {
			delete_file_if_exists(normalize_path($projectRoot . '/' . normalize_config_path($runtimeObjectPath)));
		}
	}
	$buildOutputs = collect_build_output_paths($generatedUnits, $nativeCppUnits, $runtimeBuild, $buildDir, $compiler['kind'], $outputName, $fastcgiBuild, $projectRoot, $options);
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

	$command = [
		$ninjaPath,
		'-f',
		normalize_config_path(relative_path($projectRoot, $buildNinjaPath)),
	];
	if (build_ninja_verbose_requested()) {
		$command[] = '-v';
	}
	$captureSubprocessOutput = scpp_capture_subprocess_output_enabled();
	$descriptor = [
		0 => ['file', 'php://stdin', 'r'],
		1 => $captureSubprocessOutput ? ['pipe', 'w'] : ['file', 'php://stdout', 'w'],
		2 => $captureSubprocessOutput ? ['pipe', 'w'] : ['file', 'php://stderr', 'w'],
	];
	$process = proc_open($command, $descriptor, $pipes, $projectRoot, scpp_build_process_environment());
	if (!is_resource($process)) {
		scpp_fail("Failed to start Ninja.
", 4);
	}
	$ninjaStdout = '';
	$ninjaStderr = '';
	if ($captureSubprocessOutput) {
		$ninjaStdout = stream_get_contents($pipes[1]);
		$ninjaStderr = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
	}
	$status = proc_close($process);
	if ($captureSubprocessOutput) {
		scpp_append_captured_subprocess_output(is_string($ninjaStdout) ? $ninjaStdout : '', is_string($ninjaStderr) ? $ninjaStderr : '');
	}
	if ($status !== 0) {
		$message = 'Ninja build failed. Generated file: ' . normalize_config_path(relative_path($projectRoot, $buildNinjaPath)) . PHP_EOL;
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
	];
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

/** @param array{compile_runtime?:bool,compile_dependencies?:bool,force_runtime_rebuild?:bool} $options @return array{compile_runtime:bool,compile_dependencies:bool,force_runtime_rebuild:bool} */
function normalize_build_execution_options(array $options): array
{
	return [
		'compile_runtime' => (bool) ($options['compile_runtime'] ?? false),
		'compile_dependencies' => (bool) ($options['compile_dependencies'] ?? false),
		'force_runtime_rebuild' => (bool) ($options['force_runtime_rebuild'] ?? false),
	];
}

/** @param list<string> $args @return array{compile_runtime:bool,compile_dependencies:bool,force_runtime_rebuild:bool} */
function parse_build_command_arguments(array $args): array
{
	$options = [
		'compile_runtime' => false,
		'compile_dependencies' => false,
		'force_runtime_rebuild' => false,
	];
	foreach ($args as $arg) {
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
		scpp_fail('Unknown option for `scpp build`: ' . $arg . PHP_EOL, 1);
	}
	return $options;
}

/** @param list<string> $args @return array{build_options:array{compile_runtime:bool,compile_dependencies:bool,force_runtime_rebuild:bool},run_args:list<string>} */
function parse_run_command_arguments(array $args): array
{
	$buildOptions = [
		'compile_runtime' => false,
		'compile_dependencies' => false,
		'force_runtime_rebuild' => false,
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
		$runArgs[] = $arg;
		$inRunArgs = true;
	}
	return [
		'build_options' => $buildOptions,
		'run_args' => normalize_run_arguments($runArgs),
	];
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
	if (supports_compiler_pch(['kind' => $compilerKind])) {
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
	$lines = ['#pragma once', '', '#include <scpp/lang/php.hpp>'];
	$seenHeaders = [];
	foreach (array_values(array_unique($exportManifestPaths)) as $manifestPath) {
		$headerPath = export_manifest_header_path($manifestPath);
		if ($headerPath === null) {
			continue;
		}
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
 *   export_headers?:list<string>
 * }> $projectContexts
 */
function write_project_dependency_force_include_headers(array $projectContexts): array
{
	$headers = [];
	foreach ($projectContexts as $projectRoot => $projectContext) {
		$dependencyHeaders = [];
		foreach (collect_transitive_project_dependency_roots($projectRoot, $projectContexts) as $dependencyRoot) {
			$dependencyContext = $projectContexts[$dependencyRoot] ?? null;
			if (!is_array($dependencyContext)) {
				continue;
			}
			$dependencyHeaders[] = normalize_path($dependencyContext['generated_dir'] . '/__project.hpp');
		}
		$dependencyHeaders = array_values(array_unique($dependencyHeaders));
		if ($dependencyHeaders === []) {
			continue;
		}
		$headerPath = normalize_path($projectContext['generated_dir'] . '/__project_deps.hpp');
		$lines = ['#pragma once', ''];
		foreach ($dependencyHeaders as $dependencyHeader) {
			$lines[] = '#include "' . normalize_config_path(relative_path(dirname($headerPath), $dependencyHeader)) . '"';
		}
		$lines[] = '';
		write_text_file($headerPath, implode(PHP_EOL, $lines) . PHP_EOL);
		$headers[normalize_path($projectRoot)] = $headerPath;
	}
	return $headers;
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
	$modules = $runtime['modules'] ?? ['json', 'filesystem'];
	if (!is_array($languagesRaw) || !is_array($modules)) {
		scpp_fail('Invalid runtime config in ' . SCPP_PROJECT_CONFIG . '; expected runtime.languages as either a list or object, and runtime.modules as an array.' . PHP_EOL, 2);
	}

	$languageProfiles = [];
	if (array_is_list($languagesRaw)) {
		$languages = array_values(array_unique(array_map(static fn ($value): string => strtolower(trim((string) $value)), $languagesRaw)));
		foreach ($languages as $language) {
			if ($language !== '') {
				$languageProfiles[$language] = ['profile' => 'legacy'];
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
	$allowedModules = ['json', 'filesystem', 'mysqli'];
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

function is_supported_source_extension(string $extension): bool
{
	return in_array(strtolower($extension), scpp_source_extensions(), true);
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
	if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
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

function build_generated_base(string $generatedDir, string $relativePhp): string
{
	$trimmed = strip_supported_source_extension($relativePhp);
	if (!is_string($trimmed) || $trimmed === '') {
		$trimmed = 'entry';
	}
	return $generatedDir . '/' . $trimmed;
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
				. ' or .php variants.'
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

/** @return array<string,mixed> */
function compute_s2s_generator_signature(string $repoRoot, string $phpProfile = 'legacy'): string
{
	$parts = [
		'version:' . SCPP_S2S_SIGNATURE_VERSION,
		'php_profile:' . strtolower(trim($phpProfile)),
	];

	$files = [
		$repoRoot . '/bin/scpp.php',
		$repoRoot . '/generators/php/src/Transpiler.php',
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
 */
function render_build_ninja(string $projectRoot, string $repoRoot, string $buildDir, string $generatedDir, array $generatedUnits, array $nativeCppUnits, string $outputName, array $compiler, string $buildMode, array $runtimeConfig, array $projectLibraryFlags = [], ?array $fastcgiBuild = null, array $options = ['compile_runtime' => true, 'compile_dependencies' => true]): string
{
	$generatedIncludeDir = normalize_config_path(relative_path($projectRoot, $generatedDir));
	$runtimeIncludeDir = normalize_config_path(relative_path($projectRoot, $repoRoot . '/runtime/include'));
	$output = normalize_config_path(relative_path($projectRoot, $buildDir . '/' . $outputName));
	$appPchHeader = normalize_config_path(relative_path($projectRoot, build_app_pch_header_path($buildDir)));
	$appPchArtifact = normalize_config_path(relative_path($projectRoot, build_app_pch_artifact_path($buildDir, $compiler['kind'])));
	$runtimePchHeader = normalize_config_path(relative_path($projectRoot, build_runtime_pch_header_path($buildDir)));
	$runtimePchArtifact = normalize_config_path(relative_path($projectRoot, build_runtime_pch_artifact_path($buildDir, $compiler['kind'])));
	$compilerCommand = $compiler['command'];
	$compilerLauncher = $compiler['launcher'] ?? null;
	$linkerFlags = is_array($compiler['linker_flags'] ?? null) ? $compiler['linker_flags'] : [];
	$runtimeBuild = build_runtime_artifact_spec($repoRoot, $projectRoot, $compiler, $buildMode, $runtimeConfig);
	$runtimeSignatureStamp = normalize_config_path(relative_path($projectRoot, $buildDir . '/runtime_signature.txt'));
	$runtimeLinkFlags = $options['compile_runtime'] && is_array($runtimeBuild['link_flags'] ?? null) ? $runtimeBuild['link_flags'] : [];
	$runtimeExtraCxxFlags = $options['compile_runtime'] && is_array($runtimeBuild['extra_cxxflags'] ?? null) ? $runtimeBuild['extra_cxxflags'] : [];
	$fastcgiCxxFlags = is_array($fastcgiBuild['cxxflags'] ?? null) ? $fastcgiBuild['cxxflags'] : [];
	$fastcgiLdFlags = is_array($fastcgiBuild['ldflags'] ?? null) ? $fastcgiBuild['ldflags'] : [];
	$baseLinkFlags = $linkerFlags;
	$binaryLinkFlags = array_merge($baseLinkFlags, $projectLibraryFlags);
	if (is_string($runtimeBuild['rpath_dir'] ?? null) && $runtimeBuild['rpath_dir'] !== '') {
		$binaryLinkFlags[] = '-Wl,-rpath,' . ninja_escape_path($runtimeBuild['rpath_dir']);
	}

	$lines = [];
	$lines[] = 'cxx = ' . $compilerCommand;
	if (is_string($compilerLauncher) && $compilerLauncher !== '') {
		$lines[] = 'cxx_launcher = ' . $compilerLauncher;
	}
	$lines[] = 'cxxflags = ' . build_compiler_flags($compiler['kind'], $buildMode, $runtimeIncludeDir, $generatedIncludeDir);
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
	if (supports_compiler_pch($compiler)) {
		$lines[] = 'app_pch_header = ' . $appPchHeader;
		$lines[] = 'app_pchflags = -Winvalid-pch -include $app_pch_header';
		if ($options['compile_runtime']) {
			$lines[] = 'runtime_pch_header = ' . $runtimePchHeader;
			$lines[] = 'runtime_pchflags = -Winvalid-pch -include $runtime_pch_header';
		}
	}
	$lines[] = '';
	if (supports_compiler_pch($compiler)) {
		$lines[] = 'rule compile_pch_app';
		$lines[] = '  command = ' . compiler_invocation_prefix($compiler) . ' $cxx $cxxflags -MMD -MF $out.d -x c++-header $in -o $out';
		$lines[] = '  depfile = $out.d';
		$lines[] = '  deps = gcc';
		$lines[] = '  description = PCH $out';
		$lines[] = '';
		if ($options['compile_runtime']) {
			$lines[] = 'rule compile_pch_runtime';
			$lines[] = '  command = ' . compiler_invocation_prefix($compiler) . ' $cxx $runtime_cxxflags -MMD -MF $out.d -x c++-header $in -o $out';
			$lines[] = '  depfile = $out.d';
			$lines[] = '  deps = gcc';
			$lines[] = '  description = PCH $out';
			$lines[] = '';
		}
	}
	if ($options['compile_runtime']) {
		$lines[] = 'rule compile_runtime_fallback';
		if ($compiler['kind'] === 'msvc') {
			$lines[] = '  command = ' . compiler_invocation_prefix($compiler) . ' $cxx $runtime_cxxflags /c $in /Fo$out';
		} else {
			$lines[] = '  command = ' . compiler_invocation_prefix($compiler) . ' $cxx $runtime_cxxflags' . (supports_compiler_pch($compiler) ? ' $runtime_pchflags' : '') . ' -MMD -MF $out.d -c $in -o $out';
			$lines[] = '  depfile = $out.d';
			$lines[] = '  deps = gcc';
		}
		$lines[] = '  description = CXX $out';
		$lines[] = '';
		if ($runtimeBuild['kind'] === 'shared') {
			$lines[] = 'rule compile_runtime';
			$lines[] = '  command = ' . compiler_invocation_prefix($compiler) . ' $cxx $runtime_cxxflags' . (supports_compiler_pch($compiler) ? ' $runtime_pchflags' : '') . ' -MMD -MF $out.d -c $in -o $out';
			$lines[] = '  depfile = $out.d';
			$lines[] = '  deps = gcc';
			$lines[] = '  description = CXX $out';
			$lines[] = '';
			$lines[] = 'rule link_runtime_shared';
			$lines[] = '  command = ' . compiler_invocation_prefix($compiler) . ' $cxx $base_ldflags $runtime_ldflags $in -o $out';
			$lines[] = '  description = LINK $out';
			$lines[] = '';
		}
	}
	$lines[] = 'rule compile';
	if ($compiler['kind'] === 'msvc') {
		$lines[] = '  command = ' . compiler_invocation_prefix($compiler) . ' $cxx $cxxflags $more_cxxflags /c $in /Fo$out';
	} else {
		$lines[] = '  command = ' . compiler_invocation_prefix($compiler) . ' $cxx $cxxflags $more_cxxflags' . (supports_compiler_pch($compiler) ? ' $app_pchflags' : '') . ' -MMD -MF $out.d -c $in -o $out';
		$lines[] = '  depfile = $out.d';
		$lines[] = '  deps = gcc';
	}
	$lines[] = '  description = CXX $out';
	$lines[] = '';
	$lines[] = 'rule link';
	if ($compiler['kind'] === 'msvc') {
		$lines[] = '  command = ' . compiler_invocation_prefix($compiler) . ' $cxx /nologo $in /Fe$out';
	} else {
		$lines[] = '  command = ' . compiler_invocation_prefix($compiler) . ' $cxx $ldflags $in -o $out';
	}
	$lines[] = '  description = LINK $out';
	$lines[] = '';
	if ($fastcgiBuild !== null) {
		$lines[] = 'rule compile_fcgi';
		$lines[] = '  command = ' . compiler_invocation_prefix($compiler) . ' $cxx $cxxflags $fcgi_cxxflags' . (supports_compiler_pch($compiler) ? ' $app_pchflags' : '') . ' -MMD -MF $out.d -c $in -o $out';
		$lines[] = '  depfile = $out.d';
		$lines[] = '  deps = gcc';
		$lines[] = '  description = CXX $out';
		$lines[] = '';
		$lines[] = 'rule link_fcgi';
		$lines[] = '  command = ' . compiler_invocation_prefix($compiler) . ' $cxx $fcgi_ldflags $in -o $out';
		$lines[] = '  description = LINK $out';
		$lines[] = '';
	}

	$objectPaths = [];
	if (supports_compiler_pch($compiler)) {
		$lines[] = 'build ' . ninja_escape_path($appPchArtifact) . ': compile_pch_app ' . ninja_escape_path($appPchHeader);
		if ($options['compile_runtime']) {
			$lines[] = 'build ' . ninja_escape_path($runtimePchArtifact) . ': compile_pch_runtime ' . ninja_escape_path($runtimePchHeader);
		}
	}
	foreach ($generatedUnits as $unit) {
		if (!$options['compile_dependencies'] && normalize_path($unit['project_root']) !== normalize_path($projectRoot)) {
			$objectPaths[] = ninja_escape_path(normalize_config_path(relative_path($projectRoot, $unit['object_path'])));
			continue;
		}
		$generatedCpp = normalize_config_path(relative_path($projectRoot, $unit['generated_cpp']));
		$objectPath = normalize_config_path(relative_path($projectRoot, $unit['object_path']));
		$implicitDeps = [ninja_escape_path($runtimeSignatureStamp)];
		if (supports_compiler_pch($compiler)) {
			$implicitDeps[] = ninja_escape_path($appPchArtifact);
		}
		$lines[] = 'build ' . ninja_escape_path($objectPath) . ': compile ' . ninja_escape_path($generatedCpp) . ' | ' . implode(' ', $implicitDeps);
		$unitForceIncludeHeader = is_string($unit['force_include_header'] ?? null) ? $unit['force_include_header'] : null;
		if ($unitForceIncludeHeader !== null && $unitForceIncludeHeader !== '') {
			$lines[] = '  more_cxxflags = ' . build_force_include_flags($compiler['kind'], [normalize_config_path(relative_path($projectRoot, $unitForceIncludeHeader))]);
		}
		$objectPaths[] = ninja_escape_path($objectPath);
	}
	foreach ($nativeCppUnits as $nativeUnit) {
		if (!$options['compile_dependencies'] && normalize_path($nativeUnit['project_root']) !== normalize_path($projectRoot)) {
			$objectPaths[] = ninja_escape_path(normalize_config_path(relative_path($projectRoot, $nativeUnit['object_path'])));
			continue;
		}
		$nativeRelative = normalize_config_path(relative_path($projectRoot, $nativeUnit['source_path']));
		$nativeObject = normalize_config_path(relative_path($projectRoot, $nativeUnit['object_path']));
		$implicitDeps = [ninja_escape_path($runtimeSignatureStamp)];
		if (supports_compiler_pch($compiler)) {
			$implicitDeps[] = ninja_escape_path($appPchArtifact);
		}
		$lines[] = 'build ' . ninja_escape_path($nativeObject) . ': compile ' . ninja_escape_path($nativeRelative) . ' | ' . implode(' ', $implicitDeps);
		$unitForceIncludeHeader = is_string($nativeUnit['force_include_header'] ?? null) ? $nativeUnit['force_include_header'] : null;
		if ($unitForceIncludeHeader !== null && $unitForceIncludeHeader !== '') {
			$lines[] = '  more_cxxflags = ' . build_force_include_flags($compiler['kind'], [normalize_config_path(relative_path($projectRoot, $unitForceIncludeHeader))]);
		}
		$objectPaths[] = ninja_escape_path($nativeObject);
	}
	if ($options['compile_runtime']) {
		if ($runtimeBuild['kind'] === 'shared') {
			$runtimeImplicitDeps = [ninja_escape_path($runtimeSignatureStamp)];
			if (supports_compiler_pch($compiler)) {
				$runtimeImplicitDeps[] = ninja_escape_path($runtimePchArtifact);
			}
			$lines[] = 'build ' . ninja_escape_path($runtimeBuild['object_path']) . ': compile_runtime ' . ninja_escape_path($runtimeBuild['source_path']) . ' | ' . implode(' ', $runtimeImplicitDeps);
			$lines[] = 'build ' . ninja_escape_path($runtimeBuild['artifact_path']) . ': link_runtime_shared ' . ninja_escape_path($runtimeBuild['object_path']);
			$objectPaths[] = ninja_escape_path($runtimeBuild['artifact_path']);
		} else {
			$runtimeImplicitDeps = [ninja_escape_path($runtimeSignatureStamp)];
			if (supports_compiler_pch($compiler)) {
				$runtimeImplicitDeps[] = ninja_escape_path($runtimePchArtifact);
			}
			$lines[] = 'build ' . ninja_escape_path($runtimeBuild['artifact_path']) . ': compile_runtime_fallback ' . ninja_escape_path($runtimeBuild['source_path']) . ' | ' . implode(' ', $runtimeImplicitDeps);
			$objectPaths[] = ninja_escape_path($runtimeBuild['artifact_path']);
		}
	} else {
		$objectPaths[] = ninja_escape_path($runtimeBuild['artifact_path']);
	}
	$lines[] = '';
	$lines[] = 'build ' . ninja_escape_path($output) . ': link ' . implode(' ', $objectPaths);
	if ($fastcgiBuild !== null) {
		$fcgiObjects = [];
		foreach ($generatedUnits as $unit) {
			if (($unit['is_entrypoint'] ?? false) === true && ($fastcgiBuild['entrypoint_generated_cpp'] ?? '') !== '' && ($fastcgiBuild['entrypoint_object_path'] ?? '') !== '') {
				continue;
			}
			$fcgiObjects[] = ninja_escape_path(normalize_config_path(relative_path($projectRoot, $unit['object_path'])));
		}
		foreach ($nativeCppUnits as $nativeUnit) {
			$fcgiObjects[] = ninja_escape_path(normalize_config_path(relative_path($projectRoot, $nativeUnit['object_path'])));
		}
		if (($fastcgiBuild['entrypoint_generated_cpp'] ?? '') !== '' && ($fastcgiBuild['entrypoint_object_path'] ?? '') !== '') {
			$fcgiGeneratedCpp = normalize_config_path($fastcgiBuild['entrypoint_generated_cpp']);
			$fcgiGeneratedObject = normalize_config_path($fastcgiBuild['entrypoint_object_path']);
			$implicitDeps = [ninja_escape_path($runtimeSignatureStamp)];
			if (supports_compiler_pch($compiler)) {
				$implicitDeps[] = ninja_escape_path($appPchArtifact);
			}
			$lines[] = 'build ' . ninja_escape_path($fcgiGeneratedObject) . ': compile ' . ninja_escape_path($fcgiGeneratedCpp) . ' | ' . implode(' ', $implicitDeps);
			$fcgiObjects[] = ninja_escape_path($fcgiGeneratedObject);
		}
		$fcgiMainSource = normalize_config_path($fastcgiBuild['source_path']);
		$fcgiMainObject = normalize_config_path($fastcgiBuild['main_object_path']);
		$fcgiMainImplicitDeps = [ninja_escape_path($runtimeSignatureStamp)];
		if (supports_compiler_pch($compiler)) {
			$fcgiMainImplicitDeps[] = ninja_escape_path($appPchArtifact);
		}
		$lines[] = 'build ' . ninja_escape_path($fcgiMainObject) . ': compile_fcgi ' . ninja_escape_path($fcgiMainSource) . ' | ' . implode(' ', $fcgiMainImplicitDeps);
		$fcgiObjects[] = ninja_escape_path($fcgiMainObject);
		$fcgiObjects[] = ninja_escape_path($runtimeBuild['artifact_path']);
		$lines[] = 'build ' . ninja_escape_path(normalize_config_path(relative_path($projectRoot, $fastcgiBuild['output_path']))) . ': link_fcgi ' . implode(' ', $fcgiObjects);
	}
	$lines[] = '';
	$defaults = [ninja_escape_path($output)];
	if ($fastcgiBuild !== null) {
		$defaults[] = ninja_escape_path(normalize_config_path(relative_path($projectRoot, $fastcgiBuild['output_path'])));
	}
	$lines[] = 'default ' . implode(' ', $defaults);
	return implode(PHP_EOL, $lines) . PHP_EOL;
}

function render_runtime_composition_source(array $runtimeConfig): string
{
	$languages = is_array($runtimeConfig['languages'] ?? null) ? $runtimeConfig['languages'] : ['php'];
	$modules = is_array($runtimeConfig['modules'] ?? null) ? $runtimeConfig['modules'] : ['json', 'filesystem'];
	$phpProfile = resolve_php_runtime_profile($runtimeConfig);
	$lines = [
		'#include "core/runtime.cpp"',
	];
	if (in_array('json', $modules, true)) {
		$lines[] = '#include "modules/json/json.cpp"';
	}
	if (in_array('mysqli', $modules, true)) {
		$lines[] = '#include "modules/mysql/mysql_module.cpp"';
	}
	if (in_array('php', $languages, true) && $phpProfile === 'legacy') {
		if (in_array('filesystem', $modules, true)) {
			$lines[] = '#include "lang/php/php_filesystem.cpp"';
		}
		if (in_array('json', $modules, true)) {
			$lines[] = '#include "lang/php/php_json.cpp"';
		}
		if (in_array('mysqli', $modules, true)) {
			$lines[] = '#include "lang/php/php_mysqli.cpp"';
		}
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
		$flags[] = '-g0';
		if (PHP_OS_FAMILY === 'Linux') {
			// No split dwarf output when debug info is disabled.
		}
		$flags[] = '-pipe';
	}
	$flags[] = '-I' . $runtimeIncludeDir;
	return implode(' ', $flags);
}

/**
 * @param array{command:string,kind:string,launcher?:?string,linker_flags?:list<string>,archiver?:?string} $compiler
 * @return array{kind:string,source_path:string,artifact_path:string,object_path:?string,archiver:?string}
 */
function build_runtime_artifact_spec(string $repoRoot, string $projectRoot, array $compiler, string $buildMode, array $runtimeConfig): array
{
	$signature = compute_runtime_build_signature($repoRoot, $compiler, $buildMode, $runtimeConfig);
	$runtimeCacheDir = normalize_path($repoRoot . '/.prism/runtime/' . $signature);
	ensure_directory($runtimeCacheDir);

	$compositionSource = $runtimeCacheDir . '/runtime_build.cpp';
	write_text_file($compositionSource, render_runtime_composition_source($runtimeConfig));
	$sourcePath = normalize_config_path(relative_path($projectRoot, $compositionSource));
	$modules = is_array($runtimeConfig['modules'] ?? null) ? $runtimeConfig['modules'] : ['json', 'filesystem'];
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

	if ($compiler['kind'] === 'gnu_like') {
		$libraryName = PHP_OS_FAMILY === 'Darwin' ? 'libruntime.dylib' : 'libruntime.so';
		$linkFlags = ['-shared'];
		if (PHP_OS_FAMILY === 'Darwin') {
			$linkFlags[] = '-Wl,-install_name,@rpath/' . $libraryName;
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

/**
 * @param array{command:string,kind:string,launcher?:?string,linker_flags?:list<string>,archiver?:?string} $compiler
 */
function compute_runtime_build_signature(string $repoRoot, array $compiler, string $buildMode, array $runtimeConfig): string
{
	$parts = [
		'runtime-v3',
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

	$paths = [
		$repoRoot . '/runtime/include',
	];
	foreach ($paths as $root) {
		if (!is_dir($root)) {
			$parts[] = 'missing:' . normalize_path($root);
			continue;
		}
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
		);
		foreach ($iterator as $fileInfo) {
			if (!$fileInfo->isFile()) {
				continue;
			}
			$filePath = normalize_path($fileInfo->getPathname());
			$hash = hash_file('sha256', $filePath);
			$parts[] = normalize_path(relative_path($repoRoot, $filePath)) . ':' . ($hash === false ? 'hash-failed' : $hash);
		}
	}

	sort($parts, SORT_STRING);
	return substr(hash('sha256', implode("\n", $parts)), 0, 16);
}

/** @return array{artifact_path:string,build_mode:string} */
function scpp_build_default_runtime(string $repoRoot, bool $force = false): array
{
	return scpp_build_runtime_from_config($repoRoot, null, 'debug', $force);
}

/** @param ?array<string,mixed> $config @return array{artifact_path:string,build_mode:string} */
function scpp_build_runtime_from_config(string $repoRoot, ?array $config, string $buildMode = 'debug', bool $force = false): array
{
	$repoRoot = normalize_path($repoRoot);
	$config = is_array($config) ? $config : [];
	$compiler = resolve_compiler($config);
	if ($compiler === null) {
		scpp_fail("No supported C++ compiler found.\n" . install_hint_for_compiler() . PHP_EOL, 1);
	}
	$runtimeConfig = is_array($config['runtime'] ?? null) ? $config['runtime'] : resolve_runtime_build_config($config);
	$runtimeBuild = build_runtime_artifact_spec($repoRoot, $repoRoot, $compiler, $buildMode, $runtimeConfig);
	$artifactPath = normalize_path($repoRoot . '/' . normalize_config_path($runtimeBuild['artifact_path']));
	$objectPath = is_string($runtimeBuild['object_path'] ?? null) && $runtimeBuild['object_path'] !== ''
		? normalize_path($repoRoot . '/' . normalize_config_path($runtimeBuild['object_path']))
		: null;

	if (!$force && is_file($artifactPath)) {
		echo 'Runtime artifact already up to date: ' . normalize_config_path(relative_path($repoRoot, $artifactPath)) . PHP_EOL;
		return [
			'artifact_path' => normalize_config_path(relative_path($repoRoot, $artifactPath)),
			'build_mode' => $buildMode,
		];
	}

	if ($force) {
		delete_file_if_exists($artifactPath);
		if (is_string($objectPath)) {
			delete_file_if_exists($objectPath);
		}
	}

	$compileFlags = split_shell_tokens(build_runtime_compiler_flags($compiler['kind'], $buildMode, normalize_path($repoRoot . '/runtime/include')));
	$extraCxxFlags = is_array($runtimeBuild['extra_cxxflags'] ?? null) ? $runtimeBuild['extra_cxxflags'] : [];
	$sourcePath = normalize_path($repoRoot . '/' . normalize_config_path($runtimeBuild['source_path']));
	$compileCommand = array_merge(
		scpp_compiler_command_prefix($compiler),
		[$compiler['command']],
		$compileFlags,
		$extraCxxFlags
	);

	if ($compiler['kind'] === 'gnu_like' && is_string($objectPath)) {
		$compileCommand = array_merge($compileCommand, ['-c', $sourcePath, '-o', $objectPath]);
		scpp_run_or_fail_process($compileCommand, $repoRoot, 'Failed to compile runtime object.');
		$linkFlags = is_array($compiler['linker_flags'] ?? null) ? $compiler['linker_flags'] : [];
		$runtimeLinkFlags = is_array($runtimeBuild['link_flags'] ?? null) ? $runtimeBuild['link_flags'] : [];
		$linkCommand = array_merge(
			scpp_compiler_command_prefix($compiler),
			[$compiler['command']],
			$linkFlags,
			$runtimeLinkFlags,
			[$objectPath, '-o', $artifactPath]
		);
		scpp_run_or_fail_process($linkCommand, $repoRoot, 'Failed to link runtime artifact.');
	} else {
		$compileCommand = array_merge($compileCommand, ['-c', $sourcePath, '-o', $artifactPath]);
		scpp_run_or_fail_process($compileCommand, $repoRoot, 'Failed to compile runtime artifact.');
	}

	return [
		'artifact_path' => normalize_config_path(relative_path($repoRoot, $artifactPath)),
		'build_mode' => $buildMode,
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
		$flags[] = '-g0';
		if (PHP_OS_FAMILY === 'Linux') {
			// No split dwarf output when debug info is disabled.
		}
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

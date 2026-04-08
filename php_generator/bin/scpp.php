#!/usr/bin/env php
<?php
/**
 * Prism++ CLI entrypoint.
 *
 * Current scope:
 * - single-file transpile to stdout
 * - project init scaffold
 * - one-entrypoint build scaffold backed by Ninja
 */
declare(strict_types=1);

require_once __DIR__ . '/../tools/s2s/bin/bootstrap.php';

use Scpp\S2S\Transpiler;
use Scpp\S2S\Support\S2SException;

const SCPP_VERSION = '0.1.0-dev';
const SCPP_PROJECT_CONFIG = 'prism.json';
const SCPP_STATE_FILE = 's2s_state.php';
const SCPP_S2S_SIGNATURE_VERSION = 2;

main($argv);

/**
 * Small command object for `scpp init` so init behavior stays isolated from build logic.
 */
final class ProjectInitCommand
{
	public function __construct(private readonly string $projectRoot)
	{
	}

	public function run(): void
	{
		$projectRoot = normalize_path($this->projectRoot);
		$configPath = $projectRoot . '/' . SCPP_PROJECT_CONFIG;
		if (is_file($configPath)) {
			fwrite(STDERR, 'Project config already exists: ' . relative_or_absolute($projectRoot, $configPath) . PHP_EOL);
			exit(1);
		}

		ensure_directory($projectRoot . '/.prism');
		ensure_directory($projectRoot . '/.prism/build');
		ensure_directory($projectRoot . '/.prism/generated');
		ensure_directory($projectRoot . '/.prism/cache');

		$entrypoint = guess_entrypoint($projectRoot);
		$config = [
			'config_version' => 1,
			'project_name' => basename($projectRoot),
			'entrypoint' => $entrypoint ?? 'main.php',
			'build_dir' => '.prism/build',
			'generated_dir' => '.prism/generated',
			'cache_dir' => '.prism/cache',
			'build' => [
				'backend' => 'ninja',
				'mode' => 'debug',
				'cxx' => null,
			],
		];

		$json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		if ($json === false) {
			fwrite(STDERR, "Failed to encode project config.\n");
			exit(2);
		}
		$json .= PHP_EOL;
		if (file_put_contents($configPath, $json) === false) {
			fwrite(STDERR, 'Failed to write project config: ' . relative_or_absolute($projectRoot, $configPath) . PHP_EOL);
			exit(2);
		}

		echo 'Created ' . SCPP_PROJECT_CONFIG . PHP_EOL;
		echo 'Project root: ' . $projectRoot . PHP_EOL;
		echo 'Entrypoint: ' . $config['entrypoint'] . PHP_EOL;
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
		handle_init(getcwd() === false ? '.' : getcwd());
		return;
	}

	if ($args[0] === 'build') {
		handle_build(getcwd() === false ? '.' : getcwd());
		return;
	}

	$inputFile = $args[0];
	if (!is_file($inputFile)) {
		fwrite(STDERR, "Input file not found: {$inputFile}\n");
		exit(1);
	}

	try {
		$transpiler = new Transpiler();
		$cppFile = $transpiler->transpile($inputFile);
		echo implode(PHP_EOL, $cppFile->sourceLines) . PHP_EOL;
	} catch (S2SException $e) {
		fwrite(STDERR, $e->getMessage() . PHP_EOL);
		exit(3);
	} catch (Throwable $e) {
		fwrite(STDERR, 'internal error: ' . $e->getMessage() . PHP_EOL);
		exit(4);
	}
}

function print_help(): void
{
	echo "Prism++ CLI\n";
	echo "Usage:\n";
	echo "  scpp <input.php>\n";
	echo "  scpp init\n";
	echo "  scpp build\n";
	echo "  scpp --help\n";
	echo "  scpp --version\n";
	echo "  scpp --doctor\n";
}

function print_version(): void
{
	echo 'scpp ' . SCPP_VERSION . PHP_EOL;
}

function print_doctor(): void
{
	$repoRoot = resolve_repo_root();
	$entry = __FILE__;
	$phpIni = php_ini_loaded_file();
	$astLoaded = extension_loaded('ast') ? 'yes' : 'no';
	$ninja = find_command_path(['ninja']);
	$compilerLauncher = detect_compiler_launcher();
	$compiler = detect_default_compiler();
	$projectConfig = find_project_config(getcwd() === false ? $repoRoot : getcwd());

	echo "scpp doctor\n";
	echo 'version: ' . SCPP_VERSION . PHP_EOL;
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
	echo 'ninja: ' . ($ninja ?? '(not found)') . PHP_EOL;
	echo 'sccache: ' . ($compilerLauncher ?? '(not found)') . PHP_EOL;
	echo 'default_cxx: ' . ($compiler !== null ? compiler_display_command($compiler) : '(not found)') . PHP_EOL;
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

	return normalize_path(dirname(__DIR__, 2));
}

function handle_init(string $cwd): void
{
	$command = new ProjectInitCommand($cwd);
	$command->run();
}

function handle_build(string $cwd): void
{
	$project = find_project_config($cwd);
	if ($project === null) {
		fwrite(STDERR, 'No ' . SCPP_PROJECT_CONFIG . ' found in the current directory or any parent directory.' . PHP_EOL);
		fwrite(STDERR, 'Run `scpp init` in the project root first.' . PHP_EOL);
		exit(1);
	}

	$projectRoot = $project['project_root'];
	$configPath = $project['config_path'];
	$config = load_project_config($configPath);
	$entrypoint = normalize_config_path((string) ($config['entrypoint'] ?? ''));
	if ($entrypoint === '') {
		fwrite(STDERR, 'Missing `entrypoint` in ' . SCPP_PROJECT_CONFIG . PHP_EOL);
		exit(1);
	}

	$entrypointAbs = normalize_path($projectRoot . '/' . $entrypoint);
	if (!is_file($entrypointAbs)) {
		fwrite(STDERR, 'Configured entrypoint not found: ' . $entrypoint . PHP_EOL);
		exit(1);
	}

	$ninjaPath = find_command_path(['ninja']);
	if ($ninjaPath === null) {
		fwrite(STDERR, "Ninja not found. Install it and retry.\n");
		fwrite(STDERR, install_hint_for_ninja() . PHP_EOL);
		exit(1);
	}

	$compiler = resolve_compiler($config);
	if ($compiler === null) {
		fwrite(STDERR, "No supported C++ compiler found.\n");
		fwrite(STDERR, install_hint_for_compiler() . PHP_EOL);
		exit(1);
	}
	$buildMode = resolve_build_mode($config);

	$buildDir = normalize_path($projectRoot . '/' . normalize_config_path((string) ($config['build_dir'] ?? '.prism/build')));
	$generatedDir = normalize_path($projectRoot . '/' . normalize_config_path((string) ($config['generated_dir'] ?? '.prism/generated')));
	$cacheDir = normalize_path($projectRoot . '/' . normalize_config_path((string) ($config['cache_dir'] ?? '.prism/cache')));
	ensure_directory($buildDir);
	ensure_directory($generatedDir);
	ensure_directory($cacheDir);

	$statePath = $cacheDir . '/' . SCPP_STATE_FILE;
	$state = load_s2s_state($statePath);
	$phpFiles = collect_project_php_files($projectRoot);
	$repoRoot = resolve_repo_root();
	$transpiler = new Transpiler();
	$generatorSignature = compute_s2s_generator_signature($repoRoot);
	$generatedUnits = [];
	$transpiledCount = 0;
	$skippedCount = 0;

	foreach ($phpFiles as $phpPathAbs) {
		$relativePhp = normalize_config_path(relative_path($projectRoot, $phpPathAbs));
		$generatedBase = build_generated_base($generatedDir, $relativePhp);
		$generatedHeader = $generatedBase . '.hpp';
		$generatedCpp = $generatedBase . '.cpp';
		$meta = build_file_meta($phpPathAbs);
		$previous = is_array($state['files'][$relativePhp] ?? null) ? $state['files'][$relativePhp] : null;
		$needsTranspile = !is_array($previous)
			|| !isset($previous['size'], $previous['mtime'], $previous['content_hash'], $previous['generator_signature'])
			|| (string) $previous['generator_signature'] !== $generatorSignature
			|| (int) $previous['size'] !== $meta['size']
			|| (string) $previous['content_hash'] !== $meta['content_hash']
			|| !is_file($generatedHeader)
			|| !is_file($generatedCpp);

		if ($needsTranspile) {
			try {
				$cppFile = $transpiler->transpile($phpPathAbs, false, $phpPathAbs === $entrypointAbs);
			} catch (S2SException $e) {
				fwrite(STDERR, $e->getMessage() . PHP_EOL);
				exit(3);
			} catch (Throwable $e) {
				fwrite(STDERR, 'internal error: ' . $e->getMessage() . PHP_EOL);
				exit(4);
			}

			write_text_file($generatedHeader, implode(PHP_EOL, $cppFile->headerLines) . PHP_EOL);
			write_text_file($generatedCpp, implode(PHP_EOL, $cppFile->sourceLines) . PHP_EOL);
			$transpiledCount++;
		} else {
			$skippedCount++;
		}

		$generatedUnits[] = [
			'relative_php' => $relativePhp,
			'generated_cpp' => $generatedCpp,
			'object_path' => build_object_path($buildDir, $relativePhp, $compiler['kind']),
		];
		$state['files'][$relativePhp] = [
			'size' => $meta['size'],
			'mtime' => $meta['mtime'],
			'content_hash' => $meta['content_hash'],
			'generator_signature' => $generatorSignature,
			'generated_base' => normalize_config_path(relative_path($projectRoot, $generatedBase)),
		];
	}

	$state = prune_removed_state_entries($projectRoot, $generatedDir, $state, $phpFiles);
	$state['version'] = 1;
	$state['project_root'] = $projectRoot;
	$state['updated_at'] = time();
	save_s2s_state($statePath, $state);

	if (supports_compiler_pch($compiler)) {
		write_text_file(build_runtime_pch_header_path($buildDir), render_runtime_pch_header());
	}

	$outputName = build_output_name($entrypointAbs);
	$buildNinja = render_build_ninja($projectRoot, $repoRoot, $buildDir, $generatedDir, $generatedUnits, $outputName, $compiler, $buildMode);
	$buildNinjaPath = $buildDir . '/build.ninja';
	write_text_file($buildNinjaPath, $buildNinja);
	echo 'Transpiled PHP files: ' . $transpiledCount . ', skipped unchanged: ' . $skippedCount . PHP_EOL;
	echo 'Generated Ninja file: ' . normalize_config_path(relative_path($projectRoot, $buildNinjaPath)) . PHP_EOL;
	echo 'Using compiler: ' . compiler_display_command($compiler) . ' (' . $compiler['kind'] . ')' . PHP_EOL;
	echo 'Using build mode: ' . $buildMode . PHP_EOL;
	echo 'Using repo root: ' . normalize_path($repoRoot) . PHP_EOL;

	$command = [
		$ninjaPath,
		'-f',
		normalize_config_path(relative_path($projectRoot, $buildNinjaPath)),
	];
	$descriptor = [
		0 => ['file', 'php://stdin', 'r'],
		1 => ['file', 'php://stdout', 'w'],
		2 => ['file', 'php://stderr', 'w'],
	];
	$process = proc_open($command, $descriptor, $pipes, $projectRoot);
	if (!is_resource($process)) {
		fwrite(STDERR, "Failed to start Ninja.\n");
		exit(4);
	}
	$status = proc_close($process);
	if ($status !== 0) {
		fwrite(STDERR, 'Ninja build failed. Generated file: ' . normalize_config_path(relative_path($projectRoot, $buildNinjaPath)) . PHP_EOL);
		fwrite(STDERR, "First lines of build.ninja:" . PHP_EOL);
		foreach (preview_file_lines($buildNinjaPath, 40) as $line) {
			fwrite(STDERR, $line . PHP_EOL);
		}
		exit($status);
	}

	echo 'Build completed: ' . normalize_config_path(relative_path($projectRoot, $buildDir . '/' . $outputName)) . PHP_EOL;
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
		fwrite(STDERR, 'Failed to read project config: ' . $configPath . PHP_EOL);
		exit(2);
	}

	try {
		$config = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
	} catch (JsonException $e) {
		fwrite(STDERR, 'Invalid JSON in ' . SCPP_PROJECT_CONFIG . ': ' . $e->getMessage() . PHP_EOL);
		exit(2);
	}

	if (!is_array($config)) {
		fwrite(STDERR, 'Invalid project config shape in ' . SCPP_PROJECT_CONFIG . PHP_EOL);
		exit(2);
	}

	return $config;
}

function guess_entrypoint(string $projectRoot): ?string
{
	$candidates = [
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

function ensure_directory(string $dir): void
{
	if (is_dir($dir)) {
		return;
	}
	if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
		fwrite(STDERR, 'Failed to create directory: ' . $dir . PHP_EOL);
		exit(2);
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
		fwrite(STDERR, 'Failed to write file: ' . $path . PHP_EOL);
		exit(2);
	}
}

function build_generated_base(string $generatedDir, string $relativePhp): string
{
	$trimmed = preg_replace('/\.php$/i', '', $relativePhp);
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
	return "#pragma once\n\n#include <scpp/runtime.hpp>\n";
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
		fwrite(STDERR, 'Failed to stat file: ' . $path . PHP_EOL);
		exit(2);
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
		if (strcasecmp($fileInfo->getExtension(), 'php') !== 0) {
			continue;
		}
		$files[] = $path;
	}
	sort($files, SORT_STRING);
	return $files;
}

/** @return array<string,mixed> */
function compute_s2s_generator_signature(string $repoRoot): string
{
	$parts = [
		'version:' . SCPP_S2S_SIGNATURE_VERSION,
	];

	$files = [
		$repoRoot . '/php_generator/bin/scpp.php',
		$repoRoot . '/php_generator/tools/s2s/src/Transpiler.php',
		$repoRoot . '/php_generator/tools/s2s/src/Generator/Generator.php',
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
	$trimmed = preg_replace('/\.php$/i', '', $relativePhp);
	if (!is_string($trimmed) || $trimmed === '') {
		$trimmed = 'entry';
	}
	return $buildDir . '/' . $trimmed . '.' . object_extension($compilerKind);
}

/** @return array{command:string,kind:string,launcher:?string}|null */
function resolve_compiler(array $config): ?array
{
	$override = $config['build']['cxx'] ?? null;
	if (is_string($override) && trim($override) !== '') {
		$command = trim($override);
		$path = find_command_path([$command]);
		if ($path === null && !preg_match('/[\\\/]/', $command)) {
			fwrite(STDERR, 'Configured compiler not found in PATH: ' . $command . PHP_EOL);
			exit(1);
		}
		return [
			'command' => $command,
			'kind' => strcasecmp(basename(str_replace('\\', '/', $command)), 'cl') === 0 ? 'msvc' : 'gnu_like',
			'launcher' => detect_compiler_launcher(),
		];
	}

	return detect_default_compiler();
}

function resolve_build_mode(array $config): string
{
	$mode = $config['build']['mode'] ?? 'debug';
	if (!is_string($mode)) {
		fwrite(STDERR, 'Invalid build.mode in ' . SCPP_PROJECT_CONFIG . '; expected a string.' . PHP_EOL);
		exit(1);
	}

	$normalized = strtolower(trim($mode));
	if ($normalized === '') {
		return 'debug';
	}
	if (in_array($normalized, ['debug', 'release'], true)) {
		return $normalized;
	}

	fwrite(STDERR, 'Unsupported build.mode `' . $mode . '` in ' . SCPP_PROJECT_CONFIG . '; expected `debug` or `release`.' . PHP_EOL);
	exit(1);
}

/** @return array{command:string,kind:string,launcher:?string}|null */
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
		return [
			'command' => $candidate,
			'kind' => strcasecmp($candidate, 'cl') === 0 ? 'msvc' : 'gnu_like',
			'launcher' => detect_compiler_launcher(),
		];
	}

	return null;
}

function detect_compiler_launcher(): ?string
{
	return find_command_path(['sccache']);
}

/** @param array{command:string,kind:string,launcher?:?string} $compiler */
function compiler_display_command(array $compiler): string
{
	$launcher = $compiler['launcher'] ?? null;
	if (!is_string($launcher) || $launcher === '') {
		return $compiler['command'];
	}

	$launcherName = basename(str_replace('\\', '/', $launcher));
	return $launcherName . ' ' . $compiler['command'];
}

/**
 * @param list<array{relative_php:string,generated_cpp:string,object_path:string}> $generatedUnits
 */
function render_build_ninja(string $projectRoot, string $repoRoot, string $buildDir, string $generatedDir, array $generatedUnits, string $outputName, array $compiler, string $buildMode): string
{
	$generatedIncludeDir = normalize_config_path(relative_path($projectRoot, $generatedDir));
	$runtimeCpp = normalize_config_path(relative_path($projectRoot, $repoRoot . '/runtime/src/runtime.cpp'));
	$runtimeIncludeDir = normalize_config_path(relative_path($projectRoot, $repoRoot . '/runtime/include'));
	$runtimeObj = normalize_config_path(relative_path($projectRoot, $buildDir . '/runtime.' . object_extension($compiler['kind'])));
	$output = normalize_config_path(relative_path($projectRoot, $buildDir . '/' . $outputName));
	$runtimePchHeader = normalize_config_path(relative_path($projectRoot, build_runtime_pch_header_path($buildDir)));
	$runtimePchArtifact = normalize_config_path(relative_path($projectRoot, build_runtime_pch_artifact_path($buildDir, $compiler['kind'])));
	$compilerCommand = $compiler['command'];
	$compilerLauncher = $compiler['launcher'] ?? null;

	$lines = [];
	$lines[] = 'cxx = ' . $compilerCommand;
	if (is_string($compilerLauncher) && $compilerLauncher !== '') {
		$lines[] = 'cxx_launcher = ' . $compilerLauncher;
	}
	$lines[] = 'cxxflags = ' . build_compiler_flags($compiler['kind'], $buildMode, $runtimeIncludeDir, $generatedIncludeDir);
	if (supports_compiler_pch($compiler)) {
		$lines[] = 'pch_header = ' . $runtimePchHeader;
		$lines[] = 'pchflags = -Winvalid-pch -include $pch_header';
	}
	$lines[] = '';
	if (supports_compiler_pch($compiler)) {
		$lines[] = 'rule compile_pch';
		$lines[] = '  command = ' . compiler_invocation_prefix($compiler) . ' $cxx $cxxflags -x c++-header $in -o $out';
		$lines[] = '  description = PCH $out';
		$lines[] = '';
	}
	$lines[] = 'rule compile';
	if ($compiler['kind'] === 'msvc') {
		$lines[] = '  command = ' . compiler_invocation_prefix($compiler) . ' $cxx $cxxflags /c $in /Fo$out';
	} else {
		$lines[] = '  command = ' . compiler_invocation_prefix($compiler) . ' $cxx $cxxflags' . (supports_compiler_pch($compiler) ? ' $pchflags' : '') . ' -c $in -o $out';
	}
	$lines[] = '  description = CXX $out';
	$lines[] = '';
	$lines[] = 'rule link';
	if ($compiler['kind'] === 'msvc') {
		$lines[] = '  command = ' . compiler_invocation_prefix($compiler) . ' $cxx /nologo $in /Fe$out';
	} else {
		$lines[] = '  command = ' . compiler_invocation_prefix($compiler) . ' $cxx $in -o $out';
	}
	$lines[] = '  description = LINK $out';
	$lines[] = '';

	$objectPaths = [];
	if (supports_compiler_pch($compiler)) {
		$lines[] = 'build ' . $runtimePchArtifact . ': compile_pch ' . $runtimePchHeader;
	}
	foreach ($generatedUnits as $unit) {
		$generatedCpp = normalize_config_path(relative_path($projectRoot, $unit['generated_cpp']));
		$objectPath = normalize_config_path(relative_path($projectRoot, $unit['object_path']));
		$lines[] = 'build ' . $objectPath . ': compile ' . $generatedCpp . (supports_compiler_pch($compiler) ? ' | ' . $runtimePchArtifact : '');
		$objectPaths[] = $objectPath;
	}
	$lines[] = 'build ' . $runtimeObj . ': compile ' . $runtimeCpp . (supports_compiler_pch($compiler) ? ' | ' . $runtimePchArtifact : '');
	$objectPaths[] = $runtimeObj;
	$lines[] = '';
	$lines[] = 'build ' . $output . ': link ' . implode(' ', $objectPaths);
	$lines[] = '';
	$lines[] = 'default ' . $output;
	return implode(PHP_EOL, $lines) . PHP_EOL;
}

function build_compiler_flags(string $compilerKind, string $buildMode, string $runtimeIncludeDir, string $generatedIncludeDir): string
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
			// Favor faster iteration in the default debug mode.
			$flags[] = '/Od';
			$flags[] = '/Z7';
		}
		$flags[] = '/I' . $runtimeIncludeDir;
		$flags[] = '/I' . $generatedIncludeDir;
		return implode(' ', $flags);
	}

	$flags = [
		'-std=c++23',
		'-Wall',
		'-Wextra',
	];
	if ($buildMode === 'release') {
		$flags[] = '-O3';
		$flags[] = '-DNDEBUG';
	} else {
		// Favor fast compile times while still keeping basic debug info.
		$flags[] = '-O0';
		$flags[] = '-g1';
		$flags[] = '-pipe';
	}
	$flags[] = '-I' . $runtimeIncludeDir;
	$flags[] = '-I' . $generatedIncludeDir;
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

function object_extension(string $kind): string
{
	return $kind === 'msvc' ? 'obj' : 'o';
}

/** @param list<string> $commands */
function find_command_path(array $commands): ?string
{
	$pathEnv = getenv('PATH');
	if (!is_string($pathEnv) || $pathEnv === '') {
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

function normalize_config_path(string $path): string
{
	return ltrim(str_replace('\\', '/', trim($path)), '/');
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

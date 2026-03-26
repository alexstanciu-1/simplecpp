<?php

declare(strict_types=1);

use Scpp\S2S\Transpiler;

/**
 * Phase-1 PHP test runner.
 *
 * Main modes:
 * - reset: clear volatile execution state from test JSON sidecars
 * - run:   execute matching tests with proc_open() workers, up to N in parallel
 * - worker: internal single-test executor used by the parent coordinator
 *
 * Notes:
 * - keeps static test metadata intact
 * - writes only volatile execution data into "last_run"
 * - supports --level and --test filters
 */
final class Phase1TestRunner
{
	private const DEFAULT_JOBS = 12;
	private const PHP_TIMEOUT_SECONDS = 20;
	private const GENERATE_TIMEOUT_SECONDS = 40;
	private const COMPILE_TIMEOUT_SECONDS = 60;
	private const RUN_TIMEOUT_SECONDS = 20;

	private string $projectRoot;
	private string $phpTestsRoot;
	private string $runtimeTestsRoot;
	private string $stateRoot;
	private string $selfPath;

	public function __construct()
	{
		$this->projectRoot = $this->resolveProjectRoot();
		$this->phpTestsRoot = $this->projectRoot . '/tests/php';
		$this->runtimeTestsRoot = $this->projectRoot . '/tests/runtime';
		$this->stateRoot = $this->projectRoot . '/tests/.runtime';
		$this->selfPath = realpath(__FILE__) ?: __FILE__;
	}

	public function run(array $argv): int
	{
		$options = $this->parseArgs($argv);

		return match ($options['command']) {
			'reset' => $this->resetTests($options),
			'run' => $this->runTests($options),
			'gate' => $this->runGate($options),
			'worker' => $this->runWorker($options),
			'help' => $this->printHelp(),
			default => $this->fail("Unknown command: {$options['command']}"),
		};
	}

	private function resolveProjectRoot(): string
	{
		$root = realpath(__DIR__ . '/../..');
		if ($root === false) {
			throw new RuntimeException('Failed to resolve project root.');
		}

		return $root;
	}

	private function parseArgs(array $argv): array
	{
		$command = $argv[1] ?? 'help';
		$options = [
			'command' => $command,
			'suite' => 'php',
			'level' => null,
			'test' => null,
			'jobs' => self::DEFAULT_JOBS,
			'include_disabled' => false,
			'json_path' => null,
			'san' => null,
			'help' => false,
		];

		for ($i = 2; $i < count($argv); ++$i) {
			$arg = $argv[$i];
			if ($arg === '--help' || $arg === '-h') {
				$options['help'] = true;
				continue;
			}
			if ($arg === '--include-disabled') {
				$options['include_disabled'] = true;
				continue;
			}
			if (str_starts_with($arg, '--suite=')) {
				$options['suite'] = substr($arg, 8);
				continue;
			}
			if (str_starts_with($arg, '--level=')) {
				$options['level'] = substr($arg, 8);
				continue;
			}
			if (str_starts_with($arg, '--test=')) {
				$options['test'] = substr($arg, 7);
				continue;
			}
			if (str_starts_with($arg, '--jobs=')) {
				$options['jobs'] = max(1, (int) substr($arg, 7));
				continue;
			}
			if (str_starts_with($arg, '--json=')) {
				$options['json_path'] = substr($arg, 7);
				continue;
			}
			if (str_starts_with($arg, '--san=')) {
				$options['san'] = substr($arg, 6);
				continue;
			}

			throw new RuntimeException('Unknown option: ' . $arg);
		}

		if ($options['help'] === true) {
			$options['command'] = 'help';
		}

		return $options;
	}

	private function printHelp(): int
	{
		echo <<<TXT
Usage:
	php tests/tools/run_tests.php reset [--suite=php|runtime] [--level=level_01] [--test=pattern] [--include-disabled] [--san=address,undefined]
	php tests/tools/run_tests.php run [--suite=php|runtime] [--level=level_01] [--test=pattern] [--jobs=12] [--include-disabled] [--san=address,undefined]
	php tests/tools/run_tests.php gate --suite=runtime [--jobs=12] [--include-disabled]

Filters:
	--suite=php|runtime       Select the PHP flow or the direct runtime C++ flow.
	--level=level_01          Run/reset only one level.
	--test=needle             Run/reset one specific test id, filename, or relative path fragment.
	--include-disabled        Include disabled / known-gap tests.
	--jobs=12                 Max worker processes for run mode.
	--san=list                Runtime suite only. Adds sanitizer compile flags, runtime env, and isolated state dirs.

Examples:
	php tests/tools/run_tests.php reset --suite=runtime --level=level_01
	php tests/tools/run_tests.php run --suite=php --level=level_02 --jobs=12
	php tests/tools/run_tests.php run --suite=runtime --test=runtime_ownership_001_shared_unique_weak
	php tests/tools/run_tests.php run --suite=runtime --san=address,undefined --test=stress
	php tests/tools/run_tests.php gate --suite=runtime --jobs=12
TXT;

		return 0;
	}

	private function resetTests(array $options): int
	{
		$tests = $this->discoverTests($options);
		$resetCount = 0;

		foreach ($tests as $test) {
			$this->resetOneJson($test['json_path']);
			++$resetCount;
		}

		echo "Reset {$resetCount} test JSON file(s).\n";
		return 0;
	}


	private function runGate(array $options): int
	{
		$suite = (string) ($options['suite'] ?? 'php');
		if ($suite !== 'runtime') {
			throw new RuntimeException('Gate mode currently supports only --suite=runtime.');
		}

		$passes = [
			[
				'label' => 'runtime gate / baseline',
				'options' => array_merge($options, ['command' => 'run', 'san' => null]),
			],
			[
				'label' => 'runtime gate / sanitizers',
				'options' => array_merge($options, ['command' => 'run', 'san' => 'address,undefined,leak']),
			],
		];

		foreach ($passes as $pass) {
			echo "=== {$pass['label']} ===\n";
			$exitCode = $this->runTests($pass['options']);
			if ($exitCode !== 0) {
				return $exitCode;
			}
		}

		echo "Runtime gate passed.\n";
		return 0;
	}

	private function runTests(array $options): int
	{
		$tests = $this->discoverTests($options);
		if ($tests === []) {
			echo "No tests matched.\n";
			return 0;
		}

		$this->ensureDirectory($this->stateRoot);

		$total = count($tests);
		$jobs = max(1, (int) $options['jobs']);
		$sanLabel = $this->formatSanLabel((string) ($options['san'] ?? ''));
		echo "Running {$total} test(s) with up to {$jobs} worker(s){$sanLabel}.\n";

		$queue = array_values($tests);
		$active = [];
		$completed = 0;
		$passed = 0;
		$failed = 0;
		$startedAt = microtime(true);

		while ($queue !== [] || $active !== []) {
			while ($queue !== [] && count($active) < $jobs) {
				$test = array_shift($queue);
				$handle = $this->startWorkerProcess($test['json_path'], (string) ($options['san'] ?? ''));
				$active[] = [
					'test' => $test,
					'proc' => $handle['proc'],
					'pipes' => $handle['pipes'],
					'stdout' => '',
					'stderr' => '',
					'started_at' => microtime(true),
				];
			}

			foreach ($active as $index => &$item) {
				$item['stdout'] .= stream_get_contents($item['pipes'][1]);
				$item['stderr'] .= stream_get_contents($item['pipes'][2]);

				$status = proc_get_status($item['proc']);
				if ($status['running'] === true) {
					continue;
				}

				fclose($item['pipes'][1]);
				fclose($item['pipes'][2]);
				$exitCode = proc_close($item['proc']);
				$completed++;

				$ok = ($exitCode === 0);
				if ($ok) {
					++$passed;
				} else {
					++$failed;
				}

				$label = $ok ? 'PASS' : 'FAIL';
				$relPath = $item['test']['relative_source_path'];
				echo sprintf("[%s] %3d/%3d %s\n", $label, $completed, $total, $relPath);
				if (!$ok && trim($item['stderr']) !== '') {
					echo $this->indent(trim($item['stderr'])) . "\n";
				}
				unset($active[$index]);
			}
			unset($item);

			$active = array_values($active);
			usleep(100000);
		}

		$duration = microtime(true) - $startedAt;
		echo sprintf(
			"Done. Passed: %d, Failed: %d, Total: %d, Duration: %.2fs\n",
			$passed,
			$failed,
			$total,
			$duration
		);

		return $failed === 0 ? 0 : 1;
	}

	/**
	 * Internal worker entrypoint.
	 *
	 * This mode executes exactly one test JSON sidecar and writes the results back into it.
	 */
	private function runWorker(array $options): int
	{
		$jsonPath = $options['json_path'];
		if (!is_string($jsonPath) || $jsonPath === '') {
			throw new RuntimeException('Worker mode requires --json=...');
		}

		$jsonPath = $this->normalizePath($jsonPath);
		$sanitizers = (string) ($options['san'] ?? '');
		$this->runSingleTest($jsonPath, $sanitizers);
		return 0;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function discoverTests(array $options): array
	{
		$suite = (string) ($options['suite'] ?? 'php');
		$root = match ($suite) {
			'php' => $this->phpTestsRoot,
			'runtime' => $this->runtimeTestsRoot,
			default => throw new RuntimeException('Unknown suite: ' . $suite),
		};
		$expectedExtension = $suite === 'runtime' ? 'cpp' : 'php';

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
		);

		$tests = [];
		foreach ($iterator as $fileInfo) {
			if (!$fileInfo->isFile()) {
				continue;
			}
			if ($fileInfo->getExtension() !== $expectedExtension) {
				continue;
			}

			$sourcePath = $fileInfo->getPathname();
			$jsonPath = $sourcePath . '.json';
			if (!is_file($jsonPath)) {
				continue;
			}

			$meta = $this->readJsonFile($jsonPath);
			if (!is_array($meta)) {
				continue;
			}

			if (($meta['enabled'] ?? false) !== true && $options['include_disabled'] !== true) {
				continue;
			}

			$relativeSourcePath = $this->relativePath($sourcePath);
			$testId = (string) ($meta['id'] ?? basename($sourcePath, '.' . $expectedExtension));
			$level = (string) ($meta['level'] ?? '');

			if (is_string($options['level']) && $options['level'] !== '' && $level !== $options['level']) {
				continue;
			}
			if (is_string($options['test']) && $options['test'] !== '') {
				$needle = $options['test'];
				if (
					stripos($testId, $needle) === false
					&& stripos($relativeSourcePath, $needle) === false
					&& stripos(basename($sourcePath), $needle) === false
				) {
					continue;
				}
			}

			$tests[] = [
				'id' => $testId,
				'level' => $level,
				'suite' => $suite,
				'source_path' => $sourcePath,
				'json_path' => $jsonPath,
				'relative_source_path' => $relativeSourcePath,
			];
		}

		usort(
			$tests,
			static fn (array $a, array $b): int => strcmp($a['relative_source_path'], $b['relative_source_path'])
		);

		return $tests;
	}

	private function resetOneJson(string $jsonPath): void
	{
		$data = $this->readJsonFile($jsonPath);
		if (!is_array($data)) {
			throw new RuntimeException('Invalid JSON sidecar: ' . $jsonPath);
		}

		$data['last_run'] = $this->buildEmptyLastRunState();
		$this->writeJsonFile($jsonPath, $data);
	}

	/**
	 * Executes one end-to-end test.
	 */
	private function runSingleTest(string $jsonPath, string $sanitizers = ''): void
	{
		$sourcePath = substr($jsonPath, 0, -5);
		if (str_ends_with($sourcePath, '.cpp')) {
			$this->runSingleRuntimeTest($jsonPath, $sourcePath, $sanitizers);
			return;
		}

		$this->runSinglePhpFlowTest($jsonPath, $sourcePath);
	}

	private function runSinglePhpFlowTest(string $jsonPath, string $phpPath): void
	{
		$meta = $this->readJsonFile($jsonPath);
		if (!is_array($meta)) {
			throw new RuntimeException('Invalid JSON sidecar: ' . $jsonPath);
		}
		if (!is_file($phpPath)) {
			throw new RuntimeException('Missing PHP test file: ' . $phpPath);
		}

		if ($sanitizers !== '') {
			$meta['build'] = is_array($meta['build'] ?? null) ? $meta['build'] : [];
			$meta['build']['sanitizers'] = $sanitizers;
		}

		$meta['last_run'] = $this->buildEmptyLastRunState();
		$meta['last_run']['started_at'] = gmdate('c');
		$meta['last_run']['worker'] = [
			'pid' => getmypid(),
			'host' => php_uname('n'),
		];
		$meta['last_run']['paths'] = [
			'php' => $this->relativePath($phpPath),
			'json' => $this->relativePath($jsonPath),
		];

		$tempDir = $this->createTempDirForTest($meta, $phpPath);
		$phpCode = (string) file_get_contents($phpPath);
		$phpCopyPath = $tempDir . '/input.php';
		file_put_contents($phpCopyPath, $phpCode);

		try {
			$expect = is_array($meta['expect'] ?? null) ? $meta['expect'] : [];
			$compare = is_array($meta['compare'] ?? null) ? $meta['compare'] : [];

			$phpRun = $this->runCommand(['php', $phpPath], $this->projectRoot, self::PHP_TIMEOUT_SECONDS);
			$meta['last_run']['stages']['php'] = [
				'command' => ['php', $this->relativePath($phpPath)],
				'exit_code' => $phpRun['exit_code'],
				'stdout' => $this->normalizeOutput((string) $phpRun['stdout'], $compare, 'stdout'),
				'stderr' => $this->normalizeOutput((string) $phpRun['stderr'], $compare, 'stderr'),
				'timed_out' => $phpRun['timed_out'],
				'duration_ms' => $phpRun['duration_ms'],
				'success' => ($phpRun['exit_code'] === 0 && $phpRun['timed_out'] === false),
				'comparison_ok' => true,
				'comparison_notes' => [],
			];

			$phpExpect = is_array($expect['php'] ?? null) ? $expect['php'] : [];
			if (($phpExpect['run'] ?? false) === true) {
				$phpComparison = $this->compareStageRun($phpExpect, $meta['last_run']['stages']['php'], $compare);
				$meta['last_run']['stages']['php']['comparison_ok'] = $phpComparison['ok'];
				$meta['last_run']['stages']['php']['comparison_notes'] = $phpComparison['notes'];
			}

			$generatorResult = $this->runGeneratorStage($phpCopyPath);
			$meta['last_run']['stages']['generate'] = [
				'success' => $generatorResult['success'],
				'errors' => $generatorResult['errors'],
				'duration_ms' => $generatorResult['duration_ms'],
				'header_path' => isset($generatorResult['header_path']) ? $this->relativePath($generatorResult['header_path']) : null,
				'source_path' => isset($generatorResult['source_path']) ? $this->relativePath($generatorResult['source_path']) : null,
				'compile_unit_path' => isset($generatorResult['compile_unit_path']) ? $this->relativePath($generatorResult['compile_unit_path']) : null,
				'comparison_ok' => true,
				'comparison_notes' => [],
			];
			$generateExpect = is_array($expect['generate'] ?? null) ? $expect['generate'] : [];
			$generateComparison = $this->compareBooleanStage(
				(bool) ($generateExpect['success'] ?? false),
				$generatorResult['success'],
				(array) ($generateExpect['error_contains'] ?? []),
				$generatorResult['errors'],
				(bool) ($compare['case_sensitive_errors'] ?? true),
				'generate'
			);
			$meta['last_run']['stages']['generate']['comparison_ok'] = $generateComparison['ok'];
			$meta['last_run']['stages']['generate']['comparison_notes'] = $generateComparison['notes'];

			if ($generatorResult['success'] === true) {
				$compileRun = $this->runCompileStage((string) $generatorResult['compile_unit_path'], $tempDir);
				$meta['last_run']['stages']['compile'] = [
					'success' => ($compileRun['exit_code'] === 0 && $compileRun['timed_out'] === false),
					'exit_code' => $compileRun['exit_code'],
					'stdout' => $this->normalizeOutput((string) $compileRun['stdout'], $compare, 'stdout'),
					'stderr' => $this->normalizeOutput((string) $compileRun['stderr'], $compare, 'stderr'),
					'timed_out' => $compileRun['timed_out'],
					'duration_ms' => $compileRun['duration_ms'],
					'binary_path' => $this->relativePath((string) $compileRun['binary_path']),
					'comparison_ok' => true,
					'comparison_notes' => [],
				];
				$compileExpect = is_array($expect['compile'] ?? null) ? $expect['compile'] : [];
				$compileComparison = $this->compareBooleanStage(
					(bool) ($compileExpect['success'] ?? false),
					$meta['last_run']['stages']['compile']['success'],
					(array) ($compileExpect['error_contains'] ?? []),
					$meta['last_run']['stages']['compile']['stderr'],
					(bool) ($compare['case_sensitive_errors'] ?? true),
					'compile'
				);
				$meta['last_run']['stages']['compile']['comparison_ok'] = $compileComparison['ok'];
				$meta['last_run']['stages']['compile']['comparison_notes'] = $compileComparison['notes'];

				if ($meta['last_run']['stages']['compile']['success'] === true) {
					$cppRun = $this->runCommand([(string) $compileRun['binary_path']], $tempDir, self::RUN_TIMEOUT_SECONDS);
					$meta['last_run']['stages']['run'] = [
						'success' => ($cppRun['exit_code'] === 0 && $cppRun['timed_out'] === false),
						'exit_code' => $cppRun['exit_code'],
						'stdout' => $this->normalizeOutput((string) $cppRun['stdout'], $compare, 'stdout'),
						'stderr' => $this->normalizeOutput((string) $cppRun['stderr'], $compare, 'stderr'),
						'timed_out' => $cppRun['timed_out'],
						'duration_ms' => $cppRun['duration_ms'],
						'comparison_ok' => true,
						'comparison_notes' => [],
					];
					$runExpect = is_array($expect['run'] ?? null) ? $expect['run'] : [];
					$runComparison = $this->compareStageRun($runExpect, $meta['last_run']['stages']['run'], $compare);
					$meta['last_run']['stages']['run']['comparison_ok'] = $runComparison['ok'];
					$meta['last_run']['stages']['run']['comparison_notes'] = $runComparison['notes'];
				} else {
					$meta['last_run']['stages']['run'] = [
						'skipped' => true,
						'reason' => 'compile_failed',
						'comparison_ok' => (($expect['run']['success'] ?? null) === false),
						'comparison_notes' => [],
					];
				}
			} else {
				$meta['last_run']['stages']['compile'] = [
					'skipped' => true,
					'reason' => 'generate_failed',
					'comparison_ok' => (($expect['compile']['success'] ?? null) === false),
					'comparison_notes' => [],
				];
				$meta['last_run']['stages']['run'] = [
					'skipped' => true,
					'reason' => 'generate_failed',
					'comparison_ok' => (($expect['run']['success'] ?? null) === false),
					'comparison_notes' => [],
				];
			}

			$meta['last_run']['summary'] = $this->buildSummary($meta, $expect);
			$meta['last_run']['finished_at'] = gmdate('c');
			$this->writeJsonFile($jsonPath, $meta);

			if (($meta['last_run']['summary']['pass'] ?? false) !== true) {
				throw new RuntimeException((string) ($meta['last_run']['summary']['message'] ?? 'Test failed.'));
			}
		} catch (Throwable $throwable) {
			$meta['last_run']['summary'] = [
				'pass' => false,
				'message' => $throwable->getMessage(),
				'outcome' => 'exception',
			];
			$meta['last_run']['exception'] = $this->formatThrowable($throwable);
			$meta['last_run']['finished_at'] = gmdate('c');
			$this->writeJsonFile($jsonPath, $meta);
			throw $throwable;
		}
	}

	private function runSingleRuntimeTest(string $jsonPath, string $sourcePath, string $sanitizers = ''): void
	{
		$meta = $this->readJsonFile($jsonPath);
		if (!is_array($meta)) {
			throw new RuntimeException('Invalid JSON sidecar: ' . $jsonPath);
		}
		if (!is_file($sourcePath)) {
			throw new RuntimeException('Missing runtime test file: ' . $sourcePath);
		}

		$meta['last_run'] = $this->buildEmptyLastRunState();
		$meta['last_run']['started_at'] = gmdate('c');
		$meta['last_run']['worker'] = [
			'pid' => getmypid(),
			'host' => php_uname('n'),
		];
		$meta['last_run']['paths'] = [
			'source' => $this->relativePath($sourcePath),
			'json' => $this->relativePath($jsonPath),
		];
		$meta['last_run']['sanitizers'] = $this->parseSanitizers((string) ($meta['build']['sanitizers'] ?? ''));
		$meta['last_run']['stages']['php'] = [
			'skipped' => true,
			'reason' => 'runtime_suite',
			'comparison_ok' => true,
			'comparison_notes' => [],
		];
		$meta['last_run']['stages']['generate'] = [
			'skipped' => true,
			'reason' => 'runtime_suite',
			'comparison_ok' => true,
			'comparison_notes' => [],
		];

		$tempDir = $this->createTempDirForTest($meta, $sourcePath);
		try {
			$expect = is_array($meta['expect'] ?? null) ? $meta['expect'] : [];
			$compare = is_array($meta['compare'] ?? null) ? $meta['compare'] : [];
			$build = is_array($meta['build'] ?? null) ? $meta['build'] : [];

			$compileRun = $this->runRuntimeCompileStage($sourcePath, $tempDir, $build);
			$meta['last_run']['stages']['compile'] = [
				'success' => ($compileRun['exit_code'] === 0 && $compileRun['timed_out'] === false),
				'exit_code' => $compileRun['exit_code'],
				'stdout' => $this->normalizeOutput((string) $compileRun['stdout'], $compare, 'stdout'),
				'stderr' => $this->normalizeOutput((string) $compileRun['stderr'], $compare, 'stderr'),
				'timed_out' => $compileRun['timed_out'],
				'duration_ms' => $compileRun['duration_ms'],
				'binary_path' => $this->relativePath((string) $compileRun['binary_path']),
				'command' => $compileRun['command'],
				'env' => $compileRun['env'],
				'comparison_ok' => true,
				'comparison_notes' => [],
			];
			$compileExpect = is_array($expect['compile'] ?? null) ? $expect['compile'] : [];
			$compileComparison = $this->compareBooleanStage(
				(bool) ($compileExpect['success'] ?? false),
				$meta['last_run']['stages']['compile']['success'],
				(array) ($compileExpect['error_contains'] ?? []),
				$meta['last_run']['stages']['compile']['stderr'],
				(bool) ($compare['case_sensitive_errors'] ?? true),
				'compile'
			);
			$meta['last_run']['stages']['compile']['comparison_ok'] = $compileComparison['ok'];
			$meta['last_run']['stages']['compile']['comparison_notes'] = $compileComparison['notes'];

			if ($meta['last_run']['stages']['compile']['success'] === true) {
				$runCommand = [(string) $compileRun['binary_path']];
				foreach ((array) ($build['run_args'] ?? []) as $arg) {
					$runCommand[] = (string) $arg;
				}
				$runTimeout = (int) ($build['run_timeout_seconds'] ?? self::RUN_TIMEOUT_SECONDS);
				$runtimeEnv = array_merge($this->buildSanitizerRunEnvironment((string) ($build['sanitizers'] ?? '')), (array) ($build['env'] ?? []));
				$cppRun = $this->runCommand($runCommand, $tempDir, $runTimeout, $runtimeEnv);
				$meta['last_run']['stages']['run'] = [
					'success' => ($cppRun['exit_code'] === 0 && $cppRun['timed_out'] === false),
					'exit_code' => $cppRun['exit_code'],
					'stdout' => $this->normalizeOutput((string) $cppRun['stdout'], $compare, 'stdout'),
					'stderr' => $this->normalizeOutput((string) $cppRun['stderr'], $compare, 'stderr'),
					'timed_out' => $cppRun['timed_out'],
					'duration_ms' => $cppRun['duration_ms'],
					'command' => array_map('strval', $runCommand),
					'env' => $runtimeEnv,
					'comparison_ok' => true,
					'comparison_notes' => [],
				];
				$runExpect = is_array($expect['run'] ?? null) ? $expect['run'] : [];
				$runComparison = $this->compareStageRun($runExpect, $meta['last_run']['stages']['run'], $compare);
				$meta['last_run']['stages']['run']['comparison_ok'] = $runComparison['ok'];
				$meta['last_run']['stages']['run']['comparison_notes'] = $runComparison['notes'];
			} else {
				$meta['last_run']['stages']['run'] = [
					'skipped' => true,
					'reason' => 'compile_failed',
					'comparison_ok' => (($expect['run']['success'] ?? null) === false),
					'comparison_notes' => [],
				];
			}

			$meta['last_run']['summary'] = $this->buildSummary($meta, $expect);
			$meta['last_run']['finished_at'] = gmdate('c');
			$this->writeJsonFile($jsonPath, $meta);

			if (($meta['last_run']['summary']['pass'] ?? false) !== true) {
				throw new RuntimeException((string) ($meta['last_run']['summary']['message'] ?? 'Test failed.'));
			}
		} catch (Throwable $throwable) {
			$meta['last_run']['summary'] = [
				'pass' => false,
				'message' => $throwable->getMessage(),
				'outcome' => 'exception',
			];
			$meta['last_run']['exception'] = $this->formatThrowable($throwable);
			$meta['last_run']['finished_at'] = gmdate('c');
			$this->writeJsonFile($jsonPath, $meta);
			throw $throwable;
		}
	}

	private function runGeneratorStage(string $phpPath): array
	{
		require_once $this->projectRoot . '/php_generator/tools/s2s/bin/bootstrap.php';

		$started = microtime(true);
		$transpiler = new Transpiler();
		$cppFile = $transpiler->transpile($phpPath);
		$durationMs = (int) round((microtime(true) - $started) * 1000);

		$headerPath = dirname($phpPath) . '/generated.hpp';
		$sourcePath = dirname($phpPath) . '/generated.cpp';
		$compileUnitPath = dirname($phpPath) . '/generated.build.cpp';
		file_put_contents($headerPath, implode("\n", $cppFile->headerLines) . "\n");
		file_put_contents($sourcePath, implode("\n", $cppFile->sourceLines) . "\n");
		file_put_contents($compileUnitPath, $this->buildNaturalCompileUnit($cppFile->headerLines, $cppFile->sourceLines));

		$errors = implode("\n", $cppFile->errors);
		return [
			'success' => ($errors === ''),
			'errors' => $errors,
			'duration_ms' => $durationMs,
			'header_path' => $headerPath,
			'source_path' => $sourcePath,
			'compile_unit_path' => $compileUnitPath,
		];
	}

	private function runCompileStage(string $compileUnitPath, string $workDir): array
	{
		$binaryPath = $workDir . '/test.out';
		$result = $this->runCommand(
			[
				'g++',
				'-std=c++23',
				'-O3',
				$compileUnitPath,
				$this->projectRoot . '/runtime/src/runtime.cpp',
				'-I',
				$this->projectRoot . '/runtime/include',
				'-o',
				$binaryPath,
			],
			$workDir,
			self::COMPILE_TIMEOUT_SECONDS
		);
		$result['binary_path'] = $binaryPath;
		return $result;
	}

	private function runRuntimeCompileStage(string $sourcePath, string $workDir, array $build): array
	{
		$binaryPath = $workDir . '/test.out';
		$compiler = (string) ($build['compiler'] ?? 'g++');
		$languageStandard = (string) ($build['language_standard'] ?? 'c++23');
		$flags = [];
		foreach ((array) ($build['flags'] ?? ['-O3']) as $flag) {
			if (is_string($flag) && $flag !== '') {
				$flags[] = $flag;
			}
		}
		$sanitizers = $this->parseSanitizers((string) ($build['sanitizers'] ?? ''));
		if ($sanitizers !== []) {
			$flags = array_merge($flags, ['-g', '-fno-omit-frame-pointer', '-fsanitize=' . implode(',', $sanitizers)]);
		}
		$command = array_merge(
			[$compiler, '-std=' . $languageStandard],
			$flags,
			[
				$sourcePath,
				$this->projectRoot . '/runtime/src/runtime.cpp',
				'-I',
				$this->projectRoot,
				'-I',
				$this->projectRoot . '/runtime/include',
				'-o',
				$binaryPath,
			]
		);
		$compileTimeout = (int) ($build['compile_timeout_seconds'] ?? self::COMPILE_TIMEOUT_SECONDS);
		$compileEnv = array_merge($this->buildSanitizerRunEnvironment((string) ($build['sanitizers'] ?? '')), (array) ($build['env'] ?? []));
		$result = $this->runCommand($command, $workDir, $compileTimeout, $compileEnv);
		$result['binary_path'] = $binaryPath;
		$result['command'] = array_map('strval', $command);
		$result['env'] = $compileEnv;
		return $result;
	}

	private function startWorkerProcess(string $jsonPath, string $sanitizers = ''): array
	{
		$command = [
			PHP_BINARY,
			$this->selfPath,
			'worker',
			'--json=' . $jsonPath,
		];
		if ($sanitizers !== '') {
			$command[] = '--san=' . $sanitizers;
		}
		$commandString = $this->buildShellCommand($command);
		$descriptors = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];

		$proc = proc_open($commandString, $descriptors, $pipes, $this->projectRoot);
		if (!is_resource($proc)) {
			throw new RuntimeException('Failed to start worker process.');
		}

		fclose($pipes[0]);
		stream_set_blocking($pipes[1], false);
		stream_set_blocking($pipes[2], false);

		return ['proc' => $proc, 'pipes' => $pipes];
	}

	private function runCommand(array $command, string $cwd, int $timeoutSeconds, array $env = []): array
	{
		$descriptors = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$commandString = $this->buildShellCommand($command);
		$procEnv = $env === [] ? null : array_merge($_ENV, $_SERVER, $env);
		$proc = proc_open($commandString, $descriptors, $pipes, $cwd, $procEnv);
		if (!is_resource($proc)) {
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
			$stdout .= stream_get_contents($pipes[1]);
			$stderr .= stream_get_contents($pipes[2]);
			$status = proc_get_status($proc);
			if ($status['running'] === false) {
				break;
			}
			if ((microtime(true) - $startedAt) > $timeoutSeconds) {
				$timedOut = true;
				proc_terminate($proc);
				usleep(100000);
				$status = proc_get_status($proc);
				if ($status['running'] === true) {
					proc_terminate($proc, 9);
				}
				break;
			}
			usleep(50000);
		}

		$stdout .= stream_get_contents($pipes[1]);
		$stderr .= stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$exitCode = proc_close($proc);
		$durationMs = (int) round((microtime(true) - $startedAt) * 1000);

		return [
			'exit_code' => $exitCode,
			'stdout' => $stdout,
			'stderr' => $stderr,
			'timed_out' => $timedOut,
			'duration_ms' => $durationMs,
		];
	}

	private function compareStageRun(array $expect, array $actual, array $compare): array
	{
		$notes = [];
		$ok = true;

		$expectedSuccess = (bool) ($expect['success'] ?? (($expect['run'] ?? false) === true));
		if (($actual['success'] ?? false) !== $expectedSuccess) {
			$ok = false;
			$notes[] = sprintf('success mismatch: expected=%s actual=%s', $expectedSuccess ? 'true' : 'false', ($actual['success'] ?? false) ? 'true' : 'false');
		}

		if (array_key_exists('exit_code', $expect) && (int) $expect['exit_code'] !== (int) ($actual['exit_code'] ?? -99999)) {
			$ok = false;
			$notes[] = sprintf('exit_code mismatch: expected=%d actual=%d', (int) $expect['exit_code'], (int) ($actual['exit_code'] ?? -99999));
		}

		if (array_key_exists('stdout', $expect) && (string) $expect['stdout'] !== (string) ($actual['stdout'] ?? '')) {
			$ok = false;
			$notes[] = 'stdout mismatch';
		}

		if (array_key_exists('stderr', $expect) && (string) $expect['stderr'] !== (string) ($actual['stderr'] ?? '')) {
			$ok = false;
			$notes[] = 'stderr mismatch';
		}

		return ['ok' => $ok, 'notes' => $notes];
	}

	private function compareBooleanStage(bool $expectedSuccess, bool $actualSuccess, array $needles, string $haystack, bool $caseSensitive, string $stage): array
	{
		$notes = [];
		$ok = true;
		if ($expectedSuccess !== $actualSuccess) {
			$ok = false;
			$notes[] = sprintf('%s success mismatch: expected=%s actual=%s', $stage, $expectedSuccess ? 'true' : 'false', $actualSuccess ? 'true' : 'false');
		}

		foreach ($needles as $needle) {
			if (!is_string($needle) || $needle === '') {
				continue;
			}
			$found = $caseSensitive ? (strpos($haystack, $needle) !== false) : (stripos($haystack, $needle) !== false);
			if ($found === false) {
				$ok = false;
				$notes[] = sprintf('%s error text missing substring: %s', $stage, $needle);
			}
		}

		return ['ok' => $ok, 'notes' => $notes];
	}

	private function buildSummary(array $meta, array $expect): array
	{
		$stages = (array) ($meta['last_run']['stages'] ?? []);
		$notes = [];
		$pass = true;

		foreach (['php', 'generate', 'compile', 'run'] as $stageName) {
			$stage = (array) ($stages[$stageName] ?? []);
			if (($stage['skipped'] ?? false) === true) {
				if (($stage['comparison_ok'] ?? false) !== true) {
					$pass = false;
					$notes[] = $stageName . ' skipped unexpectedly';
				}
				continue;
			}
			if (($stage['comparison_ok'] ?? false) !== true) {
				$pass = false;
				$stageNotes = (array) ($stage['comparison_notes'] ?? []);
				$notes[] = $stageName . ': ' . implode('; ', $stageNotes);
			}
		}

		return [
			'pass' => $pass,
			'message' => $pass ? 'All stage expectations matched.' : implode(' | ', $notes),
			'outcome' => $pass ? 'pass' : 'fail',
		];
	}


private function parseSanitizers(string $value): array
{
	$value = trim($value);
	if ($value === '') {
		return [];
	}

	$allowed = ['address', 'undefined', 'leak'];
	$result = [];
	foreach (explode(',', $value) as $part) {
		$name = strtolower(trim($part));
		if ($name === '') {
			continue;
		}
		if (!in_array($name, $allowed, true)) {
			throw new RuntimeException('Unsupported sanitizer: ' . $name);
		}
		if (!in_array($name, $result, true)) {
			$result[] = $name;
		}
	}

	return $result;
}

private function formatSanLabel(string $value): string
{
	$sanitizers = $this->parseSanitizers($value);
	if ($sanitizers === []) {
		return '';
	}

	return ' [san=' . implode(',', $sanitizers) . ']';
}

private function buildSanitizedStateRoot(string $sanValue): string
{
	$sanitizers = $this->parseSanitizers($sanValue);
	if ($sanitizers === []) {
		return $this->projectRoot . '/tests/.runtime';
	}

	return $this->projectRoot . '/tests/.runtime_san_' . implode('_', $sanitizers);
}

private function buildSanitizerRunEnvironment(string $sanValue): array
{
	$sanitizers = $this->parseSanitizers($sanValue);
	if ($sanitizers === []) {
		return [];
	}

	$env = [];
	if (in_array('address', $sanitizers, true) || in_array('leak', $sanitizers, true)) {
		$env['ASAN_OPTIONS'] = 'detect_leaks=1:abort_on_error=1:strict_string_checks=1:check_initialization_order=1';
		$env['LSAN_OPTIONS'] = 'exitcode=101:report_objects=1';
	}
	if (in_array('undefined', $sanitizers, true)) {
		$env['UBSAN_OPTIONS'] = 'print_stacktrace=1:halt_on_error=1';
	}

	return $env;
}

	private function buildEmptyLastRunState(): array
	{
		return [
			'started_at' => null,
			'finished_at' => null,
			'worker' => null,
			'paths' => null,
			'sanitizers' => null,
			'stages' => [
				'php' => null,
				'generate' => null,
				'compile' => null,
				'run' => null,
			],
			'summary' => [
				'pass' => null,
				'message' => null,
				'outcome' => 'not_run',
			],
			'exception' => null,
		];
	}

	private function createTempDirForTest(array $meta, string $phpPath): string
	{
		$testId = preg_replace('/[^A-Za-z0-9_.-]+/', '_', (string) ($meta['id'] ?? basename($phpPath, '.php')));
		$stateRoot = $this->buildSanitizedStateRoot((string) ($meta['build']['sanitizers'] ?? ''));
		$this->ensureDirectory($stateRoot);
		$dir = $stateRoot . '/' . $testId . '_' . bin2hex(random_bytes(4));
		$this->ensureDirectory($dir);
		return $dir;
	}

	private function ensureDirectory(string $path): void
	{
		if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
			throw new RuntimeException('Failed to create directory: ' . $path);
		}
	}

	private function normalizeOutput(string $text, array $compare, string $channel): string
	{
		$keyNormalize = $channel === 'stdout' ? 'normalize_stdout_newlines' : 'normalize_stderr_newlines';
		$keyTrim = $channel === 'stdout' ? 'trim_final_stdout_newline' : 'trim_final_stderr_newline';

		if (($compare[$keyNormalize] ?? false) === true) {
			$text = str_replace(["\r\n", "\r"], "\n", $text);
		}
		if (($compare[$keyTrim] ?? false) === true) {
			$text = preg_replace('/\n\z/', '', $text) ?? $text;
		}

		return $text;
	}

	private function buildNaturalCompileUnit(array $headerLines, array $sourceLines): string
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

	private function buildShellCommand(array $parts): string
	{
		return implode(' ', array_map('escapeshellarg', $parts));
	}

	private function relativePath(string $path): string
	{
		$normalized = $this->normalizePath($path);
		$root = $this->normalizePath($this->projectRoot);
		if (str_starts_with($normalized, $root . '/')) {
			return substr($normalized, strlen($root) + 1);
		}

		return $normalized;
	}

	private function normalizePath(string $path): string
	{
		$real = realpath($path);
		if ($real !== false) {
			return str_replace('\\', '/', $real);
		}
		return str_replace('\\', '/', $path);
	}

	private function readJsonFile(string $path): mixed
	{
		$content = file_get_contents($path);
		if ($content === false) {
			throw new RuntimeException('Failed to read file: ' . $path);
		}

		return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
	}

	private function writeJsonFile(string $path, array $data): void
	{
		$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
		if ($json === false) {
			throw new RuntimeException('Failed to encode JSON: ' . $path);
		}

		if (file_put_contents($path, $json . "\n") === false) {
			throw new RuntimeException('Failed to write JSON: ' . $path);
		}
	}

	private function formatThrowable(Throwable $throwable): array
	{
		return [
			'class' => $throwable::class,
			'message' => $throwable->getMessage(),
			'file' => $this->relativePath($throwable->getFile()),
			'line' => $throwable->getLine(),
			'trace' => explode("\n", $throwable->getTraceAsString()),
		];
	}

	private function indent(string $text): string
	{
		return preg_replace('/^/m', "\t", $text) ?? $text;
	}

	private function fail(string $message): int
	{
		fwrite(STDERR, $message . "\n");
		return 1;
	}
}

$runner = new Phase1TestRunner();
try {
	exit($runner->run($argv));
} catch (Throwable $throwable) {
	fwrite(STDERR, $throwable->getMessage() . "\n");
	exit(1);
}

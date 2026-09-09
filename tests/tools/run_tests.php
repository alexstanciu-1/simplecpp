<?php

declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

use Scpp\S2S\PreTokenizer\PreTokenizer;
use Scpp\S2S\Transpiler;

/**
 * Phase-1 PHP test runner.
 *
 * Main modes:
 * - reset: regenerate volatile execution state in *.test-results.json files
 * - run:   execute matching tests with proc_open() workers, up to N in parallel
 * - worker: internal single-test executor used by the parent coordinator
 *
 * Notes:
 * - reads static test metadata from *.test-info.json
 * - writes only volatile execution data into *.test-results.json
 * - preserves source.<ext>.json for generator AST fixtures
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
	private string $phpMatrixTestsRoot;
	private string $runtimeMatrixTestsRoot;
	private string $stateRoot;
	private string $runTestsRoot;
	private string $selfPath;

	public function __construct()
	{
		$this->projectRoot = $this->resolveProjectRoot();
		$this->phpTestsRoot = $this->projectRoot . '/tests/php';
		$this->runtimeTestsRoot = $this->projectRoot . '/tests/runtime';
		$this->phpMatrixTestsRoot = $this->projectRoot . '/tests/php-matrix';
		$this->runtimeMatrixTestsRoot = $this->projectRoot . '/tests/runtime-matrix';
		$this->stateRoot = $this->projectRoot . '/tests/.runtime';
		$this->runTestsRoot = $this->projectRoot . '/tests/.run-tests';
		$this->selfPath = realpath(__FILE__) ?: __FILE__;
	}

	public function run(array $argv): int
	{
		$options = $this->parseArgs($argv);
		if (isset($options['profile']) && is_string($options['profile'])) {
			putenv('SCPP_TEST_PHP_PROFILE=' . strtolower(trim($options['profile'])));
			$_SERVER['SCPP_TEST_PHP_PROFILE'] = strtolower(trim($options['profile']));
		}

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
			'profile' => 'legacy',
			'level' => null,
			'test' => null,
			'jobs' => self::DEFAULT_JOBS,
			'include_disabled' => false,
			'include_network' => false,
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
			if ($arg === '--include-network') {
				$options['include_network'] = true;
				continue;
			}
			if (str_starts_with($arg, '--suite=')) {
				$options['suite'] = substr($arg, 8);
				continue;
			}
			if (str_starts_with($arg, '--profile=')) {
				$options['profile'] = substr($arg, 10);
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
	php tests/tools/run_tests.php reset [--suite=php|runtime|php-matrix|runtime-matrix] [--profile=legacy|strict] [--level=level_01] [--test=pattern] [--include-disabled] [--include-network] [--san=address,undefined]
	php tests/tools/run_tests.php run [--suite=php|runtime|php-matrix|runtime-matrix] [--profile=legacy|strict] [--level=level_01] [--test=pattern] [--jobs=12] [--include-disabled] [--include-network] [--san=address,undefined]
	php tests/tools/run_tests.php gate --suite=runtime [--jobs=12] [--include-disabled]

Filters:
	--suite=php|runtime|php-matrix|runtime-matrix
	                        Select the curated or matrix-generated PHP/runtime flow.
	--profile=legacy|strict PHP suite only. `strict` includes only strict-prefixed tests.
	--level=level_01          Run/reset only one level.
	--test=needle             Run/reset one specific test id, filename, or relative path fragment.
	--include-disabled        Include disabled / known-gap tests.
	--include-network         Include opt-in network/external integration tests.
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
			$this->resetOneTestResults($test['info_path'], $test['results_path'], $test['source_path']);
			++$resetCount;
		}

		echo "Reset {$resetCount} test result file(s).\n";
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

		if ($jobs === 1) {
			return $this->runTestsSequentially($tests, (string) ($options['san'] ?? ''));
		}

		$queue = array_values($tests);
		$active = [];
		$completed = 0;
		$passed = 0;
		$failed = 0;
		$startedAt = microtime(true);

		while ($queue !== [] || $active !== []) {
			while ($queue !== [] && count($active) < $jobs) {
				$test = array_shift($queue);
				$handle = $this->startWorkerProcess($test['info_path'], (string) ($options['san'] ?? ''), (string) ($options['profile'] ?? 'legacy'));
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
				$exitCode = (is_int($status['exitcode'] ?? null) && (int) $status['exitcode'] >= 0)
					? (int) $status['exitcode']
					: proc_close($item['proc']);
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
	 * @param list<array<string, mixed>> $tests
	 */
	private function runTestsSequentially(array $tests, string $sanitizers = ''): int
	{
		$total = count($tests);
		$passed = 0;
		$failed = 0;
		$startedAt = microtime(true);

		foreach (array_values($tests) as $index => $test) {
			$ok = true;
			$stderr = '';
			try {
				$this->runSingleTest((string) $test['info_path'], $sanitizers);
			} catch (Throwable $throwable) {
				$ok = false;
				$stderr = $throwable->getMessage();
			}

			if ($ok) {
				++$passed;
			} else {
				++$failed;
			}

			$label = $ok ? 'PASS' : 'FAIL';
			$relPath = (string) $test['relative_source_path'];
			echo sprintf("[%s] %3d/%3d %s\n", $label, $index + 1, $total, $relPath);
			if (!$ok && trim($stderr) !== '') {
				echo $this->indent(trim($stderr)) . "\n";
			}
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
	 * This mode executes exactly one test definition file and writes the results into its paired results file.
	 */
	private function runWorker(array $options): int
	{
		$infoPath = $options['json_path'];
		if (!is_string($infoPath) || $infoPath === '') {
			throw new RuntimeException('Worker mode requires --json=...');
		}

		$infoPath = $this->normalizePath($infoPath);
		$sanitizers = (string) ($options['san'] ?? '');
		$this->runSingleTest($infoPath, $sanitizers);
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
			'php-matrix' => $this->phpMatrixTestsRoot,
			'runtime-matrix' => $this->runtimeMatrixTestsRoot,
			default => throw new RuntimeException('Unknown suite: ' . $suite),
		};
		$expectedExtensions = in_array($suite, ['runtime', 'runtime-matrix'], true)
			? ['cpp']
			: ['phs', 'php'];

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
		);

		$tests = [];
		foreach ($iterator as $fileInfo) {
			if (!$fileInfo->isFile()) {
				continue;
			}
			if (!in_array(strtolower($fileInfo->getExtension()), $expectedExtensions, true)) {
				continue;
			}

			$sourcePath = $fileInfo->getPathname();
			$this->assertNoCrossSuiteBasenameOverlap($sourcePath);

			$infoPath = $this->buildTestInfoPath($sourcePath);
			if (!is_file($infoPath)) {
				continue;
			}

			$meta = $this->readJsonFile($infoPath);
			if (!is_array($meta)) {
				continue;
			}

			if (($meta['enabled'] ?? false) !== true && $options['include_disabled'] !== true) {
				continue;
			}
			$buildMeta = is_array($meta['build'] ?? null) ? $meta['build'] : [];
			if (($buildMeta['external_network'] ?? false) === true && $options['include_network'] !== true) {
				continue;
			}

			$relativeSourcePath = $this->relativePath($sourcePath);
			if ($suite === 'php') {
				$profile = strtolower(trim((string) ($options['profile'] ?? 'legacy')));
				if (!in_array($profile, ['legacy', 'strict'], true)) {
					throw new RuntimeException('Unsupported PHP test profile: ' . $profile);
				}
				$isStrictProfileTest = $this->isStrictProfileTestPath($relativeSourcePath);
				if ($profile === 'legacy' && $isStrictProfileTest) {
					continue;
				}
				if ($profile === 'strict' && !$isStrictProfileTest) {
					continue;
				}
			}
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
				'info_path' => $infoPath,
				'results_path' => $this->buildTestResultsPath($sourcePath),
				'relative_source_path' => $relativeSourcePath,
			];
		}

		usort(
			$tests,
			static fn (array $a, array $b): int => strcmp($a['relative_source_path'], $b['relative_source_path'])
		);

		return $tests;
	}

	private function resetOneTestResults(string $infoPath, string $resultsPath, string $sourcePath): void
	{
		$data = $this->readJsonFile($infoPath);
		if (!is_array($data)) {
			throw new RuntimeException('Invalid test definition JSON: ' . $infoPath);
		}

		$this->writeJsonFile($resultsPath, $this->buildEmptyResultsDocument($data, $sourcePath, $infoPath, $resultsPath));
	}

	/**
	 * Executes one end-to-end test.
	 */
	private function runSingleTest(string $infoPath, string $sanitizers = ''): void
	{
		$sourcePath = $this->sourcePathFromTestInfoPath($infoPath);
		if (str_ends_with($sourcePath, '.cpp')) {
			$this->runSingleRuntimeTest($infoPath, $sourcePath, $sanitizers);
			return;
		}

		$this->runSinglePhpFlowTest($infoPath, $sourcePath);
	}

	private function runSinglePhpFlowTest(string $infoPath, string $phpPath): void
	{
		$meta = $this->readJsonFile($infoPath);
		if (!is_array($meta)) {
			throw new RuntimeException('Invalid test definition JSON: ' . $infoPath);
		}
		if (!is_file($phpPath)) {
			throw new RuntimeException('Missing PHP test file: ' . $phpPath);
		}

		$resultsPath = $this->buildTestResultsPath($phpPath);
		$results = $this->buildEmptyResultsDocument($meta, $phpPath, $infoPath, $resultsPath);
		$results['last_run']['started_at'] = gmdate('c');
		$results['last_run']['worker'] = [
			'pid' => getmypid(),
			'host' => php_uname('n'),
		];
		$results['last_run']['paths'] = [
			'php' => $this->relativePath($phpPath),
			'test_info_json' => $this->relativePath($infoPath),
			'test_results_json' => $this->relativePath($resultsPath),
			'ast_json' => $this->relativePath($phpPath . '.json'),
		];

		$tempDir = $this->createTempDirForTest($meta, $phpPath);
		$phpProfile = $this->resolvePhpTestProfile();
		$runTestsProject = $this->materializePhpRunTestProject($meta, $phpPath, $phpProfile);

		try {
			$expect = is_array($meta['expect'] ?? null) ? $meta['expect'] : [];
			$compare = is_array($meta['compare'] ?? null) ? $meta['compare'] : [];
			$build = is_array($meta['build'] ?? null) ? $meta['build'] : [];

			$phpExpect = is_array($expect['php'] ?? null) ? $expect['php'] : [];
			$shouldRunPhpStage = (($phpExpect['run'] ?? false) === true) || (($meta['php_as_oracle'] ?? false) === true);
			if ($shouldRunPhpStage) {
				$phpOraclePath = $this->materializePhpOracleSource($phpPath, $tempDir);
				$phpCommand = ['php', $phpOraclePath];
				foreach ($this->resolveRunArgs((array) ($build['run_args'] ?? [])) as $arg) {
					$phpCommand[] = (string) $arg;
				}
				$phpRun = $this->runCommand($phpCommand, $this->projectRoot, self::PHP_TIMEOUT_SECONDS);
				$results['last_run']['stages']['php'] = [
					'command' => array_map('strval', $phpCommand),
					'exit_code' => $phpRun['exit_code'],
					'stdout' => $this->normalizeOutput((string) $phpRun['stdout'], $compare, 'stdout'),
					'stderr' => $this->normalizeOutput((string) $phpRun['stderr'], $compare, 'stderr'),
					'timed_out' => $phpRun['timed_out'],
					'duration_ms' => $phpRun['duration_ms'],
					'success' => ($phpRun['exit_code'] === 0 && $phpRun['timed_out'] === false),
					'comparison_ok' => true,
					'comparison_notes' => [],
				];
			} else {
				$results['last_run']['stages']['php'] = [
					'skipped' => true,
					'reason' => 'php_oracle_disabled',
					'comparison_ok' => true,
					'comparison_notes' => [],
				];
			}

			if (($phpExpect['run'] ?? false) === true) {
				$phpComparison = $this->compareStageRun($phpExpect, $results['last_run']['stages']['php'], $compare);
				$results['last_run']['stages']['php']['comparison_ok'] = $phpComparison['ok'];
				$results['last_run']['stages']['php']['comparison_notes'] = $phpComparison['notes'];
			}

			$generatorResult = $this->runGeneratorStage($phpPath, $tempDir);
			$results['last_run']['stages']['generate'] = [
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
			$results['last_run']['stages']['generate']['comparison_ok'] = $generateComparison['ok'];
			$results['last_run']['stages']['generate']['comparison_notes'] = $generateComparison['notes'];

			if ($generatorResult['success'] === true) {
				$compileUnitOnly = (($build['compile_unit_only'] ?? false) === true);
				$compileRun = $compileUnitOnly
					? $this->runCompileUnitOnlyStage((string) ($generatorResult['compile_unit_path'] ?? ''), $tempDir)
					: $this->runPhpProjectCompileStage($runTestsProject);
				$results['last_run']['stages']['compile'] = [
					'success' => $compileRun['success'],
					'exit_code' => $compileRun['exit_code'],
					'stdout' => $this->normalizeOutput((string) $compileRun['stdout'], $compare, 'stdout'),
					'stderr' => $this->normalizeOutput((string) $compileRun['stderr'], $compare, 'stderr'),
					'timed_out' => false,
					'duration_ms' => $compileRun['duration_ms'],
					'binary_path' => $this->relativePath((string) $compileRun['binary_path']),
					'comparison_ok' => true,
					'comparison_notes' => [],
				];
				$compileExpect = is_array($expect['compile'] ?? null) ? $expect['compile'] : [];
				$compileComparison = $this->compareBooleanStage(
					(bool) ($compileExpect['success'] ?? false),
					$results['last_run']['stages']['compile']['success'],
					(array) ($compileExpect['error_contains'] ?? []),
					$results['last_run']['stages']['compile']['stdout'] . "\n" . $results['last_run']['stages']['compile']['stderr'],
					(bool) ($compare['case_sensitive_errors'] ?? true),
					'compile'
				);
				$results['last_run']['stages']['compile']['comparison_ok'] = $compileComparison['ok'];
				$results['last_run']['stages']['compile']['comparison_notes'] = $compileComparison['notes'];

				if ($results['last_run']['stages']['compile']['success'] === true && !$compileUnitOnly) {
					$runExpect = is_array($expect['run'] ?? null) ? $expect['run'] : [];
					$runtimeEnv = [];
					if (is_string($compileRun['runtime_library_dir'] ?? null) && $compileRun['runtime_library_dir'] !== '') {
						$existingLdLibraryPath = getenv('LD_LIBRARY_PATH');
						$runtimeEnv['LD_LIBRARY_PATH'] = $compileRun['runtime_library_dir']
							. PATH_SEPARATOR
							. (is_string($existingLdLibraryPath) ? $existingLdLibraryPath : '');
						$existingDyldLibraryPath = getenv('DYLD_LIBRARY_PATH');
						$runtimeEnv['DYLD_LIBRARY_PATH'] = $compileRun['runtime_library_dir']
							. PATH_SEPARATOR
							. (is_string($existingDyldLibraryPath) ? $existingDyldLibraryPath : '');
					}
					if ($this->shouldEnableRuntimeErrorJson($expect)) {
						$runtimeEnv['SCPP_ERROR_FORMAT'] = 'json';
					}
					$httpServer = $this->startPhpFlowHttpServer($runTestsProject, $build, $phpPath);
					try {
						$placeholders = [];
						if (is_array($httpServer) && is_string($httpServer['base_url'] ?? null)) {
							$placeholders['{{http_base_url}}'] = $httpServer['base_url'];
						}
						$runCommand = [(string) $compileRun['binary_path']];
						foreach ($this->resolveRunArgs((array) ($build['run_args'] ?? []), $placeholders) as $arg) {
							$runCommand[] = (string) $arg;
						}
						$cppRun = $this->runCommand($runCommand, $runTestsProject['run_cwd'], self::RUN_TIMEOUT_SECONDS, $runtimeEnv);
					} finally {
						if (is_array($httpServer)) {
							$this->stopBackgroundProcess($httpServer['proc'] ?? null, is_array($httpServer['pipes'] ?? null) ? $httpServer['pipes'] : []);
						}
					}
					$results['last_run']['stages']['run'] = [
						'success' => ($cppRun['exit_code'] === 0 && $cppRun['timed_out'] === false),
						'exit_code' => $cppRun['exit_code'],
						'stdout' => $this->normalizeOutput((string) $cppRun['stdout'], $compare, 'stdout'),
						'stderr' => $this->normalizeOutput((string) $cppRun['stderr'], $compare, 'stderr'),
						'timed_out' => $cppRun['timed_out'],
						'duration_ms' => $cppRun['duration_ms'],
						'command' => array_map('strval', $runCommand),
						'comparison_ok' => true,
						'comparison_notes' => [],
					];
					$runComparison = $this->compareStageRun($runExpect, $results['last_run']['stages']['run'], $compare);
					$results['last_run']['stages']['run']['comparison_ok'] = $runComparison['ok'];
					$results['last_run']['stages']['run']['comparison_notes'] = $runComparison['notes'];
				} else if ($results['last_run']['stages']['compile']['success'] === true) {
					$results['last_run']['stages']['run'] = [
						'skipped' => true,
						'reason' => 'compile_unit_only',
						'comparison_ok' => (($expect['run']['success'] ?? null) === false),
						'comparison_notes' => [],
					];
				} else {
					$results['last_run']['stages']['run'] = [
						'skipped' => true,
						'reason' => 'compile_failed',
						'comparison_ok' => (($expect['run']['success'] ?? null) === false),
						'comparison_notes' => [],
					];
				}
			} else {
				$results['last_run']['stages']['compile'] = [
					'skipped' => true,
					'reason' => 'generate_failed',
					'comparison_ok' => (($expect['compile']['success'] ?? null) === false),
					'comparison_notes' => [],
				];
				$results['last_run']['stages']['run'] = [
					'skipped' => true,
					'reason' => 'generate_failed',
					'comparison_ok' => (($expect['run']['success'] ?? null) === false),
					'comparison_notes' => [],
				];
			}

			$results['last_run']['summary'] = $this->buildSummary($results, $expect);
			$results['last_run']['finished_at'] = gmdate('c');
			$this->writeJsonFile($resultsPath, $results);

			if (($results['last_run']['summary']['pass'] ?? false) !== true) {
				throw new RuntimeException((string) ($results['last_run']['summary']['message'] ?? 'Test failed.'));
			}
		} catch (Throwable $throwable) {
			$results['last_run']['summary'] = [
				'pass' => false,
				'message' => $throwable->getMessage(),
				'outcome' => 'exception',
			];
			$results['last_run']['exception'] = $this->formatThrowable($throwable);
			$results['last_run']['finished_at'] = gmdate('c');
			$this->writeJsonFile($resultsPath, $results);
			throw $throwable;
		}
	}

	private function materializePhpOracleSource(string $sourcePath, string $tempDir): string
	{
		if (strtolower((string) pathinfo($sourcePath, PATHINFO_EXTENSION)) !== 'phs') {
			return $sourcePath;
		}

		$source = file_get_contents($sourcePath);
		if (!is_string($source)) {
			throw new RuntimeException('Failed to read PHP oracle source: ' . $sourcePath);
		}

		$rewritten = (new PreTokenizer())->rewrite($source)->source;
		$oraclePath = $tempDir . '/' . basename($sourcePath, '.phs') . '.oracle.php';
		write_text_file($oraclePath, "<?php\n" . $rewritten);
		return $oraclePath;
	}

	/**
	 * @param array{project_root:string,config_path:string,entry_relative:string,workspace_source:string,run_cwd:string} $runTestsProject
	 * @param array<string, mixed> $build
	 * @return array{base_url:string}|null
	 */
	private function startPhpFlowHttpServer(array $runTestsProject, array $build, string $sourcePath): ?array
	{
		$httpServer = is_array($build['http_server'] ?? null) ? $build['http_server'] : null;
		if ($httpServer === null) {
			return null;
		}

		$workspaceSource = (string) ($runTestsProject['workspace_source'] ?? '');
		if ($workspaceSource === '') {
			throw new RuntimeException('HTTP test server setup requires a workspace source path.');
		}

		$workspaceSourceDir = dirname($workspaceSource);
		$sourceDir = dirname($sourcePath);
		$documentRootRaw = trim((string) ($httpServer['document_root'] ?? ''));
		if ($documentRootRaw === '') {
			throw new RuntimeException('HTTP test server config requires build.http_server.document_root.');
		}

		$documentRoot = $this->resolveRunTestHttpPath($workspaceSourceDir, $sourceDir, $documentRootRaw);
		if (!is_dir($documentRoot)) {
			throw new RuntimeException('HTTP test server document root does not exist: ' . $documentRoot);
		}

		$router = null;
		$routerRaw = trim((string) ($httpServer['router'] ?? ''));
		if ($routerRaw !== '') {
			$router = $this->resolveRunTestHttpPath($workspaceSourceDir, $sourceDir, $routerRaw);
			if (!is_file($router)) {
				throw new RuntimeException('HTTP test server router does not exist: ' . $router);
			}
		}

		$host = trim((string) ($httpServer['host'] ?? '127.0.0.1'));
		if ($host === '') {
			$host = '127.0.0.1';
		}

		$port = $this->reserveTcpPort($host);
		$command = [PHP_BINARY, '-S', $host . ':' . $port, '-t', $documentRoot];
		if ($router !== null) {
			$command[] = $router;
		}

		$descriptors = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$proc = proc_open($command, $descriptors, $pipes, $runTestsProject['project_root']);
		if (!is_resource($proc)) {
			throw new RuntimeException('Failed to start local PHP HTTP server for test.');
		}

		fclose($pipes[0]);
		stream_set_blocking($pipes[1], false);
		stream_set_blocking($pipes[2], false);

		try {
			$this->waitForTcpServer($host, $port, 5);
		} catch (Throwable $throwable) {
			$this->stopBackgroundProcess($proc, $pipes);
			throw $throwable;
		}

		return [
			'proc' => $proc,
			'pipes' => $pipes,
			'base_url' => 'http://' . $host . ':' . $port,
		];
	}

	private function resolveRunTestHttpPath(string $workspaceSourceDir, string $sourceDir, string $path): string
	{
		if ($path === '') {
			return $workspaceSourceDir;
		}
		if (str_starts_with($path, 'source:')) {
			return $this->normalizePath($sourceDir . '/' . str_replace('\\', '/', substr($path, strlen('source:'))));
		}
		if (str_starts_with($path, '/')) {
			return $this->normalizePath($path);
		}
		return $this->normalizePath($workspaceSourceDir . '/' . str_replace('\\', '/', $path));
	}

	private function reserveTcpPort(string $host): int
	{
		$errorCode = 0;
		$errorMessage = '';
		$server = @stream_socket_server('tcp://' . $host . ':0', $errorCode, $errorMessage);
		if (!is_resource($server)) {
			throw new RuntimeException('Failed to reserve local TCP port: ' . $errorMessage);
		}

		$name = stream_socket_get_name($server, false);
		fclose($server);
		if (!is_string($name) || $name === '') {
			throw new RuntimeException('Failed to determine reserved local TCP port.');
		}

		$port = (int) substr(strrchr($name, ':'), 1);
		if ($port <= 0) {
			throw new RuntimeException('Reserved local TCP port was invalid: ' . $name);
		}
		return $port;
	}

	private function waitForTcpServer(string $host, int $port, int $timeoutSeconds): void
	{
		$deadline = microtime(true) + $timeoutSeconds;
		while (microtime(true) < $deadline) {
			$socket = @stream_socket_client('tcp://' . $host . ':' . $port, $errorCode, $errorMessage, 0.2);
			if (is_resource($socket)) {
				fclose($socket);
				return;
			}
			usleep(100000);
		}

		throw new RuntimeException('Timed out waiting for local HTTP test server on ' . $host . ':' . $port);
	}

	private function stopBackgroundProcess($proc, array $pipes): void
	{
		foreach ([1, 2] as $index) {
			if (isset($pipes[$index]) && is_resource($pipes[$index])) {
				stream_get_contents($pipes[$index]);
				fclose($pipes[$index]);
			}
		}

		if (!is_resource($proc)) {
			return;
		}

		$status = proc_get_status($proc);
		if (($status['running'] ?? false) === true) {
			proc_terminate($proc);
			usleep(100000);
			$status = proc_get_status($proc);
			if (($status['running'] ?? false) === true) {
				proc_terminate($proc, 9);
			}
		}
		proc_close($proc);
	}

	/** @param list<mixed> $args
	 *  @return list<string>
	 */
	private function resolveRunArgs(array $args, array $placeholders = []): array
	{
		$resolved = [];
		foreach ($args as $arg) {
			if (!is_string($arg)) {
				$resolved[] = (string) $arg;
				continue;
			}
			$resolved[] = strtr($arg, $placeholders);
		}
		return $resolved;
	}

	private function runSingleRuntimeTest(string $infoPath, string $sourcePath, string $sanitizers = ''): void
	{
		$meta = $this->readJsonFile($infoPath);
		if (!is_array($meta)) {
			throw new RuntimeException('Invalid test definition JSON: ' . $infoPath);
		}
		if (!is_file($sourcePath)) {
			throw new RuntimeException('Missing runtime test file: ' . $sourcePath);
		}

		$resultsPath = $this->buildTestResultsPath($sourcePath);
		$results = $this->buildEmptyResultsDocument($meta, $sourcePath, $infoPath, $resultsPath);
		$results['last_run']['started_at'] = gmdate('c');
		$results['last_run']['worker'] = [
			'pid' => getmypid(),
			'host' => php_uname('n'),
		];
		$results['last_run']['paths'] = [
			'source' => $this->relativePath($sourcePath),
			'test_info_json' => $this->relativePath($infoPath),
			'test_results_json' => $this->relativePath($resultsPath),
		];
		$results['last_run']['sanitizers'] = $this->parseSanitizers((string) ($meta['build']['sanitizers'] ?? ''));
		$results['last_run']['stages']['php'] = [
			'skipped' => true,
			'reason' => 'runtime_suite',
			'comparison_ok' => true,
			'comparison_notes' => [],
		];
		$results['last_run']['stages']['generate'] = [
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
			$results['last_run']['stages']['compile'] = [
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
				$results['last_run']['stages']['compile']['success'],
				(array) ($compileExpect['error_contains'] ?? []),
				$results['last_run']['stages']['compile']['stdout'] . "\n" . $results['last_run']['stages']['compile']['stderr'],
				(bool) ($compare['case_sensitive_errors'] ?? true),
				'compile'
			);
			$results['last_run']['stages']['compile']['comparison_ok'] = $compileComparison['ok'];
			$results['last_run']['stages']['compile']['comparison_notes'] = $compileComparison['notes'];

			if ($results['last_run']['stages']['compile']['success'] === true) {
				$runCommand = [(string) $compileRun['binary_path']];
				foreach ((array) ($build['run_args'] ?? []) as $arg) {
					$runCommand[] = (string) $arg;
				}
				$runTimeout = (int) ($build['run_timeout_seconds'] ?? self::RUN_TIMEOUT_SECONDS);
				$runtimeEnv = array_merge($this->buildSanitizerRunEnvironment((string) ($build['sanitizers'] ?? '')), (array) ($build['env'] ?? []));
				if ($this->shouldEnableRuntimeErrorJson($expect)) {
					$runtimeEnv['SCPP_ERROR_FORMAT'] = 'json';
				}
				$cppRun = $this->runCommand($runCommand, $tempDir, $runTimeout, $runtimeEnv);
				$results['last_run']['stages']['run'] = [
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
				$runComparison = $this->compareStageRun($runExpect, $results['last_run']['stages']['run'], $compare);
				$results['last_run']['stages']['run']['comparison_ok'] = $runComparison['ok'];
				$results['last_run']['stages']['run']['comparison_notes'] = $runComparison['notes'];
			} else {
				$results['last_run']['stages']['run'] = [
					'skipped' => true,
					'reason' => 'compile_failed',
					'comparison_ok' => (($expect['run']['success'] ?? null) === false),
					'comparison_notes' => [],
				];
			}

			$results['last_run']['summary'] = $this->buildSummary($results, $expect);
			$results['last_run']['finished_at'] = gmdate('c');
			$this->writeJsonFile($resultsPath, $results);

			if (($results['last_run']['summary']['pass'] ?? false) !== true) {
				throw new RuntimeException((string) ($results['last_run']['summary']['message'] ?? 'Test failed.'));
			}
		} catch (Throwable $throwable) {
			$results['last_run']['summary'] = [
				'pass' => false,
				'message' => $throwable->getMessage(),
				'outcome' => 'exception',
			];
			$results['last_run']['exception'] = $this->formatThrowable($throwable);
			$results['last_run']['finished_at'] = gmdate('c');
			$this->writeJsonFile($resultsPath, $results);
			throw $throwable;
		}
	}

	private function runGeneratorStage(string $phpPath, string $outputDir): array
	{
		require_once $this->projectRoot . '/bin/bootstrap.php';

		$started = microtime(true);
		try {
			$transpiler = new Transpiler(phpProfile: $this->resolvePhpTestProfile());
			$cppFile = $transpiler->transpile($phpPath, true);
			$durationMs = (int) round((microtime(true) - $started) * 1000);

			$headerPath = $outputDir . '/generated.hpp';
			$sourcePath = $outputDir . '/generated.cpp';
			$compileUnitPath = $outputDir . '/generated.build.cpp';
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
		} catch (Throwable $throwable) {
			return [
				'success' => false,
				'errors' => $throwable->getMessage(),
				'duration_ms' => (int) round((microtime(true) - $started) * 1000),
			];
		}
	}

	private function runCompileStage(string $compileUnitPath, string $workDir): array
	{
		$binaryPath = $workDir . '/test.out';
		$runtimeObject = $this->ensureCachedRuntimeObject(
			'g++',
			'c++23',
			['-O3'],
			['-DSCPP_LANGUAGE_TARGET_PHP=1'],
			[]
		);
		$result = $this->runCommand(
			$this->withCompilerLauncher([
				'g++',
				'-std=c++23',
				'-O3',
				'-DSCPP_LANGUAGE_TARGET_PHP=1',
				$compileUnitPath,
				$runtimeObject['object_path'],
				'-I',
				$this->projectRoot . '/runtime/include',
				'-o',
				$binaryPath,
			]),
			$workDir,
			self::COMPILE_TIMEOUT_SECONDS
		);
		$result['binary_path'] = $binaryPath;
		return $result;
	}

	/** @return array{success:bool,exit_code:int,stdout:string,stderr:string,duration_ms:int,binary_path:string,runtime_library_dir:?string} */
	private function runCompileUnitOnlyStage(string $compileUnitPath, string $workDir): array
	{
		if ($compileUnitPath === '' || !is_file($compileUnitPath)) {
			return [
				'success' => false,
				'exit_code' => 1,
				'stdout' => '',
				'stderr' => 'generated compile unit missing',
				'duration_ms' => 0,
				'binary_path' => '',
				'runtime_library_dir' => null,
			];
		}
		$started = microtime(true);
		$objectPath = $workDir . '/test.o';
		$result = $this->runCommand(
			$this->withCompilerLauncher([
				'g++',
				'-std=c++23',
				'-O3',
				'-DSCPP_LANGUAGE_TARGET_PHP=1',
				'-c',
				$compileUnitPath,
				'-I',
				$this->projectRoot . '/runtime/include',
				'-o',
				$objectPath,
			]),
			$workDir,
			self::COMPILE_TIMEOUT_SECONDS
		);
		return [
			'success' => ((int) $result['exit_code'] === 0 && ($result['timed_out'] ?? false) === false),
			'exit_code' => (int) $result['exit_code'],
			'stdout' => (string) $result['stdout'],
			'stderr' => (string) $result['stderr'],
			'duration_ms' => (int) round((microtime(true) - $started) * 1000),
			'binary_path' => $objectPath,
			'runtime_library_dir' => null,
		];
	}

	/** @return array{project_root:string,config_path:string,entry_relative:string,workspace_source:string,run_cwd:string} */
	private function materializePhpRunTestProject(array $meta, string $phpPath, string $phpProfile): array
	{
		$testId = preg_replace('/[^A-Za-z0-9_.-]+/', '_', (string) ($meta['id'] ?? basename($phpPath, '.php')));
		$projectRoot = $this->runTestsRoot . '/' . $phpProfile . '/' . $testId . '/tests/.runtime/' . $testId;
		if (is_dir($projectRoot)) {
			$this->removeDirectoryTree($projectRoot);
		}
		$this->ensureDirectory($projectRoot);
		$this->ensureDirectory($projectRoot . '/native_cpp');
		$this->ensureDirectory($projectRoot . '/.prism/build');
		$this->ensureDirectory($projectRoot . '/.prism/generated');
		$this->ensureDirectory($projectRoot . '/.prism/cache');

		$relativeSourcePath = normalize_config_path($this->relativePath($phpPath));
		$workspaceSource = normalize_path($projectRoot . '/' . $relativeSourcePath);
		$this->ensureDirectory(dirname($workspaceSource));
		$source = file_get_contents($phpPath);
		if (!is_string($source)) {
			throw new RuntimeException('Failed to read PHP test source: ' . $phpPath);
		}
		write_text_file($workspaceSource, $source);
		$this->mirrorSiblingFixtures($phpPath, $projectRoot, dirname($relativeSourcePath));
		$runCwd = $projectRoot;
		$build = is_array($meta['build'] ?? null) ? $meta['build'] : [];
		$runtimeModules = $this->resolvePhpRunTestRuntimeModules($build);

		$config = [
			'config_version' => 1,
			'project_name' => 'run_test_' . $testId,
			'entrypoint' => $relativeSourcePath,
			'build_dir' => '.prism/build',
			'generated_dir' => '.prism/generated',
			'cache_dir' => '.prism/cache',
			'native_cpp_dir' => 'native_cpp',
			'dependencies' => [],
			'libraries' => [],
			'build' => [
				'backend' => 'ninja',
				'cxx' => null,
				'mode' => 'debug',
			],
			'runtime' => [
				'languages' => ['php'],
				'modules' => $runtimeModules,
				'language_profiles' => [
					'php' => ['profile' => $phpProfile],
				],
			],
		];
		$json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		if (!is_string($json)) {
			throw new RuntimeException('Failed to encode run-tests prism.json for ' . $phpPath);
		}
		write_text_file($projectRoot . '/prism.json', $json . PHP_EOL);

		return [
			'project_root' => $projectRoot,
			'config_path' => $projectRoot . '/prism.json',
			'entry_relative' => $relativeSourcePath,
			'workspace_source' => $workspaceSource,
			'run_cwd' => $runCwd,
		];
	}

	/** @return list<string> */
	private function resolvePhpRunTestRuntimeModules(array $build): array
	{
		$modules = ['json', 'filesystem', 'datetime'];
		$extraModules = $build['runtime_modules'] ?? null;
		if (!is_array($extraModules)) {
			return $modules;
		}

		foreach ($extraModules as $module) {
			if (!is_string($module)) {
				continue;
			}
			$trimmed = trim($module);
			if ($trimmed === '' || in_array($trimmed, $modules, true)) {
				continue;
			}
			$modules[] = $trimmed;
		}

		return $modules;
	}

	private function mirrorSiblingFixtures(string $sourcePath, string $projectRoot, string $relativeSourceDir): void
	{
		$fixturesDir = dirname($sourcePath) . '/fixtures';
		if (!is_dir($fixturesDir)) {
			return;
		}

		$targetDir = normalize_path($projectRoot . '/' . normalize_config_path($relativeSourceDir) . '/fixtures');
		$this->ensureDirectory($targetDir);
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($fixturesDir, FilesystemIterator::SKIP_DOTS)
		);
		foreach ($iterator as $fileInfo) {
			if (!$fileInfo->isFile()) {
				continue;
			}
			$relative = substr($fileInfo->getPathname(), strlen($fixturesDir) + 1);
			$target = normalize_path($targetDir . '/' . str_replace('\\', '/', $relative));
			$this->ensureDirectory(dirname($target));
			$contents = file_get_contents($fileInfo->getPathname());
			if (!is_string($contents)) {
				throw new RuntimeException('Failed to read fixture file: ' . $fileInfo->getPathname());
			}
			write_text_file($target, $contents);
		}
	}

	/**
	 * @param array{project_root:string,config_path:string,entry_relative:string,workspace_source:string} $project
	 * @return array{success:bool,exit_code:int,stdout:string,stderr:string,duration_ms:int,binary_path:string,runtime_library_dir:?string}
	 */
	private function runPhpProjectCompileStage(array $project): array
	{
		$started = microtime(true);
		$build = scpp_run_build_service($project['project_root'], $project['config_path'], [
			'entry_override' => $project['entry_relative'],
		]);
		$retryForRuntimeSeed = ($build['ok'] ?? false) !== true && (
			str_contains((string) ($build['error'] ?? ''), 'Runtime artifact')
			|| str_contains((string) ($build['error'] ?? ''), 'libruntime.')
			|| str_contains((string) ($build['error'] ?? ''), 'runtime.')
		);
		if ($retryForRuntimeSeed) {
			$build = scpp_run_build_service($project['project_root'], $project['config_path'], [
				'entry_override' => $project['entry_relative'],
				'compile_runtime' => true,
			]);
		}

		$binaryPath = '';
		if (($build['ok'] ?? false) === true && is_array($build['result'] ?? null) && is_string($build['result']['output_path'] ?? null)) {
			$binaryPath = (string) $build['result']['output_path'];
		}
		$runtimeLibraryDir = null;
		if (is_array($build['result'] ?? null) && is_string($build['result']['runtime_library_dir'] ?? null) && $build['result']['runtime_library_dir'] !== '') {
			$runtimeLibraryDir = (string) $build['result']['runtime_library_dir'];
		}
		if ($runtimeLibraryDir === null) {
			$runtimeLibraryDir = $this->resolveProjectRuntimeLibraryDir($project['project_root'], $project['config_path']);
		}

		return [
			'success' => (bool) ($build['ok'] ?? false),
			'exit_code' => (int) ($build['exit_code'] ?? (($build['ok'] ?? false) ? 0 : 1)),
			'stdout' => (string) ($build['output'] ?? ''),
			'stderr' => (string) ($build['error'] ?? ''),
			'duration_ms' => (int) round((microtime(true) - $started) * 1000),
			'binary_path' => $binaryPath,
			'runtime_library_dir' => $runtimeLibraryDir,
		];
	}

	private function resolveProjectRuntimeLibraryDir(string $projectRoot, string $configPath): ?string
	{
		$config = load_project_config($configPath);
		$compiler = resolve_compiler($config);
		if ($compiler === null) {
			return null;
		}
		$runtimeConfig = is_array($config['runtime'] ?? null) ? $config['runtime'] : resolve_runtime_build_config($config);
		$runtimeBuild = build_runtime_artifact_spec(resolve_repo_root(), $projectRoot, $compiler, resolve_build_mode($config), $runtimeConfig);
		if (!is_string($runtimeBuild['artifact_path'] ?? null) || $runtimeBuild['artifact_path'] === '') {
			return null;
		}
		return normalize_path(dirname($projectRoot . '/' . normalize_config_path($runtimeBuild['artifact_path'])));
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
		$compileEnv = array_merge($this->buildSanitizerRunEnvironment((string) ($build['sanitizers'] ?? '')), (array) ($build['env'] ?? []));
		$runtimeObject = $this->ensureCachedRuntimeObject(
			$compiler,
			$languageStandard,
			$flags,
			[],
			$compileEnv
		);
		$command = array_merge(
			[$compiler, '-std=' . $languageStandard],
			$flags,
			[
				$sourcePath,
				$runtimeObject['object_path'],
				'-I',
				$this->projectRoot,
				'-I',
				$this->projectRoot . '/runtime/include',
				'-o',
				$binaryPath,
			]
		);
		$compileTimeout = (int) ($build['compile_timeout_seconds'] ?? self::COMPILE_TIMEOUT_SECONDS);
		$result = $this->runCommand($this->withCompilerLauncher($command), $workDir, $compileTimeout, $compileEnv);
		$result['binary_path'] = $binaryPath;
		$result['command'] = array_map('strval', $this->withCompilerLauncher($command));
		$result['env'] = $compileEnv;
		return $result;
	}

	private function ensureCachedRuntimeObject(
		string $compiler,
		string $languageStandard,
		array $flags,
		array $defines,
		array $env
	): array {
		$cacheRoot = $this->projectRoot . '/tests/.runtime/cache/runtime_objects';
		$this->ensureDirectory($cacheRoot);
		$phpProfile = $this->resolvePhpTestProfile();

		$cacheKey = sha1(json_encode([
			'compiler' => $compiler,
			'language_standard' => $languageStandard,
			'flags' => array_values($flags),
			'defines' => array_values($defines),
			'runtime_version' => $this->computeRuntimeCacheVersion($phpProfile),
		], JSON_THROW_ON_ERROR));
		$objectPath = $cacheRoot . '/runtime_' . $cacheKey . '.o';
		$compositionPath = $cacheRoot . '/runtime_' . $phpProfile . '.cpp';
		$lockPath = $objectPath . '.lock';
		$lockHandle = fopen($lockPath, 'c+');
		if ($lockHandle === false) {
			throw new RuntimeException('Failed to create runtime object cache lock: ' . $lockPath);
		}

		try {
			if (!flock($lockHandle, LOCK_EX)) {
				throw new RuntimeException('Failed to lock runtime object cache: ' . $lockPath);
			}

			if (file_put_contents($compositionPath, $this->renderRuntimeCompositionSource($phpProfile)) === false) {
				throw new RuntimeException('Failed to write runtime composition source: ' . $compositionPath);
			}

			if (!$this->isRuntimeObjectFresh($objectPath, $phpProfile)) {
				$tmpObjectPath = $objectPath . '.tmp.' . bin2hex(random_bytes(4));
				$command = array_merge(
					[$compiler, '-std=' . $languageStandard],
					$flags,
					$defines,
					[
						'-c',
						$compositionPath,
						'-I',
						$this->projectRoot,
						'-I',
						$this->projectRoot . '/runtime/include',
						'-o',
						$tmpObjectPath,
					]
				);
				$result = $this->runCommand($this->withCompilerLauncher($command), $this->projectRoot, self::COMPILE_TIMEOUT_SECONDS, $env);
				if ($result['exit_code'] !== 0 || $result['timed_out'] === true) {
					if (is_file($tmpObjectPath)) {
						@unlink($tmpObjectPath);
					}
					throw new RuntimeException('Failed to build cached runtime object: ' . trim((string) ($result['stderr'] !== '' ? $result['stderr'] : $result['stdout'])));
				}

				if (is_file($objectPath) && !@unlink($objectPath)) {
					@unlink($tmpObjectPath);
					throw new RuntimeException('Failed to replace cached runtime object: ' . $objectPath);
				}
				if (!@rename($tmpObjectPath, $objectPath)) {
					@unlink($tmpObjectPath);
					throw new RuntimeException('Failed to publish cached runtime object: ' . $objectPath);
				}
			}

			return ['object_path' => $objectPath];
		} finally {
			flock($lockHandle, LOCK_UN);
			fclose($lockHandle);
		}
	}

	private function isRuntimeObjectFresh(string $objectPath, string $phpProfile): bool
	{
		if (!is_file($objectPath)) {
			return false;
		}

		$objectMTime = filemtime($objectPath);
		if ($objectMTime === false) {
			return false;
		}

		return $objectMTime >= $this->latestRuntimeSourceMTime($phpProfile);
	}

	private function computeRuntimeCacheVersion(string $phpProfile): array
	{
		return [
			'latest_runtime_mtime' => $this->latestRuntimeSourceMTime($phpProfile),
			'php_profile' => $phpProfile,
			'test_harness_mtime' => filemtime(__FILE__) ?: 0,
		];
	}

	private function latestRuntimeSourceMTime(string $phpProfile): int
	{
		$latest = 0;
		foreach ([$this->projectRoot . '/runtime/include'] as $dir) {
			if (!is_dir($dir)) {
				continue;
			}
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
			);
			foreach ($iterator as $fileInfo) {
				if (!$fileInfo->isFile()) {
					continue;
				}
				$mtime = $fileInfo->getMTime();
				if ($mtime > $latest) {
					$latest = $mtime;
				}
			}
		}
		if ($phpProfile === 'legacy' || $phpProfile === 'strict') {
			$scriptMTime = filemtime(__FILE__) ?: 0;
			if ($scriptMTime > $latest) {
				$latest = $scriptMTime;
			}
		}

		return $latest;
	}

	private function renderRuntimeCompositionSource(string $phpProfile): string
	{
		$lines = [
			'#include "core/runtime.cpp"',
			'#include "modules/json/json.cpp"',
			'#include "modules/mysql/mysql_module.cpp"',
		];
		if ($phpProfile === 'legacy') {
			$lines[] = '#include "lang/php/php_filesystem.cpp"';
			$lines[] = '#include "lang/php/php_json.cpp"';
			$lines[] = '#include "lang/php/php_mysqli.cpp"';
			$lines[] = '#include "lang/php/php_regex.cpp"';
		}
		return implode("\n", $lines) . "\n";
	}

	private function withCompilerLauncher(array $command): array
	{
		$launcher = $this->detectCompilerLauncher();
		if ($launcher === null || $command === []) {
			return $command;
		}

		if ((string) $command[0] === $launcher) {
			return $command;
		}

		array_unshift($command, $launcher);
		return $command;
	}

	private function detectCompilerLauncher(): ?string
	{
		if (!$this->compilerLauncherIsUsable('sccache', 'g++')) {
			return null;
		}

		return 'sccache';
	}

	private function compilerLauncherIsUsable(string $launcher, string $compiler): bool
	{
		if (!$this->commandExistsOnPath($launcher) || !$this->commandExistsOnPath($compiler)) {
			return false;
		}

		$descriptor = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$process = @proc_open([$launcher, $compiler, '--version'], $descriptor, $pipes, $this->repoRoot);
		if (!is_resource($process)) {
			return false;
		}

		fclose($pipes[0]);
		stream_get_contents($pipes[1]);
		stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$status = proc_close($process);
		return is_int($status) && $status === 0;
	}

	private function commandExistsOnPath(string $command): bool
	{
		$pathEnv = getenv('PATH');
		if (!is_string($pathEnv) || $pathEnv === '') {
			return false;
		}

		$dirs = array_filter(explode(PATH_SEPARATOR, $pathEnv), static fn (string $dir): bool => $dir !== '');
		$extensions = [''];
		if (DIRECTORY_SEPARATOR === '\\') {
			$pathext = getenv('PATHEXT');
			$extensions = $pathext === false || $pathext === ''
				? ['.exe', '.cmd', '.bat', '.com', '']
				: array_merge(explode(';', strtolower((string) $pathext)), ['']);
			$extensions = array_values(array_unique($extensions));
		}

		foreach ($dirs as $dir) {
			$dir = rtrim($dir, "\\/");
			foreach ($extensions as $extension) {
				$candidate = $dir . DIRECTORY_SEPARATOR . $command;
				if ($extension !== '' && !str_ends_with(strtolower($candidate), $extension)) {
					$candidate .= $extension;
				}
				if (is_file($candidate)) {
					return true;
				}
			}
		}

		return false;
	}


	private function resolveBundledAstExtensionPath(): ?string
	{
		$candidates = [
			$this->projectRoot . '/ext/8.4-deb/ast.so',
			$this->projectRoot . '/ext/8.4-deb_php_ast.so',
			$this->projectRoot . '/ext/ast.so',
		];

		foreach ($candidates as $candidate) {
			if (is_file($candidate)) {
				return $candidate;
			}
		}

		return null;
	}

	private function startWorkerProcess(string $jsonPath, string $sanitizers = '', string $profile = 'legacy'): array
	{
		$command = [
			PHP_BINARY,
		];

		$astSoPath = $this->resolveBundledAstExtensionPath();
		if (!extension_loaded('ast') && is_string($astSoPath) && is_file($astSoPath)) {
			$command[] = '-dextension=' . $astSoPath;
		}

		$command[] = $this->selfPath;
		$command[] = 'worker';
		$command[] = '--json=' . $jsonPath;
		$command[] = '--profile=' . strtolower(trim($profile));
		if ($sanitizers !== '') {
			$command[] = '--san=' . $sanitizers;
		}
		$commandString = $this->buildShellCommand($command);
		$descriptors = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];

		$procEnv = $this->buildPhpWorkerEnvironment($profile);
		$proc = proc_open($commandString, $descriptors, $pipes, $this->projectRoot, $procEnv);
		if (!is_resource($proc)) {
			throw new RuntimeException('Failed to start worker process.');
		}

		fclose($pipes[0]);
		stream_set_blocking($pipes[1], false);
		stream_set_blocking($pipes[2], false);

		return ['proc' => $proc, 'pipes' => $pipes];
	}

	private function buildPhpWorkerEnvironment(string $profile = 'legacy'): array
	{
		$env = $this->buildProcessEnvironment();
		$normalizedProfile = strtolower(trim($profile));
		$env['SCPP_TEST_PHP_PROFILE'] = in_array($normalizedProfile, ['legacy', 'strict'], true) ? $normalizedProfile : 'legacy';
		$astSoPath = $this->resolveBundledAstExtensionPath();
		if (is_string($astSoPath) && is_file($astSoPath)) {
			$flag = '-dextension=' . $astSoPath;
			$env['PHP_AST_SO'] = $astSoPath;
			$env['PHP_AST_EXTENSION_FLAG'] = $flag;
			$env['PHP_WORKER_AST_SO'] = $astSoPath;
		}
		return $env;
	}

	private function resolvePhpTestProfile(): string
	{
		$profile = strtolower(trim((string) ($_SERVER['SCPP_TEST_PHP_PROFILE'] ?? getenv('SCPP_TEST_PHP_PROFILE') ?: 'legacy')));
		return in_array($profile, ['legacy', 'strict'], true) ? $profile : 'legacy';
	}

	private function isStrictProfileTestPath(string $relativeSourcePath): bool
	{
		$segments = array_filter(explode('/', str_replace('\\', '/', $relativeSourcePath)), static fn (string $segment): bool => $segment !== '');
		foreach ($segments as $segment) {
			if (str_starts_with($segment, 'strict_')) {
				return true;
			}
		}
		return false;
	}

	private function buildProcessEnvironment(array $extra = []): array
	{
		$env = [];
		foreach (array_merge($_ENV, $_SERVER, $extra) as $key => $value) {
			if (!is_string($key) || $key == '') {
				continue;
			}
			if (is_array($value) || is_object($value)) {
				continue;
			}
			if ($value === null) {
				continue;
			}
			$env[$key] = (string) $value;
		}
		return $env;
	}

	private function runCommand(array $command, string $cwd, int $timeoutSeconds, array $env = []): array
	{
		$descriptors = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$procEnv = $env === [] ? null : $this->buildProcessEnvironment($env);
		$proc = proc_open($command, $descriptors, $pipes, $cwd, $procEnv);
		if (!is_resource($proc)) {
			throw new RuntimeException('Failed to start command: ' . $this->buildShellCommand($command));
		}

		fclose($pipes[0]);
		stream_set_blocking($pipes[1], false);
		stream_set_blocking($pipes[2], false);

		$stdout = '';
		$stderr = '';
		$timedOut = false;
		$startedAt = microtime(true);
		$finalStatus = null;

		while (true) {
			$stdout .= stream_get_contents($pipes[1]);
			$stderr .= stream_get_contents($pipes[2]);
			$status = proc_get_status($proc);
			if ($status['running'] === false) {
				$finalStatus = $status;
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
		$exitCode = (is_array($finalStatus) && is_int($finalStatus['exitcode'] ?? null) && (int) $finalStatus['exitcode'] >= 0)
			? (int) $finalStatus['exitcode']
			: proc_close($proc);
		$durationMs = (int) round((microtime(true) - $startedAt) * 1000);

		return [
			'exit_code' => $exitCode,
			'stdout' => $stdout,
			'stderr' => $stderr,
			'timed_out' => $timedOut,
			'duration_ms' => $durationMs,
		];
	}


	/**
	 * @return array<string, mixed>|null
	 */
	private function decodeRuntimeErrorJson(string $stderr): ?array
	{
		$stderr = trim($stderr);
		if ($stderr === '') {
			return null;
		}

		$decoded = json_decode($stderr, true);
		if (!is_array($decoded)) {
			return null;
		}
		$error = $decoded['error'] ?? null;
		return is_array($error) ? $error : null;
	}

	/**
	 * @param array<string, mixed> $expected
	 * @param array<string, mixed> $actual
	 * @return array{ok: bool, notes: list<string>}
	 */
	private function compareRuntimeErrorJson(array $expected, array $actual): array
	{
		$notes = [];
		$ok = true;
		foreach ($expected as $key => $value) {
			if (!is_string($key) || $key === '') {
				continue;
			}
			$actualValue = $actual[$key] ?? null;
			if ((string) $actualValue !== (string) $value) {
				$ok = false;
				$notes[] = sprintf('run error json mismatch for %s: expected=%s actual=%s', $key, (string) $value, is_scalar($actualValue) ? (string) $actualValue : gettype($actualValue));
			}
		}
		return ['ok' => $ok, 'notes' => $notes];
	}

	private function shouldEnableRuntimeErrorJson(array $expect): bool
	{
		$run = is_array($expect['run'] ?? null) ? $expect['run'] : [];
		return is_array($run['error_json'] ?? null) && $run['error_json'] !== [];
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

		if (array_key_exists('stdout', $expect) && $this->normalizeOutput((string) $expect['stdout'], $compare, 'stdout') !== (string) ($actual['stdout'] ?? '')) {
			$ok = false;
			$notes[] = 'stdout mismatch';
		}

		if (array_key_exists('stderr', $expect) && $this->normalizeOutput((string) $expect['stderr'], $compare, 'stderr') !== (string) ($actual['stderr'] ?? '')) {
			$ok = false;
			$notes[] = 'stderr mismatch';
		}

		foreach ((array) ($expect['error_contains'] ?? []) as $needle) {
			if (!is_string($needle) || $needle === '') {
				continue;
			}
			$found = (bool) ($compare['case_sensitive_errors'] ?? true)
				? (strpos((string) ($actual['stderr'] ?? ''), $needle) !== false)
				: (stripos((string) ($actual['stderr'] ?? ''), $needle) !== false);
			if (!$found) {
				$ok = false;
				$notes[] = 'run error text missing substring: ' . $needle;
			}
		}

		$expectedErrorJson = $expect['error_json'] ?? null;
		if (is_array($expectedErrorJson) && $expectedErrorJson !== []) {
			$decodedError = $this->decodeRuntimeErrorJson((string) ($actual['stderr'] ?? ''));
			if ($decodedError === null) {
				$ok = false;
				$notes[] = 'run stderr is not valid runtime error JSON';
			} else {
				$jsonComparison = $this->compareRuntimeErrorJson($expectedErrorJson, $decodedError);
				if ($jsonComparison['ok'] !== true) {
					$ok = false;
					$notes = array_merge($notes, $jsonComparison['notes']);
				}
			}
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


	private function buildTestInfoPath(string $sourcePath): string
	{
		return $this->buildSharedTestStem($sourcePath) . '.test-info.json';
	}

	private function buildTestResultsPath(string $sourcePath): string
	{
		return $this->buildSharedTestStem($sourcePath) . '.test-results.json';
	}

	private function buildSharedTestStem(string $sourcePath): string
	{
		$info = pathinfo($sourcePath);
		return $info['dirname'] . '/' . $info['filename'];
	}

	private function sourcePathFromTestInfoPath(string $infoPath): string
	{
		if (!str_ends_with($infoPath, '.test-info.json')) {
			throw new RuntimeException('Invalid test definition path: ' . $infoPath);
		}

		$stem = substr($infoPath, 0, -strlen('.test-info.json'));
		$phsPath = $stem . '.phs';
		$phpPath = $stem . '.php';
		$cppPath = $stem . '.cpp';
		$hasPhs = is_file($phsPath);
		$hasPhp = is_file($phpPath);
		$hasCpp = is_file($cppPath);
		if (($hasPhs && $hasPhp) || ($hasPhs && $hasCpp) || ($hasPhp && $hasCpp)) {
			throw new RuntimeException('Basename overlap detected for test definition: ' . $stem . ' (.phs, .php, and .cpp variants must not coexist in the same folder).');
		}
		if ($hasPhs) {
			return $phsPath;
		}
		if ($hasPhp) {
			return $phpPath;
		}
		if ($hasCpp) {
			return $cppPath;
		}

		throw new RuntimeException('Missing source file for test definition: ' . $infoPath);
	}

	private function assertNoCrossSuiteBasenameOverlap(string $sourcePath): void
	{
		$stem = $this->buildSharedTestStem($sourcePath);
		$extension = strtolower((string) pathinfo($sourcePath, PATHINFO_EXTENSION));
		$conflicts = [];
		if ($extension !== 'phs') {
			$conflicts[] = $stem . '.phs';
		}
		if ($extension !== 'php') {
			$conflicts[] = $stem . '.php';
		}
		if ($extension !== 'cpp') {
			$conflicts[] = $stem . '.cpp';
		}
		foreach ($conflicts as $otherPath) {
			if (!is_file($otherPath)) {
				continue;
			}
			throw new RuntimeException('Basename overlap detected: ' . $sourcePath . ' conflicts with ' . $otherPath . '. Keep PHP++ and runtime tests in separate folders when they share the same basename.');
		}
	}

	private function buildEmptyResultsDocument(array $meta, string $sourcePath, string $infoPath, string $resultsPath): array
	{
		return [
			'id' => (string) ($meta['id'] ?? basename($sourcePath)),
			'source_path' => $this->relativePath($sourcePath),
			'test_info_json' => $this->relativePath($infoPath),
			'test_results_json' => $this->relativePath($resultsPath),
			'last_run' => $this->buildEmptyLastRunState(),
		];
	}

	private function ensureDirectory(string $path): void
	{
		if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
			throw new RuntimeException('Failed to create directory: ' . $path);
		}
	}

	private function removeDirectoryTree(string $path): void
	{
		if (!is_dir($path)) {
			return;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ($iterator as $item) {
			$itemPath = $item->getPathname();
			if ($item->isDir()) {
				if (!rmdir($itemPath) && is_dir($itemPath)) {
					throw new RuntimeException('Failed to remove directory: ' . $itemPath);
				}
				continue;
			}
			if (!unlink($itemPath) && is_file($itemPath)) {
				throw new RuntimeException('Failed to remove file: ' . $itemPath);
			}
		}

		if (!rmdir($path) && is_dir($path)) {
			throw new RuntimeException('Failed to remove directory: ' . $path);
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

<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppStanBuildWorkerIntegrationTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_stan_build_worker_' . getmypid() . '_' . bin2hex(random_bytes(4)));
	}

	public function run(): int
	{
		if (find_command_path(['ninja']) === null) {
			echo "SKIP: ninja not found\n";
			return 0;
		}
		if (resolve_compiler(['build' => []]) === null) {
			echo "SKIP: compiler not found\n";
			return 0;
		}

		$previousIdle = getenv('SCPP_STAN_WORKER_IDLE_SECONDS');
		$previousWait = getenv('SCPP_STAN_BUILD_WAIT_SECONDS');
		$previousPoll = getenv('SCPP_STAN_WORKER_POLL_INTERVAL_MS');
		putenv('SCPP_STAN_WORKER_IDLE_SECONDS=1');
		putenv('SCPP_STAN_BUILD_WAIT_SECONDS=5');
		putenv('SCPP_STAN_WORKER_POLL_INTERVAL_MS=100');

		$workerProcess = null;
		try {
			$projectRoot = $this->root . '/app';
			$this->writeProject($projectRoot, <<<'PHS'
function helper(): string
{
	return "ok";
}

function main(): void
{
	$value = null;
	$value = helper();
	echo "DONE\n";
}

main();
PHS);

			$advisoryBuild = scpp_run_build_service($projectRoot, $projectRoot . '/prism.json', [
				'compile_runtime' => true,
				'compile_dependencies' => false,
			]);
			$this->assertSame(true, $advisoryBuild['ok'], 'advisory STAN findings should not block build');
			$this->assertContains('Static Analysis: 0 errors, 1 warnings, 0 notices.', $advisoryBuild['output'], 'build should print the advisory STAN summary');

			$statusPath = $projectRoot . '/.prism/cache/' . SCPP_STAN_STATUS_FILE;
			$reportPath = $projectRoot . '/.prism/cache/' . SCPP_STAN_REPORT_FILE;
			$this->assertFileExists($statusPath, 'STAN worker should publish a status file');
			$this->assertFileExists($reportPath, 'STAN worker should publish a report file');
			$status = read_json_file($statusPath);
			$report = read_json_file($reportPath);
			$this->assertSame('ready', is_array($status) ? ($status['analysis_state'] ?? null) : null, 'status file should end in ready state');
			$this->assertSame(0, is_array($report) ? ($report['compile_error_count'] ?? null) : null, 'advisory project should not produce compile-errors');
			$this->assertSame(1, is_array($report) ? ($report['stan_warning_count'] ?? null) : null, 'advisory project should preserve the warning in the report');

			$this->write($projectRoot . '/main.phs', <<<'PHS'
function main(): void
{
	missing_helper();
}

main();
PHS);

			$blockingBuild = scpp_run_build_service($projectRoot, $projectRoot . '/prism.json');
			$this->assertSame(false, $blockingBuild['ok'], 'compile-fatal STAN findings should block build');
			$this->assertContains('STAN pre-build check failed: 1 compile-errors', $blockingBuild['error'] ?? '', 'build failure should be reported as a STAN compile-error');
			$this->assertContains('Unresolved function call `missing_helper()`', $blockingBuild['error'] ?? '', 'build failure should include the unresolved call detail');
			$this->assertContains('scpp build --no-stan', $blockingBuild['error'] ?? '', 'build failure should explain the explicit STAN bypass flag');

			$blockingReport = read_json_file($reportPath);
			$this->assertSame(1, is_array($blockingReport) ? ($blockingReport['compile_error_count'] ?? null) : null, 'report should record one compile-error for the unresolved call');

			$noStanBuild = scpp_run_build_service($projectRoot, $projectRoot . '/prism.json', [
				'disable_stan' => true,
			]);
			$this->assertSame(false, $noStanBuild['ok'], '--no-stan should bypass the STAN stop, but the native build may still fail on the real compiler error');
			$this->assertTrue(!str_contains($noStanBuild['error'] ?? '', 'STAN pre-build check failed'), '--no-stan should bypass the STAN build stop specifically');
			$this->assertTrue(!str_contains($noStanBuild['output'], 'Static Analysis:'), '--no-stan should skip the STAN advisory summary entirely');

			$workerProjectRoot = $this->root . '/worker-app';
			$this->writeProject($workerProjectRoot, <<<'PHS'
function helper(): string
{
	return "ok";
}

function main(): void
{
	echo helper(), "\n";
}

main();
PHS);

			$workerProcess = $this->startStanWorker($workerProjectRoot, 30, 100);
			$workerStatusPath = $workerProjectRoot . '/.prism/cache/' . SCPP_STAN_STATUS_FILE;
			$workerReportPath = $workerProjectRoot . '/.prism/cache/' . SCPP_STAN_REPORT_FILE;
			$workerRequestPath = $workerProjectRoot . '/.prism/cache/' . SCPP_STAN_REQUEST_FILE;

			$this->waitFor(function () use ($workerStatusPath): bool {
				$status = read_json_file($workerStatusPath);
				return is_array($status) && ($status['analysis_state'] ?? null) === 'ready';
			}, 10.0, 'STAN worker should publish an initial ready status');

			$initialWorkerReport = read_json_file($workerReportPath);
			$this->assertSame(0, is_array($initialWorkerReport) ? ($initialWorkerReport['stan_warning_count'] ?? null) : null, 'worker warm-up project should start without warnings');

			$this->write($workerProjectRoot . '/main.phs', <<<'PHS'
function helper(): string
{
	return "ok";
}

function main(): void
{
	$value = null;
	$value = helper();
	echo $value, "\n";
}

main();
PHS);

			$buildWithLiveWorker = scpp_run_build_service($workerProjectRoot, $workerProjectRoot . '/prism.json', [
				'compile_runtime' => true,
				'compile_dependencies' => false,
			]);
			$this->assertSame(true, $buildWithLiveWorker['ok'], 'build should succeed when a live STAN worker refreshes advisory findings');
			$this->assertContains('Static Analysis: 0 errors, 1 warnings, 0 notices.', $buildWithLiveWorker['output'], 'build should reuse the live worker report and print the refreshed advisory summary');

			$workerRequest = read_json_file($workerRequestPath);
			$this->assertSame('build', is_array($workerRequest) ? ($workerRequest['reason'] ?? null) : null, 'build should request a live worker refresh through the request file');

			$workerStatus = read_json_file($workerStatusPath);
			$workerReport = read_json_file($workerReportPath);
			$this->assertSame('ready', is_array($workerStatus) ? ($workerStatus['analysis_state'] ?? null) : null, 'worker-backed build should end with a ready status');
			$this->assertSame(
				is_array($workerRequest) ? ($workerRequest['requested_fingerprint'] ?? null) : null,
				is_array($workerStatus) ? ($workerStatus['source_fingerprint'] ?? null) : null,
				'worker-backed build should wait for a matching fresh fingerprint'
			);
			$this->assertSame(1, is_array($workerReport) ? ($workerReport['stan_warning_count'] ?? null) : null, 'worker-backed build should publish the refreshed advisory warning');

			echo "PASS: scpp stan build worker integration\n";
			return 0;
		} finally {
			if ($previousIdle === false) {
				putenv('SCPP_STAN_WORKER_IDLE_SECONDS');
			} else {
				putenv('SCPP_STAN_WORKER_IDLE_SECONDS=' . $previousIdle);
			}
			if ($previousWait === false) {
				putenv('SCPP_STAN_BUILD_WAIT_SECONDS');
			} else {
				putenv('SCPP_STAN_BUILD_WAIT_SECONDS=' . $previousWait);
			}
			if ($previousPoll === false) {
				putenv('SCPP_STAN_WORKER_POLL_INTERVAL_MS');
			} else {
				putenv('SCPP_STAN_WORKER_POLL_INTERVAL_MS=' . $previousPoll);
			}
			$this->stopWorker($workerProcess);
			usleep(1500000);
			$this->removeTree($this->root);
		}
	}

	/** @return resource */
	private function startStanWorker(string $projectRoot, int $idleSeconds, int $pollIntervalMs)
	{
		$command = [
			PHP_BINARY,
			resolve_repo_root() . '/bin/scpp.php',
			'stan',
			'worker',
			'--idle-seconds=' . $idleSeconds,
			'--poll-interval-ms=' . $pollIntervalMs,
		];
		$descriptor = [
			0 => ['file', '/dev/null', 'r'],
			1 => ['file', '/dev/null', 'a'],
			2 => ['file', '/dev/null', 'a'],
		];
		$process = proc_open($command, $descriptor, $pipes, $projectRoot, scpp_build_process_environment());
		if (!is_resource($process)) {
			throw new RuntimeException('Failed to start STAN worker process');
		}
		return $process;
	}

	/** @param resource|null $process */
	private function stopWorker($process): void
	{
		if (!is_resource($process)) {
			return;
		}
		$status = proc_get_status($process);
		if (is_array($status) && ($status['running'] ?? false)) {
			@proc_terminate($process);
		}
		@proc_close($process);
	}

	/** @param callable():bool $predicate */
	private function waitFor(callable $predicate, float $timeoutSeconds, string $message): void
	{
		$deadline = microtime(true) + $timeoutSeconds;
		while (microtime(true) < $deadline) {
			if ($predicate()) {
				return;
			}
			usleep(100000);
		}
		throw new RuntimeException($message);
	}

	private function writeProject(string $projectRoot, string $mainSource): void
	{
		$this->mkdir($projectRoot);
		$this->mkdir($projectRoot . '/.prism/build');
		$this->mkdir($projectRoot . '/.prism/generated');
		$this->mkdir($projectRoot . '/.prism/cache');
		$this->write($projectRoot . '/prism.json', json_encode([
			'config_version' => 1,
			'project_name' => 'stan_worker_test',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'generated_dir' => '.prism/generated',
			'cache_dir' => '.prism/cache',
			'native_cpp_dir' => 'native_cpp',
			'dependencies' => [],
			'libraries' => [],
			'build' => [
				'backend' => 'ninja',
				'cxx' => null,
			],
			'runtime' => [
				'languages' => [
					'php' => [
						'profile' => 'strict',
					],
				],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($projectRoot . '/main.phs', $mainSource);
	}

	private function write(string $path, string $contents): void
	{
		if (file_put_contents($path, $contents) === false) {
			throw new RuntimeException('Failed to write ' . $path);
		}
	}

	private function mkdir(string $path): void
	{
		if (!is_dir($path) && !mkdir($path, 0777, true)) {
			throw new RuntimeException('Failed to create ' . $path);
		}
	}

	private function read(string $path): string
	{
		$contents = file_get_contents($path);
		if (!is_string($contents)) {
			throw new RuntimeException('Failed to read ' . $path);
		}
		return $contents;
	}

	private function assertSame(mixed $expected, mixed $actual, string $message): void
	{
		if ($expected !== $actual) {
			throw new RuntimeException($message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
		}
	}

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (!str_contains($haystack, $needle)) {
			throw new RuntimeException($message . "\nMissing: " . $needle . "\nIn: " . $haystack);
		}
	}

	private function assertTrue(bool $condition, string $message): void
	{
		if (!$condition) {
			throw new RuntimeException($message);
		}
	}

	private function assertFileExists(string $path, string $message): void
	{
		if (!is_file($path)) {
			throw new RuntimeException($message . "\nMissing file: " . $path);
		}
	}

	private function removeTree(string $path): void
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
			if (is_dir($child) && !is_link($child)) {
				$this->removeTree($child);
				continue;
			}
			@unlink($child);
		}
		@rmdir($path);
	}
}

$test = new ScppStanBuildWorkerIntegrationTest();
exit($test->run());

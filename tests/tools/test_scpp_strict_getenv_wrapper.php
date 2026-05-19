<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppStrictGetenvWrapperTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_strict_getenv_' . getmypid() . '_' . bin2hex(random_bytes(4)));
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

		try {
			$project = $this->root . '/strict_getenv_wrapper';
			$this->mkdir($project . '/native_cpp');
			$this->write($project . '/prism.json', json_encode([
				'config_version' => 1,
				'project_name' => 'strict-getenv-wrapper',
				'entrypoint' => 'main.phs',
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
				'runtime' => [
					'languages' => [
						'php' => ['profile' => 'strict'],
					],
					'modules' => [],
				],
			], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
			$this->write($project . '/main.phs', implode("\n", [
				'$host = getenv("SCPP_TEST_DB_HOST");',
				'if ($host === false) {',
				'	echo "missing\\n";',
				'} else {',
				'	echo $host, "\\n";',
				'}',
				'',
			]));

			$build = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'build', '--build-runtime'], $project, 180, [
				'SCPP_TEST_DB_HOST' => 'db.example.local',
			]);
			$this->assertSame(0, $build['exit_code'], 'scpp build should accept strict getenv calls');
			$this->assertNotContains('cannot convert', $build['stderr'], 'build stderr should not show the raw C getenv conversion failure');
			$this->assertNotContains('const char*', $build['stderr'], 'build stderr should not expose the raw C getenv ABI');

			$runBinary = $project . '/.prism/build/main';
			$run = $this->runCommand([$runBinary], $project, 60, [
				'SCPP_TEST_DB_HOST' => 'db.example.local',
			]);
			$this->assertSame(0, $run['exit_code'], 'compiled strict getenv sample should run successfully');
			$this->assertSame("db.example.local\n", $run['stdout'], 'strict getenv wrapper should return the environment value');

			echo "PASS: scpp strict getenv wrapper\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	/** @return array{exit_code:int,stdout:string,stderr:string} */
	private function runCommand(array $command, string $cwd, int $timeoutSeconds, array $extraEnv = []): array
	{
		$descriptor = [
			0 => ['file', '/dev/null', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$process = proc_open($command, $descriptor, $pipes, $cwd, scpp_build_process_environment($extraEnv + [
			'SCPP_CXX_LAUNCHER' => ' ',
		]));
		if (!is_resource($process)) {
			throw new RuntimeException('Failed to start command: ' . implode(' ', $command));
		}

		$stdout = '';
		$stderr = '';
		$started = microtime(true);
		$observedExitCode = null;
		foreach ([1, 2] as $index) {
			stream_set_blocking($pipes[$index], false);
		}
		while (true) {
			$status = proc_get_status($process);
			$stdout .= (string) stream_get_contents($pipes[1]);
			$stderr .= (string) stream_get_contents($pipes[2]);
			if (($status['running'] ?? false) !== true) {
				$exitCode = $status['exitcode'] ?? null;
				$observedExitCode = is_int($exitCode) ? $exitCode : null;
				break;
			}
			if ((microtime(true) - $started) > $timeoutSeconds) {
				proc_terminate($process);
				throw new RuntimeException('Timed out after ' . $timeoutSeconds . 's: ' . implode(' ', $command));
			}
			usleep(100000);
		}
		$stdout .= (string) stream_get_contents($pipes[1]);
		$stderr .= (string) stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$exitCode = proc_close($process);
		return [
			'exit_code' => $observedExitCode ?? (is_int($exitCode) ? $exitCode : 1),
			'stdout' => $stdout,
			'stderr' => $stderr,
		];
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
			unlink($child);
		}
		rmdir($path);
	}

	private function assertSame(mixed $expected, mixed $actual, string $message): void
	{
		if ($expected !== $actual) {
			throw new RuntimeException($message . ' expected ' . var_export($expected, true) . ' got ' . var_export($actual, true) . '.');
		}
	}

	private function assertNotContains(string $needle, string $haystack, string $message): void
	{
		if (str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' found `' . $needle . '` in: ' . $haystack);
		}
	}
}

exit((new ScppStrictGetenvWrapperTest())->run());

<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppRuntimeDiagnosticsTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_runtime_diagnostics_' . getmypid() . '_' . bin2hex(random_bytes(4)));
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
			$project = $this->root . '/app';
			$this->mkdir($project);
			$this->write($project . '/prism.json', json_encode([
				'name' => 'runtime-diagnostics-regression',
				'entrypoint' => 'main.phs',
				'build_dir' => '.prism/build',
				'runtime' => [
					'languages' => [
						'php' => ['profile' => 'strict'],
					],
					'modules' => ['json', 'filesystem'],
				],
			], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
			$this->write($project . '/main.phs', implode("\n", [
				'$data = [];',
				'$data["name"] = [];',
				'echo "name=" . $data["name"], "\\n";',
				'',
			]));

			$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 120);
			$this->assertNotSame(0, $run['exit_code'], 'runtime type failure should make scpp run fail');
			$this->assertContains('Runtime error while running the built program.', $run['stderr'], 'run stderr should report a structured runtime failure');
			$this->assertContains('Actual runtime kind: shared_hash_t', $run['stderr'], 'run stderr should report actual runtime kind');
			$this->assertFileDoesNotContain($project . '/.prism/generated/main.cpp', 'with_runtime_context', 'generated code should not use expression-level runtime context wrappers');
			$generatedCppLines = explode("\n", rtrim($this->read($project . '/.prism/generated/main.cpp'), "\n"));
			$lineMap = $this->readGeneratedLineMap($project . '/.prism/generated/main.cpp.line.tsv');
			$echoGeneratedLine = $this->findGeneratedLine($generatedCppLines, 'php::echo_one');
			$this->assertSame(3, $lineMap[$echoGeneratedLine] ?? null, 'generated echo statement should map back to its source statement line');

			$error = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'error'], $project, 30);
			$this->assertSame(0, $error['exit_code'], 'scpp error should read saved runtime diagnostics');
			$this->assertContains('Category: runtime / invalid_mixed_kind_for_cast_string', $error['stdout'], 'saved summary should preserve runtime error code');

			$report = json_decode($this->read($project . '/.prism/last_error.json'), true);
			if (!is_array($report)) {
				throw new RuntimeException('last_error.json should decode as an object');
			}
			$diagnostic = $report['diagnostics'][0] ?? null;
			if (!is_array($diagnostic)) {
				throw new RuntimeException('last_error.json should contain at least one runtime diagnostic');
			}
			$this->assertSame('strict', $report['project_mode'] ?? null, 'last_error.json should store strict project mode');
			$this->assertSame(null, $diagnostic['source_file'] ?? null, 'runtime diagnostics should not use removed source-context wrapper fields');
			$this->assertSame(null, $diagnostic['source_line'] ?? null, 'runtime diagnostics should not use removed source-context wrapper fields');
			$this->assertSame(null, $diagnostic['expression'] ?? null, 'runtime diagnostics should not use removed source-context wrapper fields');
			$this->assertSame(null, $diagnostic['expected_type'] ?? null, 'runtime diagnostics should not use removed source-context wrapper fields');
			$this->assertSame('shared_hash_t', $diagnostic['actual_runtime_kind'] ?? null, 'runtime diagnostic should preserve actual runtime kind');

			echo "PASS: scpp runtime diagnostics\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	/** @return array{exit_code:int,stdout:string,stderr:string} */
	private function runCommand(array $command, string $cwd, int $timeoutSeconds): array
	{
		$descriptor = [
			0 => ['file', '/dev/null', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$process = proc_open($command, $descriptor, $pipes, $cwd, scpp_build_process_environment());
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

	private function read(string $path): string
	{
		$contents = file_get_contents($path);
		if (!is_string($contents)) {
			throw new RuntimeException('Failed to read ' . $path);
		}
		return $contents;
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
			throw new RuntimeException($message . ' expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
		}
	}

	private function assertNotSame(mixed $unexpected, mixed $actual, string $message): void
	{
		if ($unexpected === $actual) {
			throw new RuntimeException($message . ' did not expect ' . var_export($actual, true));
		}
	}

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (!str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' missing `' . $needle . '` in: ' . $haystack);
		}
	}

	private function assertFileDoesNotContain(string $path, string $needle, string $message): void
	{
		$contents = $this->read($path);
		if (str_contains($contents, $needle)) {
			throw new RuntimeException($message . ' found `' . $needle . '` in ' . $path);
		}
	}

	/** @param list<string> $lines */
	private function findGeneratedLine(array $lines, string $needle): int
	{
		foreach ($lines as $index => $line) {
			if (str_contains($line, $needle)) {
				return $index + 1;
			}
		}
		throw new RuntimeException('Could not find generated line containing `' . $needle . '`');
	}

	/** @return array<int,int> */
	private function readGeneratedLineMap(string $path): array
	{
		$lines = explode("\n", trim($this->read($path)));
		array_shift($lines);
		$map = [];
		foreach ($lines as $line) {
			if ($line === '') {
				continue;
			}
			$parts = explode("\t", $line);
			if (count($parts) !== 2) {
				throw new RuntimeException('Malformed line-map row: ' . $line);
			}
			$map[(int) $parts[0]] = (int) $parts[1];
		}
		return $map;
	}
}

exit((new ScppRuntimeDiagnosticsTest())->run());

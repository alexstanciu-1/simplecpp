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
			$this->write($project . '/main.phs', <<<'PHS'
class child_state
{
	public ?string $name = null;
}

class root_state
{
	public ?child_state $child = null;
}

function test_guard(?root_state $root): void
{
	$match = ($root === null || $root->child === null || $root->child->name === null) ? "no" : "yes";
	echo "match=", $match, "\n";
}

$full = new root_state();
$full->child = new child_state();
$full->child->name = "ok";
test_guard($full);

$missing_child = new root_state();
test_guard($missing_child);
PHS
 . "\n");

			$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 120);
			$this->assertNotSame(0, $run['exit_code'], 'runtime type failure should make scpp run fail');
			$this->assertContains('Runtime error in main.phs:13', $run['stderr'], 'run stderr should report the remapped source location');
			$this->assertContains('Source:', $run['stderr'], 'run stderr should include a tiny source snippet by default');
			$this->assertContains('> 13 |', $run['stderr'], 'run stderr should highlight the failing source line');
			$this->assertContains('Operation: operator->', $run['stderr'], 'run stderr should report the failing operation');
			$this->assertDoesNotContain('Runtime message:', $run['stderr'], 'run stderr should hide the raw runtime message by default');
			$this->assertContains('Trace:', $run['stderr'], 'run stderr should surface a compact trace when debug trace is enabled');
			$this->assertContains('at main.phs:13 |', $run['stderr'], 'run stderr should enrich source-mapped app frames with source code');
			$this->assertContains('at main.phs:23 |', $run['stderr'], 'run stderr should enrich follow-on app frames with source code');
			$this->assertDoesNotContain('scpp::test_guard', $run['stderr'], 'run stderr should not expose generated C++ function names in the default trace');
			$this->assertContains("More trace detail is available in 'scpp full-error'.", $run['stderr'], 'run stderr should explain how to get generated/runtime trace details');
			$this->assertFileDoesNotContain($project . '/.prism/generated/main.cpp', 'with_runtime_context', 'generated code should not use expression-level runtime context wrappers');
			$this->assertFileDoesNotContain($project . '/.prism/generated/main.cpp', 'cast_with_generated_location', 'generated code should not use generated-location cast helpers');

			$error = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'error'], $project, 30);
			$this->assertSame(0, $error['exit_code'], 'scpp error should read saved runtime diagnostics');
			$this->assertContains('Category: runtime / invalid_shared_arrow_null', $error['stdout'], 'saved summary should preserve runtime error code');
			$this->assertContains('Runtime message:', $error['stdout'], 'scpp error should still expose the raw runtime message');
			$this->assertContains('Trace:', $error['stdout'], 'scpp error should show the saved compact trace before full-error');
			$this->assertContains('at main.phs:13 |', $error['stdout'], 'scpp error should show source-backed trace entries');
			$this->assertDoesNotContain('scpp::test_guard', $error['stdout'], 'scpp error should not expose generated C++ function names in the default trace');

			$report = json_decode($this->read($project . '/.prism/last_error.json'), true);
			if (!is_array($report)) {
				throw new RuntimeException('last_error.json should decode as an object');
			}
			$diagnostic = $report['diagnostics'][0] ?? null;
			if (!is_array($diagnostic)) {
				throw new RuntimeException('last_error.json should contain at least one runtime diagnostic');
			}
			$this->assertSame('strict', $report['project_mode'] ?? null, 'last_error.json should store strict project mode');
			$this->assertSame(normalize_path($project . '/main.phs'), $diagnostic['original_file'] ?? null, 'runtime diagnostics should remap back to the original source file');
			$this->assertSame(13, $diagnostic['original_line'] ?? null, 'runtime diagnostics should remap back to the original source line');
			$this->assertSame('operator->', $diagnostic['operation'] ?? null, 'runtime diagnostics should preserve the failing operation');
			$this->assertTrue(is_array($diagnostic['trace'] ?? null) && $diagnostic['trace'] !== [], 'runtime diagnostics should preserve the compact trace frames');
			$generatedFile = (string) ($diagnostic['generated_file'] ?? '');
			$generatedLine = (int) ($diagnostic['generated_line'] ?? 0);
			$this->assertTrue($generatedFile !== '' && $generatedLine > 0, 'runtime diagnostics should preserve the generated location used for remapping');
			$lineMap = $this->readGeneratedLineMap($generatedFile . '.line.tsv');
			$this->assertSame(13, $lineMap[$generatedLine]['line'] ?? null, 'saved generated location should map back to the failing source statement line');

			$boundsProject = $this->root . '/bounds';
			$this->mkdir($boundsProject);
			$this->write($boundsProject . '/prism.json', json_encode([
				'name' => 'runtime-bounds-diagnostics-regression',
				'entrypoint' => 'main.phs',
				'build_dir' => '.prism/build',
				'runtime' => [
					'languages' => [
						'php' => ['profile' => 'strict'],
					],
					'modules' => ['json', 'filesystem'],
				],
			], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
			$this->write($boundsProject . '/main.phs', <<<'PHS'
$items vector<int> = [1, 2, 3];
echo $items[99], "\n";
PHS
 . "\n");

			$boundsRun = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $boundsProject, 120);
			$this->assertNotSame(0, $boundsRun['exit_code'], 'vector bounds failure should make scpp run fail');
			$this->assertContains('Runtime error in main.phs:2', $boundsRun['stderr'], 'bounds stderr should report the remapped source location');
			$this->assertContains('Vector index is out of bounds (index 99, size 3).', $boundsRun['stderr'], 'bounds stderr should describe the source-level bounds failure');
			$this->assertContains('> 2 | echo $items[99], "\n";', $boundsRun['stderr'], 'bounds stderr should highlight the failing source line');
			$this->assertContains('Operation: operator[]', $boundsRun['stderr'], 'bounds stderr should report the failing operation');

			$boundsReport = json_decode($this->read($boundsProject . '/.prism/last_error.json'), true);
			if (!is_array($boundsReport)) {
				throw new RuntimeException('bounds last_error.json should decode as an object');
			}
			$boundsDiagnostic = $boundsReport['diagnostics'][0] ?? null;
			if (!is_array($boundsDiagnostic)) {
				throw new RuntimeException('bounds last_error.json should contain at least one runtime diagnostic');
			}
			$this->assertSame('bounds_error', $boundsDiagnostic['code'] ?? null, 'bounds report should preserve the structured bounds error code');
			$this->assertSame(normalize_path($boundsProject . '/main.phs'), $boundsDiagnostic['original_file'] ?? null, 'bounds diagnostics should remap back to source');
			$this->assertSame(2, $boundsDiagnostic['original_line'] ?? null, 'bounds diagnostics should remap back to the failing source line');
			$this->assertSame('operator[]', $boundsDiagnostic['operation'] ?? null, 'bounds diagnostics should preserve the failing operation');
			$this->assertSame('99', $boundsDiagnostic['index'] ?? null, 'bounds diagnostics should preserve the failing index');
			$this->assertSame('3', $boundsDiagnostic['size'] ?? null, 'bounds diagnostics should preserve the vector size');

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
			$process = proc_open($command, $descriptor, $pipes, $cwd, scpp_build_process_environment([
				'SCPP_CXX_LAUNCHER' => ' ',
				'SCPP_DEBUG_TRACE' => '1',
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

	private function assertDoesNotContain(string $needle, string $haystack, string $message): void
	{
		if (str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' unexpectedly found `' . $needle . '` in: ' . $haystack);
		}
	}

	private function assertTrue(bool $condition, string $message): void
	{
		if (!$condition) {
			throw new RuntimeException($message);
		}
	}

	private function assertFileDoesNotContain(string $path, string $needle, string $message): void
	{
		$contents = $this->read($path);
		if (str_contains($contents, $needle)) {
			throw new RuntimeException($message . ' found `' . $needle . '` in ' . $path);
		}
	}

	/** @return array<int,array{line:int,relation:string}> */
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
			if (count($parts) < 2) {
				throw new RuntimeException('Malformed line-map row: ' . $line);
			}
			$map[(int) $parts[0]] = [
				'line' => (int) $parts[1],
				'relation' => isset($parts[2]) ? (string) $parts[2] : 'exact',
			];
		}
		return $map;
	}
}

exit((new ScppRuntimeDiagnosticsTest())->run());

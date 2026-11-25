<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppConstParamsTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_const_params_' . getmypid() . '_' . bin2hex(random_bytes(4)));
	}

	public function run(): int
	{
		try {
			$this->mkdir($this->root);
			$this->assertTranspilePreservesConstSignatures();

			if (find_command_path(['ninja']) === null) {
				echo "SKIP: ninja not found for build-gated const param cases\n";
				return 0;
			}
			if (resolve_compiler(['build' => []]) === null) {
				echo "SKIP: compiler not found for build-gated const param cases\n";
				return 0;
			}

			$this->assertConstParamWriteStopsBuild();
			$this->assertInterfaceConstMismatchStopsBuild();
			$this->assertAbstractConstMismatchStopsBuild();

			echo "PASS: scpp const params\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function assertTranspilePreservesConstSignatures(): void
	{
		$file = $this->root . '/const_params.phs';
		$this->write($file, implode("\n", [
			'function inspect(const int $count, const vector<int> &$items): int {',
			'	return $count;',
			'}',
			'',
			'$reader = function (const int $value): int {',
			'	return $value;',
			'};',
			'$arrow = fn(const int $value): int => $value;',
			'',
		]));

		$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', $file], $this->root, 60);
		$this->assertSame(0, $run['exit_code'], 'single-file transpile should accept const params');
		$this->assertContains('inspect(const int_t<> count, const vector_t<int_t<>>& items)', $run['stdout'], 'generated free function signature should preserve const value and const ref params');
		$this->assertContains('[](const int_t<> value) -> int_t<>', $run['stdout'], 'generated closure signature should preserve const params');
		if (substr_count($run['stdout'], '[](const int_t<> value) -> int_t<>') < 2) {
			throw new RuntimeException('generated closure and arrow signatures should both preserve const params');
		}
	}

	private function assertConstParamWriteStopsBuild(): void
	{
		$project = $this->makeProject('const-param-write', [
			'function bad(const int $value): void {',
			'	$value = 2;',
			'}',
			'',
			'bad(1);',
			'',
		]);

		$build = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'build'], $project, 120);
		$this->assertNotSame(0, $build['exit_code'], 'const param write should stop scpp build');
		$this->assertContains('STAN pre-build check failed', $build['stderr'], 'const param write should be a STAN compile error');
		$this->assertContains('Cannot write through const parameter `$value`', $build['stderr'], 'const param write diagnostic should name the parameter');
	}

	private function assertInterfaceConstMismatchStopsBuild(): void
	{
		$project = $this->makeProject('const-param-interface', [
			'interface Reader {',
			'	public function read(const string &$text): int;',
			'}',
			'',
			'class BadReader implements Reader {',
			'	public function read(string $text): int {',
			'		return strlen($text);',
			'	}',
			'}',
			'',
		]);

		$build = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'build'], $project, 120);
		$this->assertNotSame(0, $build['exit_code'], 'interface const mismatch should stop scpp build');
		$this->assertContains('STAN pre-build check failed', $build['stderr'], 'interface const mismatch should be a STAN compile error');
		$this->assertContains('expected const parameter, got non-const', $build['stderr'], 'interface const mismatch diagnostic should describe constness');
	}

	private function assertAbstractConstMismatchStopsBuild(): void
	{
		$project = $this->makeProject('const-param-abstract', [
			'abstract class BaseReader {',
			'	abstract public function read(const string &$text): int;',
			'}',
			'',
			'class BadReader extends BaseReader {',
			'	public function read(string $text): int {',
			'		return strlen($text);',
			'	}',
			'}',
			'',
		]);

		$build = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'build'], $project, 120);
		$this->assertNotSame(0, $build['exit_code'], 'abstract const mismatch should stop scpp build');
		$this->assertContains('STAN pre-build check failed', $build['stderr'], 'abstract const mismatch should be a STAN compile error');
		$this->assertContains('does not match abstract method contract', $build['stderr'], 'abstract const mismatch diagnostic should describe the contract failure');
	}

	/** @param list<string> $lines */
	private function makeProject(string $name, array $lines): string
	{
		$project = $this->root . '/' . $name;
		$this->mkdir($project . '/native_cpp');
		$this->write($project . '/prism.json', json_encode([
			'config_version' => 1,
			'project_name' => $name,
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
		$this->write($project . '/main.phs', implode("\n", $lines));
		return $project;
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
			} else {
				@unlink($child);
			}
		}
		@rmdir($path);
	}

	private function assertSame(mixed $expected, mixed $actual, string $message): void
	{
		if ($expected !== $actual) {
			throw new RuntimeException($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
		}
	}

	private function assertNotSame(mixed $expected, mixed $actual, string $message): void
	{
		if ($expected === $actual) {
			throw new RuntimeException($message . ' Did not expect ' . var_export($actual, true));
		}
	}

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (!str_contains($haystack, $needle)) {
			throw new RuntimeException($message . "\nMissing: " . $needle . "\nHaystack:\n" . $haystack);
		}
	}
}

exit((new ScppConstParamsTest())->run());

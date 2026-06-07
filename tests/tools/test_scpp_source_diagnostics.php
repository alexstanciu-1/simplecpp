<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppSourceDiagnosticsTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_source_diagnostics_' . getmypid() . '_' . bin2hex(random_bytes(4)));
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
			$project = $this->root . '/php_open_tag';
			$this->mkdir($project . '/native_cpp');
			$this->write($project . '/prism.json', json_encode([
				'config_version' => 1,
				'project_name' => 'php-open-tag-only-repro',
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
					'modules' => ['json', 'filesystem'],
				],
			], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
			$this->write($project . '/main.phs', implode("\n", [
				'<?php',
				'',
				'echo "ok\n";',
				'',
			]));

			$build = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'build', '--build-runtime'], $project, 120);
			$this->assertNotSame(0, $build['exit_code'], 'scpp build should fail for a .phs file starting with <?php');
			$this->assertContains('Unsupported source header in ', $build['stderr'], 'build stderr should report a friendly unsupported-source diagnostic');
			$this->assertContains('Prism++ .phs source files must not begin with `<?php`.', $build['stderr'], 'build stderr should explain the unsupported header clearly');
			$this->assertNotContains('internal error:', $build['stderr'], 'build stderr should not present this as an internal error');
			$this->assertNotContains('unexpected token "<"', $build['stderr'], 'build stderr should not leak the parser-level token error');

			$regexProject = $this->root . '/regex_module_missing';
			$this->mkdir($regexProject . '/native_cpp');
			$this->write($regexProject . '/prism.json', json_encode([
				'config_version' => 1,
				'project_name' => 'regex-module-missing-repro',
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
					'modules' => ['json', 'filesystem'],
				],
			], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
			$this->write($regexProject . '/main.phs', implode("\n", [
				'$caps vector<string> = [];',
				'if (take($caps, regex_match("/(ab+)-(cd+)/i", "xxAbb-cDDyy"))) {',
				'	echo $caps[0], "\n";',
				'}',
				'',
			]));

			$regexBuild = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'build'], $regexProject, 120);
			$this->assertNotSame(0, $regexBuild['exit_code'], 'scpp build should fail before link when regex helpers are used without the regex module');
			$this->assertContains('Regex helper `regex_match` requires runtime module `regex`', $regexBuild['stderr'], 'build stderr should report the missing regex runtime module');
			$this->assertContains('Source: main.phs:2', $regexBuild['stderr'], 'build stderr should map the regex helper use back to source');
			$this->assertNotContains('undefined reference', $regexBuild['stderr'], 'build stderr should not fall through to a native link failure');

			echo "PASS: scpp source diagnostics\n";
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

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (!str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' missing `' . $needle . '` in: ' . $haystack);
		}
	}

	private function assertNotContains(string $needle, string $haystack, string $message): void
	{
		if (str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' found `' . $needle . '` in: ' . $haystack);
		}
	}

	private function assertNotSame(mixed $unexpected, mixed $actual, string $message): void
	{
		if ($unexpected === $actual) {
			throw new RuntimeException($message . ' did not expect ' . var_export($actual, true));
		}
	}
}

exit((new ScppSourceDiagnosticsTest())->run());

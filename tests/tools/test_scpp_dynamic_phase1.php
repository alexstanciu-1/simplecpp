<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppDynamicPhase1Test
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_dynamic_phase1_' . getmypid() . '_' . bin2hex(random_bytes(4)));
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
			$project = $this->root . '/dynamic_phase1';
			$this->mkdir($project . '/native_cpp');
			$this->write($project . '/prism.json', json_encode([
				'config_version' => 1,
				'project_name' => 'dynamic-phase1',
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
				'function touch_and_return(dynamic $bag): dynamic {',
				'	$bag["seen"] = "yes";',
				'	return $bag;',
				'}',
				'',
				'class Holder {',
				'	public $bag dynamic;',
				'	public $items hash<dynamic> = [];',
				'	public $list vector<dynamic> = [];',
				'',
				'	public function store(dynamic $bag): dynamic {',
				'		$this->bag = $bag;',
				'		return $this->bag;',
				'	}',
				'}',
				'',
				'$root dynamic = (object) ["name" => "alpha"];',
				'$alias dynamic = $root;',
				'$alias["count"] = 2;',
				'',
				'$holder = new Holder();',
				'$echoed dynamic = $holder->store(touch_and_return($root));',
				'',
				'$holder->items["first"] = $root;',
				'$holder->list[] = $echoed;',
				'$empty dynamic = null;',
				'',
				'echo (string) $root["name"], " ", (string) $root["seen"], " ", (string) $root["count"], "\\n";',
				'echo (string) $echoed["name"], " ", (string) $holder->bag["count"], "\\n";',
				'echo (string) count($root), " ", (string) count($holder->items), " ", (string) count($holder->list), "\\n";',
				'echo (string) $holder->items["first"]["seen"], " ", (string) $holder->list[0]["count"], "\\n";',
				'echo isset($root["seen"]) ? "1" : "0", " ", isset($empty["seen"]) ? "1" : "0", "\\n";',
				'echo empty($root["missing"]) ? "1" : "0", " ", empty($empty) ? "1" : "0", "\\n";',
				'',
			]));

			$build = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'build', '--build-runtime'], $project, 180);
			$this->assertSame(0, $build['exit_code'], 'scpp build should accept first-class dynamic Phase 1 shapes');

			$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 180);
			$this->assertSame(0, $run['exit_code'], 'scpp run should succeed for first-class dynamic Phase 1 coverage');
			$this->assertContains("alpha yes 2\n", $run['stdout'], 'aliasing and dynamic local behavior should preserve shared identity');
			$this->assertContains("alpha 2\n", $run['stdout'], 'property/param/return dynamic behavior should round-trip');
			$this->assertContains("3 1 1\n", $run['stdout'], 'count(dynamic), hash<dynamic>, and vector<dynamic> should all report the expected sizes');
			$this->assertContains("yes 2\n", $run['stdout'], 'typed containers of dynamic should preserve nested dynamic reads');
			$this->assertContains("1 0\n", $run['stdout'], 'isset(dynamic[key]) should succeed for present values and stay false for null dynamic');
			$this->assertContains("1 1\n", $run['stdout'], 'empty(dynamic[key]) and empty(null dynamic) should follow the Phase 1 helper contract');

			echo "PASS: scpp dynamic phase1\n";
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

	private function assertSame(mixed $expected, mixed $actual, string $message): void
	{
		if ($expected !== $actual) {
			throw new RuntimeException($message . ' expected ' . var_export($expected, true) . ' got ' . var_export($actual, true) . '.');
		}
	}

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (!str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' missing `' . $needle . '` in: ' . $haystack);
		}
	}
}

exit((new ScppDynamicPhase1Test())->run());

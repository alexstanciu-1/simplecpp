<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppMemberVisibilityTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_member_visibility_' . getmypid() . '_' . bin2hex(random_bytes(4)));
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
			$this->assertPrivatePropertyReadStopsBuild();
			$this->assertPrivateStaticPropertyAccessStopsBuild();
			$this->assertPrivateParentPropertyAccessStopsBuild();
			$this->assertProtectedSubclassAccessPassesStan();
			$this->assertVisibilityLowersToCppAccessSections();
			echo "PASS: scpp member visibility\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function assertPrivatePropertyReadStopsBuild(): void
	{
		$project = $this->root . '/private_property_read';
		$this->writeProject($project, implode("\n", [
			'class SecretBox {',
			'	private int $value;',
			'	function __construct(int $value) { $this->value = $value; }',
			'	function read(): int { return $this->value; }',
			'}',
			'$box = new SecretBox(7);',
			'echo $box->value, "\n";',
			'',
		]));

		$build = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'build'], $project, 120);
		$this->assertNotSame(0, $build['exit_code'], 'external private property read should stop build');
		$this->assertContains('Cannot read private property `SecretBox::$value`', $build['stderr'], 'build stderr should report the private property violation');
		$this->assertContains('Build stopped before C++ generation/compilation.', $build['stderr'], 'visibility violation should stop before native build');
	}

	private function assertPrivateStaticPropertyAccessStopsBuild(): void
	{
		foreach ([
			'read' => 'echo SecretBox::$value, "\n";',
			'write' => 'SecretBox::$value = 9;',
		] as $operation => $accessLine) {
			$project = $this->root . '/private_static_property_' . $operation;
			$this->writeProject($project, implode("\n", [
				'class SecretBox {',
				'	private static int $value = 7;',
				'	static function read(): int { return self::$value; }',
				'}',
				$accessLine,
				'',
			]));

			$build = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'build'], $project, 120);
			$this->assertNotSame(0, $build['exit_code'], 'external private static property ' . $operation . ' should stop build');
			$this->assertContains('Cannot ' . $operation . ' private static property `SecretBox::$value`', $build['stderr'], 'build stderr should report the private static property ' . $operation . ' violation');
			$this->assertContains('Build stopped before C++ generation/compilation.', $build['stderr'], 'private static property ' . $operation . ' should stop before native build');
		}
	}

	private function assertPrivateParentPropertyAccessStopsBuild(): void
	{
		$project = $this->root . '/private_parent_property';
		$this->writeProject($project, implode("\n", [
			'class BaseBox {',
			'	private int $seed = 2;',
			'}',
			'class SecretBox extends BaseBox {',
			'	function read(): int { return $this->seed; }',
			'}',
			'$box = new SecretBox();',
			'echo $box->read(), "\n";',
			'',
		]));

		$build = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'build'], $project, 120);
		$this->assertNotSame(0, $build['exit_code'], 'subclass read of parent private property should stop build');
		$this->assertContains('Cannot read private property `SecretBox::$seed`', $build['stderr'], 'build stderr should report inherited private property access as invalid');
		$this->assertContains('Build stopped before C++ generation/compilation.', $build['stderr'], 'parent private property access should stop before native build');
	}

	private function assertProtectedSubclassAccessPassesStan(): void
	{
		$project = $this->root . '/protected_subclass_access';
		$this->writeProject($project, implode("\n", [
			'class BaseBox {',
			'	protected int $seed = 2;',
			'	protected static int $count = 5;',
			'	protected function bump(): int { return $this->seed + self::$count; }',
			'}',
			'class SecretBox extends BaseBox {',
			'	function read(): int {',
			'		self::$count = self::$count + 1;',
			'		return $this->seed + $this->bump() + self::$count;',
			'	}',
			'}',
			'$box = new SecretBox();',
			'echo $box->read(), "\n";',
			'',
		]));

		$stan = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'stan'], $project, 120);
		$this->assertSame(0, $stan['exit_code'], 'protected subclass access should pass STAN');
		$this->assertNotContains('member_visibility_violation', $stan['stderr'] . $stan['stdout'], 'protected subclass access should not report a visibility violation');
		$this->assertNotContains('Cannot read protected', $stan['stderr'] . $stan['stdout'], 'protected reads should be allowed in subclass scope');
		$this->assertNotContains('Cannot write protected', $stan['stderr'] . $stan['stdout'], 'protected writes should be allowed in subclass scope');
	}

	private function assertVisibilityLowersToCppAccessSections(): void
	{
		$project = $this->root . '/lowering_sections';
		$this->writeProject($project, implode("\n", [
			'class BaseBox {',
			'	protected int $seed = 2;',
			'	protected function seed(): int { return $this->seed; }',
			'}',
			'class SecretBox extends BaseBox {',
			'	private int $value;',
			'	private const HIDDEN = 5;',
			'	public int $shown = 1;',
			'	function __construct(int $value) { $this->value = $value; }',
			'	function read(): int { return $this->value + $this->seed(); }',
			'}',
			'$box = new SecretBox(7);',
			'echo $box->read(), "\n";',
			'',
		]));

		$build = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'build', '--no-stan'], $project, 120);
		$this->assertNotSame(0, $build['exit_code'], 'project build may stop at missing reusable runtime after generation in this test environment');
		$header = $this->read($project . '/.prism/generated/main.hpp');
		$this->assertContains('protected:', $header, 'generated C++ should include protected access sections');
		$this->assertContains('private:', $header, 'generated C++ should include private access sections');
		$this->assertContains('int_t value;', $header, 'private property should still be emitted');
		$this->assertContains('static inline const auto HIDDEN', $header, 'private class constant should still be emitted');
		$this->assertContains('public:', $header, 'generated C++ should keep public sections for public API members');
	}

	private function writeProject(string $project, string $source): void
	{
		$this->mkdir($project . '/native_cpp');
		$this->write($project . '/prism.json', json_encode([
			'config_version' => 1,
			'project_name' => basename($project),
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
		$this->write($project . '/main.phs', $source);
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
}

exit((new ScppMemberVisibilityTest())->run());

<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppAsyncCoreLanguageTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_async_core_language_' . getmypid() . '_' . bin2hex(random_bytes(4)));
		register_shutdown_function(function (): void {
			$this->removeTree($this->root);
		});
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

		$project = $this->root . '/app';
		$this->writeProject($project);

		$build = scpp_run_build_service($project, $project . '/prism.json', [
			'compile_runtime' => true,
			'compile_dependencies' => false,
		]);
		$this->assertSame(true, $build['ok'], "async core language project should build:\n" . (string) ($build['output'] ?? ''));
		$this->assertNotContains('Static Analysis:', (string) ($build['output'] ?? ''), 'async core language project should not emit STAN warnings');

		$run = scpp_run_binary_service($project, $project . '/.prism/build/main', [], $build);
		$this->assertSame(0, (int) ($run['exit_code'] ?? 1), "async core language binary should run:\nSTDOUT:\n" . (string) ($run['stdout'] ?? '') . "\nSTDERR:\n" . (string) ($run['stderr'] ?? ''));
		$this->assertContains('42', (string) ($run['stdout'] ?? ''), 'async_wait should return the async function result');

		$generated = $this->read($project . '/.prism/generated/main.cpp');
		$this->assertContains('scpp::async_core::task<int_t> compute_value()', $generated, 'async function should lower to task<T>');
		$this->assertContains('co_await scpp::async_core::sleep_ms', $generated, 'async_sleep_ms should lower to co_await sleep_ms');
		$this->assertContains('co_return', $generated, 'return inside async function should lower to co_return');
		$this->assertContains('42', $generated, 'async return value should be preserved in generated C++');
		$this->assertContains('scpp::async_core::sync_wait(compute_value())', $generated, 'async_wait should lower to sync_wait');

		echo "PASS: scpp async core language\n";
		return 0;
	}

	private function writeProject(string $project): void
	{
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'name' => 'async-core-language-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'generated_dir' => '.prism/generated',
			'cache_dir' => '.prism/cache',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', <<<'PHS'
/** @async */
function compute_value(): int {
	async_sleep_ms(1);
	return 42;
}

echo async_wait(compute_value());
PHS);
	}

	private function mkdir(string $path): void
	{
		if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
			throw new RuntimeException('Failed to create directory ' . $path);
		}
	}

	private function write(string $path, string $contents): void
	{
		$dir = dirname($path);
		$this->mkdir($dir);
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

	private function removeTree(string $path): void
	{
		if ($path === '' || !is_dir($path)) {
			return;
		}
		$it = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ($it as $item) {
			$item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
		}
		rmdir($path);
	}

	private function assertSame(mixed $expected, mixed $actual, string $message): void
	{
		if ($expected !== $actual) {
			throw new RuntimeException($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . '.');
		}
	}

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (!str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' Missing `' . $needle . '`.');
		}
	}

	private function assertNotContains(string $needle, string $haystack, string $message): void
	{
		if (str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' Unexpected `' . $needle . '`.');
		}
	}
}

exit((new ScppAsyncCoreLanguageTest())->run());

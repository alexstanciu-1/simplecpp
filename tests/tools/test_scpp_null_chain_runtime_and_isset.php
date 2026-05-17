<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppNullChainRuntimeAndIssetTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_null_chain_' . getmypid() . '_' . bin2hex(random_bytes(4)));
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
			$this->assertCompactChainFailsCleanly();
			$this->assertIssetChainProbesSafely();
			echo "PASS: scpp null chain runtime and isset\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function assertCompactChainFailsCleanly(): void
	{
		$project = $this->root . '/compact_chain';
		$this->writeProject($project, <<<'PHS'
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

test_guard(null);
PHS);

		$build = scpp_run_build_service($project, $project . '/prism.json', ['compile_runtime' => true]);
		$this->assertSame(true, $build['ok'], 'compact chain repro should build');

		$run = scpp_run_binary_service($project, $project . '/.prism/build/main');
		$this->assertSame(1, $run['exit_code'], 'compact chain repro should fail with a runtime exception exit code');
		$this->assertContains("match=yes\n", $run['stdout'], 'compact chain repro should still print the first successful case');
		$this->assertContains('scpp::shared_p runtime error: operator->() requires a present shared pointer value.', $run['stderr'], 'compact chain repro should fail cleanly through shared_p runtime error');
	}

	private function assertIssetChainProbesSafely(): void
	{
		$project = $this->root . '/isset_chain';
		$this->writeProject($project, <<<'PHS'
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
	echo isset($root->child->name) ? "yes\n" : "no\n";
}

$full = new root_state();
$full->child = new child_state();
$full->child->name = "ok";
test_guard($full);

$missing_child = new root_state();
test_guard($missing_child);

test_guard(null);
PHS);

		$build = scpp_run_build_service($project, $project . '/prism.json', ['compile_runtime' => true]);
		$this->assertSame(true, $build['ok'], 'isset chain repro should build');

		$run = scpp_run_binary_service($project, $project . '/.prism/build/main');
		$this->assertSame(0, $run['exit_code'], 'isset chain repro should exit successfully');
		$this->assertSame("yes\nno\nno\n", $run['stdout'], 'isset chain repro should probe nullable paths safely');
		$this->assertSame('', $run['stderr'], 'isset chain repro should not print a runtime error');
	}

	private function writeProject(string $project, string $source): void
	{
		$this->mkdir($project . '/native_cpp');
		$this->write($project . '/main.phs', $source . "\n");
		$config = [
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
				'cxx' => null,
				'mode' => 'debug',
			],
			'runtime' => [
				'languages' => ['php'],
				'modules' => ['json', 'filesystem'],
				'language_profiles' => [
					'php' => ['profile' => 'strict'],
				],
			],
		];
		$json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		if (!is_string($json)) {
			throw new RuntimeException('Failed to encode prism.json');
		}
		$this->write($project . '/prism.json', $json . PHP_EOL);
	}

	private function write(string $path, string $contents): void
	{
		$dir = dirname($path);
		if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
			throw new RuntimeException('Failed to create directory ' . $dir);
		}
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
			throw new RuntimeException($message . ' expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
		}
	}

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (!str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' missing `' . $needle . '`');
		}
	}
}

exit((new ScppNullChainRuntimeAndIssetTest())->run());

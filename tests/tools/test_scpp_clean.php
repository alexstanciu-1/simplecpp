<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppCleanTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_clean_test_' . getmypid() . '_' . bin2hex(random_bytes(4)));
	}

	public function run(): int
	{
		try {
			$project = $this->root . '/app';
			$dependency = $this->root . '/lib';
			$this->writeProject($dependency, []);
			$this->writeProject($project, ['../lib']);
			$this->seedGeneratedState($project);
			$this->seedGeneratedState($dependency);

			$result = scpp_run_clean_service($project, $project . '/prism.json');
			$this->assertSame(true, $result['ok'], 'clean should succeed');
			$this->assertContains('Cleaning Prism++ generated state for 2 project(s)', $result['output'], 'clean should include dependency graph');
			$this->assertCleaned($project);
			$this->assertCleaned($dependency);
			$this->assertDirectoryMissing($project . '/.prism', 'standard root .prism workspace should be removed');
			$this->assertDirectoryMissing($dependency . '/.prism', 'standard dependency .prism workspace should be removed');
			$this->assertFileExists($project . '/prism.json', 'clean should preserve root config');
			$this->assertFileExists($dependency . '/prism.json', 'clean should preserve dependency config');
			$this->assertFileExists($project . '/main.phs', 'clean should preserve root source');
			$this->assertFileExists($dependency . '/main.phs', 'clean should preserve dependency source');

			$again = scpp_run_clean_service($project, $project . '/prism.json');
			$this->assertSame(true, $again['ok'], 'second clean should succeed');
			$this->assertContains('Already clean: .prism', $again['output'], 'second clean should treat missing dirs as already clean');

			$unsafe = $this->root . '/unsafe';
			$this->writeProject($unsafe, [], ['build_dir' => '.']);
			$unsafeResult = scpp_run_clean_service($unsafe, $unsafe . '/prism.json');
			$this->assertSame(false, $unsafeResult['ok'], 'unsafe clean target should fail');
			$this->assertContains('Refusing to clean unsafe project path', $unsafeResult['error'], 'unsafe failure should explain refusal');
			$this->assertFileExists($unsafe . '/prism.json', 'unsafe clean should preserve project config');

			echo "PASS: scpp clean\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	/** @param list<string> $dependencies @param array<string,string> $overrides */
	private function writeProject(string $path, array $dependencies, array $overrides = []): void
	{
		$this->mkdir($path);
		$this->write($path . '/main.phs', "echo \"ok\\n\";\n");
		$config = array_merge([
			'config_version' => 1,
			'project_name' => basename($path),
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'generated_dir' => '.prism/generated',
			'cache_dir' => '.prism/cache',
			'native_cpp_dir' => 'native_cpp',
			'dependencies' => $dependencies,
			'libraries' => [],
			'build' => [
				'backend' => 'ninja',
				'cxx' => null,
			],
		], $overrides);
		$json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		if (!is_string($json)) {
			throw new RuntimeException('Failed to encode project config.');
		}
		$this->write($path . '/prism.json', $json . PHP_EOL);
	}

	private function seedGeneratedState(string $path): void
	{
		$this->write($path . '/.prism/build/out.o', "object\n");
		$this->write($path . '/.prism/generated/main.cpp', "generated\n");
		$this->write($path . '/.prism/cache/s2s_state.php', "<?php\nreturn [];\n");
	}

	private function assertCleaned(string $path): void
	{
		$this->assertDirectoryMissing($path . '/.prism/build', 'build dir should be removed');
		$this->assertDirectoryMissing($path . '/.prism/generated', 'generated dir should be removed');
		$this->assertDirectoryMissing($path . '/.prism/cache', 'cache dir should be removed');
	}

	private function write(string $path, string $contents): void
	{
		$dir = dirname($path);
		if (!is_dir($dir)) {
			$this->mkdir($dir);
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
			throw new RuntimeException($message . ' missing `' . $needle . '` in `' . $haystack . '`');
		}
	}

	private function assertFileExists(string $path, string $message): void
	{
		if (!is_file($path)) {
			throw new RuntimeException($message . ': ' . $path);
		}
	}

	private function assertDirectoryMissing(string $path, string $message): void
	{
		if (is_dir($path)) {
			throw new RuntimeException($message . ': ' . $path);
		}
	}
}

exit((new ScppCleanTest())->run());

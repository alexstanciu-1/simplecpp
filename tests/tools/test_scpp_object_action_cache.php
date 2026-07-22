<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppObjectActionCacheTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_object_action_cache_' . getmypid() . '_' . bin2hex(random_bytes(4)));
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
			$this->assertObjectCacheRestoresPreviousActionOutput();
			echo "PASS: scpp object action cache\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function assertObjectCacheRestoresPreviousActionOutput(): void
	{
		$project = $this->root . '/app';
		$this->mkdir($project);
		$this->writeMain($project, 'cache A');
		$this->writeJson($project . '/prism.json', [
			'config_version' => 1,
			'project_name' => 'object_action_cache',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'generated_dir' => '.prism/generated',
			'cache_dir' => '.prism/cache',
			'build' => [
				'backend' => 'ninja',
				'cxx' => null,
				'mode' => 'debug',
				'object_cache' => true,
			],
			'runtime' => [
				'languages' => ['php'],
				'modules' => ['json', 'filesystem'],
				'language_profiles' => [
					'php' => ['profile' => 'strict'],
				],
			],
		]);

		$seedBuild = scpp_run_build_service($project, $project . '/prism.json', [
			'compile_runtime' => true,
			'disable_stan' => true,
		]);
		$this->assertSame(true, $seedBuild['ok'] ?? null, 'seed build should succeed');
		$seedDetails = $this->lastRunDetails($project);
		$seedCache = normalize_object_cache_report(is_array($seedDetails['object_cache'] ?? null) ? $seedDetails['object_cache'] : []);
		$this->assertSame(true, $seedCache['enabled'] ?? null, 'object cache should be enabled from build.object_cache');
		$seedRestore = is_array($seedCache['restore'] ?? null) ? $seedCache['restore'] : [];
		$seedStore = is_array($seedCache['store'] ?? null) ? $seedCache['store'] : [];
		$this->assertSame(1, $seedRestore['action_count'] ?? null, 'seed build should have one object action to restore');
		$this->assertSame(1, $seedRestore['miss_count'] ?? null, 'seed build should miss the empty object cache');
		$this->assertSame(1, $seedStore['stored_count'] ?? null, 'seed build should store the generated object');

		$this->writeMain($project, 'cache B');
		$changedBuild = scpp_run_build_service($project, $project . '/prism.json', [
			'disable_stan' => true,
		]);
		$this->assertSame(true, $changedBuild['ok'] ?? null, 'changed build should succeed');
		$this->assertRunOutput($project, "cache B\n");

		$this->writeMain($project, 'cache A');
		$restoredBuild = scpp_run_build_service($project, $project . '/prism.json', [
			'disable_stan' => true,
		]);
		$this->assertSame(true, $restoredBuild['ok'] ?? null, 'reverted build should succeed');
		$restoredDetails = $this->lastRunDetails($project);
		$restoredCache = normalize_object_cache_report(is_array($restoredDetails['object_cache'] ?? null) ? $restoredDetails['object_cache'] : []);
		$restore = is_array($restoredCache['restore'] ?? null) ? $restoredCache['restore'] : [];
		$store = is_array($restoredCache['store'] ?? null) ? $restoredCache['store'] : [];
		$this->assertSame(1, $restore['restored_count'] ?? null, 'reverted build should restore the prior A object from cache');
		$this->assertSame(1, $restore['hit_count'] ?? null, 'reverted build should count the object cache restore as a hit');
		$this->assertSame(1, $store['preserved_count'] ?? null, 'reverted build should preserve the already matching cache entry');
		$fanout = normalize_build_rebuild_fanout(is_array($restoredDetails['rebuild_fanout'] ?? null) ? $restoredDetails['rebuild_fanout'] : []);
		$this->assertSame(0, $fanout['rebuilt_generated_object_count'] ?? null, 'restored object should avoid a Ninja generated-object rebuild');
		$this->assertRunOutput($project, "cache A\n");

		$script = normalize_path(resolve_repo_root() . '/bin/scpp.php');
		$view = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build', 'object-cache'], [], 20.0);
		$this->assertSame(0, $view['exit_code'] ?? null, 'scpp explain-build object-cache should succeed');
		$this->assertContains('Object cache: enabled (build.object_cache)', $view['stdout'], 'object-cache view should show enabled policy');
		$this->assertContains('Object cache restore: actions 1, hits 1, restored 1', $view['stdout'], 'object-cache view should show restore hit counts');
	}

	private function writeMain(string $project, string $message): void
	{
		$this->write($project . '/main.phs', 'echo "' . $message . '\n";' . PHP_EOL);
	}

	/** @return array<string,mixed> */
	private function lastRunDetails(string $project): array
	{
		$lastRun = json_decode($this->read($project . '/.prism/last_run.json'), true);
		if (!is_array($lastRun)) {
			throw new RuntimeException('last_run.json should decode');
		}
		$details = is_array($lastRun['details'] ?? null) ? $lastRun['details'] : null;
		if (!is_array($details)) {
			throw new RuntimeException('last_run.json should contain details');
		}
		return $details;
	}

	private function assertRunOutput(string $project, string $expected): void
	{
		$result = scpp_run_optional_command($project, [$project . '/.prism/build/main'], [], 20.0);
		$this->assertSame(0, $result['exit_code'] ?? null, 'compiled executable should run');
		$this->assertSame($expected, $result['stdout'] ?? null, 'compiled executable output should match');
	}

	private function writeJson(string $path, array $data): void
	{
		$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		if (!is_string($json)) {
			throw new RuntimeException('Failed to encode JSON');
		}
		$this->write($path, $json . PHP_EOL);
	}

	private function mkdir(string $path): void
	{
		if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
			throw new RuntimeException('Failed to create directory: ' . $path);
		}
	}

	private function write(string $path, string $contents): void
	{
		$dir = dirname($path);
		if (!is_dir($dir)) {
			$this->mkdir($dir);
		}
		if (file_put_contents($path, $contents) === false) {
			throw new RuntimeException('Failed to write file: ' . $path);
		}
	}

	private function read(string $path): string
	{
		$contents = file_get_contents($path);
		if (!is_string($contents)) {
			throw new RuntimeException('Failed to read file: ' . $path);
		}
		return $contents;
	}

	private function removeTree(string $path): void
	{
		if (!is_dir($path)) {
			return;
		}
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ($iterator as $entry) {
			if (!$entry instanceof SplFileInfo) {
				continue;
			}
			if ($entry->isDir() && !$entry->isLink()) {
				@rmdir($entry->getPathname());
			} else {
				@unlink($entry->getPathname());
			}
		}
		@rmdir($path);
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
}

exit((new ScppObjectActionCacheTest())->run());

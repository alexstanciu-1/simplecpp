<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppBuildPlannerStateTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_build_planner_state_' . getmypid() . '_' . bin2hex(random_bytes(4)));
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
			$this->assertBuildPlannerStateReusesWarmSourceMetadata();
			echo "PASS: scpp build planner state\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function assertBuildPlannerStateReusesWarmSourceMetadata(): void
	{
		$project = $this->root . '/app';
		$this->mkdir($project);
		$this->write($project . '/helper.phs', $this->helperSource(7));
		$this->write($project . '/main.phs', "\$helper = new PlannerHelper();\necho \$helper->value();\necho \"\\n\";\n");
		$this->writeJson($project . '/prism.json', [
			'config_version' => 1,
			'project_name' => 'build_planner_state',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'generated_dir' => '.prism/generated',
			'cache_dir' => '.prism/cache',
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
		]);
		$this->waitUntilSourceTimestampsAreSettled($project);

		$firstBuild = scpp_run_build_service($project, $project . '/prism.json', [
			'compile_runtime' => true,
			'disable_stan' => true,
		]);
		$this->assertSame(true, $firstBuild['ok'] ?? null, 'seed build should succeed');
		$firstDetails = $this->lastRunDetails($project);
		$firstPlanner = normalize_build_planner_report(is_array($firstDetails['build_planner'] ?? null) ? $firstDetails['build_planner'] : []);
		$this->assertSame('new', $firstPlanner['status'] ?? null, 'first build planner state should be new');
		$this->assertSame('.prism/cache/build_planner_state.json', $firstPlanner['state_path'] ?? null, 'planner report should point to the state file');
		$this->assertSame(2, $firstPlanner['source_count'] ?? null, 'planner should record both project sources');
		$this->assertSame(0, $firstPlanner['source_metadata_hit_count'] ?? null, 'first build should have no source metadata hits');
		$this->assertSame(2, $firstPlanner['source_metadata_miss_count'] ?? null, 'first build should hash both source files');
		$this->assertSame(2, $firstPlanner['hash_read_count'] ?? null, 'first build should read both source hashes');
		$this->assertSame(0, $firstPlanner['reused_hash_count'] ?? null, 'first build should not reuse hashes');
		$this->assertFileExists($project . '/.prism/cache/build_planner_state.json', 'planner state file should be written');
		$state = $this->readJson($project . '/.prism/cache/build_planner_state.json');
		$this->assertSame(1, $state['schema_version'] ?? null, 'planner state should record schema version');
		$this->assertTrue((int) ($state['written_at'] ?? 0) > 0, 'planner state should record write time');
		$this->assertSame(2, count(is_array($state['source_metadata'] ?? null) ? $state['source_metadata'] : []), 'planner state should persist source metadata rows');

		$warmBuild = scpp_run_build_service($project, $project . '/prism.json', [
			'disable_stan' => true,
		]);
		$this->assertSame(true, $warmBuild['ok'] ?? null, 'warm build should succeed');
		$warmDetails = $this->lastRunDetails($project);
		$warmPlanner = normalize_build_planner_report(is_array($warmDetails['build_planner'] ?? null) ? $warmDetails['build_planner'] : []);
		$this->assertSame('hit', $warmPlanner['status'] ?? null, 'warm planner state should be a hit');
		$this->assertSame(2, $warmPlanner['source_count'] ?? null, 'warm planner report should record both sources');
		$this->assertSame(2, $warmPlanner['source_metadata_hit_count'] ?? null, 'warm build should reuse both source metadata rows');
		$this->assertSame(0, $warmPlanner['source_metadata_miss_count'] ?? null, 'warm build should avoid source metadata misses');
		$this->assertSame(0, $warmPlanner['hash_read_count'] ?? null, 'warm build should avoid source hash reads');
		$this->assertSame(2, $warmPlanner['reused_hash_count'] ?? null, 'warm build should reuse both source hashes');
		$warmExplanation = is_array($warmDetails['build_explanation'] ?? null) ? $warmDetails['build_explanation'] : [];
		$this->assertSame($warmPlanner, normalize_build_planner_report(is_array($warmExplanation['build_planner'] ?? null) ? $warmExplanation['build_planner'] : []), 'top-level and explanation planner reports should match');

		$script = normalize_path(resolve_repo_root() . '/bin/scpp.php');
		$defaultExplain = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build'], [], 20.0);
		$this->assertSame(0, $defaultExplain['exit_code'] ?? null, 'scpp explain-build should succeed');
		$this->assertContains('Build planner warm state: hit, source metadata hits 2/2, hash reads 0, hashes reused 2', $defaultExplain['stdout'], 'default explain-build should summarize planner reuse');
		$plannerView = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build', 'build-planner'], [], 20.0);
		$this->assertSame(0, $plannerView['exit_code'] ?? null, 'scpp explain-build build-planner should succeed');
		$this->assertContains('Build planner warm state: hit, state .prism/cache/build_planner_state.json', $plannerView['stdout'], 'planner view should show warm-state hit and path');
		$this->assertContains('Build planner graph: projects 1, sources 2, native sources 0, modules 0, STAN summary unavailable', $plannerView['stdout'], 'planner view should summarize graph inputs');
		$this->assertContains('Build planner source metadata: hits 2, misses 0, hash reads 0, hashes reused 2', $plannerView['stdout'], 'planner view should show source metadata reuse');

		$this->write($project . '/helper.phs', $this->helperSource(42));
		$changedBuild = scpp_run_build_service($project, $project . '/prism.json', [
			'disable_stan' => true,
		]);
		$this->assertSame(true, $changedBuild['ok'] ?? null, 'changed build should succeed');
		$changedDetails = $this->lastRunDetails($project);
		$changedPlanner = normalize_build_planner_report(is_array($changedDetails['build_planner'] ?? null) ? $changedDetails['build_planner'] : []);
		$this->assertSame('partial', $changedPlanner['status'] ?? null, 'one changed source should produce a partial planner hit');
		$this->assertSame(1, $changedPlanner['source_metadata_hit_count'] ?? null, 'unchanged source metadata should still hit');
		$this->assertSame(1, $changedPlanner['source_metadata_miss_count'] ?? null, 'changed source metadata should miss');
		$this->assertSame(1, $changedPlanner['hash_read_count'] ?? null, 'changed source should require one source hash read');
		$this->assertSame(1, $changedPlanner['reused_hash_count'] ?? null, 'unchanged source hash should be reused');
		$this->assertRunOutput($project, "42\n");
	}

	private function helperSource(int $value): string
	{
		return "class PlannerHelper {\n\tpublic function value(): int {\n\t\treturn " . $value . ";\n\t}\n}\n";
	}

	private function waitUntilSourceTimestampsAreSettled(string $project): void
	{
		$deadline = microtime(true) + 5.0;
		do {
			clearstatcache();
			$latest = 0;
			foreach ([$project . '/helper.phs', $project . '/main.phs'] as $source) {
				$mtime = filemtime($source);
				$stat = @stat($source);
				$ctime = is_array($stat) ? ($stat['ctime'] ?? 0) : 0;
				$latest = max($latest, (int) ($mtime === false ? 0 : $mtime), (int) $ctime);
			}
			if (time() > $latest) {
				return;
			}
			usleep(200000);
		} while (microtime(true) < $deadline);
		throw new RuntimeException('Timed out waiting for source timestamps to settle');
	}

	/** @return array<string,mixed> */
	private function lastRunDetails(string $project): array
	{
		$lastRun = $this->readJson($project . '/.prism/last_run.json');
		$details = is_array($lastRun['details'] ?? null) ? $lastRun['details'] : null;
		if (!is_array($details)) {
			throw new RuntimeException('last_run.json should contain details');
		}
		return $details;
	}

	/** @return array<string,mixed> */
	private function readJson(string $path): array
	{
		$data = json_decode($this->read($path), true);
		if (!is_array($data)) {
			throw new RuntimeException($path . ' should decode as JSON');
		}
		return $data;
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

	private function assertTrue(bool $condition, string $message): void
	{
		if (!$condition) {
			throw new RuntimeException($message);
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
}

exit((new ScppBuildPlannerStateTest())->run());

<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppBuildBenchmarkTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_build_benchmark_' . getmypid() . '_' . bin2hex(random_bytes(4)));
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
			$this->writeProject($project);
			$coreBefore = $this->read($project . '/core.phs');
			$mainBefore = $this->read($project . '/main.phs');
			$releaseBefore = $this->read($project . '/release_hot.phs');

			$script = normalize_path(resolve_repo_root() . '/bin/scpp.php');
			$benchmark = scpp_run_optional_command($project, [
				PHP_BINARY,
				$script,
				'build-benchmark',
				'--build-runtime',
				'--no-stan',
				'--private-source=core.phs',
				'--public-source=core.phs',
				'--coordinator-source=main.phs',
				'--release-source=release_hot.phs',
			], [], 180.0);
			$this->assertSame(0, $benchmark['exit_code'], "build-benchmark should succeed\nSTDOUT:\n" . $benchmark['stdout'] . "\nSTDERR:\n" . $benchmark['stderr']);
			$this->assertContains('Build benchmark: success', $benchmark['stdout'], 'build-benchmark summary should report success');
			$this->assertContains('warm_no_change: measured', $benchmark['stdout'], 'build-benchmark summary should include warm no-change');
			$this->assertContains('release_o3_hot_edit: measured', $benchmark['stdout'], 'build-benchmark summary should include release hot edit');

			$this->assertSame($coreBefore, $this->read($project . '/core.phs'), 'benchmark must not mutate the original core source');
			$this->assertSame($mainBefore, $this->read($project . '/main.phs'), 'benchmark must not mutate the original entrypoint source');
			$this->assertSame($releaseBefore, $this->read($project . '/release_hot.phs'), 'benchmark must not mutate the original release source');

			$report = json_decode($this->read($project . '/.prism/build_invalidation_benchmark.json'), true);
			if (!is_array($report)) {
				throw new RuntimeException('build benchmark report should decode as JSON');
			}
			$this->assertSame('build_invalidation_benchmark', $report['kind'] ?? null, 'report should identify its kind');
			$this->assertSame('success', $report['status'] ?? null, 'report should record success');
			$this->assertSame(5, $report['measured_scenario_count'] ?? null, 'report should measure all five scenarios');
			$this->assertSame(0, $report['skipped_scenario_count'] ?? null, 'report should not skip selected scenarios');
			$this->assertSame(0, $report['failed_scenario_count'] ?? null, 'report should not contain failed scenarios');
			$this->assertSame(false, $report['work_dir_retained'] ?? null, 'default benchmark should remove its work directory');
			$workDir = $project . '/' . (string) ($report['work_dir'] ?? '');
			$this->assertSame(false, is_dir($workDir), 'default benchmark work directory should be removed after report publication');

			$scenarios = $this->scenariosByName($report);
			foreach (['warm_no_change', 'private_body_edit', 'public_surface_edit', 'broad_coordinator_edit', 'release_o3_hot_edit'] as $name) {
				$scenario = $scenarios[$name] ?? null;
				if (!is_array($scenario)) {
					throw new RuntimeException('report should contain scenario ' . $name);
				}
				$this->assertSame('measured', $scenario['status'] ?? null, $name . ' should be measured');
				$metrics = is_array($scenario['metrics'] ?? null) ? $scenario['metrics'] : null;
				if (!is_array($metrics)) {
					throw new RuntimeException($name . ' should contain metrics');
				}
				$this->assertMetricShape($metrics, $name);
			}

			$warmFanout = is_array($scenarios['warm_no_change']['metrics']['object_fanout'] ?? null) ? $scenarios['warm_no_change']['metrics']['object_fanout'] : [];
			$this->assertSame(0, $warmFanout['rebuilt_object_count'] ?? null, 'warm no-change should rebuild no objects');
			$this->assertSame(true, $warmFanout['ninja_no_work'] ?? null, 'warm no-change should report Ninja no-work');

			$privateMutation = is_array($scenarios['private_body_edit']['mutation'] ?? null) ? $scenarios['private_body_edit']['mutation'] : [];
			$this->assertSame('private_body', $privateMutation['kind'] ?? null, 'private scenario should record mutation kind');
			$this->assertSame('marker_replacement', $privateMutation['strategy'] ?? null, 'private scenario should prefer the explicit marker');
			$this->assertSame('core.phs', $privateMutation['source_path'] ?? null, 'private scenario should record selected source');

			$publicMutation = is_array($scenarios['public_surface_edit']['mutation'] ?? null) ? $scenarios['public_surface_edit']['mutation'] : [];
			$this->assertSame('append_public_class', $publicMutation['strategy'] ?? null, 'public scenario should append a public class fallback');

			$coordinatorMutation = is_array($scenarios['broad_coordinator_edit']['mutation'] ?? null) ? $scenarios['broad_coordinator_edit']['mutation'] : [];
			$this->assertSame('append_empty_echo', $coordinatorMutation['strategy'] ?? null, 'coordinator scenario should append a no-output statement fallback');

			echo "PASS: scpp build benchmark\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function writeProject(string $projectRoot): void
	{
		$this->mkdir($projectRoot);
		$this->write($projectRoot . '/core.phs', <<<'PHS'
class CoreModel {
    public function label(): string {
        return "SCPP_BENCH_PRIVATE_EDIT_0";
    }
}
PHS);
		$this->write($projectRoot . '/release_hot.phs', <<<'PHS'
class ReleaseProbe {
    public function label(): string {
        return "SCPP_BENCH_RELEASE_EDIT_0";
    }
}
PHS);
		$this->write($projectRoot . '/main.phs', <<<'PHS'
$model = new CoreModel();
echo "ready\n";
PHS);
		$config = [
			'config_version' => 1,
			'project_name' => 'build_benchmark',
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
				'grouping_policy' => 'incremental',
			],
			'project_modules' => [
				[
					'name' => 'domain',
					'sources' => ['core.phs', 'release_hot.phs'],
					'dependencies' => [],
				],
				[
					'name' => 'app',
					'sources' => ['main.phs'],
					'dependencies' => ['domain'],
				],
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
		$this->write($projectRoot . '/prism.json', $json . PHP_EOL);
	}

	/** @param array<string,mixed> $report @return array<string,array<string,mixed>> */
	private function scenariosByName(array $report): array
	{
		$byName = [];
		foreach (is_array($report['scenarios'] ?? null) ? $report['scenarios'] : [] as $scenario) {
			if (is_array($scenario) && is_string($scenario['name'] ?? null)) {
				$byName[$scenario['name']] = $scenario;
			}
		}
		return $byName;
	}

	/** @param array<string,mixed> $metrics */
	private function assertMetricShape(array $metrics, string $label): void
	{
		$this->assertTrue(isset($metrics['wall_ms']) && is_int($metrics['wall_ms']), $label . ' should record wall time');
		$this->assertTrue(isset($metrics['ninja_subprocess_ms']) && is_int($metrics['ninja_subprocess_ms']), $label . ' should record Ninja time');
		$this->assertTrue(isset($metrics['transpiled_count']) && is_int($metrics['transpiled_count']), $label . ' should record transpiled count');
		$this->assertTrue(isset($metrics['reused_count']) && is_int($metrics['reused_count']), $label . ' should record reused count');
		$this->assertTrue(is_array($metrics['generated_artifact_writes'] ?? null), $label . ' should include generated artifact write counters');
		$this->assertTrue(is_array($metrics['object_fanout'] ?? null), $label . ' should include object fanout');
		$this->assertTrue(is_array($metrics['grouping_fanout'] ?? null), $label . ' should include grouping fanout');
		$this->assertTrue(is_array($metrics['module_cache_status_counts'] ?? null), $label . ' should include module cache counts');
		$ninjaExplain = is_array($metrics['ninja_explain'] ?? null) ? $metrics['ninja_explain'] : [];
		$this->assertSame(true, $ninjaExplain['enabled'] ?? null, $label . ' should run with Ninja explain probe enabled');
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
			throw new RuntimeException($message . ' expected ' . var_export($expected, true) . ' got ' . var_export($actual, true));
		}
	}

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (!str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' missing `' . $needle . '` in `' . $haystack . '`');
		}
	}

	private function assertTrue(bool $condition, string $message): void
	{
		if (!$condition) {
			throw new RuntimeException($message);
		}
	}
}

exit((new ScppBuildBenchmarkTest())->run());

<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppBuildGroupingAutoTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_build_grouping_auto_' . getmypid() . '_' . bin2hex(random_bytes(4)));
	}

	public function run(): int
	{
		try {
			$this->assertAutoReleaseBuildUsesNoEvidenceFolderGrouping();
			$this->assertAutoPriorEvidenceIsolatesVolatileSources();
			echo "PASS: scpp auto build grouping\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function assertAutoReleaseBuildUsesNoEvidenceFolderGrouping(): void
	{
		if (find_command_path(['ninja']) === null || resolve_compiler(['build' => []]) === null) {
			return;
		}
		$project = $this->root . '/auto_release_build_app';
		$this->mkdir($project . '/domain');
		$this->write($project . '/domain/base.phs', <<<'PHS'
class AutoBaseProbe {
    public function value(): int {
        return 1;
    }
}
PHS);
		$this->write($project . '/domain/child.phs', <<<'PHS'
class AutoChildProbe extends AutoBaseProbe {
    public function label(): string {
        return "child";
    }
}
PHS);
		$this->write($project . '/main.phs', <<<'PHS'
$child = new AutoChildProbe();
echo "auto grouped\n";
PHS);
		$this->writeConfig($project, [
			'config_version' => 1,
			'project_name' => 'auto_release_build_app',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'generated_dir' => '.prism/generated',
			'cache_dir' => '.prism/cache',
			'dependencies' => [],
			'libraries' => [],
			'build' => [
				'backend' => 'ninja',
				'cxx' => null,
				'mode' => 'release',
				'grouping_policy' => 'auto',
				'grouping_compile' => true,
			],
			'runtime' => [
				'languages' => ['php'],
				'modules' => ['json', 'filesystem'],
				'language_profiles' => [
					'php' => ['profile' => 'strict'],
				],
			],
		]);

		$build = scpp_run_build_service($project, $project . '/prism.json', [
			'compile_runtime' => true,
			'disable_stan' => true,
			'build_mode' => 'release',
		]);
		$this->assertSame(true, $build['ok'], "auto grouped release project should build\nSTDOUT:\n" . (string) ($build['output'] ?? '') . "\nSTDERR:\n" . (string) ($build['error'] ?? ''));
		$result = is_array($build['result'] ?? null) ? $build['result'] : [];
		$buildDir = normalize_path((string) ($result['build_dir'] ?? $project . '/.prism/build/release'));
		$buildNinja = $this->read($buildDir . '/build.ninja');
		$this->assertContains('build __build_groups/', $buildNinja, 'auto release build should contain a grouped generated object edge');
		$this->assertContains(': compile ../../generated/release/__build_groups/', $buildNinja, 'auto release grouped edge should compile a grouped generated source');
		$this->assertNotContains('build domain/base.o: compile ../../generated/release/domain/base.cpp', $buildNinja, 'auto grouped base should not compile as an isolated release object');
		$this->assertNotContains('build domain/child.o: compile ../../generated/release/domain/child.cpp', $buildNinja, 'auto grouped child should not compile as an isolated release object');

		$lastRun = json_decode($this->read($project . '/.prism/last_run.json'), true);
		if (!is_array($lastRun)) {
			throw new RuntimeException('last_run.json should decode');
		}
		$details = is_array($lastRun['details'] ?? null) ? $lastRun['details'] : [];
		$explanation = is_array($details['build_explanation'] ?? null) ? $details['build_explanation'] : [];
		$grouping = is_array($explanation['build_grouping'] ?? null) ? $explanation['build_grouping'] : [];
		$this->assertSame('auto', $grouping['policy'] ?? null, 'saved grouping report should preserve auto policy');
		$this->assertSame('active_generated_edges', $grouping['status'] ?? null, 'auto grouping_compile should activate generated edges in release mode');
		$this->assertSame('release_grouped_generated_objects', $grouping['compile_unit_strategy'] ?? null, 'auto release compile strategy should use release grouped generated objects');
		$evidence = is_array($grouping['auto_evidence'] ?? null) ? $grouping['auto_evidence'] : [];
		$this->assertSame('none', $evidence['source'] ?? null, 'first auto build should record missing prior evidence');
		$this->assertSame('folder', $evidence['selected_policy'] ?? null, 'first auto release build should start with folder grouping');
		$decisions = $this->autoDecisionsBySource($grouping);
		$this->assertSame('grouped', $decisions['domain/base.phs']['decision'] ?? null, 'auto should group stable base source');
		$this->assertSame('folder:root:domain', $decisions['domain/base.phs']['group_id'] ?? null, 'auto should group base by folder');
		$this->assertSame('grouped', $decisions['domain/child.phs']['decision'] ?? null, 'auto should group stable child source');
		$this->assertSame('isolated', $decisions['main.phs']['decision'] ?? null, 'auto should keep entrypoint isolated');

		$lines = render_build_grouping_lines($grouping, true);
		$this->assertContains('Build grouping auto: selected folder, evidence none', implode("\n", $lines), 'grouping view should explain selected auto policy');
		$this->assertContains('domain/base.phs: grouped folder:root:domain', implode("\n", $lines), 'grouping view should list auto source decisions');
	}

	private function assertAutoPriorEvidenceIsolatesVolatileSources(): void
	{
		$project = $this->root . '/auto_evidence_app';
		$repoRoot = $this->root . '/repo';
		$buildDir = $project . '/.prism/build/release';
		$generatedDir = $project . '/.prism/generated/release';
		$this->mkdir($buildDir . '/domain');
		$this->mkdir($generatedDir . '/domain');
		$this->mkdir($repoRoot . '/runtime/include');
		foreach (['hot', 'heavy', 'stable_a', 'stable_b'] as $name) {
			$this->write($generatedDir . '/domain/' . $name . '.cpp', 'void auto_' . $name . "_probe() {}\n");
		}
		$this->write($generatedDir . '/main.cpp', "int main() { return 0; }\n");
		$this->write($generatedDir . '/__project_units/broad.hpp', "// broad\n");
		$this->write($buildDir . '/domain/heavy.o', str_repeat('0', (8 * 1024 * 1024) + 1));
		$this->writePreviousLastRun($project);

		$policy = resolve_build_grouping_policy([
			'build' => [
				'mode' => 'release',
				'grouping_policy' => 'auto',
				'grouping_compile' => true,
			],
		], 'release');
		$generatedUnits = [
			$this->generatedUnit($project, $generatedDir, $buildDir, 'domain/hot.phs', false),
			$this->generatedUnit($project, $generatedDir, $buildDir, 'domain/heavy.phs', false),
			$this->generatedUnit($project, $generatedDir, $buildDir, 'domain/stable_a.phs', false),
			$this->generatedUnit($project, $generatedDir, $buildDir, 'domain/stable_b.phs', false),
			$this->generatedUnit($project, $generatedDir, $buildDir, 'main.phs', true),
		];
		$policy = apply_auto_build_grouping_evidence($project, $policy, $generatedUnits);
		$evidence = is_array($policy['auto_evidence'] ?? null) ? $policy['auto_evidence'] : [];
		$this->assertSame('.prism/last_run.json', $evidence['source'] ?? null, 'auto should use previous last_run evidence');
		$this->assertSame('folder', $evidence['selected_policy'] ?? null, 'narrow prior fanout should select folder grouping');
		$decisions = $this->autoDecisionsBySource($policy);
		$this->assertSame('isolated', $decisions['domain/hot.phs']['decision'] ?? null, 'previously rebuilt source should be isolated');
		$this->assertSame('previous build changed this source generated artifacts', $decisions['domain/hot.phs']['reason'] ?? null, 'volatile source should carry generated-artifact reason');
		$this->assertSame('isolated', $decisions['domain/heavy.phs']['decision'] ?? null, 'large previous object should be isolated');
		$this->assertSame('previous object size is at least 8 MiB', $decisions['domain/heavy.phs']['reason'] ?? null, 'large source should carry object-size reason');
		$this->assertSame('grouped', $decisions['domain/stable_a.phs']['decision'] ?? null, 'stable source should remain grouped');
		$this->assertSame('folder:root:domain', $decisions['domain/stable_a.phs']['group_id'] ?? null, 'stable source should use folder group');

		$sourceRows = [
			['project_root' => $project, 'path' => 'domain/hot.phs', 'object_path' => '.prism/build/release/domain/hot.o'],
			['project_root' => $project, 'path' => 'domain/heavy.phs', 'object_path' => '.prism/build/release/domain/heavy.o'],
			['project_root' => $project, 'path' => 'domain/stable_a.phs', 'object_path' => '.prism/build/release/domain/stable_a.o'],
			['project_root' => $project, 'path' => 'domain/stable_b.phs', 'object_path' => '.prism/build/release/domain/stable_b.o'],
			['project_root' => $project, 'path' => 'main.phs', 'object_path' => '.prism/build/release/main.o'],
		];
		apply_grouped_generated_object_edges($project, $buildDir, $generatedDir, $policy, $generatedUnits, $sourceRows, 'gnu_like');
		$stableGroupObject = normalize_path((string) ($generatedUnits[2]['object_path'] ?? ''));
		$this->assertSame($stableGroupObject, normalize_path((string) ($generatedUnits[3]['object_path'] ?? '')), 'stable auto sources should share one grouped object');
		$this->assertContains('/.prism/build/release/__build_groups/', $stableGroupObject, 'stable auto group should use grouped object path');
		$this->assertSame($buildDir . '/domain/hot.o', normalize_path((string) ($generatedUnits[0]['object_path'] ?? '')), 'volatile source should stay per-source');
		$this->assertSame($buildDir . '/domain/heavy.o', normalize_path((string) ($generatedUnits[1]['object_path'] ?? '')), 'large source should stay per-source');

		$report = collect_build_grouping_report($project, $policy, $generatedUnits, [], [
			'rebuilt_object_count' => 1,
			'rebuilt_generated_object_count' => 1,
			'rebuilt_native_object_count' => 0,
			'rebuilt_generated_objects' => [normalize_config_path(relative_path($project, $stableGroupObject))],
		]);
		$groups = $this->groupsById($report);
		$this->assertSame('auto_group', $groups['folder:root:domain']['kind'] ?? null, 'auto grouped report row should carry auto_group kind');
		$this->assertSame(1, $report['changed_group_count'] ?? null, 'auto grouped object rebuild should mark one changed group');
	}

	/** @return array<string,mixed> */
	private function generatedUnit(string $project, string $generatedDir, string $buildDir, string $source, bool $entrypoint): array
	{
		$base = preg_replace('/\.phs$/', '', $source);
		if (!is_string($base)) {
			$base = $source;
		}
		return [
			'project_root' => $project,
			'relative_php' => $source,
			'generated_cpp' => $generatedDir . '/' . $base . '.cpp',
			'object_path' => $buildDir . '/' . $base . '.o',
			'is_entrypoint' => $entrypoint,
			'force_include_header' => $generatedDir . '/__project_units/broad.hpp',
		];
	}

	private function writePreviousLastRun(string $project): void
	{
		$this->writeJson($project . '/.prism/last_run.json', [
			'details' => [
				'timing_breakdown_ms' => [
					'ninja_subprocess_ms' => 42,
				],
				'build_explanation' => [
					'rebuild_fanout' => [
						'rebuilt_object_count' => 1,
						'rebuilt_generated_object_count' => 1,
					],
					'build_grouping' => [
						'changed_group_count' => 1,
					],
					'sources' => [
						[
							'path' => 'domain/hot.phs',
							'object_path' => '.prism/build/release/domain/hot.o',
							'object_rebuilt' => true,
							'generated_artifacts' => [
								'interface_changed' => false,
								'implementation_changed' => true,
								'interface_first_recorded' => false,
								'implementation_first_recorded' => false,
							],
						],
						[
							'path' => 'domain/heavy.phs',
							'object_path' => '.prism/build/release/domain/heavy.o',
							'object_rebuilt' => false,
						],
						[
							'path' => 'domain/stable_a.phs',
							'object_path' => '.prism/build/release/domain/stable_a.o',
							'object_rebuilt' => false,
						],
						[
							'path' => 'domain/stable_b.phs',
							'object_path' => '.prism/build/release/domain/stable_b.o',
							'object_rebuilt' => false,
						],
					],
				],
			],
		]);
	}

	/** @param array<string,mixed> $report @return array<string,array<string,mixed>> */
	private function autoDecisionsBySource(array $report): array
	{
		$rows = [];
		foreach (is_array($report['auto_source_decisions'] ?? null) ? $report['auto_source_decisions'] : [] as $decision) {
			if (!is_array($decision)) {
				continue;
			}
			$rows[(string) ($decision['source'] ?? '')] = $decision;
		}
		return $rows;
	}

	/** @param array<string,mixed> $report @return array<string,array<string,mixed>> */
	private function groupsById(array $report): array
	{
		$rows = [];
		foreach (is_array($report['groups'] ?? null) ? $report['groups'] : [] as $group) {
			if (!is_array($group)) {
				continue;
			}
			$rows[(string) ($group['id'] ?? '')] = $group;
		}
		return $rows;
	}

	/** @param array<string,mixed> $config */
	private function writeConfig(string $project, array $config): void
	{
		$this->writeJson($project . '/prism.json', $config);
	}

	/** @param array<string,mixed> $data */
	private function writeJson(string $path, array $data): void
	{
		$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		if (!is_string($json)) {
			throw new RuntimeException('Failed to encode JSON for ' . $path);
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

	private function assertNotContains(string $needle, string $haystack, string $message): void
	{
		if (str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' contained `' . $needle . '` in `' . $haystack . '`');
		}
	}
}

exit((new ScppBuildGroupingAutoTest())->run());

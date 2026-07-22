<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppBuildGroupingManualTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_build_grouping_manual_' . getmypid() . '_' . bin2hex(random_bytes(4)));
	}

	public function run(): int
	{
		try {
			$this->assertManualGroupingReport();
			$this->assertManualGroupedGeneratedObjectEdges();
			if (find_command_path(['ninja']) !== null && resolve_compiler(['build' => []]) !== null) {
				$this->assertManualGroupedGeneratedProjectBuilds();
			}
			$this->assertDuplicateManualSourceFails();
			$this->assertUnknownManualSourceFails();
			$this->assertPathEscapeFails();
			$this->assertMissingManualMapFails();
			$this->assertGroupingCompileRequiresManualPolicy();
			echo "PASS: scpp manual build grouping\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function assertManualGroupingReport(): void
	{
		$project = $this->root . '/app';
		$this->mkdir($project . '/native_cpp');
		$policy = resolve_build_grouping_policy([
			'build' => [
				'grouping_policy' => 'manual',
				'grouping' => [
					'domain' => ['child.phs', 'base.phs'],
					'native-tools' => ['native_cpp/policy_probe.cpp'],
				],
			],
		], 'debug');
		$this->assertSame('manual', $policy['policy'] ?? null, 'manual policy should resolve');
		$this->assertSame(2, count(is_array($policy['manual_groups'] ?? null) ? $policy['manual_groups'] : []), 'manual policy should preserve configured groups');

		$generatedUnits = [
			[
				'project_root' => $project,
				'relative_php' => 'base.phs',
				'generated_cpp' => $project . '/.prism/generated/base.cpp',
				'object_path' => $project . '/.prism/build/base.o',
				'is_entrypoint' => false,
				'force_include_header' => null,
			],
			[
				'project_root' => $project,
				'relative_php' => 'child.phs',
				'generated_cpp' => $project . '/.prism/generated/child.cpp',
				'object_path' => $project . '/.prism/build/child.o',
				'is_entrypoint' => false,
				'force_include_header' => null,
			],
			[
				'project_root' => $project,
				'relative_php' => 'main.phs',
				'generated_cpp' => $project . '/.prism/generated/main.cpp',
				'object_path' => $project . '/.prism/build/main.o',
				'is_entrypoint' => true,
				'force_include_header' => null,
			],
		];
		$nativeUnits = [
			[
				'project_root' => $project,
				'source_path' => $project . '/native_cpp/policy_probe.cpp',
				'object_path' => $project . '/.prism/build/native_cpp/policy_probe.o',
				'force_include_header' => null,
			],
		];
		$report = collect_build_grouping_report($project, $policy, $generatedUnits, $nativeUnits, [
			'rebuilt_object_count' => 1,
			'rebuilt_generated_object_count' => 1,
			'rebuilt_native_object_count' => 0,
			'rebuilt_generated_objects' => ['.prism/build/base.o'],
		]);

		$this->assertSame('manual', $report['policy'] ?? null, 'report should preserve manual policy');
		$this->assertSame(2, $report['manual_group_count'] ?? null, 'report should count manual groups');
		$this->assertSame(3, $report['manual_assigned_source_count'] ?? null, 'report should count assigned manual sources');
		$this->assertSame(1, $report['manual_unassigned_source_count'] ?? null, 'report should count unassigned root sources');
		$this->assertSame(['main.phs'], $report['manual_unassigned_sources'] ?? null, 'report should list unassigned root source deterministically');
		$this->assertSame(3, $report['total_groups'] ?? null, 'manual grouping should produce two manual groups plus one isolated unassigned source');
		$this->assertSame(1, $report['changed_group_count'] ?? null, 'rebuilt source should mark its manual group changed');
		$this->assertSame(['manual:root:domain'], $report['changed_groups'] ?? null, 'changed groups should name the manual group');

		$groups = $this->groupsById($report);
		$domain = $groups['manual:root:domain'] ?? null;
		if (!is_array($domain)) {
			throw new RuntimeException('manual domain group should be present');
		}
		$this->assertSame('manual', $domain['kind'] ?? null, 'manual domain group should carry manual kind');
		$this->assertSame(['base.phs', 'child.phs'], $domain['generated_sources'] ?? null, 'manual domain group should include configured generated sources');
		$this->assertSame(2, $domain['generated_source_count'] ?? null, 'manual domain group should count generated sources');
		$this->assertSame(1, $domain['rebuilt_object_count'] ?? null, 'manual domain group should record rebuilt object fanout');

		$native = $groups['manual:root:native-tools'] ?? null;
		if (!is_array($native)) {
			throw new RuntimeException('manual native group should be present');
		}
		$this->assertSame(['native_cpp/policy_probe.cpp'], $native['native_sources'] ?? null, 'manual native group should include configured native source');

		$main = $groups['source:root:generated:main.phs'] ?? null;
		if (!is_array($main)) {
			throw new RuntimeException('unassigned main source should stay isolated');
		}
		$this->assertSame('generated_source', $main['kind'] ?? null, 'unassigned generated source should keep source grouping');

		$lines = implode("\n", render_build_grouping_lines($report, true));
		$this->assertContains('Build grouping: manual (build.grouping_policy, report-only)', $lines, 'rendered grouping should identify manual policy');
		$this->assertContains('Build grouping manual map: groups 2, assigned sources 3, unassigned root sources 1', $lines, 'rendered grouping should summarize manual map');
		$this->assertContains('manual:root:domain', $lines, 'rendered grouping should list manual domain group');
		$this->assertContains('sources: base.phs, child.phs', $lines, 'rendered grouping should list manual group sources');
	}

	private function assertManualGroupedGeneratedObjectEdges(): void
	{
		$project = $this->root . '/grouped_app';
		$repoRoot = $this->root . '/repo';
		$buildDir = $project . '/.prism/build';
		$generatedDir = $project . '/.prism/generated';
		$this->mkdir($buildDir);
		$this->mkdir($generatedDir);
		$this->mkdir($repoRoot . '/runtime/include');
		$this->write($generatedDir . '/base.cpp', "void base_probe() {}\n");
		$this->write($generatedDir . '/child.cpp', "void child_probe() {}\n");
		$this->write($generatedDir . '/main.cpp', "int main() { return 0; }\n");
		$this->write($generatedDir . '/__project_units/broad.hpp', "// broad\n");

		$policy = resolve_build_grouping_policy([
			'build' => [
				'grouping_policy' => 'manual',
				'grouping_compile' => true,
				'grouping' => [
					'domain' => ['base.phs', 'child.phs'],
				],
			],
		], 'debug');
		$this->assertSame('active_generated_edges', $policy['status'] ?? null, 'manual grouping_compile should activate generated edges');
		$this->assertSame('manual_grouped_generated_objects', $policy['compile_unit_strategy'] ?? null, 'manual grouping_compile should report grouped generated strategy');
		$this->assertSame(true, $policy['generated_grouping_enabled'] ?? null, 'manual grouping_compile should set generated grouping flag');

		$generatedUnits = [
			[
				'project_root' => $project,
				'relative_php' => 'base.phs',
				'generated_cpp' => $generatedDir . '/base.cpp',
				'object_path' => $buildDir . '/base.o',
				'is_entrypoint' => false,
				'force_include_header' => $generatedDir . '/__project_units/broad.hpp',
			],
			[
				'project_root' => $project,
				'relative_php' => 'child.phs',
				'generated_cpp' => $generatedDir . '/child.cpp',
				'object_path' => $buildDir . '/child.o',
				'is_entrypoint' => false,
				'force_include_header' => $generatedDir . '/__project_units/broad.hpp',
			],
			[
				'project_root' => $project,
				'relative_php' => 'main.phs',
				'generated_cpp' => $generatedDir . '/main.cpp',
				'object_path' => $buildDir . '/main.o',
				'is_entrypoint' => true,
				'force_include_header' => $generatedDir . '/__project_units/broad.hpp',
			],
		];
		$sourceRows = [
			[
				'project_root' => $project,
				'path' => 'base.phs',
				'generated_cpp' => '.prism/generated/base.cpp',
				'object_path' => '.prism/build/base.o',
				'action' => 'transpiled',
				'reasons' => ['test'],
			],
			[
				'project_root' => $project,
				'path' => 'child.phs',
				'generated_cpp' => '.prism/generated/child.cpp',
				'object_path' => '.prism/build/child.o',
				'action' => 'transpiled',
				'reasons' => ['test'],
			],
			[
				'project_root' => $project,
				'path' => 'main.phs',
				'generated_cpp' => '.prism/generated/main.cpp',
				'object_path' => '.prism/build/main.o',
				'action' => 'transpiled',
				'reasons' => ['test'],
			],
		];
		apply_grouped_generated_object_edges($project, $buildDir, $generatedDir, $policy, $generatedUnits, $sourceRows, 'gnu_like');

		$groupObject = normalize_path((string) ($generatedUnits[0]['object_path'] ?? ''));
		$this->assertSame($groupObject, normalize_path((string) ($generatedUnits[1]['object_path'] ?? '')), 'manual group members should share one generated object path');
		$this->assertContains('/.prism/build/__build_groups/', $groupObject, 'group object should live under build __build_groups');
		$this->assertSame($buildDir . '/main.o', normalize_path((string) ($generatedUnits[2]['object_path'] ?? '')), 'unassigned entrypoint should keep its per-source object');
		$groupSource = normalize_path((string) ($generatedUnits[0]['compile_source_path'] ?? ''));
		$this->assertContains('/.prism/generated/__build_groups/', $groupSource, 'group compile source should live under generated __build_groups');
		$groupSourceContents = $this->read($groupSource);
		$this->assertContains('#include "../base.cpp"', $groupSourceContents, 'group compile source should include base generated source');
		$this->assertContains('#include "../child.cpp"', $groupSourceContents, 'group compile source should include child generated source');
		$this->assertSame(normalize_config_path(relative_path($project, $groupObject)), $sourceRows[0]['object_path'] ?? null, 'source rows should use grouped object path');
		$this->assertSame(normalize_config_path(relative_path($project, $groupObject)), $sourceRows[1]['object_path'] ?? null, 'source rows should use grouped object path for all grouped sources');
		$this->assertSame('.prism/build/main.o', $sourceRows[2]['object_path'] ?? null, 'unassigned source row should keep per-source object path');

		$compiler = [
			'command' => 'g++',
			'kind' => 'gnu_like',
			'launcher' => null,
			'linker_flags' => [],
		];
		$runtimeConfig = [
			'languages' => ['php'],
			'modules' => ['json', 'filesystem'],
			'language_profiles' => [
				'php' => ['profile' => 'strict'],
			],
		];
		$ninja = render_build_ninja(
			$project,
			$repoRoot,
			$buildDir,
			$generatedDir,
			$generatedUnits,
			[],
			'app',
			$compiler,
			'debug',
			$runtimeConfig,
			[],
			null,
			['compile_runtime' => false, 'compile_dependencies' => true, 'use_pch' => false],
			'reuse',
			[]
		);
		$groupObjectBuildRelative = normalize_config_path(relative_path($buildDir, $groupObject));
		$this->assertContains('build ' . $groupObjectBuildRelative . ': compile ../generated/__build_groups/', $ninja, 'Ninja should compile the grouped generated object once');
		$this->assertNotContains('build base.o: compile ../generated/base.cpp', $ninja, 'grouped base source should not emit a per-source compile edge');
		$this->assertNotContains('build child.o: compile ../generated/child.cpp', $ninja, 'grouped child source should not emit a per-source compile edge');
		$this->assertContains('build main.o: compile ../generated/main.cpp', $ninja, 'unassigned main source should still emit a per-source compile edge');
		$linkLine = $this->findLineStartingWith($ninja, 'build app: link ');
		$this->assertSame(1, substr_count($linkLine, $groupObjectBuildRelative), 'link line should include grouped object once');
		$this->assertSame(0, substr_count($linkLine, 'base.o'), 'link line should not include the old base object');
		$this->assertSame(0, substr_count($linkLine, 'child.o'), 'link line should not include the old child object');

		$report = collect_build_grouping_report($project, $policy, $generatedUnits, [], [
			'rebuilt_object_count' => 1,
			'rebuilt_generated_object_count' => 1,
			'rebuilt_native_object_count' => 0,
			'rebuilt_generated_objects' => [normalize_config_path(relative_path($project, $groupObject))],
		]);
		$groups = $this->groupsById($report);
		$domain = $groups['manual:root:domain'] ?? null;
		if (!is_array($domain)) {
			throw new RuntimeException('grouped domain report should be present');
		}
		$this->assertSame([normalize_config_path(relative_path($project, $groupObject))], $domain['objects'] ?? null, 'group report should list the grouped object once');
		$this->assertSame(1, $domain['rebuilt_object_count'] ?? null, 'group report should count the grouped object once');
		$this->assertSame(1, $report['changed_group_count'] ?? null, 'grouped object rebuild should mark one changed group');
		$this->assertSame([
			[
				'group_id' => 'manual:root:domain',
				'reasons' => ['rebuilt object ' . normalize_config_path(relative_path($project, $groupObject))],
			],
		], $report['changed_group_reasons'] ?? null, 'manual grouping report should expose changed group reasons');
	}

	private function assertManualGroupedGeneratedProjectBuilds(): void
	{
		$project = $this->root . '/grouped_build_app';
		$this->mkdir($project);
		$this->write($project . '/base.phs', <<<'PHS'
class BaseProbe {
    public function value(): int {
        return 1;
    }
}
PHS);
		$this->write($project . '/child.phs', <<<'PHS'
class ChildProbe extends BaseProbe {
    public function label(): string {
        return "child";
    }
}
PHS);
		$this->write($project . '/main.phs', <<<'PHS'
$child = new ChildProbe();
echo "grouped\n";
PHS);
		$config = [
			'config_version' => 1,
			'project_name' => 'grouped_build_app',
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
				'grouping_policy' => 'manual',
				'grouping_compile' => true,
				'grouping' => [
					'domain' => ['base.phs', 'child.phs'],
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
		$this->write($project . '/prism.json', $json . PHP_EOL);
		$build = scpp_run_build_service($project, $project . '/prism.json', [
			'compile_runtime' => true,
			'disable_stan' => true,
		]);
		$this->assertSame(true, $build['ok'], "manual grouped generated project should build\nSTDOUT:\n" . (string) ($build['output'] ?? '') . "\nSTDERR:\n" . (string) ($build['error'] ?? ''));
		$buildNinja = $this->read($project . '/.prism/build/build.ninja');
		$this->assertContains('build __build_groups/', $buildNinja, 'build.ninja should contain a grouped generated object edge');
		$this->assertContains(': compile ../generated/__build_groups/', $buildNinja, 'grouped object edge should compile a grouped generated source');
		$this->assertNotContains('build base.o: compile ../generated/base.cpp', $buildNinja, 'base should not compile as an isolated object when grouped');
		$this->assertNotContains('build child.o: compile ../generated/child.cpp', $buildNinja, 'child should not compile as an isolated object when grouped');
		$lastRun = json_decode($this->read($project . '/.prism/last_run.json'), true);
		if (!is_array($lastRun)) {
			throw new RuntimeException('last_run.json should decode');
		}
		$details = is_array($lastRun['details'] ?? null) ? $lastRun['details'] : [];
		$explanation = is_array($details['build_explanation'] ?? null) ? $details['build_explanation'] : [];
		$grouping = is_array($explanation['build_grouping'] ?? null) ? $explanation['build_grouping'] : [];
		$this->assertSame('active_generated_edges', $grouping['status'] ?? null, 'saved grouping report should record active generated edges');
		$this->assertSame('manual_grouped_generated_objects', $grouping['compile_unit_strategy'] ?? null, 'saved grouping report should record grouped generated strategy');
		$this->assertSame(true, $grouping['generated_grouping_enabled'] ?? null, 'saved grouping report should expose generated grouping flag');
	}

	private function assertDuplicateManualSourceFails(): void
	{
		$this->assertFails(static function (): void {
			resolve_build_grouping_policy([
				'build' => [
					'grouping_policy' => 'manual',
					'grouping' => [
						'one' => ['base.phs'],
						'two' => ['base.phs'],
					],
				],
			], 'debug');
		}, 'Duplicate manual build grouping source `base.phs`');
	}

	private function assertUnknownManualSourceFails(): void
	{
		$project = $this->root . '/unknown_app';
		$this->mkdir($project);
		$policy = resolve_build_grouping_policy([
			'build' => [
				'grouping_policy' => 'manual',
				'grouping' => [
					'bad' => ['missing.phs'],
				],
			],
		], 'debug');
		$this->assertFails(static function () use ($project, $policy): void {
			collect_build_grouping_report($project, $policy, [
				[
					'project_root' => $project,
					'relative_php' => 'main.phs',
					'generated_cpp' => $project . '/.prism/generated/main.cpp',
					'object_path' => $project . '/.prism/build/main.o',
					'is_entrypoint' => true,
					'force_include_header' => null,
				],
			], [], []);
		}, 'manual source `missing.phs` is not a generated or native source');
	}

	private function assertPathEscapeFails(): void
	{
		$this->assertFails(static function (): void {
			resolve_build_grouping_policy([
				'build' => [
					'grouping_policy' => 'manual',
					'grouping' => [
						'bad' => ['../outside.phs'],
					],
				],
			], 'debug');
		}, 'source path escapes the project root');
	}

	private function assertMissingManualMapFails(): void
	{
		$this->assertFails(static function (): void {
			resolve_build_grouping_policy([
				'build' => [
					'grouping_policy' => 'manual',
				],
			], 'debug');
		}, 'manual grouping requires an object mapping group names to source lists');
	}

	private function assertGroupingCompileRequiresManualPolicy(): void
	{
		$this->assertFails(static function (): void {
			resolve_build_grouping_policy([
				'build' => [
					'grouping_policy' => 'folder',
					'grouping_compile' => true,
				],
			], 'debug');
		}, 'grouped generated object edges currently require build.grouping_policy = "manual"');
	}

	/** @param array<string,mixed> $report @return array<string,array<string,mixed>> */
	private function groupsById(array $report): array
	{
		$groups = [];
		foreach (is_array($report['groups'] ?? null) ? $report['groups'] : [] as $group) {
			if (is_array($group) && is_string($group['id'] ?? null)) {
				$groups[$group['id']] = $group;
			}
		}
		return $groups;
	}

	private function assertFails(callable $callback, string $needle): void
	{
		try {
			$callback();
		} catch (ScppCliException $exception) {
			$this->assertContains($needle, $exception->getMessage(), 'failure should include expected diagnostic');
			return;
		}
		throw new RuntimeException('Expected ScppCliException containing `' . $needle . '`');
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

	private function findLineStartingWith(string $text, string $prefix): string
	{
		foreach (preg_split('/\R/', $text) ?: [] as $line) {
			if (str_starts_with((string) $line, $prefix)) {
				return (string) $line;
			}
		}
		throw new RuntimeException('Missing line starting with `' . $prefix . '`');
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

	private function assertNotContains(string $needle, string $haystack, string $message): void
	{
		if (str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' contained `' . $needle . '` in `' . $haystack . '`');
		}
	}
}

exit((new ScppBuildGroupingManualTest())->run());

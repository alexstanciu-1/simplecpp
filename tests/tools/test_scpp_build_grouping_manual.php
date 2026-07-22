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
			$this->assertDuplicateManualSourceFails();
			$this->assertUnknownManualSourceFails();
			$this->assertPathEscapeFails();
			$this->assertMissingManualMapFails();
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
}

exit((new ScppBuildGroupingManualTest())->run());

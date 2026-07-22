<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppBuildGroupingModuleTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_build_grouping_module_' . getmypid() . '_' . bin2hex(random_bytes(4)));
	}

	public function run(): int
	{
		try {
			$this->assertModuleGroupedGeneratedObjectEdges();
			if (find_command_path(['ninja']) !== null && resolve_compiler(['build' => []]) !== null) {
				$this->assertModuleGroupedProjectBuilds();
			}
			$this->assertModuleGroupingCompileRequiresReleaseMode();
			echo "PASS: scpp module build grouping\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function assertModuleGroupedGeneratedObjectEdges(): void
	{
		$project = $this->root . '/module_app';
		$repoRoot = $this->root . '/repo';
		$buildDir = $project . '/.prism/build/release';
		$generatedDir = $project . '/.prism/generated/release';
		$this->mkdir($buildDir);
		$this->mkdir($generatedDir);
		$this->mkdir($repoRoot . '/runtime/include');
		foreach (['base', 'child', 'main'] as $name) {
			$this->write($generatedDir . '/' . $name . '.hpp', '// header ' . $name . "\n");
			$this->write($generatedDir . '/' . $name . '.cpp', $name === 'main' ? "int main() { return 0; }\n" : 'void module_' . $name . "_probe() {}\n");
		}
		$this->write($generatedDir . '/__project_units/broad.hpp', "// broad\n");

		$policy = resolve_build_grouping_policy([
			'build' => [
				'mode' => 'release',
				'grouping_policy' => 'module',
				'grouping_compile' => true,
			],
		], 'release');
		$this->assertSame('module', $policy['policy'] ?? null, 'module grouping policy should resolve');
		$this->assertSame('active_generated_edges', $policy['status'] ?? null, 'module grouping_compile should activate generated edges in release mode');
		$this->assertSame('module_grouped_generated_objects', $policy['compile_unit_strategy'] ?? null, 'module grouping_compile should report module grouped strategy');

		$generatedUnits = [
			$this->generatedUnit($project, $generatedDir, $buildDir, 'base.phs', false),
			$this->generatedUnit($project, $generatedDir, $buildDir, 'child.phs', false),
			$this->generatedUnit($project, $generatedDir, $buildDir, 'main.phs', true),
		];
		$context = $this->buildContext($project, $generatedDir, $buildDir);
		$moduleReport = collect_project_module_report($project, [$project => $context], $generatedUnits, []);
		$policy = apply_project_module_build_grouping_evidence($project, $policy, $moduleReport, $generatedUnits);
		$this->assertSame(1, $policy['module_group_count'] ?? null, 'module grouping should collect the non-entrypoint domain module group');
		$this->assertSame(2, $policy['module_assigned_source_count'] ?? null, 'module grouping should assign base and child sources');
		$this->assertSame(0, $policy['module_unassigned_source_count'] ?? null, 'module grouping should not leave non-entrypoint sources unassigned');

		$sourceRows = [
			['project_root' => $project, 'path' => 'base.phs', 'object_path' => '.prism/build/release/base.o'],
			['project_root' => $project, 'path' => 'child.phs', 'object_path' => '.prism/build/release/child.o'],
			['project_root' => $project, 'path' => 'main.phs', 'object_path' => '.prism/build/release/main.o'],
		];
		apply_grouped_generated_object_edges($project, $buildDir, $generatedDir, $policy, $generatedUnits, $sourceRows, 'gnu_like');
		$groupObject = normalize_path((string) ($generatedUnits[0]['object_path'] ?? ''));
		$this->assertSame($groupObject, normalize_path((string) ($generatedUnits[1]['object_path'] ?? '')), 'module group members should share one generated object path');
		$this->assertContains('/.prism/build/release/__build_groups/', $groupObject, 'module group object should live under release build __build_groups');
		$this->assertSame($buildDir . '/main.o', normalize_path((string) ($generatedUnits[2]['object_path'] ?? '')), 'entrypoint should stay isolated for module grouping');
		$this->assertSame('module:root:domain', $generatedUnits[0]['build_group_id'] ?? null, 'module grouped unit should record module group id');
		$groupSource = normalize_path((string) ($generatedUnits[0]['compile_source_path'] ?? ''));
		$this->assertContains('/.prism/generated/release/__build_groups/', $groupSource, 'module group source should live under generated __build_groups');
		$groupSourceContents = $this->read($groupSource);
		$this->assertContains('#include "../base.cpp"', $groupSourceContents, 'module group source should include base generated source');
		$this->assertContains('#include "../child.cpp"', $groupSourceContents, 'module group source should include child generated source');

		$ninja = render_build_ninja(
			$project,
			$repoRoot,
			$buildDir,
			$generatedDir,
			$generatedUnits,
			[],
			'app',
			[
				'command' => 'g++',
				'kind' => 'gnu_like',
				'launcher' => null,
				'linker_flags' => [],
			],
			'release',
			['languages' => ['php'], 'modules' => ['json'], 'language_profiles' => ['php' => ['profile' => 'strict']]],
			[],
			null,
			['compile_runtime' => false, 'compile_dependencies' => true, 'use_pch' => false],
			'reuse',
			$moduleReport
		);
		$groupObjectBuildRelative = normalize_config_path(relative_path($buildDir, $groupObject));
		$groupSourceBuildRelative = normalize_config_path(relative_path($buildDir, $groupSource));
		$this->assertContains('build ' . $groupObjectBuildRelative . ': compile ' . $groupSourceBuildRelative, $ninja, 'Ninja should compile the module grouped object once');
		$this->assertNotContains('build base.o: compile ../../generated/release/base.cpp', $ninja, 'grouped base source should not emit a per-source release edge');
		$this->assertNotContains('build child.o: compile ../../generated/release/child.cpp', $ninja, 'grouped child source should not emit a per-source release edge');
		$this->assertContains('build main.o: compile ../../generated/release/main.cpp', $ninja, 'entrypoint should still emit a per-source release edge');
		$this->assertContains('project_modules/domain-', $ninja, 'module grouped compile edge should keep module surface artifacts as implicit inputs');
		$this->assertContains('.surface.json', $ninja, 'module grouped compile edge should reference public surface artifacts');
		$this->assertNotContains('.implementation.json', $ninja, 'module grouped compile edge should not reference private implementation artifacts');

		$report = collect_build_grouping_report($project, $policy, $generatedUnits, [], [
			'rebuilt_object_count' => 1,
			'rebuilt_generated_object_count' => 1,
			'rebuilt_native_object_count' => 0,
			'rebuilt_generated_objects' => [normalize_config_path(relative_path($project, $groupObject))],
		]);
		$groups = $this->groupsById($report);
		$domain = $groups['module:root:domain'] ?? null;
		if (!is_array($domain)) {
			throw new RuntimeException('module domain group should be present');
		}
		$this->assertSame('project_module', $domain['kind'] ?? null, 'module grouping report row should carry project_module kind');
		$this->assertSame(['base.phs', 'child.phs'], $domain['generated_sources'] ?? null, 'module grouping report should list grouped module sources');
		$this->assertSame(1, $domain['rebuilt_object_count'] ?? null, 'module grouping report should count grouped object rebuild once');
		$this->assertSame('module_grouped_generated_objects', $report['compile_unit_strategy'] ?? null, 'module grouping report should preserve compile strategy');
		$lines = implode("\n", render_build_grouping_lines($report, true));
		$this->assertContains('Build grouping project modules: groups 1, assigned sources 2, unassigned grouped sources 0', $lines, 'module grouping view should summarize explicit module map');
		$this->assertContains('module:root:domain', $lines, 'module grouping view should list module group id');
	}

	private function assertModuleGroupedProjectBuilds(): void
	{
		$project = $this->root . '/module_build_app';
		$this->write($project . '/base.phs', <<<'PHS'
class ModuleBaseProbe {
    public function value(): int {
        return 1;
    }
}
PHS);
		$this->write($project . '/child.phs', <<<'PHS'
class ModuleChildProbe extends ModuleBaseProbe {
    public function label(): string {
        return "child";
    }
}
PHS);
		$this->write($project . '/main.phs', <<<'PHS'
$child = new ModuleChildProbe();
echo "module grouped\n";
PHS);
		$this->writeJson($project . '/prism.json', [
			'config_version' => 1,
			'project_name' => 'module_build_app',
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
				'grouping_policy' => 'module',
				'grouping_compile' => true,
			],
			'project_modules' => [
				[
					'name' => 'domain',
					'sources' => ['base.phs', 'child.phs'],
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
		]);
		$build = scpp_run_build_service($project, $project . '/prism.json', [
			'compile_runtime' => true,
			'disable_stan' => true,
			'build_mode' => 'release',
		]);
		$this->assertSame(true, $build['ok'], "module grouped release project should build\nSTDOUT:\n" . (string) ($build['output'] ?? '') . "\nSTDERR:\n" . (string) ($build['error'] ?? ''));
		$result = is_array($build['result'] ?? null) ? $build['result'] : [];
		$buildDir = normalize_path((string) ($result['build_dir'] ?? $project . '/.prism/build/release'));
		$buildNinja = $this->read($buildDir . '/build.ninja');
		$this->assertContains('build __build_groups/', $buildNinja, 'module release build.ninja should contain a grouped generated object edge');
		$this->assertContains(': compile ../../generated/release/__build_groups/', $buildNinja, 'module grouped edge should compile a grouped generated source');
		$this->assertContains('project_modules/domain-', $buildNinja, 'module grouped build should keep public module surface inputs');
		$this->assertNotContains('.implementation.json', $buildNinja, 'module grouped build should keep private implementation artifacts out of compile inputs');

		$lastRun = json_decode($this->read($project . '/.prism/last_run.json'), true);
		if (!is_array($lastRun)) {
			throw new RuntimeException('last_run.json should decode');
		}
		$details = is_array($lastRun['details'] ?? null) ? $lastRun['details'] : [];
		$explanation = is_array($details['build_explanation'] ?? null) ? $details['build_explanation'] : [];
		$grouping = is_array($explanation['build_grouping'] ?? null) ? $explanation['build_grouping'] : [];
		$this->assertSame('module', $grouping['policy'] ?? null, 'saved grouping report should preserve module policy');
		$this->assertSame('active_generated_edges', $grouping['status'] ?? null, 'saved module grouping report should record active generated edges');
		$this->assertSame('module_grouped_generated_objects', $grouping['compile_unit_strategy'] ?? null, 'saved module grouping report should record module grouped strategy');
		$this->assertSame(1, $grouping['module_group_count'] ?? null, 'saved module grouping report should count module groups');
	}

	private function assertModuleGroupingCompileRequiresReleaseMode(): void
	{
		$this->assertFails(static function (): void {
			resolve_build_grouping_policy([
				'build' => [
					'grouping_policy' => 'module',
					'grouping_compile' => true,
				],
			], 'debug');
		}, 'non-manual grouped generated object edges require release build mode');
	}

	/** @return array<string,mixed> */
	private function buildContext(string $project, string $generatedDir, string $buildDir): array
	{
		return [
			'config' => [
				'project_modules' => [
					[
						'name' => 'domain',
						'sources' => ['base.phs', 'child.phs'],
					],
					[
						'name' => 'app',
						'sources' => ['main.phs'],
						'dependencies' => ['domain'],
					],
				],
			],
			'generated_dir' => $generatedDir,
			'build_dir' => $buildDir,
			'cache_dir' => $project . '/.prism/cache',
			'state' => [
				'files' => [],
			],
		];
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
			'generated_header' => $generatedDir . '/' . $base . '.hpp',
			'generated_cpp' => $generatedDir . '/' . $base . '.cpp',
			'object_path' => $buildDir . '/' . $base . '.o',
			'is_entrypoint' => $entrypoint,
			'force_include_header' => $generatedDir . '/__project_units/broad.hpp',
		];
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

	private function assertFails(callable $callback, string $messageFragment): void
	{
		try {
			ob_start();
			$callback();
			$output = (string) ob_get_clean();
		} catch (ScppCliException $exception) {
			if (ob_get_level() > 0) {
				ob_end_clean();
			}
			if (!str_contains($exception->getMessage(), $messageFragment)) {
				throw new RuntimeException('Expected failure containing `' . $messageFragment . '`, got `' . $exception->getMessage() . '`');
			}
			return;
		}
		throw new RuntimeException('Expected failure containing `' . $messageFragment . '`, got success with output: ' . $output);
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

	/** @param array<string,mixed> $data */
	private function writeJson(string $path, array $data): void
	{
		$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		if (!is_string($json)) {
			throw new RuntimeException('Failed to encode JSON: ' . $path);
		}
		$this->write($path, $json . PHP_EOL);
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
				continue;
			}
			@unlink($entry->getPathname());
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
			throw new RuntimeException($message . ': missing `' . $needle . '` in text');
		}
	}

	private function assertNotContains(string $needle, string $haystack, string $message): void
	{
		if (str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ': unexpectedly found `' . $needle . '`');
		}
	}
}

exit((new ScppBuildGroupingModuleTest())->run());

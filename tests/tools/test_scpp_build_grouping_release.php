<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppBuildGroupingReleaseTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_build_grouping_release_' . getmypid() . '_' . bin2hex(random_bytes(4)));
	}

	public function run(): int
	{
		try {
			$this->assertReleaseFolderGroupedGeneratedObjectEdges();
			if (find_command_path(['ninja']) !== null && resolve_compiler(['build' => []]) !== null) {
				$this->assertReleaseFolderGroupedProjectBuilds();
			}
			$this->assertNonManualGroupingCompileRequiresReleaseMode();
			$this->assertUnsupportedGroupingCompilePolicyFails();
			echo "PASS: scpp release build grouping\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function assertReleaseFolderGroupedGeneratedObjectEdges(): void
	{
		$project = $this->root . '/release_app';
		$repoRoot = $this->root . '/repo';
		$buildDir = $project . '/.prism/build/release';
		$generatedDir = $project . '/.prism/generated/release';
		$this->mkdir($buildDir);
		$this->mkdir($generatedDir);
		$this->mkdir($generatedDir . '/domain');
		$this->mkdir($generatedDir . '/other');
		$this->mkdir($repoRoot . '/runtime/include');
		$this->write($generatedDir . '/domain/base.cpp', "void release_base_probe() {}\n");
		$this->write($generatedDir . '/domain/child.cpp', "void release_child_probe() {}\n");
		$this->write($generatedDir . '/other/single.cpp', "void release_single_probe() {}\n");
		$this->write($generatedDir . '/main.cpp', "int main() { return 0; }\n");
		$this->write($generatedDir . '/__project_units/broad.hpp', "// broad\n");

		$policy = resolve_build_grouping_policy([
			'build' => [
				'mode' => 'release',
				'grouping_policy' => 'folder',
				'grouping_compile' => true,
			],
		], 'release');
		$this->assertSame('folder', $policy['policy'] ?? null, 'release grouping should preserve folder policy');
		$this->assertSame('active_generated_edges', $policy['status'] ?? null, 'release grouping_compile should activate generated edges');
		$this->assertSame('release_grouped_generated_objects', $policy['compile_unit_strategy'] ?? null, 'release grouping_compile should report release grouped strategy');
		$this->assertSame(true, $policy['generated_grouping_enabled'] ?? null, 'release grouping_compile should expose generated grouping flag');

		$generatedUnits = [
			[
				'project_root' => $project,
				'relative_php' => 'domain/base.phs',
				'generated_cpp' => $generatedDir . '/domain/base.cpp',
				'object_path' => $buildDir . '/domain/base.o',
				'is_entrypoint' => false,
				'force_include_header' => $generatedDir . '/__project_units/broad.hpp',
			],
			[
				'project_root' => $project,
				'relative_php' => 'domain/child.phs',
				'generated_cpp' => $generatedDir . '/domain/child.cpp',
				'object_path' => $buildDir . '/domain/child.o',
				'is_entrypoint' => false,
				'force_include_header' => $generatedDir . '/__project_units/broad.hpp',
			],
			[
				'project_root' => $project,
				'relative_php' => 'other/single.phs',
				'generated_cpp' => $generatedDir . '/other/single.cpp',
				'object_path' => $buildDir . '/other/single.o',
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
			['project_root' => $project, 'path' => 'domain/base.phs', 'object_path' => '.prism/build/release/domain/base.o'],
			['project_root' => $project, 'path' => 'domain/child.phs', 'object_path' => '.prism/build/release/domain/child.o'],
			['project_root' => $project, 'path' => 'other/single.phs', 'object_path' => '.prism/build/release/other/single.o'],
			['project_root' => $project, 'path' => 'main.phs', 'object_path' => '.prism/build/release/main.o'],
		];
		apply_grouped_generated_object_edges($project, $buildDir, $generatedDir, $policy, $generatedUnits, $sourceRows, 'gnu_like');

		$groupObject = normalize_path((string) ($generatedUnits[0]['object_path'] ?? ''));
		$this->assertSame($groupObject, normalize_path((string) ($generatedUnits[1]['object_path'] ?? '')), 'folder group members should share one release object path');
		$this->assertContains('/.prism/build/release/__build_groups/', $groupObject, 'release group object should live under release build __build_groups');
		$this->assertSame($buildDir . '/other/single.o', normalize_path((string) ($generatedUnits[2]['object_path'] ?? '')), 'singleton folders should keep per-source objects');
		$this->assertSame($buildDir . '/main.o', normalize_path((string) ($generatedUnits[3]['object_path'] ?? '')), 'entrypoint should keep its per-source object');
		$this->assertSame('folder:root:domain', $generatedUnits[0]['build_group_id'] ?? null, 'grouped units should record the folder group id');
		$groupSource = normalize_path((string) ($generatedUnits[0]['compile_source_path'] ?? ''));
		$this->assertContains('/.prism/generated/release/__build_groups/', $groupSource, 'release group source should live under release generated __build_groups');
		$groupSourceContents = $this->read($groupSource);
		$this->assertContains('#include "../domain/base.cpp"', $groupSourceContents, 'release group source should include base generated source');
		$this->assertContains('#include "../domain/child.cpp"', $groupSourceContents, 'release group source should include child generated source');
		$this->assertSame(normalize_config_path(relative_path($project, $groupObject)), $sourceRows[0]['object_path'] ?? null, 'source row should use grouped release object');
		$this->assertSame(normalize_config_path(relative_path($project, $groupObject)), $sourceRows[1]['object_path'] ?? null, 'all grouped source rows should use grouped release object');
		$this->assertSame('.prism/build/release/other/single.o', $sourceRows[2]['object_path'] ?? null, 'singleton source row should remain per-source');
		$this->assertSame('.prism/build/release/main.o', $sourceRows[3]['object_path'] ?? null, 'entrypoint source row should remain per-source');

		$compiler = [
			'command' => 'g++',
			'kind' => 'gnu_like',
			'launcher' => null,
			'linker_flags' => [],
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
			'release',
			['languages' => ['php'], 'modules' => ['json'], 'language_profiles' => ['php' => ['profile' => 'strict']]],
			[],
			null,
			['compile_runtime' => false, 'compile_dependencies' => true, 'use_pch' => false],
			'reuse',
			[]
		);
		$groupObjectBuildRelative = normalize_config_path(relative_path($buildDir, $groupObject));
		$groupSourceBuildRelative = normalize_config_path(relative_path($buildDir, $groupSource));
		$baseSourceBuildRelative = normalize_config_path(relative_path($buildDir, $generatedDir . '/domain/base.cpp'));
		$childSourceBuildRelative = normalize_config_path(relative_path($buildDir, $generatedDir . '/domain/child.cpp'));
		$singleSourceBuildRelative = normalize_config_path(relative_path($buildDir, $generatedDir . '/other/single.cpp'));
		$mainSourceBuildRelative = normalize_config_path(relative_path($buildDir, $generatedDir . '/main.cpp'));
		$this->assertContains('-O3', $ninja, 'release Ninja graph should keep release optimization flags');
		$this->assertContains('build ' . $groupObjectBuildRelative . ': compile ' . $groupSourceBuildRelative, $ninja, 'Ninja should compile the release grouped object once');
		$this->assertNotContains('build domain/base.o: compile ' . $baseSourceBuildRelative, $ninja, 'grouped base source should not emit a per-source release edge');
		$this->assertNotContains('build domain/child.o: compile ' . $childSourceBuildRelative, $ninja, 'grouped child source should not emit a per-source release edge');
		$this->assertContains('build other/single.o: compile ' . $singleSourceBuildRelative, $ninja, 'singleton source should still emit a per-source release edge');
		$this->assertContains('build main.o: compile ' . $mainSourceBuildRelative, $ninja, 'entrypoint should still emit a per-source release edge');
		$linkLine = $this->findLineStartingWith($ninja, 'build app: link ');
		$this->assertSame(1, substr_count($linkLine, $groupObjectBuildRelative), 'link line should include release grouped object once');
		$this->assertSame(0, substr_count($linkLine, 'domain/base.o'), 'link line should not include the old base release object');
		$this->assertSame(0, substr_count($linkLine, 'domain/child.o'), 'link line should not include the old child release object');

		$report = collect_build_grouping_report($project, $policy, $generatedUnits, [], [
			'rebuilt_object_count' => 1,
			'rebuilt_generated_object_count' => 1,
			'rebuilt_native_object_count' => 0,
			'rebuilt_generated_objects' => [normalize_config_path(relative_path($project, $groupObject))],
		]);
		$groups = $this->groupsById($report);
		$domain = $groups['folder:root:domain'] ?? null;
		if (!is_array($domain)) {
			throw new RuntimeException('release folder group should be present');
		}
		$this->assertSame([normalize_config_path(relative_path($project, $groupObject))], $domain['objects'] ?? null, 'release folder group should list the grouped object once');
		$this->assertSame(1, $domain['rebuilt_object_count'] ?? null, 'release folder group should count the grouped object once');
		$this->assertSame(1, $report['changed_group_count'] ?? null, 'release grouped object rebuild should mark one changed group');
		$this->assertSame([
			[
				'group_id' => 'folder:root:domain',
				'reasons' => ['rebuilt object ' . normalize_config_path(relative_path($project, $groupObject))],
			],
		], $report['changed_group_reasons'] ?? null, 'release grouping report should expose changed group reasons');
	}

	private function assertReleaseFolderGroupedProjectBuilds(): void
	{
		$project = $this->root . '/release_build_app';
		$this->mkdir($project . '/domain');
		$this->write($project . '/domain/base.phs', <<<'PHS'
class ReleaseBaseProbe {
    public function value(): int {
        return 1;
    }
}
PHS);
		$this->write($project . '/domain/child.phs', <<<'PHS'
class ReleaseChildProbe extends ReleaseBaseProbe {
    public function label(): string {
        return "child";
    }
}
PHS);
		$this->write($project . '/main.phs', <<<'PHS'
$child = new ReleaseChildProbe();
echo "release grouped\n";
PHS);
		$config = [
			'config_version' => 1,
			'project_name' => 'release_build_app',
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
				'grouping_policy' => 'folder',
				'grouping_compile' => true,
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
			'build_mode' => 'release',
		]);
		$this->assertSame(true, $build['ok'], "release grouped generated project should build\nSTDOUT:\n" . (string) ($build['output'] ?? '') . "\nSTDERR:\n" . (string) ($build['error'] ?? ''));
		$result = is_array($build['result'] ?? null) ? $build['result'] : [];
		$buildDir = normalize_path((string) ($result['build_dir'] ?? $project . '/.prism/build'));
		$buildNinja = $this->read($buildDir . '/build.ninja');
		$this->assertContains('build __build_groups/', $buildNinja, 'release build.ninja should contain a grouped generated object edge');
		$this->assertContains(': compile ../../generated/release/__build_groups/', $buildNinja, 'release grouped edge should compile a grouped generated source');
		$this->assertContains('-O3', $buildNinja, 'release grouped build should use release optimization flags');
		$this->assertNotContains('build domain/base.o: compile ../../generated/release/domain/base.cpp', $buildNinja, 'base should not compile as an isolated release object when grouped');
		$this->assertNotContains('build domain/child.o: compile ../../generated/release/domain/child.cpp', $buildNinja, 'child should not compile as an isolated release object when grouped');
		$lastRun = json_decode($this->read($project . '/.prism/last_run.json'), true);
		if (!is_array($lastRun)) {
			throw new RuntimeException('last_run.json should decode');
		}
		$details = is_array($lastRun['details'] ?? null) ? $lastRun['details'] : [];
		$explanation = is_array($details['build_explanation'] ?? null) ? $details['build_explanation'] : [];
		$grouping = is_array($explanation['build_grouping'] ?? null) ? $explanation['build_grouping'] : [];
		$this->assertSame('active_generated_edges', $grouping['status'] ?? null, 'saved release grouping report should record active generated edges');
		$this->assertSame('release_grouped_generated_objects', $grouping['compile_unit_strategy'] ?? null, 'saved release grouping report should record grouped release strategy');
		$this->assertSame(true, $grouping['generated_grouping_enabled'] ?? null, 'saved release grouping report should expose generated grouping flag');
	}

	private function assertNonManualGroupingCompileRequiresReleaseMode(): void
	{
		$this->assertFails(static function (): void {
			resolve_build_grouping_policy([
				'build' => [
					'grouping_policy' => 'folder',
					'grouping_compile' => true,
				],
			], 'debug');
		}, 'non-manual grouped generated object edges require release build mode');
	}

	private function assertUnsupportedGroupingCompilePolicyFails(): void
	{
		$this->assertFails(static function (): void {
			resolve_build_grouping_policy([
				'build' => [
					'grouping_policy' => 'incremental',
					'grouping_compile' => true,
				],
			], 'release');
		}, 'grouped generated object edges currently require build.grouping_policy = "manual", "folder", "package", "release", or "auto"');
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

exit((new ScppBuildGroupingReleaseTest())->run());

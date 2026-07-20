<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppExplainBuildTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_explain_build_' . getmypid() . '_' . bin2hex(random_bytes(4)));
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
			$this->mkdir($project . '/native_cpp');
			$this->write($project . '/base.phs', "class Base {}\n");
			$this->write($project . '/child.phs', "class Child extends Base {}\n");
			$this->write($project . '/main.phs', "\$child = new Child();\necho \"hello\\n\";\n");
			$config = [
				'config_version' => 1,
				'project_name' => 'explain_build',
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

			$fullBuild = scpp_run_build_service($project, $project . '/prism.json', [
				'compile_runtime' => true,
				'compile_dependencies' => false,
			]);
			$this->assertSame(true, $fullBuild['ok'], 'seed build should succeed');

			$warmBuild = scpp_run_build_service($project, $project . '/prism.json');
			$this->assertSame(true, $warmBuild['ok'], 'warm build should succeed');

			$report = json_decode($this->read($project . '/.prism/last_run.json'), true);
			if (!is_array($report)) {
				throw new RuntimeException('last_run.json should decode as an object');
			}
			$details = is_array($report['details'] ?? null) ? $report['details'] : null;
			if (!is_array($details)) {
				throw new RuntimeException('last_run.json should contain details');
			}
			$explanation = is_array($details['build_explanation'] ?? null) ? $details['build_explanation'] : null;
			if (!is_array($explanation)) {
				throw new RuntimeException('last_run.json should contain build_explanation');
			}
			$this->assertSame('success', $explanation['status'] ?? null, 'build explanation should record success');
			$this->assertSame('reuse', $explanation['runtime']['action'] ?? null, 'warm build should report runtime reuse');
			$this->assertSame(['reusing existing runtime artifact by default'], $explanation['runtime']['reasons'] ?? null, 'warm build should preserve runtime reuse reason');
			$projectUnits = is_array($explanation['project_unit_force_includes'] ?? null) ? $explanation['project_unit_force_includes'] : null;
			if (!is_array($projectUnits)) {
				throw new RuntimeException('build explanation should contain project unit force-include details');
			}
			$this->assertSame(3, $projectUnits['total_units'] ?? null, 'three-file project should report three compiled units');
			$this->assertSame(3, $projectUnits['units_with_force_include'] ?? null, 'three-file project should force-include the project unit header for each generated unit');
			$this->assertSame(1, $projectUnits['distinct_headers'] ?? null, 'three-file project should still use one broad project unit header');
			$projectUnitHeaders = is_array($projectUnits['headers'] ?? null) ? $projectUnits['headers'] : [];
			$projectUnitHeader = $projectUnitHeaders[0] ?? null;
			if (!is_array($projectUnitHeader)) {
				throw new RuntimeException('project unit report should contain a header row');
			}
			$activeProjectUnitHeader = (string) ($projectUnitHeader['path'] ?? '');
			$this->assertTrue(str_starts_with($activeProjectUnitHeader, '.prism/generated/__project_units/'), 'project unit report should name the active hash-pack header');
			$this->assertSame('broad_equivalent_pack', $projectUnitHeader['mode'] ?? null, 'current project unit report should classify the active header as a broad-equivalent pack');
			$dependencySummaries = is_array($projectUnits['dependency_summaries'] ?? null) ? $projectUnits['dependency_summaries'] : [];
			$dependencySummaryBySource = [];
			foreach ($dependencySummaries as $summary) {
				if (is_array($summary) && is_string($summary['source'] ?? null)) {
					$dependencySummaryBySource[$summary['source']] = $summary;
				}
			}
			$childDependencySummary = $dependencySummaryBySource['child.phs'] ?? null;
			if (!is_array($childDependencySummary)) {
				throw new RuntimeException('project unit report should contain a child.phs dependency summary');
			}
			$this->assertSame('fallback_broad', $childDependencySummary['status'] ?? null, 'dependency summaries should preserve the active broad fallback status');
			$this->assertSame('candidate_scoped', $childDependencySummary['candidate_status'] ?? null, 'Phase C0 should recognize child.phs as a dry-run scoped pack candidate');
			$this->assertTrue(str_starts_with((string) ($childDependencySummary['candidate_pack_header'] ?? ''), '.prism/generated/__project_units/scoped-'), 'Phase C0 should report the child scoped candidate pack path');
			$childCandidateHeaders = is_array($childDependencySummary['candidate_scoped_headers'] ?? null) ? $childDependencySummary['candidate_scoped_headers'] : [];
			$this->assertTrue(in_array('.prism/generated/__project_fwd.hpp', $childCandidateHeaders, true), 'child scoped candidate should include the project forward header');
			$this->assertTrue(in_array('.prism/generated/base.hpp', $childCandidateHeaders, true), 'child scoped candidate should include its direct base header');
			$this->assertTrue(in_array('.prism/generated/child.hpp', $childCandidateHeaders, true), 'child scoped candidate should include its own generated header');
			$this->assertSame([], $childDependencySummary['candidate_blocking_reasons'] ?? null, 'child scoped candidate should not have C0 blockers');
			$this->assertSame(['base.phs'], $childDependencySummary['direct_source_dependencies'] ?? null, 'child.phs should record its direct base-class source dependency');
			$this->assertSame(['.prism/generated/base.hpp'], $childDependencySummary['direct_local_headers'] ?? null, 'child.phs should map its direct dependency to the generated base header');
			$mainDependencySummary = $dependencySummaryBySource['main.phs'] ?? null;
			if (!is_array($mainDependencySummary)) {
				throw new RuntimeException('project unit report should contain a main.phs dependency summary');
			}
			$this->assertSame('blocked_broad_fallback', $mainDependencySummary['candidate_status'] ?? null, 'executable source should stay blocked for scoped-pack activation during C0');
			$this->assertContains('function or executable statement body present', implode("\n", is_array($mainDependencySummary['candidate_blocking_reasons'] ?? null) ? $mainDependencySummary['candidate_blocking_reasons'] : []), 'executable source should explain the scoped candidate blocker');

			$sources = is_array($explanation['sources'] ?? null) ? $explanation['sources'] : [];
			$mainSource = null;
			foreach ($sources as $source) {
				if (is_array($source) && ($source['path'] ?? null) === 'main.phs') {
					$mainSource = $source;
					break;
				}
			}
			if (!is_array($mainSource)) {
				throw new RuntimeException('build explanation should include the main source record');
			}
			$this->assertSame('main.phs', $mainSource['path'] ?? null, 'source explanation should preserve relative path');
			$this->assertSame('reused', $mainSource['action'] ?? null, 'warm build should reuse unchanged source');

			$script = normalize_path(resolve_repo_root() . '/bin/scpp.php');
			$explain = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build'], [], 20.0);
			$this->assertSame(0, $explain['exit_code'], 'scpp explain-build should succeed');
			$this->assertContains('Explain build: build', $explain['stdout'], 'explain-build should identify the saved command');
			$this->assertContains('Runtime: reuse (reusing existing runtime artifact by default)', $explain['stdout'], 'explain-build should explain runtime reuse');
			$this->assertContains('main.phs -> reused (source metadata and generated artifacts unchanged)', $explain['stdout'], 'explain-build should explain source reuse');
			$this->assertContains('Direct Ninja target: main', $explain['stdout'], 'explain-build should report the direct Ninja target name');
			$this->assertContains('ninja -C .prism/build -d explain main', $explain['stdout'], 'explain-build should report the direct Ninja debug command');
			$this->assertContains('Warning: `.prism/build/main` is the built executable path, not a Ninja target name.', $explain['stdout'], 'explain-build should warn about path-shaped Ninja targets');

			$transpiledView = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build', 'files-transpiled'], [], 20.0);
			$this->assertSame(0, $transpiledView['exit_code'], 'scpp explain-build files-transpiled should succeed');
			$this->assertContains('Files transpiled: none', $transpiledView['stdout'], 'files-transpiled should report no warm-build transpiles');

			$reusedView = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build', 'files-reused'], [], 20.0);
			$this->assertSame(0, $reusedView['exit_code'], 'scpp explain-build files-reused should succeed');
			$this->assertContains('Files reused:', $reusedView['stdout'], 'files-reused should include a header');
			$this->assertContains('main.phs (source metadata and generated artifacts unchanged)', $reusedView['stdout'], 'files-reused should list the reused source');

			$projectUnitsView = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build', 'project-units'], [], 20.0);
			$this->assertSame(0, $projectUnitsView['exit_code'], 'scpp explain-build project-units should succeed');
			$this->assertContains('Project unit force-includes: 3/3 unit(s), 1 distinct header(s)', $projectUnitsView['stdout'], 'project-units should summarize force-include fanout');
			$this->assertContains('.prism/generated/__project_units/', $projectUnitsView['stdout'], 'project-units should list the force-included hash-pack header');
			$this->assertContains('broad_equivalent_pack', $projectUnitsView['stdout'], 'project-units should classify the active pack header');
			$this->assertContains('child.phs: fallback_broad', $projectUnitsView['stdout'], 'project-units should list the child dependency summary');
			$this->assertContains('candidate status: candidate_scoped', $projectUnitsView['stdout'], 'project-units should show scoped candidate status');
			$this->assertContains('candidate pack: .prism/generated/__project_units/scoped-', $projectUnitsView['stdout'], 'project-units should show scoped candidate pack paths');
			$this->assertContains('candidate scoped headers:', $projectUnitsView['stdout'], 'project-units should show scoped candidate header lists');
			$this->assertContains('candidate blocker: function or executable statement body present', $projectUnitsView['stdout'], 'project-units should show scoped candidate blockers');
			$this->assertContains('direct source dependencies: base.phs', $projectUnitsView['stdout'], 'project-units should show the child direct source dependency');
			$this->assertContains('direct local headers: .prism/generated/base.hpp', $projectUnitsView['stdout'], 'project-units should show the child direct generated header dependency');

			$entrypointView = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build', 'entrypoint'], [], 20.0);
			$this->assertSame(0, $entrypointView['exit_code'], 'scpp explain-build entrypoint should succeed');
			$this->assertContains('Entrypoint: main.phs', $entrypointView['stdout'], 'entrypoint should list the entry source');
			$this->assertContains('Generated C++: .prism/generated/main.cpp', $entrypointView['stdout'], 'entrypoint should list the generated C++ path');
			$this->assertContains('Object: .prism/build/main.o', $entrypointView['stdout'], 'entrypoint should list the object path');

			$finalOutputView = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build', 'final-output'], [], 20.0);
			$this->assertSame(0, $finalOutputView['exit_code'], 'scpp explain-build final-output should succeed');
			$this->assertContains('Final output: .prism/build/main', $finalOutputView['stdout'], 'final-output should list the executable path');

			$generatedFilesView = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build', 'generated-files'], [], 20.0);
			$this->assertSame(0, $generatedFilesView['exit_code'], 'scpp explain-build generated-files should succeed');
			$this->assertContains('Generated files:', $generatedFilesView['stdout'], 'generated-files should include a header');
			$this->assertContains('main.phs -> .prism/generated/main.cpp -> .prism/build/main.o', $generatedFilesView['stdout'], 'generated-files should map source to generated and object outputs');

			$ninjaTargetView = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build', 'ninja-target'], [], 20.0);
			$this->assertSame(0, $ninjaTargetView['exit_code'], 'scpp explain-build ninja-target should succeed');
			$this->assertContains('Direct Ninja target: main', $ninjaTargetView['stdout'], 'ninja-target should list the direct Ninja target');
			$this->assertContains('Use `main` as the Ninja target, not `.prism/build/main`.', $ninjaTargetView['stdout'], 'ninja-target should explicitly contrast the target and executable path');

			$noStanProject = $this->root . '/no_stan_report';
			$this->mkdir($noStanProject . '/.prism/generated');
			$this->mkdir($noStanProject . '/.prism/cache');
			$this->write($noStanProject . '/main.phs', "echo \"nostan\\n\";\n");
			$this->write($noStanProject . '/.prism/generated/main.hpp', "#pragma once\n");
			$this->mkdir($noStanProject . '/.prism/generated/__project_units');
			$this->write($noStanProject . '/.prism/generated/__project_units.hpp', "#pragma once\n#include \"main.hpp\"\n");
			$this->write($noStanProject . '/.prism/generated/__project_units/pack.hpp', "#pragma once\n#include \"../main.hpp\"\n");
			$noStanReport = collect_project_unit_force_include_report(
				$noStanProject,
				[
					$noStanProject => [
						'project_root' => $noStanProject,
						'generated_dir' => $noStanProject . '/.prism/generated',
						'cache_dir' => $noStanProject . '/.prism/cache',
						'dependency_roots' => [],
					],
				],
				[
					[
						'project_root' => $noStanProject,
						'relative_php' => 'main.phs',
						'generated_header' => $noStanProject . '/.prism/generated/main.hpp',
						'generated_cpp' => $noStanProject . '/.prism/generated/main.cpp',
						'object_path' => $noStanProject . '/.prism/build/main.o',
						'is_entrypoint' => true,
						'force_include_header' => $noStanProject . '/.prism/generated/__project_units/pack.hpp',
					],
				],
				[]
			);
			$noStanSummaries = is_array($noStanReport['dependency_summaries'] ?? null) ? $noStanReport['dependency_summaries'] : [];
			$noStanSummary = $noStanSummaries[0] ?? null;
			if (!is_array($noStanSummary)) {
				throw new RuntimeException('no-STAN project unit report should still contain a dependency summary');
			}
			$this->assertSame('fallback_broad', $noStanSummary['status'] ?? null, 'no-STAN dependency summaries should preserve broad fallback status');
			$this->assertSame('blocked_broad_fallback', $noStanSummary['candidate_status'] ?? null, 'no-STAN scoped candidates should be marked blocked');
			$this->assertSame(['.prism/generated/main.hpp'], $noStanSummary['candidate_scoped_headers'] ?? null, 'no-STAN scoped candidate should still report its own header');
			$this->assertContains('STAN dependency state unavailable', implode("\n", is_array($noStanSummary['candidate_blocking_reasons'] ?? null) ? $noStanSummary['candidate_blocking_reasons'] : []), 'no-STAN scoped candidate should explain missing STAN state');
			$this->assertContains('source summary unavailable', implode("\n", is_array($noStanSummary['candidate_blocking_reasons'] ?? null) ? $noStanSummary['candidate_blocking_reasons'] : []), 'no-STAN scoped candidate should explain missing source summary');
			$this->assertContains('STAN dependency state unavailable for this build', implode("\n", is_array($noStanSummary['reasons'] ?? null) ? $noStanSummary['reasons'] : []), 'no-STAN dependency summary should explain missing STAN state');

			echo "PASS: scpp explain-build\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function write(string $path, string $contents): void
	{
		if (file_put_contents($path, $contents) === false) {
			throw new RuntimeException('Failed to write ' . $path);
		}
	}

	private function read(string $path): string
	{
		$contents = file_get_contents($path);
		if (!is_string($contents)) {
			throw new RuntimeException('Failed to read ' . $path);
		}
		return $contents;
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

	private function assertTrue(bool $condition, string $message): void
	{
		if (!$condition) {
			throw new RuntimeException($message);
		}
	}

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (!str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' missing `' . $needle . '`');
		}
	}
}

exit((new ScppExplainBuildTest())->run());

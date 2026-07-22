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
			$this->write($project . '/native_cpp/policy_probe.cpp', "void scpp_native_project_unit_policy_probe() {}\n");
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
					'grouping_policy' => 'incremental',
				],
				'project_modules' => [
					[
						'name' => 'domain',
						'sources' => ['base.phs', 'child.phs'],
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
			$buildGrouping = is_array($explanation['build_grouping'] ?? null) ? $explanation['build_grouping'] : null;
			if (!is_array($buildGrouping)) {
				throw new RuntimeException('build explanation should contain build grouping details');
			}
			$this->assertBuildGroupingReportShape($buildGrouping, 'warm build grouping report');
			$this->assertSame('incremental', $buildGrouping['policy'] ?? null, 'warm build grouping report should preserve the configured policy');
			$this->assertSame('build.grouping_policy', $buildGrouping['source'] ?? null, 'warm build grouping report should identify the config source');
			$this->assertSame('report_only', $buildGrouping['status'] ?? null, 'warm build grouping report should be explicit about current report-only status');
			$this->assertSame(4, $buildGrouping['total_groups'] ?? null, 'incremental grouping should isolate each generated/native source as its own group');
			$this->assertSame(0, $buildGrouping['changed_group_count'] ?? null, 'warm build grouping should record no changed groups');
			$buildGroupingFanout = is_array($buildGrouping['object_fanout'] ?? null) ? $buildGrouping['object_fanout'] : [];
			$this->assertSame(0, $buildGroupingFanout['rebuilt_object_count'] ?? null, 'warm build grouping should mirror no object fanout');
			$groupIds = [];
			foreach (is_array($buildGrouping['groups'] ?? null) ? $buildGrouping['groups'] : [] as $group) {
				if (is_array($group) && is_string($group['id'] ?? null)) {
					$groupIds[] = $group['id'];
				}
			}
			$this->assertContains('source:root:generated:child.phs', implode("\n", $groupIds), 'warm build grouping should include an isolated child source group');
			$this->assertContains('source:root:native:native_cpp/policy_probe.cpp', implode("\n", $groupIds), 'warm build grouping should include an isolated native source group');
			$projectModules = is_array($explanation['project_modules'] ?? null) ? $explanation['project_modules'] : null;
			if (!is_array($projectModules)) {
				throw new RuntimeException('build explanation should contain project module details');
			}
			$this->assertProjectModuleReportShape($projectModules, 'warm project module report');
			$this->assertSame(true, $projectModules['configured'] ?? null, 'project module report should record configured modules');
			$this->assertSame(2, $projectModules['total_modules'] ?? null, 'project module report should count configured modules');
			$this->assertSame(3, $projectModules['generated_source_count'] ?? null, 'project module report should count generated project sources');
			$this->assertSame(3, $projectModules['assigned_source_count'] ?? null, 'project module report should count assigned generated sources');
			$this->assertSame(0, $projectModules['unassigned_source_count'] ?? null, 'project module report should record no unassigned generated sources');
			$moduleCacheCounts = is_array($projectModules['cache_status_counts'] ?? null) ? $projectModules['cache_status_counts'] : [];
			$this->assertSame(2, $moduleCacheCounts['hit'] ?? null, 'warm project module report should reuse both module surface artifacts');
			$moduleByName = [];
			foreach (is_array($projectModules['modules'] ?? null) ? $projectModules['modules'] : [] as $module) {
				if (is_array($module) && is_string($module['name'] ?? null)) {
					$moduleByName[$module['name']] = $module;
				}
			}
			$domainModule = $moduleByName['domain'] ?? null;
			if (!is_array($domainModule)) {
				throw new RuntimeException('project module report should contain domain module');
			}
			$this->assertSame(2, $domainModule['source_count'] ?? null, 'domain module should own base and child sources');
			$this->assertSame('hit', $domainModule['cache_status'] ?? null, 'warm domain module should be a cache hit');
			$this->assertSame(false, $domainModule['interface_changed'] ?? null, 'warm domain module should not change interface hash');
			$this->assertSame(false, $domainModule['implementation_changed'] ?? null, 'warm domain module should not change implementation hash');
			$this->assertSame('interface_hash_only', $domainModule['consumer_rebuild_policy'] ?? null, 'domain module should report interface-hash consumer policy');
			$this->assertTrue(is_string($domainModule['surface_artifact'] ?? null) && str_ends_with((string) $domainModule['surface_artifact'], '.surface.json'), 'domain module should report a surface artifact');
			$this->assertTrue(is_string($domainModule['implementation_artifact'] ?? null) && str_ends_with((string) $domainModule['implementation_artifact'], '.implementation.json'), 'domain module should report an implementation artifact');
			$this->assertFileExists($project . '/' . (string) ($domainModule['surface_artifact'] ?? ''), 'warm build should write the domain module surface artifact');
			$this->assertFileExists($project . '/' . (string) ($domainModule['implementation_artifact'] ?? ''), 'warm build should write the domain module implementation artifact');
			$domainSurfaceArtifact = json_decode($this->read($project . '/' . (string) ($domainModule['surface_artifact'] ?? '')), true);
			if (!is_array($domainSurfaceArtifact)) {
				throw new RuntimeException('domain module surface artifact should decode as JSON');
			}
			$this->assertSame('project_module_surface', $domainSurfaceArtifact['kind'] ?? null, 'domain module surface artifact should identify its kind');
			$this->assertSame($domainModule['interface_hash'] ?? null, $domainSurfaceArtifact['interface_hash'] ?? null, 'domain module surface artifact should persist the interface hash');
			$appModule = $moduleByName['app'] ?? null;
			if (!is_array($appModule)) {
				throw new RuntimeException('project module report should contain app module');
			}
			$this->assertSame(['domain'], $appModule['dependencies'] ?? null, 'app module should report its project-local dependency');
			$this->assertSame(false, $appModule['consumer_rebuild_required'] ?? null, 'warm app module should not require consumer rebuild when domain interface is unchanged');
			$projectUnits = is_array($explanation['project_unit_force_includes'] ?? null) ? $explanation['project_unit_force_includes'] : null;
			if (!is_array($projectUnits)) {
				throw new RuntimeException('build explanation should contain project unit force-include details');
			}
			$this->assertProjectUnitReportShape($projectUnits, 'warm project-unit report');
			$this->assertSame(['changed_headers' => [], 'removed_headers' => [], 'changed_count' => 0, 'removed_count' => 0], $projectUnits['pack_changes'] ?? null, 'warm project-unit report should record no changed pack headers');
			$summaryArtifact = is_array($projectUnits['dependency_summary_artifact'] ?? null) ? $projectUnits['dependency_summary_artifact'] : [];
			$this->assertSame('.prism/cache/project_unit_dependency_summary.php', $summaryArtifact['path'] ?? null, 'warm project-unit report should point to the build-owned dependency summary artifact');
			$this->assertSame(3, $summaryArtifact['source_count'] ?? null, 'warm dependency summary artifact should count project sources');
			$this->assertSame(false, $summaryArtifact['used_stan_dependency_state'] ?? null, 'warm dependency summary artifact should record build-owned inputs when fresh STAN state is unavailable');
			$this->assertSame(false, $summaryArtifact['source_overrides_active'] ?? null, 'warm dependency summary artifact should record source override state');
			$this->assertFileExists($project . '/' . (string) ($summaryArtifact['path'] ?? ''), 'warm build should write the project-unit dependency summary artifact');
			$summaryArtifactState = require $project . '/' . (string) ($summaryArtifact['path'] ?? '');
			if (!is_array($summaryArtifactState)) {
				throw new RuntimeException('project-unit dependency summary artifact should return an object');
			}
			$this->assertSame(1, $summaryArtifactState['version'] ?? null, 'project-unit dependency summary artifact should record version');
			$artifactSources = is_array($summaryArtifactState['sources'] ?? null) ? $summaryArtifactState['sources'] : [];
			$artifactSourceByName = [];
			foreach ($artifactSources as $artifactSource) {
				if (is_array($artifactSource) && is_string($artifactSource['source'] ?? null)) {
					$artifactSourceByName[$artifactSource['source']] = $artifactSource;
				}
			}
			$childArtifactSource = $artifactSourceByName['child.phs'] ?? null;
			if (!is_array($childArtifactSource)) {
				throw new RuntimeException('project-unit dependency summary artifact should contain child.phs');
			}
			$this->assertSame(['base.phs'], $childArtifactSource['direct_source_keys'] ?? null, 'project-unit dependency summary artifact should persist direct source keys');
			$this->assertSame(['.prism/generated/base.hpp'], $childArtifactSource['direct_local_headers'] ?? null, 'project-unit dependency summary artifact should persist direct local headers');
			$this->assertSame('candidate_scoped', $childArtifactSource['candidate_status'] ?? null, 'project-unit dependency summary artifact should persist candidate status');
			$this->assertSame([], $childArtifactSource['candidate_blocking_reasons'] ?? null, 'project-unit dependency summary artifact should persist candidate blockers');
			$childArtifactFreshness = is_array($childArtifactSource['freshness'] ?? null) ? $childArtifactSource['freshness'] : [];
			$this->assertSame('child.phs', $childArtifactFreshness['source'] ?? null, 'project-unit dependency summary artifact should persist per-source freshness');
			$this->assertTrue(is_string($childArtifactFreshness['content_hash'] ?? null) && strlen((string) $childArtifactFreshness['content_hash']) === 64, 'project-unit dependency summary artifact should persist source content hash');
			$topLevelFanout = is_array($details['rebuild_fanout'] ?? null) ? $details['rebuild_fanout'] : null;
			if (!is_array($topLevelFanout)) {
				throw new RuntimeException('last_run details should contain rebuild fanout');
			}
			$this->assertRebuildFanoutShape($topLevelFanout, 'warm top-level rebuild fanout');
			$rebuildFanout = is_array($explanation['rebuild_fanout'] ?? null) ? $explanation['rebuild_fanout'] : null;
			if (!is_array($rebuildFanout)) {
				throw new RuntimeException('build explanation should contain rebuild fanout');
			}
			$this->assertRebuildFanoutShape($rebuildFanout, 'warm explanation rebuild fanout');
			$this->assertSame($topLevelFanout, $rebuildFanout, 'top-level and explanation rebuild fanout should match');
			$this->assertSame(0, $rebuildFanout['rebuilt_output_count'] ?? null, 'warm build should record no changed outputs');
			$this->assertSame(0, $rebuildFanout['rebuilt_object_count'] ?? null, 'warm build should record no rebuilt objects');
			$this->assertSame(0, $rebuildFanout['changed_project_unit_pack_count'] ?? null, 'warm build should record no changed project-unit packs');
			$this->assertSame(0, $rebuildFanout['removed_project_unit_pack_count'] ?? null, 'warm build should record no removed project-unit packs');
			$this->assertSame(true, $rebuildFanout['ninja_no_work'] ?? null, 'warm build should record Ninja no-work');
			$this->assertSame(4, $projectUnits['total_units'] ?? null, 'three-source project with one native file should report four compiled units');
			$this->assertSame(4, $projectUnits['units_with_force_include'] ?? null, 'three-source project with one native file should force-include a project unit header for each compiled unit');
			$this->assertSame(3, $projectUnits['distinct_headers'] ?? null, 'three-source project should use scoped packs for safe declaration-only units and broad fallback for main/native');
			$this->assertSame(2, $projectUnits['active_scoped_units'] ?? null, 'three-file project should count two active scoped units');
			$this->assertSame(1, $projectUnits['active_broad_fallback_units'] ?? null, 'three-file project should count one active broad fallback unit');
			$this->assertSame(2, $projectUnits['candidate_scoped_units'] ?? null, 'three-file project should count two scoped candidates');
			$this->assertSame(1, $projectUnits['candidate_blocked_units'] ?? null, 'three-file project should count one blocked scoped candidate');
			$this->assertSame(1, $projectUnits['native_units'] ?? null, 'project unit report should count native C++ units');
			$this->assertSame(1, $projectUnits['native_broad_fallback_units'] ?? null, 'native C++ units should stay on broad fallback');
			$this->assertSame('broad_fallback_without_dependency_manifest', $projectUnits['native_policy']['status'] ?? null, 'native C++ project-unit policy should be explicit');
			$this->assertSame([['reason' => 'executable body present', 'unit_count' => 1]], $projectUnits['candidate_blocker_counts'] ?? null, 'three-file project should count the executable-body blocker');
			$projectUnitHeaders = is_array($projectUnits['headers'] ?? null) ? $projectUnits['headers'] : [];
			$headerModes = [];
			foreach ($projectUnitHeaders as $projectUnitHeader) {
				if (!is_array($projectUnitHeader)) {
					continue;
				}
				$activeProjectUnitHeader = (string) ($projectUnitHeader['path'] ?? '');
				$this->assertTrue(str_starts_with($activeProjectUnitHeader, '.prism/generated/__project_units/'), 'project unit report should name hash-pack headers');
				$headerModes[] = (string) ($projectUnitHeader['mode'] ?? '');
			}
			$this->assertTrue(in_array('scoped', $headerModes, true), 'project unit report should classify active scoped pack headers');
			$this->assertTrue(in_array('broad_equivalent_pack', $headerModes, true), 'project unit report should preserve broad fallback pack headers');
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
			$this->assertSame('scoped', $childDependencySummary['status'] ?? null, 'C1 should activate scoped packs for safe declaration-only units');
			$this->assertSame('candidate_scoped', $childDependencySummary['candidate_status'] ?? null, 'Phase C1 should recognize child.phs as a scoped pack candidate');
			$this->assertTrue(str_starts_with((string) ($childDependencySummary['candidate_pack_header'] ?? ''), '.prism/generated/__project_units/scoped-'), 'Phase C1 should report the child scoped candidate pack path');
			$this->assertSame($childDependencySummary['candidate_pack_header'] ?? null, $childDependencySummary['force_include_header'] ?? null, 'C1 should assign the scoped candidate pack to child.phs');
			$childCandidateHeaders = is_array($childDependencySummary['candidate_scoped_headers'] ?? null) ? $childDependencySummary['candidate_scoped_headers'] : [];
			$this->assertTrue(in_array('.prism/generated/__project_fwd.hpp', $childCandidateHeaders, true), 'child scoped candidate should include the project forward header');
			$this->assertTrue(in_array('.prism/generated/base.hpp', $childCandidateHeaders, true), 'child scoped candidate should include its direct base header');
			$this->assertTrue(in_array('.prism/generated/child.hpp', $childCandidateHeaders, true), 'child scoped candidate should include its own generated header');
			$this->assertSame([], $childDependencySummary['candidate_blocking_reasons'] ?? null, 'child scoped candidate should not have C0 blockers');
			$this->assertSame(['base.phs'], $childDependencySummary['direct_source_dependencies'] ?? null, 'child.phs should record its direct base-class source dependency');
			$this->assertSame(['.prism/generated/base.hpp'], $childDependencySummary['direct_local_headers'] ?? null, 'child.phs should map its direct dependency to the generated base header');
			$this->assertSame(['base.phs'], $this->dependencyCategorySources($childDependencySummary, 'inheritance'), 'child.phs should categorize the base-class dependency as inheritance');
			$mainDependencySummary = $dependencySummaryBySource['main.phs'] ?? null;
			if (!is_array($mainDependencySummary)) {
				throw new RuntimeException('project unit report should contain a main.phs dependency summary');
			}
			$this->assertSame('fallback_broad', $mainDependencySummary['status'] ?? null, 'executable source should keep the active broad fallback status during C1');
			$this->assertSame('blocked_broad_fallback', $mainDependencySummary['candidate_status'] ?? null, 'executable source should stay blocked for scoped-pack activation during C1');
			$this->assertContains('executable body present', implode("\n", is_array($mainDependencySummary['candidate_blocking_reasons'] ?? null) ? $mainDependencySummary['candidate_blocking_reasons'] : []), 'executable source should explain the scoped candidate blocker');

			$sources = is_array($explanation['sources'] ?? null) ? $explanation['sources'] : [];
			$sourceByPath = [];
			foreach ($sources as $source) {
				if (is_array($source) && is_string($source['path'] ?? null)) {
					$sourceByPath[$source['path']] = $source;
				}
			}
			$mainSource = $sourceByPath['main.phs'] ?? null;
			if (!is_array($mainSource)) {
				throw new RuntimeException('build explanation should include the main source record');
			}
			$this->assertSame('main.phs', $mainSource['path'] ?? null, 'source explanation should preserve relative path');
			$this->assertSame('reused', $mainSource['action'] ?? null, 'warm build should reuse unchanged source');
			$this->assertSame('fallback_broad', $mainSource['project_unit_status'] ?? null, 'source explanation should annotate main broad fallback status');
			$this->assertSame('broad_equivalent_pack', $mainSource['project_unit_force_include_mode'] ?? null, 'source explanation should annotate main broad pack mode');
			$this->assertTrue(str_starts_with((string) ($mainSource['project_unit_force_include_header'] ?? ''), '.prism/generated/__project_units/'), 'source explanation should annotate main force-include header');
			$this->assertSame('app', $mainSource['project_module'] ?? null, 'source explanation should annotate main project module membership');
			$mainModuleSurfaceArtifacts = is_array($mainSource['project_module_surface_artifacts'] ?? null) ? $mainSource['project_module_surface_artifacts'] : [];
			$this->assertContains('.prism/cache/project_modules/app-', implode("\n", $mainModuleSurfaceArtifacts), 'source explanation should include the app module surface input');
			$this->assertContains('.prism/cache/project_modules/domain-', implode("\n", $mainModuleSurfaceArtifacts), 'source explanation should include the domain dependency surface input');
			$childSource = $sourceByPath['child.phs'] ?? null;
			if (!is_array($childSource)) {
				throw new RuntimeException('build explanation should include the child source record');
			}
			$this->assertSame('scoped', $childSource['project_unit_status'] ?? null, 'source explanation should annotate child scoped status');
			$this->assertSame('scoped', $childSource['project_unit_force_include_mode'] ?? null, 'source explanation should annotate child scoped pack mode');
			$this->assertSame($childDependencySummary['candidate_pack_header'] ?? null, $childSource['project_unit_force_include_header'] ?? null, 'source explanation should annotate child scoped force-include header');

			$script = normalize_path(resolve_repo_root() . '/bin/scpp.php');
			$explain = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build'], [], 20.0);
			$this->assertSame(0, $explain['exit_code'], 'scpp explain-build should succeed');
			$this->assertContains('Explain build: build', $explain['stdout'], 'explain-build should identify the saved command');
			$this->assertContains('Runtime: reuse (reusing existing runtime artifact by default)', $explain['stdout'], 'explain-build should explain runtime reuse');
			$this->assertContains('Rebuild fanout: outputs 0, objects 0 (generated 0, native 0, runtime 0), project-unit packs changed 0, removed 0, Ninja no-work yes', $explain['stdout'], 'explain-build should summarize warm no-work fanout');
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

			$fanoutView = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build', 'rebuild-fanout'], [], 20.0);
			$this->assertSame(0, $fanoutView['exit_code'], 'scpp explain-build rebuild-fanout should succeed');
			$this->assertContains('Rebuild fanout: outputs 0, objects 0 (generated 0, native 0, runtime 0), project-unit packs changed 0, removed 0, Ninja no-work yes', $fanoutView['stdout'], 'rebuild-fanout should report warm no-work fanout');

			$groupingView = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build', 'grouping'], [], 20.0);
			$this->assertSame(0, $groupingView['exit_code'], 'scpp explain-build grouping should succeed');
			$this->assertContains('Build grouping: incremental (build.grouping_policy, report-only)', $groupingView['stdout'], 'grouping view should show the configured policy and report-only status');
			$this->assertContains('Build grouping fanout: groups 4, changed 0, rebuilt objects 0 (generated 0, native 0)', $groupingView['stdout'], 'grouping view should summarize group/object fanout');
			$this->assertContains('Build groups:', $groupingView['stdout'], 'grouping view should list deterministic groups');
			$this->assertContains('source:root:generated:child.phs', $groupingView['stdout'], 'grouping view should list the child source group');
			$this->assertContains('source:root:native:native_cpp/policy_probe.cpp', $groupingView['stdout'], 'grouping view should list the native source group');

			$projectUnitsView = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build', 'project-units'], [], 20.0);
			$this->assertSame(0, $projectUnitsView['exit_code'], 'scpp explain-build project-units should succeed');
			$this->assertContains('Project unit force-includes: 4/4 unit(s), 3 distinct header(s)', $projectUnitsView['stdout'], 'project-units should summarize force-include fanout');
			$this->assertContains('Project unit scoped fanout: active scoped 2, active broad fallback 1, candidates scoped 2, candidates blocked 1', $projectUnitsView['stdout'], 'project-units should summarize scoped activation fanout');
			$this->assertContains('Project unit native policy: 1/1 native unit(s) broad fallback (native C++ project-unit dependencies are not modeled; native units use broad-equivalent packs)', $projectUnitsView['stdout'], 'project-units should summarize native broad fallback policy');
			$this->assertContains('Project unit candidate blockers: executable body present (1 unit(s))', $projectUnitsView['stdout'], 'project-units should summarize candidate blocker counts');
			$this->assertContains('Project unit pack changes: changed 0, removed 0', $projectUnitsView['stdout'], 'project-units should summarize project-unit pack changes');
			$this->assertContains('Project unit dependency summary artifact: .prism/cache/project_unit_dependency_summary.php (sources 3, STAN no, overrides no)', $projectUnitsView['stdout'], 'project-units should show the dependency summary artifact pointer');
			$this->assertContains('.prism/generated/__project_units/', $projectUnitsView['stdout'], 'project-units should list the force-included hash-pack header');
			$this->assertContains('scoped', $projectUnitsView['stdout'], 'project-units should classify active scoped pack headers');
			$this->assertContains('broad_equivalent_pack', $projectUnitsView['stdout'], 'project-units should classify fallback broad pack headers');
			$this->assertContains('Dependency summaries: 3 unit(s)', $projectUnitsView['stdout'], 'project-units should summarize dependency row count');
			$this->assertContains('child.phs: scoped, candidate candidate_scoped, direct deps 1, direct headers 1, categories inheritance', $projectUnitsView['stdout'], 'project-units should show a compact child dependency summary');
			$this->assertContains('main.phs: fallback_broad, candidate blocked_broad_fallback', $projectUnitsView['stdout'], 'project-units should show a compact main broad-fallback summary');
			$this->assertContains('blockers executable body present', $projectUnitsView['stdout'], 'project-units should show compact blocker evidence');
			$this->assertNotContains('candidate scoped headers:', $projectUnitsView['stdout'], 'project-units should keep verbose header lists out of the compact overview');

			$projectUnitDetailView = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build', 'project-unit', 'child.phs'], [], 20.0);
			$this->assertSame(0, $projectUnitDetailView['exit_code'], 'scpp explain-build project-unit child.phs should succeed');
			$this->assertContains('Dependency summary for child.phs:', $projectUnitDetailView['stdout'], 'project-unit should identify the requested source');
			$this->assertContains('child.phs: scoped', $projectUnitDetailView['stdout'], 'project-unit should list the active child scoped summary');
			$this->assertContains('candidate status: candidate_scoped', $projectUnitDetailView['stdout'], 'project-unit should show scoped candidate status');
			$this->assertContains('candidate pack: .prism/generated/__project_units/scoped-', $projectUnitDetailView['stdout'], 'project-unit should show scoped candidate pack paths');
			$this->assertContains('candidate scoped headers:', $projectUnitDetailView['stdout'], 'project-unit should show scoped candidate header lists');
			$this->assertContains('direct source dependencies: base.phs', $projectUnitDetailView['stdout'], 'project-unit should show the child direct source dependency');
			$this->assertContains('direct local headers: .prism/generated/base.hpp', $projectUnitDetailView['stdout'], 'project-unit should show the child direct generated header dependency');
			$this->assertContains('dependency categories: inheritance: base.phs', $projectUnitDetailView['stdout'], 'project-unit should show categorized child dependency evidence');

			$missingProjectUnitView = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build', 'project-unit', 'missing.phs'], [], 20.0);
			$this->assertSame(0, $missingProjectUnitView['exit_code'], 'scpp explain-build project-unit missing.phs should still produce a focused report');
			$this->assertContains('Dependency summary for missing.phs: not found', $missingProjectUnitView['stdout'], 'project-unit should clearly report a missing requested source');

			$modulesView = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build', 'modules'], [], 20.0);
			$this->assertSame(0, $modulesView['exit_code'], 'scpp explain-build modules should succeed');
			$this->assertContains('Project modules: 2 module(s), 3/3 generated source(s) assigned, unassigned 0', $modulesView['stdout'], 'modules view should summarize configured module coverage');
			$this->assertContains('Project module cache: hits 2, new 0, interface changed 0, implementation changed 0', $modulesView['stdout'], 'modules view should summarize warm module cache hits');
			$this->assertContains('Project module rows:', $modulesView['stdout'], 'modules view should list module rows');
			$this->assertContains('domain: sources 2, deps none, cache hit', $modulesView['stdout'], 'modules view should show the domain module');
			$this->assertContains('app: sources 1, deps domain, cache hit', $modulesView['stdout'], 'modules view should show the app module dependency');

			$moduleDetailView = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build', 'module', 'app'], [], 20.0);
			$this->assertSame(0, $moduleDetailView['exit_code'], 'scpp explain-build module app should succeed');
			$this->assertContains('Project module detail:', $moduleDetailView['stdout'], 'module detail view should include a detail header');
			$this->assertContains('app: sources 1, deps domain, cache hit', $moduleDetailView['stdout'], 'module detail view should show the requested app module');
			$this->assertContains('sources: main.phs', $moduleDetailView['stdout'], 'module detail view should list module sources');
			$buildNinjaText = $this->read($project . '/.prism/build/build.ninja');
			$this->assertContains('../cache/project_modules/domain-', $buildNinjaText, 'build.ninja should use module surface artifacts as generated-object compile inputs');
			$this->assertContains('.surface.json', $buildNinjaText, 'build.ninja should reference public surface artifacts, not private implementation artifacts');
			$this->assertNotContains('.implementation.json', $buildNinjaText, 'build.ninja should not rebuild consumers from private implementation artifacts');

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
			$this->assertContains('main.phs -> .prism/generated/main.cpp -> .prism/build/main.o (project unit: broad_equivalent_pack .prism/generated/__project_units/', $generatedFilesView['stdout'], 'generated-files should map main source to generated outputs and active broad pack');
			$this->assertContains('child.phs -> .prism/generated/child.cpp -> .prism/build/child.o (project unit: scoped .prism/generated/__project_units/scoped-', $generatedFilesView['stdout'], 'generated-files should map child source to generated outputs and active scoped pack');

			$ninjaTargetView = scpp_run_optional_command($project, [PHP_BINARY, $script, 'explain-build', 'ninja-target'], [], 20.0);
			$this->assertSame(0, $ninjaTargetView['exit_code'], 'scpp explain-build ninja-target should succeed');
			$this->assertContains('Direct Ninja target: main', $ninjaTargetView['stdout'], 'ninja-target should list the direct Ninja target');
			$this->assertContains('Use `main` as the Ninja target, not `.prism/build/main`.', $ninjaTargetView['stdout'], 'ninja-target should explicitly contrast the target and executable path');

			$packDir = $project . '/.prism/generated/__project_units';
			$staleScopedPack = $packDir . '/scoped-deadbeefdeadbeef.hpp';
			$staleBroadPack = $packDir . '/0123456789abcdef.hpp';
			$customPack = $packDir . '/custom.hpp';
			$this->write($staleScopedPack, "#pragma once\n");
			$this->write($staleBroadPack, "#pragma once\n");
			$this->write($customPack, "#pragma once\n");

			$noStanWarmBuild = scpp_run_build_service($project, $project . '/prism.json', ['disable_stan' => true]);
			$this->assertSame(true, $noStanWarmBuild['ok'], 'warm --no-stan build should still succeed after a STAN-backed scoped-pack build');
			$this->assertFileMissing($staleScopedPack, 'warm --no-stan build should remove stale scoped pack headers');
			$this->assertFileMissing($staleBroadPack, 'warm --no-stan build should remove stale broad hash-pack headers');
			$this->assertFileExists($customPack, 'project unit pack cleanup should not remove non-build-owned files');
			$packManifest = json_decode($this->read($packDir . '/manifest.json'), true);
			if (!is_array($packManifest)) {
				throw new RuntimeException('project unit pack manifest should decode as an object');
			}
			$manifestHeaders = is_array($packManifest['pack_headers'] ?? null) ? $packManifest['pack_headers'] : [];
			$this->assertTrue(in_array('.prism/generated/__project_units/broad.hpp', $manifestHeaders, true), 'project unit pack manifest should keep the broad alias header');
			$this->assertTrue(!in_array('.prism/generated/__project_units/scoped-deadbeefdeadbeef.hpp', $manifestHeaders, true), 'project unit pack manifest should omit stale scoped headers');
			$noStanBuildReport = json_decode($this->read($project . '/.prism/last_run.json'), true);
			if (!is_array($noStanBuildReport)) {
				throw new RuntimeException('no-STAN last_run.json should decode as an object');
			}
			$noStanBuildDetails = is_array($noStanBuildReport['details'] ?? null) ? $noStanBuildReport['details'] : [];
			$noStanBuildExplanation = is_array($noStanBuildDetails['build_explanation'] ?? null) ? $noStanBuildDetails['build_explanation'] : [];
			$noStanProjectUnits = is_array($noStanBuildExplanation['project_unit_force_includes'] ?? null) ? $noStanBuildExplanation['project_unit_force_includes'] : [];
			$this->assertProjectUnitReportShape($noStanProjectUnits, 'warm no-STAN project-unit report');
			$noStanSummaryArtifact = is_array($noStanProjectUnits['dependency_summary_artifact'] ?? null) ? $noStanProjectUnits['dependency_summary_artifact'] : [];
			$this->assertSame('.prism/cache/project_unit_dependency_summary.php', $noStanSummaryArtifact['path'] ?? null, 'warm --no-stan project-unit report should point to the build-owned dependency summary artifact');
			$this->assertSame(3, $noStanSummaryArtifact['source_count'] ?? null, 'warm --no-stan dependency summary artifact should count project sources');
			$this->assertSame(false, $noStanSummaryArtifact['used_stan_dependency_state'] ?? null, 'warm --no-stan dependency summary artifact should record non-STAN inputs');
			$noStanSummaryArtifactState = require $project . '/' . (string) ($noStanSummaryArtifact['path'] ?? '');
			if (!is_array($noStanSummaryArtifactState)) {
				throw new RuntimeException('warm --no-stan project-unit dependency summary artifact should return an object');
			}
			$noStanArtifactFreshness = is_array($noStanSummaryArtifactState['freshness'] ?? null) ? $noStanSummaryArtifactState['freshness'] : [];
			$this->assertSame(false, $noStanArtifactFreshness['used_stan_dependency_state'] ?? null, 'warm --no-stan dependency summary artifact freshness should record non-STAN inputs');
			$noStanPackChanges = is_array($noStanProjectUnits['pack_changes'] ?? null) ? $noStanProjectUnits['pack_changes'] : [];
			$this->assertTrue((int) ($noStanPackChanges['removed_count'] ?? 0) >= 2, 'warm --no-stan build should report stale pack removals');
			$removedPackHeaders = is_array($noStanPackChanges['removed_headers'] ?? null) ? $noStanPackChanges['removed_headers'] : [];
			$this->assertContains('.prism/generated/__project_units/0123456789abcdef.hpp', implode("\n", $removedPackHeaders), 'warm --no-stan build should report stale broad hash-pack removal');
			$this->assertContains('.prism/generated/__project_units/scoped-deadbeefdeadbeef.hpp', implode("\n", $removedPackHeaders), 'warm --no-stan build should report stale scoped-pack removal');
			$noStanFanout = is_array($noStanBuildExplanation['rebuild_fanout'] ?? null) ? $noStanBuildExplanation['rebuild_fanout'] : null;
			if (!is_array($noStanFanout)) {
				throw new RuntimeException('warm --no-stan build explanation should contain rebuild fanout');
			}
			$this->assertRebuildFanoutShape($noStanFanout, 'warm no-STAN rebuild fanout');
			$this->assertSame($noStanPackChanges['removed_count'] ?? null, $noStanFanout['removed_project_unit_pack_count'] ?? null, 'warm --no-stan rebuild fanout should mirror removed project-unit packs');
			$this->assertSame($noStanPackChanges['removed_headers'] ?? null, $noStanFanout['removed_project_unit_pack_headers'] ?? null, 'warm --no-stan rebuild fanout should mirror removed project-unit pack headers');
			$this->assertSame(4, $noStanProjectUnits['total_units'] ?? null, 'warm --no-stan build should still report all generated and native units');
			$this->assertSame(3, $noStanProjectUnits['distinct_headers'] ?? null, 'warm --no-stan build should use build-owned scoped packs for safe units plus broad fallback for main/native');
			$this->assertSame(2, $noStanProjectUnits['active_scoped_units'] ?? null, 'warm --no-stan build should count scoped units from build-owned dependency summaries');
			$this->assertSame(1, $noStanProjectUnits['active_broad_fallback_units'] ?? null, 'warm --no-stan build should keep only executable generated units on broad fallback');
			$this->assertSame(1, $noStanProjectUnits['native_broad_fallback_units'] ?? null, 'warm --no-stan build should keep native units on broad fallback');
			$this->assertSame(2, $noStanProjectUnits['candidate_scoped_units'] ?? null, 'warm --no-stan build should count safe build-owned summaries as scoped candidates');
			$this->assertSame(1, $noStanProjectUnits['candidate_blocked_units'] ?? null, 'warm --no-stan build should count only blocked generated candidates as blocked');
			$this->assertSame([
				['reason' => 'executable body present', 'unit_count' => 1],
			], $noStanProjectUnits['candidate_blocker_counts'] ?? null, 'warm --no-stan build should use build-owned source summaries for scoped activation');
			$this->assertFileExists($project . '/.prism/cache/project_unit_dependency_state.php', 'warm --no-stan build should write a build-owned project unit dependency state');
			$noStanBuildHeaders = is_array($noStanProjectUnits['headers'] ?? null) ? $noStanProjectUnits['headers'] : [];
			$noStanHeaderModes = [];
			foreach ($noStanBuildHeaders as $header) {
				if (is_array($header) && is_string($header['mode'] ?? null)) {
					$noStanHeaderModes[] = $header['mode'];
				}
			}
			$this->assertContains('broad_equivalent_pack', implode("\n", $noStanHeaderModes), 'warm --no-stan build should keep active broad-equivalent pack mode for fallback units');
			$this->assertContains('scoped', implode("\n", $noStanHeaderModes), 'warm --no-stan build should assign scoped pack headers from build-owned summaries');
			$noStanBuildSummaries = is_array($noStanProjectUnits['dependency_summaries'] ?? null) ? $noStanProjectUnits['dependency_summaries'] : [];
			$noStanChildSummary = null;
			foreach ($noStanBuildSummaries as $summary) {
				if (is_array($summary) && ($summary['source'] ?? null) === 'child.phs') {
					$noStanChildSummary = $summary;
					break;
				}
			}
			if (!is_array($noStanChildSummary)) {
				throw new RuntimeException('warm --no-stan project unit report should contain a child.phs dependency summary');
			}
			$this->assertSame('scoped', $noStanChildSummary['status'] ?? null, 'warm --no-stan child unit should use scoped activation from build-owned summaries');
			$this->assertSame('candidate_scoped', $noStanChildSummary['candidate_status'] ?? null, 'warm --no-stan child unit should be a scoped candidate');
			$this->assertSame([], $noStanChildSummary['candidate_blocking_reasons'] ?? null, 'warm --no-stan child unit should not block solely because STAN is bypassed');
			$this->assertNotContains('source summary unavailable', implode("\n", is_array($noStanChildSummary['candidate_blocking_reasons'] ?? null) ? $noStanChildSummary['candidate_blocking_reasons'] : []), 'warm --no-stan child unit should use the build-owned source summary');
			$this->assertSame(['base.phs'], $noStanChildSummary['direct_source_dependencies'] ?? null, 'warm --no-stan child unit should still report direct dependencies from build-owned summaries');
			$this->assertSame(['.prism/generated/base.hpp'], $noStanChildSummary['direct_local_headers'] ?? null, 'warm --no-stan child unit should still map direct dependencies to generated headers');
			$this->assertSame(['base.phs'], $this->dependencyCategorySources($noStanChildSummary, 'inheritance'), 'warm --no-stan child unit should still categorize direct dependency evidence');
			$this->assertContains('build-owned project unit dependency summary available', implode("\n", is_array($noStanChildSummary['reasons'] ?? null) ? $noStanChildSummary['reasons'] : []), 'warm --no-stan child summary should identify the build-owned summary source');

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
			$this->assertProjectUnitReportShape($noStanReport, 'direct no-STAN project-unit report');
			$noStanSummaries = is_array($noStanReport['dependency_summaries'] ?? null) ? $noStanReport['dependency_summaries'] : [];
			$noStanSummary = $noStanSummaries[0] ?? null;
			if (!is_array($noStanSummary)) {
				throw new RuntimeException('no-STAN project unit report should still contain a dependency summary');
			}
			$this->assertSame('fallback_broad', $noStanSummary['status'] ?? null, 'no-STAN dependency summaries should preserve broad fallback status');
			$this->assertSame(0, $noStanReport['active_scoped_units'] ?? null, 'direct no-STAN report should count no active scoped units');
			$this->assertSame(1, $noStanReport['active_broad_fallback_units'] ?? null, 'direct no-STAN report should count one active broad fallback unit');
			$this->assertSame(0, $noStanReport['candidate_scoped_units'] ?? null, 'direct no-STAN report should count no scoped candidates');
			$this->assertSame(1, $noStanReport['candidate_blocked_units'] ?? null, 'direct no-STAN report should count one blocked scoped candidate');
			$this->assertSame([
				['reason' => 'project unit dependency state unavailable', 'unit_count' => 1],
				['reason' => 'source summary unavailable', 'unit_count' => 1],
			], $noStanReport['candidate_blocker_counts'] ?? null, 'direct no-STAN report should count blocker reasons');
			$this->assertSame('blocked_broad_fallback', $noStanSummary['candidate_status'] ?? null, 'no-STAN scoped candidates should be marked blocked');
			$this->assertSame(['.prism/generated/main.hpp'], $noStanSummary['candidate_scoped_headers'] ?? null, 'no-STAN scoped candidate should still report its own header');
			$this->assertContains('project unit dependency state unavailable', implode("\n", is_array($noStanSummary['candidate_blocking_reasons'] ?? null) ? $noStanSummary['candidate_blocking_reasons'] : []), 'no-STAN scoped candidate should explain missing dependency state');
			$this->assertContains('source summary unavailable', implode("\n", is_array($noStanSummary['candidate_blocking_reasons'] ?? null) ? $noStanSummary['candidate_blocking_reasons'] : []), 'no-STAN scoped candidate should explain missing source summary');
			$this->assertContains('missing summary', implode("\n", $this->dependencyCategoryNames($noStanSummary)), 'no-STAN dependency summary should categorize missing source-summary evidence');
			$this->assertContains('STAN dependency state unavailable for this build', implode("\n", is_array($noStanSummary['reasons'] ?? null) ? $noStanSummary['reasons'] : []), 'no-STAN dependency summary should explain missing STAN state');
			$noStanReportView = implode("\n", render_project_unit_force_include_lines($noStanReport, true));
			$this->assertContains('dependency categories: missing summary', $noStanReportView, 'project-units view should show missing-summary category evidence');

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

	/** @return list<string> */
	private function dependencyCategorySources(array $summary, string $category): array
	{
		$sources = [];
		foreach (is_array($summary['dependency_categories'] ?? null) ? $summary['dependency_categories'] : [] as $row) {
			if (!is_array($row) || ($row['category'] ?? null) !== $category) {
				continue;
			}
			foreach (is_array($row['source_dependencies'] ?? null) ? $row['source_dependencies'] : [] as $source) {
				$source = trim((string) $source);
				if ($source !== '') {
					$sources[$source] = true;
				}
			}
		}
		$result = array_keys($sources);
		sort($result, SORT_STRING);
		return $result;
	}

	/** @return list<string> */
	private function dependencyCategoryNames(array $summary): array
	{
		$categories = [];
		foreach (is_array($summary['dependency_categories'] ?? null) ? $summary['dependency_categories'] : [] as $row) {
			if (!is_array($row)) {
				continue;
			}
			$category = trim((string) ($row['category'] ?? ''));
			if ($category !== '') {
				$categories[$category] = true;
			}
		}
		$result = array_keys($categories);
		sort($result, SORT_STRING);
		return $result;
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

	private function assertNotContains(string $needle, string $haystack, string $message): void
	{
		if (str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' contained `' . $needle . '`');
		}
	}

	private function assertBuildGroupingReportShape(array $buildGrouping, string $context): void
	{
		$this->assertKeys([
			'allowed_policies',
			'build_mode',
			'changed_group_count',
			'changed_groups',
			'compile_unit_strategy',
			'deterministic',
			'groups',
			'native_strategy',
			'notes',
			'object_fanout',
			'policy',
			'source',
			'status',
			'total_groups',
		], $buildGrouping, $context . ' top-level keys');

		$objectFanout = is_array($buildGrouping['object_fanout'] ?? null) ? $buildGrouping['object_fanout'] : null;
		if (!is_array($objectFanout)) {
			throw new RuntimeException($context . ' object_fanout should be an object');
		}
		$this->assertKeys(['rebuilt_generated_object_count', 'rebuilt_native_object_count', 'rebuilt_object_count'], $objectFanout, $context . ' object_fanout keys');

		foreach (is_array($buildGrouping['groups'] ?? null) ? $buildGrouping['groups'] : [] as $group) {
			if (!is_array($group)) {
				throw new RuntimeException($context . ' group row should be an object');
			}
			$this->assertKeys([
				'changed',
				'generated_source_count',
				'generated_sources',
				'id',
				'kind',
				'label',
				'native_source_count',
				'native_sources',
				'object_count',
				'objects',
				'project_root',
				'rebuilt_object_count',
				'rebuilt_objects',
			], $group, $context . ' group row keys');
		}
	}

	private function assertProjectModuleReportShape(array $projectModules, string $context): void
	{
		$this->assertKeys([
			'assigned_source_count',
			'cache_status_counts',
			'configured',
			'consumer_rebuild_required_count',
			'duplicate_assignments',
			'generated_source_count',
			'manifest_artifacts',
			'modules',
			'total_modules',
			'unassigned_source_count',
			'unassigned_sources',
		], $projectModules, $context . ' top-level keys');

		$cacheStatusCounts = is_array($projectModules['cache_status_counts'] ?? null) ? $projectModules['cache_status_counts'] : null;
		if (!is_array($cacheStatusCounts)) {
			throw new RuntimeException($context . ' cache_status_counts should be an object');
		}
		$this->assertKeys([
			'hit',
			'implementation_changed',
			'interface_and_implementation_changed',
			'interface_changed',
			'new',
			'unavailable',
		], $cacheStatusCounts, $context . ' cache_status_counts keys');

		foreach (is_array($projectModules['modules'] ?? null) ? $projectModules['modules'] : [] as $module) {
			if (!is_array($module)) {
				throw new RuntimeException($context . ' module row should be an object');
			}
			$this->assertKeys([
				'cache_status',
				'configured_sources',
				'consumer_rebuild_policy',
				'consumer_rebuild_reasons',
				'consumer_rebuild_required',
				'dependencies',
				'implementation_artifacts',
				'implementation_artifact',
				'implementation_changed',
				'implementation_hash',
				'interface_changed',
				'interface_hash',
				'name',
				'project_root',
				'public_exports',
				'source_count',
				'source_roots',
				'sources',
				'surface_artifact',
				'unresolved_dependencies',
			], $module, $context . ' module row keys');
			foreach (is_array($module['sources'] ?? null) ? $module['sources'] : [] as $source) {
				if (!is_array($source)) {
					throw new RuntimeException($context . ' module source row should be an object');
				}
				$this->assertKeys([
					'generated_cpp',
					'generated_header',
					'implementation_hash',
					'interface_hash',
					'object_path',
					'project_root',
					'source',
				], $source, $context . ' module source row keys');
			}
		}
	}

	private function assertProjectUnitReportShape(array $projectUnits, string $context): void
	{
		$this->assertKeys([
			'active_broad_fallback_units',
			'active_scoped_units',
			'candidate_blocked_units',
			'candidate_blocker_counts',
			'candidate_scoped_units',
			'dependency_summary_artifact',
			'dependency_summaries',
			'distinct_headers',
			'headers',
			'native_broad_fallback_units',
			'native_policy',
			'native_units',
			'pack_changes',
			'total_units',
			'units_with_force_include',
		], $projectUnits, $context . ' top-level keys');

		$packChanges = is_array($projectUnits['pack_changes'] ?? null) ? $projectUnits['pack_changes'] : null;
		if (!is_array($packChanges)) {
			throw new RuntimeException($context . ' pack_changes should be an object');
		}
		$this->assertKeys(['changed_count', 'changed_headers', 'removed_count', 'removed_headers'], $packChanges, $context . ' pack_changes keys');

		$nativePolicy = is_array($projectUnits['native_policy'] ?? null) ? $projectUnits['native_policy'] : null;
		if (!is_array($nativePolicy)) {
			throw new RuntimeException($context . ' native_policy should be an object');
		}
		$this->assertKeys(['reason', 'status'], $nativePolicy, $context . ' native_policy keys');

		$summaryArtifact = is_array($projectUnits['dependency_summary_artifact'] ?? null) ? $projectUnits['dependency_summary_artifact'] : null;
		if (!is_array($summaryArtifact)) {
			throw new RuntimeException($context . ' dependency_summary_artifact should be an object');
		}
		$this->assertKeys(['path', 'source_count', 'source_fingerprint', 'source_overrides_active', 'summary_signature', 'used_stan_dependency_state'], $summaryArtifact, $context . ' dependency_summary_artifact keys');

		foreach (is_array($projectUnits['headers'] ?? null) ? $projectUnits['headers'] : [] as $header) {
			if (!is_array($header)) {
				throw new RuntimeException($context . ' header row should be an object');
			}
			$this->assertKeys(['byte_count', 'line_count', 'mode', 'path', 'unit_count'], $header, $context . ' header row keys');
		}
		foreach (is_array($projectUnits['candidate_blocker_counts'] ?? null) ? $projectUnits['candidate_blocker_counts'] : [] as $row) {
			if (!is_array($row)) {
				throw new RuntimeException($context . ' candidate blocker row should be an object');
			}
			$this->assertKeys(['reason', 'unit_count'], $row, $context . ' candidate blocker row keys');
		}
		foreach (is_array($projectUnits['dependency_summaries'] ?? null) ? $projectUnits['dependency_summaries'] : [] as $summary) {
			if (!is_array($summary)) {
				throw new RuntimeException($context . ' dependency summary row should be an object');
			}
			$this->assertKeys([
				'candidate_blocking_reasons',
				'candidate_pack_hash',
				'candidate_pack_header',
				'candidate_scoped_headers',
				'candidate_status',
				'dependency_categories',
				'dependency_export_headers',
				'direct_local_headers',
				'direct_source_dependencies',
				'force_include_header',
				'generated_header',
				'project_root',
				'reasons',
				'scoped_local_headers',
				'source',
				'source_key',
				'status',
				'unresolved_dependency_keys',
			], $summary, $context . ' dependency summary row keys');
			foreach (is_array($summary['dependency_categories'] ?? null) ? $summary['dependency_categories'] : [] as $category) {
				if (!is_array($category)) {
					throw new RuntimeException($context . ' dependency category row should be an object');
				}
				$this->assertKeys(['category', 'kind', 'owner', 'resolution', 'source_dependencies', 'target'], $category, $context . ' dependency category row keys');
			}
		}
	}

	private function assertRebuildFanoutShape(array $fanout, string $context): void
	{
		$this->assertKeys([
			'changed_project_unit_pack_count',
			'changed_project_unit_pack_headers',
			'ninja_no_work',
			'rebuilt_generated_object_count',
			'rebuilt_generated_objects',
			'rebuilt_native_object_count',
			'rebuilt_native_objects',
			'rebuilt_object_count',
			'rebuilt_other_outputs',
			'rebuilt_output_count',
			'rebuilt_runtime_object_count',
			'rebuilt_runtime_objects',
			'removed_project_unit_pack_count',
			'removed_project_unit_pack_headers',
		], $fanout, $context . ' keys');
	}

	private function assertKeys(array $expected, array $actual, string $message): void
	{
		$keys = array_keys($actual);
		sort($keys, SORT_STRING);
		$expected = array_values($expected);
		sort($expected, SORT_STRING);
		$this->assertSame($expected, $keys, $message);
	}

	private function assertFileExists(string $path, string $message): void
	{
		if (!is_file($path)) {
			throw new RuntimeException($message . ' missing file `' . $path . '`');
		}
	}

	private function assertFileMissing(string $path, string $message): void
	{
		if (file_exists($path)) {
			throw new RuntimeException($message . ' unexpected file `' . $path . '`');
		}
	}
}

exit((new ScppExplainBuildTest())->run());

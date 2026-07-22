<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppProjectModulePublicApiValidationTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_project_module_public_api_validation_' . getmypid() . '_' . bin2hex(random_bytes(4)));
	}

	public function run(): int
	{
		try {
			$this->assertReportPolicyRecordsPublicApiEvidence();
			$this->assertFailPolicyStopsOnPublicApiViolation();
			$this->assertEmptyPublicExportsAreUnconstrained();
			$this->assertUnavailableFileEvidenceIsExplicit();
			echo "PASS: scpp project module public API validation\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function assertReportPolicyRecordsPublicApiEvidence(): void
	{
		$project = $this->root . '/report_app';
		$context = $this->buildContext($project, 'report', ['PublicThing', 'MissingThing']);
		$report = collect_project_module_report($project, [$project => $context], $this->generatedUnits($project), $this->dependencyReport(true, 'PrivateThing'));
		$validation = is_array($report['public_api_validation'] ?? null) ? $report['public_api_validation'] : [];
		$this->assertSame('report', $validation['policy'] ?? null, 'validation should preserve report policy');
		$this->assertSame('violations', $validation['status'] ?? null, 'private module evidence should mark public API violations');
		$this->assertSame('build', $validation['evidence_source'] ?? null, 'no-STAN evidence should be reported as build-owned');
		$this->assertSame(1, $validation['resolved_public_export_count'] ?? null, 'validation should count resolved public exports');
		$this->assertSame(1, $validation['unknown_public_export_count'] ?? null, 'validation should count unknown public exports');
		$this->assertSame(1, $validation['private_dependency_count'] ?? null, 'validation should count private dependency uses');

		$unknown = is_array($validation['unknown_public_exports'] ?? null) ? $validation['unknown_public_exports'] : [];
		$this->assertSame('domain', $unknown[0]['module'] ?? null, 'domain should own the unknown export');
		$this->assertSame('MissingThing', $unknown[0]['export'] ?? null, 'unknown export should be reported');
		$private = is_array($validation['private_dependencies'] ?? null) ? $validation['private_dependencies'] : [];
		$this->assertSame('app', $private[0]['module'] ?? null, 'app should be the violating module');
		$this->assertSame('domain', $private[0]['dependency'] ?? null, 'domain should be the private dependency owner');
		$this->assertSame('PrivateThing', $private[0]['target'] ?? null, 'private target should be reported');
		$this->assertSame(['main.phs'], $private[0]['sources'] ?? null, 'validation should point at source evidence');

		$modules = $this->modulesByName($report);
		$domain = $modules['domain'] ?? null;
		$app = $modules['app'] ?? null;
		if (!is_array($domain) || !is_array($app)) {
			throw new RuntimeException('domain and app modules should be present');
		}
		$this->assertSame('violations', $domain['public_api_validation_status'] ?? null, 'domain module should carry unknown export status');
		$this->assertSame(['PublicThing'], $domain['resolved_public_exports'] ?? null, 'domain module should list resolved exports');
		$this->assertSame(['MissingThing'], $domain['unknown_public_exports'] ?? null, 'domain module should list unknown exports');
		$this->assertSame('violations', $app['public_api_validation_status'] ?? null, 'app module should carry private dependency status');
		$this->assertSame(['domain:PrivateThing'], $app['private_dependency_violations'] ?? null, 'app module should list private dependency evidence');

		$lines = implode("\n", render_project_module_report_lines($report, true));
		$this->assertContains('Project module public API validation: violations (policy report, evidence build), resolved exports 1, unknown exports 1, private deps 1', $lines, 'module view should summarize public API validation');
		$this->assertContains('resolved public exports: PublicThing', $lines, 'module view should list resolved exports');
		$this->assertContains('unknown public exports: MissingThing', $lines, 'module view should list unknown exports');
		$this->assertContains('private dependency violations: domain:PrivateThing', $lines, 'module view should list private dependency violations');
	}

	private function assertFailPolicyStopsOnPublicApiViolation(): void
	{
		$project = $this->root . '/fail_app';
		$context = $this->buildContext($project, 'fail', ['PublicThing']);
		$generatedUnits = $this->generatedUnits($project);
		$dependencyReport = $this->dependencyReport(true, 'PrivateThing');
		$this->assertFails(static function () use ($project, $context, $generatedUnits, $dependencyReport): void {
			collect_project_module_report($project, [$project => $context], $generatedUnits, $dependencyReport);
		}, 'Project module public API validation failed');
	}

	private function assertEmptyPublicExportsAreUnconstrained(): void
	{
		$project = $this->root . '/unconstrained_app';
		$context = $this->buildContext($project, 'report', []);
		$report = collect_project_module_report($project, [$project => $context], $this->generatedUnits($project), $this->dependencyReport(true, 'PrivateThing'));
		$validation = is_array($report['public_api_validation'] ?? null) ? $report['public_api_validation'] : [];
		$this->assertSame('ok', $validation['status'] ?? null, 'empty public_exports should leave module surface unconstrained');
		$this->assertSame(0, $validation['unknown_public_export_count'] ?? null, 'empty public_exports should not invent unknown exports');
		$this->assertSame(0, $validation['private_dependency_count'] ?? null, 'empty public_exports should not block cross-module target use');
	}

	private function assertUnavailableFileEvidenceIsExplicit(): void
	{
		$project = $this->root . '/unavailable_app';
		$context = $this->buildContext($project, 'report', ['PublicThing']);
		$report = collect_project_module_report($project, [$project => $context], $this->generatedUnits($project), $this->dependencyReport(false, 'PrivateThing'));
		$validation = is_array($report['public_api_validation'] ?? null) ? $report['public_api_validation'] : [];
		$this->assertSame('unavailable', $validation['status'] ?? null, 'missing file summaries should be explicit');
		$this->assertSame('build', $validation['evidence_source'] ?? null, 'dependency summaries still carry build-owned evidence');
		$this->assertSame(0, $validation['unknown_public_export_count'] ?? null, 'missing file summaries should not invent unknown exports');
		$this->assertSame(0, $validation['private_dependency_count'] ?? null, 'missing file summaries should not invent private dependency uses');
		$this->assertContains('file summary evidence unavailable', implode("\n", is_array($validation['notes'] ?? null) ? $validation['notes'] : []), 'validation should explain missing file evidence');
	}

	/** @param list<string> $domainPublicExports @return array<string,mixed> */
	private function buildContext(string $project, string $publicPolicy, array $domainPublicExports): array
	{
		$generatedDir = $project . '/.prism/generated';
		$buildDir = $project . '/.prism/build';
		$cacheDir = $project . '/.prism/cache';
		$this->mkdir($generatedDir);
		$this->mkdir($buildDir);
		$this->mkdir($cacheDir);
		foreach (['base', 'main'] as $name) {
			$this->write($generatedDir . '/' . $name . '.hpp', '// header ' . $name . "\n");
			$this->write($generatedDir . '/' . $name . '.cpp', '// source ' . $name . "\n");
		}
		return [
			'config' => [
				'project_module_public_policy' => $publicPolicy,
				'project_modules' => [
					[
						'name' => 'domain',
						'sources' => ['base.phs'],
						'public_exports' => $domainPublicExports,
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
			'cache_dir' => $cacheDir,
			'state' => [
				'files' => [],
			],
		];
	}

	/** @return list<array<string,mixed>> */
	private function generatedUnits(string $project): array
	{
		$generatedDir = $project . '/.prism/generated';
		$buildDir = $project . '/.prism/build';
		return [
			$this->generatedUnit($project, $generatedDir, $buildDir, 'base.phs'),
			$this->generatedUnit($project, $generatedDir, $buildDir, 'main.phs'),
		];
	}

	/** @return array<string,mixed> */
	private function generatedUnit(string $project, string $generatedDir, string $buildDir, string $source): array
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
			'is_entrypoint' => $source === 'main.phs',
			'force_include_header' => null,
		];
	}

	/** @return array<string,mixed> */
	private function dependencyReport(bool $includeFileSummaries, string $target): array
	{
		$report = [
			'dependency_summary_artifact' => [
				'path' => '.prism/cache/project_unit_dependency_summary.php',
				'source_count' => 2,
				'used_stan_dependency_state' => false,
			],
			'dependency_summaries' => [
				[
					'source' => 'base.phs',
					'source_key' => 'base.phs',
					'project_root' => '.',
					'direct_source_dependencies' => [],
					'dependency_categories' => [],
				],
				[
					'source' => 'main.phs',
					'source_key' => 'main.phs',
					'project_root' => '.',
					'direct_source_dependencies' => ['base.phs'],
					'dependency_categories' => [
						[
							'category' => 'executable body',
							'kind' => 'executable_body_type',
							'target' => $target,
							'owner' => '',
							'resolution' => 'resolved',
							'source_dependencies' => ['base.phs'],
						],
					],
				],
			],
		];
		if ($includeFileSummaries) {
			$report['file_summaries'] = [
				'base.phs' => [
					'root_classes' => [
						[
							'name' => 'PublicThing',
							'namespace' => '',
							'line' => 1,
							'declaration_kind' => 'class',
						],
						[
							'name' => 'PrivateThing',
							'namespace' => '',
							'line' => 5,
							'declaration_kind' => 'class',
						],
					],
					'root_functions' => [],
					'root_constants' => [],
					'namespaces' => [],
				],
				'main.phs' => [
					'root_classes' => [],
					'root_functions' => [],
					'root_constants' => [],
					'namespaces' => [],
				],
			];
		}
		return $report;
	}

	/** @param array<string,mixed> $report @return array<string,array<string,mixed>> */
	private function modulesByName(array $report): array
	{
		$modules = [];
		foreach (is_array($report['modules'] ?? null) ? $report['modules'] : [] as $module) {
			if (is_array($module) && is_string($module['name'] ?? null)) {
				$modules[(string) $module['name']] = $module;
			}
		}
		return $modules;
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

exit((new ScppProjectModulePublicApiValidationTest())->run());

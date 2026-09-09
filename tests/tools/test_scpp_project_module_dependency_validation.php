<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppProjectModuleDependencyValidationTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_project_module_dependency_validation_' . getmypid() . '_' . bin2hex(random_bytes(4)));
	}

	public function run(): int
	{
		try {
			$this->assertReportPolicyRecordsDependencyEvidence();
			$this->assertFailPolicyStopsOnUndeclaredDependency();
			$this->assertUnavailableEvidenceIsExplicit();
			echo "PASS: scpp project module dependency validation\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function assertReportPolicyRecordsDependencyEvidence(): void
	{
		$project = $this->root . '/report_app';
		$context = $this->buildContext($project, 'report');
		$report = collect_project_module_report($project, [$project => $context], $this->generatedUnits($project), $this->dependencyReport($project, false));
		$validation = is_array($report['dependency_validation'] ?? null) ? $report['dependency_validation'] : [];
		$this->assertSame('report', $validation['policy'] ?? null, 'validation should preserve report policy');
		$this->assertSame('violations', $validation['status'] ?? null, 'undeclared module evidence should mark validation violations');
		$this->assertSame('build', $validation['evidence_source'] ?? null, 'no-STAN evidence should be reported as build-owned');
		$this->assertSame(1, $validation['inferred_dependency_count'] ?? null, 'validation should count inferred module edges');
		$this->assertSame(1, $validation['undeclared_dependency_count'] ?? null, 'validation should count undeclared module edges');
		$this->assertSame(1, $validation['unused_declared_dependency_count'] ?? null, 'validation should count unused declared dependencies');
		$undeclared = is_array($validation['undeclared_dependencies'] ?? null) ? $validation['undeclared_dependencies'] : [];
		$this->assertSame('app', $undeclared[0]['module'] ?? null, 'app should be the violating module');
		$this->assertSame('domain', $undeclared[0]['dependency'] ?? null, 'domain should be the undeclared dependency');
		$this->assertSame(['main.phs'], $undeclared[0]['sources'] ?? null, 'validation should point at source evidence');

		$modules = $this->modulesByName($report);
		$app = $modules['app'] ?? null;
		if (!is_array($app)) {
			throw new RuntimeException('app module should be present');
		}
		$this->assertSame('violations', $app['dependency_validation_status'] ?? null, 'app module should carry validation status');
		$this->assertSame(['domain'], $app['inferred_dependencies'] ?? null, 'app module should list inferred dependencies');
		$this->assertSame(['domain'], $app['undeclared_dependencies'] ?? null, 'app module should list undeclared dependencies');
		$this->assertSame(['util'], $app['unused_declared_dependencies'] ?? null, 'app module should list unused declared dependencies');

		$lines = implode("\n", render_project_module_report_lines($report, true));
		$this->assertContains('Project module dependency validation: violations (policy report, evidence build), inferred 1, undeclared 1, unused declared 1', $lines, 'module view should summarize validation');
		$this->assertContains('undeclared dependencies: domain', $lines, 'module view should list undeclared dependencies');
		$this->assertContains('unused declared dependencies: util', $lines, 'module view should list unused declared dependencies');
	}

	private function assertFailPolicyStopsOnUndeclaredDependency(): void
	{
		$project = $this->root . '/fail_app';
		$context = $this->buildContext($project, 'fail');
		$generatedUnits = $this->generatedUnits($project);
		$dependencyReport = $this->dependencyReport($project, false);
		$this->assertFails(static function () use ($project, $context, $generatedUnits, $dependencyReport): void {
			collect_project_module_report($project, [$project => $context], $generatedUnits, $dependencyReport);
		}, 'Project module dependency validation failed');
	}

	private function assertUnavailableEvidenceIsExplicit(): void
	{
		$project = $this->root . '/unavailable_app';
		$context = $this->buildContext($project, 'report');
		$report = collect_project_module_report($project, [$project => $context], $this->generatedUnits($project), []);
		$validation = is_array($report['dependency_validation'] ?? null) ? $report['dependency_validation'] : [];
		$this->assertSame('unavailable', $validation['status'] ?? null, 'missing dependency summaries should be explicit');
		$this->assertSame('none', $validation['evidence_source'] ?? null, 'missing dependency summaries should use none evidence source');
		$this->assertSame(0, $validation['undeclared_dependency_count'] ?? null, 'missing evidence should not invent violations');
		$this->assertContains('project-unit dependency evidence unavailable', implode("\n", is_array($validation['notes'] ?? null) ? $validation['notes'] : []), 'validation should explain missing evidence');
	}

	/** @return array<string,mixed> */
	private function buildContext(string $project, string $policy): array
	{
		$generatedDir = $project . '/.prism/generated';
		$buildDir = $project . '/.prism/build';
		$cacheDir = $project . '/.prism/cache';
		$this->mkdir($generatedDir);
		$this->mkdir($buildDir);
		$this->mkdir($cacheDir);
		foreach (['base', 'main', 'util'] as $name) {
			$this->write($generatedDir . '/' . $name . '.hpp', '// header ' . $name . "\n");
			$this->write($generatedDir . '/' . $name . '.cpp', '// source ' . $name . "\n");
		}
		return [
			'config' => [
				'project_module_dependency_policy' => $policy,
				'project_modules' => [
					[
						'name' => 'domain',
						'sources' => ['base.phs'],
					],
					[
						'name' => 'app',
						'sources' => ['main.phs'],
						'dependencies' => ['util'],
					],
					[
						'name' => 'util',
						'sources' => ['util.phs'],
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
			$this->generatedUnit($project, $generatedDir, $buildDir, 'util.phs'),
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
	private function dependencyReport(string $project, bool $usedStan): array
	{
		return [
			'dependency_summary_artifact' => [
				'path' => '.prism/cache/project_unit_dependency_summary.php',
				'source_count' => 3,
				'used_stan_dependency_state' => $usedStan,
			],
			'dependency_summaries' => [
				[
					'source' => 'base.phs',
					'source_key' => 'base.phs',
					'project_root' => '.',
					'direct_source_dependencies' => [],
				],
				[
					'source' => 'main.phs',
					'source_key' => 'main.phs',
					'project_root' => '.',
					'direct_source_dependencies' => ['base.phs'],
				],
				[
					'source' => 'util.phs',
					'source_key' => 'util.phs',
					'project_root' => '.',
					'direct_source_dependencies' => [],
				],
			],
		];
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

exit((new ScppProjectModuleDependencyValidationTest())->run());

<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppProjectModuleStanSummaryCacheTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_project_module_stan_summary_cache_' . getmypid() . '_' . bin2hex(random_bytes(4)));
	}

	public function run(): int
	{
		try {
			$this->assertSummaryArtifactsRecordNewHitAndChanged();
			echo "PASS: scpp project module STAN summary cache\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function assertSummaryArtifactsRecordNewHitAndChanged(): void
	{
		$project = $this->root . '/app';
		$context = $this->buildContext($project);
		$generatedUnits = $this->generatedUnits($project);

		$first = collect_project_module_report($project, [$project => $context], $generatedUnits, $this->dependencyReport(false, ['base.phs']));
		$firstModules = $this->modulesByName($first);
		$firstApp = $firstModules['app'] ?? null;
		if (!is_array($firstApp)) {
			throw new RuntimeException('app module should be present in first report');
		}
		$this->assertSame('new', $firstApp['stan_summary_cache_status'] ?? null, 'first app module analysis summary should be new');
		$this->assertSame('build', $firstApp['stan_summary_evidence_source'] ?? null, 'first app module analysis summary should record build-owned evidence');
		$this->assertContains('no previous module analysis summary artifact', implode("\n", is_array($firstApp['stan_summary_cache_reasons'] ?? null) ? $firstApp['stan_summary_cache_reasons'] : []), 'first app summary should explain the cache miss');
		$this->assertFileExists($project . '/' . (string) ($firstApp['stan_summary_artifact'] ?? ''), 'first app summary artifact should be written');
		$firstHash = (string) ($firstApp['stan_summary_hash'] ?? '');
		$this->assertTrue(strlen($firstHash) === 64, 'first app summary hash should be a sha256');

		$artifact = json_decode($this->read($project . '/' . (string) ($firstApp['stan_summary_artifact'] ?? '')), true);
		if (!is_array($artifact)) {
			throw new RuntimeException('app module analysis summary artifact should decode as JSON');
		}
		$this->assertSame('project_module_stan_summary', $artifact['kind'] ?? null, 'artifact should identify the module STAN summary kind');
		$this->assertSame($firstHash, $artifact['stan_summary_hash'] ?? null, 'artifact should persist the summary hash');
		$this->assertSame('build', $artifact['evidence_source'] ?? null, 'artifact should persist evidence source');
		$sources = is_array($artifact['sources'] ?? null) ? $artifact['sources'] : [];
		$this->assertSame(['base.phs'], $sources[0]['direct_source_dependencies'] ?? null, 'artifact should persist direct source dependencies');

		$second = collect_project_module_report($project, [$project => $context], $generatedUnits, $this->dependencyReport(false, ['base.phs']));
		$secondApp = $this->modulesByName($second)['app'] ?? null;
		if (!is_array($secondApp)) {
			throw new RuntimeException('app module should be present in second report');
		}
		$this->assertSame('hit', $secondApp['stan_summary_cache_status'] ?? null, 'second app module analysis summary should be a cache hit');
		$this->assertSame($firstHash, $secondApp['stan_summary_hash'] ?? null, 'second app module summary hash should be unchanged');
		$this->assertContains('module dependency summary hash unchanged', implode("\n", is_array($secondApp['stan_summary_cache_reasons'] ?? null) ? $secondApp['stan_summary_cache_reasons'] : []), 'second app summary should explain the hit');

		$third = collect_project_module_report($project, [$project => $context], $generatedUnits, $this->dependencyReport(false, ['base.phs', 'util.phs']));
		$thirdApp = $this->modulesByName($third)['app'] ?? null;
		if (!is_array($thirdApp)) {
			throw new RuntimeException('app module should be present in third report');
		}
		$this->assertSame('changed', $thirdApp['stan_summary_cache_status'] ?? null, 'changed app dependency evidence should change the summary cache');
		$this->assertNotSame($firstHash, (string) ($thirdApp['stan_summary_hash'] ?? ''), 'changed app dependency evidence should change the summary hash');
		$this->assertContains('module dependency summary hash changed', implode("\n", is_array($thirdApp['stan_summary_cache_reasons'] ?? null) ? $thirdApp['stan_summary_cache_reasons'] : []), 'changed app summary should explain the cache miss');
	}

	/** @return array<string,mixed> */
	private function buildContext(string $project): array
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
				'project_modules' => [
					[
						'name' => 'domain',
						'sources' => ['base.phs'],
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

	/** @param list<string> $mainDependencies @return array<string,mixed> */
	private function dependencyReport(bool $usedStan, array $mainDependencies): array
	{
		return [
			'dependency_summary_artifact' => [
				'path' => '.prism/cache/project_unit_dependency_summary.php',
				'summary_signature' => 'synthetic-v1',
				'source_fingerprint' => 'ignored-by-module-summary-cache',
				'source_count' => 2,
				'used_stan_dependency_state' => $usedStan,
				'source_overrides_active' => false,
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
					'direct_source_dependencies' => $mainDependencies,
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

	private function assertSame(mixed $expected, mixed $actual, string $message): void
	{
		if ($expected !== $actual) {
			throw new RuntimeException($message . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
		}
	}

	private function assertNotSame(mixed $unexpected, mixed $actual, string $message): void
	{
		if ($unexpected === $actual) {
			throw new RuntimeException($message . ': both were ' . var_export($actual, true));
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
			throw new RuntimeException($message . ': missing `' . $needle . '` in `' . $haystack . '`');
		}
	}

	private function assertFileExists(string $path, string $message): void
	{
		if (!is_file($path)) {
			throw new RuntimeException($message . ': ' . $path);
		}
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
				continue;
			}
			@unlink($entry->getPathname());
		}
		@rmdir($path);
	}
}

exit((new ScppProjectModuleStanSummaryCacheTest())->run());

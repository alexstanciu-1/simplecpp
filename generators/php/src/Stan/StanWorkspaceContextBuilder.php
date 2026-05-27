<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanWorkspaceContextBuilder
{
	public function __construct(
		private readonly StanRuntimeProfilePreparer $runtimeProfilePreparer = new StanRuntimeProfilePreparer(),
		private readonly StanSourceCatalogBuilder $sourceCatalogBuilder = new StanSourceCatalogBuilder(),
	)
	{
	}

	/** @param array<string,string> $sourceOverrides */
	public function build(string $projectRoot, string $configPath, array $sourceOverrides = []): StanWorkspaceContext
	{
		$config = \load_project_config($configPath);
		$projectGraph = \resolve_project_dependency_graph($projectRoot, $configPath, $config);
		$projectContexts = \build_project_contexts($projectGraph);
		$repoRoot = \resolve_repo_root();
		$runtimeProfile = $this->runtimeProfilePreparer->prepare($repoRoot, $config);
		$phpProfile = $runtimeProfile['php_profile'];
		$runtimeShallowSources = $runtimeProfile['runtime_shallow_sources'];
		$activeRuntimeShallowPath = $runtimeProfile['active_runtime_shallow_path'];
		$normalizedOverrides = [];
		foreach ($sourceOverrides as $path => $contents) {
			$normalizedOverrides[\normalize_path($path)] = $contents;
		}
		$sourceUnits = $this->sourceCatalogBuilder->build($projectRoot, $projectContexts, [\normalize_path($activeRuntimeShallowPath)], $normalizedOverrides);
		$sourceFingerprint = $this->computeSourceFingerprint($projectGraph, $sourceUnits);
		$stanSignature = $this->computeStanSignature($repoRoot, $phpProfile);
		$statePath = \normalize_path($projectRoot . '/.prism/cache/' . \SCPP_STAN_STATE_FILE);
		$cacheDir = \normalize_path($projectRoot . '/.prism/cache/stan/files');

		\ensure_directory(dirname($statePath));
		\ensure_directory($cacheDir);

		return new StanWorkspaceContext(
			projectRoot: $projectRoot,
			configPath: $configPath,
			config: $config,
			projectGraph: $projectGraph,
			projectContexts: $projectContexts,
			repoRoot: $repoRoot,
			phpProfile: $phpProfile,
			runtimeShallowSources: $runtimeShallowSources,
			activeRuntimeShallowPath: $activeRuntimeShallowPath,
			sourceUnits: $sourceUnits,
			sourceFingerprint: $sourceFingerprint,
			stanSignature: $stanSignature,
			statePath: $statePath,
			cacheDir: $cacheDir,
		);
	}

	/** @param array<string,mixed> $projectGraph @param list<StanSourceUnit> $sourceUnits */
	private function computeSourceFingerprint(array $projectGraph, array $sourceUnits): string
	{
		$parts = [];
		foreach ($projectGraph as $projectSpec) {
			if (!is_array($projectSpec)) {
				continue;
			}
			$configPath = \normalize_path((string) ($projectSpec['config_path'] ?? ''));
			if ($configPath === '') {
				continue;
			}
			$configHash = is_file($configPath) ? hash_file('sha256', $configPath) : false;
			$parts[] = $configPath . ':' . ($configHash === false ? 'missing' : $configHash);
		}
		foreach ($sourceUnits as $sourceUnit) {
			$parts[] = \normalize_path($sourceUnit->path) . ':' . $sourceUnit->meta['content_hash'];
		}
		sort($parts, SORT_STRING);
		return hash('sha256', implode("\n", $parts));
	}

	private function computeStanSignature(string $repoRoot, string $phpProfile = 'legacy'): string
	{
		$parts = [
			'version:' . \SCPP_STAN_SIGNATURE_VERSION,
			'php_profile:' . strtolower(trim($phpProfile)),
		];

		$files = [
			$repoRoot . '/bin/project_services.php',
			$repoRoot . '/generators/php/src/Analysis/FrontEndSymbolExtractor.php',
			$repoRoot . '/generators/php/src/Analysis/RuntimeShallowSourceGenerator.php',
			$repoRoot . '/generators/php/src/Stan/StanPathMapper.php',
			$repoRoot . '/generators/php/src/Stan/StanSymbolIndexBuilder.php',
			$repoRoot . '/generators/php/src/Stan/StanDependencyResolver.php',
			$repoRoot . '/generators/php/src/Stan/StanDiagnosticCollector.php',
			$repoRoot . '/generators/php/src/Stan/StanDiagnosticEnricher.php',
			$repoRoot . '/generators/php/src/Stan/StanExpressionTypeResolver.php',
			$repoRoot . '/generators/php/src/Stan/StanSourceUnit.php',
			$repoRoot . '/generators/php/src/Stan/StanSourceMetaBuilder.php',
			$repoRoot . '/generators/php/src/Stan/StanSourceCatalogBuilder.php',
			$repoRoot . '/generators/php/src/Stan/StanWorkspaceContext.php',
			$repoRoot . '/generators/php/src/Stan/StanWorkspaceContextBuilder.php',
			$repoRoot . '/generators/php/src/Stan/StanWorkspaceSession.php',
			$repoRoot . '/generators/php/src/Stan/StanRunner.php',
			$repoRoot . '/generators/php/src/Stan/StanStateStore.php',
			$repoRoot . '/generators/php/src/Loader/InputLoader.php',
			$repoRoot . '/generators/php/src/Builder/IrBuilder.php',
			$repoRoot . '/generators/php/specs/php_runtime_symbols_legacy.json',
			$repoRoot . '/generators/php/specs/php_runtime_symbols_strict.json',
		];

		foreach ($files as $file) {
			if (!is_file($file)) {
				$parts[] = 'missing:' . \normalize_config_path($file);
				continue;
			}
			$hash = hash_file('sha256', $file);
			$parts[] = \normalize_config_path($file) . ':' . ($hash === false ? 'hash-failed' : $hash);
		}

		return hash('sha256', implode("\n", $parts));
	}
}

<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanRunner
{
	public function __construct(
		private readonly StanStateStore $stateStore = new StanStateStore(),
		private readonly StanRuntimeProfilePreparer $runtimeProfilePreparer = new StanRuntimeProfilePreparer(),
		private readonly StanFilePass $filePass = new StanFilePass(),
		private readonly StanWarningPresenter $warningPresenter = new StanWarningPresenter(),
		private readonly StanResultAssembler $resultAssembler = new StanResultAssembler(),
		private readonly StanSemanticPass $semanticPass = new StanSemanticPass(),
	)
	{
	}

	/** @return array{project_root:string,php_profile:string,source_unit_count:int,analyzed_count:int,reused_count:int,warning_count:int,duplicate_count:int,resolution_warning_count:int,override_warning_count:int,return_chain_warning_count:int,expression_chain_warning_count:int,local_type_warning_count:int,property_type_warning_count:int,property_read_warning_count:int,initialization_warning_count:int,call_site_warning_count:int,return_type_warning_count:int,symbol_count:int,state_path:string,runtime_shallow_sources:list<array{profile:string,path:string,generated:int,skipped:list<string>}>,warning_samples:list<string>} */
	public function run(string $projectRoot, string $configPath): array
	{
		$config = \load_project_config($configPath);
		$projectGraph = \resolve_project_dependency_graph($projectRoot, $configPath, $config);
		$projectContexts = \build_project_contexts($projectGraph);
		$repoRoot = \resolve_repo_root();
		$runtimeProfile = $this->runtimeProfilePreparer->prepare($repoRoot, $config);
		$phpProfile = $runtimeProfile['php_profile'];
		$runtimeShallowSources = $runtimeProfile['runtime_shallow_sources'];
		$activeRuntimeShallowPath = $runtimeProfile['active_runtime_shallow_path'];
		$stanSignature = $this->computeStanSignature($repoRoot, $phpProfile);

		$statePath = \normalize_path($projectRoot . '/.prism/cache/' . \SCPP_STAN_STATE_FILE);
		$cacheDir = \normalize_path($projectRoot . '/.prism/cache/stan/files');
		\ensure_directory(dirname($statePath));
		\ensure_directory($cacheDir);
		$state = $this->stateStore->load($statePath);
		$filePassResult = $this->filePass->analyze(
			$projectRoot,
			$statePath,
			$cacheDir,
			$stanSignature,
			$state,
			$projectContexts,
			[\normalize_path($activeRuntimeShallowPath)],
		);
		$files = $filePassResult['files'];
		$fileSummaries = $filePassResult['file_summaries'];
		$newFilesState = $filePassResult['files_state'];
		$analyzedCount = $filePassResult['analyzed_count'];
		$reusedCount = $filePassResult['reused_count'];
		$warningCount = $filePassResult['warning_count'];

		$semanticResult = $this->semanticPass->analyze($fileSummaries, $projectRoot);
		$symbolIndex = $semanticResult['symbol_index'];
		$duplicateDiagnostics = $semanticResult['duplicate_diagnostics'];
		$resolutionDiagnostics = $semanticResult['resolution_diagnostics'];
		$overrideDiagnostics = $semanticResult['override_diagnostics'];
		$returnChainTypes = $semanticResult['return_chain_types'];
		$returnChainDiagnostics = $semanticResult['return_chain_diagnostics'];
		$expressionChainTypes = $semanticResult['expression_chain_types'];
		$expressionChainDiagnostics = $semanticResult['expression_chain_diagnostics'];
		$localTypeDiagnostics = $semanticResult['local_type_diagnostics'];
		$propertyTypeDiagnostics = $semanticResult['property_type_diagnostics'];
		$propertyReadDiagnostics = $semanticResult['property_read_diagnostics'];
		$initializationDiagnostics = $semanticResult['initialization_diagnostics'];
		$callSiteDiagnostics = $semanticResult['call_site_diagnostics'];
		$returnTypeDiagnostics = $semanticResult['return_type_diagnostics'];
		$fileDependencyKeys = $semanticResult['file_dependency_keys'];
		$warningCount += (int) ($semanticResult['warning_count'] ?? 0);
		foreach ($newFilesState as $sourceKey => $fileState) {
			$newFilesState[$sourceKey]['dependency_keys'] = $fileDependencyKeys[$sourceKey] ?? [];
		}

		$state = $this->resultAssembler->buildState(
			$projectRoot,
			$phpProfile,
			$symbolIndex,
			$duplicateDiagnostics,
			$resolutionDiagnostics,
			$overrideDiagnostics,
			$returnChainTypes,
			$returnChainDiagnostics,
			$expressionChainTypes,
			$expressionChainDiagnostics,
			$localTypeDiagnostics,
			$propertyTypeDiagnostics,
			$propertyReadDiagnostics,
			$initializationDiagnostics,
			$callSiteDiagnostics,
			$returnTypeDiagnostics,
			$newFilesState,
			$activeRuntimeShallowPath,
		);
		$this->stateStore->save($statePath, $state);

		return $this->resultAssembler->buildRunResult(
			$projectRoot,
			$phpProfile,
			count($files),
			$analyzedCount,
			$reusedCount,
			$warningCount,
			$symbolIndex,
			$duplicateDiagnostics,
			$resolutionDiagnostics,
			$overrideDiagnostics,
			$returnChainDiagnostics,
			$expressionChainDiagnostics,
			$localTypeDiagnostics,
			$propertyTypeDiagnostics,
			$propertyReadDiagnostics,
			$initializationDiagnostics,
			$callSiteDiagnostics,
			$returnTypeDiagnostics,
			$statePath,
			$runtimeShallowSources,
			$semanticResult['warning_samples'],
		);
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
			$repoRoot . '/generators/php/src/Stan/StanExpressionTypeResolver.php',
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

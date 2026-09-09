<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanWorkspaceSession
{
	public function __construct(
		private readonly StanStateStore $stateStore = new StanStateStore(),
		private readonly StanWorkspaceContextBuilder $contextBuilder = new StanWorkspaceContextBuilder(),
		private readonly StanFilePass $filePass = new StanFilePass(),
		private readonly StanResultAssembler $resultAssembler = new StanResultAssembler(),
		private readonly StanSemanticPass $semanticPass = new StanSemanticPass(),
		private readonly StanPositionResolver $positionResolver = new StanPositionResolver(),
	)
	{
	}

	/** @return array{project_root:string,php_profile:string,source_unit_count:int,analyzed_count:int,reused_count:int,warning_count:int,duplicate_count:int,resolution_warning_count:int,override_warning_count:int,return_chain_warning_count:int,expression_chain_warning_count:int,local_type_warning_count:int,property_type_warning_count:int,property_read_warning_count:int,initialization_warning_count:int,call_site_warning_count:int,return_type_warning_count:int,symbol_count:int,state_path:string,runtime_shallow_sources:list<array{profile:string,path:string,generated:int,skipped:list<string>}>,warning_samples:list<string>} */
	public function run(string $projectRoot, string $configPath): array
	{
		return $this->runWithOverrides($projectRoot, $configPath, []);
	}

	/** @param array<string,string> $sourceOverrides @return array<string,mixed> */
	public function runDiagnostics(string $projectRoot, string $configPath, array $sourceOverrides = []): array
	{
		return $this->buildDiagnosticsResultFromSnapshot($this->createBridgeSnapshot($projectRoot, $configPath, $sourceOverrides));
	}

	/** @param array<string,string> $sourceOverrides @return array<string,mixed> */
	public function runBuildGateDiagnostics(string $projectRoot, string $configPath, array $sourceOverrides = []): array
	{
		return $this->buildDiagnosticsResultFromSnapshot($this->createBridgeSnapshot($projectRoot, $configPath, $sourceOverrides, 'build_gate'));
	}

	/** @param array<string,string> $sourceOverrides @return array<string,mixed> */
	public function runDocumentSymbols(string $projectRoot, string $configPath, string $documentPath, array $sourceOverrides = []): array
	{
		return $this->buildDocumentSymbolsResultFromSnapshot($this->createBridgeSnapshot($projectRoot, $configPath, $sourceOverrides), $documentPath);
	}

	/** @param array<string,string> $sourceOverrides @return array<string,mixed> */
	public function runHover(string $projectRoot, string $configPath, string $documentPath, int $line, ?int $column = null, array $sourceOverrides = []): array
	{
		return $this->buildHoverResultFromSnapshot($this->createBridgeSnapshot($projectRoot, $configPath, $sourceOverrides), $documentPath, $line, $column);
	}

	/** @param array<string,string> $sourceOverrides @return array<string,mixed> */
	public function runDefinition(string $projectRoot, string $configPath, string $documentPath, int $line, ?int $column = null, array $sourceOverrides = []): array
	{
		return $this->buildDefinitionResultFromSnapshot($this->createBridgeSnapshot($projectRoot, $configPath, $sourceOverrides), $documentPath, $line, $column);
	}

	/** @param array<string,string> $sourceOverrides @return array<string,mixed> */
	public function runReferences(string $projectRoot, string $configPath, string $documentPath, int $line, ?int $column = null, array $sourceOverrides = []): array
	{
		return $this->buildReferencesResultFromSnapshot($this->createBridgeSnapshot($projectRoot, $configPath, $sourceOverrides), $documentPath, $line, $column);
	}

	/** @param array<string,string> $sourceOverrides @return array<string,mixed> */
	public function createBridgeSnapshot(string $projectRoot, string $configPath, array $sourceOverrides = [], string $analysisMode = 'full'): array
	{
		[$context, $filePassResult, $semanticResult, $warningCount, $timings] = $this->analyzeWorkspace($projectRoot, $configPath, $sourceOverrides, $analysisMode);

		$diagnosticAssemblyStart = microtime(true);
		$allDiagnostics = $this->resultAssembler->flattenDiagnostics(
			$semanticResult['duplicate_diagnostics'],
			$semanticResult['resolution_diagnostics'],
			$semanticResult['override_diagnostics'],
			$semanticResult['return_chain_diagnostics'],
			$semanticResult['expression_chain_diagnostics'],
			$semanticResult['local_type_diagnostics'],
			$semanticResult['property_type_diagnostics'],
			$semanticResult['property_read_diagnostics'],
			$semanticResult['initialization_diagnostics'],
			$semanticResult['call_site_diagnostics'],
			$semanticResult['return_type_diagnostics'],
			$semanticResult['frontend_diagnostics'] ?? [],
		);
		$diagnosticsByPath = $this->resultAssembler->groupDiagnosticsByPath($allDiagnostics);
		$timings['diagnostic_assembly_ms'] = $this->elapsedMilliseconds($diagnosticAssemblyStart);

		return [
			'context' => $context,
			'file_pass_result' => $filePassResult,
			'semantic_result' => $semanticResult,
			'warning_count' => $warningCount,
			'all_diagnostics' => $allDiagnostics,
			'diagnostics_by_path' => $diagnosticsByPath,
			'debug' => [
				'analyzed_count' => (int) ($filePassResult['analyzed_count'] ?? 0),
				'reused_count' => (int) ($filePassResult['reused_count'] ?? 0),
				'source_unit_count' => count($filePassResult['files'] ?? []),
				'warning_count' => $warningCount,
				'timings_ms' => $timings,
				'analysis_mode' => $analysisMode,
			],
		];
	}

	/** @param array<string,mixed> $snapshot @return array<string,mixed> */
	public function buildDiagnosticsResultFromSnapshot(array $snapshot): array
	{
		$context = $snapshot['context'];
		$filePassResult = $snapshot['file_pass_result'];
		$semanticResult = $snapshot['semantic_result'];
		$result = $this->resultAssembler->buildSessionDiagnosticsResult(
			$context->projectRoot,
			$context->phpProfile,
			count($filePassResult['files']),
			$filePassResult['analyzed_count'],
			$filePassResult['reused_count'],
			(int) ($snapshot['warning_count'] ?? 0),
			$context->statePath,
			$context->runtimeShallowSources,
			$semanticResult['warning_samples'],
			$snapshot['all_diagnostics'],
			$snapshot['diagnostics_by_path'],
		);
		$debug = is_array($snapshot['debug'] ?? null) ? $snapshot['debug'] : [];
		$result['timings_ms'] = is_array($debug['timings_ms'] ?? null) ? $debug['timings_ms'] : [];
		$result['analysis_mode'] = (string) ($debug['analysis_mode'] ?? 'full');
		return $result;
	}

	/** @param array<string,mixed> $snapshot @return array<string,mixed> */
	public function buildDocumentSymbolsResultFromSnapshot(array $snapshot, string $documentPath): array
	{
		$context = $snapshot['context'];
		$semanticResult = $snapshot['semantic_result'];
		$normalizedPath = \normalize_path($documentPath);
		$documentSymbols = $this->buildDocumentSymbols($semanticResult['symbol_index'], $normalizedPath);
		return $this->resultAssembler->buildDocumentSymbolsResult(
			$context->projectRoot,
			$context->phpProfile,
			$normalizedPath,
			$documentSymbols,
		);
	}

	/** @param array<string,mixed> $snapshot @return array<string,mixed> */
	public function buildHoverResultFromSnapshot(array $snapshot, string $documentPath, int $line, ?int $column = null): array
	{
		$context = $snapshot['context'];
		$semanticResult = $snapshot['semantic_result'];
		$normalizedPath = \normalize_path($documentPath);
		$hover = $this->buildHover($semanticResult['symbol_index'], $semanticResult, $normalizedPath, $line, $column);
		return $this->resultAssembler->buildHoverResult(
			$context->projectRoot,
			$context->phpProfile,
			$normalizedPath,
			$line,
			$hover,
		);
	}

	/** @param array<string,mixed> $snapshot @return array<string,mixed> */
	public function buildDefinitionResultFromSnapshot(array $snapshot, string $documentPath, int $line, ?int $column = null): array
	{
		$context = $snapshot['context'];
		$semanticResult = $snapshot['semantic_result'];
		$normalizedPath = \normalize_path($documentPath);
		$definition = $this->buildDefinition($semanticResult['symbol_index'], $normalizedPath, $line, $column);
		return [
			'project_root' => \normalize_path($context->projectRoot),
			'php_profile' => $context->phpProfile,
			'path' => $normalizedPath,
			'uri' => 'file://' . $normalizedPath,
			'line' => $line,
			'column' => $column,
			'definition' => $definition,
		];
	}

	/** @param array<string,mixed> $snapshot @return array<string,mixed> */
	public function buildReferencesResultFromSnapshot(array $snapshot, string $documentPath, int $line, ?int $column = null): array
	{
		$context = $snapshot['context'];
		$semanticResult = $snapshot['semantic_result'];
		$normalizedPath = \normalize_path($documentPath);
		$references = $this->buildReferences($semanticResult['symbol_index'], $normalizedPath, $line, $column);
		return [
			'project_root' => \normalize_path($context->projectRoot),
			'php_profile' => $context->phpProfile,
			'path' => $normalizedPath,
			'uri' => 'file://' . $normalizedPath,
			'line' => $line,
			'column' => $column,
			'reference_count' => count($references),
			'references' => $references,
		];
	}

	/** @param array<string,string> $sourceOverrides @return array{project_root:string,php_profile:string,source_unit_count:int,analyzed_count:int,reused_count:int,warning_count:int,duplicate_count:int,resolution_warning_count:int,override_warning_count:int,return_chain_warning_count:int,expression_chain_warning_count:int,local_type_warning_count:int,property_type_warning_count:int,property_read_warning_count:int,initialization_warning_count:int,call_site_warning_count:int,return_type_warning_count:int,symbol_count:int,state_path:string,runtime_shallow_sources:list<array{profile:string,path:string,generated:int,skipped:list<string>}>,warning_samples:list<string>} */
	public function runWithOverrides(string $projectRoot, string $configPath, array $sourceOverrides): array
	{
		[$context, $filePassResult, $semanticResult, $warningCount] = $this->analyzeWorkspace($projectRoot, $configPath, $sourceOverrides);
		$files = $filePassResult['files'];
		$analyzedCount = $filePassResult['analyzed_count'];
		$reusedCount = $filePassResult['reused_count'];
		$symbolIndex = $semanticResult['symbol_index'];
		$duplicateDiagnostics = $semanticResult['duplicate_diagnostics'];
		$resolutionDiagnostics = $semanticResult['resolution_diagnostics'];
		$overrideDiagnostics = $semanticResult['override_diagnostics'];
		$returnChainDiagnostics = $semanticResult['return_chain_diagnostics'];
		$expressionChainDiagnostics = $semanticResult['expression_chain_diagnostics'];
		$localTypeDiagnostics = $semanticResult['local_type_diagnostics'];
		$propertyTypeDiagnostics = $semanticResult['property_type_diagnostics'];
		$propertyReadDiagnostics = $semanticResult['property_read_diagnostics'];
		$initializationDiagnostics = $semanticResult['initialization_diagnostics'];
		$callSiteDiagnostics = $semanticResult['call_site_diagnostics'];
		$returnTypeDiagnostics = $semanticResult['return_type_diagnostics'];
		$frontendDiagnostics = $semanticResult['frontend_diagnostics'] ?? [];

		return $this->resultAssembler->buildRunResult(
			$context->projectRoot,
			$context->phpProfile,
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
			$frontendDiagnostics,
			$context->statePath,
			$context->runtimeShallowSources,
			$semanticResult['warning_samples'],
		);
	}

	/** @param array<string,string> $sourceOverrides @return array{0:StanWorkspaceContext,1:array<string,mixed>,2:array<string,mixed>,3:int,4:array<string,mixed>} */
	private function analyzeWorkspace(string $projectRoot, string $configPath, array $sourceOverrides, string $analysisMode = 'full'): array
	{
		$hasSourceOverrides = $sourceOverrides !== [];
		$buildGateOnly = $analysisMode === 'build_gate';
		$timings = [];
		$contextStart = microtime(true);
		$context = $this->contextBuilder->build($projectRoot, $configPath, $sourceOverrides);
		$timings['context_build_ms'] = $this->elapsedMilliseconds($contextStart);
		$stateLoadStart = microtime(true);
		$state = $this->stateStore->load($context->statePath);
		$timings['state_load_ms'] = $this->elapsedMilliseconds($stateLoadStart);
		if (!is_array($state['files'] ?? null)) {
			$state = ['version' => 1, 'files' => []];
		}
		$previousState = $state;
		if ($this->canReusePersistedState($context, $state, $sourceOverrides, $analysisMode)) {
			$timings['file_pass_ms'] = 0;
			$timings['semantic_pass_ms'] = 0;
			$timings['semantic_subpasses_ms'] = $this->zeroSemanticSubpassTimings();
			$timings['state_build_ms'] = 0;
			$timings['state_save_ms'] = 0;
			$timings['state_cache_hit'] = true;
			return [
				$context,
				$this->buildFastPathFilePassResult($context, $state),
				$this->buildSemanticResultFromState($state),
				$this->warningCountFromState($state),
				$timings,
			];
		}
		$filePassStart = microtime(true);
		$filePassResult = $this->filePass->analyze(
			$context->projectRoot,
			$context->statePath,
			$context->cacheDir,
			$context->stanSignature,
			$state,
			$context->sourceUnits,
		);
		$timings['file_pass_ms'] = $this->elapsedMilliseconds($filePassStart);
		$warningCount = (int) ($filePassResult['warning_count'] ?? 0);
		$runtimeConfig = \resolve_runtime_build_config($context->config);
		$activeRuntimeModules = is_array($runtimeConfig['modules'] ?? null) ? array_values(array_map('strval', $runtimeConfig['modules'])) : null;
		$semanticStart = microtime(true);
		$semanticResult = $this->semanticPass->analyze(
			$filePassResult['file_summaries'],
			$context->projectRoot,
			$activeRuntimeModules,
			$analysisMode,
			is_array($state['semantic_cache'] ?? null) ? $state['semantic_cache'] : [],
			$context->stanSignature
		);
		$timings['semantic_pass_ms'] = $this->elapsedMilliseconds($semanticStart);
		$timings['semantic_subpasses_ms'] = is_array($semanticResult['timings_ms'] ?? null) ? $semanticResult['timings_ms'] : [];
		$warningCount += (int) ($semanticResult['warning_count'] ?? 0);

		$newFilesState = $filePassResult['files_state'];
		$fileDependencyKeys = $semanticResult['file_dependency_keys'];
		foreach ($newFilesState as $sourceKey => $fileState) {
			$newFilesState[$sourceKey]['dependency_keys'] = $fileDependencyKeys[$sourceKey] ?? [];
		}

		if ($buildGateOnly) {
			$timings['state_build_ms'] = 0;
			$timings['state_save_ms'] = 0;
			$filePassResult['files_state'] = $newFilesState;
			return [$context, $filePassResult, $semanticResult, $warningCount, $timings];
		}

		$stateBuildStart = microtime(true);
		$state = $this->resultAssembler->buildState(
			$context->projectRoot,
			$context->phpProfile,
			$context->sourceFingerprint,
			$semanticResult['symbol_index'],
			$semanticResult['duplicate_diagnostics'],
			$semanticResult['resolution_diagnostics'],
			$semanticResult['override_diagnostics'],
			$semanticResult['return_chain_types'],
			$semanticResult['return_chain_diagnostics'],
			$semanticResult['expression_chain_types'],
			$semanticResult['expression_chain_diagnostics'],
			$semanticResult['local_type_diagnostics'],
			$semanticResult['property_type_diagnostics'],
			$semanticResult['property_read_diagnostics'],
			$semanticResult['initialization_diagnostics'],
			$semanticResult['call_site_diagnostics'],
			$semanticResult['return_type_diagnostics'],
			$semanticResult['frontend_diagnostics'] ?? [],
			$semanticResult['frontend_classifications'] ?? [],
			$newFilesState,
			$context->activeRuntimeShallowPath,
			$warningCount,
			$semanticResult['warning_samples'],
			is_array($semanticResult['semantic_cache'] ?? null) ? $semanticResult['semantic_cache'] : [],
		);
		$timings['state_build_ms'] = $this->elapsedMilliseconds($stateBuildStart);
		if (!$hasSourceOverrides) {
			if ($this->statesEquivalentForSave($previousState, $state)) {
				$timings['state_save_ms'] = 0;
				$timings['state_save_skipped'] = true;
			} else {
				$stateSaveStart = microtime(true);
				$this->stateStore->save($context->statePath, $state);
				$timings['state_save_ms'] = $this->elapsedMilliseconds($stateSaveStart);
				$timings['state_save_skipped'] = false;
			}
		} else {
			$timings['state_save_ms'] = 0;
		}
		$filePassResult['files_state'] = $newFilesState;

		return [$context, $filePassResult, $semanticResult, $warningCount, $timings];
	}

	private function elapsedMilliseconds(float $startedAt): int
	{
		return (int) round(max(0.0, (microtime(true) - $startedAt) * 1000.0));
	}

	/** @param array<string,string> $sourceOverrides @param array<string,mixed> $state */
	private function canReusePersistedState(StanWorkspaceContext $context, array $state, array $sourceOverrides, string $analysisMode): bool
	{
		if ($sourceOverrides !== [] || $analysisMode !== 'full') {
			return false;
		}
		if ((string) ($state['source_fingerprint'] ?? '') !== $context->sourceFingerprint) {
			return false;
		}
		if ((string) ($state['php_profile'] ?? '') !== $context->phpProfile) {
			return false;
		}
		if (!is_array($state['files'] ?? null)) {
			return false;
		}
		$files = $state['files'];
		foreach ($context->sourceUnits as $sourceUnit) {
			$fileState = is_array($files[$sourceUnit->sourceKey] ?? null) ? $files[$sourceUnit->sourceKey] : null;
			if ($fileState === null) {
				return false;
			}
			if ((string) ($fileState['content_hash'] ?? '') !== $sourceUnit->meta['content_hash']) {
				return false;
			}
			if ((string) ($fileState['stan_signature'] ?? '') !== $context->stanSignature) {
				return false;
			}
			$cachePath = is_string($fileState['cache_path'] ?? null) ? \normalize_path($fileState['cache_path']) : '';
			if ($cachePath === '' || !is_file($cachePath)) {
				return false;
			}
		}
		return true;
	}

	/** @return array{files:list<string>,file_summaries:array<string,array<string,mixed>>,files_state:array<string,array<string,mixed>>,analyzed_count:int,reused_count:int,warning_count:int} */
	private function buildFastPathFilePassResult(StanWorkspaceContext $context, array $state): array
	{
		$files = [];
		foreach ($context->sourceUnits as $sourceUnit) {
			$files[] = $sourceUnit->path;
		}
		return [
			'files' => $files,
			'file_summaries' => [],
			'files_state' => is_array($state['files'] ?? null) ? $state['files'] : [],
			'analyzed_count' => 0,
			'reused_count' => count($context->sourceUnits),
			'warning_count' => $this->fileWarningCountFromState($state),
		];
	}

	/** @return array<string,mixed> */
	private function buildSemanticResultFromState(array $state): array
	{
		$fileDependencyKeys = [];
		foreach (is_array($state['files'] ?? null) ? $state['files'] : [] as $sourceKey => $fileState) {
			if (!is_string($sourceKey) || !is_array($fileState)) {
				continue;
			}
			$keys = is_array($fileState['dependency_keys'] ?? null) ? $fileState['dependency_keys'] : [];
			$fileDependencyKeys[$sourceKey] = array_values(array_filter($keys, static fn (mixed $key): bool => is_string($key) && $key !== ''));
		}
		return [
			'symbol_index' => $this->stateList($state, 'symbol_index'),
			'duplicate_diagnostics' => $this->stateList($state, 'duplicate_diagnostics'),
			'resolution_diagnostics' => $this->stateList($state, 'resolution_diagnostics'),
			'override_diagnostics' => $this->stateList($state, 'override_diagnostics'),
			'return_chain_types' => $this->stateList($state, 'return_chain_types'),
			'return_chain_diagnostics' => $this->stateList($state, 'return_chain_diagnostics'),
			'expression_chain_types' => $this->stateList($state, 'expression_chain_types'),
			'expression_chain_diagnostics' => $this->stateList($state, 'expression_chain_diagnostics'),
			'local_type_diagnostics' => $this->stateList($state, 'local_type_diagnostics'),
			'property_type_diagnostics' => $this->stateList($state, 'property_type_diagnostics'),
			'property_read_diagnostics' => $this->stateList($state, 'property_read_diagnostics'),
			'initialization_diagnostics' => $this->stateList($state, 'initialization_diagnostics'),
			'call_site_diagnostics' => $this->stateList($state, 'call_site_diagnostics'),
			'return_type_diagnostics' => $this->stateList($state, 'return_type_diagnostics'),
			'frontend_diagnostics' => $this->stateList($state, 'frontend_diagnostics'),
			'frontend_classifications' => is_array($state['frontend_classifications'] ?? null) ? $state['frontend_classifications'] : [],
			'file_dependency_keys' => $fileDependencyKeys,
			'warning_samples' => $this->stateList($state, 'warning_samples'),
			'timings_ms' => $this->zeroSemanticSubpassTimings(),
			'warning_count' => $this->semanticWarningCountFromState($state),
			'semantic_cache' => is_array($state['semantic_cache'] ?? null) ? $state['semantic_cache'] : [],
		];
	}

	/** @return list<mixed> */
	private function stateList(array $state, string $key): array
	{
		return array_values(is_array($state[$key] ?? null) ? $state[$key] : []);
	}

	private function warningCountFromState(array $state): int
	{
		if (isset($state['warning_count']) && is_int($state['warning_count'])) {
			return $state['warning_count'];
		}
		return $this->fileWarningCountFromState($state) + $this->semanticWarningCountFromState($state);
	}

	private function fileWarningCountFromState(array $state): int
	{
		$count = 0;
		foreach (is_array($state['files'] ?? null) ? $state['files'] : [] as $fileState) {
			if (is_array($fileState)) {
				$count += (int) ($fileState['file_warning_count'] ?? 0);
			}
		}
		return $count;
	}

	private function semanticWarningCountFromState(array $state): int
	{
		$count = 0;
		foreach ([
			'duplicate_diagnostics',
			'resolution_diagnostics',
			'override_diagnostics',
			'return_chain_diagnostics',
			'expression_chain_diagnostics',
			'local_type_diagnostics',
			'property_type_diagnostics',
			'property_read_diagnostics',
			'initialization_diagnostics',
			'call_site_diagnostics',
			'return_type_diagnostics',
			'frontend_diagnostics',
		] as $key) {
			$count += count($this->stateList($state, $key));
		}
		return $count;
	}

	/** @return array<string,int> */
	private function zeroSemanticSubpassTimings(): array
	{
		return [
			'symbol_index_ms' => 0,
			'duplicate_diagnostics_ms' => 0,
			'resolution_diagnostics_ms' => 0,
			'override_diagnostics_ms' => 0,
			'expression_analysis_ms' => 0,
			'expression_cache_hits' => 0,
			'expression_cache_misses' => 0,
			'frontend_classify_ms' => 0,
			'frontend_diagnostics_ms' => 0,
			'suppress_redundant_ms' => 0,
			'enrich_diagnostics_ms' => 0,
			'file_dependency_keys_ms' => 0,
			'warning_samples_ms' => 0,
		];
	}

	/** @param array<string,mixed> $left @param array<string,mixed> $right */
	private function statesEquivalentForSave(array $left, array $right): bool
	{
		unset($left['updated_at'], $right['updated_at']);
		return serialize($left) === serialize($right);
	}

	/** @param list<array<string,mixed>> $symbolIndex @return list<array<string,mixed>> */
	private function buildDocumentSymbols(array $symbolIndex, string $documentPath): array
	{
		return $this->positionResolver->buildDocumentSymbols($symbolIndex, $documentPath, $this->mapSymbolKindToLsp(...));
	}

	/** @param list<array<string,mixed>> $symbolIndex @param array<string,mixed> $semanticResult @return array<string,mixed>|null */
	private function buildHover(array $symbolIndex, array $semanticResult, string $documentPath, int $line, ?int $column = null): ?array
	{
		$matchingDiagnostics = $this->positionResolver->collectDiagnosticsForPosition(
			$this->resultAssembler->flattenDiagnostics(
				$semanticResult['duplicate_diagnostics'] ?? [],
				$semanticResult['resolution_diagnostics'] ?? [],
				$semanticResult['override_diagnostics'] ?? [],
				$semanticResult['return_chain_diagnostics'] ?? [],
				$semanticResult['expression_chain_diagnostics'] ?? [],
				$semanticResult['local_type_diagnostics'] ?? [],
				$semanticResult['property_type_diagnostics'] ?? [],
				$semanticResult['property_read_diagnostics'] ?? [],
				$semanticResult['initialization_diagnostics'] ?? [],
				$semanticResult['call_site_diagnostics'] ?? [],
				$semanticResult['return_type_diagnostics'] ?? [],
				$semanticResult['frontend_diagnostics'] ?? [],
			),
			$documentPath,
			$line,
			$column,
		);
		$bestSymbol = $this->resolveNavigationTarget($symbolIndex, $documentPath, $line, $column);

		if ($bestSymbol === null && $matchingDiagnostics === []) {
			return null;
		}

		$summary = null;
		if ($bestSymbol !== null) {
			$summary = [
				'name' => (string) ($bestSymbol['name'] ?? ''),
				'kind' => (string) ($bestSymbol['kind'] ?? 'symbol'),
				'scope' => (string) ($bestSymbol['scope'] ?? ''),
				'owner_class' => $bestSymbol['owner_class'] ?? null,
				'line' => (int) ($bestSymbol['line'] ?? 0),
				'signature' => (string) ($bestSymbol['signature'] ?? ''),
			];
		}

		return [
			'symbol' => $summary,
			'diagnostics' => $matchingDiagnostics,
		];
	}

	/** @param list<array<string,mixed>> $symbolIndex @return array<string,mixed>|null */
	private function buildDefinition(array $symbolIndex, string $documentPath, int $line, ?int $column = null): ?array
	{
		$symbol = $this->resolveNavigationTarget($symbolIndex, $documentPath, $line, $column);
		if ($symbol === null) {
			return null;
		}
		$name = (string) ($symbol['name'] ?? '');
		$kind = (string) ($symbol['kind'] ?? '');
		$scope = (string) ($symbol['scope'] ?? '');
		$matches = [];
		foreach ($symbolIndex as $candidate) {
			if (!is_array($candidate)) {
				continue;
			}
			if ((string) ($candidate['name'] ?? '') !== $name || (string) ($candidate['kind'] ?? '') !== $kind) {
				continue;
			}
			if ($kind === 'method' || $kind === 'property') {
				if ((string) ($candidate['scope'] ?? '') !== $scope) {
					continue;
				}
			}
			$candidatePath = \normalize_path((string) ($candidate['path'] ?? ''));
			$candidateLine = (int) ($candidate['line'] ?? 0);
			$matches[] = [
				'name' => $name,
				'kind' => $kind,
				'path' => $candidatePath,
				'uri' => 'file://' . $candidatePath,
				'line' => $candidateLine,
				'span' => $this->positionResolver->buildSymbolSpan($candidatePath, $candidateLine, $name, $kind),
			];
		}
		if ($matches === []) {
			return null;
		}
		return $matches[0];
	}

	/** @param list<array<string,mixed>> $symbolIndex @return list<array<string,mixed>> */
	private function buildReferences(array $symbolIndex, string $documentPath, int $line, ?int $column = null): array
	{
		$symbol = $this->resolveNavigationTarget($symbolIndex, $documentPath, $line, $column);
		if ($symbol === null) {
			return [];
		}
		return $this->positionResolver->collectReferenceLocations($symbol, $symbolIndex);
	}

	/** @param list<array<string,mixed>> $symbolIndex @return array<string,mixed>|null */
	private function resolveNavigationTarget(array $symbolIndex, string $documentPath, int $line, ?int $column = null): ?array
	{
		$symbol = $this->positionResolver->findBestSymbolAtPosition($symbolIndex, $documentPath, $line, $column);
		if ($symbol !== null) {
			return $symbol;
		}
		$identifier = $this->positionResolver->extractIdentifierAtPosition($documentPath, $line, $column);
		if (!is_string($identifier) || $identifier === '') {
			return null;
		}

		$matches = [];
		foreach ($symbolIndex as $candidate) {
			if (!is_array($candidate)) {
				continue;
			}
			if ((string) ($candidate['name'] ?? '') !== $identifier) {
				continue;
			}
			$matches[] = $candidate;
		}
		if ($matches === []) {
			return null;
		}
		if (count($matches) === 1) {
			return $matches[0];
		}

		foreach (['function', 'class', 'constant'] as $preferredKind) {
			$kindMatches = array_values(array_filter($matches, static fn (array $candidate): bool => (string) ($candidate['kind'] ?? '') === $preferredKind));
			if (count($kindMatches) === 1) {
				return $kindMatches[0];
			}
		}
		return $matches[0];
	}

	private function mapSymbolKindToLsp(string $kind): int
	{
		return match ($kind) {
			'class' => 5,
			'method' => 6,
			'function' => 12,
			'property' => 7,
			'constant' => 14,
			default => 13,
		};
	}
}

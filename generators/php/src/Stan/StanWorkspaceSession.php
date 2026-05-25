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
	public function createBridgeSnapshot(string $projectRoot, string $configPath, array $sourceOverrides = []): array
	{
		[$context, $filePassResult, $semanticResult, $warningCount] = $this->analyzeWorkspace($projectRoot, $configPath, $sourceOverrides);

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
		);
		$diagnosticsByPath = $this->resultAssembler->groupDiagnosticsByPath($allDiagnostics);

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
			],
		];
	}

	/** @param array<string,mixed> $snapshot @return array<string,mixed> */
	public function buildDiagnosticsResultFromSnapshot(array $snapshot): array
	{
		$context = $snapshot['context'];
		$filePassResult = $snapshot['file_pass_result'];
		$semanticResult = $snapshot['semantic_result'];
		return $this->resultAssembler->buildSessionDiagnosticsResult(
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
			$context->statePath,
			$context->runtimeShallowSources,
			$semanticResult['warning_samples'],
		);
	}

	/** @param array<string,string> $sourceOverrides @return array{0:StanWorkspaceContext,1:array<string,mixed>,2:array<string,mixed>,3:int} */
	private function analyzeWorkspace(string $projectRoot, string $configPath, array $sourceOverrides): array
	{
		$context = $this->contextBuilder->build($projectRoot, $configPath, $sourceOverrides);
		$state = $this->stateStore->load($context->statePath);
		$filePassResult = $this->filePass->analyze(
			$context->projectRoot,
			$context->statePath,
			$context->cacheDir,
			$context->stanSignature,
			$state,
			$context->sourceUnits,
		);
		$warningCount = (int) ($filePassResult['warning_count'] ?? 0);
		$semanticResult = $this->semanticPass->analyze($filePassResult['file_summaries'], $context->projectRoot);
		$warningCount += (int) ($semanticResult['warning_count'] ?? 0);

		$newFilesState = $filePassResult['files_state'];
		$fileDependencyKeys = $semanticResult['file_dependency_keys'];
		foreach ($newFilesState as $sourceKey => $fileState) {
			$newFilesState[$sourceKey]['dependency_keys'] = $fileDependencyKeys[$sourceKey] ?? [];
		}

		$state = $this->resultAssembler->buildState(
			$context->projectRoot,
			$context->phpProfile,
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
			$newFilesState,
			$context->activeRuntimeShallowPath,
		);
		$this->stateStore->save($context->statePath, $state);
		$filePassResult['files_state'] = $newFilesState;

		return [$context, $filePassResult, $semanticResult, $warningCount];
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
			),
			$documentPath,
			$line,
			$column,
		);
		$bestSymbol = $this->positionResolver->findBestSymbolAtPosition($symbolIndex, $documentPath, $line, $column);

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

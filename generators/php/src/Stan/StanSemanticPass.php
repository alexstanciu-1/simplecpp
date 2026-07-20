<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanSemanticPass
{
	public function __construct(
		private readonly StanSymbolIndexBuilder $symbolIndexBuilder = new StanSymbolIndexBuilder(),
		private readonly StanDependencyResolver $dependencyResolver = new StanDependencyResolver(),
		private readonly StanDiagnosticCollector $diagnosticCollector = new StanDiagnosticCollector(),
		private readonly StanDiagnosticEnricher $diagnosticEnricher = new StanDiagnosticEnricher(),
		private readonly StanExpressionTypeResolver $expressionTypeResolver = new StanExpressionTypeResolver(),
		private readonly StanFrontendClassifier $frontendClassifier = new StanFrontendClassifier(),
		private readonly StanWarningPresenter $warningPresenter = new StanWarningPresenter(),
	)
	{
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @param list<string>|null $activeRuntimeModules @return array<string,mixed> */
	public function analyze(array $fileSummaries, string $projectRoot, ?array $activeRuntimeModules = null): array
	{
		$timings = [];
		$timeSubpass = static function (string $label, callable $callback) use (&$timings): mixed {
			$startedAt = microtime(true);
			try {
				return $callback();
			} finally {
				$timings[$label . '_ms'] = (int) round(max(0.0, (microtime(true) - $startedAt) * 1000.0));
			}
		};

		$symbolIndex = $timeSubpass('symbol_index', fn (): array => $this->symbolIndexBuilder->build($fileSummaries));
		$duplicateDiagnostics = $timeSubpass('duplicate_diagnostics', fn (): array => $this->diagnosticCollector->collectDuplicateDiagnostics($symbolIndex));
		$resolutionDiagnostics = $timeSubpass('resolution_diagnostics', fn (): array => $this->diagnosticCollector->collectResolutionDiagnostics($fileSummaries, $symbolIndex));
		$overrideDiagnostics = $timeSubpass('override_diagnostics', function () use ($fileSummaries, $symbolIndex, $projectRoot): array {
			$diagnostics = $this->diagnosticCollector->collectOverrideDiagnostics($fileSummaries, $symbolIndex, $projectRoot);
			$diagnostics = array_merge($diagnostics, $this->diagnosticCollector->collectInterfaceContractDiagnostics($fileSummaries, $symbolIndex, $projectRoot));
			$diagnostics = array_merge($diagnostics, $this->diagnosticCollector->collectStructContractDiagnostics($fileSummaries, $symbolIndex, $projectRoot));
			$diagnostics = array_merge($diagnostics, $this->diagnosticCollector->collectUnionContractDiagnostics($fileSummaries, $symbolIndex, $projectRoot));
			return $diagnostics;
		});
		$expressionAnalysis = $timeSubpass('expression_analysis', fn (): array => $this->expressionTypeResolver->analyzeWorkspaceExpressions($fileSummaries, $symbolIndex));
		$returnChainTypes = $expressionAnalysis['return_chain_types'] ?? [];
		$returnChainDiagnostics = $expressionAnalysis['return_chain_diagnostics'] ?? [];
		$expressionChainTypes = $expressionAnalysis['expression_chain_types'] ?? [];
		$expressionChainDiagnostics = $expressionAnalysis['expression_chain_diagnostics'] ?? [];
		$localTypeDiagnostics = $expressionAnalysis['local_type_diagnostics'] ?? [];
		$propertyTypeDiagnostics = $expressionAnalysis['property_type_diagnostics'] ?? [];
		$propertyReadDiagnostics = $expressionAnalysis['property_read_diagnostics'] ?? [];
		$initializationDiagnostics = $expressionAnalysis['initialization_diagnostics'] ?? [];
		$callSiteDiagnostics = $expressionAnalysis['call_site_diagnostics'] ?? [];
		$returnTypeDiagnostics = $expressionAnalysis['return_type_diagnostics'] ?? [];
		$frontendClassifications = $timeSubpass('frontend_classify', fn (): array => $this->frontendClassifier->classify($fileSummaries, $symbolIndex, $activeRuntimeModules));
		$frontendDiagnostics = $timeSubpass('frontend_diagnostics', fn (): array => $this->collectFrontendDiagnostics($frontendClassifications));
		$suppressionStart = microtime(true);
		[
			$initializationDiagnostics,
			$returnChainDiagnostics,
			$expressionChainDiagnostics,
			$propertyReadDiagnostics,
			$callSiteDiagnostics,
			$returnTypeDiagnostics,
		] = $this->warningPresenter->suppressRedundantDiagnostics(
			$initializationDiagnostics,
			$localTypeDiagnostics,
			$propertyTypeDiagnostics,
			$returnChainDiagnostics,
			$expressionChainDiagnostics,
			$propertyReadDiagnostics,
			$callSiteDiagnostics,
			$returnTypeDiagnostics,
		);
		$timings['suppress_redundant_ms'] = (int) round(max(0.0, (microtime(true) - $suppressionStart) * 1000.0));
		$enrichStart = microtime(true);
		$duplicateDiagnostics = $this->diagnosticEnricher->enrichList($duplicateDiagnostics);
		$resolutionDiagnostics = $this->diagnosticEnricher->enrichList($resolutionDiagnostics);
		$overrideDiagnostics = $this->diagnosticEnricher->enrichList($overrideDiagnostics);
		$returnChainDiagnostics = $this->diagnosticEnricher->enrichList($returnChainDiagnostics);
		$expressionChainDiagnostics = $this->diagnosticEnricher->enrichList($expressionChainDiagnostics);
		$localTypeDiagnostics = $this->diagnosticEnricher->enrichList($localTypeDiagnostics);
		$propertyTypeDiagnostics = $this->diagnosticEnricher->enrichList($propertyTypeDiagnostics);
		$propertyReadDiagnostics = $this->diagnosticEnricher->enrichList($propertyReadDiagnostics);
		$initializationDiagnostics = $this->diagnosticEnricher->enrichList($initializationDiagnostics);
		$callSiteDiagnostics = $this->diagnosticEnricher->enrichList($callSiteDiagnostics);
		$returnTypeDiagnostics = $this->diagnosticEnricher->enrichList($returnTypeDiagnostics);
		$frontendDiagnostics = $this->diagnosticEnricher->enrichList($frontendDiagnostics);
		$timings['enrich_diagnostics_ms'] = (int) round(max(0.0, (microtime(true) - $enrichStart) * 1000.0));
		$fileDependencyKeys = $timeSubpass('file_dependency_keys', fn (): array => $this->dependencyResolver->collectFileDependencyKeys($fileSummaries, $symbolIndex, $projectRoot));
		$warningSamples = $timeSubpass('warning_samples', fn (): array => $this->warningPresenter->buildWarningSamples(
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
		));

		return [
			'symbol_index' => $symbolIndex,
			'duplicate_diagnostics' => $duplicateDiagnostics,
			'resolution_diagnostics' => $resolutionDiagnostics,
			'override_diagnostics' => $overrideDiagnostics,
			'return_chain_types' => $returnChainTypes,
			'return_chain_diagnostics' => $returnChainDiagnostics,
			'expression_chain_types' => $expressionChainTypes,
			'expression_chain_diagnostics' => $expressionChainDiagnostics,
			'local_type_diagnostics' => $localTypeDiagnostics,
			'property_type_diagnostics' => $propertyTypeDiagnostics,
			'property_read_diagnostics' => $propertyReadDiagnostics,
			'initialization_diagnostics' => $initializationDiagnostics,
			'call_site_diagnostics' => $callSiteDiagnostics,
			'return_type_diagnostics' => $returnTypeDiagnostics,
			'frontend_diagnostics' => $frontendDiagnostics,
			'frontend_classifications' => $frontendClassifications,
			'file_dependency_keys' => $fileDependencyKeys,
			'warning_samples' => $warningSamples,
			'timings_ms' => $timings,
			'warning_count' => count($duplicateDiagnostics)
				+ count($resolutionDiagnostics)
				+ count($overrideDiagnostics)
				+ count($returnChainDiagnostics)
				+ count($expressionChainDiagnostics)
				+ count($localTypeDiagnostics)
				+ count($propertyTypeDiagnostics)
				+ count($propertyReadDiagnostics)
				+ count($initializationDiagnostics)
				+ count($callSiteDiagnostics)
				+ count($returnTypeDiagnostics)
				+ count($frontendDiagnostics),
		];
	}

	/** @param array<string,array<string,mixed>> $frontendClassifications @return list<array<string,mixed>> */
	private function collectFrontendDiagnostics(array $frontendClassifications): array
	{
		$diagnostics = [];
		foreach ($frontendClassifications as $classification) {
			if (!is_array($classification)) {
				continue;
			}
			$classificationDiagnostics = is_array($classification['diagnostics'] ?? null) ? $classification['diagnostics'] : [];
			foreach ($classificationDiagnostics as $diagnostic) {
				if (!is_array($diagnostic)) {
					continue;
				}
				$message = trim((string) ($diagnostic['message'] ?? ''));
				if ($message === '') {
					continue;
				}
				$diagnostics[] = [
					'kind' => 'frontend_classification',
					'code' => 'frontend_' . (string) ($classification['request_kind'] ?? 'classification'),
					'path' => (string) ($classification['path'] ?? ''),
					'line' => (int) ($classification['line'] ?? 0),
					'column' => (int) ($classification['column'] ?? 0),
					'message' => $message,
				];
			}
		}
		return $diagnostics;
	}
}

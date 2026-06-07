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
		private readonly StanWarningPresenter $warningPresenter = new StanWarningPresenter(),
	)
	{
	}

	/** @param array<string,array<string,mixed>> $fileSummaries @return array<string,mixed> */
	public function analyze(array $fileSummaries, string $projectRoot): array
	{
		$symbolIndex = $this->symbolIndexBuilder->build($fileSummaries);
		$duplicateDiagnostics = $this->diagnosticCollector->collectDuplicateDiagnostics($symbolIndex);
		$resolutionDiagnostics = $this->diagnosticCollector->collectResolutionDiagnostics($fileSummaries, $symbolIndex);
		$overrideDiagnostics = $this->diagnosticCollector->collectOverrideDiagnostics($fileSummaries, $symbolIndex, $projectRoot);
		$returnChainTypes = $this->expressionTypeResolver->resolveReturnChains($fileSummaries, $symbolIndex);
		$returnChainDiagnostics = $this->expressionTypeResolver->collectReturnChainDiagnostics($fileSummaries, $symbolIndex);
		$expressionChainTypes = $this->expressionTypeResolver->resolveExpressionChains($fileSummaries, $symbolIndex);
		$expressionChainDiagnostics = $this->expressionTypeResolver->collectExpressionChainDiagnostics($fileSummaries, $symbolIndex);
		$localTypeDiagnostics = $this->expressionTypeResolver->collectLocalTypeDiagnostics($fileSummaries, $symbolIndex);
		$propertyTypeDiagnostics = $this->expressionTypeResolver->collectPropertyTypeDiagnostics($fileSummaries, $symbolIndex);
		$propertyReadDiagnostics = $this->expressionTypeResolver->collectPropertyReadDiagnostics($fileSummaries, $symbolIndex);
		$initializationDiagnostics = $this->expressionTypeResolver->collectInitializationDiagnostics($fileSummaries, $symbolIndex);
		$callSiteDiagnostics = $this->expressionTypeResolver->collectCallSiteDiagnostics($fileSummaries, $symbolIndex);
		$callSiteDiagnostics = array_merge($callSiteDiagnostics, $this->expressionTypeResolver->collectWrapperBoundaryDiagnostics($fileSummaries, $symbolIndex));
		$returnTypeDiagnostics = $this->expressionTypeResolver->collectReturnTypeDiagnostics($fileSummaries, $symbolIndex);
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
		$fileDependencyKeys = $this->dependencyResolver->collectFileDependencyKeys($fileSummaries, $symbolIndex, $projectRoot);

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
			'file_dependency_keys' => $fileDependencyKeys,
			'warning_samples' => $this->warningPresenter->buildWarningSamples(
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
			),
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
				+ count($returnTypeDiagnostics),
		];
	}
}

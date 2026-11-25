<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanWarningPresenter
{
	/** @return array{0:list<array<string,mixed>>,1:list<array<string,mixed>>,2:list<array<string,mixed>>,3:list<array<string,mixed>>,4:list<array<string,mixed>>,5:list<array<string,mixed>>} */
	public function suppressRedundantDiagnostics(
		array $initializationDiagnostics,
		array $localTypeDiagnostics,
		array $propertyTypeDiagnostics,
		array $returnChainDiagnostics,
		array $expressionChainDiagnostics,
		array $propertyReadDiagnostics,
		array $callSiteDiagnostics,
		array $returnTypeDiagnostics,
	): array {
		$rootCausesBySite = [];
		$localTypeSites = [];
		$propertyTypeSites = [];
		foreach ([$initializationDiagnostics, $localTypeDiagnostics, $propertyTypeDiagnostics] as $rootGroup) {
			foreach ($rootGroup as $diagnostic) {
				if (!is_array($diagnostic)) {
					continue;
				}
				$site = $this->diagnosticSiteKey($diagnostic);
				if ($site === null) {
					continue;
				}
				$rootCausesBySite[$site] = true;
			}
		}
		foreach ($localTypeDiagnostics as $diagnostic) {
			if (!is_array($diagnostic)) {
				continue;
			}
			$site = $this->diagnosticSiteKey($diagnostic);
			if ($site !== null) {
				$localTypeSites[$site] = true;
			}
		}
		foreach ($propertyTypeDiagnostics as $diagnostic) {
			if (!is_array($diagnostic)) {
				continue;
			}
			$site = $this->diagnosticSiteKey($diagnostic);
			if ($site !== null) {
				$propertyTypeSites[$site] = true;
			}
		}

		$initializationDiagnostics = array_values(array_filter(
			$initializationDiagnostics,
			fn (mixed $diagnostic): bool => !$this->shouldSuppressInitializationDiagnostic($diagnostic, $localTypeSites, $propertyTypeSites)
		));

		$returnChainDiagnostics = $this->filterDiagnosticsBySite($returnChainDiagnostics, $rootCausesBySite);
		$expressionChainDiagnostics = $this->filterDiagnosticsBySite($expressionChainDiagnostics, $rootCausesBySite);
		$propertyReadDiagnostics = $this->filterDiagnosticsBySite($propertyReadDiagnostics, $rootCausesBySite);

		$propertyReadSites = [];
		foreach ($propertyReadDiagnostics as $diagnostic) {
			if (!is_array($diagnostic)) {
				continue;
			}
			$site = $this->diagnosticSiteKey($diagnostic);
			if ($site !== null) {
				$propertyReadSites[$site] = true;
			}
		}

		$callSiteDiagnostics = array_values(array_filter(
			$callSiteDiagnostics,
			fn (mixed $diagnostic): bool => !$this->shouldSuppressCallSiteDiagnostic($diagnostic, $rootCausesBySite, $propertyReadSites)
		));

		$returnTypeDiagnostics = array_values(array_filter(
			$returnTypeDiagnostics,
			fn (mixed $diagnostic): bool => !$this->shouldSuppressReturnTypeDiagnostic($diagnostic, $rootCausesBySite)
		));

		return [
			$initializationDiagnostics,
			$returnChainDiagnostics,
			$expressionChainDiagnostics,
			$propertyReadDiagnostics,
			$callSiteDiagnostics,
			$returnTypeDiagnostics,
		];
	}

	/** @param list<array<string,mixed>> ...$diagnosticGroups @return list<string> */
	public function buildWarningSamples(array ...$diagnosticGroups): array
	{
		$samples = [];
		foreach ($diagnosticGroups as $diagnostics) {
			foreach ($diagnostics as $diagnostic) {
				if (!is_array($diagnostic)) {
					continue;
				}
				$message = (string) ($diagnostic['message'] ?? '');
				if ($message === '') {
					continue;
				}
				$samples[] = $message;
				if (count($samples) >= 5) {
					return $samples;
				}
			}
		}
		return $samples;
	}

	/** @param list<array<string,mixed>> $diagnostics @param array<string,bool> $suppressedSites @return list<array<string,mixed>> */
	private function filterDiagnosticsBySite(array $diagnostics, array $suppressedSites): array
	{
		return array_values(array_filter($diagnostics, function (mixed $diagnostic) use ($suppressedSites): bool {
			if (!is_array($diagnostic)) {
				return false;
			}
			$site = $this->diagnosticSiteKey($diagnostic);
			return $site === null || !isset($suppressedSites[$site]);
		}));
	}

	/** @param array<string,mixed> $diagnostic */
	private function diagnosticSiteKey(array $diagnostic): ?string
	{
		$context = trim((string) ($diagnostic['context'] ?? ''));
		$line = (int) ($diagnostic['line'] ?? 0);
		if ($context === '' || $line <= 0) {
			return null;
		}
		return $context . '|' . $line;
	}

	private function shouldSuppressCallSiteDiagnostic(mixed $diagnostic, array $rootCausesBySite, array $propertyReadSites): bool
	{
		if (!is_array($diagnostic)) {
			return true;
		}
		$site = $this->diagnosticSiteKey($diagnostic);
		if ($site === null) {
			return false;
		}
		if (isset($rootCausesBySite[$site])) {
			return true;
		}
		$kind = (string) ($diagnostic['kind'] ?? '');
		if (in_array($kind, ['argument_type_mismatch', 'unresolved_method_call', 'unresolved_call', 'unresolved_static_call'], true) && isset($propertyReadSites[$site])) {
			return true;
		}
		return false;
	}

	private function shouldSuppressReturnTypeDiagnostic(mixed $diagnostic, array $rootCausesBySite): bool
	{
		if (!is_array($diagnostic)) {
			return true;
		}
		$site = $this->diagnosticSiteKey($diagnostic);
		if ($site === null) {
			return false;
		}
		$kind = (string) ($diagnostic['kind'] ?? '');
		return $kind === 'return_type_mismatch' && isset($rootCausesBySite[$site]);
	}

	private function shouldSuppressInitializationDiagnostic(mixed $diagnostic, array $localTypeSites, array $propertyTypeSites): bool
	{
		if (!is_array($diagnostic)) {
			return true;
		}
		$site = $this->diagnosticSiteKey($diagnostic);
		if ($site === null) {
			return false;
		}
		return isset($localTypeSites[$site]) || isset($propertyTypeSites[$site]);
	}
}

<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanResultAssembler
{
	/** @return array<string,mixed> */
	public function buildState(
		string $projectRoot,
		string $phpProfile,
		array $symbolIndex,
		array $duplicateDiagnostics,
		array $resolutionDiagnostics,
		array $overrideDiagnostics,
		array $returnChainTypes,
		array $returnChainDiagnostics,
		array $expressionChainTypes,
		array $expressionChainDiagnostics,
		array $localTypeDiagnostics,
		array $propertyTypeDiagnostics,
		array $propertyReadDiagnostics,
		array $initializationDiagnostics,
		array $callSiteDiagnostics,
		array $returnTypeDiagnostics,
		array $filesState,
		string $activeRuntimeShallowPath,
	): array {
		return [
			'version' => 1,
			'project_root' => \normalize_path($projectRoot),
			'php_profile' => $phpProfile,
			'updated_at' => time(),
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
			'files' => $filesState,
			'runtime_shallow_path' => \normalize_path($activeRuntimeShallowPath),
		];
	}

	/** @return array{project_root:string,php_profile:string,source_unit_count:int,analyzed_count:int,reused_count:int,warning_count:int,duplicate_count:int,resolution_warning_count:int,override_warning_count:int,return_chain_warning_count:int,expression_chain_warning_count:int,local_type_warning_count:int,property_type_warning_count:int,property_read_warning_count:int,initialization_warning_count:int,call_site_warning_count:int,return_type_warning_count:int,symbol_count:int,state_path:string,runtime_shallow_sources:list<array{profile:string,path:string,generated:int,skipped:list<string>}>,warning_samples:list<string>} */
	public function buildRunResult(
		string $projectRoot,
		string $phpProfile,
		int $sourceUnitCount,
		int $analyzedCount,
		int $reusedCount,
		int $warningCount,
		array $symbolIndex,
		array $duplicateDiagnostics,
		array $resolutionDiagnostics,
		array $overrideDiagnostics,
		array $returnChainDiagnostics,
		array $expressionChainDiagnostics,
		array $localTypeDiagnostics,
		array $propertyTypeDiagnostics,
		array $propertyReadDiagnostics,
		array $initializationDiagnostics,
		array $callSiteDiagnostics,
		array $returnTypeDiagnostics,
		string $statePath,
		array $runtimeShallowSources,
		array $warningSamples,
	): array {
		return [
			'project_root' => \normalize_path($projectRoot),
			'php_profile' => $phpProfile,
			'source_unit_count' => $sourceUnitCount,
			'analyzed_count' => $analyzedCount,
			'reused_count' => $reusedCount,
			'warning_count' => $warningCount,
			'duplicate_count' => count($duplicateDiagnostics),
			'resolution_warning_count' => count($resolutionDiagnostics),
			'override_warning_count' => count($overrideDiagnostics),
			'return_chain_warning_count' => count($returnChainDiagnostics),
			'expression_chain_warning_count' => count($expressionChainDiagnostics),
			'local_type_warning_count' => count($localTypeDiagnostics),
			'property_type_warning_count' => count($propertyTypeDiagnostics),
			'property_read_warning_count' => count($propertyReadDiagnostics),
			'initialization_warning_count' => count($initializationDiagnostics),
			'call_site_warning_count' => count($callSiteDiagnostics),
			'return_type_warning_count' => count($returnTypeDiagnostics),
			'symbol_count' => count($symbolIndex),
			'state_path' => $statePath,
			'runtime_shallow_sources' => $runtimeShallowSources,
			'warning_samples' => $warningSamples,
		];
	}
}

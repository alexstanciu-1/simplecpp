#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
class ProjectCallableContractArtifact;
class ProjectReferenceResolution;
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
class SourceUnitTable;
void __latency_fn_project_reference_resolution_record_reference_contract_publication_metrics__exec(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits, int_t<std::uint32_t> ownerRunId, shared_p<ProjectSymbolIndex> projectSymbols, ProjectSymbolIndexRow entrySymbol, const string_t& entryText, shared_p<ProjectReferenceResolution> references, shared_p<ProjectCallableContractArtifact> contracts, int_t<> workerCount, bool_t upstreamSymbolFactWorkerReady, bool_t& referenceContractWorkerCandidateReady);
}

#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CapabilityCoverageArtifact;
class CompilerProjectRunReport;
class ProjectCallableContractArtifact;
struct ProjectSymbolIndexRow;
struct TypeRefTable;
void __latency_fn_type_capability_readiness_record_capability_readiness_publication_metrics__exec(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, TypeRefTable typeRefs, shared_p<ProjectCallableContractArtifact> contracts, shared_p<CapabilityCoverageArtifact> coverage, int_t<> workerCount, bool_t upstreamReferenceContractWorkerReady, bool_t& capabilityReadinessWorkerCandidateReady);
}

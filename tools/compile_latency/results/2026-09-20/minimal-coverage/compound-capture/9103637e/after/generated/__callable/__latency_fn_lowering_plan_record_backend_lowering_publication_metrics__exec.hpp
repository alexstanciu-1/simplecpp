#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct AnalysisEntryContextRow;
class BackendRequestAuthorizationArtifact;
class CompilerProjectRunReport;
class LoweringPlan;
struct OperationReadiness;
struct ProjectCallableContractRow;
struct ProjectSymbolIndexRow;
struct StorageLifetimeRequestRow;
void __latency_fn_lowering_plan_record_backend_lowering_publication_metrics__exec(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, AnalysisEntryContextRow entry, ProjectCallableContractRow contract, OperationReadiness operation, StorageLifetimeRequestRow storage, shared_p<BackendRequestAuthorizationArtifact> requests, shared_p<LoweringPlan> plan, int_t<> workerCount, bool_t upstreamStorageLifetimeWorkerReady, bool_t& backendLoweringWorkerCandidateReady);
}

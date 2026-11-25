#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
class BackendRequestAuthorizationArtifact;
class CompilerProjectRunReport;
class EmissionLLVMCompositeTextSnapshot;
struct FunctionBodyTextEmissionPreflightArtifact;
class LoweringPlan;
struct ProjectSymbolIndexRow;
void __latency_fn_llvm_text_from_plan_record_emission_llvm_publication_metrics__exec(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, shared_p<BackendRequestAuthorizationArtifact>& requests, shared_p<LoweringPlan> plan, BackendEmissionDecisionArtifact& emission, FunctionBodyTextEmissionPreflightArtifact preflightArtifact, const string_t& moduleText, shared_p<EmissionLLVMCompositeTextSnapshot> compositeSnapshot, int_t<> workerCount, bool_t upstreamBackendLoweringWorkerReady, bool_t& emissionLLVMWorkerCandidateReady);
}

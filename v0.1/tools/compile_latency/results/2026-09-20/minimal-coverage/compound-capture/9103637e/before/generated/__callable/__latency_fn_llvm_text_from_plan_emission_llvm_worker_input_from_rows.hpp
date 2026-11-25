#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendRequestAuthorizationArtifact;
class EmissionLLVMCompositeTextSnapshot;
class EmissionLLVMWorkerInput;
class LoweringPlan;
struct ProjectSymbolIndexRow;
shared_p<EmissionLLVMWorkerInput> __latency_fn_llvm_text_from_plan_emission_llvm_worker_input_from_rows(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, shared_p<BackendRequestAuthorizationArtifact>& requests, shared_p<LoweringPlan> plan, shared_p<EmissionLLVMCompositeTextSnapshot> compositeSnapshot);
}

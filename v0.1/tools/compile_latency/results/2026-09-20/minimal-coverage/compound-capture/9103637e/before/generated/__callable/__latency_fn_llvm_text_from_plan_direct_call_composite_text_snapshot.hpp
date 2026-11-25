#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendRequestAuthorizationArtifact;
class EmissionLLVMCompositeTextSnapshot;
class LoweringPlan;
class ProjectSymbolIndex;
struct ProjectSymbolIndexRow;
shared_p<EmissionLLVMCompositeTextSnapshot> __latency_fn_llvm_text_from_plan_direct_call_composite_text_snapshot(shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow targetSymbol, shared_p<BackendRequestAuthorizationArtifact>& targetRequests, shared_p<LoweringPlan> targetPlan);
}

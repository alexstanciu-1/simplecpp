#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
class BackendRequestAuthorizationArtifact;
class ProjectSymbolIndex;
string_t __latency_fn_llvm_text_from_plan_module_text_from_entry_and_target_emissions(BackendEmissionDecisionArtifact& entryEmission, shared_p<BackendRequestAuthorizationArtifact>& entryRequests, BackendEmissionDecisionArtifact& targetEmission, shared_p<BackendRequestAuthorizationArtifact>& targetRequests, shared_p<ProjectSymbolIndex> symbols);
}

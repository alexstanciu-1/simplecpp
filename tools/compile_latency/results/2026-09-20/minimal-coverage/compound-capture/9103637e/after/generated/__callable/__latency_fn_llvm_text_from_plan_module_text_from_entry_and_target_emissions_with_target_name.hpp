#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
class BackendRequestAuthorizationArtifact;
string_t __latency_fn_llvm_text_from_plan_module_text_from_entry_and_target_emissions_with_target_name(BackendEmissionDecisionArtifact& entryEmission, shared_p<BackendRequestAuthorizationArtifact>& entryRequests, BackendEmissionDecisionArtifact& targetEmission, shared_p<BackendRequestAuthorizationArtifact>& targetRequests, const string_t& targetName);
}

#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
class BackendRequestAuthorizationArtifact;
string_t __latency_fn_llvm_text_from_plan_module_text_from_entry_call_with_target_text(BackendEmissionDecisionArtifact& entryEmission, shared_p<BackendRequestAuthorizationArtifact>& entryRequests, const string_t& targetFunctionText, const string_t& targetName);
}

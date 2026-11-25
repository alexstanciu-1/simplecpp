#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
class BackendRequestAuthorizationArtifact;
bool_t __latency_fn_llvm_text_from_plan_append_local_function_text_from_emission_and_backend_requests(str::text_builder& lines, const string_t& llvmFunctionName, BackendEmissionDecisionArtifact& emission, shared_p<BackendRequestAuthorizationArtifact>& requests);
}

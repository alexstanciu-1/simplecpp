#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
class BackendRequestAuthorizationArtifact;
string_t __latency_fn_llvm_text_from_plan_literal_function_text_from_emission_and_backend_requests(const string_t& llvmFunctionName, BackendEmissionDecisionArtifact& emission, shared_p<BackendRequestAuthorizationArtifact>& requests);
}

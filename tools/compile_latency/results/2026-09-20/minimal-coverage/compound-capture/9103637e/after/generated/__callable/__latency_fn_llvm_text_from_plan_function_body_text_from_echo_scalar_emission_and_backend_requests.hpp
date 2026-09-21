#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
class BackendRequestAuthorizationArtifact;
string_t __latency_fn_llvm_text_from_plan_function_body_text_from_echo_scalar_emission_and_backend_requests(BackendEmissionDecisionArtifact& emission, shared_p<BackendRequestAuthorizationArtifact>& requests, const string_t& returnType);
}

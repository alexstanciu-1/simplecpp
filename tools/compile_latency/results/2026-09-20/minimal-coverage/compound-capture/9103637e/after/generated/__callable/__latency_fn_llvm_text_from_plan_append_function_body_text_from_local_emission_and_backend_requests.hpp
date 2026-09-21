#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
class BackendRequestAuthorizationArtifact;
void __latency_fn_llvm_text_from_plan_append_function_body_text_from_local_emission_and_backend_requests(str::text_builder& lines, BackendEmissionDecisionArtifact& emission, shared_p<BackendRequestAuthorizationArtifact>& requests, const string_t& returnType);
}

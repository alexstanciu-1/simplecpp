#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
struct BackendEmissionValueRow;
class BackendRequestAuthorizationArtifact;
string_t __latency_fn_llvm_text_from_plan_return_value_text_from_emission_and_backend_requests(BackendEmissionDecisionArtifact& emission, shared_p<BackendRequestAuthorizationArtifact>& requests, BackendEmissionValueRow value, const string_t& returnType);
}

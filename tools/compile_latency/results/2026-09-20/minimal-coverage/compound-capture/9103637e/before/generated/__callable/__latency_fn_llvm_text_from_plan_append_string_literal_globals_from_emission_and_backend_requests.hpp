#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
class BackendRequestAuthorizationArtifact;
void __latency_fn_llvm_text_from_plan_append_string_literal_globals_from_emission_and_backend_requests(str::text_builder& lines, BackendEmissionDecisionArtifact& emission, shared_p<BackendRequestAuthorizationArtifact>& requests);
}

#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
class BackendRequestAuthorizationArtifact;
struct FunctionBodyTextEmissionPreflightArtifact;
int_t<std::uint32_t> __latency_fn_llvm_text_from_plan_semantic_hash_for_emission_llvm(shared_p<BackendRequestAuthorizationArtifact>& requests, BackendEmissionDecisionArtifact& emission, FunctionBodyTextEmissionPreflightArtifact preflightArtifact, const string_t& moduleText);
}

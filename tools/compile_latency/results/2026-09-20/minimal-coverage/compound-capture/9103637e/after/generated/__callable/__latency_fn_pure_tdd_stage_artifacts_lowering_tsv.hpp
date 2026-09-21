#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
class BackendRequestAuthorizationArtifact;
struct FunctionBodyTextEmissionPreflightArtifact;
class LoweringPlan;
string_t __latency_fn_pure_tdd_stage_artifacts_lowering_tsv(shared_p<LoweringPlan> plan, shared_p<BackendRequestAuthorizationArtifact> requests, BackendEmissionDecisionArtifact emission, FunctionBodyTextEmissionPreflightArtifact preflight);
}

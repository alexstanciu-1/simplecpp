#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
class BackendRequestAuthorizationArtifact;
class LoweringPlan;
BackendEmissionDecisionArtifact __latency_fn_backend_emission_decisions_from_lowering_plan_and_backend_requests(shared_p<LoweringPlan> plan, shared_p<BackendRequestAuthorizationArtifact>& requests);
}

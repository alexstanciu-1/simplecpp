#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
class LoweringPlan;
BackendEmissionDecisionArtifact __latency_fn_backend_emission_decisions_from_lowering_plan(shared_p<LoweringPlan> plan);
}

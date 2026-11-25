#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
class LoweringPlan;
BackendEmissionDecisionArtifact __latency_fn_backend_emission_decisions_new_artifact(shared_p<LoweringPlan> plan, int_t<> decisionCapacity, int_t<> valueCapacity, int_t<> blockCapacity);
}

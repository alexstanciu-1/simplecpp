#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
class LoweringPlan;
BackendEmissionDecisionArtifact __latency_fn_backend_emission_decisions_new_artifact_with_sidecar_capacity(shared_p<LoweringPlan> plan, int_t<> decisionCapacity, int_t<> valueCapacity, int_t<> blockCapacity, int_t<> sidecarCapacity);
}

#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
class LoweringPlan;
struct LoweringStep;
void __latency_fn_backend_emission_decisions_append_operands_from_lowering_plan(BackendEmissionDecisionArtifact& artifact, shared_p<LoweringPlan> plan, LoweringStep step);
}

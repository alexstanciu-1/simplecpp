#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionRow;
class LoweringPlan;
BackendEmissionDecisionRow __latency_fn_backend_emission_decisions_decision_from_plan(int_t<std::uint32_t> decisionId, shared_p<LoweringPlan> plan);
}

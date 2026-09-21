#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class LoweringPlan;
struct LoweringStep;
LoweringStep __latency_fn_lowering_plan_step_by_id(shared_p<LoweringPlan> plan, int_t<std::uint32_t> stepId);
}

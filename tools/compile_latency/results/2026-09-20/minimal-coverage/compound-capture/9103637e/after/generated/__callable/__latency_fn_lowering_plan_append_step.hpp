#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class LoweringPlan;
struct LoweringStep;
void __latency_fn_lowering_plan_append_step(shared_p<LoweringPlan> plan, LoweringStep step);
}

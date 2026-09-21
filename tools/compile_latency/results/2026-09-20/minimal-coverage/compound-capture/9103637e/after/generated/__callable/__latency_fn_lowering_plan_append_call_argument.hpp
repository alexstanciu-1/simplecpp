#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendCallArgumentRow;
class LoweringPlan;
void __latency_fn_lowering_plan_append_call_argument(shared_p<LoweringPlan> plan, BackendCallArgumentRow row);
}

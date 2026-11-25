#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct LoweringBlockedRequestRow;
class LoweringPlan;
void __latency_fn_lowering_plan_append_blocked_request(shared_p<LoweringPlan> plan, LoweringBlockedRequestRow row);
}

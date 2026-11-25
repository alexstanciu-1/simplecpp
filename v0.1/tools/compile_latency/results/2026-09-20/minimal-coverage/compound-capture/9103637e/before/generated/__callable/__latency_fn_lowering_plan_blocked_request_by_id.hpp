#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct LoweringBlockedRequestRow;
class LoweringPlan;
LoweringBlockedRequestRow __latency_fn_lowering_plan_blocked_request_by_id(shared_p<LoweringPlan> plan, int_t<std::uint32_t> blockedRequestId);
}

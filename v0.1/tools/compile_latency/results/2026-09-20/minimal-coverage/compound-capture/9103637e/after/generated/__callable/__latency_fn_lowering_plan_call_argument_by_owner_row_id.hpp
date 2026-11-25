#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendCallArgumentRow;
class LoweringPlan;
BackendCallArgumentRow __latency_fn_lowering_plan_call_argument_by_owner_row_id(shared_p<LoweringPlan> plan, int_t<std::uint32_t> ownerRowId);
}

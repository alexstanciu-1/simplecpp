#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendLocalOperandRow;
class LoweringPlan;
BackendLocalOperandRow __latency_fn_lowering_plan_local_operand_by_owner_row_id(shared_p<LoweringPlan> plan, int_t<std::uint32_t> ownerRowId);
}

#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendLocalOperandRow;
class LoweringPlan;
void __latency_fn_lowering_plan_append_local_operand(shared_p<LoweringPlan> plan, BackendLocalOperandRow row);
}

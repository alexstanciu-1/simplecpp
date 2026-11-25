#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendControlFlowOperandRow;
class LoweringPlan;
void __latency_fn_lowering_plan_append_control_flow_operand(shared_p<LoweringPlan> plan, BackendControlFlowOperandRow row);
}

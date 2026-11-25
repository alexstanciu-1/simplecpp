#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendBinaryOperandRow;
class LoweringPlan;
void __latency_fn_lowering_plan_append_binary_operand(shared_p<LoweringPlan> plan, BackendBinaryOperandRow row);
}

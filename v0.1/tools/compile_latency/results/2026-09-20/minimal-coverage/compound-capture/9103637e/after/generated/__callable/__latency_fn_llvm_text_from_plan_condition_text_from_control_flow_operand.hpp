#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendControlFlowOperandRow;
string_t __latency_fn_llvm_text_from_plan_condition_text_from_control_flow_operand(str::text_builder& lines, BackendControlFlowOperandRow controlFlowOperand, int_t<> valueId, vector_t<int_t<std::uint32_t>>& localSourceRows);
}

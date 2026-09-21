#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendControlFlowOperandRow;
void __latency_fn_llvm_text_from_plan_append_if_condition_branch_text(str::text_builder& lines, BackendControlFlowOperandRow controlFlowOperand, int_t<> valueId, vector_t<int_t<std::uint32_t>>& localSourceRows);
}

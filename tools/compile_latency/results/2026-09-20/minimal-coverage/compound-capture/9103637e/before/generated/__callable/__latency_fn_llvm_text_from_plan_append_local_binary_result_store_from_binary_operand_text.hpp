#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendBinaryOperandRow;
struct BackendLocalOperandRow;
bool_t __latency_fn_llvm_text_from_plan_append_local_binary_result_store_from_binary_operand_text(str::text_builder& lines, int_t<> valueId, BackendLocalOperandRow localOperand, BackendBinaryOperandRow binaryOperand, vector_t<int_t<std::uint32_t>>& localSourceRows, vector_t<int_t<std::uint32_t>>& localTypeRefs);
}

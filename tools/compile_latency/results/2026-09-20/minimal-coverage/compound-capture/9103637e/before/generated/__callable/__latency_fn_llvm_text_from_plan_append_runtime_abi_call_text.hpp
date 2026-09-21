#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SemanticRuntimeAbiBridgeDescriptorRow;
void __latency_fn_llvm_text_from_plan_append_runtime_abi_call_text(str::text_builder& lines, shared_p<SemanticRuntimeAbiBridgeDescriptorRow> row, const string_t& resultName, const string_t& argumentText);
}

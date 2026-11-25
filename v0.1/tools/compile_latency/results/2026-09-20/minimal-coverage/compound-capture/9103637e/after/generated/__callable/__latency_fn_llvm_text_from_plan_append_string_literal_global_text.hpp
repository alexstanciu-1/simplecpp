#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendStringLiteralOperandRow;
void __latency_fn_llvm_text_from_plan_append_string_literal_global_text(str::text_builder& lines, int_t<> valueId, shared_p<BackendStringLiteralOperandRow> literal);
}

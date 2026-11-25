#include <scpp/lang/php.hpp>
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_parameter_add_function_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_parameter_add_function_text_from_value.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_parameter_add_function_text_from_value.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_parameter_add_function_text(const string_t& llvmFunctionName, FrontendLiteralPayloadRow rightLiteral) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::parameter_add_function_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[196]);
	return __latency_fn_llvm_text_from_plan_parameter_add_function_text_from_value(llvmFunctionName, cast<int_t<>>(rightLiteral->numeric_payload));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_parameter_add_function_text_from_value(const string_t& llvmFunctionName, int_t<> rightLiteralValue) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::parameter_add_function_text_from_value", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[197]);
	string_t text = required_cast<string_t>((string_t("define i64 ") + cast<string_t>(llvmFunctionName) + string_t("(i64 %arg_1) {\n")));
	text = (cast<string_t>(text) + string_t("entry:\n"));
	text = (cast<string_t>(text) + string_t("  %binary_add_1 = add i64 %arg_1, ") + cast<string_t>(rightLiteralValue) + string_t("\n"));
	text = (cast<string_t>(text) + string_t("  ret i64 %binary_add_1\n"));
	text = (cast<string_t>(text) + string_t("}\n\n"));
	return text;
}

}

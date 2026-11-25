#include <scpp/lang/php.hpp>
#include "__callable/__latency_fn_llvm_text_from_plan_direct_call_function_text_from_target_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_direct_call_function_text_from_local_reference_argument.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_direct_call_function_text_from_target_text(const string_t& llvmFunctionName, const string_t& returnType, const string_t& targetFunctionText, const string_t& targetName, const string_t& argumentListText) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::direct_call_function_text_from_target_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[201]);
	if (static_cast<bool>((php::identical(targetFunctionText, string_t("")) || php::identical(targetName, string_t(""))))) {
		return string_t("");
	}
	string_t text = required_cast<string_t>(targetFunctionText);
	text = (cast<string_t>(text) + string_t("define ") + cast<string_t>(returnType) + string_t(" ") + cast<string_t>(llvmFunctionName) + string_t("() {\n"));
	text = (cast<string_t>(text) + string_t("entry:\n"));
	text = (cast<string_t>(text) + string_t("  %direct_call_result_1 = call ") + cast<string_t>(returnType) + string_t(" ") + cast<string_t>(targetName) + string_t("(") + cast<string_t>(argumentListText) + string_t(")\n"));
	text = (cast<string_t>(text) + string_t("  ret ") + cast<string_t>(returnType) + string_t(" %direct_call_result_1\n"));
	text = (cast<string_t>(text) + string_t("}\n\n"));
	return text;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_direct_call_function_text_from_local_reference_argument(const string_t& llvmFunctionName, const string_t& returnType, const string_t& targetFunctionText, const string_t& targetName, int_t<std::uint32_t> localTypeRefId, int_t<std::int32_t> initialValue) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::direct_call_function_text_from_local_reference_argument", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[202]);
	if (static_cast<bool>((php::identical(targetFunctionText, string_t("")) || php::identical(targetName, string_t(""))))) {
		return string_t("");
	}
	string_t localType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(cast<int_t<std::uint32_t>>(localTypeRefId)));
	if (static_cast<bool>(php::identical(localType, string_t("")))) {
		return string_t("");
	}
	int_t<> align = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(cast<int_t<std::uint32_t>>(localTypeRefId)));
	string_t text = required_cast<string_t>(targetFunctionText);
	text = (cast<string_t>(text) + string_t("define ") + cast<string_t>(returnType) + string_t(" ") + cast<string_t>(llvmFunctionName) + string_t("() {\n"));
	text = (cast<string_t>(text) + string_t("entry:\n"));
	text = (cast<string_t>(text) + string_t("  %local_1 = alloca ") + cast<string_t>(localType) + string_t(", align ") + cast<string_t>(align) + string_t("\n"));
	text = (cast<string_t>(text) + string_t("  store ") + cast<string_t>(localType) + string_t(" ") + cast<string_t>(cast<int_t<>>(initialValue)) + string_t(", ptr %local_1, align ") + cast<string_t>(align) + string_t("\n"));
	text = (cast<string_t>(text) + string_t("  %direct_call_result_1 = call ") + cast<string_t>(returnType) + string_t(" ") + cast<string_t>(targetName) + string_t("(ptr %local_1)\n"));
	text = (cast<string_t>(text) + string_t("  ret ") + cast<string_t>(returnType) + string_t(" %direct_call_result_1\n"));
	text = (cast<string_t>(text) + string_t("}\n\n"));
	return text;
}

}

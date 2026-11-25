#include "__types/BackendFunctionCompositionInput.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_function_composition_input_from_call_arguments_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_function_text_from_call_composition.hpp"
#include <cstdio>
using namespace scpp;
int main() {
 if (!static_cast<bool>(BackendFunctionCompositionInput::__scpp_static_accepts(BackendFunctionCompositionInput::__scpp_static_token()))) return 4;
 if (static_cast<bool>(BackendFunctionCompositionInput::__scpp_static_accepts(nullptr))) return 5;
 auto input = __latency_fn_llvm_text_from_plan_function_composition_input_from_call_arguments_text(
 string_t("@main"), string_t("i32"), string_t("define i32 @target() { ret i32 42 }\n"), string_t("@target"), string_t(""));
 auto alias = input;
 alias->caller_function_name = string_t("@witness");
 if (!static_cast<bool>(php::identical(input->caller_function_name,string_t("@witness")))) return 1;
 input->caller_function_name = string_t("@main");
 auto output = __latency_fn_llvm_text_from_plan_function_text_from_call_composition(input);
 string_t expected("define i32 @target() { ret i32 42 }\ndefine i32 @main() {\nentry:\n  %direct_call_result_1 = call i32 @target()\n  ret i32 %direct_call_result_1\n}\n\n");
 if (!static_cast<bool>(php::identical(output,expected))) return 2;
 alias->target_function_name = string_t("");
 if (!static_cast<bool>(php::identical(__latency_fn_llvm_text_from_plan_function_text_from_call_composition(input),string_t("")))) return 3;
 std::puts("class_bytes=unchanged;class_identity=ok;shared_aliasing=ok;composition_output=ok;empty_target_rejection=ok");
}

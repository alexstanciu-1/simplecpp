#include <scpp/lang/php.hpp>
#include "__types/SemanticRuntimeAbiBridgeDescriptorRow.hpp"
#include "__types/llvm_abi_call_lowering.hpp"
#include "__callable/__latency_fn_llvm_abi_call_lowering_call_text.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_status_ready_id.hpp"
namespace scpp { extern const int __latency_lines_llvm_abi_call_lowering[]; }
namespace scpp {
bool_t llvm_abi_call_lowering::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == llvm_abi_call_lowering::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_llvm_abi_call_lowering[]; }
namespace scpp {
string_t __latency_fn_llvm_abi_call_lowering_call_text(shared_p<SemanticRuntimeAbiBridgeDescriptorRow> row, const string_t& resultName, const string_t& argumentText) {
	SCPP_CALL_DEPTH_GUARD("llvm_abi_call_lowering::call_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/llvm_abi_call_lowering.phs", __latency_lines_llvm_abi_call_lowering[0]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(row->call_lowering_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_status_ready_id()))))) {
		return string_t("");
	}
	if (static_cast<bool>(php::identical(row->llvm_return_type, string_t("void")))) {
		return (string_t("call void @") + cast<string_t>(row->runtime_symbol) + string_t("(") + cast<string_t>(argumentText) + string_t(")\n"));
	}
	return (cast<string_t>(resultName) + string_t(" = call ") + cast<string_t>(row->llvm_return_type) + string_t(" @") + cast<string_t>(row->runtime_symbol) + string_t("(") + cast<string_t>(argumentText) + string_t(")\n"));
}

}

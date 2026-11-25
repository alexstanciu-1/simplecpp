#include <scpp/lang/php.hpp>
#include "__types/RuntimeAbiBridgeArtifact.hpp"
#include "__types/SemanticRuntimeAbiBridgeDescriptorRow.hpp"
#include "__types/llvm_abi_declarations.hpp"
#include "__callable/__latency_fn_llvm_abi_declarations_declaration_text.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_status_ready_id.hpp"
#include "__callable/__latency_fn_llvm_abi_declarations_declaration_key.hpp"
#include "__callable/__latency_fn_llvm_abi_declarations_has_declaration_key.hpp"
#include "__callable/__latency_fn_llvm_abi_declarations_declaration_key.hpp"
#include "__callable/__latency_fn_llvm_abi_declarations_declaration_text.hpp"
#include "__callable/__latency_fn_llvm_abi_declarations_has_declaration_key.hpp"
#include "__callable/__latency_fn_llvm_abi_declarations_module_declarations.hpp"
namespace scpp { extern const int __latency_lines_llvm_abi_declarations[]; }
namespace scpp {
bool_t llvm_abi_declarations::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == llvm_abi_declarations::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_llvm_abi_declarations[]; }
namespace scpp {
string_t __latency_fn_llvm_abi_declarations_declaration_text(shared_p<SemanticRuntimeAbiBridgeDescriptorRow> row) {
	SCPP_CALL_DEPTH_GUARD("llvm_abi_declarations::declaration_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/llvm_abi_declarations.phs", __latency_lines_llvm_abi_declarations[0]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(row->declaration_status_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_status_ready_id()))))) {
		return string_t("");
	}
	string_t argumentSignature = required_cast<string_t>(row->llvm_argument_signature);
	if (static_cast<bool>(php::identical(argumentSignature, string_t("void")))) {
		argumentSignature = string_t("");
	}
	return (string_t("declare ") + cast<string_t>(row->llvm_return_type) + string_t(" @") + cast<string_t>(row->runtime_symbol) + string_t("(") + cast<string_t>(argumentSignature) + string_t(")\n"));
}

}

namespace scpp { extern const int __latency_lines_llvm_abi_declarations[]; }
namespace scpp {
string_t __latency_fn_llvm_abi_declarations_declaration_key(shared_p<SemanticRuntimeAbiBridgeDescriptorRow> row) {
	SCPP_CALL_DEPTH_GUARD("llvm_abi_declarations::declaration_key", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/llvm_abi_declarations.phs", __latency_lines_llvm_abi_declarations[1]);
	return (cast<string_t>(row->runtime_symbol) + string_t("|") + cast<string_t>(row->llvm_return_type) + string_t("|") + cast<string_t>(row->llvm_argument_signature));
}

}

namespace scpp { extern const int __latency_lines_llvm_abi_declarations[]; }
namespace scpp {
bool_t __latency_fn_llvm_abi_declarations_has_declaration_key(const vector_t<string_t>& keys, const string_t& key) {
	SCPP_CALL_DEPTH_GUARD("llvm_abi_declarations::has_declaration_key", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/llvm_abi_declarations.phs", __latency_lines_llvm_abi_declarations[2]);
	auto& __latency_local_0 = keys;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto seenKey = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(seenKey, key))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_llvm_abi_declarations[]; }
namespace scpp {
string_t __latency_fn_llvm_abi_declarations_module_declarations(shared_p<RuntimeAbiBridgeArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("llvm_abi_declarations::module_declarations", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/llvm_abi_declarations.phs", __latency_lines_llvm_abi_declarations[3]);
	string_t out = required_cast<string_t>(string_t(""));
	vector_t<string_t> seenKeys = {};
	php::vector_reserve(seenKeys, cast<int_t<>>(artifact->row_count));
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		string_t key = required_cast<string_t>(__latency_fn_llvm_abi_declarations_declaration_key(row));
		if (static_cast<bool>(php::condition_truthy(__latency_fn_llvm_abi_declarations_has_declaration_key(seenKeys, key)))) {
			continue;
		}
		(void) seenKeys.push_back(key);
		out = (cast<string_t>(out) + cast<string_t>(__latency_fn_llvm_abi_declarations_declaration_text(row)));
	}
	return out;
}

}

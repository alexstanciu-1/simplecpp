#include <scpp/lang/php.hpp>
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectReferenceResolution.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/callable_contract_identity.hpp"
#include "__callable/__latency_fn_callable_contract_identity_debug_string_from_parts.hpp"
#include "__callable/__latency_fn_callable_contract_identity_debug_string.hpp"
#include "__callable/__latency_fn_callable_contract_identity_debug_string.hpp"
#include "__callable/__latency_fn_callable_contract_identity_debug_string_from_artifacts.hpp"
#include "__callable/__latency_fn_callable_contract_identity_debug_string_from_parts.hpp"
#include "__callable/__latency_fn_project_callable_contracts_from_symbol_key.hpp"
#include "__callable/__latency_fn_project_callable_contracts_target_symbol_key.hpp"
#include "__callable/__latency_fn_callable_contract_identity_stable_hash_from_artifacts.hpp"
#include "__callable/__latency_fn_project_callable_contracts_from_symbol_key.hpp"
#include "__callable/__latency_fn_project_callable_contracts_target_symbol_key.hpp"
namespace scpp { extern const int __latency_lines_callable_contract_identity[]; }
namespace scpp {
bool_t callable_contract_identity::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == callable_contract_identity::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_callable_contract_identity[]; }
namespace scpp {
string_t __latency_fn_callable_contract_identity_debug_string_from_parts(const string_t& fromSymbolKey, const string_t& targetSymbolKey) {
	SCPP_CALL_DEPTH_GUARD("callable_contract_identity::debug_string_from_parts", "/tmp/scpp-edit-latency-20260919/app/compile/model/callable_contract_identity.phs", __latency_lines_callable_contract_identity[0]);
	return (string_t("callable_contract:") + cast<string_t>(fromSymbolKey) + string_t("->") + cast<string_t>(targetSymbolKey));
}

}

namespace scpp { extern const int __latency_lines_callable_contract_identity[]; }
namespace scpp {
string_t __latency_fn_callable_contract_identity_debug_string(ProjectCallableContractRow row) {
	SCPP_CALL_DEPTH_GUARD("callable_contract_identity::debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/model/callable_contract_identity.phs", __latency_lines_callable_contract_identity[1]);
	if (static_cast<bool>((cast<int_t<>>(row->contract_id) > static_cast<int_t<> >(0)))) {
		return (string_t("callable_contract_id:") + cast<string_t>(row->contract_id));
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_callable_contract_identity[]; }
namespace scpp {
string_t __latency_fn_callable_contract_identity_debug_string_from_artifacts(ProjectCallableContractRow row, shared_p<ProjectReferenceResolution> references, shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("callable_contract_identity::debug_string_from_artifacts", "/tmp/scpp-edit-latency-20260919/app/compile/model/callable_contract_identity.phs", __latency_lines_callable_contract_identity[2]);
	string_t fromSymbolKey = required_cast<string_t>(__latency_fn_project_callable_contracts_from_symbol_key(row, references, symbols));
	string_t targetSymbolKey = required_cast<string_t>(__latency_fn_project_callable_contracts_target_symbol_key(row, references, symbols));
	if (static_cast<bool>((php::not_identical(fromSymbolKey, string_t("")) || php::not_identical(targetSymbolKey, string_t(""))))) {
		return __latency_fn_callable_contract_identity_debug_string_from_parts(fromSymbolKey, targetSymbolKey);
	}
	return __latency_fn_callable_contract_identity_debug_string(row);
}

}

namespace scpp { extern const int __latency_lines_callable_contract_identity[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_callable_contract_identity_stable_hash_from_artifacts(ProjectCallableContractRow row, shared_p<ProjectReferenceResolution> references, shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("callable_contract_identity::stable_hash_from_artifacts", "/tmp/scpp-edit-latency-20260919/app/compile/model/callable_contract_identity.phs", __latency_lines_callable_contract_identity[3]);
	string_t identity = required_cast<string_t>((string_t("callable_contract:v2:") + cast<string_t>(__latency_fn_project_callable_contracts_from_symbol_key(row, references, symbols)) + string_t(":") + cast<string_t>(__latency_fn_project_callable_contracts_target_symbol_key(row, references, symbols)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->actual_arg_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->expected_arg_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->return_type_ref_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id))));
	return php::stable_hash_string_u64(identity);
}

}

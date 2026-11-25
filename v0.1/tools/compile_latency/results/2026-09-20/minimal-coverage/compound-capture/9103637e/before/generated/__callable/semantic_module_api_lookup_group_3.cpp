#include <scpp/lang/php.hpp>
#include "__types/SemanticModuleApiLookupRow.hpp"
#include "__callable/__latency_fn_semantic_module_api_lookup_row_by_source_name.hpp"
#include "__callable/__latency_fn_semantic_module_api_lookup_rows.hpp"
#include "__callable/__latency_fn_semantic_module_api_lookup_arity_unknown_count.hpp"
#include "__callable/__latency_fn_semantic_module_api_lookup_availability_policy_count.hpp"
#include "__callable/__latency_fn_semantic_module_api_lookup_contract_incomplete_count.hpp"
#include "__callable/__latency_fn_semantic_module_api_lookup_debug_string.hpp"
#include "__callable/__latency_fn_semantic_module_api_lookup_default_runtime_module_count.hpp"
#include "__callable/__latency_fn_semantic_module_api_lookup_explicit_project_module_count.hpp"
#include "__callable/__latency_fn_semantic_module_api_lookup_owner_count.hpp"
#include "__callable/__latency_fn_semantic_module_api_lookup_return_unknown_count.hpp"
#include "__callable/__latency_fn_semantic_module_api_lookup_row_count.hpp"
#include "__callable/__latency_fn_semantic_module_api_lookup_shared_runtime_core_count.hpp"
#include "__callable/__latency_fn_semantic_module_api_lookup_signature_missing_count.hpp"
#include "__callable/__latency_fn_semantic_module_api_lookup_source_consumption_blocked_count.hpp"
#include "__callable/__latency_fn_semantic_module_api_lookup_strict_profile_core_count.hpp"
namespace scpp { extern const int __latency_lines_semantic_module_api_lookup[]; }
namespace scpp {
shared_p<SemanticModuleApiLookupRow> __latency_fn_semantic_module_api_lookup_row_by_source_name(const string_t& sourceName) {
	SCPP_CALL_DEPTH_GUARD("semantic_module_api_lookup::row_by_source_name", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_module_api_lookup.phs", __latency_lines_semantic_module_api_lookup[42]);
	auto __latency_local_0 = __latency_fn_semantic_module_api_lookup_rows();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(row->source_name, sourceName))) {
			return row;
		}
	}
	shared_p<SemanticModuleApiLookupRow> empty = create<SemanticModuleApiLookupRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_semantic_module_api_lookup[]; }
namespace scpp {
string_t __latency_fn_semantic_module_api_lookup_debug_string() {
	SCPP_CALL_DEPTH_GUARD("semantic_module_api_lookup::debug_string", "/tmp/scpp-edit-latency-20260919/app/generated/semantic_module_api_lookup.phs", __latency_lines_semantic_module_api_lookup[43]);
	return (string_t("semantic_module_api_lookup:rows=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_module_api_lookup_row_count())) + string_t(":owners=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_module_api_lookup_owner_count())) + string_t(":availability_policies=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_module_api_lookup_availability_policy_count())) + string_t(":strict_profile_core=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_module_api_lookup_strict_profile_core_count())) + string_t(":shared_runtime_core=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_module_api_lookup_shared_runtime_core_count())) + string_t(":default_runtime_module=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_module_api_lookup_default_runtime_module_count())) + string_t(":explicit_project_module=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_module_api_lookup_explicit_project_module_count())) + string_t(":contract_incomplete=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_module_api_lookup_contract_incomplete_count())) + string_t(":signature_missing=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_module_api_lookup_signature_missing_count())) + string_t(":arity_unknown=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_module_api_lookup_arity_unknown_count())) + string_t(":return_unknown=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_module_api_lookup_return_unknown_count())) + string_t(":source_consumption_blocked=") + cast<string_t>(cast<int_t<>>(__latency_fn_semantic_module_api_lookup_source_consumption_blocked_count())) + string_t(":state=module_contract_overlay_partial_signature_ready_source_consumption_blocked"));
}

}

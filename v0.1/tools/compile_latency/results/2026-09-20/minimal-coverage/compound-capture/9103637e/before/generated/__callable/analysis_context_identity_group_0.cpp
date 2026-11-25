#include <scpp/lang/php.hpp>
#include "__types/AnalysisEntryContextRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/analysis_context_identity.hpp"
#include "__callable/__latency_fn_analysis_context_identity_debug_string.hpp"
#include "__callable/__latency_fn_project_dependency_graph_symbol_key_for_id.hpp"
#include "__callable/__latency_fn_analysis_context_identity_equals.hpp"
#include "__callable/__latency_fn_analysis_context_identity_debug_string.hpp"
#include "__callable/__latency_fn_analysis_context_identity_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_analysis_context_identity[]; }
namespace scpp {
bool_t analysis_context_identity::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == analysis_context_identity::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_analysis_context_identity[]; }
namespace scpp {
string_t __latency_fn_analysis_context_identity_debug_string(AnalysisEntryContextRow row, shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("analysis_context_identity::debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context_identity.phs", __latency_lines_analysis_context_identity[0]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->context_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	return (string_t("analysis_entry:") + cast<string_t>(__latency_fn_project_dependency_graph_symbol_key_for_id(symbols, row->symbol_id)));
}

}

namespace scpp { extern const int __latency_lines_analysis_context_identity[]; }
namespace scpp {
bool_t __latency_fn_analysis_context_identity_equals(AnalysisEntryContextRow left, AnalysisEntryContextRow right) {
	SCPP_CALL_DEPTH_GUARD("analysis_context_identity::equals", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context_identity.phs", __latency_lines_analysis_context_identity[1]);
	if (static_cast<bool>(((cast<int_t<>>(left->context_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(right->context_id) > static_cast<int_t<> >(0))))) {
		return bool_t(php::identical(cast<int_t<>>(left->context_id), cast<int_t<>>(right->context_id)));
	}
	return (php::identical(cast<int_t<>>(left->source_unit_id), cast<int_t<>>(right->source_unit_id)) && php::identical(cast<int_t<>>(left->symbol_id), cast<int_t<>>(right->symbol_id)));
}

}

namespace scpp { extern const int __latency_lines_analysis_context_identity[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_analysis_context_identity_stable_hash(AnalysisEntryContextRow row, shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("analysis_context_identity::stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context_identity.phs", __latency_lines_analysis_context_identity[2]);
	string_t identity = required_cast<string_t>((string_t("analysis_entry:v2:") + cast<string_t>(__latency_fn_analysis_context_identity_debug_string(row, symbols)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_unit_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->declaration_node_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->graph_symbol_node_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->first_reference_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->reference_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->first_callable_contract_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->callable_contract_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->first_dependency_edge_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->dependency_edge_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->downstream_table_status_id))));
	return php::stable_hash_string_u64(identity);
}

}

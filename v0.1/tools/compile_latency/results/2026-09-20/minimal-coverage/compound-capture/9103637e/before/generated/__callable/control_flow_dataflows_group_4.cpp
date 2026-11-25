#include <scpp/lang/php.hpp>
#include "__types/ControlFlowDataflowArtifact.hpp"
#include "__types/ControlFlowDataflowRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_from_frontend_model.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_project_tsv.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_kind_function_id.hpp"
#include "__callable/__latency_fn_pure_tdd_stage_artifacts_clean_tsv_value.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_diagnostic_none_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_has_blocking_diagnostic.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_from_frontend_model.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_has_blocking_diagnostic.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_project_has_blocking_diagnostic.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_kind_function_id.hpp"
namespace scpp { extern const int __latency_lines_control_flow_dataflows[]; }
namespace scpp {
string_t __latency_fn_control_flow_dataflows_project_tsv(shared_p<ProjectSymbolIndex> symbols, shared_p<FrontendModel> model, const string_t& sourceText) {
	SCPP_CALL_DEPTH_GUARD("control_flow_dataflows::project_tsv", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_dataflows.phs", __latency_lines_control_flow_dataflows[41]);
	string_t text = required_cast<string_t>(string_t("row_id\towner_symbol_id\tsource_unit_id\trow_kind_id\tbranch_kind_id\tstatus_id\tdiagnostic_id\tstatement_node_id\tlocal_name_id\tlocal_name\tlocal_source_row_id\ttype_ref_id\n"));
	auto __latency_local_0 = symbols->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto symbol = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::not_identical(cast<int_t<>>(symbol->symbol_kind_id), cast<int_t<>>(__latency_fn_project_symbol_index_symbol_kind_function_id())) || php::not_identical(cast<int_t<>>(symbol->source_unit_id), cast<int_t<>>(model->source_unit_id))) || php::identical(cast<int_t<>>(symbol->body_first_node_id), static_cast<int_t<> >(0))))) {
			continue;
		}
		shared_p<ControlFlowDataflowArtifact> artifact = __latency_fn_control_flow_dataflows_from_frontend_model(symbol, model, sourceText);
		auto __latency_local_2 = artifact->rows;
		for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
			auto row = __latency_local_3.value_copy();
			string_t name = required_cast<string_t>(string_t(""));
			int_t<> nameIndex = required_cast<int_t<>>((cast<int_t<>>(row->local_name_id) - static_cast<int_t<> >(1)));
			if (static_cast<bool>(((nameIndex >= static_cast<int_t<> >(0)) && (nameIndex < php::count(artifact->local_names))))) {
				name = artifact->local_names[nameIndex];
			}
			text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(row->row_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->owner_symbol_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->source_unit_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->row_kind_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->branch_kind_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->diagnostic_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->statement_node_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->local_name_id)) + string_t("\t") + cast<string_t>(__latency_fn_pure_tdd_stage_artifacts_clean_tsv_value(name)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->local_source_row_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->type_ref_id)) + string_t("\n"));
		}
	}
	return text;
}

}

namespace scpp { extern const int __latency_lines_control_flow_dataflows[]; }
namespace scpp {
bool_t __latency_fn_control_flow_dataflows_has_blocking_diagnostic(shared_p<ControlFlowDataflowArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("control_flow_dataflows::has_blocking_diagnostic", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_dataflows.phs", __latency_lines_control_flow_dataflows[42]);
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(row->diagnostic_id), cast<int_t<>>(__latency_fn_control_flow_dataflows_diagnostic_none_id()))))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_control_flow_dataflows[]; }
namespace scpp {
bool_t __latency_fn_control_flow_dataflows_project_has_blocking_diagnostic(shared_p<ProjectSymbolIndex> symbols, shared_p<FrontendModel> model, const string_t& sourceText) {
	SCPP_CALL_DEPTH_GUARD("control_flow_dataflows::project_has_blocking_diagnostic", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_dataflows.phs", __latency_lines_control_flow_dataflows[43]);
	auto __latency_local_0 = symbols->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto symbol = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::not_identical(cast<int_t<>>(symbol->symbol_kind_id), cast<int_t<>>(__latency_fn_project_symbol_index_symbol_kind_function_id())) || php::not_identical(cast<int_t<>>(symbol->source_unit_id), cast<int_t<>>(model->source_unit_id))) || php::identical(cast<int_t<>>(symbol->body_first_node_id), static_cast<int_t<> >(0))))) {
			continue;
		}
		shared_p<ControlFlowDataflowArtifact> artifact = __latency_fn_control_flow_dataflows_from_frontend_model(symbol, model, sourceText);
		if (static_cast<bool>(php::condition_truthy(__latency_fn_control_flow_dataflows_has_blocking_diagnostic(artifact)))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

#include <scpp/lang/php.hpp>
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/ProjectReferenceResolution.hpp"
#include "__types/ProjectReferenceResolutionRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_literal_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_literal_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_actual_arguments_from_call_expression.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_default_actual_arguments_from_resolved_target.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_direct_call_from_call_expression_fields_with_first_argument_type.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_direct_call_from_frontend.hpp"
#include "__callable/__latency_fn_project_reference_resolution_argument_count_from_call_expression.hpp"
#include "__callable/__latency_fn_project_reference_resolution_call_expression_node_from_symbol.hpp"
#include "__callable/__latency_fn_project_reference_resolution_callee_name_from_call_expression.hpp"
#include "__callable/__latency_fn_project_reference_resolution_row_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_row_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
ProjectReferenceResolutionRow __latency_fn_project_reference_resolution_append_direct_call_from_frontend(shared_p<ProjectReferenceResolution> artifact, shared_p<ProjectSymbolIndex> symbols, shared_p<FrontendModel> model, const string_t& sourceText, ProjectSymbolIndexRow fromSymbol) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::append_direct_call_from_frontend", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[79]);
	FrontendNodeRow callNode = __latency_fn_project_reference_resolution_call_expression_node_from_symbol(model, fromSymbol);
	string_t calleeName = required_cast<string_t>(__latency_fn_project_reference_resolution_callee_name_from_call_expression(model, sourceText, callNode));
	int_t<std::uint32_t> actualArgCount = required_cast<int_t<std::uint32_t>>(__latency_fn_project_reference_resolution_argument_count_from_call_expression(model, callNode));
	int_t<std::uint32_t> actualFirstArgumentTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint16_t> actualFirstArgumentStatusId = required_cast<int_t<std::uint16_t>>(__latency_fn_structure_row_ids_none_kind_id());
	if (static_cast<bool>((cast<int_t<>>(callNode->payload_row_id) > static_cast<int_t<> >(0)))) {
		shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
		FrontendExpressionPayloadRow callExpression = __latency_fn_frontend_model_tables_expression_by_id(model, callNode->payload_row_id, counters);
		int_t<std::uint32_t> argumentNodeId = required_cast<int_t<std::uint32_t>>(callExpression->first_argument_node_id);
		if (static_cast<bool>(php::identical(cast<int_t<>>(argumentNodeId), static_cast<int_t<> >(0)))) {
			argumentNodeId = callNode->first_child_node_id;
		}
		if (static_cast<bool>((cast<int_t<>>(argumentNodeId) > static_cast<int_t<> >(0)))) {
			FrontendNodeRow argumentNode = __latency_fn_frontend_model_tables_node_by_id(model, argumentNodeId, counters);
			if (static_cast<bool>((php::identical(cast<int_t<>>(argumentNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) && php::identical(cast<int_t<>>(argumentNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_literal_id()))))) {
				FrontendLiteralPayloadRow argumentLiteral = __latency_fn_frontend_model_tables_literal_by_id(model, argumentNode->payload_row_id, counters);
				actualFirstArgumentTypeRefId = argumentLiteral->type_ref_id;
				actualFirstArgumentStatusId = argumentLiteral->literal_status_id;
			}
		}
	}
	ProjectReferenceResolutionRow reference = __latency_fn_project_reference_resolution_append_direct_call_from_call_expression_fields_with_first_argument_type(artifact, symbols, fromSymbol, callNode->node_id, calleeName, cast<int_t<std::uint32_t>>(actualArgCount), cast<int_t<std::uint32_t>>(actualFirstArgumentTypeRefId), cast<int_t<std::uint16_t>>(actualFirstArgumentStatusId));
	__latency_fn_project_reference_resolution_append_actual_arguments_from_call_expression(artifact, reference, model, callNode);
	__latency_fn_project_reference_resolution_append_default_actual_arguments_from_resolved_target(artifact, reference, symbols);
	return __latency_fn_project_reference_resolution_row_by_id(artifact, reference->reference_id);
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
ProjectReferenceResolutionRow __latency_fn_project_reference_resolution_row_by_id(shared_p<ProjectReferenceResolution> artifact, int_t<std::uint32_t> referenceId) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::row_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[80]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(referenceId, cast<int_t<>>(artifact->reference_count))))) {
		ProjectReferenceResolutionRow row = artifact->rows[__latency_fn_structure_row_ids_dense_index(referenceId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->reference_id), cast<int_t<>>(referenceId)))) {
			return row;
		}
	}
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->reference_id), cast<int_t<>>(referenceId)))) {
			return row;
		}
	}
	ProjectReferenceResolutionRow empty = ProjectReferenceResolutionRow{};
	return empty;
}

}

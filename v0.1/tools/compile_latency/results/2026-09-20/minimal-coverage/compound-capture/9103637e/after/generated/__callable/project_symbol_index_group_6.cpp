#include <scpp/lang/php.hpp>
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/ProjectSymbolParameterRow.hpp"
#include "__types/SourceRangeRow.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_variable_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_list_count__exec.hpp"
#include "__callable/__latency_fn_project_symbol_index_type_ref_name.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_symbol_index_literal_status_ready.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_has_ready_default.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_flag_by_reference_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_flag_by_reference_with_default_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_is_by_reference.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_variable_name_range.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_source_range_text.hpp"
#include "__callable/__latency_fn_frontend_model_tables_literal_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_literal_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_parameter_row.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_flag_by_reference_with_default_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_flag_default_literal_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_is_by_reference.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_variable_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_parameter_row.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_parameter_rows_from_declaration.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_rows_for_symbol.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_has_parameter_rows.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_rows_for_symbol.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_symbol_index_parameter_list_count__exec(shared_p<FrontendModel> model, int_t<std::uint32_t> parameterListNodeId, int_t<std::uint32_t>& firstParameterTypeRefId, string_t& signatureParameterShape) {
	firstParameterTypeRefId = __latency_fn_structure_row_ids_none_id();
	signatureParameterShape = string_t("");
	if (static_cast<bool>(php::identical(cast<int_t<>>(parameterListNodeId), static_cast<int_t<> >(0)))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	int_t<> parameterCount = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> parameterNodeId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(parameterListNodeId));
	while (static_cast<bool>((cast<int_t<>>(parameterNodeId) > static_cast<int_t<> >(0)))) {
		FrontendNodeRow parameterNode = __latency_fn_frontend_model_tables_node_by_id(model, parameterNodeId, counters);
		if (static_cast<bool>((php::not_identical(cast<int_t<>>(parameterNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) || php::not_identical(cast<int_t<>>(parameterNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_variable_id()))))) {
			break;
		}
		FrontendExpressionPayloadRow parameterExpression = __latency_fn_frontend_model_tables_expression_by_id(model, parameterNode->payload_row_id, counters);
		if (static_cast<bool>(php::identical(cast<int_t<>>(firstParameterTypeRefId), static_cast<int_t<> >(0)))) {
			firstParameterTypeRefId = parameterExpression->inferred_type_ref_id;
		}
		if (static_cast<bool>(php::condition_truthy(php::not_identical(signatureParameterShape, string_t(""))))) {
			signatureParameterShape = (cast<string_t>(signatureParameterShape) + string_t(","));
		}
		signatureParameterShape = (cast<string_t>(signatureParameterShape) + cast<string_t>(__latency_fn_project_symbol_index_type_ref_name(parameterExpression->inferred_type_ref_id)));
		parameterCount = (parameterCount + static_cast<int_t<> >(1));
		parameterNodeId = parameterNode->next_sibling_node_id;
	}
	return __latency_fn_structure_row_ids_uint32_from_int(parameterCount);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
bool_t __latency_fn_project_symbol_index_parameter_has_ready_default(ProjectSymbolParameterRow row) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::parameter_has_ready_default", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[67]);
	return (((cast<int_t<>>(row->default_literal_source_row_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(row->default_literal_type_ref_id) > static_cast<int_t<> >(0))) && __latency_fn_project_symbol_index_literal_status_ready(row->default_literal_status_id));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
bool_t __latency_fn_project_symbol_index_parameter_is_by_reference(ProjectSymbolParameterRow row) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::parameter_is_by_reference", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[68]);
	return (php::identical(cast<int_t<>>(row->flags), cast<int_t<>>(__latency_fn_project_symbol_index_parameter_flag_by_reference_id())) || php::identical(cast<int_t<>>(row->flags), cast<int_t<>>(__latency_fn_project_symbol_index_parameter_flag_by_reference_with_default_id())));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
string_t __latency_fn_project_symbol_index_parameter_name(shared_p<ProjectSymbolIndex> index, shared_p<FrontendModel> model, const string_t& sourceText, ProjectSymbolParameterRow row) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::parameter_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[69]);
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	FrontendNodeRow node = __latency_fn_frontend_model_tables_node_by_id(model, row->parameter_source_row_id, counters);
	SourceRangeRow range = __latency_fn_frontend_body_summaries_variable_name_range(model, node, counters);
	return __latency_fn_project_symbol_index_source_range_text(model, sourceText, range->source_range_id);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_symbol_index_append_parameter_row(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow& symbolRow, shared_p<FrontendModel> model, FrontendNodeRow parameterNode, int_t<std::uint32_t> typeRefId, int_t<std::uint16_t> position) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::append_parameter_row", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[70]);
	ProjectSymbolParameterRow row = ProjectSymbolParameterRow{};
	row->parameter_row_id = __latency_fn_structure_row_ids_next_dense_id(php::count(index->parameter_rows));
	row->symbol_id = symbolRow->symbol_id;
	row->parameter_source_row_id = parameterNode->node_id;
	row->type_ref_id = typeRefId;
	row->position = position;
	row->flags = parameterNode->flags;
	if (static_cast<bool>((cast<int_t<>>(parameterNode->first_child_node_id) > static_cast<int_t<> >(0)))) {
		shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
		FrontendNodeRow defaultNode = __latency_fn_frontend_model_tables_node_by_id(model, parameterNode->first_child_node_id, counters);
		if (static_cast<bool>((php::identical(cast<int_t<>>(defaultNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) && php::identical(cast<int_t<>>(defaultNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_literal_id()))))) {
			FrontendLiteralPayloadRow defaultLiteral = __latency_fn_frontend_model_tables_literal_by_id(model, defaultNode->payload_row_id, counters);
			row->default_literal_source_row_id = defaultNode->node_id;
			row->default_literal_type_ref_id = defaultLiteral->type_ref_id;
			row->default_literal_numeric_payload = defaultLiteral->numeric_payload;
			row->default_literal_status_id = defaultLiteral->literal_status_id;
			if (static_cast<bool>(php::condition_truthy(__latency_fn_project_symbol_index_parameter_is_by_reference(row)))) {
				row->flags = __latency_fn_project_symbol_index_parameter_flag_by_reference_with_default_id();
			}
			else {
				row->flags = __latency_fn_project_symbol_index_parameter_flag_default_literal_id();
			}
		}
	}
	(void) index->parameter_rows.append(row);
	if (static_cast<bool>(php::identical(cast<int_t<>>(symbolRow->first_parameter_row_id), static_cast<int_t<> >(0)))) {
		symbolRow->first_parameter_row_id = row->parameter_row_id;
	}
	return row->parameter_row_id;
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
void __latency_fn_project_symbol_index_append_parameter_rows_from_declaration(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow& symbolRow, shared_p<FrontendModel> model, FrontendDeclarationPayloadRow declaration) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::append_parameter_rows_from_declaration", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[71]);
	symbolRow->first_parameter_row_id = __latency_fn_structure_row_ids_none_id();
	if (static_cast<bool>((php::identical(cast<int_t<>>(declaration->parameter_list_node_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(symbolRow->parameter_count), static_cast<int_t<> >(0))))) {
		return;
	}
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	int_t<std::uint32_t> parameterNodeId = required_cast<int_t<std::uint32_t>>(declaration->parameter_list_node_id);
	int_t<> position = required_cast<int_t<>>(static_cast<int_t<> >(1));
	while (static_cast<bool>((cast<int_t<>>(parameterNodeId) > static_cast<int_t<> >(0)))) {
		FrontendNodeRow parameterNode = __latency_fn_frontend_model_tables_node_by_id(model, parameterNodeId, counters);
		if (static_cast<bool>((php::not_identical(cast<int_t<>>(parameterNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) || php::not_identical(cast<int_t<>>(parameterNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_variable_id()))))) {
			break;
		}
		FrontendExpressionPayloadRow parameterExpression = __latency_fn_frontend_model_tables_expression_by_id(model, parameterNode->payload_row_id, counters);
		if (static_cast<bool>((cast<int_t<>>(parameterExpression->inferred_type_ref_id) > static_cast<int_t<> >(0)))) {
			__latency_fn_project_symbol_index_append_parameter_row(index, symbolRow, model, parameterNode, parameterExpression->inferred_type_ref_id, __latency_fn_structure_row_ids_uint16_from_int(position));
		}
		position = (position + static_cast<int_t<> >(1));
		parameterNodeId = parameterNode->next_sibling_node_id;
	}
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
bool_t __latency_fn_project_symbol_index_symbol_has_parameter_rows(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow row) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::symbol_has_parameter_rows", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[72]);
	return (((cast<int_t<>>(row->parameter_count) > static_cast<int_t<> >(0)) && (cast<int_t<>>(row->first_parameter_row_id) > static_cast<int_t<> >(0))) && php::identical(php::count(__latency_fn_project_symbol_index_parameter_rows_for_symbol(index, row)), cast<int_t<>>(row->parameter_count)));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
vector_t<ProjectSymbolParameterRow> __latency_fn_project_symbol_index_parameter_rows_for_symbol(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow row) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::parameter_rows_for_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[73]);
	vector_t<ProjectSymbolParameterRow> rows = {};
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->parameter_count), static_cast<int_t<> >(0)))) {
		return rows;
	}
	php::vector_reserve(rows, cast<int_t<>>(row->parameter_count));
	if (static_cast<bool>((cast<int_t<>>(row->first_parameter_row_id) > static_cast<int_t<> >(0)))) {
		int_t<> startIndex = required_cast<int_t<>>(__latency_fn_structure_row_ids_dense_index(row->first_parameter_row_id));
		int_t<> endIndex = required_cast<int_t<>>((startIndex + cast<int_t<>>(row->parameter_count)));
		if (static_cast<bool>(((startIndex >= static_cast<int_t<> >(0)) && (endIndex <= php::count(index->parameter_rows))))) {
			int_t<> indexLocal = required_cast<int_t<>>(startIndex);
			while (static_cast<bool>((indexLocal < endIndex))) {
				ProjectSymbolParameterRow parameter = index->parameter_rows[indexLocal];
				if (static_cast<bool>(php::identical(cast<int_t<>>(parameter->symbol_id), cast<int_t<>>(row->symbol_id)))) {
					(void) rows.push_back(parameter);
				}
				indexLocal = (indexLocal + static_cast<int_t<> >(1));
			}
			if (static_cast<bool>(php::identical(php::count(rows), cast<int_t<>>(row->parameter_count)))) {
				return rows;
			}
		}
	}
	auto __latency_local_0 = index->parameter_rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto parameter = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(parameter->symbol_id), cast<int_t<>>(row->symbol_id)))) {
			(void) rows.push_back(parameter);
		}
	}
	return rows;
}

}

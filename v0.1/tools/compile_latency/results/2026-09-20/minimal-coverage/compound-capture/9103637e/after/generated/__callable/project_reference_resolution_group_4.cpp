#include <scpp/lang/php.hpp>
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/ProjectReferenceActualArgumentRow.hpp"
#include "__types/ProjectReferenceResolution.hpp"
#include "__types/ProjectReferenceResolutionRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/ProjectSymbolParameterRow.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_project_reference_resolution_update_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_actual_argument.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_actual_argument_from_values.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_actual_argument_from_values.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_builder_literal_status_ready_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_literal_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_literal_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_variable_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_actual_argument_flag_stable_local_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_actual_argument.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_actual_argument_from_values.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_actual_arguments_from_call_expression.hpp"
#include "__callable/__latency_fn_project_reference_resolution_update_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_actual_argument_flag_default_value_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_actual_argument_from_values.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_default_actual_arguments_from_resolved_target.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_resolved_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_update_row.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_has_ready_default.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_rows_for_symbol.hpp"
#include "__callable/__latency_fn_project_symbol_index_row_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_project_reference_resolution_actual_argument_rows.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
void __latency_fn_project_reference_resolution_update_row(shared_p<ProjectReferenceResolution> artifact, ProjectReferenceResolutionRow row) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::update_row", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[49]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(row->reference_id, cast<int_t<>>(artifact->reference_count))))) {
		artifact->rows[__latency_fn_structure_row_ids_dense_index(row->reference_id)] = row;
		return;
	}
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < php::count(artifact->rows)))) {
		ProjectReferenceResolutionRow existingRow = artifact->rows[index];
		if (static_cast<bool>(php::identical(cast<int_t<>>(existingRow->reference_id), cast<int_t<>>(row->reference_id)))) {
			artifact->rows[index] = row;
			return;
		}
		index = (index + static_cast<int_t<> >(1));
	}
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_reference_resolution_append_actual_argument(shared_p<ProjectReferenceResolution> artifact, ProjectReferenceResolutionRow& reference, FrontendNodeRow argumentNode, FrontendLiteralPayloadRow argumentLiteral, int_t<std::uint16_t> position) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::append_actual_argument", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[50]);
	return __latency_fn_project_reference_resolution_append_actual_argument_from_values(artifact, reference, argumentNode->node_id, argumentLiteral->type_ref_id, argumentLiteral->numeric_payload, argumentLiteral->literal_status_id, cast<int_t<std::uint16_t>>(position), __latency_fn_structure_row_ids_none_kind_id());
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_reference_resolution_append_actual_argument_from_values(shared_p<ProjectReferenceResolution> artifact, ProjectReferenceResolutionRow& reference, int_t<std::uint32_t> argumentSourceRowId, int_t<std::uint32_t> typeRefId, int_t<std::int32_t> numericPayload, int_t<std::uint16_t> literalStatusId, int_t<std::uint16_t> position, int_t<std::uint16_t> flags) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::append_actual_argument_from_values", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[51]);
	ProjectReferenceActualArgumentRow row = ProjectReferenceActualArgumentRow{};
	row->argument_row_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->actual_arguments));
	row->reference_id = reference->reference_id;
	row->argument_source_row_id = argumentSourceRowId;
	row->type_ref_id = typeRefId;
	row->numeric_payload = numericPayload;
	row->position = position;
	row->literal_status_id = literalStatusId;
	row->flags = flags;
	(void) artifact->actual_arguments.append(row);
	if (static_cast<bool>(php::identical(cast<int_t<>>(reference->first_actual_argument_row_id), static_cast<int_t<> >(0)))) {
		reference->first_actual_argument_row_id = row->argument_row_id;
	}
	reference->actual_argument_row_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(reference->actual_argument_row_count) + static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::identical(cast<int_t<>>(position), static_cast<int_t<> >(1)))) {
		reference->actual_first_argument_type_ref_id = typeRefId;
		reference->actual_first_argument_status_id = literalStatusId;
	}
	return row->argument_row_id;
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
void __latency_fn_project_reference_resolution_append_actual_arguments_from_call_expression(shared_p<ProjectReferenceResolution> artifact, ProjectReferenceResolutionRow& reference, shared_p<FrontendModel> model, FrontendNodeRow callNode) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::append_actual_arguments_from_call_expression", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[52]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(callNode->payload_row_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(reference->actual_arg_count), static_cast<int_t<> >(0))))) {
		return;
	}
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	FrontendExpressionPayloadRow callExpression = __latency_fn_frontend_model_tables_expression_by_id(model, callNode->payload_row_id, counters);
	int_t<std::uint32_t> argumentNodeId = required_cast<int_t<std::uint32_t>>(callExpression->first_argument_node_id);
	if (static_cast<bool>(php::identical(cast<int_t<>>(argumentNodeId), static_cast<int_t<> >(0)))) {
		argumentNodeId = callNode->first_child_node_id;
	}
	int_t<> position = required_cast<int_t<>>(static_cast<int_t<> >(1));
	while (static_cast<bool>(((cast<int_t<>>(argumentNodeId) > static_cast<int_t<> >(0)) && (position <= cast<int_t<>>(reference->actual_arg_count))))) {
		FrontendNodeRow argumentNode = __latency_fn_frontend_model_tables_node_by_id(model, argumentNodeId, counters);
		if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(argumentNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id()))))) {
			break;
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(argumentNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_literal_id())))) {
			FrontendLiteralPayloadRow argumentLiteral = __latency_fn_frontend_model_tables_literal_by_id(model, argumentNode->payload_row_id, counters);
			__latency_fn_project_reference_resolution_append_actual_argument(artifact, reference, argumentNode, argumentLiteral, __latency_fn_structure_row_ids_uint16_from_int(position));
		}
		else {
			FrontendExpressionPayloadRow argumentExpression = __latency_fn_frontend_model_tables_expression_by_id(model, argumentNode->payload_row_id, counters);
			int_t<std::uint16_t> argumentFlags = required_cast<int_t<std::uint16_t>>(__latency_fn_structure_row_ids_none_kind_id());
			int_t<std::uint16_t> argumentStatusId = required_cast<int_t<std::uint16_t>>(__latency_fn_structure_row_ids_none_kind_id());
			if (static_cast<bool>(((php::identical(cast<int_t<>>(argumentNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_variable_id())) && (cast<int_t<>>(argumentExpression->inferred_type_ref_id) > static_cast<int_t<> >(0))) && php::not_identical(cast<int_t<>>(argumentExpression->inferred_type_ref_id), cast<int_t<>>(__latency_fn_type_refs_unknown_id()))))) {
				argumentFlags = __latency_fn_project_reference_resolution_actual_argument_flag_stable_local_id();
				argumentStatusId = __latency_fn_frontend_model_builder_literal_status_ready_id();
			}
			__latency_fn_project_reference_resolution_append_actual_argument_from_values(artifact, reference, argumentNode->node_id, argumentExpression->inferred_type_ref_id, __latency_fn_structure_row_ids_int32_from_int(static_cast<int_t<> >(0)), cast<int_t<std::uint16_t>>(argumentStatusId), __latency_fn_structure_row_ids_uint16_from_int(position), cast<int_t<std::uint16_t>>(argumentFlags));
		}
		position = (position + static_cast<int_t<> >(1));
		argumentNodeId = argumentNode->next_sibling_node_id;
	}
	__latency_fn_project_reference_resolution_update_row(artifact, reference);
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
void __latency_fn_project_reference_resolution_append_default_actual_arguments_from_resolved_target(shared_p<ProjectReferenceResolution> artifact, ProjectReferenceResolutionRow& reference, shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::append_default_actual_arguments_from_resolved_target", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[53]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(reference->status_id), cast<int_t<>>(__latency_fn_project_reference_resolution_status_resolved_id()))))) {
		return;
	}
	ProjectSymbolIndexRow target = __latency_fn_project_symbol_index_row_by_id(symbols, reference->resolved_symbol_id);
	if (static_cast<bool>((php::identical(cast<int_t<>>(target->parameter_count), static_cast<int_t<> >(0)) || (cast<int_t<>>(reference->actual_arg_count) >= cast<int_t<>>(target->parameter_count))))) {
		return;
	}
	vector_t<ProjectSymbolParameterRow> parameters = required_cast<vector_t<ProjectSymbolParameterRow>>(__latency_fn_project_symbol_index_parameter_rows_for_symbol(symbols, target));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(php::count(parameters), cast<int_t<>>(target->parameter_count))))) {
		return;
	}
	int_t<> startPosition = required_cast<int_t<>>((cast<int_t<>>(reference->actual_arg_count) + static_cast<int_t<> >(1)));
	int_t<> position = required_cast<int_t<>>(startPosition);
	while (static_cast<bool>((position <= cast<int_t<>>(target->parameter_count)))) {
		ProjectSymbolParameterRow parameter = parameters.at((position - static_cast<int_t<> >(1)));
		if (static_cast<bool>(((!__latency_fn_project_symbol_index_parameter_has_ready_default(parameter)) || php::not_identical(cast<int_t<>>(parameter->default_literal_type_ref_id), cast<int_t<>>(parameter->type_ref_id))))) {
			return;
		}
		position = (position + static_cast<int_t<> >(1));
	}
	position = startPosition;
	while (static_cast<bool>((position <= cast<int_t<>>(target->parameter_count)))) {
		ProjectSymbolParameterRow parameter = parameters.at((position - static_cast<int_t<> >(1)));
		__latency_fn_project_reference_resolution_append_actual_argument_from_values(artifact, reference, parameter->default_literal_source_row_id, parameter->default_literal_type_ref_id, parameter->default_literal_numeric_payload, parameter->default_literal_status_id, __latency_fn_structure_row_ids_uint16_from_int(position), __latency_fn_project_reference_resolution_actual_argument_flag_default_value_id());
		position = (position + static_cast<int_t<> >(1));
	}
	reference->actual_arg_count = target->parameter_count;
	__latency_fn_project_reference_resolution_update_row(artifact, reference);
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
vector_t<ProjectReferenceActualArgumentRow> __latency_fn_project_reference_resolution_actual_argument_rows(shared_p<ProjectReferenceResolution> artifact, ProjectReferenceResolutionRow reference) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::actual_argument_rows", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[54]);
	vector_t<ProjectReferenceActualArgumentRow> rows = {};
	if (static_cast<bool>(php::identical(cast<int_t<>>(reference->actual_argument_row_count), static_cast<int_t<> >(0)))) {
		return rows;
	}
	php::vector_reserve(rows, cast<int_t<>>(reference->actual_argument_row_count));
	if (static_cast<bool>((cast<int_t<>>(reference->first_actual_argument_row_id) > static_cast<int_t<> >(0)))) {
		int_t<> startIndex = required_cast<int_t<>>(__latency_fn_structure_row_ids_dense_index(reference->first_actual_argument_row_id));
		int_t<> endIndex = required_cast<int_t<>>((startIndex + cast<int_t<>>(reference->actual_argument_row_count)));
		if (static_cast<bool>(((startIndex >= static_cast<int_t<> >(0)) && (endIndex <= php::count(artifact->actual_arguments))))) {
			int_t<> index = required_cast<int_t<>>(startIndex);
			while (static_cast<bool>((index < endIndex))) {
				ProjectReferenceActualArgumentRow row = artifact->actual_arguments[index];
				if (static_cast<bool>(php::identical(cast<int_t<>>(row->reference_id), cast<int_t<>>(reference->reference_id)))) {
					(void) rows.push_back(row);
				}
				index = (index + static_cast<int_t<> >(1));
			}
			if (static_cast<bool>(php::identical(php::count(rows), cast<int_t<>>(reference->actual_argument_row_count)))) {
				return rows;
			}
		}
	}
	auto __latency_local_0 = artifact->actual_arguments;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->reference_id), cast<int_t<>>(reference->reference_id)))) {
			(void) rows.push_back(row);
		}
	}
	return rows;
}

}

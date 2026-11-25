#include <scpp/lang/php.hpp>
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendNodeSpan.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/SourceRangeRow.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_compiler_profile_events_elapsed_us_since.hpp"
#include "__callable/__latency_fn_compiler_profile_events_now_us.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_range_by_id.hpp"
#include "__callable/__latency_fn_project_symbol_identity_body_shape_key_from_body_shape.hpp"
#include "__callable/__latency_fn_project_symbol_identity_export_shape_key_from_signature_shape.hpp"
#include "__callable/__latency_fn_project_symbol_index_add_u32.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_parameter_rows_from_declaration.hpp"
#include "__callable/__latency_fn_project_symbol_index_declaration_body_shape.hpp"
#include "__callable/__latency_fn_project_symbol_index_declaration_function_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_declaration_qualified_function_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_declaration_return_type_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_intern_body_shape.hpp"
#include "__callable/__latency_fn_project_symbol_index_intern_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_intern_qualified_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_intern_signature_shape.hpp"
#include "__callable/__latency_fn_project_symbol_index_intern_source_unit_key.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_list_count.hpp"
#include "__callable/__latency_fn_project_symbol_index_row_from_declaration.hpp"
#include "__callable/__latency_fn_project_symbol_index_scope_global_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_status_indexed_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_kind_function_id.hpp"
#include "__callable/__latency_fn_source_buffers_content_hash32.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_key_from_table.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_declaration_node_id_by_payload_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_declaration_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_declaration_function_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_declaration_use_function_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_from_declaration_sidecar.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_function_import_from_declaration.hpp"
#include "__callable/__latency_fn_project_symbol_index_record_function_name_symbol.hpp"
#include "__callable/__latency_fn_project_symbol_index_row_from_declaration.hpp"
#include "__callable/__latency_fn_project_symbol_index_update_lookup_policy.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_tables_declaration_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_first_node_span.hpp"
#include "__callable/__latency_fn_frontend_model_tables_next_node_span.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_span_is_empty.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_declaration_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_declaration_function_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_declaration_use_function_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_span_node_at.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_from_model_by_node_scan.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_function_import_from_declaration.hpp"
#include "__callable/__latency_fn_project_symbol_index_record_function_name_symbol.hpp"
#include "__callable/__latency_fn_project_symbol_index_row_from_declaration.hpp"
#include "__callable/__latency_fn_project_symbol_index_update_lookup_policy.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
ProjectSymbolIndexRow __latency_fn_project_symbol_index_row_from_declaration(shared_p<ProjectSymbolIndex> index, shared_p<SourceUnitTable> sourceUnits, SourceUnitTableRow sourceUnit, const string_t& sourceText, shared_p<FrontendModel> model, FrontendNodeRow declarationNode, FrontendDeclarationPayloadRow declaration) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::row_from_declaration", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[74]);
	string_t functionName = required_cast<string_t>(__latency_fn_project_symbol_index_declaration_function_name(model, sourceText, declaration));
	string_t qualifiedFunctionName = required_cast<string_t>(__latency_fn_project_symbol_index_declaration_qualified_function_name(model, sourceText, declaration, functionName));
	string_t returnTypeName = required_cast<string_t>(__latency_fn_project_symbol_index_declaration_return_type_name(model, sourceText, declarationNode, declaration));
	int_t<std::uint32_t> parameterCount = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	string_t signatureParameterShape = required_cast<string_t>(string_t(""));
	int_t<std::uint32_t> firstParameterTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	if (static_cast<bool>((cast<int_t<>>(declaration->parameter_list_node_id) > static_cast<int_t<> >(0)))) {
		parameterCount = __latency_fn_project_symbol_index_parameter_list_count(model, declaration->parameter_list_node_id, firstParameterTypeRefId, signatureParameterShape);
	}
	string_t signatureShape = required_cast<string_t>((string_t("function ") + cast<string_t>(qualifiedFunctionName) + string_t("(") + cast<string_t>(signatureParameterShape) + string_t("):") + cast<string_t>(returnTypeName)));
	int_t<std::uint32_t> bodyShapeLength = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> bodyShapeWalkRows = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	bool_t usedCachedDigest = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
	int_t<std::uint64_t> bodyShapeStarted = required_cast<int_t<std::uint64_t>>(__latency_fn_compiler_profile_events_now_us());
	string_t bodyShape = required_cast<string_t>(__latency_fn_project_symbol_index_declaration_body_shape(model, sourceText, declaration, bodyShapeLength, bodyShapeWalkRows, usedCachedDigest));
	index->body_shape_elapsed_us = __latency_fn_project_symbol_index_add_u32(index->body_shape_elapsed_us, cast<int_t<>>(__latency_fn_compiler_profile_events_elapsed_us_since(bodyShapeStarted)));
	index->body_shape_source_bytes = __latency_fn_project_symbol_index_add_u32(index->body_shape_source_bytes, cast<int_t<>>(bodyShapeLength));
	index->body_shape_walk_rows = __latency_fn_project_symbol_index_add_u32(index->body_shape_walk_rows, cast<int_t<>>(bodyShapeWalkRows));
	if (static_cast<bool>(php::condition_truthy(usedCachedDigest))) {
		index->body_shape_cached_rows = __latency_fn_project_symbol_index_add_u32(index->body_shape_cached_rows, static_cast<int_t<> >(1));
	}
	int_t<std::uint32_t> bodySourceRangeId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint32_t> bodyStartOffset = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	if (static_cast<bool>((cast<int_t<>>(declaration->body_node_id) > static_cast<int_t<> >(0)))) {
		shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
		FrontendNodeRow bodyNode = __latency_fn_frontend_model_tables_node_by_id(model, declaration->body_node_id, counters);
		bodySourceRangeId = bodyNode->source_range_id;
		if (static_cast<bool>((cast<int_t<>>(bodySourceRangeId) > static_cast<int_t<> >(0)))) {
			SourceRangeRow bodyRange = __latency_fn_frontend_model_tables_source_range_by_id(model, bodySourceRangeId, counters);
			bodyStartOffset = bodyRange->start_offset;
		}
	}
	index->declaration_rows_indexed = __latency_fn_project_symbol_index_add_u32(index->declaration_rows_indexed, static_cast<int_t<> >(1));
	ProjectSymbolIndexRow row = ProjectSymbolIndexRow{};
	row->symbol_id = __latency_fn_structure_row_ids_next_dense_id(php::count(index->rows));
	row->source_unit_id = sourceUnit->source_unit_id;
	row->source_row_id = declarationNode->node_id;
	row->symbol_kind_id = __latency_fn_project_symbol_index_symbol_kind_function_id();
	row->scope_id = __latency_fn_project_symbol_index_scope_global_id();
	row->status_id = __latency_fn_project_symbol_index_status_indexed_id();
	row->name_id = __latency_fn_project_symbol_index_intern_name(index, functionName);
	row->qualified_name_id = __latency_fn_project_symbol_index_intern_qualified_name(index, qualifiedFunctionName);
	row->return_type_ref_id = declaration->declared_type_ref_id;
	row->signature_shape_id = __latency_fn_project_symbol_index_intern_signature_shape(index, signatureShape);
	row->body_shape_id = __latency_fn_project_symbol_index_intern_body_shape(index, bodyShape);
	row->source_unit_key_id = __latency_fn_project_symbol_index_intern_source_unit_key(index, __latency_fn_source_identity_source_unit_key_from_table(sourceUnits, sourceUnit));
	row->parameter_count = parameterCount;
	row->first_parameter_row_id = __latency_fn_structure_row_ids_none_id();
	row->first_parameter_type_ref_id = firstParameterTypeRefId;
	row->body_first_node_id = declaration->body_node_id;
	row->body_source_range_id = bodySourceRangeId;
	row->body_start_offset = bodyStartOffset;
	row->body_length = bodyShapeLength;
	row->body_node_count = bodyShapeWalkRows;
	row->public_surface_hash = __latency_fn_source_buffers_content_hash32(__latency_fn_project_symbol_identity_export_shape_key_from_signature_shape(signatureShape));
	row->body_hash = __latency_fn_source_buffers_content_hash32(__latency_fn_project_symbol_identity_body_shape_key_from_body_shape(bodyShape));
	__latency_fn_project_symbol_index_append_parameter_rows_from_declaration(index, row, model, declaration);
	return row;
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
bool_t __latency_fn_project_symbol_index_append_from_declaration_sidecar(shared_p<ProjectSymbolIndex> index, shared_p<SourceUnitTable> sourceUnits, SourceUnitTableRow sourceUnit, const string_t& sourceText, shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::append_from_declaration_sidecar", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[75]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(php::count(model->declarations), php::count(model->declaration_node_ids))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	vector_t<FrontendNodeRow> declarationNodes = {};
	php::vector_reserve(declarationNodes, php::count(model->declarations));
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	auto __latency_local_0 = model->declarations;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto declaration = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(declaration->payload_id), static_cast<int_t<> >(0)))) {
			return bool_t(static_cast<bool_t>(false));
		}
		int_t<std::uint32_t> nodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_declaration_node_id_by_payload_id(model, declaration->payload_id));
		if (static_cast<bool>(php::identical(cast<int_t<>>(nodeId), static_cast<int_t<> >(0)))) {
			return bool_t(static_cast<bool_t>(false));
		}
		FrontendNodeRow node = __latency_fn_frontend_model_tables_node_by_id(model, nodeId, counters);
		if (static_cast<bool>((php::identical(cast<int_t<>>(node->node_id), static_cast<int_t<> >(0)) || php::not_identical(cast<int_t<>>(node->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_declaration_id()))))) {
			return bool_t(static_cast<bool_t>(false));
		}
		(void) declarationNodes.push_back(node);
	}
	int_t<> declarationIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_2 = model->declarations;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto declaration = __latency_local_3.value_copy();
		FrontendNodeRow node = declarationNodes.at(declarationIndex);
		if (static_cast<bool>(php::identical(cast<int_t<>>(node->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_declaration_function_id())))) {
			ProjectSymbolIndexRow row = __latency_fn_project_symbol_index_row_from_declaration(index, sourceUnits, sourceUnit, sourceText, model, node, declaration);
			__latency_fn_project_symbol_index_record_function_name_symbol(index, row);
			(void) index->rows.append(row);
		}
		else {
			if (static_cast<bool>(php::identical(cast<int_t<>>(node->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_declaration_use_function_id())))) {
				__latency_fn_project_symbol_index_append_function_import_from_declaration(index, sourceUnit, sourceText, model, node, declaration);
			}
		}
		declarationIndex = (declarationIndex + static_cast<int_t<> >(1));
	}
	index->symbol_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(index->rows));
	__latency_fn_project_symbol_index_update_lookup_policy(index);
	return bool_t(static_cast<bool_t>(true));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
void __latency_fn_project_symbol_index_append_from_model_by_node_scan(shared_p<ProjectSymbolIndex> index, shared_p<SourceUnitTable> sourceUnits, SourceUnitTableRow sourceUnit, const string_t& sourceText, shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::append_from_model_by_node_scan", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[76]);
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	FrontendNodeSpan span = __latency_fn_frontend_model_tables_first_node_span(model);
	while (static_cast<bool>((!__latency_fn_frontend_model_tables_node_span_is_empty(span)))) {
		int_t<> offset = required_cast<int_t<>>(static_cast<int_t<> >(0));
		while (static_cast<bool>((offset < cast<int_t<>>(span->count)))) {
			FrontendNodeRow node = __latency_fn_frontend_model_tables_span_node_at(model, span, offset);
			if (static_cast<bool>((php::identical(cast<int_t<>>(node->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_declaration_id())) && php::identical(cast<int_t<>>(node->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_declaration_function_id()))))) {
				FrontendDeclarationPayloadRow declaration = __latency_fn_frontend_model_tables_declaration_by_id(model, node->payload_row_id, counters);
				ProjectSymbolIndexRow row = __latency_fn_project_symbol_index_row_from_declaration(index, sourceUnits, sourceUnit, sourceText, model, node, declaration);
				__latency_fn_project_symbol_index_record_function_name_symbol(index, row);
				(void) index->rows.append(row);
			}
			else {
				if (static_cast<bool>((php::identical(cast<int_t<>>(node->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_declaration_id())) && php::identical(cast<int_t<>>(node->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_declaration_use_function_id()))))) {
					FrontendDeclarationPayloadRow declaration = __latency_fn_frontend_model_tables_declaration_by_id(model, node->payload_row_id, counters);
					__latency_fn_project_symbol_index_append_function_import_from_declaration(index, sourceUnit, sourceText, model, node, declaration);
				}
			}
			offset = (offset + static_cast<int_t<> >(1));
		}
		span = __latency_fn_frontend_model_tables_next_node_span(model, span);
	}
	index->symbol_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(index->rows));
	__latency_fn_project_symbol_index_update_lookup_policy(index);
}

}

#include <scpp/lang/php.hpp>
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeList.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/LineStartRow.hpp"
#include "__types/SourceBufferRow.hpp"
#include "__types/SourceRangeExtRow.hpp"
#include "__types/SourceRangeRow.hpp"
#include "__counter/lookup_call_count.hpp"
#include "__counter/update_call_count.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_range_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_range_ext_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_line_start_by_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_clear_declaration_node_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_has_node_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_record_declaration_node_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_replace_node_family_count.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_node.hpp"
#include "__callable/__latency_fn_frontend_node_lists_row_by_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_update_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_declaration_payload.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_statement_payload.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_expression_payload.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_patch_node_links.hpp"
#include "__callable/__latency_fn_frontend_model_tables_update_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_equals.hpp"
#include "__callable/__latency_fn_frontend_model_tables_declaration_equals.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_buffer_equals.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
SourceRangeRow __latency_fn_frontend_model_tables_source_range_by_id(shared_p<FrontendModel> model, int_t<std::uint32_t> sourceRangeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::source_range_by_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[72]);
	__latency_counter_lookup_call_count(counters) = (__latency_counter_lookup_call_count(counters) + static_cast<int_t<> >(1));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sourceRangeId, php::count(model->source_ranges))))) {
		return model->source_ranges[__latency_fn_structure_row_ids_dense_index(sourceRangeId)];
	}
	SourceRangeRow empty = SourceRangeRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
SourceRangeExtRow __latency_fn_frontend_model_tables_source_range_ext_by_id(shared_p<FrontendModel> model, int_t<std::uint32_t> sourceRangeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::source_range_ext_by_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[73]);
	__latency_counter_lookup_call_count(counters) = (__latency_counter_lookup_call_count(counters) + static_cast<int_t<> >(1));
	auto __latency_local_0 = model->source_range_exts;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->source_range_id), cast<int_t<>>(sourceRangeId)))) {
			return row;
		}
	}
	SourceRangeExtRow empty = SourceRangeExtRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
LineStartRow __latency_fn_frontend_model_tables_line_start_by_index(shared_p<FrontendModel> model, int_t<std::uint32_t> lineStartIndex, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::line_start_by_index", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[74]);
	__latency_counter_lookup_call_count(counters) = (__latency_counter_lookup_call_count(counters) + static_cast<int_t<> >(1));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(lineStartIndex, php::count(model->line_starts))))) {
		return model->line_starts[__latency_fn_structure_row_ids_dense_index(lineStartIndex)];
	}
	LineStartRow empty = LineStartRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_tables_update_node(shared_p<FrontendModel> model, FrontendNodeRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::update_node", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[75]);
	if (static_cast<bool>((!__latency_fn_frontend_model_tables_has_node_id(model, row->node_id)))) {
		return bool_t(static_cast<bool_t>(false));
	}
	FrontendNodeRow previous = __latency_fn_frontend_node_lists_row_by_id(model->node_rows, row->node_id);
	__latency_fn_frontend_node_lists_update_by_id(model->node_rows, row);
	__latency_fn_frontend_model_tables_replace_node_family_count(model, previous->row_family_id, row->row_family_id);
	__latency_fn_frontend_model_tables_clear_declaration_node_id(model, previous);
	__latency_fn_frontend_model_tables_record_declaration_node_id(model, row, row->node_id);
	__latency_counter_update_call_count(counters) = (__latency_counter_update_call_count(counters) + static_cast<int_t<> >(1));
	return bool_t(static_cast<bool_t>(true));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_tables_update_declaration_payload(shared_p<FrontendModel> model, FrontendDeclarationPayloadRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::update_declaration_payload", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[76]);
	if (static_cast<bool>((!__latency_fn_structure_row_ids_has_dense_id(row->payload_id, php::count(model->declarations))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	model->declarations[__latency_fn_structure_row_ids_dense_index(row->payload_id)] = row;
	__latency_counter_update_call_count(counters) = (__latency_counter_update_call_count(counters) + static_cast<int_t<> >(1));
	return bool_t(static_cast<bool_t>(true));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_tables_update_statement_payload(shared_p<FrontendModel> model, FrontendStatementPayloadRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::update_statement_payload", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[77]);
	if (static_cast<bool>((!__latency_fn_structure_row_ids_has_dense_id(row->payload_id, php::count(model->statements))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	model->statements[__latency_fn_structure_row_ids_dense_index(row->payload_id)] = row;
	__latency_counter_update_call_count(counters) = (__latency_counter_update_call_count(counters) + static_cast<int_t<> >(1));
	return bool_t(static_cast<bool_t>(true));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_tables_update_expression_payload(shared_p<FrontendModel> model, FrontendExpressionPayloadRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::update_expression_payload", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[78]);
	if (static_cast<bool>((!__latency_fn_structure_row_ids_has_dense_id(row->payload_id, php::count(model->expressions))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	model->expressions[__latency_fn_structure_row_ids_dense_index(row->payload_id)] = row;
	__latency_counter_update_call_count(counters) = (__latency_counter_update_call_count(counters) + static_cast<int_t<> >(1));
	return bool_t(static_cast<bool_t>(true));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_tables_patch_node_links(shared_p<FrontendModel> model, int_t<std::uint32_t> nodeId, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> firstChildNodeId, int_t<std::uint32_t> nextSiblingNodeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::patch_node_links", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[79]);
	FrontendNodeRow row = __latency_fn_frontend_model_tables_node_by_id(model, cast<int_t<std::uint32_t>>(nodeId), counters);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->node_id), static_cast<int_t<> >(0)))) {
		return bool_t(static_cast<bool_t>(false));
	}
	row->parent_node_id = parentNodeId;
	row->first_child_node_id = firstChildNodeId;
	row->next_sibling_node_id = nextSiblingNodeId;
	return __latency_fn_frontend_model_tables_update_node(model, row, counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_tables_node_equals(FrontendNodeRow left, FrontendNodeRow right) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::node_equals", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[80]);
	if (static_cast<bool>(((cast<int_t<>>(left->node_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(right->node_id) > static_cast<int_t<> >(0))))) {
		return bool_t(php::identical(cast<int_t<>>(left->node_id), cast<int_t<>>(right->node_id)));
	}
	return (((((php::identical(cast<int_t<>>(left->parent_node_id), cast<int_t<>>(right->parent_node_id)) && php::identical(cast<int_t<>>(left->row_family_id), cast<int_t<>>(right->row_family_id))) && php::identical(cast<int_t<>>(left->row_kind_id), cast<int_t<>>(right->row_kind_id))) && php::identical(cast<int_t<>>(left->payload_kind_id), cast<int_t<>>(right->payload_kind_id))) && php::identical(cast<int_t<>>(left->payload_row_id), cast<int_t<>>(right->payload_row_id))) && php::identical(cast<int_t<>>(left->source_range_id), cast<int_t<>>(right->source_range_id)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_tables_declaration_equals(FrontendDeclarationPayloadRow left, FrontendDeclarationPayloadRow right) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::declaration_equals", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[81]);
	if (static_cast<bool>(((cast<int_t<>>(left->payload_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(right->payload_id) > static_cast<int_t<> >(0))))) {
		return bool_t(php::identical(cast<int_t<>>(left->payload_id), cast<int_t<>>(right->payload_id)));
	}
	return ((((((((((((php::identical(cast<int_t<>>(left->declaration_kind_id), cast<int_t<>>(right->declaration_kind_id)) && php::identical(cast<int_t<>>(left->name_id), cast<int_t<>>(right->name_id))) && php::identical(cast<int_t<>>(left->name_source_range_id), cast<int_t<>>(right->name_source_range_id))) && php::identical(cast<int_t<>>(left->namespace_name_id), cast<int_t<>>(right->namespace_name_id))) && php::identical(cast<int_t<>>(left->namespace_source_range_id), cast<int_t<>>(right->namespace_source_range_id))) && php::identical(cast<int_t<>>(left->symbol_id), cast<int_t<>>(right->symbol_id))) && php::identical(cast<int_t<>>(left->declared_type_ref_id), cast<int_t<>>(right->declared_type_ref_id))) && php::identical(cast<int_t<>>(left->parameter_list_node_id), cast<int_t<>>(right->parameter_list_node_id))) && php::identical(cast<int_t<>>(left->body_node_id), cast<int_t<>>(right->body_node_id))) && php::identical(cast<int_t<>>(left->body_source_length), cast<int_t<>>(right->body_source_length))) && php::identical(cast<int_t<>>(left->body_content_hash), cast<int_t<>>(right->body_content_hash))) && php::identical(cast<int_t<>>(left->body_shape_walk_rows), cast<int_t<>>(right->body_shape_walk_rows))) && php::identical(cast<int_t<>>(left->source_range_id), cast<int_t<>>(right->source_range_id)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_tables_source_buffer_equals(SourceBufferRow left, SourceBufferRow right) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::source_buffer_equals", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[82]);
	if (static_cast<bool>(((cast<int_t<>>(left->source_buffer_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(right->source_buffer_id) > static_cast<int_t<> >(0))))) {
		return bool_t(php::identical(cast<int_t<>>(left->source_buffer_id), cast<int_t<>>(right->source_buffer_id)));
	}
	return (((php::identical(cast<int_t<>>(left->source_unit_id), cast<int_t<>>(right->source_unit_id)) && php::identical(cast<int_t<>>(left->content_hash), cast<int_t<>>(right->content_hash))) && php::identical(cast<int_t<>>(left->byte_length), cast<int_t<>>(right->byte_length))) && php::identical(cast<int_t<>>(left->status_id), cast<int_t<>>(right->status_id)));
}

}

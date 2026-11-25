#include <scpp/lang/php.hpp>
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNamePayloadRow.hpp"
#include "__types/FrontendNodeList.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/FrontendTypeSyntaxPayloadRow.hpp"
#include "__types/LineStartRow.hpp"
#include "__types/SourceBufferRow.hpp"
#include "__types/SourceRangeExtRow.hpp"
#include "__types/SourceRangeRow.hpp"
#include "__types/source_buffers.hpp"
#include "__counter/append_call_count.hpp"
#include "__counter/reserve_call_count.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_literal_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_tables_payload_kind_name_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_tables_reserve_model.hpp"
#include "__callable/__latency_fn_frontend_node_lists_reserve.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_source_buffer.hpp"
#include "__callable/__latency_fn_frontend_model_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_source_range.hpp"
#include "__callable/__latency_fn_frontend_model_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_source_range_ext.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_line_start.hpp"
#include "__callable/__latency_fn_frontend_model_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_increment_node_family_count.hpp"
#include "__callable/__latency_fn_frontend_model_tables_record_declaration_node_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_append.hpp"
#include "__callable/__latency_fn_frontend_node_lists_row_count.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_declaration_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_ensure_declaration_node_id_slot.hpp"
#include "__callable/__latency_fn_frontend_model_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_tables_ensure_declaration_node_id_slot.hpp"
#include "__callable/__latency_fn_frontend_model_tables_none_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_ensure_declaration_node_id_slot.hpp"
#include "__callable/__latency_fn_frontend_model_tables_record_declaration_node_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_declaration_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_frontend_model_tables_clear_declaration_node_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_none_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_declaration_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_model_tables_payload_kind_literal_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::payload_kind_literal_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[33]);
	return __latency_fn_frontend_model_tables_uint16_from_int(static_cast<int_t<> >(5));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_model_tables_payload_kind_name_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::payload_kind_name_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[34]);
	return __latency_fn_frontend_model_tables_uint16_from_int(static_cast<int_t<> >(6));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
void __latency_fn_frontend_model_tables_reserve_model(shared_p<FrontendModel> model, int_t<> sourceBufferCapacity, int_t<> sourceRangeCapacity, int_t<> sourceRangeExtCapacity, int_t<> lineStartCapacity, int_t<> nodeCapacity, int_t<> declarationCapacity, int_t<> statementCapacity, int_t<> expressionCapacity, int_t<> typeSyntaxCapacity, int_t<> literalCapacity, int_t<> nameCapacity, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::reserve_model", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[35]);
	php::vector_reserve(model->source_buffers, sourceBufferCapacity);
	php::vector_reserve(model->source_ranges, sourceRangeCapacity);
	php::vector_reserve(model->source_range_exts, sourceRangeExtCapacity);
	php::vector_reserve(model->line_starts, lineStartCapacity);
	__latency_fn_frontend_node_lists_reserve(model->node_rows, nodeCapacity);
	php::vector_reserve(model->declaration_node_ids, declarationCapacity);
	php::vector_reserve(model->declarations, declarationCapacity);
	php::vector_reserve(model->statements, statementCapacity);
	php::vector_reserve(model->expressions, expressionCapacity);
	php::vector_reserve(model->type_syntaxes, typeSyntaxCapacity);
	php::vector_reserve(model->literals, literalCapacity);
	php::vector_reserve(model->names, nameCapacity);
	__latency_counter_reserve_call_count(counters) = (__latency_counter_reserve_call_count(counters) + static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_tables_append_source_buffer(shared_p<FrontendModel> model, SourceBufferRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::append_source_buffer", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[36]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->source_buffer_id), static_cast<int_t<> >(0)))) {
		row->source_buffer_id = __latency_fn_frontend_model_tables_uint32_from_int((php::count(model->source_buffers) + static_cast<int_t<> >(1)));
	}
	(void) model->source_buffers.append(row);
	model->source_buffer_id = row->source_buffer_id;
	__latency_counter_append_call_count(counters) = (__latency_counter_append_call_count(counters) + static_cast<int_t<> >(1));
	return row->source_buffer_id;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_tables_append_source_range(shared_p<FrontendModel> model, SourceRangeRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::append_source_range", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[37]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->source_range_id), static_cast<int_t<> >(0)))) {
		row->source_range_id = __latency_fn_frontend_model_tables_uint32_from_int((cast<int_t<>>(model->source_range_count) + static_cast<int_t<> >(1)));
	}
	(void) model->source_ranges.append(row);
	model->source_range_count = __latency_fn_frontend_model_tables_uint32_from_int((cast<int_t<>>(model->source_range_count) + static_cast<int_t<> >(1)));
	__latency_counter_append_call_count(counters) = (__latency_counter_append_call_count(counters) + static_cast<int_t<> >(1));
	return row->source_range_id;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_tables_append_source_range_ext(shared_p<FrontendModel> model, SourceRangeExtRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::append_source_range_ext", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[38]);
	(void) model->source_range_exts.append(row);
	__latency_counter_append_call_count(counters) = (__latency_counter_append_call_count(counters) + static_cast<int_t<> >(1));
	return row->source_range_id;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_tables_append_line_start(shared_p<FrontendModel> model, LineStartRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::append_line_start", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[39]);
	(void) model->line_starts.append(row);
	model->line_start_count = __latency_fn_frontend_model_tables_uint32_from_int(php::count(model->line_starts));
	__latency_counter_append_call_count(counters) = (__latency_counter_append_call_count(counters) + static_cast<int_t<> >(1));
	return __latency_fn_frontend_model_tables_uint32_from_int(php::count(model->line_starts));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_tables_append_node(shared_p<FrontendModel> model, FrontendNodeRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::append_node", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[40]);
	int_t<std::uint32_t> rowId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_node_lists_append(model->node_rows, row));
	model->node_count = __latency_fn_frontend_node_lists_row_count(model->node_rows);
	__latency_fn_frontend_model_tables_increment_node_family_count(model, row->row_family_id);
	__latency_fn_frontend_model_tables_record_declaration_node_id(model, row, cast<int_t<std::uint32_t>>(rowId));
	__latency_counter_append_call_count(counters) = (__latency_counter_append_call_count(counters) + static_cast<int_t<> >(1));
	return cast<int_t<std::uint32_t>>(rowId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_tables_append_declaration_payload(shared_p<FrontendModel> model, FrontendDeclarationPayloadRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::append_declaration_payload", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[41]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->payload_id), static_cast<int_t<> >(0)))) {
		row->payload_id = __latency_fn_frontend_model_tables_uint32_from_int((php::count(model->declarations) + static_cast<int_t<> >(1)));
	}
	(void) model->declarations.append(row);
	__latency_fn_frontend_model_tables_ensure_declaration_node_id_slot(model, row->payload_id);
	__latency_counter_append_call_count(counters) = (__latency_counter_append_call_count(counters) + static_cast<int_t<> >(1));
	return row->payload_id;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
void __latency_fn_frontend_model_tables_ensure_declaration_node_id_slot(shared_p<FrontendModel> model, int_t<std::uint32_t> payloadId) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::ensure_declaration_node_id_slot", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[42]);
	while (static_cast<bool>((php::count(model->declaration_node_ids) < cast<int_t<>>(payloadId)))) {
		{
		auto __latency_local_0 = __latency_fn_frontend_model_tables_none_id();
		(void) model->declaration_node_ids.append(__latency_local_0);
		}
	}
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
void __latency_fn_frontend_model_tables_record_declaration_node_id(shared_p<FrontendModel> model, FrontendNodeRow row, int_t<std::uint32_t> nodeId) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::record_declaration_node_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[43]);
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(row->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_declaration_id())) || php::identical(cast<int_t<>>(row->payload_row_id), static_cast<int_t<> >(0))))) {
		return;
	}
	__latency_fn_frontend_model_tables_ensure_declaration_node_id_slot(model, row->payload_row_id);
	model->declaration_node_ids[__latency_fn_structure_row_ids_dense_index(row->payload_row_id)] = nodeId;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
void __latency_fn_frontend_model_tables_clear_declaration_node_id(shared_p<FrontendModel> model, FrontendNodeRow row) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::clear_declaration_node_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[44]);
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(row->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_declaration_id())) || php::identical(cast<int_t<>>(row->payload_row_id), static_cast<int_t<> >(0))))) {
		return;
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(row->payload_row_id, php::count(model->declaration_node_ids))))) {
		model->declaration_node_ids[__latency_fn_structure_row_ids_dense_index(row->payload_row_id)] = __latency_fn_frontend_model_tables_none_id();
	}
}

}

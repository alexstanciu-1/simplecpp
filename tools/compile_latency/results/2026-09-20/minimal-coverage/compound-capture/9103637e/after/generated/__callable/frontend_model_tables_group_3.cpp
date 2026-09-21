#include <scpp/lang/php.hpp>
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNamePayloadRow.hpp"
#include "__types/FrontendNodeList.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/FrontendTypeSyntaxPayloadRow.hpp"
#include "__counter/append_call_count.hpp"
#include "__callable/__latency_fn_frontend_model_tables_declaration_node_id_by_payload_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_statement_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_expression_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_type_syntax_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_literal_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_name_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_tables_has_node_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_has_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_count.hpp"
#include "__callable/__latency_fn_frontend_node_lists_row_count.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_storage_kind_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_nodes_use_segmented.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uses_segmented.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_segment_count.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_segment_reserved_bytes.hpp"
#include "__callable/__latency_fn_frontend_model_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_node_lists_reserved_segment_bytes.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_segment_slack_bytes.hpp"
#include "__callable/__latency_fn_frontend_model_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_node_lists_segment_slack_bytes.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_retained_old_generation_bytes.hpp"
#include "__callable/__latency_fn_frontend_model_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_node_lists_retained_old_generation_bytes.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_tables_declaration_node_id_by_payload_id(shared_p<FrontendModel> model, int_t<std::uint32_t> payloadId) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::declaration_node_id_by_payload_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[45]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(payloadId, php::count(model->declaration_node_ids))))) {
		return model->declaration_node_ids[__latency_fn_structure_row_ids_dense_index(payloadId)];
	}
	return __latency_fn_frontend_model_tables_none_id();
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_tables_append_statement_payload(shared_p<FrontendModel> model, FrontendStatementPayloadRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::append_statement_payload", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[46]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->payload_id), static_cast<int_t<> >(0)))) {
		row->payload_id = __latency_fn_frontend_model_tables_uint32_from_int((php::count(model->statements) + static_cast<int_t<> >(1)));
	}
	(void) model->statements.append(row);
	__latency_counter_append_call_count(counters) = (__latency_counter_append_call_count(counters) + static_cast<int_t<> >(1));
	return row->payload_id;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_tables_append_expression_payload(shared_p<FrontendModel> model, FrontendExpressionPayloadRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::append_expression_payload", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[47]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->payload_id), static_cast<int_t<> >(0)))) {
		row->payload_id = __latency_fn_frontend_model_tables_uint32_from_int((php::count(model->expressions) + static_cast<int_t<> >(1)));
	}
	(void) model->expressions.append(row);
	__latency_counter_append_call_count(counters) = (__latency_counter_append_call_count(counters) + static_cast<int_t<> >(1));
	return row->payload_id;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_tables_append_type_syntax_payload(shared_p<FrontendModel> model, FrontendTypeSyntaxPayloadRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::append_type_syntax_payload", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[48]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->payload_id), static_cast<int_t<> >(0)))) {
		row->payload_id = __latency_fn_frontend_model_tables_uint32_from_int((php::count(model->type_syntaxes) + static_cast<int_t<> >(1)));
	}
	(void) model->type_syntaxes.append(row);
	__latency_counter_append_call_count(counters) = (__latency_counter_append_call_count(counters) + static_cast<int_t<> >(1));
	return row->payload_id;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_tables_append_literal_payload(shared_p<FrontendModel> model, FrontendLiteralPayloadRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::append_literal_payload", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[49]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->payload_id), static_cast<int_t<> >(0)))) {
		row->payload_id = __latency_fn_frontend_model_tables_uint32_from_int((php::count(model->literals) + static_cast<int_t<> >(1)));
	}
	(void) model->literals.append(row);
	model->literal_count = __latency_fn_frontend_model_tables_uint32_from_int(php::count(model->literals));
	__latency_counter_append_call_count(counters) = (__latency_counter_append_call_count(counters) + static_cast<int_t<> >(1));
	return row->payload_id;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_tables_append_name_payload(shared_p<FrontendModel> model, FrontendNamePayloadRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::append_name_payload", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[50]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->payload_id), static_cast<int_t<> >(0)))) {
		row->payload_id = __latency_fn_frontend_model_tables_uint32_from_int((php::count(model->names) + static_cast<int_t<> >(1)));
	}
	(void) model->names.append(row);
	model->name_count = __latency_fn_frontend_model_tables_uint32_from_int(php::count(model->names));
	__latency_counter_append_call_count(counters) = (__latency_counter_append_call_count(counters) + static_cast<int_t<> >(1));
	return row->payload_id;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_tables_has_node_id(shared_p<FrontendModel> model, int_t<std::uint32_t> nodeId) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::has_node_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[51]);
	return __latency_fn_frontend_node_lists_has_id(model->node_rows, nodeId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_tables_node_count(shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::node_count", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[52]);
	return __latency_fn_frontend_node_lists_row_count(model->node_rows);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_model_tables_node_storage_kind_id(shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::node_storage_kind_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[53]);
	return model->node_rows->storage_kind_id;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_tables_nodes_use_segmented(shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::nodes_use_segmented", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[54]);
	return __latency_fn_frontend_node_lists_uses_segmented(model->node_rows);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_tables_node_segment_count(shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::node_segment_count", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[55]);
	return model->node_rows->segment_count;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_tables_node_segment_reserved_bytes(shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::node_segment_reserved_bytes", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[56]);
	return __latency_fn_frontend_model_tables_uint32_from_int(__latency_fn_frontend_node_lists_reserved_segment_bytes(model->node_rows));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_tables_node_segment_slack_bytes(shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::node_segment_slack_bytes", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[57]);
	return __latency_fn_frontend_model_tables_uint32_from_int(__latency_fn_frontend_node_lists_segment_slack_bytes(model->node_rows));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_model_tables_node_retained_old_generation_bytes(shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::node_retained_old_generation_bytes", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[58]);
	return __latency_fn_frontend_model_tables_uint32_from_int(__latency_fn_frontend_node_lists_retained_old_generation_bytes(model->node_rows));
}

}

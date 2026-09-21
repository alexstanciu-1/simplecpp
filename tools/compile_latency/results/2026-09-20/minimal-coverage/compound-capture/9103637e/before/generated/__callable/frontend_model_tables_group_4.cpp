#include <scpp/lang/php.hpp>
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNamePayloadRow.hpp"
#include "__types/FrontendNodeList.hpp"
#include "__types/FrontendNodeListRef.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendNodeSpan.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/FrontendTypeSyntaxPayloadRow.hpp"
#include "__types/SourceBufferRow.hpp"
#include "__types/source_buffers.hpp"
#include "__counter/lookup_call_count.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_list_ref.hpp"
#include "__callable/__latency_fn_frontend_node_lists_list_ref.hpp"
#include "__callable/__latency_fn_frontend_model_tables_first_node_span.hpp"
#include "__callable/__latency_fn_frontend_node_lists_first_span.hpp"
#include "__callable/__latency_fn_frontend_model_tables_next_node_span.hpp"
#include "__callable/__latency_fn_frontend_node_lists_next_span.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_span_is_empty.hpp"
#include "__callable/__latency_fn_frontend_node_lists_span_is_empty.hpp"
#include "__callable/__latency_fn_frontend_model_tables_span_node_at.hpp"
#include "__callable/__latency_fn_frontend_node_lists_span_node_at.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_buffer_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_row_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_declaration_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_type_syntax_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_literal_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_name_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
FrontendNodeListRef __latency_fn_frontend_model_tables_node_list_ref(shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::node_list_ref", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[59]);
	return __latency_fn_frontend_node_lists_list_ref(model->node_rows, model->source_unit_id);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
FrontendNodeSpan __latency_fn_frontend_model_tables_first_node_span(shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::first_node_span", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[60]);
	return __latency_fn_frontend_node_lists_first_span(model->node_rows);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
FrontendNodeSpan __latency_fn_frontend_model_tables_next_node_span(shared_p<FrontendModel> model, FrontendNodeSpan span) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::next_node_span", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[61]);
	return __latency_fn_frontend_node_lists_next_span(model->node_rows, span);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_tables_node_span_is_empty(FrontendNodeSpan span) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::node_span_is_empty", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[62]);
	return __latency_fn_frontend_node_lists_span_is_empty(span);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
FrontendNodeRow __latency_fn_frontend_model_tables_span_node_at(shared_p<FrontendModel> model, FrontendNodeSpan span, int_t<> offset) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::span_node_at", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[63]);
	return __latency_fn_frontend_node_lists_span_node_at(model->node_rows, span, offset);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
SourceBufferRow __latency_fn_frontend_model_tables_source_buffer_by_id(shared_p<FrontendModel> model, int_t<std::uint32_t> sourceBufferId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::source_buffer_by_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[64]);
	__latency_counter_lookup_call_count(counters) = (__latency_counter_lookup_call_count(counters) + static_cast<int_t<> >(1));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sourceBufferId, php::count(model->source_buffers))))) {
		return model->source_buffers[__latency_fn_structure_row_ids_dense_index(sourceBufferId)];
	}
	SourceBufferRow empty = SourceBufferRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
FrontendNodeRow __latency_fn_frontend_model_tables_node_by_id(shared_p<FrontendModel> model, int_t<std::uint32_t> nodeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::node_by_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[65]);
	__latency_counter_lookup_call_count(counters) = (__latency_counter_lookup_call_count(counters) + static_cast<int_t<> >(1));
	return __latency_fn_frontend_node_lists_row_by_id(model->node_rows, nodeId);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
FrontendDeclarationPayloadRow __latency_fn_frontend_model_tables_declaration_by_id(shared_p<FrontendModel> model, int_t<std::uint32_t> payloadId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::declaration_by_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[66]);
	__latency_counter_lookup_call_count(counters) = (__latency_counter_lookup_call_count(counters) + static_cast<int_t<> >(1));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(payloadId, php::count(model->declarations))))) {
		return model->declarations[__latency_fn_structure_row_ids_dense_index(payloadId)];
	}
	FrontendDeclarationPayloadRow empty = FrontendDeclarationPayloadRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
FrontendStatementPayloadRow __latency_fn_frontend_model_tables_statement_by_id(shared_p<FrontendModel> model, int_t<std::uint32_t> payloadId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::statement_by_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[67]);
	__latency_counter_lookup_call_count(counters) = (__latency_counter_lookup_call_count(counters) + static_cast<int_t<> >(1));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(payloadId, php::count(model->statements))))) {
		return model->statements[__latency_fn_structure_row_ids_dense_index(payloadId)];
	}
	FrontendStatementPayloadRow empty = FrontendStatementPayloadRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
FrontendExpressionPayloadRow __latency_fn_frontend_model_tables_expression_by_id(shared_p<FrontendModel> model, int_t<std::uint32_t> payloadId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::expression_by_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[68]);
	__latency_counter_lookup_call_count(counters) = (__latency_counter_lookup_call_count(counters) + static_cast<int_t<> >(1));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(payloadId, php::count(model->expressions))))) {
		return model->expressions[__latency_fn_structure_row_ids_dense_index(payloadId)];
	}
	FrontendExpressionPayloadRow empty = FrontendExpressionPayloadRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
FrontendTypeSyntaxPayloadRow __latency_fn_frontend_model_tables_type_syntax_by_id(shared_p<FrontendModel> model, int_t<std::uint32_t> payloadId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::type_syntax_by_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[69]);
	__latency_counter_lookup_call_count(counters) = (__latency_counter_lookup_call_count(counters) + static_cast<int_t<> >(1));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(payloadId, php::count(model->type_syntaxes))))) {
		return model->type_syntaxes[__latency_fn_structure_row_ids_dense_index(payloadId)];
	}
	FrontendTypeSyntaxPayloadRow empty = FrontendTypeSyntaxPayloadRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
FrontendLiteralPayloadRow __latency_fn_frontend_model_tables_literal_by_id(shared_p<FrontendModel> model, int_t<std::uint32_t> payloadId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::literal_by_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[70]);
	__latency_counter_lookup_call_count(counters) = (__latency_counter_lookup_call_count(counters) + static_cast<int_t<> >(1));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(payloadId, php::count(model->literals))))) {
		return model->literals[__latency_fn_structure_row_ids_dense_index(payloadId)];
	}
	FrontendLiteralPayloadRow empty = FrontendLiteralPayloadRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
FrontendNamePayloadRow __latency_fn_frontend_model_tables_name_by_id(shared_p<FrontendModel> model, int_t<std::uint32_t> payloadId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::name_by_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[71]);
	__latency_counter_lookup_call_count(counters) = (__latency_counter_lookup_call_count(counters) + static_cast<int_t<> >(1));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(payloadId, php::count(model->names))))) {
		return model->names[__latency_fn_structure_row_ids_dense_index(payloadId)];
	}
	FrontendNamePayloadRow empty = FrontendNamePayloadRow{};
	return empty;
}

}

#include <scpp/lang/php.hpp>
#include "__types/FrontendBodySummaryArtifact.hpp"
#include "__types/FrontendBodySummaryRow.hpp"
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendLocalBindingSummaryRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNamePayloadRow.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/SourceRangeRow.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_from_declaration.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_from_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_tables_declaration_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_source_range_text_equals.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_variable_name_range.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_name_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_variable_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_range_by_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_variable_name_range.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_variable_name_text.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_variable_name_payload_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_variable_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_ready_literal_from_node.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_ready_literal_payload.hpp"
#include "__callable/__latency_fn_frontend_model_tables_literal_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_literal_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_literal_status_ready.hpp"
#include "__callable/__latency_fn_frontend_model_builder_literal_status_ready_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_literal_status_ready.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_ready_literal_payload.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_expression_type_ref_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_literal_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_literal_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_append_local.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_append_row.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_status_blocked_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_summary_kind_condition_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_summary_kind_expression_dependency_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
shared_p<FrontendBodySummaryArtifact> __latency_fn_frontend_body_summaries_from_symbol(shared_p<FrontendModel> model, const string_t& sourceText, ProjectSymbolIndexRow symbol, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::from_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[16]);
	FrontendNodeRow declarationNode = __latency_fn_frontend_model_tables_node_by_id(model, symbol->source_row_id, counters);
	if (static_cast<bool>(php::identical(cast<int_t<>>(declarationNode->node_id), static_cast<int_t<> >(0)))) {
		shared_p<FrontendBodySummaryArtifact> empty = create<FrontendBodySummaryArtifact>();
		return empty;
	}
	FrontendDeclarationPayloadRow declaration = __latency_fn_frontend_model_tables_declaration_by_id(model, declarationNode->payload_row_id, counters);
	return __latency_fn_frontend_body_summaries_from_declaration(model, sourceText, declaration);
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
bool_t __latency_fn_frontend_body_summaries_source_range_text_equals(const string_t& sourceText, SourceRangeRow left, SourceRangeRow right) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::source_range_text_equals", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[17]);
	if (static_cast<bool>(((php::identical(cast<int_t<>>(left->source_range_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(right->source_range_id), static_cast<int_t<> >(0))) || php::not_identical(cast<int_t<>>(left->length), cast<int_t<>>(right->length))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return bool_t(php::identical(str::substr(sourceText, cast<int_t<>>(left->start_offset), cast<int_t<>>(left->length)), str::substr(sourceText, cast<int_t<>>(right->start_offset), cast<int_t<>>(right->length))));
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
SourceRangeRow __latency_fn_frontend_body_summaries_variable_name_range(shared_p<FrontendModel> model, FrontendNodeRow variableNode, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::variable_name_range", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[18]);
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(variableNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) || php::not_identical(cast<int_t<>>(variableNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_variable_id()))))) {
		SourceRangeRow empty = SourceRangeRow{};
		return empty;
	}
	FrontendExpressionPayloadRow expression = __latency_fn_frontend_model_tables_expression_by_id(model, variableNode->payload_row_id, counters);
	FrontendNamePayloadRow name = __latency_fn_frontend_model_tables_name_by_id(model, expression->callee_name_id, counters);
	return __latency_fn_frontend_model_tables_source_range_by_id(model, name->source_range_id, counters);
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
string_t __latency_fn_frontend_body_summaries_variable_name_text(shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow variableNode, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::variable_name_text", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[19]);
	SourceRangeRow nameRange = __latency_fn_frontend_body_summaries_variable_name_range(model, variableNode, counters);
	if (static_cast<bool>(php::identical(cast<int_t<>>(nameRange->source_range_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	return str::byte_slice(sourceText, cast<int_t<>>(nameRange->start_offset), cast<int_t<>>(nameRange->length));
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_body_summaries_variable_name_payload_id(shared_p<FrontendModel> model, FrontendNodeRow variableNode, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::variable_name_payload_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[20]);
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(variableNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) || php::not_identical(cast<int_t<>>(variableNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_variable_id()))))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	FrontendExpressionPayloadRow expression = __latency_fn_frontend_model_tables_expression_by_id(model, variableNode->payload_row_id, counters);
	return expression->callee_name_id;
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
bool_t __latency_fn_frontend_body_summaries_ready_literal_from_node(shared_p<FrontendModel> model, FrontendNodeRow node, FrontendLiteralPayloadRow& literal, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::ready_literal_from_node", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[21]);
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(node->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) || php::not_identical(cast<int_t<>>(node->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_literal_id()))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	literal = __latency_fn_frontend_model_tables_literal_by_id(model, node->payload_row_id, counters);
	return __latency_fn_frontend_body_summaries_ready_literal_payload(literal);
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
bool_t __latency_fn_frontend_body_summaries_literal_status_ready(int_t<std::uint16_t> literalStatusId) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::literal_status_ready", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[22]);
	return bool_t(php::identical(cast<int_t<>>(literalStatusId), cast<int_t<>>(__latency_fn_frontend_model_builder_literal_status_ready_id())));
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
bool_t __latency_fn_frontend_body_summaries_ready_literal_payload(FrontendLiteralPayloadRow literal) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::ready_literal_payload", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[23]);
	return ((cast<int_t<>>(literal->payload_id) > static_cast<int_t<> >(0)) && __latency_fn_frontend_body_summaries_literal_status_ready(literal->literal_status_id));
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_body_summaries_expression_type_ref_id(shared_p<FrontendModel> model, FrontendNodeRow node, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::expression_type_ref_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[24]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(node->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id()))))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(node->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_literal_id())))) {
		FrontendLiteralPayloadRow literal = __latency_fn_frontend_model_tables_literal_by_id(model, node->payload_row_id, counters);
		return literal->type_ref_id;
	}
	FrontendExpressionPayloadRow expression = __latency_fn_frontend_model_tables_expression_by_id(model, node->payload_row_id, counters);
	return expression->inferred_type_ref_id;
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_body_summaries_append_local(shared_p<FrontendBodySummaryArtifact>& artifact, FrontendLocalBindingSummaryRow row) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::append_local", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[25]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->local_id), static_cast<int_t<> >(0)))) {
		row->local_id = __latency_fn_structure_row_ids_uint32_from_int((php::count(artifact->locals) + static_cast<int_t<> >(1)));
	}
	(void) artifact->locals.append(row);
	artifact->local_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->locals));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_frontend_body_summaries_status_blocked_id())))) {
		artifact->blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->blocked_count) + static_cast<int_t<> >(1)));
	}
	return row->local_id;
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
void __latency_fn_frontend_body_summaries_append_row(shared_p<FrontendBodySummaryArtifact>& artifact, FrontendBodySummaryRow row) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::append_row", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[26]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->summary_id), static_cast<int_t<> >(0)))) {
		row->summary_id = __latency_fn_structure_row_ids_uint32_from_int((php::count(artifact->rows) + static_cast<int_t<> >(1)));
	}
	(void) artifact->rows.append(row);
	artifact->row_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->rows));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->summary_kind_id), cast<int_t<>>(__latency_fn_frontend_body_summaries_summary_kind_condition_id())))) {
		artifact->condition_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->condition_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->summary_kind_id), cast<int_t<>>(__latency_fn_frontend_body_summaries_summary_kind_expression_dependency_id())))) {
		artifact->expression_dependency_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->expression_dependency_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(row->summary_kind_id), cast<int_t<>>(__latency_fn_frontend_body_summaries_summary_kind_expression_dependency_id()))))) {
		artifact->statement_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->statement_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_frontend_body_summaries_status_blocked_id())))) {
		artifact->blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->blocked_count) + static_cast<int_t<> >(1)));
	}
}

}

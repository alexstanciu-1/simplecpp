#include <scpp/lang/php.hpp>
#include "__types/FrontendBodySummaryArtifact.hpp"
#include "__types/FrontendBodySummaryRow.hpp"
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendLocalBindingSummaryRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/SourceRangeRow.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_row_is_statement_fact.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_summary_kind_expression_dependency_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_function_body_statement_rows.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_row_is_statement_fact.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_function_body_statement_rows.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_only_function_body_statement_row.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_accepted_literal_return_statement.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_only_function_body_statement_row.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_ready_literal_from_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_return_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_local_id_for_name_range.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_local_name_range.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_source_range_text_equals.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_local_name_range.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_local_by_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_local_id_for_name_range.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_local_id_for_variable_node.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_variable_name_range.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_append_expression_dependencies.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_append_row.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_status_ready_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_summary_kind_expression_dependency_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_binary_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_feature_id_for_operator_id.hpp"
namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
bool_t __latency_fn_frontend_body_summaries_row_is_statement_fact(FrontendBodySummaryRow row) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::row_is_statement_fact", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[27]);
	return bool_t(php::not_identical(cast<int_t<>>(row->summary_kind_id), cast<int_t<>>(__latency_fn_frontend_body_summaries_summary_kind_expression_dependency_id())));
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
vector_t<FrontendBodySummaryRow> __latency_fn_frontend_body_summaries_function_body_statement_rows(shared_p<FrontendBodySummaryArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::function_body_statement_rows", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[28]);
	vector_t<FrontendBodySummaryRow> rows = {};
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(((__latency_fn_frontend_body_summaries_row_is_statement_fact(row) && php::identical(cast<int_t<>>(row->scope_node_id), cast<int_t<>>(artifact->declaration_node_id))) && php::identical(cast<int_t<>>(row->parent_statement_node_id), static_cast<int_t<> >(0))))) {
			(void) rows.push_back(row);
		}
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
FrontendBodySummaryRow __latency_fn_frontend_body_summaries_only_function_body_statement_row(shared_p<FrontendBodySummaryArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::only_function_body_statement_row", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[29]);
	vector_t<FrontendBodySummaryRow> rows = required_cast<vector_t<FrontendBodySummaryRow>>(__latency_fn_frontend_body_summaries_function_body_statement_rows(artifact));
	if (static_cast<bool>(php::identical(php::count(rows), static_cast<int_t<> >(1)))) {
		return rows.at(static_cast<int_t<> >(0));
	}
	FrontendBodySummaryRow empty = FrontendBodySummaryRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
FrontendNodeRow __latency_fn_frontend_body_summaries_accepted_literal_return_statement(shared_p<FrontendBodySummaryArtifact> artifact, shared_p<FrontendModel> model, ProjectSymbolIndexRow symbol, FrontendLiteralPayloadRow& literal, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::accepted_literal_return_statement", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[30]);
	FrontendNodeRow empty = FrontendNodeRow{};
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(symbol->parameter_count), static_cast<int_t<> >(0))))) {
		return empty;
	}
	FrontendBodySummaryRow statementRow = __latency_fn_frontend_body_summaries_only_function_body_statement_row(artifact);
	if (static_cast<bool>((php::identical(cast<int_t<>>(statementRow->statement_node_id), static_cast<int_t<> >(0)) || php::not_identical(cast<int_t<>>(statementRow->statement_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_return_id()))))) {
		return empty;
	}
	FrontendNodeRow statementNode = __latency_fn_frontend_model_tables_node_by_id(model, statementRow->statement_node_id, counters);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statementNode->node_id), static_cast<int_t<> >(0)))) {
		return empty;
	}
	FrontendStatementPayloadRow statement = __latency_fn_frontend_model_tables_statement_by_id(model, statementNode->payload_row_id, counters);
	FrontendNodeRow valueNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->value_node_id, counters);
	if (static_cast<bool>(((!__latency_fn_frontend_body_summaries_ready_literal_from_node(model, valueNode, literal, counters)) || php::not_identical(cast<int_t<>>(literal->type_ref_id), cast<int_t<>>(symbol->return_type_ref_id))))) {
		return empty;
	}
	return valueNode;
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_body_summaries_local_id_for_name_range(shared_p<FrontendBodySummaryArtifact>& artifact, const string_t& sourceText, SourceRangeRow nameRange) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::local_id_for_name_range", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[31]);
	auto __latency_local_0 = artifact->locals;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto local = __latency_local_1.value_copy();
		if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_body_summaries_source_range_text_equals(sourceText, nameRange, __latency_fn_frontend_body_summaries_local_name_range(artifact, local))))) {
			artifact->source_text_match_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->source_text_match_count) + static_cast<int_t<> >(1)));
			return local->local_id;
		}
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
SourceRangeRow __latency_fn_frontend_body_summaries_local_name_range(shared_p<FrontendBodySummaryArtifact> artifact, FrontendLocalBindingSummaryRow local) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::local_name_range", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[32]);
	SourceRangeRow row = SourceRangeRow{};
	row->source_range_id = local->name_source_range_id;
	row->start_offset = local->name_start_offset;
	row->length = local->name_length;
	return row;
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
FrontendLocalBindingSummaryRow __latency_fn_frontend_body_summaries_local_by_id(shared_p<FrontendBodySummaryArtifact> artifact, int_t<std::uint32_t> localId) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::local_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[33]);
	if (static_cast<bool>(((cast<int_t<>>(localId) > static_cast<int_t<> >(0)) && (cast<int_t<>>(localId) <= php::count(artifact->locals))))) {
		FrontendLocalBindingSummaryRow row = artifact->locals[(cast<int_t<>>(localId) - static_cast<int_t<> >(1))];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->local_id), cast<int_t<>>(localId)))) {
			return row;
		}
	}
	auto __latency_local_0 = artifact->locals;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->local_id), cast<int_t<>>(localId)))) {
			return row;
		}
	}
	FrontendLocalBindingSummaryRow empty = FrontendLocalBindingSummaryRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_body_summaries_local_id_for_variable_node(shared_p<FrontendBodySummaryArtifact>& artifact, shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow variableNode, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::local_id_for_variable_node", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[34]);
	SourceRangeRow nameRange = __latency_fn_frontend_body_summaries_variable_name_range(model, variableNode, counters);
	return __latency_fn_frontend_body_summaries_local_id_for_name_range(artifact, sourceText, nameRange);
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
void __latency_fn_frontend_body_summaries_append_expression_dependencies(shared_p<FrontendBodySummaryArtifact>& artifact, shared_p<FrontendModel> model, int_t<std::uint32_t> scopeNodeId, int_t<std::uint32_t> parentStatementNodeId, FrontendNodeRow valueNode, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::append_expression_dependencies", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[35]);
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(valueNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) || php::not_identical(cast<int_t<>>(valueNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_binary_id()))))) {
		return;
	}
	FrontendExpressionPayloadRow binary = __latency_fn_frontend_model_tables_expression_by_id(model, valueNode->payload_row_id, counters);
	FrontendBodySummaryRow row = FrontendBodySummaryRow{};
	row->source_unit_id = artifact->source_unit_id;
	row->declaration_node_id = artifact->declaration_node_id;
	row->scope_node_id = scopeNodeId;
	row->statement_node_id = valueNode->node_id;
	row->parent_statement_node_id = parentStatementNodeId;
	row->value_node_id = valueNode->node_id;
	row->value_type_ref_id = binary->inferred_type_ref_id;
	row->summary_kind_id = __latency_fn_frontend_body_summaries_summary_kind_expression_dependency_id();
	row->operator_id = binary->operator_id;
	row->feature_id = __latency_fn_semantic_operator_lookup_feature_id_for_operator_id(binary->operator_id);
	row->status_id = __latency_fn_frontend_body_summaries_status_ready_id();
	row->blocked_reason_id = __latency_fn_frontend_body_summaries_blocked_reason_none_id();
	__latency_fn_frontend_body_summaries_append_row(artifact, row);
	FrontendNodeRow leftNode = __latency_fn_frontend_model_tables_node_by_id(model, binary->left_node_id, counters);
	FrontendNodeRow rightNode = __latency_fn_frontend_model_tables_node_by_id(model, binary->right_node_id, counters);
	__latency_fn_frontend_body_summaries_append_expression_dependencies(artifact, model, cast<int_t<std::uint32_t>>(scopeNodeId), cast<int_t<std::uint32_t>>(parentStatementNodeId), leftNode, counters);
	__latency_fn_frontend_body_summaries_append_expression_dependencies(artifact, model, cast<int_t<std::uint32_t>>(scopeNodeId), cast<int_t<std::uint32_t>>(parentStatementNodeId), rightNode, counters);
}

}

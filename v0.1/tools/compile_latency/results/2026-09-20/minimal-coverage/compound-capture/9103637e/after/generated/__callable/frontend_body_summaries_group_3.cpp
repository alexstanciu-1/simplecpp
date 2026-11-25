#include <scpp/lang/php.hpp>
#include "__types/FrontendBodySummaryArtifact.hpp"
#include "__types/FrontendBodySummaryRow.hpp"
#include "__types/FrontendLocalBindingSummaryRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/SourceRangeRow.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_append_expression_dependencies.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_append_local.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_append_row.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_append_statement.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_binding_kind_explicit_local_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_binding_kind_implicit_assignment_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_blocked_reason_unsupported_statement_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_expression_type_ref_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_local_id_for_name_range.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_local_id_for_variable_node.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_status_blocked_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_status_ready_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_summary_kind_assignment_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_summary_kind_condition_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_summary_kind_echo_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_summary_kind_local_binding_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_summary_kind_return_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_variable_name_payload_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_variable_name_range.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_variable_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_assignment_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_echo_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_for_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_if_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_local_declaration_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_return_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_while_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
bool_t __latency_fn_frontend_body_summaries_append_statement(shared_p<FrontendBodySummaryArtifact>& artifact, shared_p<FrontendModel> model, const string_t& sourceText, int_t<std::uint32_t> scopeNodeId, int_t<std::uint32_t> parentStatementNodeId, FrontendNodeRow statementNode, bool_t allowImplicitLocal, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::append_statement", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[36]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(statementNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_statement_id()))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	FrontendStatementPayloadRow statement = __latency_fn_frontend_model_tables_statement_by_id(model, statementNode->payload_row_id, counters);
	if (static_cast<bool>((php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_local_declaration_id())) || php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_assignment_id()))))) {
		FrontendNodeRow targetNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->target_node_id, counters);
		FrontendNodeRow valueNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->value_node_id, counters);
		SourceRangeRow nameRange = __latency_fn_frontend_body_summaries_variable_name_range(model, targetNode, counters);
		int_t<std::uint32_t> localId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_body_summaries_local_id_for_name_range(artifact, sourceText, nameRange));
		if (static_cast<bool>(php::identical(cast<int_t<>>(localId), static_cast<int_t<> >(0)))) {
			if (static_cast<bool>(((!allowImplicitLocal) && php::not_identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_local_declaration_id()))))) {
				return bool_t(static_cast<bool_t>(false));
			}
			FrontendLocalBindingSummaryRow local = FrontendLocalBindingSummaryRow{};
			local->source_unit_id = artifact->source_unit_id;
			local->declaration_node_id = artifact->declaration_node_id;
			local->scope_node_id = scopeNodeId;
			local->statement_node_id = statementNode->node_id;
			local->target_node_id = targetNode->node_id;
			local->name_payload_id = __latency_fn_frontend_body_summaries_variable_name_payload_id(model, targetNode, counters);
			local->name_source_range_id = nameRange->source_range_id;
			local->name_start_offset = nameRange->start_offset;
			local->name_length = nameRange->length;
			local->type_ref_id = __latency_fn_frontend_body_summaries_expression_type_ref_id(model, valueNode, counters);
			local->initializer_node_id = valueNode->node_id;
			local->binding_kind_id = php::ternary_eval([&]() -> decltype(auto) { return php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_local_declaration_id())); }, [&]() -> decltype(auto) { return __latency_fn_frontend_body_summaries_binding_kind_explicit_local_id(); }, [&]() -> decltype(auto) { return __latency_fn_frontend_body_summaries_binding_kind_implicit_assignment_id(); });
			local->status_id = __latency_fn_frontend_body_summaries_status_ready_id();
			local->blocked_reason_id = __latency_fn_frontend_body_summaries_blocked_reason_none_id();
			localId = __latency_fn_frontend_body_summaries_append_local(artifact, local);
		}
		FrontendBodySummaryRow row = FrontendBodySummaryRow{};
		row->source_unit_id = artifact->source_unit_id;
		row->declaration_node_id = artifact->declaration_node_id;
		row->scope_node_id = scopeNodeId;
		row->statement_node_id = statementNode->node_id;
		row->parent_statement_node_id = parentStatementNodeId;
		row->target_node_id = targetNode->node_id;
		row->target_local_id = localId;
		row->value_node_id = valueNode->node_id;
		row->value_type_ref_id = __latency_fn_frontend_body_summaries_expression_type_ref_id(model, valueNode, counters);
		row->summary_kind_id = php::ternary_eval([&]() -> decltype(auto) { return php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_local_declaration_id())); }, [&]() -> decltype(auto) { return __latency_fn_frontend_body_summaries_summary_kind_local_binding_id(); }, [&]() -> decltype(auto) { return __latency_fn_frontend_body_summaries_summary_kind_assignment_id(); });
		row->statement_kind_id = statementNode->row_kind_id;
		row->status_id = __latency_fn_frontend_body_summaries_status_ready_id();
		row->blocked_reason_id = __latency_fn_frontend_body_summaries_blocked_reason_none_id();
		__latency_fn_frontend_body_summaries_append_row(artifact, row);
		__latency_fn_frontend_body_summaries_append_expression_dependencies(artifact, model, cast<int_t<std::uint32_t>>(scopeNodeId), statementNode->node_id, valueNode, counters);
		return bool_t(static_cast<bool_t>(true));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_echo_id())))) {
		FrontendNodeRow valueNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->value_node_id, counters);
		FrontendBodySummaryRow row = FrontendBodySummaryRow{};
		row->source_unit_id = artifact->source_unit_id;
		row->declaration_node_id = artifact->declaration_node_id;
		row->scope_node_id = scopeNodeId;
		row->statement_node_id = statementNode->node_id;
		row->parent_statement_node_id = parentStatementNodeId;
		row->value_node_id = valueNode->node_id;
		row->value_type_ref_id = __latency_fn_frontend_body_summaries_expression_type_ref_id(model, valueNode, counters);
		row->summary_kind_id = __latency_fn_frontend_body_summaries_summary_kind_echo_id();
		row->statement_kind_id = statementNode->row_kind_id;
		row->status_id = __latency_fn_frontend_body_summaries_status_ready_id();
		row->blocked_reason_id = __latency_fn_frontend_body_summaries_blocked_reason_none_id();
		if (static_cast<bool>(php::identical(cast<int_t<>>(valueNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_variable_id())))) {
			row->target_local_id = __latency_fn_frontend_body_summaries_local_id_for_variable_node(artifact, model, sourceText, valueNode, counters);
		}
		__latency_fn_frontend_body_summaries_append_row(artifact, row);
		__latency_fn_frontend_body_summaries_append_expression_dependencies(artifact, model, cast<int_t<std::uint32_t>>(scopeNodeId), statementNode->node_id, valueNode, counters);
		return bool_t(static_cast<bool_t>(true));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_if_id())))) {
		FrontendNodeRow conditionNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->condition_node_id, counters);
		FrontendBodySummaryRow row = FrontendBodySummaryRow{};
		row->source_unit_id = artifact->source_unit_id;
		row->declaration_node_id = artifact->declaration_node_id;
		row->scope_node_id = scopeNodeId;
		row->statement_node_id = statementNode->node_id;
		row->parent_statement_node_id = parentStatementNodeId;
		row->condition_node_id = conditionNode->node_id;
		row->condition_type_ref_id = __latency_fn_frontend_body_summaries_expression_type_ref_id(model, conditionNode, counters);
		row->summary_kind_id = __latency_fn_frontend_body_summaries_summary_kind_condition_id();
		row->statement_kind_id = statementNode->row_kind_id;
		row->status_id = __latency_fn_frontend_body_summaries_status_ready_id();
		row->blocked_reason_id = __latency_fn_frontend_body_summaries_blocked_reason_none_id();
		__latency_fn_frontend_body_summaries_append_row(artifact, row);
		artifact->scope_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->scope_count) + static_cast<int_t<> >(1)));
		int_t<std::uint32_t> bodyNodeId = required_cast<int_t<std::uint32_t>>(statement->body_first_node_id);
		while (static_cast<bool>((cast<int_t<>>(bodyNodeId) > static_cast<int_t<> >(0)))) {
			FrontendNodeRow bodyNode = __latency_fn_frontend_model_tables_node_by_id(model, bodyNodeId, counters);
			if (static_cast<bool>((!__latency_fn_frontend_body_summaries_append_statement(artifact, model, sourceText, statementNode->node_id, statementNode->node_id, bodyNode, bool_t(static_cast<bool_t>(false)), counters)))) {
				return bool_t(static_cast<bool_t>(false));
			}
			bodyNodeId = bodyNode->next_sibling_node_id;
		}
		return bool_t(static_cast<bool_t>(true));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_while_id())))) {
		FrontendNodeRow conditionNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->condition_node_id, counters);
		FrontendBodySummaryRow row = FrontendBodySummaryRow{};
		row->source_unit_id = artifact->source_unit_id;
		row->declaration_node_id = artifact->declaration_node_id;
		row->scope_node_id = scopeNodeId;
		row->statement_node_id = statementNode->node_id;
		row->parent_statement_node_id = parentStatementNodeId;
		row->condition_node_id = conditionNode->node_id;
		row->condition_type_ref_id = __latency_fn_frontend_body_summaries_expression_type_ref_id(model, conditionNode, counters);
		row->summary_kind_id = __latency_fn_frontend_body_summaries_summary_kind_condition_id();
		row->statement_kind_id = statementNode->row_kind_id;
		row->status_id = __latency_fn_frontend_body_summaries_status_ready_id();
		row->blocked_reason_id = __latency_fn_frontend_body_summaries_blocked_reason_none_id();
		__latency_fn_frontend_body_summaries_append_row(artifact, row);
		artifact->scope_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->scope_count) + static_cast<int_t<> >(1)));
		int_t<std::uint32_t> bodyNodeId = required_cast<int_t<std::uint32_t>>(statement->body_first_node_id);
		while (static_cast<bool>((cast<int_t<>>(bodyNodeId) > static_cast<int_t<> >(0)))) {
			FrontendNodeRow bodyNode = __latency_fn_frontend_model_tables_node_by_id(model, bodyNodeId, counters);
			if (static_cast<bool>((!__latency_fn_frontend_body_summaries_append_statement(artifact, model, sourceText, statementNode->node_id, statementNode->node_id, bodyNode, bool_t(static_cast<bool_t>(false)), counters)))) {
				return bool_t(static_cast<bool_t>(false));
			}
			bodyNodeId = bodyNode->next_sibling_node_id;
		}
		return bool_t(static_cast<bool_t>(true));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_for_id())))) {
		FrontendNodeRow initNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->target_node_id, counters);
		if (static_cast<bool>(((cast<int_t<>>(initNode->node_id) > static_cast<int_t<> >(0)) && (!__latency_fn_frontend_body_summaries_append_statement(artifact, model, sourceText, statementNode->node_id, statementNode->node_id, initNode, bool_t(static_cast<bool_t>(false)), counters))))) {
			return bool_t(static_cast<bool_t>(false));
		}
		FrontendNodeRow conditionNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->condition_node_id, counters);
		FrontendBodySummaryRow row = FrontendBodySummaryRow{};
		row->source_unit_id = artifact->source_unit_id;
		row->declaration_node_id = artifact->declaration_node_id;
		row->scope_node_id = scopeNodeId;
		row->statement_node_id = statementNode->node_id;
		row->parent_statement_node_id = parentStatementNodeId;
		row->condition_node_id = conditionNode->node_id;
		row->condition_type_ref_id = __latency_fn_frontend_body_summaries_expression_type_ref_id(model, conditionNode, counters);
		row->summary_kind_id = __latency_fn_frontend_body_summaries_summary_kind_condition_id();
		row->statement_kind_id = statementNode->row_kind_id;
		row->status_id = __latency_fn_frontend_body_summaries_status_ready_id();
		row->blocked_reason_id = __latency_fn_frontend_body_summaries_blocked_reason_none_id();
		__latency_fn_frontend_body_summaries_append_row(artifact, row);
		artifact->scope_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->scope_count) + static_cast<int_t<> >(1)));
		int_t<std::uint32_t> bodyNodeId = required_cast<int_t<std::uint32_t>>(statement->body_first_node_id);
		while (static_cast<bool>((cast<int_t<>>(bodyNodeId) > static_cast<int_t<> >(0)))) {
			FrontendNodeRow bodyNode = __latency_fn_frontend_model_tables_node_by_id(model, bodyNodeId, counters);
			if (static_cast<bool>((!__latency_fn_frontend_body_summaries_append_statement(artifact, model, sourceText, statementNode->node_id, statementNode->node_id, bodyNode, bool_t(static_cast<bool_t>(false)), counters)))) {
				return bool_t(static_cast<bool_t>(false));
			}
			bodyNodeId = bodyNode->next_sibling_node_id;
		}
		FrontendNodeRow updateNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->value_node_id, counters);
		if (static_cast<bool>(((cast<int_t<>>(updateNode->node_id) > static_cast<int_t<> >(0)) && (!__latency_fn_frontend_body_summaries_append_statement(artifact, model, sourceText, statementNode->node_id, statementNode->node_id, updateNode, bool_t(static_cast<bool_t>(false)), counters))))) {
			return bool_t(static_cast<bool_t>(false));
		}
		return bool_t(static_cast<bool_t>(true));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_return_id())))) {
		FrontendNodeRow valueNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->value_node_id, counters);
		FrontendBodySummaryRow row = FrontendBodySummaryRow{};
		row->source_unit_id = artifact->source_unit_id;
		row->declaration_node_id = artifact->declaration_node_id;
		row->scope_node_id = scopeNodeId;
		row->statement_node_id = statementNode->node_id;
		row->parent_statement_node_id = parentStatementNodeId;
		row->value_node_id = valueNode->node_id;
		row->value_type_ref_id = __latency_fn_frontend_body_summaries_expression_type_ref_id(model, valueNode, counters);
		row->summary_kind_id = __latency_fn_frontend_body_summaries_summary_kind_return_id();
		row->statement_kind_id = statementNode->row_kind_id;
		row->status_id = __latency_fn_frontend_body_summaries_status_ready_id();
		row->blocked_reason_id = __latency_fn_frontend_body_summaries_blocked_reason_none_id();
		if (static_cast<bool>(php::identical(cast<int_t<>>(valueNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_variable_id())))) {
			row->target_local_id = __latency_fn_frontend_body_summaries_local_id_for_variable_node(artifact, model, sourceText, valueNode, counters);
		}
		__latency_fn_frontend_body_summaries_append_row(artifact, row);
		__latency_fn_frontend_body_summaries_append_expression_dependencies(artifact, model, cast<int_t<std::uint32_t>>(scopeNodeId), statementNode->node_id, valueNode, counters);
		return bool_t(static_cast<bool_t>(true));
	}
	FrontendBodySummaryRow blocked = FrontendBodySummaryRow{};
	blocked->source_unit_id = artifact->source_unit_id;
	blocked->declaration_node_id = artifact->declaration_node_id;
	blocked->scope_node_id = scopeNodeId;
	blocked->statement_node_id = statementNode->node_id;
	blocked->parent_statement_node_id = parentStatementNodeId;
	blocked->summary_kind_id = __latency_fn_frontend_body_summaries_summary_kind_assignment_id();
	blocked->statement_kind_id = statementNode->row_kind_id;
	blocked->status_id = __latency_fn_frontend_body_summaries_status_blocked_id();
	blocked->blocked_reason_id = __latency_fn_frontend_body_summaries_blocked_reason_unsupported_statement_id();
	__latency_fn_frontend_body_summaries_append_row(artifact, blocked);
	return bool_t(static_cast<bool_t>(false));
}

}

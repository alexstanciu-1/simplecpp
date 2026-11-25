#include <scpp/lang/php.hpp>
#include "__types/ControlFlowDataflowArtifact.hpp"
#include "__types/ControlFlowLocalWriteSet.hpp"
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_statement_local_name_and_type_ref__exec.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_variable_name_text.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_from_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_assignment_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_local_declaration_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_local_write_set_row.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_local_write_statement.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_row.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_diagnostic_none_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_local_name_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_statement_local_name_and_type_ref.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_status_definitely_assigned_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_name_index.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_merge_for_if.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_row.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_branch_kind_merge_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_diagnostic_incompatible_branch_type_ref_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_diagnostic_maybe_assigned_after_branch_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_diagnostic_none_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_name_index.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_row_kind_merge_participation_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_status_definitely_assigned_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_status_incompatible_type_ref_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_status_maybe_assigned_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_post_branch_read.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_row.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_branch_kind_merge_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_diagnostic_branch_local_scope_leak_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_diagnostic_none_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_local_name_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_name_index.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_row_kind_branch_read_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_status_definitely_assigned_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_status_scope_leak_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_variable_name_text.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_from_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_variable_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_return_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_expression_local_reads.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_row.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_diagnostic_none_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_local_name_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_status_definitely_assigned_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_variable_name_text.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_type_ref_from_node.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_binary_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_variable_id.hpp"
namespace scpp { extern const int __latency_lines_control_flow_dataflows[]; }
namespace scpp {
bool_t __latency_fn_control_flow_dataflows_statement_local_name_and_type_ref__exec(shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow statementNode, FrontendStatementPayloadRow statement, string_t& name, int_t<std::uint32_t>& typeRefId, shared_p<FrontendModelKernelCounters> counters) {
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_assignment_id())) && php::not_identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_local_declaration_id()))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	FrontendNodeRow targetNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->target_node_id, counters);
	name = __latency_fn_frontend_body_summaries_variable_name_text(model, sourceText, targetNode, counters);
	typeRefId = __latency_fn_frontend_model_builder_expression_type_ref_from_node(model, statement->value_node_id, counters);
	return (php::not_identical(name, string_t("")) && (cast<int_t<>>(typeRefId) > static_cast<int_t<> >(0)));
}

}

namespace scpp { extern const int __latency_lines_control_flow_dataflows[]; }
namespace scpp {
void __latency_fn_control_flow_dataflows_append_local_write_statement(shared_p<ControlFlowDataflowArtifact>& artifact, shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow statementNode, int_t<std::uint16_t> rowKindId, int_t<std::uint16_t> branchKindId, shared_p<ControlFlowLocalWriteSet>& writes, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("control_flow_dataflows::append_local_write_statement", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_dataflows.phs", __latency_lines_control_flow_dataflows[32]);
	FrontendStatementPayloadRow statement = __latency_fn_frontend_model_tables_statement_by_id(model, statementNode->payload_row_id, counters);
	string_t name = required_cast<string_t>(string_t(""));
	int_t<std::uint32_t> typeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	if (static_cast<bool>((!__latency_fn_control_flow_dataflows_statement_local_name_and_type_ref(model, sourceText, statementNode, statement, name, typeRefId, counters)))) {
		return;
	}
	int_t<std::uint32_t> nameId = required_cast<int_t<std::uint32_t>>(__latency_fn_control_flow_dataflows_local_name_id(artifact, name));
	__latency_fn_control_flow_dataflows_append_local_write_set_row(writes, cast<int_t<std::uint32_t>>(nameId), statementNode->node_id, cast<int_t<std::uint32_t>>(typeRefId));
	__latency_fn_control_flow_dataflows_append_row(artifact, cast<int_t<std::uint16_t>>(rowKindId), cast<int_t<std::uint16_t>>(branchKindId), __latency_fn_control_flow_dataflows_status_definitely_assigned_id(), __latency_fn_control_flow_dataflows_diagnostic_none_id(), statementNode->node_id, cast<int_t<std::uint32_t>>(nameId), statementNode->node_id, cast<int_t<std::uint32_t>>(typeRefId));
}

}

namespace scpp { extern const int __latency_lines_control_flow_dataflows[]; }
namespace scpp {
int_t<> __latency_fn_control_flow_dataflows_name_index(const vector_t<int_t<std::uint32_t>>& nameIds, int_t<std::uint32_t> nameId) {
	SCPP_CALL_DEPTH_GUARD("control_flow_dataflows::name_index", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_dataflows.phs", __latency_lines_control_flow_dataflows[33]);
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < php::count(nameIds)))) {
		if (static_cast<bool>(php::identical(cast<int_t<>>(nameIds.at(index)), cast<int_t<>>(nameId)))) {
			return index;
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return (-static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_control_flow_dataflows[]; }
namespace scpp {
void __latency_fn_control_flow_dataflows_append_merge_for_if(shared_p<ControlFlowDataflowArtifact>& artifact, int_t<std::uint32_t> ifNodeId, shared_p<ControlFlowLocalWriteSet> thenWrites, shared_p<ControlFlowLocalWriteSet> elseWrites) {
	SCPP_CALL_DEPTH_GUARD("control_flow_dataflows::append_merge_for_if", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_dataflows.phs", __latency_lines_control_flow_dataflows[34]);
	int_t<> thenIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((thenIndex < php::count(thenWrites->name_ids)))) {
		int_t<std::uint32_t> nameId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(thenWrites->name_ids.at(thenIndex)));
		int_t<> elseIndex = required_cast<int_t<>>(__latency_fn_control_flow_dataflows_name_index(elseWrites->name_ids, cast<int_t<std::uint32_t>>(nameId)));
		if (static_cast<bool>((elseIndex < static_cast<int_t<> >(0)))) {
			__latency_fn_control_flow_dataflows_append_row(artifact, __latency_fn_control_flow_dataflows_row_kind_merge_participation_id(), __latency_fn_control_flow_dataflows_branch_kind_merge_id(), __latency_fn_control_flow_dataflows_status_maybe_assigned_id(), __latency_fn_control_flow_dataflows_diagnostic_maybe_assigned_after_branch_id(), cast<int_t<std::uint32_t>>(ifNodeId), cast<int_t<std::uint32_t>>(nameId), cast<int_t<std::uint32_t>>(thenWrites->source_row_ids.at(thenIndex)), cast<int_t<std::uint32_t>>(thenWrites->type_ref_ids.at(thenIndex)));
		}
		else {
			if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(thenWrites->type_ref_ids.at(thenIndex)), cast<int_t<>>(elseWrites->type_ref_ids.at(elseIndex)))))) {
				__latency_fn_control_flow_dataflows_append_row(artifact, __latency_fn_control_flow_dataflows_row_kind_merge_participation_id(), __latency_fn_control_flow_dataflows_branch_kind_merge_id(), __latency_fn_control_flow_dataflows_status_incompatible_type_ref_id(), __latency_fn_control_flow_dataflows_diagnostic_incompatible_branch_type_ref_id(), cast<int_t<std::uint32_t>>(ifNodeId), cast<int_t<std::uint32_t>>(nameId), cast<int_t<std::uint32_t>>(thenWrites->source_row_ids.at(thenIndex)), cast<int_t<std::uint32_t>>(thenWrites->type_ref_ids.at(thenIndex)));
			}
			else {
				__latency_fn_control_flow_dataflows_append_row(artifact, __latency_fn_control_flow_dataflows_row_kind_merge_participation_id(), __latency_fn_control_flow_dataflows_branch_kind_merge_id(), __latency_fn_control_flow_dataflows_status_definitely_assigned_id(), __latency_fn_control_flow_dataflows_diagnostic_none_id(), cast<int_t<std::uint32_t>>(ifNodeId), cast<int_t<std::uint32_t>>(nameId), cast<int_t<std::uint32_t>>(thenWrites->source_row_ids.at(thenIndex)), cast<int_t<std::uint32_t>>(thenWrites->type_ref_ids.at(thenIndex)));
			}
		}
		thenIndex = (thenIndex + static_cast<int_t<> >(1));
	}
	int_t<> elseIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((elseIndex < php::count(elseWrites->name_ids)))) {
		int_t<std::uint32_t> nameId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(elseWrites->name_ids.at(elseIndex)));
		if (static_cast<bool>((__latency_fn_control_flow_dataflows_name_index(thenWrites->name_ids, cast<int_t<std::uint32_t>>(nameId)) < static_cast<int_t<> >(0)))) {
			__latency_fn_control_flow_dataflows_append_row(artifact, __latency_fn_control_flow_dataflows_row_kind_merge_participation_id(), __latency_fn_control_flow_dataflows_branch_kind_merge_id(), __latency_fn_control_flow_dataflows_status_maybe_assigned_id(), __latency_fn_control_flow_dataflows_diagnostic_maybe_assigned_after_branch_id(), cast<int_t<std::uint32_t>>(ifNodeId), cast<int_t<std::uint32_t>>(nameId), cast<int_t<std::uint32_t>>(elseWrites->source_row_ids.at(elseIndex)), cast<int_t<std::uint32_t>>(elseWrites->type_ref_ids.at(elseIndex)));
		}
		elseIndex = (elseIndex + static_cast<int_t<> >(1));
	}
}

}

namespace scpp { extern const int __latency_lines_control_flow_dataflows[]; }
namespace scpp {
void __latency_fn_control_flow_dataflows_append_post_branch_read(shared_p<ControlFlowDataflowArtifact>& artifact, shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow statementNode, const vector_t<int_t<std::uint32_t>>& branchLocalNameIds, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("control_flow_dataflows::append_post_branch_read", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_dataflows.phs", __latency_lines_control_flow_dataflows[35]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_return_id()))))) {
		return;
	}
	FrontendStatementPayloadRow statement = __latency_fn_frontend_model_tables_statement_by_id(model, statementNode->payload_row_id, counters);
	FrontendNodeRow valueNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->value_node_id, counters);
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(valueNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) || php::not_identical(cast<int_t<>>(valueNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_variable_id()))))) {
		return;
	}
	string_t name = required_cast<string_t>(__latency_fn_frontend_body_summaries_variable_name_text(model, sourceText, valueNode, counters));
	int_t<std::uint32_t> nameId = required_cast<int_t<std::uint32_t>>(__latency_fn_control_flow_dataflows_local_name_id(artifact, name));
	int_t<std::uint16_t> statusId = required_cast<int_t<std::uint16_t>>(__latency_fn_control_flow_dataflows_status_definitely_assigned_id());
	int_t<std::uint16_t> diagnosticId = required_cast<int_t<std::uint16_t>>(__latency_fn_control_flow_dataflows_diagnostic_none_id());
	if (static_cast<bool>(php::condition_truthy((__latency_fn_control_flow_dataflows_name_index(branchLocalNameIds, cast<int_t<std::uint32_t>>(nameId)) >= static_cast<int_t<> >(0))))) {
		statusId = __latency_fn_control_flow_dataflows_status_scope_leak_id();
		diagnosticId = __latency_fn_control_flow_dataflows_diagnostic_branch_local_scope_leak_id();
	}
	__latency_fn_control_flow_dataflows_append_row(artifact, __latency_fn_control_flow_dataflows_row_kind_branch_read_id(), __latency_fn_control_flow_dataflows_branch_kind_merge_id(), cast<int_t<std::uint16_t>>(statusId), cast<int_t<std::uint16_t>>(diagnosticId), statementNode->node_id, cast<int_t<std::uint32_t>>(nameId), valueNode->node_id, __latency_fn_frontend_model_builder_expression_type_ref_from_node(model, valueNode->node_id, counters));
}

}

namespace scpp { extern const int __latency_lines_control_flow_dataflows[]; }
namespace scpp {
void __latency_fn_control_flow_dataflows_append_expression_local_reads(shared_p<ControlFlowDataflowArtifact>& artifact, shared_p<FrontendModel> model, const string_t& sourceText, int_t<std::uint32_t> expressionNodeId, int_t<std::uint32_t> statementNodeId, int_t<std::uint16_t> rowKindId, int_t<std::uint16_t> branchKindId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("control_flow_dataflows::append_expression_local_reads", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_dataflows.phs", __latency_lines_control_flow_dataflows[36]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(expressionNodeId), static_cast<int_t<> >(0)))) {
		return;
	}
	FrontendNodeRow expressionNode = __latency_fn_frontend_model_tables_node_by_id(model, expressionNodeId, counters);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(expressionNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id()))))) {
		return;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(expressionNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_variable_id())))) {
		string_t name = required_cast<string_t>(__latency_fn_frontend_body_summaries_variable_name_text(model, sourceText, expressionNode, counters));
		int_t<std::uint32_t> nameId = required_cast<int_t<std::uint32_t>>(__latency_fn_control_flow_dataflows_local_name_id(artifact, name));
		__latency_fn_control_flow_dataflows_append_row(artifact, cast<int_t<std::uint16_t>>(rowKindId), cast<int_t<std::uint16_t>>(branchKindId), __latency_fn_control_flow_dataflows_status_definitely_assigned_id(), __latency_fn_control_flow_dataflows_diagnostic_none_id(), cast<int_t<std::uint32_t>>(statementNodeId), cast<int_t<std::uint32_t>>(nameId), expressionNode->node_id, __latency_fn_frontend_model_builder_expression_type_ref_from_node(model, expressionNode->node_id, counters));
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(expressionNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_binary_id())))) {
			FrontendExpressionPayloadRow expression = __latency_fn_frontend_model_tables_expression_by_id(model, expressionNode->payload_row_id, counters);
			__latency_fn_control_flow_dataflows_append_expression_local_reads(artifact, model, sourceText, expression->left_node_id, cast<int_t<std::uint32_t>>(statementNodeId), cast<int_t<std::uint16_t>>(rowKindId), cast<int_t<std::uint16_t>>(branchKindId), counters);
			__latency_fn_control_flow_dataflows_append_expression_local_reads(artifact, model, sourceText, expression->right_node_id, cast<int_t<std::uint32_t>>(statementNodeId), cast<int_t<std::uint16_t>>(rowKindId), cast<int_t<std::uint16_t>>(branchKindId), counters);
		}
	}
}

}

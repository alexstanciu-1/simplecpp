#include <scpp/lang/php.hpp>
#include "__types/ControlFlowDataflowArtifact.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_expression_local_reads.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_local_write_statement.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_row.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_while_statement.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_branch_kind_loop_back_edge_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_branch_kind_loop_body_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_branch_kind_loop_condition_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_branch_kind_loop_exit_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_diagnostic_none_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_row_kind_loop_carried_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_row_kind_loop_read_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_row_kind_loop_write_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_row_kind_loop_zero_iteration_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_status_loop_carried_assigned_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_status_zero_iteration_preserved_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_expression_local_reads.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_for_statement.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_local_write_statement.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_row.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_branch_kind_loop_back_edge_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_branch_kind_loop_body_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_branch_kind_loop_condition_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_branch_kind_loop_exit_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_branch_kind_loop_init_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_branch_kind_loop_update_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_diagnostic_none_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_row_kind_loop_carried_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_row_kind_loop_read_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_row_kind_loop_write_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_row_kind_loop_zero_iteration_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_status_loop_carried_assigned_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_status_zero_iteration_preserved_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_if_statement.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_local_write_statement.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_merge_for_if.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_post_branch_read.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_branch_kind_else_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_branch_kind_then_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_row_kind_branch_write_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_local_declaration_id.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_for_statement.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_if_statement.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_append_while_statement.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_empty_artifact.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_from_frontend_model.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_for_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_if_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_while_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
namespace scpp { extern const int __latency_lines_control_flow_dataflows[]; }
namespace scpp {
void __latency_fn_control_flow_dataflows_append_while_statement(shared_p<ControlFlowDataflowArtifact>& artifact, shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow whileNode, FrontendStatementPayloadRow whileStatement, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("control_flow_dataflows::append_while_statement", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_dataflows.phs", __latency_lines_control_flow_dataflows[37]);
	__latency_fn_control_flow_dataflows_append_expression_local_reads(artifact, model, sourceText, whileStatement->condition_node_id, whileNode->node_id, __latency_fn_control_flow_dataflows_row_kind_loop_read_id(), __latency_fn_control_flow_dataflows_branch_kind_loop_condition_id(), counters);
	vector_t<int_t<std::uint32_t>> bodyNameIds = {};
	vector_t<int_t<std::uint32_t>> bodySourceRowIds = {};
	vector_t<int_t<std::uint32_t>> bodyTypeRefIds = {};
	int_t<std::uint32_t> bodyNodeId = required_cast<int_t<std::uint32_t>>(whileStatement->body_first_node_id);
	while (static_cast<bool>((cast<int_t<>>(bodyNodeId) > static_cast<int_t<> >(0)))) {
		FrontendNodeRow bodyNode = __latency_fn_frontend_model_tables_node_by_id(model, bodyNodeId, counters);
		if (static_cast<bool>(php::identical(cast<int_t<>>(bodyNode->node_id), static_cast<int_t<> >(0)))) {
			break;
		}
		__latency_fn_control_flow_dataflows_append_local_write_statement(artifact, model, sourceText, bodyNode, __latency_fn_control_flow_dataflows_row_kind_loop_write_id(), __latency_fn_control_flow_dataflows_branch_kind_loop_body_id(), bodyNameIds, bodySourceRowIds, bodyTypeRefIds, counters);
		bodyNodeId = bodyNode->next_sibling_node_id;
	}
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < php::count(bodyNameIds)))) {
		__latency_fn_control_flow_dataflows_append_row(artifact, __latency_fn_control_flow_dataflows_row_kind_loop_carried_id(), __latency_fn_control_flow_dataflows_branch_kind_loop_back_edge_id(), __latency_fn_control_flow_dataflows_status_loop_carried_assigned_id(), __latency_fn_control_flow_dataflows_diagnostic_none_id(), whileNode->node_id, cast<int_t<std::uint32_t>>(bodyNameIds.at(index)), cast<int_t<std::uint32_t>>(bodySourceRowIds.at(index)), cast<int_t<std::uint32_t>>(bodyTypeRefIds.at(index)));
		__latency_fn_control_flow_dataflows_append_row(artifact, __latency_fn_control_flow_dataflows_row_kind_loop_zero_iteration_id(), __latency_fn_control_flow_dataflows_branch_kind_loop_exit_id(), __latency_fn_control_flow_dataflows_status_zero_iteration_preserved_id(), __latency_fn_control_flow_dataflows_diagnostic_none_id(), whileNode->node_id, cast<int_t<std::uint32_t>>(bodyNameIds.at(index)), cast<int_t<std::uint32_t>>(bodySourceRowIds.at(index)), cast<int_t<std::uint32_t>>(bodyTypeRefIds.at(index)));
		index = (index + static_cast<int_t<> >(1));
	}
}

}

namespace scpp { extern const int __latency_lines_control_flow_dataflows[]; }
namespace scpp {
void __latency_fn_control_flow_dataflows_append_for_statement(shared_p<ControlFlowDataflowArtifact>& artifact, shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow forNode, FrontendStatementPayloadRow forStatement, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("control_flow_dataflows::append_for_statement", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_dataflows.phs", __latency_lines_control_flow_dataflows[38]);
	vector_t<int_t<std::uint32_t>> initNameIds = {};
	vector_t<int_t<std::uint32_t>> initSourceRowIds = {};
	vector_t<int_t<std::uint32_t>> initTypeRefIds = {};
	FrontendNodeRow initNode = __latency_fn_frontend_model_tables_node_by_id(model, forStatement->target_node_id, counters);
	if (static_cast<bool>((cast<int_t<>>(initNode->node_id) > static_cast<int_t<> >(0)))) {
		__latency_fn_control_flow_dataflows_append_local_write_statement(artifact, model, sourceText, initNode, __latency_fn_control_flow_dataflows_row_kind_loop_write_id(), __latency_fn_control_flow_dataflows_branch_kind_loop_init_id(), initNameIds, initSourceRowIds, initTypeRefIds, counters);
	}
	__latency_fn_control_flow_dataflows_append_expression_local_reads(artifact, model, sourceText, forStatement->condition_node_id, forNode->node_id, __latency_fn_control_flow_dataflows_row_kind_loop_read_id(), __latency_fn_control_flow_dataflows_branch_kind_loop_condition_id(), counters);
	vector_t<int_t<std::uint32_t>> bodyNameIds = {};
	vector_t<int_t<std::uint32_t>> bodySourceRowIds = {};
	vector_t<int_t<std::uint32_t>> bodyTypeRefIds = {};
	int_t<std::uint32_t> bodyNodeId = required_cast<int_t<std::uint32_t>>(forStatement->body_first_node_id);
	while (static_cast<bool>((cast<int_t<>>(bodyNodeId) > static_cast<int_t<> >(0)))) {
		FrontendNodeRow bodyNode = __latency_fn_frontend_model_tables_node_by_id(model, bodyNodeId, counters);
		if (static_cast<bool>(php::identical(cast<int_t<>>(bodyNode->node_id), static_cast<int_t<> >(0)))) {
			break;
		}
		__latency_fn_control_flow_dataflows_append_local_write_statement(artifact, model, sourceText, bodyNode, __latency_fn_control_flow_dataflows_row_kind_loop_write_id(), __latency_fn_control_flow_dataflows_branch_kind_loop_body_id(), bodyNameIds, bodySourceRowIds, bodyTypeRefIds, counters);
		FrontendStatementPayloadRow bodyStatement = __latency_fn_frontend_model_tables_statement_by_id(model, bodyNode->payload_row_id, counters);
		__latency_fn_control_flow_dataflows_append_expression_local_reads(artifact, model, sourceText, bodyStatement->value_node_id, bodyNode->node_id, __latency_fn_control_flow_dataflows_row_kind_loop_read_id(), __latency_fn_control_flow_dataflows_branch_kind_loop_body_id(), counters);
		bodyNodeId = bodyNode->next_sibling_node_id;
	}
	vector_t<int_t<std::uint32_t>> updateNameIds = {};
	vector_t<int_t<std::uint32_t>> updateSourceRowIds = {};
	vector_t<int_t<std::uint32_t>> updateTypeRefIds = {};
	FrontendNodeRow updateNode = __latency_fn_frontend_model_tables_node_by_id(model, forStatement->value_node_id, counters);
	if (static_cast<bool>((cast<int_t<>>(updateNode->node_id) > static_cast<int_t<> >(0)))) {
		__latency_fn_control_flow_dataflows_append_local_write_statement(artifact, model, sourceText, updateNode, __latency_fn_control_flow_dataflows_row_kind_loop_write_id(), __latency_fn_control_flow_dataflows_branch_kind_loop_update_id(), updateNameIds, updateSourceRowIds, updateTypeRefIds, counters);
		FrontendStatementPayloadRow updateStatement = __latency_fn_frontend_model_tables_statement_by_id(model, updateNode->payload_row_id, counters);
		__latency_fn_control_flow_dataflows_append_expression_local_reads(artifact, model, sourceText, updateStatement->value_node_id, updateNode->node_id, __latency_fn_control_flow_dataflows_row_kind_loop_read_id(), __latency_fn_control_flow_dataflows_branch_kind_loop_update_id(), counters);
	}
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < php::count(bodyNameIds)))) {
		__latency_fn_control_flow_dataflows_append_row(artifact, __latency_fn_control_flow_dataflows_row_kind_loop_carried_id(), __latency_fn_control_flow_dataflows_branch_kind_loop_back_edge_id(), __latency_fn_control_flow_dataflows_status_loop_carried_assigned_id(), __latency_fn_control_flow_dataflows_diagnostic_none_id(), forNode->node_id, cast<int_t<std::uint32_t>>(bodyNameIds.at(index)), cast<int_t<std::uint32_t>>(bodySourceRowIds.at(index)), cast<int_t<std::uint32_t>>(bodyTypeRefIds.at(index)));
		__latency_fn_control_flow_dataflows_append_row(artifact, __latency_fn_control_flow_dataflows_row_kind_loop_zero_iteration_id(), __latency_fn_control_flow_dataflows_branch_kind_loop_exit_id(), __latency_fn_control_flow_dataflows_status_zero_iteration_preserved_id(), __latency_fn_control_flow_dataflows_diagnostic_none_id(), forNode->node_id, cast<int_t<std::uint32_t>>(bodyNameIds.at(index)), cast<int_t<std::uint32_t>>(bodySourceRowIds.at(index)), cast<int_t<std::uint32_t>>(bodyTypeRefIds.at(index)));
		index = (index + static_cast<int_t<> >(1));
	}
	int_t<> updateIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((updateIndex < php::count(updateNameIds)))) {
		__latency_fn_control_flow_dataflows_append_row(artifact, __latency_fn_control_flow_dataflows_row_kind_loop_carried_id(), __latency_fn_control_flow_dataflows_branch_kind_loop_back_edge_id(), __latency_fn_control_flow_dataflows_status_loop_carried_assigned_id(), __latency_fn_control_flow_dataflows_diagnostic_none_id(), forNode->node_id, cast<int_t<std::uint32_t>>(updateNameIds.at(updateIndex)), cast<int_t<std::uint32_t>>(updateSourceRowIds.at(updateIndex)), cast<int_t<std::uint32_t>>(updateTypeRefIds.at(updateIndex)));
		updateIndex = (updateIndex + static_cast<int_t<> >(1));
	}
}

}

namespace scpp { extern const int __latency_lines_control_flow_dataflows[]; }
namespace scpp {
void __latency_fn_control_flow_dataflows_append_if_statement(shared_p<ControlFlowDataflowArtifact>& artifact, shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow ifNode, FrontendStatementPayloadRow ifStatement, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("control_flow_dataflows::append_if_statement", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_dataflows.phs", __latency_lines_control_flow_dataflows[39]);
	vector_t<int_t<std::uint32_t>> thenNameIds = {};
	vector_t<int_t<std::uint32_t>> thenSourceRowIds = {};
	vector_t<int_t<std::uint32_t>> thenTypeRefIds = {};
	vector_t<int_t<std::uint32_t>> elseNameIds = {};
	vector_t<int_t<std::uint32_t>> elseSourceRowIds = {};
	vector_t<int_t<std::uint32_t>> elseTypeRefIds = {};
	vector_t<int_t<std::uint32_t>> branchLocalNameIds = {};
	int_t<std::uint32_t> thenNodeId = required_cast<int_t<std::uint32_t>>(ifStatement->body_first_node_id);
	while (static_cast<bool>((cast<int_t<>>(thenNodeId) > static_cast<int_t<> >(0)))) {
		FrontendNodeRow thenNode = __latency_fn_frontend_model_tables_node_by_id(model, thenNodeId, counters);
		if (static_cast<bool>(php::identical(cast<int_t<>>(thenNode->node_id), static_cast<int_t<> >(0)))) {
			break;
		}
		__latency_fn_control_flow_dataflows_append_local_write_statement(artifact, model, sourceText, thenNode, __latency_fn_control_flow_dataflows_row_kind_branch_write_id(), __latency_fn_control_flow_dataflows_branch_kind_then_id(), thenNameIds, thenSourceRowIds, thenTypeRefIds, counters);
		if (static_cast<bool>((php::identical(cast<int_t<>>(thenNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_local_declaration_id())) && (php::count(thenNameIds) > static_cast<int_t<> >(0))))) {
			{
			auto __latency_local_0 = thenNameIds.at((php::count(thenNameIds) - static_cast<int_t<> >(1)));
			(void) branchLocalNameIds.push_back(__latency_local_0);
			}
		}
		thenNodeId = thenNode->next_sibling_node_id;
	}
	int_t<std::uint32_t> elseNodeId = required_cast<int_t<std::uint32_t>>(ifStatement->else_body_first_node_id);
	while (static_cast<bool>((cast<int_t<>>(elseNodeId) > static_cast<int_t<> >(0)))) {
		FrontendNodeRow elseNode = __latency_fn_frontend_model_tables_node_by_id(model, elseNodeId, counters);
		if (static_cast<bool>(php::identical(cast<int_t<>>(elseNode->node_id), static_cast<int_t<> >(0)))) {
			break;
		}
		__latency_fn_control_flow_dataflows_append_local_write_statement(artifact, model, sourceText, elseNode, __latency_fn_control_flow_dataflows_row_kind_branch_write_id(), __latency_fn_control_flow_dataflows_branch_kind_else_id(), elseNameIds, elseSourceRowIds, elseTypeRefIds, counters);
		if (static_cast<bool>((php::identical(cast<int_t<>>(elseNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_local_declaration_id())) && (php::count(elseNameIds) > static_cast<int_t<> >(0))))) {
			{
			auto __latency_local_1 = elseNameIds.at((php::count(elseNameIds) - static_cast<int_t<> >(1)));
			(void) branchLocalNameIds.push_back(__latency_local_1);
			}
		}
		elseNodeId = elseNode->next_sibling_node_id;
	}
	__latency_fn_control_flow_dataflows_append_merge_for_if(artifact, ifNode->node_id, thenNameIds, thenSourceRowIds, thenTypeRefIds, elseNameIds, elseSourceRowIds, elseTypeRefIds);
	int_t<std::uint32_t> nextNodeId = required_cast<int_t<std::uint32_t>>(ifNode->next_sibling_node_id);
	while (static_cast<bool>((cast<int_t<>>(nextNodeId) > static_cast<int_t<> >(0)))) {
		FrontendNodeRow nextNode = __latency_fn_frontend_model_tables_node_by_id(model, nextNodeId, counters);
		if (static_cast<bool>(php::identical(cast<int_t<>>(nextNode->node_id), static_cast<int_t<> >(0)))) {
			break;
		}
		__latency_fn_control_flow_dataflows_append_post_branch_read(artifact, model, sourceText, nextNode, branchLocalNameIds, counters);
		nextNodeId = nextNode->next_sibling_node_id;
	}
}

}

namespace scpp { extern const int __latency_lines_control_flow_dataflows[]; }
namespace scpp {
shared_p<ControlFlowDataflowArtifact> __latency_fn_control_flow_dataflows_from_frontend_model(ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, const string_t& sourceText) {
	SCPP_CALL_DEPTH_GUARD("control_flow_dataflows::from_frontend_model", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_dataflows.phs", __latency_lines_control_flow_dataflows[40]);
	shared_p<ControlFlowDataflowArtifact> artifact = __latency_fn_control_flow_dataflows_empty_artifact(symbol->source_unit_id, symbol->symbol_id);
	if (static_cast<bool>((php::identical(cast<int_t<>>(symbol->symbol_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(symbol->body_first_node_id), static_cast<int_t<> >(0))))) {
		return artifact;
	}
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	int_t<std::uint32_t> statementNodeId = required_cast<int_t<std::uint32_t>>(symbol->body_first_node_id);
	while (static_cast<bool>((cast<int_t<>>(statementNodeId) > static_cast<int_t<> >(0)))) {
		FrontendNodeRow statementNode = __latency_fn_frontend_model_tables_node_by_id(model, statementNodeId, counters);
		if (static_cast<bool>((php::identical(cast<int_t<>>(statementNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_statement_id())) && php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_if_id()))))) {
			FrontendStatementPayloadRow statement = __latency_fn_frontend_model_tables_statement_by_id(model, statementNode->payload_row_id, counters);
			__latency_fn_control_flow_dataflows_append_if_statement(artifact, model, sourceText, statementNode, statement, counters);
		}
		else {
			if (static_cast<bool>((php::identical(cast<int_t<>>(statementNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_statement_id())) && php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_while_id()))))) {
				FrontendStatementPayloadRow statement = __latency_fn_frontend_model_tables_statement_by_id(model, statementNode->payload_row_id, counters);
				__latency_fn_control_flow_dataflows_append_while_statement(artifact, model, sourceText, statementNode, statement, counters);
			}
			else {
				if (static_cast<bool>((php::identical(cast<int_t<>>(statementNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_statement_id())) && php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_for_id()))))) {
					FrontendStatementPayloadRow statement = __latency_fn_frontend_model_tables_statement_by_id(model, statementNode->payload_row_id, counters);
					__latency_fn_control_flow_dataflows_append_for_statement(artifact, model, sourceText, statementNode, statement, counters);
				}
			}
		}
		statementNodeId = statementNode->next_sibling_node_id;
	}
	return artifact;
}

}

#include <scpp/lang/php.hpp>
#include "__types/ControlFlowGraphArtifact.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_control_flow_graphs_append_block.hpp"
#include "__callable/__latency_fn_control_flow_graphs_append_edge.hpp"
#include "__callable/__latency_fn_control_flow_graphs_append_if_statement_blocks.hpp"
#include "__callable/__latency_fn_control_flow_graphs_block_kind_if_condition_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_block_kind_if_else_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_block_kind_if_merge_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_block_kind_if_then_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_condition_false_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_condition_true_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_else_to_merge_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_entry_to_condition_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_merge_to_exit_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_then_to_merge_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_append_block.hpp"
#include "__callable/__latency_fn_control_flow_graphs_append_edge.hpp"
#include "__callable/__latency_fn_control_flow_graphs_append_while_statement_blocks.hpp"
#include "__callable/__latency_fn_control_flow_graphs_block_kind_while_back_edge_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_block_kind_while_body_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_block_kind_while_condition_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_block_kind_while_exit_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_condition_false_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_condition_true_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_entry_to_condition_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_loop_back_edge_to_condition_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_loop_body_to_back_edge_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_loop_exit_to_function_exit_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_graphs_append_block.hpp"
#include "__callable/__latency_fn_control_flow_graphs_append_edge.hpp"
#include "__callable/__latency_fn_control_flow_graphs_append_for_statement_blocks.hpp"
#include "__callable/__latency_fn_control_flow_graphs_block_kind_for_back_edge_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_block_kind_for_body_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_block_kind_for_condition_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_block_kind_for_exit_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_block_kind_for_init_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_block_kind_for_update_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_condition_false_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_condition_true_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_entry_to_condition_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_for_body_to_update_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_for_init_to_condition_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_for_update_to_back_edge_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_loop_back_edge_to_condition_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_loop_exit_to_function_exit_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_graphs_append_block.hpp"
#include "__callable/__latency_fn_control_flow_graphs_append_for_statement_blocks.hpp"
#include "__callable/__latency_fn_control_flow_graphs_append_if_statement_blocks.hpp"
#include "__callable/__latency_fn_control_flow_graphs_append_while_statement_blocks.hpp"
#include "__callable/__latency_fn_control_flow_graphs_block_kind_function_entry_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_block_kind_function_exit_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_empty_artifact.hpp"
#include "__callable/__latency_fn_control_flow_graphs_from_frontend_model.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_for_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_if_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_while_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_control_flow_graphs_from_frontend_model.hpp"
#include "__callable/__latency_fn_control_flow_graphs_project_tsv.hpp"
#include "__callable/__latency_fn_control_flow_graphs_tsv_body.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_kind_function_id.hpp"
namespace scpp { extern const int __latency_lines_control_flow_graphs[]; }
namespace scpp {
void __latency_fn_control_flow_graphs_append_if_statement_blocks(shared_p<ControlFlowGraphArtifact>& artifact, FrontendNodeRow statementNode, FrontendStatementPayloadRow statement, int_t<std::uint32_t> entryBlockId, int_t<std::uint32_t> exitBlockId) {
	SCPP_CALL_DEPTH_GUARD("control_flow_graphs::append_if_statement_blocks", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_graphs.phs", __latency_lines_control_flow_graphs[31]);
	int_t<std::uint32_t> conditionBlockId = required_cast<int_t<std::uint32_t>>(__latency_fn_control_flow_graphs_append_block(artifact, __latency_fn_control_flow_graphs_block_kind_if_condition_id(), statementNode->row_kind_id, statementNode->node_id, cast<int_t<std::uint32_t>>(entryBlockId), statement->condition_node_id, statement->condition_node_id, __latency_fn_structure_row_ids_none_kind_id()));
	int_t<std::uint32_t> thenBlockId = required_cast<int_t<std::uint32_t>>(__latency_fn_control_flow_graphs_append_block(artifact, __latency_fn_control_flow_graphs_block_kind_if_then_id(), statementNode->row_kind_id, statementNode->node_id, cast<int_t<std::uint32_t>>(conditionBlockId), statement->body_first_node_id, statement->body_last_node_id, __latency_fn_structure_row_ids_none_kind_id()));
	int_t<std::uint32_t> mergeBlockId = required_cast<int_t<std::uint32_t>>(__latency_fn_control_flow_graphs_append_block(artifact, __latency_fn_control_flow_graphs_block_kind_if_merge_id(), statementNode->row_kind_id, statementNode->node_id, cast<int_t<std::uint32_t>>(entryBlockId), statementNode->node_id, statementNode->node_id, __latency_fn_structure_row_ids_none_kind_id()));
	__latency_fn_control_flow_graphs_append_edge(artifact, cast<int_t<std::uint32_t>>(entryBlockId), cast<int_t<std::uint32_t>>(conditionBlockId), __latency_fn_control_flow_graphs_edge_kind_entry_to_condition_id(), statementNode->node_id);
	__latency_fn_control_flow_graphs_append_edge(artifact, cast<int_t<std::uint32_t>>(conditionBlockId), cast<int_t<std::uint32_t>>(thenBlockId), __latency_fn_control_flow_graphs_edge_kind_condition_true_id(), statement->condition_node_id);
	if (static_cast<bool>((cast<int_t<>>(statement->else_body_first_node_id) > static_cast<int_t<> >(0)))) {
		int_t<std::uint32_t> elseBlockId = required_cast<int_t<std::uint32_t>>(__latency_fn_control_flow_graphs_append_block(artifact, __latency_fn_control_flow_graphs_block_kind_if_else_id(), statementNode->row_kind_id, statementNode->node_id, cast<int_t<std::uint32_t>>(conditionBlockId), statement->else_body_first_node_id, statement->else_body_last_node_id, __latency_fn_structure_row_ids_none_kind_id()));
		__latency_fn_control_flow_graphs_append_edge(artifact, cast<int_t<std::uint32_t>>(conditionBlockId), cast<int_t<std::uint32_t>>(elseBlockId), __latency_fn_control_flow_graphs_edge_kind_condition_false_id(), statement->condition_node_id);
		__latency_fn_control_flow_graphs_append_edge(artifact, cast<int_t<std::uint32_t>>(elseBlockId), cast<int_t<std::uint32_t>>(mergeBlockId), __latency_fn_control_flow_graphs_edge_kind_else_to_merge_id(), statement->else_body_last_node_id);
	}
	else {
		__latency_fn_control_flow_graphs_append_edge(artifact, cast<int_t<std::uint32_t>>(conditionBlockId), cast<int_t<std::uint32_t>>(mergeBlockId), __latency_fn_control_flow_graphs_edge_kind_condition_false_id(), statement->condition_node_id);
	}
	__latency_fn_control_flow_graphs_append_edge(artifact, cast<int_t<std::uint32_t>>(thenBlockId), cast<int_t<std::uint32_t>>(mergeBlockId), __latency_fn_control_flow_graphs_edge_kind_then_to_merge_id(), statement->body_last_node_id);
	__latency_fn_control_flow_graphs_append_edge(artifact, cast<int_t<std::uint32_t>>(mergeBlockId), cast<int_t<std::uint32_t>>(exitBlockId), __latency_fn_control_flow_graphs_edge_kind_merge_to_exit_id(), statementNode->node_id);
}

}

namespace scpp { extern const int __latency_lines_control_flow_graphs[]; }
namespace scpp {
void __latency_fn_control_flow_graphs_append_while_statement_blocks(shared_p<ControlFlowGraphArtifact>& artifact, FrontendNodeRow statementNode, FrontendStatementPayloadRow statement, int_t<std::uint32_t> entryBlockId, int_t<std::uint32_t> exitBlockId) {
	SCPP_CALL_DEPTH_GUARD("control_flow_graphs::append_while_statement_blocks", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_graphs.phs", __latency_lines_control_flow_graphs[32]);
	int_t<std::uint32_t> conditionBlockId = required_cast<int_t<std::uint32_t>>(__latency_fn_control_flow_graphs_append_block(artifact, __latency_fn_control_flow_graphs_block_kind_while_condition_id(), statementNode->row_kind_id, statementNode->node_id, cast<int_t<std::uint32_t>>(entryBlockId), statement->condition_node_id, statement->condition_node_id, __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1))));
	int_t<std::uint32_t> bodyBlockId = required_cast<int_t<std::uint32_t>>(__latency_fn_control_flow_graphs_append_block(artifact, __latency_fn_control_flow_graphs_block_kind_while_body_id(), statementNode->row_kind_id, statementNode->node_id, cast<int_t<std::uint32_t>>(conditionBlockId), statement->body_first_node_id, statement->body_last_node_id, __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1))));
	int_t<std::uint32_t> backEdgeBlockId = required_cast<int_t<std::uint32_t>>(__latency_fn_control_flow_graphs_append_block(artifact, __latency_fn_control_flow_graphs_block_kind_while_back_edge_id(), statementNode->row_kind_id, statementNode->node_id, cast<int_t<std::uint32_t>>(bodyBlockId), statement->body_last_node_id, statement->condition_node_id, __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1))));
	int_t<std::uint32_t> loopExitBlockId = required_cast<int_t<std::uint32_t>>(__latency_fn_control_flow_graphs_append_block(artifact, __latency_fn_control_flow_graphs_block_kind_while_exit_id(), statementNode->row_kind_id, statementNode->node_id, cast<int_t<std::uint32_t>>(conditionBlockId), statementNode->node_id, statementNode->node_id, __latency_fn_structure_row_ids_none_kind_id()));
	__latency_fn_control_flow_graphs_append_edge(artifact, cast<int_t<std::uint32_t>>(entryBlockId), cast<int_t<std::uint32_t>>(conditionBlockId), __latency_fn_control_flow_graphs_edge_kind_entry_to_condition_id(), statementNode->node_id);
	__latency_fn_control_flow_graphs_append_edge(artifact, cast<int_t<std::uint32_t>>(conditionBlockId), cast<int_t<std::uint32_t>>(bodyBlockId), __latency_fn_control_flow_graphs_edge_kind_condition_true_id(), statement->condition_node_id);
	__latency_fn_control_flow_graphs_append_edge(artifact, cast<int_t<std::uint32_t>>(conditionBlockId), cast<int_t<std::uint32_t>>(loopExitBlockId), __latency_fn_control_flow_graphs_edge_kind_condition_false_id(), statement->condition_node_id);
	__latency_fn_control_flow_graphs_append_edge(artifact, cast<int_t<std::uint32_t>>(bodyBlockId), cast<int_t<std::uint32_t>>(backEdgeBlockId), __latency_fn_control_flow_graphs_edge_kind_loop_body_to_back_edge_id(), statement->body_last_node_id);
	__latency_fn_control_flow_graphs_append_edge(artifact, cast<int_t<std::uint32_t>>(backEdgeBlockId), cast<int_t<std::uint32_t>>(conditionBlockId), __latency_fn_control_flow_graphs_edge_kind_loop_back_edge_to_condition_id(), statement->condition_node_id);
	__latency_fn_control_flow_graphs_append_edge(artifact, cast<int_t<std::uint32_t>>(loopExitBlockId), cast<int_t<std::uint32_t>>(exitBlockId), __latency_fn_control_flow_graphs_edge_kind_loop_exit_to_function_exit_id(), statementNode->node_id);
}

}

namespace scpp { extern const int __latency_lines_control_flow_graphs[]; }
namespace scpp {
void __latency_fn_control_flow_graphs_append_for_statement_blocks(shared_p<ControlFlowGraphArtifact>& artifact, FrontendNodeRow statementNode, FrontendStatementPayloadRow statement, int_t<std::uint32_t> entryBlockId, int_t<std::uint32_t> exitBlockId) {
	SCPP_CALL_DEPTH_GUARD("control_flow_graphs::append_for_statement_blocks", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_graphs.phs", __latency_lines_control_flow_graphs[33]);
	int_t<std::uint32_t> initBlockId = required_cast<int_t<std::uint32_t>>(__latency_fn_control_flow_graphs_append_block(artifact, __latency_fn_control_flow_graphs_block_kind_for_init_id(), statementNode->row_kind_id, statementNode->node_id, cast<int_t<std::uint32_t>>(entryBlockId), statement->target_node_id, statement->target_node_id, __latency_fn_structure_row_ids_none_kind_id()));
	int_t<std::uint32_t> conditionBlockId = required_cast<int_t<std::uint32_t>>(__latency_fn_control_flow_graphs_append_block(artifact, __latency_fn_control_flow_graphs_block_kind_for_condition_id(), statementNode->row_kind_id, statementNode->node_id, cast<int_t<std::uint32_t>>(initBlockId), statement->condition_node_id, statement->condition_node_id, __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1))));
	int_t<std::uint32_t> bodyBlockId = required_cast<int_t<std::uint32_t>>(__latency_fn_control_flow_graphs_append_block(artifact, __latency_fn_control_flow_graphs_block_kind_for_body_id(), statementNode->row_kind_id, statementNode->node_id, cast<int_t<std::uint32_t>>(conditionBlockId), statement->body_first_node_id, statement->body_last_node_id, __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1))));
	int_t<std::uint32_t> updateBlockId = required_cast<int_t<std::uint32_t>>(__latency_fn_control_flow_graphs_append_block(artifact, __latency_fn_control_flow_graphs_block_kind_for_update_id(), statementNode->row_kind_id, statementNode->node_id, cast<int_t<std::uint32_t>>(bodyBlockId), statement->value_node_id, statement->value_node_id, __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1))));
	int_t<std::uint32_t> backEdgeBlockId = required_cast<int_t<std::uint32_t>>(__latency_fn_control_flow_graphs_append_block(artifact, __latency_fn_control_flow_graphs_block_kind_for_back_edge_id(), statementNode->row_kind_id, statementNode->node_id, cast<int_t<std::uint32_t>>(updateBlockId), statement->value_node_id, statement->condition_node_id, __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1))));
	int_t<std::uint32_t> loopExitBlockId = required_cast<int_t<std::uint32_t>>(__latency_fn_control_flow_graphs_append_block(artifact, __latency_fn_control_flow_graphs_block_kind_for_exit_id(), statementNode->row_kind_id, statementNode->node_id, cast<int_t<std::uint32_t>>(conditionBlockId), statementNode->node_id, statementNode->node_id, __latency_fn_structure_row_ids_none_kind_id()));
	__latency_fn_control_flow_graphs_append_edge(artifact, cast<int_t<std::uint32_t>>(entryBlockId), cast<int_t<std::uint32_t>>(initBlockId), __latency_fn_control_flow_graphs_edge_kind_entry_to_condition_id(), statementNode->node_id);
	__latency_fn_control_flow_graphs_append_edge(artifact, cast<int_t<std::uint32_t>>(initBlockId), cast<int_t<std::uint32_t>>(conditionBlockId), __latency_fn_control_flow_graphs_edge_kind_for_init_to_condition_id(), statement->target_node_id);
	__latency_fn_control_flow_graphs_append_edge(artifact, cast<int_t<std::uint32_t>>(conditionBlockId), cast<int_t<std::uint32_t>>(bodyBlockId), __latency_fn_control_flow_graphs_edge_kind_condition_true_id(), statement->condition_node_id);
	__latency_fn_control_flow_graphs_append_edge(artifact, cast<int_t<std::uint32_t>>(conditionBlockId), cast<int_t<std::uint32_t>>(loopExitBlockId), __latency_fn_control_flow_graphs_edge_kind_condition_false_id(), statement->condition_node_id);
	__latency_fn_control_flow_graphs_append_edge(artifact, cast<int_t<std::uint32_t>>(bodyBlockId), cast<int_t<std::uint32_t>>(updateBlockId), __latency_fn_control_flow_graphs_edge_kind_for_body_to_update_id(), statement->body_last_node_id);
	__latency_fn_control_flow_graphs_append_edge(artifact, cast<int_t<std::uint32_t>>(updateBlockId), cast<int_t<std::uint32_t>>(backEdgeBlockId), __latency_fn_control_flow_graphs_edge_kind_for_update_to_back_edge_id(), statement->value_node_id);
	__latency_fn_control_flow_graphs_append_edge(artifact, cast<int_t<std::uint32_t>>(backEdgeBlockId), cast<int_t<std::uint32_t>>(conditionBlockId), __latency_fn_control_flow_graphs_edge_kind_loop_back_edge_to_condition_id(), statement->condition_node_id);
	__latency_fn_control_flow_graphs_append_edge(artifact, cast<int_t<std::uint32_t>>(loopExitBlockId), cast<int_t<std::uint32_t>>(exitBlockId), __latency_fn_control_flow_graphs_edge_kind_loop_exit_to_function_exit_id(), statementNode->node_id);
}

}

namespace scpp { extern const int __latency_lines_control_flow_graphs[]; }
namespace scpp {
shared_p<ControlFlowGraphArtifact> __latency_fn_control_flow_graphs_from_frontend_model(ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("control_flow_graphs::from_frontend_model", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_graphs.phs", __latency_lines_control_flow_graphs[34]);
	shared_p<ControlFlowGraphArtifact> artifact = __latency_fn_control_flow_graphs_empty_artifact(symbol->source_unit_id, symbol->symbol_id);
	if (static_cast<bool>((php::identical(cast<int_t<>>(symbol->symbol_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(symbol->body_first_node_id), static_cast<int_t<> >(0))))) {
		return artifact;
	}
	int_t<std::uint32_t> entryBlockId = required_cast<int_t<std::uint32_t>>(__latency_fn_control_flow_graphs_append_block(artifact, __latency_fn_control_flow_graphs_block_kind_function_entry_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), symbol->body_first_node_id, symbol->body_first_node_id, __latency_fn_structure_row_ids_none_kind_id()));
	int_t<std::uint32_t> exitBlockId = required_cast<int_t<std::uint32_t>>(__latency_fn_control_flow_graphs_append_block(artifact, __latency_fn_control_flow_graphs_block_kind_function_exit_id(), __latency_fn_structure_row_ids_none_kind_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_kind_id()));
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	int_t<std::uint32_t> statementNodeId = required_cast<int_t<std::uint32_t>>(symbol->body_first_node_id);
	while (static_cast<bool>((cast<int_t<>>(statementNodeId) > static_cast<int_t<> >(0)))) {
		FrontendNodeRow statementNode = __latency_fn_frontend_model_tables_node_by_id(model, statementNodeId, counters);
		if (static_cast<bool>((php::identical(cast<int_t<>>(statementNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_statement_id())) && php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_if_id()))))) {
			FrontendStatementPayloadRow statement = __latency_fn_frontend_model_tables_statement_by_id(model, statementNode->payload_row_id, counters);
			__latency_fn_control_flow_graphs_append_if_statement_blocks(artifact, statementNode, statement, cast<int_t<std::uint32_t>>(entryBlockId), cast<int_t<std::uint32_t>>(exitBlockId));
		}
		else {
			if (static_cast<bool>((php::identical(cast<int_t<>>(statementNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_statement_id())) && php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_while_id()))))) {
				FrontendStatementPayloadRow statement = __latency_fn_frontend_model_tables_statement_by_id(model, statementNode->payload_row_id, counters);
				__latency_fn_control_flow_graphs_append_while_statement_blocks(artifact, statementNode, statement, cast<int_t<std::uint32_t>>(entryBlockId), cast<int_t<std::uint32_t>>(exitBlockId));
			}
			else {
				if (static_cast<bool>((php::identical(cast<int_t<>>(statementNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_statement_id())) && php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_for_id()))))) {
					FrontendStatementPayloadRow statement = __latency_fn_frontend_model_tables_statement_by_id(model, statementNode->payload_row_id, counters);
					__latency_fn_control_flow_graphs_append_for_statement_blocks(artifact, statementNode, statement, cast<int_t<std::uint32_t>>(entryBlockId), cast<int_t<std::uint32_t>>(exitBlockId));
				}
			}
		}
		statementNodeId = statementNode->next_sibling_node_id;
	}
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_control_flow_graphs[]; }
namespace scpp {
string_t __latency_fn_control_flow_graphs_project_tsv(shared_p<ProjectSymbolIndex> symbols, shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("control_flow_graphs::project_tsv", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_graphs.phs", __latency_lines_control_flow_graphs[35]);
	string_t text = required_cast<string_t>(string_t("section\trow_id\towner_symbol_id\tsource_unit_id\tkind_id\tstatement_kind_id\tstatement_node_id\tparent_or_from_block_id\tto_block_id\tfirst_source_row_id\tlast_source_row_id\tsource_row_id\tloop_depth\n"));
	auto __latency_local_0 = symbols->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto symbol = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::not_identical(cast<int_t<>>(symbol->symbol_kind_id), cast<int_t<>>(__latency_fn_project_symbol_index_symbol_kind_function_id())) || php::not_identical(cast<int_t<>>(symbol->source_unit_id), cast<int_t<>>(model->source_unit_id))) || php::identical(cast<int_t<>>(symbol->body_first_node_id), static_cast<int_t<> >(0))))) {
			continue;
		}
		shared_p<ControlFlowGraphArtifact> artifact = __latency_fn_control_flow_graphs_from_frontend_model(symbol, model);
		text = (cast<string_t>(text) + cast<string_t>(__latency_fn_control_flow_graphs_tsv_body(artifact)));
	}
	return text;
}

}

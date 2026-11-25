#include <scpp/lang/php.hpp>
#include "__types/ControlTransferTargetDecision.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_control_flow_transfers_resolved_target_for_ready_transfer.hpp"
#include "__callable/__latency_fn_control_flow_transfers_transfer_kind_continue_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_for_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_while_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
#include "__callable/__latency_fn_control_flow_transfers_append_transfer_row__exec.hpp"
#include "__callable/__latency_fn_control_flow_transfers_status_blocked_id.hpp"
#include "__callable/__latency_fn_control_flow_transfers_status_ready_id.hpp"
#include "__callable/__latency_fn_control_flow_transfers_target_decision_for_one_level_loop_transfer.hpp"
#include "__callable/__latency_fn_control_flow_transfers_append_statement_list_transfers.hpp"
#include "__callable/__latency_fn_control_flow_transfers_append_statement_list_transfers__exec.hpp"
#include "__callable/__latency_fn_control_flow_transfers_append_transfer_row.hpp"
#include "__callable/__latency_fn_control_flow_transfers_is_loop_statement.hpp"
#include "__callable/__latency_fn_control_flow_transfers_transfer_kind_from_statement.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_for_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_if_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_while_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_control_flow_transfers_append_statement_list_transfers.hpp"
#include "__callable/__latency_fn_control_flow_transfers_project_tsv.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_kind_function_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_control_flow_transfers_is_loop_statement.hpp"
#include "__callable/__latency_fn_control_flow_transfers_statement_list_has_blocking_transfer.hpp"
#include "__callable/__latency_fn_control_flow_transfers_target_decision_for_one_level_loop_transfer.hpp"
#include "__callable/__latency_fn_control_flow_transfers_transfer_kind_from_statement.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_for_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_if_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_while_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_transfers_project_has_blocking_diagnostic.hpp"
#include "__callable/__latency_fn_control_flow_transfers_statement_list_has_blocking_transfer.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_kind_function_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
namespace scpp { extern const int __latency_lines_control_flow_transfers[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_control_flow_transfers_resolved_target_for_ready_transfer(shared_p<FrontendModel> model, int_t<std::uint16_t> transferKindId, int_t<std::uint32_t> enclosingLoopNodeId, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("control_flow_transfers::resolved_target_for_ready_transfer", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_transfers.phs", __latency_lines_control_flow_transfers[13]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(transferKindId), cast<int_t<>>(__latency_fn_control_flow_transfers_transfer_kind_continue_id())))) {
		FrontendNodeRow loopNode = __latency_fn_frontend_model_tables_node_by_id(model, enclosingLoopNodeId, counters);
		if (static_cast<bool>(php::identical(cast<int_t<>>(loopNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_while_id())))) {
			FrontendStatementPayloadRow loopStatement = __latency_fn_frontend_model_tables_statement_by_id(model, loopNode->payload_row_id, counters);
			return loopStatement->condition_node_id;
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(loopNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_for_id())))) {
			FrontendStatementPayloadRow loopStatement = __latency_fn_frontend_model_tables_statement_by_id(model, loopNode->payload_row_id, counters);
			return loopStatement->value_node_id;
		}
	}
	return cast<int_t<std::uint32_t>>(enclosingLoopNodeId);
}

}

namespace scpp { extern const int __latency_lines_control_flow_transfers[]; }
namespace scpp {
void __latency_fn_control_flow_transfers_append_transfer_row__exec(string_t& text, int_t<std::uint32_t> rowId, ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, FrontendNodeRow statementNode, int_t<std::uint16_t> transferKindId, int_t<std::uint32_t> enclosingLoopNodeId, int_t<std::uint16_t> loopDepth, shared_p<FrontendModelKernelCounters> counters) {
	shared_p<ControlTransferTargetDecision> decision = __latency_fn_control_flow_transfers_target_decision_for_one_level_loop_transfer(model, statementNode, cast<int_t<std::uint16_t>>(transferKindId), cast<int_t<std::uint32_t>>(enclosingLoopNodeId), cast<int_t<std::uint16_t>>(loopDepth), counters);
	int_t<std::uint16_t> statusId = required_cast<int_t<std::uint16_t>>(__latency_fn_control_flow_transfers_status_blocked_id());
	if (static_cast<bool>(php::condition_truthy(decision->ready))) {
		statusId = __latency_fn_control_flow_transfers_status_ready_id();
	}
	text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(rowId)) + string_t("\t"));
	text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(symbol->symbol_id)) + string_t("\t"));
	text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(symbol->source_unit_id)) + string_t("\t"));
	text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(transferKindId)) + string_t("\t"));
	text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(statusId)) + string_t("\t"));
	text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(decision->diagnostic_id)) + string_t("\t"));
	text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(statementNode->node_id)) + string_t("\t"));
	text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(enclosingLoopNodeId)) + string_t("\t"));
	text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(loopDepth)) + string_t("\t"));
	text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(decision->resolved_target_id)) + string_t("\t"));
	text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(decision->cleanup_status_id)) + string_t("\n"));
}

}

namespace scpp { extern const int __latency_lines_control_flow_transfers[]; }
namespace scpp {
void __latency_fn_control_flow_transfers_append_statement_list_transfers__exec(string_t& text, int_t<std::uint32_t>& rowCount, ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, int_t<std::uint32_t> firstStatementNodeId, int_t<std::uint32_t> enclosingLoopNodeId, int_t<std::uint16_t> loopDepth, shared_p<FrontendModelKernelCounters> counters) {
	int_t<std::uint32_t> statementNodeId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(firstStatementNodeId));
	while (static_cast<bool>((cast<int_t<>>(statementNodeId) > static_cast<int_t<> >(0)))) {
		FrontendNodeRow statementNode = __latency_fn_frontend_model_tables_node_by_id(model, statementNodeId, counters);
		if (static_cast<bool>(php::identical(cast<int_t<>>(statementNode->node_id), static_cast<int_t<> >(0)))) {
			break;
		}
		int_t<std::uint16_t> transferKindId = required_cast<int_t<std::uint16_t>>(__latency_fn_control_flow_transfers_transfer_kind_from_statement(statementNode));
		if (static_cast<bool>((cast<int_t<>>(transferKindId) > static_cast<int_t<> >(0)))) {
			rowCount = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(rowCount) + static_cast<int_t<> >(1)));
			__latency_fn_control_flow_transfers_append_transfer_row(text, cast<int_t<std::uint32_t>>(rowCount), symbol, model, statementNode, cast<int_t<std::uint16_t>>(transferKindId), cast<int_t<std::uint32_t>>(enclosingLoopNodeId), cast<int_t<std::uint16_t>>(loopDepth), counters);
		}
		if (static_cast<bool>((php::identical(cast<int_t<>>(statementNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_statement_id())) && ((php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_if_id())) || php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_while_id()))) || php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_for_id())))))) {
			FrontendStatementPayloadRow statement = __latency_fn_frontend_model_tables_statement_by_id(model, statementNode->payload_row_id, counters);
			int_t<std::uint32_t> childLoopNodeId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(enclosingLoopNodeId));
			int_t<std::uint16_t> childLoopDepth = required_cast<int_t<std::uint16_t>>(cast<int_t<std::uint16_t>>(loopDepth));
			if (static_cast<bool>(php::condition_truthy(__latency_fn_control_flow_transfers_is_loop_statement(statementNode)))) {
				childLoopNodeId = statementNode->node_id;
				childLoopDepth = __latency_fn_structure_row_ids_uint16_from_int((cast<int_t<>>(loopDepth) + static_cast<int_t<> >(1)));
			}
			__latency_fn_control_flow_transfers_append_statement_list_transfers(text, rowCount, symbol, model, statement->body_first_node_id, cast<int_t<std::uint32_t>>(childLoopNodeId), cast<int_t<std::uint16_t>>(childLoopDepth), counters);
			__latency_fn_control_flow_transfers_append_statement_list_transfers(text, rowCount, symbol, model, statement->else_body_first_node_id, cast<int_t<std::uint32_t>>(childLoopNodeId), cast<int_t<std::uint16_t>>(childLoopDepth), counters);
		}
		statementNodeId = statementNode->next_sibling_node_id;
	}
}

}

namespace scpp { extern const int __latency_lines_control_flow_transfers[]; }
namespace scpp {
string_t __latency_fn_control_flow_transfers_project_tsv(shared_p<ProjectSymbolIndex> symbols, shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("control_flow_transfers::project_tsv", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_transfers.phs", __latency_lines_control_flow_transfers[14]);
	string_t text = required_cast<string_t>(string_t("row_id\towner_symbol_id\tsource_unit_id\ttransfer_kind_id\tstatus_id\tdiagnostic_id\tstatement_node_id\tenclosing_loop_node_id\tloop_depth\tresolved_target_id\tcleanup_status_id\n"));
	auto __latency_local_0 = symbols->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto symbol = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::not_identical(cast<int_t<>>(symbol->symbol_kind_id), cast<int_t<>>(__latency_fn_project_symbol_index_symbol_kind_function_id())) || php::not_identical(cast<int_t<>>(symbol->source_unit_id), cast<int_t<>>(model->source_unit_id))) || php::identical(cast<int_t<>>(symbol->body_first_node_id), static_cast<int_t<> >(0))))) {
			continue;
		}
		int_t<std::uint32_t> rowCount = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
		shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
		__latency_fn_control_flow_transfers_append_statement_list_transfers(text, rowCount, symbol, model, symbol->body_first_node_id, __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_kind_id(), counters);
	}
	return text;
}

}

namespace scpp { extern const int __latency_lines_control_flow_transfers[]; }
namespace scpp {
bool_t __latency_fn_control_flow_transfers_statement_list_has_blocking_transfer(shared_p<FrontendModel> model, int_t<std::uint32_t> firstStatementNodeId, int_t<std::uint32_t> enclosingLoopNodeId, int_t<std::uint16_t> loopDepth, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("control_flow_transfers::statement_list_has_blocking_transfer", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_transfers.phs", __latency_lines_control_flow_transfers[15]);
	int_t<std::uint32_t> statementNodeId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(firstStatementNodeId));
	while (static_cast<bool>((cast<int_t<>>(statementNodeId) > static_cast<int_t<> >(0)))) {
		FrontendNodeRow statementNode = __latency_fn_frontend_model_tables_node_by_id(model, statementNodeId, counters);
		if (static_cast<bool>(php::identical(cast<int_t<>>(statementNode->node_id), static_cast<int_t<> >(0)))) {
			break;
		}
		int_t<std::uint16_t> transferKindId = required_cast<int_t<std::uint16_t>>(__latency_fn_control_flow_transfers_transfer_kind_from_statement(statementNode));
		if (static_cast<bool>((cast<int_t<>>(transferKindId) > static_cast<int_t<> >(0)))) {
			shared_p<ControlTransferTargetDecision> decision = __latency_fn_control_flow_transfers_target_decision_for_one_level_loop_transfer(model, statementNode, cast<int_t<std::uint16_t>>(transferKindId), cast<int_t<std::uint32_t>>(enclosingLoopNodeId), cast<int_t<std::uint16_t>>(loopDepth), counters);
			if (static_cast<bool>((!decision->ready))) {
				return bool_t(static_cast<bool_t>(true));
			}
		}
		if (static_cast<bool>((php::identical(cast<int_t<>>(statementNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_statement_id())) && ((php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_if_id())) || php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_while_id()))) || php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_for_id())))))) {
			FrontendStatementPayloadRow statement = __latency_fn_frontend_model_tables_statement_by_id(model, statementNode->payload_row_id, counters);
			int_t<std::uint32_t> childLoopNodeId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(enclosingLoopNodeId));
			int_t<std::uint16_t> childLoopDepth = required_cast<int_t<std::uint16_t>>(cast<int_t<std::uint16_t>>(loopDepth));
			if (static_cast<bool>(php::condition_truthy(__latency_fn_control_flow_transfers_is_loop_statement(statementNode)))) {
				childLoopNodeId = statementNode->node_id;
				childLoopDepth = __latency_fn_structure_row_ids_uint16_from_int((cast<int_t<>>(loopDepth) + static_cast<int_t<> >(1)));
			}
			if (static_cast<bool>((__latency_fn_control_flow_transfers_statement_list_has_blocking_transfer(model, statement->body_first_node_id, cast<int_t<std::uint32_t>>(childLoopNodeId), cast<int_t<std::uint16_t>>(childLoopDepth), counters) || __latency_fn_control_flow_transfers_statement_list_has_blocking_transfer(model, statement->else_body_first_node_id, cast<int_t<std::uint32_t>>(childLoopNodeId), cast<int_t<std::uint16_t>>(childLoopDepth), counters)))) {
				return bool_t(static_cast<bool_t>(true));
			}
		}
		statementNodeId = statementNode->next_sibling_node_id;
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_control_flow_transfers[]; }
namespace scpp {
bool_t __latency_fn_control_flow_transfers_project_has_blocking_diagnostic(shared_p<ProjectSymbolIndex> symbols, shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("control_flow_transfers::project_has_blocking_diagnostic", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_transfers.phs", __latency_lines_control_flow_transfers[16]);
	auto __latency_local_0 = symbols->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto symbol = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::not_identical(cast<int_t<>>(symbol->symbol_kind_id), cast<int_t<>>(__latency_fn_project_symbol_index_symbol_kind_function_id())) || php::not_identical(cast<int_t<>>(symbol->source_unit_id), cast<int_t<>>(model->source_unit_id))) || php::identical(cast<int_t<>>(symbol->body_first_node_id), static_cast<int_t<> >(0))))) {
			continue;
		}
		shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
		if (static_cast<bool>(php::condition_truthy(__latency_fn_control_flow_transfers_statement_list_has_blocking_transfer(model, symbol->body_first_node_id, __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_kind_id(), counters)))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

#include <scpp/lang/php.hpp>
#include "__types/ControlTransferTargetDecision.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/control_flow_transfers.hpp"
#include "__callable/__latency_fn_control_flow_transfers_transfer_kind_break_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_transfers_transfer_kind_continue_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_transfers_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_transfers_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_transfers_diagnostic_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_control_flow_transfers_diagnostic_orphan_transfer_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_transfers_diagnostic_target_cleanup_missing_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_transfers_cleanup_status_not_applicable_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_transfers_cleanup_status_missing_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_transfers_cleanup_status_trivial_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_transfers_transfer_kind_break_id.hpp"
#include "__callable/__latency_fn_control_flow_transfers_transfer_kind_continue_id.hpp"
#include "__callable/__latency_fn_control_flow_transfers_transfer_kind_from_statement.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_break_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_continue_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_control_flow_transfers_is_loop_statement.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_for_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_while_id.hpp"
#include "__callable/__latency_fn_control_flow_transfers_cleanup_status_missing_id.hpp"
#include "__callable/__latency_fn_control_flow_transfers_cleanup_status_not_applicable_id.hpp"
#include "__callable/__latency_fn_control_flow_transfers_cleanup_status_trivial_ready_id.hpp"
#include "__callable/__latency_fn_control_flow_transfers_diagnostic_none_id.hpp"
#include "__callable/__latency_fn_control_flow_transfers_diagnostic_orphan_transfer_id.hpp"
#include "__callable/__latency_fn_control_flow_transfers_diagnostic_target_cleanup_missing_id.hpp"
#include "__callable/__latency_fn_control_flow_transfers_resolved_target_for_ready_transfer.hpp"
#include "__callable/__latency_fn_control_flow_transfers_target_decision_for_one_level_loop_transfer.hpp"
#include "__callable/__latency_fn_control_flow_transfers_transfer_kind_break_id.hpp"
#include "__callable/__latency_fn_control_flow_transfers_transfer_kind_continue_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_for_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_while_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
namespace scpp { extern const int __latency_lines_control_flow_transfers[]; }
namespace scpp {
bool_t control_flow_transfers::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == control_flow_transfers::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_control_flow_transfers[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_transfers_transfer_kind_break_id() {
	SCPP_CALL_DEPTH_GUARD("control_flow_transfers::transfer_kind_break_id", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_transfers.phs", __latency_lines_control_flow_transfers[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_control_flow_transfers[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_transfers_transfer_kind_continue_id() {
	SCPP_CALL_DEPTH_GUARD("control_flow_transfers::transfer_kind_continue_id", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_transfers.phs", __latency_lines_control_flow_transfers[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_control_flow_transfers[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_transfers_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("control_flow_transfers::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_transfers.phs", __latency_lines_control_flow_transfers[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_control_flow_transfers[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_transfers_status_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("control_flow_transfers::status_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_transfers.phs", __latency_lines_control_flow_transfers[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_control_flow_transfers[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_transfers_diagnostic_none_id() {
	SCPP_CALL_DEPTH_GUARD("control_flow_transfers::diagnostic_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_transfers.phs", __latency_lines_control_flow_transfers[4]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_control_flow_transfers[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_transfers_diagnostic_orphan_transfer_id() {
	SCPP_CALL_DEPTH_GUARD("control_flow_transfers::diagnostic_orphan_transfer_id", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_transfers.phs", __latency_lines_control_flow_transfers[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_control_flow_transfers[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_transfers_diagnostic_target_cleanup_missing_id() {
	SCPP_CALL_DEPTH_GUARD("control_flow_transfers::diagnostic_target_cleanup_missing_id", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_transfers.phs", __latency_lines_control_flow_transfers[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_control_flow_transfers[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_transfers_cleanup_status_not_applicable_id() {
	SCPP_CALL_DEPTH_GUARD("control_flow_transfers::cleanup_status_not_applicable_id", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_transfers.phs", __latency_lines_control_flow_transfers[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_control_flow_transfers[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_transfers_cleanup_status_missing_id() {
	SCPP_CALL_DEPTH_GUARD("control_flow_transfers::cleanup_status_missing_id", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_transfers.phs", __latency_lines_control_flow_transfers[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_control_flow_transfers[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_transfers_cleanup_status_trivial_ready_id() {
	SCPP_CALL_DEPTH_GUARD("control_flow_transfers::cleanup_status_trivial_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_transfers.phs", __latency_lines_control_flow_transfers[9]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_control_flow_transfers[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_transfers_transfer_kind_from_statement(FrontendNodeRow statementNode) {
	SCPP_CALL_DEPTH_GUARD("control_flow_transfers::transfer_kind_from_statement", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_transfers.phs", __latency_lines_control_flow_transfers[10]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_break_id())))) {
		return __latency_fn_control_flow_transfers_transfer_kind_break_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_continue_id())))) {
		return __latency_fn_control_flow_transfers_transfer_kind_continue_id();
	}
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_control_flow_transfers[]; }
namespace scpp {
bool_t __latency_fn_control_flow_transfers_is_loop_statement(FrontendNodeRow statementNode) {
	SCPP_CALL_DEPTH_GUARD("control_flow_transfers::is_loop_statement", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_transfers.phs", __latency_lines_control_flow_transfers[11]);
	return (php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_while_id())) || php::identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_for_id())));
}

}

namespace scpp { extern const int __latency_lines_control_flow_transfers[]; }
namespace scpp {
shared_p<ControlTransferTargetDecision> __latency_fn_control_flow_transfers_target_decision_for_one_level_loop_transfer(shared_p<FrontendModel> model, FrontendNodeRow statementNode, int_t<std::uint16_t> transferKindId, int_t<std::uint32_t> enclosingLoopNodeId, int_t<std::uint16_t> loopDepth, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("control_flow_transfers::target_decision_for_one_level_loop_transfer", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_transfers.phs", __latency_lines_control_flow_transfers[12]);
	shared_p<ControlTransferTargetDecision> decision = create<ControlTransferTargetDecision>();
	decision->resolved_target_id = __latency_fn_structure_row_ids_none_id();
	decision->diagnostic_id = __latency_fn_control_flow_transfers_diagnostic_orphan_transfer_id();
	decision->cleanup_status_id = __latency_fn_control_flow_transfers_cleanup_status_not_applicable_id();
	if (static_cast<bool>(((php::identical(cast<int_t<>>(enclosingLoopNodeId), static_cast<int_t<> >(0)) || php::not_identical(cast<int_t<>>(loopDepth), static_cast<int_t<> >(1))) || php::not_identical(cast<int_t<>>(statementNode->parent_node_id), cast<int_t<>>(enclosingLoopNodeId))))) {
		if (static_cast<bool>((cast<int_t<>>(enclosingLoopNodeId) > static_cast<int_t<> >(0)))) {
			decision->diagnostic_id = __latency_fn_control_flow_transfers_diagnostic_target_cleanup_missing_id();
			decision->cleanup_status_id = __latency_fn_control_flow_transfers_cleanup_status_missing_id();
		}
		return decision;
	}
	FrontendNodeRow loopNode = __latency_fn_frontend_model_tables_node_by_id(model, enclosingLoopNodeId, counters);
	bool_t ready = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
	if (static_cast<bool>(php::identical(cast<int_t<>>(transferKindId), cast<int_t<>>(__latency_fn_control_flow_transfers_transfer_kind_break_id())))) {
		ready = (php::identical(cast<int_t<>>(loopNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_while_id())) || php::identical(cast<int_t<>>(loopNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_for_id())));
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(transferKindId), cast<int_t<>>(__latency_fn_control_flow_transfers_transfer_kind_continue_id())))) {
			ready = (php::identical(cast<int_t<>>(loopNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_while_id())) || php::identical(cast<int_t<>>(loopNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_for_id())));
		}
	}
	if (static_cast<bool>((!ready))) {
		decision->diagnostic_id = __latency_fn_control_flow_transfers_diagnostic_target_cleanup_missing_id();
		decision->cleanup_status_id = __latency_fn_control_flow_transfers_cleanup_status_missing_id();
		return decision;
	}
	decision->ready = static_cast<bool_t>(true);
	decision->diagnostic_id = __latency_fn_control_flow_transfers_diagnostic_none_id();
	decision->cleanup_status_id = __latency_fn_control_flow_transfers_cleanup_status_trivial_ready_id();
	decision->resolved_target_id = __latency_fn_control_flow_transfers_resolved_target_for_ready_transfer(model, cast<int_t<std::uint16_t>>(transferKindId), cast<int_t<std::uint32_t>>(enclosingLoopNodeId), counters);
	return decision;
}

}

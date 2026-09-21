#include <scpp/lang/php.hpp>
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_terminator_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_terminator_break_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_terminator_continue_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_terminator_break_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_terminator_is_break.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_terminator_continue_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_terminator_is_continue.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_terminator_break_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_terminator_continue_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_terminator_from_transfer_kind.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_terminator_none_id.hpp"
#include "__callable/__latency_fn_control_flow_transfers_transfer_kind_break_id.hpp"
#include "__callable/__latency_fn_control_flow_transfers_transfer_kind_continue_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_condition_operand_literal_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_condition_operand_local_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_condition_operand_local_binary_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_condition_operand_local_immediate_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_blocked_reason_lowering_plan_not_reintroduced_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_blocked_reason_body_and_text_not_reintroduced_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_blocked_reason_call_argument_storage_not_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_next_step_restore_lowering_plan_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_next_step_restore_function_body_text_preflights_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_control_flow_terminator_none_id() {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::control_flow_terminator_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[101]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_control_flow_terminator_break_id() {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::control_flow_terminator_break_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[102]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_control_flow_terminator_continue_id() {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::control_flow_terminator_continue_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[103]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
bool_t __latency_fn_backend_preflight_requests_control_flow_terminator_is_break(int_t<std::uint16_t> terminatorKindId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::control_flow_terminator_is_break", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[104]);
	return bool_t(php::identical(cast<int_t<>>(terminatorKindId), cast<int_t<>>(__latency_fn_backend_preflight_requests_control_flow_terminator_break_id())));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
bool_t __latency_fn_backend_preflight_requests_control_flow_terminator_is_continue(int_t<std::uint16_t> terminatorKindId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::control_flow_terminator_is_continue", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[105]);
	return bool_t(php::identical(cast<int_t<>>(terminatorKindId), cast<int_t<>>(__latency_fn_backend_preflight_requests_control_flow_terminator_continue_id())));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_control_flow_terminator_from_transfer_kind(int_t<std::uint16_t> transferKindId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::control_flow_terminator_from_transfer_kind", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[106]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(transferKindId), cast<int_t<>>(__latency_fn_control_flow_transfers_transfer_kind_break_id())))) {
		return __latency_fn_backend_preflight_requests_control_flow_terminator_break_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(transferKindId), cast<int_t<>>(__latency_fn_control_flow_transfers_transfer_kind_continue_id())))) {
		return __latency_fn_backend_preflight_requests_control_flow_terminator_continue_id();
	}
	return __latency_fn_backend_preflight_requests_control_flow_terminator_none_id();
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_control_condition_operand_literal_id() {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::control_condition_operand_literal_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[107]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_control_condition_operand_local_id() {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::control_condition_operand_local_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[108]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_control_condition_operand_local_binary_id() {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::control_condition_operand_local_binary_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[109]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_control_condition_operand_local_immediate_id() {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::control_condition_operand_local_immediate_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[110]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[111]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_blocked_reason_lowering_plan_not_reintroduced_id() {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::blocked_reason_lowering_plan_not_reintroduced_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[112]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_blocked_reason_body_and_text_not_reintroduced_id() {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::blocked_reason_body_and_text_not_reintroduced_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[113]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_blocked_reason_call_argument_storage_not_ready_id() {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::blocked_reason_call_argument_storage_not_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[114]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_next_step_restore_lowering_plan_id() {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::next_step_restore_lowering_plan_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[115]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_next_step_restore_function_body_text_preflights_id() {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::next_step_restore_function_body_text_preflights_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[116]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

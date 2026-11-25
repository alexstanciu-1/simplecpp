#include <scpp/lang/php.hpp>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendCallArgumentRow.hpp"
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/CallableAbiReadinessRow.hpp"
#include "__types/DirectCallAuthorizationGateRow.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_direct_call_gate_debug_string.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_status_name.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_equals.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_binary_operand_equals.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operand_equals.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_call_argument_equals.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_operand_equals.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_binary_operand_debug_string.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operand_debug_string.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_call_argument_debug_string.hpp"
#include "__callable/__latency_fn_type_ref_identity_name.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_operand_debug_string.hpp"
#include "__callable/__latency_fn_type_ref_identity_name.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_callable_abi_stable_hash.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_stable_hash.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_binary_operand_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
string_t __latency_fn_backend_preflight_requests_direct_call_gate_debug_string(DirectCallAuthorizationGateRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::direct_call_gate_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[207]);
	return (string_t("direct_call_gate:") + cast<string_t>(cast<int_t<>>(row->reference_id)) + string_t(":") + cast<string_t>(__latency_fn_backend_preflight_requests_status_name(row->status_id)));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
bool_t __latency_fn_backend_preflight_requests_backend_request_equals(BackendRequestAuthorizationRow left, BackendRequestAuthorizationRow right) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_request_equals", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[208]);
	return (((((((php::identical(cast<int_t<>>(left->request_id), cast<int_t<>>(right->request_id)) && php::identical(cast<int_t<>>(left->contract_id), cast<int_t<>>(right->contract_id))) && php::identical(cast<int_t<>>(left->source_row_id), cast<int_t<>>(right->source_row_id))) && php::identical(cast<int_t<>>(left->source_reference_id), cast<int_t<>>(right->source_reference_id))) && php::identical(cast<int_t<>>(left->target_symbol_id), cast<int_t<>>(right->target_symbol_id))) && php::identical(cast<int_t<>>(left->provider_type_ref_id), cast<int_t<>>(right->provider_type_ref_id))) && php::identical(cast<int_t<>>(left->status_id), cast<int_t<>>(right->status_id))) && php::identical(cast<int_t<>>(left->value), cast<int_t<>>(right->value)));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
bool_t __latency_fn_backend_preflight_requests_binary_operand_equals(BackendBinaryOperandRow left, BackendBinaryOperandRow right) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::binary_operand_equals", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[209]);
	return ((((php::identical(cast<int_t<>>(left->owner_row_id), cast<int_t<>>(right->owner_row_id)) && php::identical(cast<int_t<>>(left->left_source_row_id), cast<int_t<>>(right->left_source_row_id))) && php::identical(cast<int_t<>>(left->right_source_row_id), cast<int_t<>>(right->right_source_row_id))) && php::identical(cast<int_t<>>(left->left_value), cast<int_t<>>(right->left_value))) && php::identical(cast<int_t<>>(left->right_value), cast<int_t<>>(right->right_value)));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
bool_t __latency_fn_backend_preflight_requests_local_operand_equals(BackendLocalOperandRow left, BackendLocalOperandRow right) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::local_operand_equals", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[210]);
	return (((((php::identical(cast<int_t<>>(left->owner_row_id), cast<int_t<>>(right->owner_row_id)) && php::identical(cast<int_t<>>(left->local_source_row_id), cast<int_t<>>(right->local_source_row_id))) && php::identical(cast<int_t<>>(left->value_source_row_id), cast<int_t<>>(right->value_source_row_id))) && php::identical(cast<int_t<>>(left->type_ref_id), cast<int_t<>>(right->type_ref_id))) && php::identical(cast<int_t<>>(left->value), cast<int_t<>>(right->value))) && php::identical(cast<int_t<>>(left->local_operation_id), cast<int_t<>>(right->local_operation_id)));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
bool_t __latency_fn_backend_preflight_requests_call_argument_equals(BackendCallArgumentRow left, BackendCallArgumentRow right) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::call_argument_equals", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[211]);
	return (((((php::identical(cast<int_t<>>(left->owner_row_id), cast<int_t<>>(right->owner_row_id)) && php::identical(cast<int_t<>>(left->argument_source_row_id), cast<int_t<>>(right->argument_source_row_id))) && php::identical(cast<int_t<>>(left->type_ref_id), cast<int_t<>>(right->type_ref_id))) && php::identical(cast<int_t<>>(left->value), cast<int_t<>>(right->value))) && php::identical(cast<int_t<>>(left->position), cast<int_t<>>(right->position))) && php::identical(cast<int_t<>>(left->flags), cast<int_t<>>(right->flags)));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
bool_t __latency_fn_backend_preflight_requests_control_flow_operand_equals(BackendControlFlowOperandRow left, BackendControlFlowOperandRow right) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::control_flow_operand_equals", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[212]);
	return (((((((((((((((((php::identical(cast<int_t<>>(left->owner_row_id), cast<int_t<>>(right->owner_row_id)) && php::identical(cast<int_t<>>(left->condition_source_row_id), cast<int_t<>>(right->condition_source_row_id))) && php::identical(cast<int_t<>>(left->body_first_source_row_id), cast<int_t<>>(right->body_first_source_row_id))) && php::identical(cast<int_t<>>(left->body_last_source_row_id), cast<int_t<>>(right->body_last_source_row_id))) && php::identical(cast<int_t<>>(left->body_terminator_source_row_id), cast<int_t<>>(right->body_terminator_source_row_id))) && php::identical(cast<int_t<>>(left->body_terminator_kind_id), cast<int_t<>>(right->body_terminator_kind_id))) && php::identical(cast<int_t<>>(left->else_body_first_source_row_id), cast<int_t<>>(right->else_body_first_source_row_id))) && php::identical(cast<int_t<>>(left->else_body_last_source_row_id), cast<int_t<>>(right->else_body_last_source_row_id))) && php::identical(cast<int_t<>>(left->condition_type_ref_id), cast<int_t<>>(right->condition_type_ref_id))) && php::identical(cast<int_t<>>(left->condition_value), cast<int_t<>>(right->condition_value))) && php::identical(cast<int_t<>>(left->condition_operand_kind_id), cast<int_t<>>(right->condition_operand_kind_id))) && php::identical(cast<int_t<>>(left->condition_local_source_row_id), cast<int_t<>>(right->condition_local_source_row_id))) && php::identical(cast<int_t<>>(left->condition_left_local_source_row_id), cast<int_t<>>(right->condition_left_local_source_row_id))) && php::identical(cast<int_t<>>(left->condition_right_local_source_row_id), cast<int_t<>>(right->condition_right_local_source_row_id))) && php::identical(cast<int_t<>>(left->condition_lhs_type_ref_id), cast<int_t<>>(right->condition_lhs_type_ref_id))) && php::identical(cast<int_t<>>(left->condition_local_operation_id), cast<int_t<>>(right->condition_local_operation_id))) && php::identical(cast<int_t<>>(left->control_operation_id), cast<int_t<>>(right->control_operation_id))) && php::identical(cast<int_t<>>(left->flags), cast<int_t<>>(right->flags)));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
string_t __latency_fn_backend_preflight_requests_binary_operand_debug_string(BackendBinaryOperandRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::binary_operand_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[213]);
	return (string_t("backend_binary_operand:") + cast<string_t>(cast<int_t<>>(row->owner_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->left_value)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->right_value)));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
string_t __latency_fn_backend_preflight_requests_local_operand_debug_string(BackendLocalOperandRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::local_operand_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[214]);
	return (string_t("backend_local_operand:") + cast<string_t>(cast<int_t<>>(row->owner_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->local_source_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->local_operation_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->value)));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
string_t __latency_fn_backend_preflight_requests_call_argument_debug_string(BackendCallArgumentRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::call_argument_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[215]);
	return (string_t("backend_call_argument:") + cast<string_t>(cast<int_t<>>(row->owner_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->argument_source_row_id)) + string_t(":") + cast<string_t>(__latency_fn_type_ref_identity_name(row->type_ref_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->position)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->value)));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
string_t __latency_fn_backend_preflight_requests_control_flow_operand_debug_string(BackendControlFlowOperandRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::control_flow_operand_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[216]);
	return (string_t("backend_control_flow_operand:") + cast<string_t>(cast<int_t<>>(row->owner_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->condition_source_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->body_first_source_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->body_last_source_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->body_terminator_source_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->body_terminator_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->else_body_first_source_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->else_body_last_source_row_id)) + string_t(":") + cast<string_t>(__latency_fn_type_ref_identity_name(row->condition_type_ref_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->control_operation_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->condition_value)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->condition_operand_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->condition_local_source_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->condition_left_local_source_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->condition_right_local_source_row_id)) + string_t(":") + cast<string_t>(__latency_fn_type_ref_identity_name(row->condition_lhs_type_ref_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->condition_local_operation_id)));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_backend_preflight_requests_callable_abi_stable_hash(CallableAbiReadinessRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::callable_abi_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[217]);
	string_t identity = required_cast<string_t>((string_t("callable_abi:v2:") + cast<string_t>(cast<int_t<>>(row->abi_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->reference_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->from_symbol_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->target_symbol_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->return_type_ref_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->abi_status_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_backend_preflight_requests_backend_request_stable_hash(BackendRequestAuthorizationRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_request_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[218]);
	string_t identity = required_cast<string_t>((string_t("backend_request:v2:") + cast<string_t>(cast<int_t<>>(row->request_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->contract_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_reference_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->target_symbol_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->feature_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->provider_type_ref_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->cache_owner_symbol_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->blocked_reason_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->value))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_backend_preflight_requests_binary_operand_stable_hash(BackendBinaryOperandRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::binary_operand_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[219]);
	string_t identity = required_cast<string_t>((string_t("backend_binary_operand:v2:") + cast<string_t>(cast<int_t<>>(row->owner_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->left_source_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->right_source_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->left_value)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->right_value))));
	return php::stable_hash_string_u64(identity);
}

}

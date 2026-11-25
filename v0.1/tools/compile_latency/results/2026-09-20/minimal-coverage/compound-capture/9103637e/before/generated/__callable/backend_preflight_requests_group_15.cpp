#include <scpp/lang/php.hpp>
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/CallableAbiReadinessRow.hpp"
#include "__types/DirectCallAuthorizationGateArtifact.hpp"
#include "__types/DirectCallAuthorizationGateRow.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_immediate_value.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_status_ready_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_is_step_ready.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_status.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_step_kind.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_adapter_none_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_blocked_reason_body_and_text_not_reintroduced_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_direct_call_gate_from_abi_and_request.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_next_step_restore_function_body_text_preflights_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_artifact_kind_direct_call_gate_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_direct_call_gate_artifact.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_direct_call_gate_from_abi_and_request.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_gate_model_direct_call_preflight_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_source_model_project_contracts_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_direct_call_gate_by_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_status_blocked_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_status_name.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_status_ready_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_blocked_reason_body_and_text_not_reintroduced_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_blocked_reason_call_argument_storage_not_ready_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_blocked_reason_lowering_plan_not_reintroduced_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_blocked_reason_name.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_next_step_name.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_next_step_restore_function_body_text_preflights_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_next_step_restore_lowering_plan_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_callable_abi_debug_string.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_status_name.hpp"
#include "__callable/__latency_fn_type_ref_identity_name.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_debug_string.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_status_name.hpp"
#include "__callable/__latency_fn_type_ref_identity_name.hpp"
namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::int32_t> __latency_fn_backend_preflight_requests_work_immediate_value(BackendRequestAuthorizationRow work) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::work_immediate_value", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[197]);
	return work->value;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
bool_t __latency_fn_backend_preflight_requests_work_is_step_ready(BackendRequestAuthorizationRow work) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::work_is_step_ready", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[198]);
	return (php::identical(cast<int_t<>>(__latency_fn_backend_preflight_requests_work_status(work)), cast<int_t<>>(__latency_fn_backend_preflight_requests_status_ready_id())) && (cast<int_t<>>(__latency_fn_backend_preflight_requests_work_step_kind(work)) > static_cast<int_t<> >(0)));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
DirectCallAuthorizationGateRow __latency_fn_backend_preflight_requests_direct_call_gate_from_abi_and_request(CallableAbiReadinessRow abi, BackendRequestAuthorizationRow request) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::direct_call_gate_from_abi_and_request", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[199]);
	DirectCallAuthorizationGateRow row = DirectCallAuthorizationGateRow{};
	row->gate_id = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	row->reference_id = abi->reference_id;
	row->from_symbol_id = abi->from_symbol_id;
	row->target_symbol_id = abi->target_symbol_id;
	row->target_source_unit_id = abi->target_source_unit_id;
	row->abi_status_id = abi->abi_status_id;
	row->definition_status_id = __latency_fn_backend_preflight_requests_status_blocked_id();
	row->body_status_id = __latency_fn_backend_preflight_requests_status_blocked_id();
	row->body_text_status_id = __latency_fn_backend_preflight_requests_status_blocked_id();
	row->partition_status_id = __latency_fn_backend_preflight_requests_status_blocked_id();
	row->link_status_id = __latency_fn_backend_preflight_requests_status_blocked_id();
	row->backend_request_transition_id = __latency_fn_structure_row_ids_none_kind_id();
	row->status_id = __latency_fn_backend_preflight_requests_status_blocked_id();
	row->blocked_reason_id = __latency_fn_backend_preflight_requests_blocked_reason_body_and_text_not_reintroduced_id();
	row->next_step_id = __latency_fn_backend_preflight_requests_next_step_restore_function_body_text_preflights_id();
	row->blocked_adapter_id = request->lowering_adapter_id;
	row->candidate_adapter_id = __latency_fn_backend_preflight_requests_adapter_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
DirectCallAuthorizationGateArtifact __latency_fn_backend_preflight_requests_direct_call_gate_artifact(CallableAbiReadinessRow abi, BackendRequestAuthorizationRow request) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::direct_call_gate_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[200]);
	DirectCallAuthorizationGateArtifact artifact = DirectCallAuthorizationGateArtifact{};
	artifact->artifact_kind_id = __latency_fn_backend_preflight_requests_artifact_kind_direct_call_gate_id();
	artifact->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	artifact->source_model_id = __latency_fn_backend_preflight_requests_source_model_project_contracts_id();
	artifact->gate_model_id = __latency_fn_backend_preflight_requests_gate_model_direct_call_preflight_id();
	DirectCallAuthorizationGateRow row = __latency_fn_backend_preflight_requests_direct_call_gate_from_abi_and_request(abi, request);
	(void) artifact->rows.append(row);
	artifact->row_count = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	artifact->blocked_count = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
DirectCallAuthorizationGateRow __latency_fn_backend_preflight_requests_direct_call_gate_by_id(DirectCallAuthorizationGateArtifact artifact, int_t<std::uint32_t> gateId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::direct_call_gate_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[201]);
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->gate_id), cast<int_t<>>(gateId)))) {
			return row;
		}
	}
	DirectCallAuthorizationGateRow empty = DirectCallAuthorizationGateRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
string_t __latency_fn_backend_preflight_requests_status_name(int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[202]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_backend_preflight_requests_status_ready_id())))) {
		return string_t("ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_backend_preflight_requests_status_blocked_id())))) {
		return string_t("blocked");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
string_t __latency_fn_backend_preflight_requests_blocked_reason_name(int_t<std::uint16_t> blockedReasonId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::blocked_reason_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[203]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_backend_preflight_requests_blocked_reason_none_id())))) {
		return string_t("");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_backend_preflight_requests_blocked_reason_lowering_plan_not_reintroduced_id())))) {
		return string_t("lowering_plan_not_reintroduced");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_backend_preflight_requests_blocked_reason_body_and_text_not_reintroduced_id())))) {
		return string_t("function_body_and_text_preflights_not_reintroduced");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_backend_preflight_requests_blocked_reason_call_argument_storage_not_ready_id())))) {
		return string_t("call_argument_storage_not_ready");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
string_t __latency_fn_backend_preflight_requests_next_step_name(int_t<std::uint16_t> nextStepId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::next_step_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[204]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(nextStepId), cast<int_t<>>(__latency_fn_backend_preflight_requests_next_step_restore_lowering_plan_id())))) {
		return string_t("restore_lowering_plan_rows");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(nextStepId), cast<int_t<>>(__latency_fn_backend_preflight_requests_next_step_restore_function_body_text_preflights_id())))) {
		return string_t("restore_function_body_text_preflight_rows");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
string_t __latency_fn_backend_preflight_requests_callable_abi_debug_string(CallableAbiReadinessRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::callable_abi_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[205]);
	return (string_t("callable_abi:") + cast<string_t>(cast<int_t<>>(row->reference_id)) + string_t(":") + cast<string_t>(__latency_fn_type_ref_identity_name(row->return_type_ref_id)) + string_t(":") + cast<string_t>(__latency_fn_backend_preflight_requests_status_name(row->abi_status_id)));
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
string_t __latency_fn_backend_preflight_requests_backend_request_debug_string(BackendRequestAuthorizationRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::backend_request_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[206]);
	return (string_t("backend_request:") + cast<string_t>(cast<int_t<>>(row->request_id)) + string_t(":") + cast<string_t>(__latency_fn_type_ref_identity_name(row->provider_type_ref_id)) + string_t(":") + cast<string_t>(__latency_fn_backend_preflight_requests_status_name(row->status_id)));
}

}

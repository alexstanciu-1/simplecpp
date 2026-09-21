#include <scpp/lang/php.hpp>
#include "__types/ResidentDaemonDirtySourceProjectionRow.hpp"
#include "__types/ResidentDaemonIpcArtifact.hpp"
#include "__types/ResidentDaemonLoopPolicyRow.hpp"
#include "__types/ResidentDaemonRequestRow.hpp"
#include "__types/ResidentDaemonResponseRow.hpp"
#include "__types/ResidentDaemonSessionRow.hpp"
#include "__types/ResidentDaemonSourceSlotEventRow.hpp"
#include "__types/ResidentDaemonSourceSlotScanRow.hpp"
#include "__types/ResidentDaemonSourceSlotStatRow.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_session_equals.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_request_equals.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_response_equals.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_equals.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_stat_equals.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_scan_equals.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_dirty_source_projection_equals.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_loop_equals.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_session_status_active_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_session_status_missing_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_session_status_name.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_session_status_stale_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_request_status_completed_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_request_status_name.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_request_status_pending_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_response_status_compile_error_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_response_status_daemon_error_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_response_status_name.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_response_status_ok_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_response_status_timeout_id.hpp"
namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
ResidentDaemonSourceSlotEventRow __latency_fn_resident_daemon_file_ipc_source_slot_event_by_id(shared_p<ResidentDaemonIpcArtifact> artifact, int_t<std::uint32_t> eventId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::source_slot_event_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[83]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(eventId, cast<int_t<>>(artifact->source_slot_event_count))))) {
		ResidentDaemonSourceSlotEventRow row = artifact->source_slot_events[__latency_fn_structure_row_ids_dense_index(eventId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->event_id), cast<int_t<>>(eventId)))) {
			return row;
		}
	}
	auto __latency_local_0 = artifact->source_slot_events;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->event_id), cast<int_t<>>(eventId)))) {
			return row;
		}
	}
	ResidentDaemonSourceSlotEventRow empty = ResidentDaemonSourceSlotEventRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
bool_t __latency_fn_resident_daemon_file_ipc_session_equals(ResidentDaemonSessionRow left, ResidentDaemonSessionRow right) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::session_equals", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[84]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(left->session_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(right->session_id), static_cast<int_t<> >(0))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return (((((((php::identical(cast<int_t<>>(left->session_id), cast<int_t<>>(right->session_id)) && php::identical(cast<int_t<>>(left->project_root_path_id), cast<int_t<>>(right->project_root_path_id))) && php::identical(cast<int_t<>>(left->session_path_id), cast<int_t<>>(right->session_path_id))) && php::identical(cast<int_t<>>(left->request_dir_path_id), cast<int_t<>>(right->request_dir_path_id))) && php::identical(cast<int_t<>>(left->response_dir_path_id), cast<int_t<>>(right->response_dir_path_id))) && php::identical(cast<int_t<>>(left->pid), cast<int_t<>>(right->pid))) && php::identical(cast<int_t<>>(left->generation), cast<int_t<>>(right->generation))) && php::identical(cast<int_t<>>(left->status_id), cast<int_t<>>(right->status_id)));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
bool_t __latency_fn_resident_daemon_file_ipc_request_equals(ResidentDaemonRequestRow left, ResidentDaemonRequestRow right) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::request_equals", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[85]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(left->request_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(right->request_id), static_cast<int_t<> >(0))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return ((((((php::identical(cast<int_t<>>(left->request_id), cast<int_t<>>(right->request_id)) && php::identical(cast<int_t<>>(left->session_id), cast<int_t<>>(right->session_id))) && php::identical(cast<int_t<>>(left->request_path_id), cast<int_t<>>(right->request_path_id))) && php::identical(cast<int_t<>>(left->response_path_id), cast<int_t<>>(right->response_path_id))) && php::identical(cast<int_t<>>(left->source_slot_event_first_id), cast<int_t<>>(right->source_slot_event_first_id))) && php::identical(cast<int_t<>>(left->source_slot_event_count), cast<int_t<>>(right->source_slot_event_count))) && php::identical(cast<int_t<>>(left->status_id), cast<int_t<>>(right->status_id)));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
bool_t __latency_fn_resident_daemon_file_ipc_response_equals(ResidentDaemonResponseRow left, ResidentDaemonResponseRow right) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::response_equals", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[86]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(left->response_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(right->response_id), static_cast<int_t<> >(0))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return (((((((php::identical(cast<int_t<>>(left->response_id), cast<int_t<>>(right->response_id)) && php::identical(cast<int_t<>>(left->request_id), cast<int_t<>>(right->request_id))) && php::identical(cast<int_t<>>(left->stdout_text_id), cast<int_t<>>(right->stdout_text_id))) && php::identical(cast<int_t<>>(left->stderr_text_id), cast<int_t<>>(right->stderr_text_id))) && php::identical(cast<int_t<>>(left->summary_text_id), cast<int_t<>>(right->summary_text_id))) && php::identical(cast<int_t<>>(left->elapsed_us), cast<int_t<>>(right->elapsed_us))) && php::identical(cast<int_t<>>(left->status_id), cast<int_t<>>(right->status_id))) && php::identical(cast<int_t<>>(left->exit_code), cast<int_t<>>(right->exit_code)));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
bool_t __latency_fn_resident_daemon_file_ipc_source_slot_event_equals(ResidentDaemonSourceSlotEventRow left, ResidentDaemonSourceSlotEventRow right) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::source_slot_event_equals", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[87]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(left->event_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(right->event_id), static_cast<int_t<> >(0))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return (((php::identical(cast<int_t<>>(left->event_id), cast<int_t<>>(right->event_id)) && php::identical(cast<int_t<>>(left->request_id), cast<int_t<>>(right->request_id))) && php::identical(cast<int_t<>>(left->packed_event), cast<int_t<>>(right->packed_event))) && php::identical(cast<int_t<>>(left->status_id), cast<int_t<>>(right->status_id)));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
bool_t __latency_fn_resident_daemon_file_ipc_source_slot_stat_equals(ResidentDaemonSourceSlotStatRow left, ResidentDaemonSourceSlotStatRow right) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::source_slot_stat_equals", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[88]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(left->stat_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(right->stat_id), static_cast<int_t<> >(0))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return ((((((((php::identical(cast<int_t<>>(left->stat_id), cast<int_t<>>(right->stat_id)) && php::identical(cast<int_t<>>(left->request_id), cast<int_t<>>(right->request_id))) && php::identical(cast<int_t<>>(left->source_slot_id), cast<int_t<>>(right->source_slot_id))) && php::identical(cast<int_t<>>(left->path_id), cast<int_t<>>(right->path_id))) && php::identical(cast<int_t<>>(left->previous_size), cast<int_t<>>(right->previous_size))) && php::identical(cast<int_t<>>(left->current_size), cast<int_t<>>(right->current_size))) && php::identical(cast<int_t<>>(left->previous_content_hash), cast<int_t<>>(right->previous_content_hash))) && php::identical(cast<int_t<>>(left->current_content_hash), cast<int_t<>>(right->current_content_hash))) && php::identical(cast<int_t<>>(left->scan_status_id), cast<int_t<>>(right->scan_status_id)));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
bool_t __latency_fn_resident_daemon_file_ipc_source_slot_scan_equals(ResidentDaemonSourceSlotScanRow left, ResidentDaemonSourceSlotScanRow right) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::source_slot_scan_equals", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[89]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(left->scan_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(right->scan_id), static_cast<int_t<> >(0))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return (((((((php::identical(cast<int_t<>>(left->scan_id), cast<int_t<>>(right->scan_id)) && php::identical(cast<int_t<>>(left->request_id), cast<int_t<>>(right->request_id))) && php::identical(cast<int_t<>>(left->source_slot_stat_first_id), cast<int_t<>>(right->source_slot_stat_first_id))) && php::identical(cast<int_t<>>(left->source_slot_stat_count), cast<int_t<>>(right->source_slot_stat_count))) && php::identical(cast<int_t<>>(left->candidate_event_count), cast<int_t<>>(right->candidate_event_count))) && php::identical(cast<int_t<>>(left->event_first_id), cast<int_t<>>(right->event_first_id))) && php::identical(cast<int_t<>>(left->event_count), cast<int_t<>>(right->event_count))) && php::identical(cast<int_t<>>(left->status_id), cast<int_t<>>(right->status_id)));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
bool_t __latency_fn_resident_daemon_file_ipc_dirty_source_projection_equals(ResidentDaemonDirtySourceProjectionRow left, ResidentDaemonDirtySourceProjectionRow right) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::dirty_source_projection_equals", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[90]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(left->projection_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(right->projection_id), static_cast<int_t<> >(0))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return (((((((((((((((php::identical(cast<int_t<>>(left->projection_id), cast<int_t<>>(right->projection_id)) && php::identical(cast<int_t<>>(left->request_id), cast<int_t<>>(right->request_id))) && php::identical(cast<int_t<>>(left->scan_id), cast<int_t<>>(right->scan_id))) && php::identical(cast<int_t<>>(left->owner_run_id), cast<int_t<>>(right->owner_run_id))) && php::identical(cast<int_t<>>(left->transaction_id), cast<int_t<>>(right->transaction_id))) && php::identical(cast<int_t<>>(left->source_slot_event_first_id), cast<int_t<>>(right->source_slot_event_first_id))) && php::identical(cast<int_t<>>(left->source_slot_event_count), cast<int_t<>>(right->source_slot_event_count))) && php::identical(cast<int_t<>>(left->input_source_count), cast<int_t<>>(right->input_source_count))) && php::identical(cast<int_t<>>(left->dirty_source_count), cast<int_t<>>(right->dirty_source_count))) && php::identical(cast<int_t<>>(left->reused_source_count), cast<int_t<>>(right->reused_source_count))) && php::identical(cast<int_t<>>(left->changed_source_count), cast<int_t<>>(right->changed_source_count))) && php::identical(cast<int_t<>>(left->added_source_count), cast<int_t<>>(right->added_source_count))) && php::identical(cast<int_t<>>(left->deleted_source_count), cast<int_t<>>(right->deleted_source_count))) && php::identical(cast<int_t<>>(left->parse_candidate_count), cast<int_t<>>(right->parse_candidate_count))) && php::identical(cast<int_t<>>(left->execution_mode_id), cast<int_t<>>(right->execution_mode_id))) && php::identical(cast<int_t<>>(left->status_id), cast<int_t<>>(right->status_id)));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
bool_t __latency_fn_resident_daemon_file_ipc_loop_equals(ResidentDaemonLoopPolicyRow left, ResidentDaemonLoopPolicyRow right) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::loop_equals", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[91]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(left->loop_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(right->loop_id), static_cast<int_t<> >(0))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return ((((((((php::identical(cast<int_t<>>(left->loop_id), cast<int_t<>>(right->loop_id)) && php::identical(cast<int_t<>>(left->session_id), cast<int_t<>>(right->session_id))) && php::identical(cast<int_t<>>(left->idle_timeout_ms), cast<int_t<>>(right->idle_timeout_ms))) && php::identical(cast<int_t<>>(left->test_idle_timeout_ms), cast<int_t<>>(right->test_idle_timeout_ms))) && php::identical(cast<int_t<>>(left->poll_interval_ms), cast<int_t<>>(right->poll_interval_ms))) && php::identical(cast<int_t<>>(left->request_limit), cast<int_t<>>(right->request_limit))) && php::identical(cast<int_t<>>(left->processed_request_count), cast<int_t<>>(right->processed_request_count))) && php::identical(cast<int_t<>>(left->status_id), cast<int_t<>>(right->status_id))) && php::identical(cast<int_t<>>(left->shutdown_reason_id), cast<int_t<>>(right->shutdown_reason_id)));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_session_status_name(int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::session_status_name", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[92]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_session_status_active_id())))) {
		return string_t("active");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_session_status_stale_id())))) {
		return string_t("stale");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_session_status_missing_id())))) {
		return string_t("missing");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_request_status_name(int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::request_status_name", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[93]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_request_status_pending_id())))) {
		return string_t("pending");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_request_status_completed_id())))) {
		return string_t("completed");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_response_status_name(int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::response_status_name", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[94]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_response_status_ok_id())))) {
		return string_t("ok");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_response_status_compile_error_id())))) {
		return string_t("compile_error");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_response_status_daemon_error_id())))) {
		return string_t("daemon_error");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_response_status_timeout_id())))) {
		return string_t("timeout");
	}
	return string_t("unknown");
}

}

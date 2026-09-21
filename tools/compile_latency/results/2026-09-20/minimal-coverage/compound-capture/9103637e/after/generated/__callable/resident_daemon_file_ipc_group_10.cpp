#include <scpp/lang/php.hpp>
#include "__types/ResidentDaemonDirtySourceProjectionRow.hpp"
#include "__types/ResidentDaemonLoopPolicyRow.hpp"
#include "__types/ResidentDaemonRequestRow.hpp"
#include "__types/ResidentDaemonSessionRow.hpp"
#include "__types/ResidentDaemonSourceSlotScanRow.hpp"
#include "__types/ResidentDaemonSourceSlotStatRow.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_scan_debug_string.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_dirty_projection_execution_mode_name.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_dirty_source_projection_debug_string.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_loop_debug_string.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_loop_shutdown_reason_name.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_session_stable_hash.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_loop_stable_hash.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_stat_stable_hash.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_scan_stable_hash.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_dirty_source_projection_stable_hash.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_request_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_source_slot_scan_debug_string(ResidentDaemonSourceSlotScanRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::source_slot_scan_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[105]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->scan_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	string_t text = required_cast<string_t>(string_t("resident_daemon_source_slot_scan:"));
	text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(row->source_slot_stat_count)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(cast<int_t<>>(row->candidate_event_count)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(cast<int_t<>>(row->event_count)));
	return text;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_dirty_source_projection_debug_string(ResidentDaemonDirtySourceProjectionRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::dirty_source_projection_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[106]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->projection_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	string_t text = required_cast<string_t>(string_t("resident_daemon_dirty_projection:"));
	text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(row->projection_id)));
	text = (cast<string_t>(text) + string_t(":dirty=") + cast<string_t>(cast<int_t<>>(row->dirty_source_count)));
	text = (cast<string_t>(text) + string_t(":parse=") + cast<string_t>(cast<int_t<>>(row->parse_candidate_count)));
	text = (cast<string_t>(text) + string_t(":mode=") + cast<string_t>(__latency_fn_resident_daemon_file_ipc_dirty_projection_execution_mode_name(row->execution_mode_id)));
	return text;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_loop_debug_string(ResidentDaemonLoopPolicyRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::loop_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[107]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->loop_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	string_t text = required_cast<string_t>(string_t("resident_daemon_loop:"));
	text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(row->processed_request_count)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(__latency_fn_resident_daemon_file_ipc_loop_shutdown_reason_name(row->shutdown_reason_id)));
	return text;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_resident_daemon_file_ipc_session_stable_hash(ResidentDaemonSessionRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::session_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[108]);
	string_t identity = required_cast<string_t>(string_t("resident_daemon_session:v2:"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->session_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->project_root_path_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->session_path_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->request_dir_path_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->response_dir_path_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->pid)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->generation)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->idle_timeout_ms)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->transport_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->status_id)));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_resident_daemon_file_ipc_loop_stable_hash(ResidentDaemonLoopPolicyRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::loop_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[109]);
	string_t identity = required_cast<string_t>(string_t("resident_daemon_loop:v2:"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->loop_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->session_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->idle_timeout_ms)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->test_idle_timeout_ms)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->poll_interval_ms)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->request_limit)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->processed_request_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->shutdown_reason_id)));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_resident_daemon_file_ipc_source_slot_stat_stable_hash(ResidentDaemonSourceSlotStatRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::source_slot_stat_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[110]);
	string_t identity = required_cast<string_t>(string_t("resident_daemon_source_slot_stat:v2:"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->stat_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->request_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->source_slot_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->path_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->previous_size)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->current_size)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->previous_content_hash)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->current_content_hash)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->previous_mtime_tick)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->current_mtime_tick)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->scan_status_id)));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_resident_daemon_file_ipc_source_slot_scan_stable_hash(ResidentDaemonSourceSlotScanRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::source_slot_scan_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[111]);
	string_t identity = required_cast<string_t>(string_t("resident_daemon_source_slot_scan:v2:"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->scan_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->request_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->source_slot_stat_first_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->source_slot_stat_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->candidate_event_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->event_first_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->event_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->status_id)));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_resident_daemon_file_ipc_dirty_source_projection_stable_hash(ResidentDaemonDirtySourceProjectionRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::dirty_source_projection_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[112]);
	string_t identity = required_cast<string_t>(string_t("resident_daemon_dirty_projection:v2:"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->projection_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->request_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->scan_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->owner_run_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->transaction_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->source_slot_event_first_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->source_slot_event_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->input_source_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->dirty_source_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->reused_source_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->changed_source_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->added_source_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->deleted_source_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->parse_candidate_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->execution_mode_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->status_id)));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_resident_daemon_file_ipc_request_stable_hash(ResidentDaemonRequestRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::request_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[113]);
	string_t identity = required_cast<string_t>(string_t("resident_daemon_request:v2:"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->request_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->session_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->project_root_path_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->request_path_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->response_path_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->source_slot_event_first_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->source_slot_event_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->command_kind_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->status_id)));
	return php::stable_hash_string_u64(identity);
}

}

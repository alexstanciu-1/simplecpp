#include <scpp/lang/php.hpp>
#include "__types/ResidentDaemonIpcArtifact.hpp"
#include "__types/ResidentDaemonRequestRow.hpp"
#include "__types/ResidentDaemonResponseRow.hpp"
#include "__types/ResidentDaemonSessionRow.hpp"
#include "__types/ResidentDaemonSourceSlotEventRow.hpp"
#include "__types/ResidentDaemonSourceSlotStatRow.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_loop_shutdown_idle_timeout_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_loop_shutdown_reason_name.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_loop_shutdown_test_request_limit_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_scan_added_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_scan_changed_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_scan_deleted_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_scan_status_name.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_scan_unchanged_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_added_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_changed_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_deleted_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_name.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_rescan_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_client_action_forward_request_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_client_action_name.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_client_action_start_local_resident_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_dirty_projection_execution_full_project_compare_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_dirty_projection_execution_mode_name.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_path_by_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_session_debug_string.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_session_status_name.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_path_by_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_request_debug_string.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_request_status_name.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_response_debug_string.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_response_status_name.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_debug_string.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_name.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_slot_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_path_by_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_scan_status_name.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_stat_debug_string.hpp"
namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_loop_shutdown_reason_name(int_t<std::uint16_t> reasonId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::loop_shutdown_reason_name", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[95]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(reasonId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_loop_shutdown_test_request_limit_id())))) {
		return string_t("test_request_limit");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(reasonId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_loop_shutdown_idle_timeout_id())))) {
		return string_t("idle_timeout");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_source_slot_scan_status_name(int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::source_slot_scan_status_name", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[96]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_source_slot_scan_unchanged_id())))) {
		return string_t("unchanged");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_source_slot_scan_changed_id())))) {
		return string_t("changed");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_source_slot_scan_added_id())))) {
		return string_t("added");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_source_slot_scan_deleted_id())))) {
		return string_t("deleted");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_source_slot_event_kind_name(int_t<std::uint16_t> kindId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::source_slot_event_kind_name", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[97]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(kindId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_changed_id())))) {
		return string_t("changed");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(kindId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_added_id())))) {
		return string_t("added");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(kindId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_deleted_id())))) {
		return string_t("deleted");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(kindId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_rescan_id())))) {
		return string_t("rescan");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_client_action_name(int_t<std::uint16_t> actionId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::client_action_name", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[98]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(actionId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_client_action_forward_request_id())))) {
		return string_t("forward_request");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(actionId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_client_action_start_local_resident_id())))) {
		return string_t("start_local_resident");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_dirty_projection_execution_mode_name(int_t<std::uint16_t> modeId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::dirty_projection_execution_mode_name", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[99]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(modeId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_dirty_projection_execution_full_project_compare_id())))) {
		return string_t("full_project_compare");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_session_debug_string(shared_p<ResidentDaemonIpcArtifact> artifact, ResidentDaemonSessionRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::session_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[100]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->session_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	string_t text = required_cast<string_t>(string_t("resident_daemon_session:"));
	text = (cast<string_t>(text) + cast<string_t>(__latency_fn_resident_daemon_file_ipc_session_status_name(row->status_id)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(__latency_fn_resident_daemon_file_ipc_path_by_id(artifact, row->project_root_path_id)));
	return text;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_request_debug_string(shared_p<ResidentDaemonIpcArtifact> artifact, ResidentDaemonRequestRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::request_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[101]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->request_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	string_t text = required_cast<string_t>(string_t("resident_daemon_request:"));
	text = (cast<string_t>(text) + cast<string_t>(__latency_fn_resident_daemon_file_ipc_request_status_name(row->status_id)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(__latency_fn_resident_daemon_file_ipc_path_by_id(artifact, row->request_path_id)));
	return text;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_response_debug_string(ResidentDaemonResponseRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::response_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[102]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->response_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	string_t text = required_cast<string_t>(string_t("resident_daemon_response:"));
	text = (cast<string_t>(text) + cast<string_t>(__latency_fn_resident_daemon_file_ipc_response_status_name(row->status_id)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(cast<int_t<>>(row->exit_code)));
	return text;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_source_slot_event_debug_string(ResidentDaemonSourceSlotEventRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::source_slot_event_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[103]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->event_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	int_t<std::uint16_t> kindId = required_cast<int_t<std::uint16_t>>(__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_id(row->packed_event));
	int_t<std::uint32_t> slotId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_daemon_file_ipc_source_slot_event_slot_id(row->packed_event));
	string_t text = required_cast<string_t>(string_t("resident_daemon_event:"));
	text = (cast<string_t>(text) + cast<string_t>(__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_name(cast<int_t<std::uint16_t>>(kindId))));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(cast<int_t<>>(slotId)));
	return text;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_source_slot_stat_debug_string(shared_p<ResidentDaemonIpcArtifact> artifact, ResidentDaemonSourceSlotStatRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::source_slot_stat_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[104]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->stat_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	string_t text = required_cast<string_t>(string_t("resident_daemon_source_slot_stat:"));
	text = (cast<string_t>(text) + cast<string_t>(__latency_fn_resident_daemon_file_ipc_source_slot_scan_status_name(row->scan_status_id)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_slot_id)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(__latency_fn_resident_daemon_file_ipc_path_by_id(artifact, row->path_id)));
	return text;
}

}

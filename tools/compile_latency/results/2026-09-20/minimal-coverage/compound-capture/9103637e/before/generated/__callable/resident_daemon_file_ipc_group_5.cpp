#include <scpp/lang/php.hpp>
#include "__types/ResidentDaemonIpcArtifact.hpp"
#include "__types/ResidentDaemonLoopPolicyRow.hpp"
#include "__types/ResidentDaemonRequestRow.hpp"
#include "__types/ResidentDaemonResponseRow.hpp"
#include "__types/ResidentDaemonSessionRow.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_bounded_loop_proof.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_idle_timeout_ms.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_loop_shutdown_idle_timeout_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_loop_shutdown_test_request_limit_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_loop_status_completed_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_mark_request_completed.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_path_by_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_request_json.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_response_json.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_text_by_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_path_by_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_session_json.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_write_text_file.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_path_by_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_request_json.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_write_request_json.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_write_text_file.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_path_by_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_response_json.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_write_response_json.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_write_text_file.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_path_by_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_session_json.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_write_session_json.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_write_text_file.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_classify_session_status.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_session_status_active_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_session_status_stale_id.hpp"
namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_append_bounded_loop_proof(shared_p<ResidentDaemonIpcArtifact>& artifact, int_t<std::uint32_t> sessionId, int_t<std::uint32_t> requestId, int_t<std::uint32_t> pollIntervalMs, int_t<std::uint32_t> testIdleTimeoutMs) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::append_bounded_loop_proof", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[59]);
	int_t<std::uint32_t> processedRequestCount = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	int_t<std::uint16_t> shutdownReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_resident_daemon_file_ipc_loop_shutdown_idle_timeout_id());
	if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_daemon_file_ipc_mark_request_completed(artifact, cast<int_t<std::uint32_t>>(requestId))))) {
		processedRequestCount = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
		shutdownReasonId = __latency_fn_resident_daemon_file_ipc_loop_shutdown_test_request_limit_id();
	}
	ResidentDaemonLoopPolicyRow row = ResidentDaemonLoopPolicyRow{};
	row->loop_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->loops));
	row->session_id = sessionId;
	row->idle_timeout_ms = __latency_fn_resident_daemon_file_ipc_idle_timeout_ms();
	row->test_idle_timeout_ms = testIdleTimeoutMs;
	row->poll_interval_ms = pollIntervalMs;
	row->request_limit = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	row->processed_request_count = processedRequestCount;
	row->status_id = __latency_fn_resident_daemon_file_ipc_loop_status_completed_id();
	row->shutdown_reason_id = shutdownReasonId;
	(void) artifact->loops.append(row);
	artifact->loop_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->loops));
	return row->loop_id;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_request_json(shared_p<ResidentDaemonIpcArtifact> artifact, ResidentDaemonRequestRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::request_json", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[60]);
	return (string_t("{\n  \"schema_version\": 1,\n  \"transport\": \"file_ipc\",\n  \"request_id\": ") + cast<string_t>(cast<int_t<>>(row->request_id)) + string_t(",\n") + string_t("  \"session_id\": ") + cast<string_t>(cast<int_t<>>(row->session_id)) + string_t(",\n") + string_t("  \"command_kind_id\": ") + cast<string_t>(cast<int_t<>>(row->command_kind_id)) + string_t(",\n") + string_t("  \"status_id\": ") + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t(",\n") + string_t("  \"project_root\": ") + cast<string_t>(json::encode(__latency_fn_resident_daemon_file_ipc_path_by_id(artifact, row->project_root_path_id))) + string_t(",\n") + string_t("  \"request_path\": ") + cast<string_t>(json::encode(__latency_fn_resident_daemon_file_ipc_path_by_id(artifact, row->request_path_id))) + string_t(",\n") + string_t("  \"response_path\": ") + cast<string_t>(json::encode(__latency_fn_resident_daemon_file_ipc_path_by_id(artifact, row->response_path_id))) + string_t(",\n") + string_t("  \"created_age_ms\": ") + cast<string_t>(cast<int_t<>>(row->created_age_ms)) + string_t(",\n") + string_t("  \"source_slot_event_first_id\": ") + cast<string_t>(cast<int_t<>>(row->source_slot_event_first_id)) + string_t(",\n") + string_t("  \"source_slot_event_count\": ") + cast<string_t>(cast<int_t<>>(row->source_slot_event_count)) + string_t("\n") + string_t("}\n"));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_response_json(shared_p<ResidentDaemonIpcArtifact> artifact, ResidentDaemonResponseRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::response_json", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[61]);
	return (string_t("{\n  \"schema_version\": 1,\n  \"transport\": \"file_ipc\",\n  \"response_id\": ") + cast<string_t>(cast<int_t<>>(row->response_id)) + string_t(",\n") + string_t("  \"request_id\": ") + cast<string_t>(cast<int_t<>>(row->request_id)) + string_t(",\n") + string_t("  \"status_id\": ") + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t(",\n") + string_t("  \"exit_code\": ") + cast<string_t>(cast<int_t<>>(row->exit_code)) + string_t(",\n") + string_t("  \"stdout\": ") + cast<string_t>(json::encode(__latency_fn_resident_daemon_file_ipc_text_by_id(artifact, row->stdout_text_id))) + string_t(",\n") + string_t("  \"stderr\": ") + cast<string_t>(json::encode(__latency_fn_resident_daemon_file_ipc_text_by_id(artifact, row->stderr_text_id))) + string_t(",\n") + string_t("  \"summary\": ") + cast<string_t>(json::encode(__latency_fn_resident_daemon_file_ipc_text_by_id(artifact, row->summary_text_id))) + string_t(",\n") + string_t("  \"elapsed_us\": ") + cast<string_t>(cast<int_t<>>(row->elapsed_us)) + string_t("\n") + string_t("}\n"));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_session_json(shared_p<ResidentDaemonIpcArtifact> artifact, ResidentDaemonSessionRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::session_json", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[62]);
	return (string_t("{\n  \"schema_version\": 1,\n  \"transport\": \"file_ipc\",\n  \"session_id\": ") + cast<string_t>(cast<int_t<>>(row->session_id)) + string_t(",\n") + string_t("  \"session_generation\": ") + cast<string_t>(cast<int_t<>>(row->generation)) + string_t(",\n") + string_t("  \"pid\": ") + cast<string_t>(cast<int_t<>>(row->pid)) + string_t(",\n") + string_t("  \"status_id\": ") + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t(",\n") + string_t("  \"project_root\": ") + cast<string_t>(json::encode(__latency_fn_resident_daemon_file_ipc_path_by_id(artifact, row->project_root_path_id))) + string_t(",\n") + string_t("  \"session_path\": ") + cast<string_t>(json::encode(__latency_fn_resident_daemon_file_ipc_path_by_id(artifact, row->session_path_id))) + string_t(",\n") + string_t("  \"request_dir\": ") + cast<string_t>(json::encode(__latency_fn_resident_daemon_file_ipc_path_by_id(artifact, row->request_dir_path_id))) + string_t(",\n") + string_t("  \"response_dir\": ") + cast<string_t>(json::encode(__latency_fn_resident_daemon_file_ipc_path_by_id(artifact, row->response_dir_path_id))) + string_t(",\n") + string_t("  \"heartbeat_age_ms\": ") + cast<string_t>(cast<int_t<>>(row->heartbeat_age_ms)) + string_t(",\n") + string_t("  \"idle_timeout_ms\": ") + cast<string_t>(cast<int_t<>>(row->idle_timeout_ms)) + string_t("\n") + string_t("}\n"));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
bool_t __latency_fn_resident_daemon_file_ipc_write_text_file(const string_t& path, const string_t& text) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::write_text_file", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[63]);
	int_t<> written = required_cast<int_t<>>(static_cast<int_t<> >(0));
	error_t err;
	if (static_cast<bool>(php::condition_truthy(php::take(written, err, fs::put(path, text))))) {
		return bool_t(static_cast<bool_t>(true));
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
bool_t __latency_fn_resident_daemon_file_ipc_write_request_json(shared_p<ResidentDaemonIpcArtifact> artifact, ResidentDaemonRequestRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::write_request_json", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[64]);
	string_t path = required_cast<string_t>(__latency_fn_resident_daemon_file_ipc_path_by_id(artifact, row->request_path_id));
	if (static_cast<bool>(php::identical(path, string_t("")))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return __latency_fn_resident_daemon_file_ipc_write_text_file(path, __latency_fn_resident_daemon_file_ipc_request_json(artifact, row));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
bool_t __latency_fn_resident_daemon_file_ipc_write_response_json(shared_p<ResidentDaemonIpcArtifact> artifact, ResidentDaemonResponseRow row, ResidentDaemonRequestRow request) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::write_response_json", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[65]);
	string_t path = required_cast<string_t>(__latency_fn_resident_daemon_file_ipc_path_by_id(artifact, request->response_path_id));
	if (static_cast<bool>(php::identical(path, string_t("")))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return __latency_fn_resident_daemon_file_ipc_write_text_file(path, __latency_fn_resident_daemon_file_ipc_response_json(artifact, row));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
bool_t __latency_fn_resident_daemon_file_ipc_write_session_json(shared_p<ResidentDaemonIpcArtifact> artifact, ResidentDaemonSessionRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::write_session_json", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[66]);
	string_t path = required_cast<string_t>(__latency_fn_resident_daemon_file_ipc_path_by_id(artifact, row->session_path_id));
	if (static_cast<bool>(php::identical(path, string_t("")))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return __latency_fn_resident_daemon_file_ipc_write_text_file(path, __latency_fn_resident_daemon_file_ipc_session_json(artifact, row));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_daemon_file_ipc_classify_session_status(int_t<std::uint32_t> heartbeatAgeMs, int_t<std::uint32_t> idleTimeoutMs) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::classify_session_status", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[67]);
	if (static_cast<bool>((cast<int_t<>>(heartbeatAgeMs) > cast<int_t<>>(idleTimeoutMs)))) {
		return __latency_fn_resident_daemon_file_ipc_session_status_stale_id();
	}
	return __latency_fn_resident_daemon_file_ipc_session_status_active_id();
}

}

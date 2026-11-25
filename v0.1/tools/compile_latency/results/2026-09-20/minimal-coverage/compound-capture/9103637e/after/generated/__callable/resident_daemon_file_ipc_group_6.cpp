#include <scpp/lang/php.hpp>
#include "__types/ResidentDaemonIpcArtifact.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_client_action_for_session_status.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_client_action_forward_request_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_client_action_start_local_resident_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_session_status_active_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_request.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_read_request_json.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_response.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_read_response_json.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_session.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_classify_session_status.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_read_session_json.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_session.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_classify_session_file.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_idle_timeout_ms.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_read_session_json.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_request_dir_path.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_response_dir_path.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_session_file_path.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_session_status_missing_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_pack_source_slot_event.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_multiplier.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_max_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_multiplier.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_multiplier.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_slot_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_daemon_file_ipc_client_action_for_session_status(int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::client_action_for_session_status", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[68]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_session_status_active_id())))) {
		return __latency_fn_resident_daemon_file_ipc_client_action_forward_request_id();
	}
	return __latency_fn_resident_daemon_file_ipc_client_action_start_local_resident_id();
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_read_request_json(shared_p<ResidentDaemonIpcArtifact>& artifact, const string_t& path) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::read_request_json", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[69]);
	string_t text = required_cast<string_t>(string_t(""));
	error_t err;
	if (static_cast<bool>((!php::take(text, err, fs::get(path))))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	auto decoded = json::decode(text);
	if (static_cast<bool>((((((((((!php::isset(decoded, string_t("project_root"))) || (!php::isset(decoded, string_t("request_path")))) || (!php::isset(decoded, string_t("response_path")))) || (!php::isset(decoded, string_t("session_id")))) || (!php::isset(decoded, string_t("created_age_ms")))) || (!php::isset(decoded, string_t("source_slot_event_first_id")))) || (!php::isset(decoded, string_t("source_slot_event_count")))) || (!php::isset(decoded, string_t("command_kind_id")))) || (!php::isset(decoded, string_t("status_id")))))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	int_t<> sessionId = required_cast<int_t<>>(decoded[string_t("session_id")]);
	int_t<> createdAgeMs = required_cast<int_t<>>(decoded[string_t("created_age_ms")]);
	int_t<> eventFirstId = required_cast<int_t<>>(decoded[string_t("source_slot_event_first_id")]);
	int_t<> eventCount = required_cast<int_t<>>(decoded[string_t("source_slot_event_count")]);
	int_t<> commandKindId = required_cast<int_t<>>(decoded[string_t("command_kind_id")]);
	int_t<> statusId = required_cast<int_t<>>(decoded[string_t("status_id")]);
	string_t projectRoot = required_cast<string_t>(decoded[string_t("project_root")]);
	string_t requestPath = required_cast<string_t>(decoded[string_t("request_path")]);
	string_t responsePath = required_cast<string_t>(decoded[string_t("response_path")]);
	return __latency_fn_resident_daemon_file_ipc_append_request(artifact, __latency_fn_structure_row_ids_uint32_from_int(sessionId), projectRoot, requestPath, responsePath, __latency_fn_structure_row_ids_uint32_from_int(createdAgeMs), __latency_fn_structure_row_ids_uint32_from_int(eventFirstId), __latency_fn_structure_row_ids_uint32_from_int(eventCount), __latency_fn_structure_row_ids_uint16_from_int(commandKindId), __latency_fn_structure_row_ids_uint16_from_int(statusId));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_read_response_json(shared_p<ResidentDaemonIpcArtifact>& artifact, const string_t& path) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::read_response_json", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[70]);
	string_t text = required_cast<string_t>(string_t(""));
	error_t err;
	if (static_cast<bool>((!php::take(text, err, fs::get(path))))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	auto decoded = json::decode(text);
	if (static_cast<bool>((((((((!php::isset(decoded, string_t("request_id"))) || (!php::isset(decoded, string_t("status_id")))) || (!php::isset(decoded, string_t("exit_code")))) || (!php::isset(decoded, string_t("stdout")))) || (!php::isset(decoded, string_t("stderr")))) || (!php::isset(decoded, string_t("summary")))) || (!php::isset(decoded, string_t("elapsed_us")))))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	int_t<> requestId = required_cast<int_t<>>(decoded[string_t("request_id")]);
	int_t<> statusId = required_cast<int_t<>>(decoded[string_t("status_id")]);
	int_t<> exitCode = required_cast<int_t<>>(decoded[string_t("exit_code")]);
	string_t stdoutText = required_cast<string_t>(decoded[string_t("stdout")]);
	string_t stderrText = required_cast<string_t>(decoded[string_t("stderr")]);
	string_t summaryText = required_cast<string_t>(decoded[string_t("summary")]);
	int_t<> elapsedUs = required_cast<int_t<>>(decoded[string_t("elapsed_us")]);
	return __latency_fn_resident_daemon_file_ipc_append_response(artifact, __latency_fn_structure_row_ids_uint32_from_int(requestId), stdoutText, stderrText, summaryText, __latency_fn_structure_row_ids_uint32_from_int(elapsedUs), __latency_fn_structure_row_ids_uint16_from_int(statusId), __latency_fn_structure_row_ids_uint16_from_int(exitCode));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_read_session_json(shared_p<ResidentDaemonIpcArtifact>& artifact, const string_t& path) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::read_session_json", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[71]);
	string_t text = required_cast<string_t>(string_t(""));
	error_t err;
	if (static_cast<bool>((!php::take(text, err, fs::get(path))))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	auto decoded = json::decode(text);
	if (static_cast<bool>(((((((((!php::isset(decoded, string_t("project_root"))) || (!php::isset(decoded, string_t("session_path")))) || (!php::isset(decoded, string_t("request_dir")))) || (!php::isset(decoded, string_t("response_dir")))) || (!php::isset(decoded, string_t("pid")))) || (!php::isset(decoded, string_t("session_generation")))) || (!php::isset(decoded, string_t("heartbeat_age_ms")))) || (!php::isset(decoded, string_t("idle_timeout_ms")))))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	string_t projectRoot = required_cast<string_t>(decoded[string_t("project_root")]);
	string_t sessionPath = required_cast<string_t>(decoded[string_t("session_path")]);
	string_t requestDir = required_cast<string_t>(decoded[string_t("request_dir")]);
	string_t responseDir = required_cast<string_t>(decoded[string_t("response_dir")]);
	int_t<> pid = required_cast<int_t<>>(decoded[string_t("pid")]);
	int_t<> generation = required_cast<int_t<>>(decoded[string_t("session_generation")]);
	int_t<> heartbeatAgeMs = required_cast<int_t<>>(decoded[string_t("heartbeat_age_ms")]);
	int_t<> idleTimeoutMs = required_cast<int_t<>>(decoded[string_t("idle_timeout_ms")]);
	return __latency_fn_resident_daemon_file_ipc_append_session(artifact, projectRoot, sessionPath, requestDir, responseDir, __latency_fn_structure_row_ids_uint32_from_int(pid), __latency_fn_structure_row_ids_uint32_from_int(generation), __latency_fn_structure_row_ids_uint32_from_int(heartbeatAgeMs), __latency_fn_resident_daemon_file_ipc_classify_session_status(__latency_fn_structure_row_ids_uint32_from_int(heartbeatAgeMs), __latency_fn_structure_row_ids_uint32_from_int(idleTimeoutMs)));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_classify_session_file(shared_p<ResidentDaemonIpcArtifact>& artifact, const string_t& projectRoot) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::classify_session_file", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[72]);
	string_t sessionPath = required_cast<string_t>(__latency_fn_resident_daemon_file_ipc_session_file_path(projectRoot));
	if (static_cast<bool>((!fs::exists(sessionPath)))) {
		return __latency_fn_resident_daemon_file_ipc_append_session(artifact, projectRoot, sessionPath, __latency_fn_resident_daemon_file_ipc_request_dir_path(projectRoot), __latency_fn_resident_daemon_file_ipc_response_dir_path(projectRoot), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_resident_daemon_file_ipc_idle_timeout_ms(), __latency_fn_resident_daemon_file_ipc_session_status_missing_id());
	}
	return __latency_fn_resident_daemon_file_ipc_read_session_json(artifact, sessionPath);
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_pack_source_slot_event(int_t<std::uint16_t> eventKindId, int_t<std::uint32_t> sourceSlotId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::pack_source_slot_event", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[73]);
	if (static_cast<bool>(((cast<int_t<>>(eventKindId) <= static_cast<int_t<> >(0)) || (cast<int_t<>>(eventKindId) > static_cast<int_t<> >(255))))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	if (static_cast<bool>(((cast<int_t<>>(sourceSlotId) <= static_cast<int_t<> >(0)) || (cast<int_t<>>(sourceSlotId) > cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_source_slot_max_id()))))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	int_t<> packed = required_cast<int_t<>>(((cast<int_t<>>(eventKindId) * cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_multiplier())) + cast<int_t<>>(sourceSlotId)));
	return __latency_fn_structure_row_ids_uint32_from_int(packed);
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_daemon_file_ipc_source_slot_event_kind_id(int_t<std::uint32_t> packedEvent) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::source_slot_event_kind_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[74]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(packedEvent), static_cast<int_t<> >(0)))) {
		return __latency_fn_structure_row_ids_none_kind_id();
	}
	int_t<> remaining = required_cast<int_t<>>(cast<int_t<>>(packedEvent));
	int_t<> multiplier = required_cast<int_t<>>(cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_multiplier()));
	int_t<> kind = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>(((remaining >= multiplier) && (kind < static_cast<int_t<> >(255))))) {
		remaining = (remaining - multiplier);
		kind = (kind + static_cast<int_t<> >(1));
	}
	return __latency_fn_structure_row_ids_uint16_from_int(kind);
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_source_slot_event_slot_id(int_t<std::uint32_t> packedEvent) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::source_slot_event_slot_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[75]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(packedEvent), static_cast<int_t<> >(0)))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	int_t<std::uint16_t> kind = required_cast<int_t<std::uint16_t>>(__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_id(cast<int_t<std::uint32_t>>(packedEvent)));
	int_t<> slot = required_cast<int_t<>>((cast<int_t<>>(packedEvent) - (cast<int_t<>>(kind) * cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_multiplier()))));
	return __latency_fn_structure_row_ids_uint32_from_int(slot);
}

}

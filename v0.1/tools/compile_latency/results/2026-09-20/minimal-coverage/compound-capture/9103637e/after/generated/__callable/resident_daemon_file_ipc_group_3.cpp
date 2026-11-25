#include <scpp/lang/php.hpp>
#include "__types/ResidentDaemonIpcArtifact.hpp"
#include "__types/ResidentDaemonRequestRow.hpp"
#include "__types/ResidentDaemonResponseRow.hpp"
#include "__types/ResidentDaemonSessionRow.hpp"
#include "__types/ResidentDaemonSourceSlotEventRow.hpp"
#include "__types/ResidentDaemonSourceSlotStatRow.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_session.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_idle_timeout_ms.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_intern_path.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_transport_file_ipc_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_request.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_intern_path.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_response.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_intern_text.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_source_slot_event.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_coalesced_source_slot_event.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_source_slot_event.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_slot_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_status_queued_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_source_slot_stat.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_intern_path.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_source_slot_content_stat.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_intern_path.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_added_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_changed_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_deleted_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_for_scan_status.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_scan_added_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_scan_changed_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_scan_deleted_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_append_session(shared_p<ResidentDaemonIpcArtifact>& artifact, const string_t& projectRoot, const string_t& sessionPath, const string_t& requestDir, const string_t& responseDir, int_t<std::uint32_t> pid, int_t<std::uint32_t> generation, int_t<std::uint32_t> heartbeatAgeMs, int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::append_session", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[45]);
	ResidentDaemonSessionRow row = ResidentDaemonSessionRow{};
	row->session_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->sessions));
	row->project_root_path_id = __latency_fn_resident_daemon_file_ipc_intern_path(artifact, projectRoot);
	row->session_path_id = __latency_fn_resident_daemon_file_ipc_intern_path(artifact, sessionPath);
	row->request_dir_path_id = __latency_fn_resident_daemon_file_ipc_intern_path(artifact, requestDir);
	row->response_dir_path_id = __latency_fn_resident_daemon_file_ipc_intern_path(artifact, responseDir);
	row->pid = pid;
	row->generation = generation;
	row->heartbeat_age_ms = heartbeatAgeMs;
	row->idle_timeout_ms = __latency_fn_resident_daemon_file_ipc_idle_timeout_ms();
	row->transport_id = __latency_fn_resident_daemon_file_ipc_transport_file_ipc_id();
	row->status_id = statusId;
	(void) artifact->sessions.append(row);
	artifact->session_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->sessions));
	return row->session_id;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_append_request(shared_p<ResidentDaemonIpcArtifact>& artifact, int_t<std::uint32_t> sessionId, const string_t& projectRoot, const string_t& requestPath, const string_t& responsePath, int_t<std::uint32_t> createdAgeMs, int_t<std::uint32_t> eventFirstId, int_t<std::uint32_t> eventCount, int_t<std::uint16_t> commandKindId, int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::append_request", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[46]);
	ResidentDaemonRequestRow row = ResidentDaemonRequestRow{};
	row->request_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->requests));
	row->session_id = sessionId;
	row->project_root_path_id = __latency_fn_resident_daemon_file_ipc_intern_path(artifact, projectRoot);
	row->request_path_id = __latency_fn_resident_daemon_file_ipc_intern_path(artifact, requestPath);
	row->response_path_id = __latency_fn_resident_daemon_file_ipc_intern_path(artifact, responsePath);
	row->created_age_ms = createdAgeMs;
	row->source_slot_event_first_id = eventFirstId;
	row->source_slot_event_count = eventCount;
	row->command_kind_id = commandKindId;
	row->status_id = statusId;
	(void) artifact->requests.append(row);
	artifact->request_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->requests));
	return row->request_id;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_append_response(shared_p<ResidentDaemonIpcArtifact>& artifact, int_t<std::uint32_t> requestId, const string_t& stdoutText, const string_t& stderrText, const string_t& summaryText, int_t<std::uint32_t> elapsedUs, int_t<std::uint16_t> statusId, int_t<std::uint16_t> exitCode) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::append_response", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[47]);
	ResidentDaemonResponseRow row = ResidentDaemonResponseRow{};
	row->response_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->responses));
	row->request_id = requestId;
	row->stdout_text_id = __latency_fn_resident_daemon_file_ipc_intern_text(artifact, stdoutText);
	row->stderr_text_id = __latency_fn_resident_daemon_file_ipc_intern_text(artifact, stderrText);
	row->summary_text_id = __latency_fn_resident_daemon_file_ipc_intern_text(artifact, summaryText);
	row->elapsed_us = elapsedUs;
	row->status_id = statusId;
	row->exit_code = exitCode;
	(void) artifact->responses.append(row);
	artifact->response_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->responses));
	return row->response_id;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_append_source_slot_event(shared_p<ResidentDaemonIpcArtifact>& artifact, int_t<std::uint32_t> requestId, int_t<std::uint32_t> packedEvent, int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::append_source_slot_event", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[48]);
	ResidentDaemonSourceSlotEventRow row = ResidentDaemonSourceSlotEventRow{};
	row->event_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->source_slot_events));
	row->request_id = requestId;
	row->packed_event = packedEvent;
	row->status_id = statusId;
	(void) artifact->source_slot_events.append(row);
	artifact->source_slot_event_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->source_slot_events));
	return row->event_id;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_append_coalesced_source_slot_event(shared_p<ResidentDaemonIpcArtifact>& artifact, int_t<std::uint32_t> requestId, int_t<std::uint32_t> packedEvent) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::append_coalesced_source_slot_event", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[49]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(packedEvent), static_cast<int_t<> >(0)))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	int_t<std::uint32_t> sourceSlotId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_daemon_file_ipc_source_slot_event_slot_id(cast<int_t<std::uint32_t>>(packedEvent)));
	auto __latency_local_0 = artifact->source_slot_events;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->request_id), cast<int_t<>>(requestId)) && php::identical(cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_source_slot_event_slot_id(row->packed_event)), cast<int_t<>>(sourceSlotId))))) {
			return row->event_id;
		}
	}
	return __latency_fn_resident_daemon_file_ipc_append_source_slot_event(artifact, cast<int_t<std::uint32_t>>(requestId), cast<int_t<std::uint32_t>>(packedEvent), __latency_fn_resident_daemon_file_ipc_source_slot_event_status_queued_id());
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_append_source_slot_stat(shared_p<ResidentDaemonIpcArtifact>& artifact, int_t<std::uint32_t> requestId, int_t<std::uint32_t> sourceSlotId, const string_t& path, int_t<std::uint32_t> previousSize, int_t<std::uint32_t> currentSize, int_t<std::uint32_t> previousMtimeTick, int_t<std::uint32_t> currentMtimeTick, int_t<std::uint16_t> scanStatusId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::append_source_slot_stat", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[50]);
	ResidentDaemonSourceSlotStatRow row = ResidentDaemonSourceSlotStatRow{};
	row->stat_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->source_slot_stats));
	row->request_id = requestId;
	row->source_slot_id = sourceSlotId;
	row->path_id = __latency_fn_resident_daemon_file_ipc_intern_path(artifact, path);
	row->previous_size = previousSize;
	row->current_size = currentSize;
	row->previous_mtime_tick = previousMtimeTick;
	row->current_mtime_tick = currentMtimeTick;
	row->scan_status_id = scanStatusId;
	(void) artifact->source_slot_stats.append(row);
	artifact->source_slot_stat_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->source_slot_stats));
	return row->stat_id;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_append_source_slot_content_stat(shared_p<ResidentDaemonIpcArtifact>& artifact, int_t<std::uint32_t> requestId, int_t<std::uint32_t> sourceSlotId, const string_t& path, int_t<std::uint32_t> previousSize, int_t<std::uint32_t> currentSize, int_t<std::uint32_t> previousContentHash, int_t<std::uint32_t> currentContentHash, int_t<std::uint16_t> scanStatusId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::append_source_slot_content_stat", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[51]);
	ResidentDaemonSourceSlotStatRow row = ResidentDaemonSourceSlotStatRow{};
	row->stat_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->source_slot_stats));
	row->request_id = requestId;
	row->source_slot_id = sourceSlotId;
	row->path_id = __latency_fn_resident_daemon_file_ipc_intern_path(artifact, path);
	row->previous_size = previousSize;
	row->current_size = currentSize;
	row->previous_content_hash = previousContentHash;
	row->current_content_hash = currentContentHash;
	row->previous_mtime_tick = __latency_fn_structure_row_ids_none_id();
	row->current_mtime_tick = __latency_fn_structure_row_ids_none_id();
	row->scan_status_id = scanStatusId;
	(void) artifact->source_slot_stats.append(row);
	artifact->source_slot_stat_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->source_slot_stats));
	return row->stat_id;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_daemon_file_ipc_source_slot_event_kind_for_scan_status(int_t<std::uint16_t> scanStatusId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::source_slot_event_kind_for_scan_status", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[52]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(scanStatusId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_source_slot_scan_changed_id())))) {
		return __latency_fn_resident_daemon_file_ipc_source_slot_event_kind_changed_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(scanStatusId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_source_slot_scan_added_id())))) {
		return __latency_fn_resident_daemon_file_ipc_source_slot_event_kind_added_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(scanStatusId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_source_slot_scan_deleted_id())))) {
		return __latency_fn_resident_daemon_file_ipc_source_slot_event_kind_deleted_id();
	}
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

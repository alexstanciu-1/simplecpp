#include <scpp/lang/php.hpp>
#include "__types/ResidentDaemonDirtySourceProjectionRow.hpp"
#include "__types/ResidentDaemonIpcArtifact.hpp"
#include "__types/ResidentDaemonRequestRow.hpp"
#include "__types/ResidentDaemonSourceSlotEventRow.hpp"
#include "__types/ResidentDaemonSourceSlotScanRow.hpp"
#include "__types/ResidentDaemonSourceSlotStatRow.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_coalesced_source_slot_event.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_source_slot_event_for_stat.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_pack_source_slot_event.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_for_scan_status.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_source_slot_scan.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_dirty_source_projection_from_scan.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_dirty_projection_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_by_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_added_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_changed_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_deleted_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_update_dirty_source_projection_transaction.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_update_request_event_window.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_mark_request_completed.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_request_status_completed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_append_source_slot_event_for_stat(shared_p<ResidentDaemonIpcArtifact>& artifact, ResidentDaemonSourceSlotStatRow stat) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::append_source_slot_event_for_stat", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[53]);
	int_t<std::uint16_t> eventKindId = required_cast<int_t<std::uint16_t>>(__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_for_scan_status(stat->scan_status_id));
	if (static_cast<bool>(php::identical(cast<int_t<>>(eventKindId), static_cast<int_t<> >(0)))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	int_t<std::uint32_t> packedEvent = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_daemon_file_ipc_pack_source_slot_event(cast<int_t<std::uint16_t>>(eventKindId), stat->source_slot_id));
	return __latency_fn_resident_daemon_file_ipc_append_coalesced_source_slot_event(artifact, stat->request_id, cast<int_t<std::uint32_t>>(packedEvent));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_append_source_slot_scan(shared_p<ResidentDaemonIpcArtifact>& artifact, int_t<std::uint32_t> requestId, int_t<std::uint32_t> statFirstId, int_t<std::uint32_t> statCount, int_t<std::uint32_t> candidateEventCount, int_t<std::uint32_t> eventFirstId, int_t<std::uint32_t> eventCount, int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::append_source_slot_scan", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[54]);
	ResidentDaemonSourceSlotScanRow row = ResidentDaemonSourceSlotScanRow{};
	row->scan_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->source_slot_scans));
	row->request_id = requestId;
	row->source_slot_stat_first_id = statFirstId;
	row->source_slot_stat_count = statCount;
	row->candidate_event_count = candidateEventCount;
	row->event_first_id = eventFirstId;
	row->event_count = eventCount;
	row->status_id = statusId;
	(void) artifact->source_slot_scans.append(row);
	artifact->source_slot_scan_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->source_slot_scans));
	return row->scan_id;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_append_dirty_source_projection_from_scan(shared_p<ResidentDaemonIpcArtifact>& artifact, ResidentDaemonRequestRow request, ResidentDaemonSourceSlotScanRow scan, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> transactionId, int_t<std::uint16_t> executionModeId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::append_dirty_source_projection_from_scan", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[55]);
	int_t<> changedCount = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> addedCount = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> deletedCount = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> eventIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((eventIndex < cast<int_t<>>(scan->event_count)))) {
		int_t<std::uint32_t> eventId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(scan->event_first_id) + eventIndex)));
		ResidentDaemonSourceSlotEventRow event = __latency_fn_resident_daemon_file_ipc_source_slot_event_by_id(artifact, cast<int_t<std::uint32_t>>(eventId));
		int_t<std::uint16_t> kindId = required_cast<int_t<std::uint16_t>>(__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_id(event->packed_event));
		if (static_cast<bool>(php::identical(cast<int_t<>>(kindId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_changed_id())))) {
			changedCount = (changedCount + static_cast<int_t<> >(1));
		}
		else {
			if (static_cast<bool>(php::identical(cast<int_t<>>(kindId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_added_id())))) {
				addedCount = (addedCount + static_cast<int_t<> >(1));
			}
			else {
				if (static_cast<bool>(php::identical(cast<int_t<>>(kindId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_source_slot_event_kind_deleted_id())))) {
					deletedCount = (deletedCount + static_cast<int_t<> >(1));
				}
			}
		}
		eventIndex = (eventIndex + static_cast<int_t<> >(1));
	}
	int_t<> dirtyCount = required_cast<int_t<>>(((changedCount + addedCount) + deletedCount));
	int_t<> reusedCount = required_cast<int_t<>>((cast<int_t<>>(scan->source_slot_stat_count) - dirtyCount));
	if (static_cast<bool>((reusedCount < static_cast<int_t<> >(0)))) {
		reusedCount = static_cast<int_t<> >(0);
	}
	ResidentDaemonDirtySourceProjectionRow row = ResidentDaemonDirtySourceProjectionRow{};
	row->projection_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->dirty_source_projections));
	row->request_id = request->request_id;
	row->scan_id = scan->scan_id;
	row->owner_run_id = ownerRunId;
	row->transaction_id = transactionId;
	row->source_slot_event_first_id = scan->event_first_id;
	row->source_slot_event_count = scan->event_count;
	row->input_source_count = scan->source_slot_stat_count;
	row->dirty_source_count = __latency_fn_structure_row_ids_uint32_from_int(dirtyCount);
	row->reused_source_count = __latency_fn_structure_row_ids_uint32_from_int(reusedCount);
	row->changed_source_count = __latency_fn_structure_row_ids_uint32_from_int(changedCount);
	row->added_source_count = __latency_fn_structure_row_ids_uint32_from_int(addedCount);
	row->deleted_source_count = __latency_fn_structure_row_ids_uint32_from_int(deletedCount);
	row->parse_candidate_count = __latency_fn_structure_row_ids_uint32_from_int((changedCount + addedCount));
	row->execution_mode_id = executionModeId;
	row->status_id = __latency_fn_resident_daemon_file_ipc_dirty_projection_status_ready_id();
	(void) artifact->dirty_source_projections.append(row);
	artifact->dirty_source_projection_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->dirty_source_projections));
	return row->projection_id;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
bool_t __latency_fn_resident_daemon_file_ipc_update_dirty_source_projection_transaction(shared_p<ResidentDaemonIpcArtifact>& artifact, int_t<std::uint32_t> projectionId, int_t<std::uint32_t> transactionId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::update_dirty_source_projection_transaction", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[56]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(projectionId, cast<int_t<>>(artifact->dirty_source_projection_count))))) {
		int_t<> index = required_cast<int_t<>>(__latency_fn_structure_row_ids_dense_index(projectionId));
		ResidentDaemonDirtySourceProjectionRow row = artifact->dirty_source_projections[index];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->projection_id), cast<int_t<>>(projectionId)))) {
			row->transaction_id = transactionId;
			artifact->dirty_source_projections[index] = row;
			return bool_t(static_cast<bool_t>(true));
		}
	}
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = artifact->dirty_source_projections;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->projection_id), cast<int_t<>>(projectionId)))) {
			row->transaction_id = transactionId;
			artifact->dirty_source_projections[index] = row;
			return bool_t(static_cast<bool_t>(true));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
bool_t __latency_fn_resident_daemon_file_ipc_update_request_event_window(shared_p<ResidentDaemonIpcArtifact>& artifact, int_t<std::uint32_t> requestId, int_t<std::uint32_t> eventFirstId, int_t<std::uint32_t> eventCount) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::update_request_event_window", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[57]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(requestId, cast<int_t<>>(artifact->request_count))))) {
		int_t<> index = required_cast<int_t<>>(__latency_fn_structure_row_ids_dense_index(requestId));
		ResidentDaemonRequestRow row = artifact->requests[index];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->request_id), cast<int_t<>>(requestId)))) {
			row->source_slot_event_first_id = eventFirstId;
			row->source_slot_event_count = eventCount;
			artifact->requests[index] = row;
			return bool_t(static_cast<bool_t>(true));
		}
	}
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = artifact->requests;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->request_id), cast<int_t<>>(requestId)))) {
			row->source_slot_event_first_id = eventFirstId;
			row->source_slot_event_count = eventCount;
			artifact->requests[index] = row;
			return bool_t(static_cast<bool_t>(true));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
bool_t __latency_fn_resident_daemon_file_ipc_mark_request_completed(shared_p<ResidentDaemonIpcArtifact>& artifact, int_t<std::uint32_t> requestId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::mark_request_completed", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[58]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(requestId, cast<int_t<>>(artifact->request_count))))) {
		int_t<> index = required_cast<int_t<>>(__latency_fn_structure_row_ids_dense_index(requestId));
		ResidentDaemonRequestRow row = artifact->requests[index];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->request_id), cast<int_t<>>(requestId)))) {
			row->status_id = __latency_fn_resident_daemon_file_ipc_request_status_completed_id();
			artifact->requests[index] = row;
			return bool_t(static_cast<bool_t>(true));
		}
	}
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = artifact->requests;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->request_id), cast<int_t<>>(requestId)))) {
			row->status_id = __latency_fn_resident_daemon_file_ipc_request_status_completed_id();
			artifact->requests[index] = row;
			return bool_t(static_cast<bool_t>(true));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return bool_t(static_cast<bool_t>(false));
}

}

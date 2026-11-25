#include <scpp/lang/php.hpp>
#include "__types/ResidentDaemonDirtySourceProjectionRow.hpp"
#include "__types/ResidentDaemonIpcArtifact.hpp"
#include "__types/ResidentDaemonLoopPolicyRow.hpp"
#include "__types/ResidentDaemonRequestRow.hpp"
#include "__types/ResidentDaemonResponseRow.hpp"
#include "__types/ResidentDaemonSessionRow.hpp"
#include "__types/ResidentDaemonSourceSlotScanRow.hpp"
#include "__types/ResidentDaemonSourceSlotStatRow.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_stat_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_scan_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_dirty_source_projection_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_loop_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_session_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_request_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_response_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
ResidentDaemonSourceSlotStatRow __latency_fn_resident_daemon_file_ipc_source_slot_stat_by_id(shared_p<ResidentDaemonIpcArtifact> artifact, int_t<std::uint32_t> statId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::source_slot_stat_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[76]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(statId, cast<int_t<>>(artifact->source_slot_stat_count))))) {
		ResidentDaemonSourceSlotStatRow row = artifact->source_slot_stats[__latency_fn_structure_row_ids_dense_index(statId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->stat_id), cast<int_t<>>(statId)))) {
			return row;
		}
	}
	auto __latency_local_0 = artifact->source_slot_stats;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->stat_id), cast<int_t<>>(statId)))) {
			return row;
		}
	}
	ResidentDaemonSourceSlotStatRow empty = ResidentDaemonSourceSlotStatRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
ResidentDaemonSourceSlotScanRow __latency_fn_resident_daemon_file_ipc_source_slot_scan_by_id(shared_p<ResidentDaemonIpcArtifact> artifact, int_t<std::uint32_t> scanId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::source_slot_scan_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[77]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(scanId, cast<int_t<>>(artifact->source_slot_scan_count))))) {
		ResidentDaemonSourceSlotScanRow row = artifact->source_slot_scans[__latency_fn_structure_row_ids_dense_index(scanId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->scan_id), cast<int_t<>>(scanId)))) {
			return row;
		}
	}
	auto __latency_local_0 = artifact->source_slot_scans;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->scan_id), cast<int_t<>>(scanId)))) {
			return row;
		}
	}
	ResidentDaemonSourceSlotScanRow empty = ResidentDaemonSourceSlotScanRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
ResidentDaemonDirtySourceProjectionRow __latency_fn_resident_daemon_file_ipc_dirty_source_projection_by_id(shared_p<ResidentDaemonIpcArtifact> artifact, int_t<std::uint32_t> projectionId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::dirty_source_projection_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[78]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(projectionId, cast<int_t<>>(artifact->dirty_source_projection_count))))) {
		ResidentDaemonDirtySourceProjectionRow row = artifact->dirty_source_projections[__latency_fn_structure_row_ids_dense_index(projectionId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->projection_id), cast<int_t<>>(projectionId)))) {
			return row;
		}
	}
	auto __latency_local_0 = artifact->dirty_source_projections;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->projection_id), cast<int_t<>>(projectionId)))) {
			return row;
		}
	}
	ResidentDaemonDirtySourceProjectionRow empty = ResidentDaemonDirtySourceProjectionRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
ResidentDaemonLoopPolicyRow __latency_fn_resident_daemon_file_ipc_loop_by_id(shared_p<ResidentDaemonIpcArtifact> artifact, int_t<std::uint32_t> loopId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::loop_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[79]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(loopId, cast<int_t<>>(artifact->loop_count))))) {
		ResidentDaemonLoopPolicyRow row = artifact->loops[__latency_fn_structure_row_ids_dense_index(loopId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->loop_id), cast<int_t<>>(loopId)))) {
			return row;
		}
	}
	auto __latency_local_0 = artifact->loops;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->loop_id), cast<int_t<>>(loopId)))) {
			return row;
		}
	}
	ResidentDaemonLoopPolicyRow empty = ResidentDaemonLoopPolicyRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
ResidentDaemonSessionRow __latency_fn_resident_daemon_file_ipc_session_by_id(shared_p<ResidentDaemonIpcArtifact> artifact, int_t<std::uint32_t> sessionId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::session_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[80]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sessionId, cast<int_t<>>(artifact->session_count))))) {
		ResidentDaemonSessionRow row = artifact->sessions[__latency_fn_structure_row_ids_dense_index(sessionId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->session_id), cast<int_t<>>(sessionId)))) {
			return row;
		}
	}
	auto __latency_local_0 = artifact->sessions;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->session_id), cast<int_t<>>(sessionId)))) {
			return row;
		}
	}
	ResidentDaemonSessionRow empty = ResidentDaemonSessionRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
ResidentDaemonRequestRow __latency_fn_resident_daemon_file_ipc_request_by_id(shared_p<ResidentDaemonIpcArtifact> artifact, int_t<std::uint32_t> requestId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::request_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[81]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(requestId, cast<int_t<>>(artifact->request_count))))) {
		ResidentDaemonRequestRow row = artifact->requests[__latency_fn_structure_row_ids_dense_index(requestId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->request_id), cast<int_t<>>(requestId)))) {
			return row;
		}
	}
	auto __latency_local_0 = artifact->requests;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->request_id), cast<int_t<>>(requestId)))) {
			return row;
		}
	}
	ResidentDaemonRequestRow empty = ResidentDaemonRequestRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
ResidentDaemonResponseRow __latency_fn_resident_daemon_file_ipc_response_by_id(shared_p<ResidentDaemonIpcArtifact> artifact, int_t<std::uint32_t> responseId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::response_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[82]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(responseId, cast<int_t<>>(artifact->response_count))))) {
		ResidentDaemonResponseRow row = artifact->responses[__latency_fn_structure_row_ids_dense_index(responseId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->response_id), cast<int_t<>>(responseId)))) {
			return row;
		}
	}
	auto __latency_local_0 = artifact->responses;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->response_id), cast<int_t<>>(responseId)))) {
			return row;
		}
	}
	ResidentDaemonResponseRow empty = ResidentDaemonResponseRow{};
	return empty;
}

}

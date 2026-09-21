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
#include "__callable/__latency_fn_resident_daemon_file_ipc_artifact_kind_file_ipc_daemon_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_new_artifact.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_transport_file_ipc_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_ipc_root_path.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_ipc_root_path.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_session_file_path.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_ipc_root_path.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_request_dir_path.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_ipc_root_path.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_response_dir_path.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_request_dir_path.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_request_file_path.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_response_dir_path.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_response_file_path.hpp"
#include "__callable/__latency_fn_output_paths_ensure_dir.hpp"
#include "__callable/__latency_fn_output_paths_ensure_parent_and_dir.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_ensure_ipc_dirs.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_ipc_root_path.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_request_dir_path.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_response_dir_path.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_intern_path.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_intern_text.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_path_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_text_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
shared_p<ResidentDaemonIpcArtifact> __latency_fn_resident_daemon_file_ipc_new_artifact(int_t<> sessionCapacity, int_t<> requestCapacity, int_t<> responseCapacity, int_t<> eventCapacity, int_t<> pathCapacity, int_t<> textCapacity) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::new_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[33]);
	shared_p<ResidentDaemonIpcArtifact> artifact = create<ResidentDaemonIpcArtifact>();
	artifact->artifact_kind_id = __latency_fn_resident_daemon_file_ipc_artifact_kind_file_ipc_daemon_id();
	artifact->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	artifact->transport_id = __latency_fn_resident_daemon_file_ipc_transport_file_ipc_id();
	php::vector_reserve(artifact->sessions, sessionCapacity);
	php::vector_reserve(artifact->requests, requestCapacity);
	php::vector_reserve(artifact->responses, responseCapacity);
	php::vector_reserve(artifact->source_slot_events, eventCapacity);
	php::vector_reserve(artifact->source_slot_stats, eventCapacity);
	php::vector_reserve(artifact->source_slot_scans, requestCapacity);
	php::vector_reserve(artifact->dirty_source_projections, requestCapacity);
	php::vector_reserve(artifact->loops, requestCapacity);
	php::vector_reserve(artifact->paths, pathCapacity);
	php::vector_reserve(artifact->texts, textCapacity);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_ipc_root_path(const string_t& projectRoot) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::ipc_root_path", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[34]);
	return (cast<string_t>(projectRoot) + string_t("/.scpp-v2"));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_session_file_path(const string_t& projectRoot) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::session_file_path", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[35]);
	return (cast<string_t>(__latency_fn_resident_daemon_file_ipc_ipc_root_path(projectRoot)) + string_t("/session.json"));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_request_dir_path(const string_t& projectRoot) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::request_dir_path", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[36]);
	return (cast<string_t>(__latency_fn_resident_daemon_file_ipc_ipc_root_path(projectRoot)) + string_t("/requests"));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_response_dir_path(const string_t& projectRoot) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::response_dir_path", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[37]);
	return (cast<string_t>(__latency_fn_resident_daemon_file_ipc_ipc_root_path(projectRoot)) + string_t("/responses"));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_request_file_path(const string_t& projectRoot, int_t<std::uint32_t> requestId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::request_file_path", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[38]);
	return (cast<string_t>(__latency_fn_resident_daemon_file_ipc_request_dir_path(projectRoot)) + string_t("/") + cast<string_t>(cast<int_t<>>(requestId)) + string_t(".json"));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_response_file_path(const string_t& projectRoot, int_t<std::uint32_t> requestId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::response_file_path", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[39]);
	return (cast<string_t>(__latency_fn_resident_daemon_file_ipc_response_dir_path(projectRoot)) + string_t("/") + cast<string_t>(cast<int_t<>>(requestId)) + string_t(".json"));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
bool_t __latency_fn_resident_daemon_file_ipc_ensure_ipc_dirs(const string_t& projectRoot) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::ensure_ipc_dirs", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[40]);
	string_t ipcRoot = required_cast<string_t>(__latency_fn_resident_daemon_file_ipc_ipc_root_path(projectRoot));
	string_t requestDir = required_cast<string_t>(__latency_fn_resident_daemon_file_ipc_request_dir_path(projectRoot));
	string_t responseDir = required_cast<string_t>(__latency_fn_resident_daemon_file_ipc_response_dir_path(projectRoot));
	return (((__latency_fn_output_paths_ensure_dir(projectRoot) && __latency_fn_output_paths_ensure_parent_and_dir(projectRoot, ipcRoot)) && __latency_fn_output_paths_ensure_parent_and_dir(ipcRoot, requestDir)) && __latency_fn_output_paths_ensure_parent_and_dir(ipcRoot, responseDir));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_intern_path(shared_p<ResidentDaemonIpcArtifact>& artifact, const string_t& path) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::intern_path", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[41]);
	int_t<> position = required_cast<int_t<>>(static_cast<int_t<> >(1));
	auto __latency_local_0 = artifact->paths;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto existing = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(existing, path))) {
			return __latency_fn_structure_row_ids_uint32_from_int(position);
		}
		position = (position + static_cast<int_t<> >(1));
	}
	(void) artifact->paths.append(path);
	artifact->path_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->paths));
	return artifact->path_count;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_daemon_file_ipc_intern_text(shared_p<ResidentDaemonIpcArtifact>& artifact, const string_t& text) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::intern_text", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[42]);
	int_t<> position = required_cast<int_t<>>(static_cast<int_t<> >(1));
	auto __latency_local_0 = artifact->texts;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto existing = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(existing, text))) {
			return __latency_fn_structure_row_ids_uint32_from_int(position);
		}
		position = (position + static_cast<int_t<> >(1));
	}
	(void) artifact->texts.append(text);
	artifact->text_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->texts));
	return artifact->text_count;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_path_by_id(shared_p<ResidentDaemonIpcArtifact> artifact, int_t<std::uint32_t> pathId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::path_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[43]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(pathId, cast<int_t<>>(artifact->path_count))))) {
		return artifact->paths[__latency_fn_structure_row_ids_dense_index(pathId)];
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_file_ipc_text_by_id(shared_p<ResidentDaemonIpcArtifact> artifact, int_t<std::uint32_t> textId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::text_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[44]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(textId, cast<int_t<>>(artifact->text_count))))) {
		return artifact->texts[__latency_fn_structure_row_ids_dense_index(textId)];
	}
	return string_t("");
}

}

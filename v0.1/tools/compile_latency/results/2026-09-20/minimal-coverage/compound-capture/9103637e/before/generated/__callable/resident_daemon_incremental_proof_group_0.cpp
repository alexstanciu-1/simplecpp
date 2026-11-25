#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/PipelineBatchConfig.hpp"
#include "__types/ProjectManifest.hpp"
#include "__types/ResidentDaemonDirtySourceProjectionRow.hpp"
#include "__types/ResidentDaemonIpcArtifact.hpp"
#include "__types/ResidentDaemonRequestRow.hpp"
#include "__types/ResidentDaemonResponseRow.hpp"
#include "__types/ResidentDaemonSourceSlotEventRow.hpp"
#include "__types/ResidentDaemonSourceSlotScanRow.hpp"
#include "__types/ResidentDaemonSourceSlotStatRow.hpp"
#include "__types/ResidentIncrementalTransactionRow.hpp"
#include "__types/ResidentSourceUnitSnapshotRow.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__types/resident_daemon_incremental_proof.hpp"
#include "__types/resident_transactions.hpp"
#include "__callable/__latency_fn_output_paths_ensure_dir.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_write_text_file.hpp"
#include "__callable/__latency_fn_resident_daemon_incremental_proof_write_two_file_project.hpp"
#include "__callable/__latency_fn_resident_daemon_incremental_proof_batch_for_project.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_request.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_session.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_new_artifact.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_request_command_compile_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_request_dir_path.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_request_file_path.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_request_status_pending_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_response_dir_path.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_response_file_path.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_session_file_path.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_session_status_active_id.hpp"
#include "__callable/__latency_fn_resident_daemon_incremental_proof_request_artifact_for_project.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_scan_added_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_scan_changed_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_scan_unchanged_id.hpp"
#include "__callable/__latency_fn_resident_daemon_incremental_proof_scan_status_from_previous.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_source_slot_content_stat.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_source_slot_event_for_stat.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_source_slot_scan.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_scan_status_completed_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_scan_unchanged_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_stat_by_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_update_request_event_window.hpp"
#include "__callable/__latency_fn_resident_daemon_incremental_proof_append_request_rescan.hpp"
#include "__callable/__latency_fn_resident_daemon_incremental_proof_scan_status_from_previous.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_snapshot_by_owner_and_source.hpp"
#include "__callable/__latency_fn_source_buffers_content_hash32.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_path_from_table.hpp"
#include "__callable/__latency_fn_source_units_source_text_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_daemon_incremental_proof_first_transaction_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_resident_daemon_incremental_proof_response_summary.hpp"
#include "__callable/__latency_fn_compiler_project_runner_run_batch_against_previous.hpp"
#include "__callable/__latency_fn_project_manifest_from_project_dir.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_dirty_source_projection_from_scan.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_append_response.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_dirty_projection_execution_full_project_compare_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_dirty_source_projection_by_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_mark_request_completed.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_path_by_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_read_request_json.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_request_by_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_response_by_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_response_status_ok_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_scan_by_id.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_update_dirty_source_projection_transaction.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_write_response_json.hpp"
#include "__callable/__latency_fn_resident_daemon_incremental_proof_append_request_rescan.hpp"
#include "__callable/__latency_fn_resident_daemon_incremental_proof_first_transaction_id.hpp"
#include "__callable/__latency_fn_resident_daemon_incremental_proof_response_summary.hpp"
#include "__callable/__latency_fn_resident_daemon_incremental_proof_run_changed_file_request.hpp"
#include "__callable/__latency_fn_source_units_table_from_manifest.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_daemon_incremental_proof[]; }
namespace scpp {
bool_t resident_daemon_incremental_proof::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_daemon_incremental_proof::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_incremental_proof[]; }
namespace scpp {
bool_t __latency_fn_resident_daemon_incremental_proof_write_two_file_project(const string_t& projectRoot, int_t<> helperValue) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_incremental_proof::write_two_file_project", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_incremental_proof.phs", __latency_lines_resident_daemon_incremental_proof[0]);
	if (static_cast<bool>((!__latency_fn_output_paths_ensure_dir(projectRoot)))) {
		return bool_t(static_cast<bool_t>(false));
	}
	string_t manifest = required_cast<string_t>(string_t("source=main.phs\nsource=helper.phs\nentry=run\n"));
	string_t main = required_cast<string_t>(string_t("function run(): int {\n\treturn helper();\n}\n"));
	string_t helper = required_cast<string_t>((string_t("function helper(): int {\n\treturn ") + cast<string_t>(helperValue) + string_t(";\n") + string_t("}\n")));
	return ((__latency_fn_resident_daemon_file_ipc_write_text_file((cast<string_t>(projectRoot) + string_t("/project.manifest")), manifest) && __latency_fn_resident_daemon_file_ipc_write_text_file((cast<string_t>(projectRoot) + string_t("/main.phs")), main)) && __latency_fn_resident_daemon_file_ipc_write_text_file((cast<string_t>(projectRoot) + string_t("/helper.phs")), helper));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_incremental_proof[]; }
namespace scpp {
shared_p<PipelineBatchConfig> __latency_fn_resident_daemon_incremental_proof_batch_for_project(const string_t& projectRootParent, const string_t& runLabel, const string_t& buildRoot) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_incremental_proof::batch_for_project", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_incremental_proof.phs", __latency_lines_resident_daemon_incremental_proof[1]);
	shared_p<PipelineBatchConfig> batch = create<PipelineBatchConfig>();
	batch->project_root = projectRootParent;
	batch->build_root = buildRoot;
	(void) batch->run_labels.append(runLabel);
	return batch;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_incremental_proof[]; }
namespace scpp {
shared_p<ResidentDaemonIpcArtifact> __latency_fn_resident_daemon_incremental_proof_request_artifact_for_project(const string_t& projectRoot, int_t<std::uint32_t> requestId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_incremental_proof::request_artifact_for_project", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_incremental_proof.phs", __latency_lines_resident_daemon_incremental_proof[2]);
	shared_p<ResidentDaemonIpcArtifact> artifact = __latency_fn_resident_daemon_file_ipc_new_artifact(static_cast<int_t<> >(1), static_cast<int_t<> >(1), static_cast<int_t<> >(1), static_cast<int_t<> >(2), static_cast<int_t<> >(8), static_cast<int_t<> >(3));
	int_t<std::uint32_t> sessionId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_daemon_file_ipc_append_session(artifact, projectRoot, __latency_fn_resident_daemon_file_ipc_session_file_path(projectRoot), __latency_fn_resident_daemon_file_ipc_request_dir_path(projectRoot), __latency_fn_resident_daemon_file_ipc_response_dir_path(projectRoot), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1234)), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), __latency_fn_resident_daemon_file_ipc_session_status_active_id()));
	__latency_fn_resident_daemon_file_ipc_append_request(artifact, sessionId, projectRoot, __latency_fn_resident_daemon_file_ipc_request_file_path(projectRoot, requestId), __latency_fn_resident_daemon_file_ipc_response_file_path(projectRoot, requestId), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), __latency_fn_structure_row_ids_none_id(), __latency_fn_structure_row_ids_none_id(), __latency_fn_resident_daemon_file_ipc_request_command_compile_id(), __latency_fn_resident_daemon_file_ipc_request_status_pending_id());
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_incremental_proof[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_daemon_incremental_proof_scan_status_from_previous(ResidentSourceUnitSnapshotRow previous, int_t<std::uint32_t> currentContentHash, int_t<std::uint32_t> currentSourceLength, int_t<std::uint32_t> currentLineCount) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_incremental_proof::scan_status_from_previous", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_incremental_proof.phs", __latency_lines_resident_daemon_incremental_proof[3]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(previous->snapshot_id), static_cast<int_t<> >(0)))) {
		return __latency_fn_resident_daemon_file_ipc_source_slot_scan_added_id();
	}
	if (static_cast<bool>(((php::identical(cast<int_t<>>(previous->content_hash), cast<int_t<>>(currentContentHash)) && php::identical(cast<int_t<>>(previous->source_length), cast<int_t<>>(currentSourceLength))) && php::identical(cast<int_t<>>(previous->line_count), cast<int_t<>>(currentLineCount))))) {
		return __latency_fn_resident_daemon_file_ipc_source_slot_scan_unchanged_id();
	}
	return __latency_fn_resident_daemon_file_ipc_source_slot_scan_changed_id();
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_incremental_proof[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_daemon_incremental_proof_append_request_rescan(shared_p<ResidentDaemonIpcArtifact>& artifact, ResidentDaemonRequestRow request, shared_p<CompilerProjectRunReport> previousReport, shared_p<SourceUnitTable> sourceUnits, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_incremental_proof::append_request_rescan", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_incremental_proof.phs", __latency_lines_resident_daemon_incremental_proof[4]);
	int_t<std::uint32_t> statFirstId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_next_dense_id(php::count(artifact->source_slot_stats)));
	int_t<std::uint32_t> eventFirstId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_next_dense_id(php::count(artifact->source_slot_events)));
	int_t<> candidateEventCount = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = sourceUnits->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto sourceUnit = __latency_local_1.value_copy();
		string_t sourceText = required_cast<string_t>(__latency_fn_source_units_source_text_by_id(sourceUnits, sourceUnit->source_unit_id));
		int_t<std::uint32_t> currentContentHash = required_cast<int_t<std::uint32_t>>(__latency_fn_source_buffers_content_hash32(sourceText));
		ResidentSourceUnitSnapshotRow previous = __latency_fn_resident_definition_granularity_source_snapshot_by_owner_and_source(previousReport, ownerRunId, sourceUnit->source_unit_id);
		int_t<std::uint16_t> scanStatusId = required_cast<int_t<std::uint16_t>>(__latency_fn_resident_daemon_incremental_proof_scan_status_from_previous(previous, cast<int_t<std::uint32_t>>(currentContentHash), sourceUnit->source_length, sourceUnit->line_count));
		int_t<std::uint32_t> statId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_daemon_file_ipc_append_source_slot_content_stat(artifact, request->request_id, sourceUnit->source_unit_id, __latency_fn_source_identity_source_unit_path_from_table(sourceUnits, sourceUnit), previous->source_length, sourceUnit->source_length, previous->content_hash, currentContentHash, scanStatusId));
		if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(scanStatusId), cast<int_t<>>(__latency_fn_resident_daemon_file_ipc_source_slot_scan_unchanged_id()))))) {
			ResidentDaemonSourceSlotStatRow stat = __latency_fn_resident_daemon_file_ipc_source_slot_stat_by_id(artifact, statId);
			int_t<std::uint32_t> eventId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_daemon_file_ipc_append_source_slot_event_for_stat(artifact, stat));
			if (static_cast<bool>((cast<int_t<>>(eventId) > static_cast<int_t<> >(0)))) {
				candidateEventCount = (candidateEventCount + static_cast<int_t<> >(1));
			}
		}
	}
	int_t<> eventCount = required_cast<int_t<>>((cast<int_t<>>(artifact->source_slot_event_count) - (cast<int_t<>>(eventFirstId) - static_cast<int_t<> >(1))));
	int_t<> statCount = required_cast<int_t<>>((cast<int_t<>>(artifact->source_slot_stat_count) - (cast<int_t<>>(statFirstId) - static_cast<int_t<> >(1))));
	__latency_fn_resident_daemon_file_ipc_update_request_event_window(artifact, request->request_id, eventFirstId, __latency_fn_structure_row_ids_uint32_from_int(eventCount));
	return __latency_fn_resident_daemon_file_ipc_append_source_slot_scan(artifact, request->request_id, statFirstId, __latency_fn_structure_row_ids_uint32_from_int(statCount), __latency_fn_structure_row_ids_uint32_from_int(candidateEventCount), eventFirstId, __latency_fn_structure_row_ids_uint32_from_int(eventCount), __latency_fn_resident_daemon_file_ipc_source_slot_scan_status_completed_id());
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_incremental_proof[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_daemon_incremental_proof_first_transaction_id(shared_p<CompilerProjectRunReport> report) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_incremental_proof::first_transaction_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_incremental_proof.phs", __latency_lines_resident_daemon_incremental_proof[5]);
	if (static_cast<bool>(((cast<int_t<>>(report->resident_transaction_count) > static_cast<int_t<> >(0)) && (php::count(report->resident_transactions) > static_cast<int_t<> >(0))))) {
		ResidentIncrementalTransactionRow transaction = report->resident_transactions[static_cast<int_t<> >(0)];
		return transaction->transaction_id;
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_incremental_proof[]; }
namespace scpp {
string_t __latency_fn_resident_daemon_incremental_proof_response_summary(shared_p<CompilerProjectRunReport> report, ResidentDaemonSourceSlotScanRow scan, ResidentDaemonDirtySourceProjectionRow projection) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_incremental_proof::response_summary", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_incremental_proof.phs", __latency_lines_resident_daemon_incremental_proof[6]);
	return (string_t("scan_events=") + cast<string_t>(cast<int_t<>>(scan->event_count)) + string_t(";projected_dirty=") + cast<string_t>(cast<int_t<>>(projection->dirty_source_count)) + string_t(";projected_parse=") + cast<string_t>(cast<int_t<>>(projection->parse_candidate_count)) + string_t(";source_change_rows=") + cast<string_t>(cast<int_t<>>(report->resident_source_unit_change_count)) + string_t(";source_reused=") + cast<string_t>(cast<int_t<>>(report->resident_source_unit_reuse_count)) + string_t(";symbol_body_changed=") + cast<string_t>(cast<int_t<>>(report->resident_symbol_definition_body_change_count)) + string_t(";transactions=") + cast<string_t>(cast<int_t<>>(report->resident_transaction_count)));
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_incremental_proof[]; }
namespace scpp {
shared_p<CompilerProjectRunReport> __latency_fn_resident_daemon_incremental_proof_run_changed_file_request(shared_p<ResidentDaemonIpcArtifact>& artifact, const string_t& requestPath, shared_p<CompilerProjectRunReport> previousReport, shared_p<PipelineBatchConfig> batch) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_incremental_proof::run_changed_file_request", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_incremental_proof.phs", __latency_lines_resident_daemon_incremental_proof[7]);
	int_t<std::uint32_t> requestReadId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_daemon_file_ipc_read_request_json(artifact, requestPath));
	ResidentDaemonRequestRow request = __latency_fn_resident_daemon_file_ipc_request_by_id(artifact, requestReadId);
	string_t projectRoot = required_cast<string_t>(__latency_fn_resident_daemon_file_ipc_path_by_id(artifact, request->project_root_path_id));
	shared_p<ProjectManifest> manifest = __latency_fn_project_manifest_from_project_dir(projectRoot);
	shared_p<SourceUnitTable> sourceUnits = __latency_fn_source_units_table_from_manifest(manifest);
	int_t<std::uint32_t> scanId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_daemon_incremental_proof_append_request_rescan(artifact, request, previousReport, sourceUnits, __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1))));
	ResidentDaemonSourceSlotScanRow scan = __latency_fn_resident_daemon_file_ipc_source_slot_scan_by_id(artifact, scanId);
	int_t<std::uint32_t> projectionId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_daemon_file_ipc_append_dirty_source_projection_from_scan(artifact, request, scan, __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), __latency_fn_structure_row_ids_none_id(), __latency_fn_resident_daemon_file_ipc_dirty_projection_execution_full_project_compare_id()));
	shared_p<CompilerProjectRunReport> report = __latency_fn_compiler_project_runner_run_batch_against_previous(batch, previousReport);
	__latency_fn_resident_daemon_file_ipc_update_dirty_source_projection_transaction(artifact, projectionId, __latency_fn_resident_daemon_incremental_proof_first_transaction_id(report));
	ResidentDaemonDirtySourceProjectionRow projection = __latency_fn_resident_daemon_file_ipc_dirty_source_projection_by_id(artifact, projectionId);
	ResidentDaemonRequestRow currentRequest = __latency_fn_resident_daemon_file_ipc_request_by_id(artifact, requestReadId);
	__latency_fn_resident_daemon_file_ipc_mark_request_completed(artifact, requestReadId);
	int_t<std::uint32_t> responseId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_daemon_file_ipc_append_response(artifact, requestReadId, string_t("incremental_ok\n"), string_t(""), __latency_fn_resident_daemon_incremental_proof_response_summary(report, scan, projection), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), __latency_fn_resident_daemon_file_ipc_response_status_ok_id(), __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(0))));
	ResidentDaemonResponseRow response = __latency_fn_resident_daemon_file_ipc_response_by_id(artifact, responseId);
	__latency_fn_resident_daemon_file_ipc_write_response_json(artifact, response, currentRequest);
	return report;
}

}

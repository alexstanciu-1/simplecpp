#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/CapabilityProviderRow.hpp"
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/OperationReadiness.hpp"
#include "__types/PartitionMergeReductionArtifact.hpp"
#include "__types/PartitionMergeReductionRow.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/StorageLifetimeRequestRow.hpp"
#include "__types/StorageLifetimeWorkerInput.hpp"
#include "__types/StorageLifetimeWorkerResult.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_record_selected_publication_owner.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_record_worker_gate.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_first_output_row.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_from_partition_readiness.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_finish.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_storage_lifetime_snapshot_build.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_storage_lifetime_worker_task.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_start.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_publication_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_publication_input_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_publication_metadata_commit_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_publication_output_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_publication_output_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_publication_output_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_publication_published_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_publication_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_publication_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_storage_lifetime_publication_simulated_first_input_order_sum.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_production_mt_enabled.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_first_worker_result.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_publication_blocked_request_count.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_publication_ready_request_count.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_publication_request_count.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_record_storage_lifetime_publication_metrics__exec.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_record_storage_lifetime_worker_handoff_metrics.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_semantic_hash_for_requests.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_storage_lifetime_publication_artifact.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_storage_lifetime_publication_published_row_total.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_worker_input_from_rows.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_worker_results.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
void __latency_fn_storage_lifetime_readiness_record_storage_lifetime_publication_metrics__exec(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, OperationReadiness readyOperation, OperationReadiness blockedOperation, CapabilityConsumerRow readyConsumer, CapabilityConsumerRow blockedConsumer, CapabilityProviderRow provider, StorageLifetimeRequestRow readyRow, StorageLifetimeRequestRow blockedRow, int_t<> workerCount, bool_t upstreamCapabilityReadinessWorkerReady, bool_t& storageLifetimeWorkerCandidateReady) {
	storageLifetimeWorkerCandidateReady = bool_t(static_cast<bool_t>(false));
	shared_p<PartitionReadinessArtifact> publication = __latency_fn_storage_lifetime_readiness_storage_lifetime_publication_artifact(cast<int_t<std::uint32_t>>(ownerRunId), entrySymbol, readyRow, blockedRow);
	shared_p<PartitionReadinessArtifact> simulatedPublication = __latency_fn_storage_lifetime_readiness_storage_lifetime_publication_artifact(cast<int_t<std::uint32_t>>(ownerRunId), entrySymbol, readyRow, blockedRow);
	shared_p<PartitionMergeReductionArtifact> reduction = __latency_fn_partition_merge_reductions_from_partition_readiness(publication);
	shared_p<PartitionMergeReductionArtifact> simulatedReduction = __latency_fn_partition_merge_reductions_from_partition_readiness(simulatedPublication);
	PartitionMergeReductionRow firstSimulatedOutput = __latency_fn_partition_merge_reductions_first_output_row(simulatedReduction);
	bool_t outputsMatch = required_cast<bool_t>((php::identical(reduction->stable_output_hash, simulatedReduction->stable_output_hash) && php::identical(cast<int_t<>>(reduction->output_row_count), cast<int_t<>>(simulatedReduction->output_row_count))));
	int_t<std::uint32_t> publishedRows = required_cast<int_t<std::uint32_t>>(__latency_fn_storage_lifetime_readiness_storage_lifetime_publication_published_row_total(publication));
	bool_t hasWorkerCandidate = required_cast<bool_t>((workerCount > static_cast<int_t<> >(1)));
	bool_t workerHandoffMatches = required_cast<bool_t>(bool_t(static_cast<bool_t>(true)));
	bool_t productionMtEnabled = required_cast<bool_t>(__latency_fn_source_unit_frontend_scheduler_production_mt_enabled());
	if (static_cast<bool>(((productionMtEnabled && hasWorkerCandidate) && upstreamCapabilityReadinessWorkerReady))) {
		int_t<std::uint64_t> storageSnapshotStarted = required_cast<int_t<std::uint64_t>>(__latency_fn_production_mt_heartbeat_start(report, __latency_fn_production_mt_heartbeat_stage_storage_lifetime_snapshot_build(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(5))));
		vector_t<shared_p<StorageLifetimeWorkerInput>> workerInputs = required_cast<vector_t<shared_p<StorageLifetimeWorkerInput>>>(vector_t<shared_p<StorageLifetimeWorkerInput>>{__latency_fn_storage_lifetime_readiness_worker_input_from_rows(cast<int_t<std::uint32_t>>(ownerRunId), entrySymbol, readyOperation, blockedOperation, readyConsumer, blockedConsumer, provider)});
		__latency_fn_production_mt_heartbeat_finish(report, __latency_fn_production_mt_heartbeat_stage_storage_lifetime_snapshot_build(), storageSnapshotStarted, __latency_fn_structure_row_ids_uint32_from_int(php::count(workerInputs)));
		int_t<std::uint64_t> storageWorkerTaskStarted = required_cast<int_t<std::uint64_t>>(__latency_fn_production_mt_heartbeat_start(report, __latency_fn_production_mt_heartbeat_stage_storage_lifetime_worker_task(), __latency_fn_structure_row_ids_uint32_from_int(php::count(workerInputs))));
		vector_t<shared_p<StorageLifetimeWorkerResult>> workerResults = required_cast<vector_t<shared_p<StorageLifetimeWorkerResult>>>(__latency_fn_storage_lifetime_readiness_worker_results(workerInputs, workerCount));
		__latency_fn_production_mt_heartbeat_finish(report, __latency_fn_production_mt_heartbeat_stage_storage_lifetime_worker_task(), storageWorkerTaskStarted, __latency_fn_structure_row_ids_uint32_from_int(php::count(workerResults)));
		shared_p<StorageLifetimeWorkerResult> workerResult = __latency_fn_storage_lifetime_readiness_first_worker_result(workerResults);
		int_t<std::uint32_t> coordinatorSemanticHash = required_cast<int_t<std::uint32_t>>(__latency_fn_storage_lifetime_readiness_semantic_hash_for_requests(readyRow, blockedRow));
		int_t<std::uint32_t> coordinatorRequestRows = required_cast<int_t<std::uint32_t>>(__latency_fn_storage_lifetime_readiness_publication_request_count(readyRow, blockedRow));
		int_t<std::uint32_t> coordinatorReadyRows = required_cast<int_t<std::uint32_t>>(__latency_fn_storage_lifetime_readiness_publication_ready_request_count(readyRow, blockedRow));
		int_t<std::uint32_t> coordinatorBlockedRows = required_cast<int_t<std::uint32_t>>(__latency_fn_storage_lifetime_readiness_publication_blocked_request_count(readyRow, blockedRow));
		workerHandoffMatches = ((((((((((php::identical(php::count(workerResults), static_cast<int_t<> >(1)) && php::identical(workerResult->request_rows, cast<int_t<>>(coordinatorRequestRows))) && php::identical(workerResult->ready_rows, cast<int_t<>>(coordinatorReadyRows))) && php::identical(workerResult->blocked_rows, cast<int_t<>>(coordinatorBlockedRows))) && php::identical(workerResult->storage_context_rows, cast<int_t<>>(coordinatorRequestRows))) && php::identical(workerResult->copy_policy_rows, cast<int_t<>>(coordinatorRequestRows))) && php::identical(workerResult->cleanup_policy_rows, cast<int_t<>>(coordinatorRequestRows))) && php::identical(workerResult->lifetime_policy_rows, cast<int_t<>>(coordinatorRequestRows))) && php::identical(workerResult->published_rows, cast<int_t<>>(coordinatorRequestRows))) && (workerResult->semantic_hash > static_cast<int_t<> >(0))) && php::identical(workerResult->semantic_hash, cast<int_t<>>(coordinatorSemanticHash)));
		__latency_fn_storage_lifetime_readiness_record_storage_lifetime_worker_handoff_metrics(report, workerInputs, workerResults, readyRow, blockedRow, cast<int_t<std::uint32_t>>(coordinatorSemanticHash), bool_t(workerHandoffMatches));
	}
	bool_t workerCandidateReady = required_cast<bool_t>((((((productionMtEnabled && hasWorkerCandidate) && upstreamCapabilityReadinessWorkerReady) && workerHandoffMatches) && outputsMatch) && (cast<int_t<>>(publishedRows) > static_cast<int_t<> >(0))));
	storageLifetimeWorkerCandidateReady = bool_t(workerCandidateReady);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_publication_runs(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_publication_input_rows(), publication->row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_publication_output_rows(), reduction->output_row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_publication_published_rows(), publishedRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_publication_ready_rows(), __latency_fn_storage_lifetime_readiness_publication_ready_request_count(readyRow, blockedRow));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_publication_blocked_rows(), __latency_fn_storage_lifetime_readiness_publication_blocked_request_count(readyRow, blockedRow));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_publication_metadata_commit_bytes(), __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(publication->row_count) * static_cast<int_t<> >(sizeof(PartitionReadinessRow)))));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_publication_simulated_first_input_order_sum(), firstSimulatedOutput->input_order_id);
	if (static_cast<bool>(php::condition_truthy(outputsMatch))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_publication_output_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_publication_output_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_publication_output_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_storage_lifetime_publication_output_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	}
	__latency_fn_mt_publication_metrics_record_selected_publication_owner(report, string_t("storage_lifetime_publication"), workerCandidateReady);
	__latency_fn_mt_publication_metrics_record_worker_gate(report, string_t("storage_lifetime_publication"), string_t("worker_blocked_by_upstream_payload_source"), string_t("upstream_capability_readiness_worker_ready"), string_t("upstream_capability_readiness_coordinator_blocked"), string_t("worker_candidate_ready"), string_t("o3_measurement_required"), string_t("speedup_claim_blocked"), string_t("payload_copy_bytes"), hasWorkerCandidate, upstreamCapabilityReadinessWorkerReady, workerCandidateReady);
}

}

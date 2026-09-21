#include <scpp/lang/php.hpp>
#include "__types/AnalysisEntryContextRow.hpp"
#include "__types/BackendLoweringWorkerInput.hpp"
#include "__types/BackendLoweringWorkerResult.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/LoweringPlan.hpp"
#include "__types/OperationReadiness.hpp"
#include "__types/PartitionMergeReductionArtifact.hpp"
#include "__types/PartitionMergeReductionRow.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/StorageLifetimeRequestRow.hpp"
#include "__callable/__latency_fn_lowering_plan_storage_from_worker_input.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_artifact_from_operation.hpp"
#include "__callable/__latency_fn_lowering_plan_contract_from_worker_input.hpp"
#include "__callable/__latency_fn_lowering_plan_entry_from_worker_input.hpp"
#include "__callable/__latency_fn_lowering_plan_from_entry_and_backend_requests.hpp"
#include "__callable/__latency_fn_lowering_plan_operation_from_worker_input.hpp"
#include "__callable/__latency_fn_lowering_plan_publication_plan_row_count.hpp"
#include "__callable/__latency_fn_lowering_plan_publication_published_row_count.hpp"
#include "__callable/__latency_fn_lowering_plan_semantic_hash_for_backend_lowering.hpp"
#include "__callable/__latency_fn_lowering_plan_sidecar_row_count.hpp"
#include "__callable/__latency_fn_lowering_plan_storage_from_worker_input.hpp"
#include "__callable/__latency_fn_lowering_plan_worker_result_for_input.hpp"
#include "__callable/__latency_fn_lowering_plan_worker_result_for_input.hpp"
#include "__callable/__latency_fn_lowering_plan_worker_results.hpp"
#include "__callable/__latency_fn_lowering_plan_first_worker_result.hpp"
#include "__callable/__latency_fn_lowering_plan_first_worker_result.hpp"
#include "__callable/__latency_fn_lowering_plan_publication_plan_row_count.hpp"
#include "__callable/__latency_fn_lowering_plan_record_backend_lowering_worker_handoff_metrics.hpp"
#include "__callable/__latency_fn_lowering_plan_sidecar_row_count.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_coordinator_blocked_lowering_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_coordinator_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_coordinator_lowering_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_coordinator_owner_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_coordinator_plan_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_coordinator_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_coordinator_request_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_coordinator_semantic_hash.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_coordinator_sidecar_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_coordinator_work_refs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_descriptor_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_inputs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_snapshot_contract_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_snapshot_entry_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_snapshot_operation_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_snapshot_storage_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_worker_blocked_lowering_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_worker_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_worker_lowering_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_worker_owner_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_worker_plan_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_worker_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_worker_request_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_worker_semantic_hash.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_worker_sidecar_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_worker_handoff_worker_work_refs.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_lowering_plan_backend_lowering_publication_artifact.hpp"
#include "__callable/__latency_fn_lowering_plan_backend_lowering_publication_published_row_total.hpp"
#include "__callable/__latency_fn_lowering_plan_first_worker_result.hpp"
#include "__callable/__latency_fn_lowering_plan_publication_plan_row_count.hpp"
#include "__callable/__latency_fn_lowering_plan_publication_published_row_count.hpp"
#include "__callable/__latency_fn_lowering_plan_record_backend_lowering_publication_metrics__exec.hpp"
#include "__callable/__latency_fn_lowering_plan_record_backend_lowering_worker_handoff_metrics.hpp"
#include "__callable/__latency_fn_lowering_plan_semantic_hash_for_backend_lowering.hpp"
#include "__callable/__latency_fn_lowering_plan_sidecar_row_count.hpp"
#include "__callable/__latency_fn_lowering_plan_worker_input_from_rows.hpp"
#include "__callable/__latency_fn_lowering_plan_worker_results.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_record_selected_publication_owner.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_record_worker_gate.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_first_output_row.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_from_partition_readiness.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_finish.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_backend_lowering_snapshot_build.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_backend_lowering_worker_task.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_start.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_publication_backend_request_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_publication_backend_request_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_publication_backend_request_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_publication_input_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_publication_lowering_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_publication_lowering_plan_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_publication_lowering_step_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_publication_lowering_work_refs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_publication_metadata_commit_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_publication_output_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_publication_output_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_publication_output_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_publication_published_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_publication_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_backend_lowering_publication_simulated_first_input_order_sum.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_production_mt_enabled.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
StorageLifetimeRequestRow __latency_fn_lowering_plan_storage_from_worker_input(shared_p<BackendLoweringWorkerInput> input) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::storage_from_worker_input", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[74]);
	StorageLifetimeRequestRow row = StorageLifetimeRequestRow{};
	row->request_id = __latency_fn_structure_row_ids_uint32_from_int(input->storage_request_id);
	row->consumer_kind_id = __latency_fn_structure_row_ids_uint16_from_int(input->storage_consumer_kind_id);
	row->capability_id = __latency_fn_structure_row_ids_uint16_from_int(input->storage_capability_id);
	row->consumer_feature_id = __latency_fn_structure_row_ids_uint16_from_int(input->storage_consumer_feature_id);
	row->storage_context_id = __latency_fn_structure_row_ids_uint16_from_int(input->storage_context_id);
	row->type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(input->storage_type_ref_id);
	row->source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->storage_source_row_id);
	row->storage_policy_id = __latency_fn_structure_row_ids_uint16_from_int(input->storage_policy_id);
	row->copy_policy_id = __latency_fn_structure_row_ids_uint16_from_int(input->storage_copy_policy_id);
	row->cleanup_policy_id = __latency_fn_structure_row_ids_uint16_from_int(input->storage_cleanup_policy_id);
	row->lifetime_policy_id = __latency_fn_structure_row_ids_uint16_from_int(input->storage_lifetime_policy_id);
	row->readiness_status_id = __latency_fn_structure_row_ids_uint16_from_int(input->storage_readiness_status_id);
	return row;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
shared_p<BackendLoweringWorkerResult> __latency_fn_lowering_plan_worker_result_for_input(shared_p<BackendLoweringWorkerInput> input) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::worker_result_for_input", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[75]);
	AnalysisEntryContextRow entry = __latency_fn_lowering_plan_entry_from_worker_input(input);
	ProjectCallableContractRow contract = __latency_fn_lowering_plan_contract_from_worker_input(input);
	OperationReadiness operation = __latency_fn_lowering_plan_operation_from_worker_input(input);
	StorageLifetimeRequestRow storage = __latency_fn_lowering_plan_storage_from_worker_input(input);
	shared_p<BackendRequestAuthorizationArtifact> requests = __latency_fn_backend_preflight_requests_backend_request_artifact_from_operation(operation, storage, contract);
	shared_p<LoweringPlan> plan = __latency_fn_lowering_plan_from_entry_and_backend_requests(entry, requests);
	shared_p<BackendLoweringWorkerResult> result = create<BackendLoweringWorkerResult>();
	result->source_unit_id = input->source_unit_id;
	result->symbol_id = input->symbol_id;
	result->backend_request_rows = cast<int_t<>>(requests->request_count);
	result->backend_request_ready_rows = cast<int_t<>>(requests->ready_count);
	result->backend_request_blocked_rows = cast<int_t<>>(requests->blocked_count);
	result->lowering_plan_rows = cast<int_t<>>(__latency_fn_lowering_plan_publication_plan_row_count(plan));
	result->lowering_step_rows = cast<int_t<>>(plan->step_count);
	result->lowering_work_refs = cast<int_t<>>(plan->work_ref_count);
	result->lowering_blocked_rows = cast<int_t<>>(plan->blocked_request_count);
	result->sidecar_rows = cast<int_t<>>(__latency_fn_lowering_plan_sidecar_row_count(requests, plan));
	result->owner_rows = cast<int_t<>>(__latency_fn_lowering_plan_publication_plan_row_count(plan));
	result->published_rows = cast<int_t<>>(__latency_fn_lowering_plan_publication_published_row_count(requests, plan));
	result->semantic_hash = cast<int_t<>>(__latency_fn_lowering_plan_semantic_hash_for_backend_lowering(requests, plan));
	return result;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
vector_t<shared_p<BackendLoweringWorkerResult>> __latency_fn_lowering_plan_worker_results(const vector_t<shared_p<BackendLoweringWorkerInput>>& inputs, int_t<> workerCount) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::worker_results", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[76]);
	vector_t<shared_p<BackendLoweringWorkerResult>> results = required_cast<vector_t<shared_p<BackendLoweringWorkerResult>>>(tasks::run(inputs, workerCount, [](shared_p<BackendLoweringWorkerInput> input) -> shared_p<BackendLoweringWorkerResult> {
	return __latency_fn_lowering_plan_worker_result_for_input(input);
}));
	return results;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
shared_p<BackendLoweringWorkerResult> __latency_fn_lowering_plan_first_worker_result(const vector_t<shared_p<BackendLoweringWorkerResult>>& results) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::first_worker_result", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[77]);
	if (static_cast<bool>((php::count(results) > static_cast<int_t<> >(0)))) {
		return results.at(static_cast<int_t<> >(0));
	}
	shared_p<BackendLoweringWorkerResult> empty = create<BackendLoweringWorkerResult>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
void __latency_fn_lowering_plan_record_backend_lowering_worker_handoff_metrics(shared_p<CompilerProjectRunReport>& report, const vector_t<shared_p<BackendLoweringWorkerInput>>& inputs, const vector_t<shared_p<BackendLoweringWorkerResult>>& results, shared_p<BackendRequestAuthorizationArtifact> requests, shared_p<LoweringPlan> plan, int_t<std::uint32_t> coordinatorSemanticHash, bool_t matches) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::record_backend_lowering_worker_handoff_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[78]);
	shared_p<BackendLoweringWorkerResult> worker = __latency_fn_lowering_plan_first_worker_result(results);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_runs(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_inputs(), __latency_fn_structure_row_ids_uint32_from_int(php::count(inputs)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_snapshot_entry_rows(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_snapshot_contract_rows(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_snapshot_operation_rows(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_snapshot_storage_rows(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_descriptor_rows(), __latency_fn_structure_row_ids_uint32_from_int(php::count(results)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_worker_request_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->backend_request_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_coordinator_request_rows(), requests->request_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_worker_ready_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->backend_request_ready_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_coordinator_ready_rows(), requests->ready_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_worker_blocked_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->backend_request_blocked_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_coordinator_blocked_rows(), requests->blocked_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_worker_plan_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->lowering_plan_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_coordinator_plan_rows(), __latency_fn_lowering_plan_publication_plan_row_count(plan));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_worker_lowering_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->lowering_step_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_coordinator_lowering_rows(), plan->step_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_worker_work_refs(), __latency_fn_structure_row_ids_uint32_from_int(worker->lowering_work_refs));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_coordinator_work_refs(), plan->work_ref_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_worker_blocked_lowering_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->lowering_blocked_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_coordinator_blocked_lowering_rows(), plan->blocked_request_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_worker_sidecar_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->sidecar_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_coordinator_sidecar_rows(), __latency_fn_lowering_plan_sidecar_row_count(requests, plan));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_worker_owner_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->owner_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_coordinator_owner_rows(), __latency_fn_lowering_plan_publication_plan_row_count(plan));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_worker_semantic_hash(), __latency_fn_structure_row_ids_uint32_from_int(worker->semantic_hash));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_coordinator_semantic_hash(), coordinatorSemanticHash);
	if (static_cast<bool>(php::condition_truthy(matches))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_worker_handoff_payload_copy_bytes(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
void __latency_fn_lowering_plan_record_backend_lowering_publication_metrics__exec(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, AnalysisEntryContextRow entry, ProjectCallableContractRow contract, OperationReadiness operation, StorageLifetimeRequestRow storage, shared_p<BackendRequestAuthorizationArtifact> requests, shared_p<LoweringPlan> plan, int_t<> workerCount, bool_t upstreamStorageLifetimeWorkerReady, bool_t& backendLoweringWorkerCandidateReady) {
	backendLoweringWorkerCandidateReady = bool_t(static_cast<bool_t>(false));
	shared_p<PartitionReadinessArtifact> publication = __latency_fn_lowering_plan_backend_lowering_publication_artifact(cast<int_t<std::uint32_t>>(ownerRunId), entrySymbol, requests, plan);
	shared_p<PartitionReadinessArtifact> simulatedPublication = __latency_fn_lowering_plan_backend_lowering_publication_artifact(cast<int_t<std::uint32_t>>(ownerRunId), entrySymbol, requests, plan);
	shared_p<PartitionMergeReductionArtifact> reduction = __latency_fn_partition_merge_reductions_from_partition_readiness(publication);
	shared_p<PartitionMergeReductionArtifact> simulatedReduction = __latency_fn_partition_merge_reductions_from_partition_readiness(simulatedPublication);
	PartitionMergeReductionRow firstSimulatedOutput = __latency_fn_partition_merge_reductions_first_output_row(simulatedReduction);
	bool_t outputsMatch = required_cast<bool_t>((php::identical(reduction->stable_output_hash, simulatedReduction->stable_output_hash) && php::identical(cast<int_t<>>(reduction->output_row_count), cast<int_t<>>(simulatedReduction->output_row_count))));
	int_t<std::uint32_t> publishedRows = required_cast<int_t<std::uint32_t>>(__latency_fn_lowering_plan_backend_lowering_publication_published_row_total(publication));
	bool_t hasWorkerCandidate = required_cast<bool_t>((workerCount > static_cast<int_t<> >(1)));
	bool_t workerHandoffMatches = required_cast<bool_t>(bool_t(static_cast<bool_t>(true)));
	bool_t productionMtEnabled = required_cast<bool_t>(__latency_fn_source_unit_frontend_scheduler_production_mt_enabled());
	if (static_cast<bool>(((productionMtEnabled && hasWorkerCandidate) && upstreamStorageLifetimeWorkerReady))) {
		int_t<std::uint64_t> backendLoweringSnapshotStarted = required_cast<int_t<std::uint64_t>>(__latency_fn_production_mt_heartbeat_start(report, __latency_fn_production_mt_heartbeat_stage_backend_lowering_snapshot_build(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(4))));
		vector_t<shared_p<BackendLoweringWorkerInput>> workerInputs = required_cast<vector_t<shared_p<BackendLoweringWorkerInput>>>(vector_t<shared_p<BackendLoweringWorkerInput>>{__latency_fn_lowering_plan_worker_input_from_rows(cast<int_t<std::uint32_t>>(ownerRunId), entrySymbol, entry, contract, operation, storage)});
		__latency_fn_production_mt_heartbeat_finish(report, __latency_fn_production_mt_heartbeat_stage_backend_lowering_snapshot_build(), backendLoweringSnapshotStarted, __latency_fn_structure_row_ids_uint32_from_int(php::count(workerInputs)));
		int_t<std::uint64_t> backendLoweringWorkerTaskStarted = required_cast<int_t<std::uint64_t>>(__latency_fn_production_mt_heartbeat_start(report, __latency_fn_production_mt_heartbeat_stage_backend_lowering_worker_task(), __latency_fn_structure_row_ids_uint32_from_int(php::count(workerInputs))));
		vector_t<shared_p<BackendLoweringWorkerResult>> workerResults = required_cast<vector_t<shared_p<BackendLoweringWorkerResult>>>(__latency_fn_lowering_plan_worker_results(workerInputs, workerCount));
		__latency_fn_production_mt_heartbeat_finish(report, __latency_fn_production_mt_heartbeat_stage_backend_lowering_worker_task(), backendLoweringWorkerTaskStarted, __latency_fn_structure_row_ids_uint32_from_int(php::count(workerResults)));
		shared_p<BackendLoweringWorkerResult> workerResult = __latency_fn_lowering_plan_first_worker_result(workerResults);
		int_t<std::uint32_t> coordinatorSemanticHash = required_cast<int_t<std::uint32_t>>(__latency_fn_lowering_plan_semantic_hash_for_backend_lowering(requests, plan));
		int_t<std::uint32_t> coordinatorSidecarRows = required_cast<int_t<std::uint32_t>>(__latency_fn_lowering_plan_sidecar_row_count(requests, plan));
		int_t<std::uint32_t> coordinatorPlanRows = required_cast<int_t<std::uint32_t>>(__latency_fn_lowering_plan_publication_plan_row_count(plan));
		workerHandoffMatches = ((((((((((((php::identical(php::count(workerResults), static_cast<int_t<> >(1)) && php::identical(workerResult->backend_request_rows, cast<int_t<>>(requests->request_count))) && php::identical(workerResult->backend_request_ready_rows, cast<int_t<>>(requests->ready_count))) && php::identical(workerResult->backend_request_blocked_rows, cast<int_t<>>(requests->blocked_count))) && php::identical(workerResult->lowering_plan_rows, cast<int_t<>>(coordinatorPlanRows))) && php::identical(workerResult->lowering_step_rows, cast<int_t<>>(plan->step_count))) && php::identical(workerResult->lowering_work_refs, cast<int_t<>>(plan->work_ref_count))) && php::identical(workerResult->lowering_blocked_rows, cast<int_t<>>(plan->blocked_request_count))) && php::identical(workerResult->sidecar_rows, cast<int_t<>>(coordinatorSidecarRows))) && php::identical(workerResult->owner_rows, cast<int_t<>>(coordinatorPlanRows))) && php::identical(workerResult->published_rows, cast<int_t<>>(__latency_fn_lowering_plan_publication_published_row_count(requests, plan)))) && (workerResult->semantic_hash > static_cast<int_t<> >(0))) && php::identical(workerResult->semantic_hash, cast<int_t<>>(coordinatorSemanticHash)));
		__latency_fn_lowering_plan_record_backend_lowering_worker_handoff_metrics(report, workerInputs, workerResults, requests, plan, cast<int_t<std::uint32_t>>(coordinatorSemanticHash), bool_t(workerHandoffMatches));
	}
	bool_t workerCandidateReady = required_cast<bool_t>((((((productionMtEnabled && hasWorkerCandidate) && upstreamStorageLifetimeWorkerReady) && workerHandoffMatches) && outputsMatch) && (cast<int_t<>>(publishedRows) > static_cast<int_t<> >(0))));
	backendLoweringWorkerCandidateReady = bool_t(workerCandidateReady);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_publication_runs(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_publication_input_rows(), publication->row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_publication_output_rows(), reduction->output_row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_publication_published_rows(), publishedRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_publication_backend_request_rows(), requests->request_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_publication_backend_request_ready_rows(), requests->ready_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_publication_backend_request_blocked_rows(), requests->blocked_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_publication_lowering_plan_rows(), __latency_fn_lowering_plan_publication_plan_row_count(plan));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_publication_lowering_step_rows(), plan->step_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_publication_lowering_work_refs(), plan->work_ref_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_publication_lowering_blocked_rows(), plan->blocked_request_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_publication_metadata_commit_bytes(), __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(publication->row_count) * static_cast<int_t<> >(sizeof(PartitionReadinessRow)))));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_publication_simulated_first_input_order_sum(), firstSimulatedOutput->input_order_id);
	if (static_cast<bool>(php::condition_truthy(outputsMatch))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_publication_output_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_publication_output_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_publication_output_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_backend_lowering_publication_output_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	}
	__latency_fn_mt_publication_metrics_record_selected_publication_owner(report, string_t("backend_lowering_publication"), workerCandidateReady);
	__latency_fn_mt_publication_metrics_record_worker_gate(report, string_t("backend_lowering_publication"), string_t("worker_blocked_by_upstream_payload_source"), string_t("upstream_storage_lifetime_worker_ready"), string_t("upstream_storage_lifetime_coordinator_blocked"), string_t("worker_candidate_ready"), string_t("o3_measurement_required"), string_t("speedup_claim_blocked"), string_t("payload_copy_bytes"), hasWorkerCandidate, upstreamStorageLifetimeWorkerReady, workerCandidateReady);
}

}

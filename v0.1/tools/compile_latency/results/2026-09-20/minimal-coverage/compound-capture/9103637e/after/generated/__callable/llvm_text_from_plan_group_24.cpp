#include <scpp/lang/php.hpp>
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/BackendSinkBoundaryRow.hpp"
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/EmissionLLVMCompositeTextSnapshot.hpp"
#include "__types/EmissionLLVMWorkerInput.hpp"
#include "__types/EmissionLLVMWorkerResult.hpp"
#include "__types/FunctionBodyTextEmissionPreflightArtifact.hpp"
#include "__types/LoweringPlan.hpp"
#include "__types/PartitionMergeReductionArtifact.hpp"
#include "__types/PartitionMergeReductionRow.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_backend_sink_boundary_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_emission_llvm_publication_artifact.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_emission_llvm_publication_published_row_count.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_emission_llvm_publication_published_row_total.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_emission_llvm_worker_input_from_rows.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_first_worker_result.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_publication_llvm_text_nonempty_row_count.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_publication_sink_boundary_row_count.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_record_emission_llvm_publication_metrics__exec.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_record_emission_llvm_worker_handoff_metrics.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_semantic_hash_for_emission_llvm.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_blocked_row_count.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_ready_row_count.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_status_ready_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_worker_results.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_record_selected_publication_owner.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_record_worker_gate.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_first_output_row.hpp"
#include "__callable/__latency_fn_partition_merge_reductions_from_partition_readiness.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_finish.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_emission_llvm_snapshot_build.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_emission_llvm_worker_task.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_start.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_publication_emission_block_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_publication_emission_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_publication_emission_decision_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_publication_emission_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_publication_emission_value_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_publication_input_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_publication_llvm_preflight_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_publication_llvm_preflight_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_publication_llvm_preflight_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_publication_llvm_sink_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_publication_llvm_sink_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_publication_llvm_sink_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_publication_llvm_text_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_publication_llvm_text_nonempty_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_publication_metadata_commit_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_publication_output_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_publication_output_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_publication_output_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_publication_published_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_publication_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_publication_simulated_first_input_order_sum.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_production_mt_enabled.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_record_emission_llvm_publication_metrics__exec(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, shared_p<BackendRequestAuthorizationArtifact>& requests, shared_p<LoweringPlan> plan, BackendEmissionDecisionArtifact& emission, FunctionBodyTextEmissionPreflightArtifact preflightArtifact, const string_t& moduleText, shared_p<EmissionLLVMCompositeTextSnapshot> compositeSnapshot, int_t<> workerCount, bool_t upstreamBackendLoweringWorkerReady, bool_t& emissionLLVMWorkerCandidateReady) {
	emissionLLVMWorkerCandidateReady = bool_t(static_cast<bool_t>(false));
	shared_p<PartitionReadinessArtifact> publication = __latency_fn_llvm_text_from_plan_emission_llvm_publication_artifact(cast<int_t<std::uint32_t>>(ownerRunId), entrySymbol, emission, preflightArtifact);
	shared_p<PartitionReadinessArtifact> simulatedPublication = __latency_fn_llvm_text_from_plan_emission_llvm_publication_artifact(cast<int_t<std::uint32_t>>(ownerRunId), entrySymbol, emission, preflightArtifact);
	shared_p<PartitionMergeReductionArtifact> reduction = __latency_fn_partition_merge_reductions_from_partition_readiness(publication);
	shared_p<PartitionMergeReductionArtifact> simulatedReduction = __latency_fn_partition_merge_reductions_from_partition_readiness(simulatedPublication);
	PartitionMergeReductionRow firstSimulatedOutput = __latency_fn_partition_merge_reductions_first_output_row(simulatedReduction);
	bool_t outputsMatch = required_cast<bool_t>((php::identical(reduction->stable_output_hash, simulatedReduction->stable_output_hash) && php::identical(cast<int_t<>>(reduction->output_row_count), cast<int_t<>>(simulatedReduction->output_row_count))));
	int_t<std::uint32_t> publishedRows = required_cast<int_t<std::uint32_t>>(__latency_fn_llvm_text_from_plan_emission_llvm_publication_published_row_total(publication));
	bool_t hasWorkerCandidate = required_cast<bool_t>((workerCount > static_cast<int_t<> >(1)));
	bool_t workerHandoffMatches = required_cast<bool_t>(bool_t(static_cast<bool_t>(true)));
	bool_t productionMtEnabled = required_cast<bool_t>(__latency_fn_source_unit_frontend_scheduler_production_mt_enabled());
	if (static_cast<bool>(((productionMtEnabled && hasWorkerCandidate) && upstreamBackendLoweringWorkerReady))) {
		int_t<std::uint64_t> emissionLLVMSnapshotStarted = required_cast<int_t<std::uint64_t>>(__latency_fn_production_mt_heartbeat_start(report, __latency_fn_production_mt_heartbeat_stage_emission_llvm_snapshot_build(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(2))));
		vector_t<shared_p<EmissionLLVMWorkerInput>> workerInputs = required_cast<vector_t<shared_p<EmissionLLVMWorkerInput>>>(vector_t<shared_p<EmissionLLVMWorkerInput>>{__latency_fn_llvm_text_from_plan_emission_llvm_worker_input_from_rows(cast<int_t<std::uint32_t>>(ownerRunId), entrySymbol, requests, plan, compositeSnapshot)});
		__latency_fn_production_mt_heartbeat_finish(report, __latency_fn_production_mt_heartbeat_stage_emission_llvm_snapshot_build(), emissionLLVMSnapshotStarted, __latency_fn_structure_row_ids_uint32_from_int(php::count(workerInputs)));
		int_t<std::uint64_t> emissionLLVMWorkerTaskStarted = required_cast<int_t<std::uint64_t>>(__latency_fn_production_mt_heartbeat_start(report, __latency_fn_production_mt_heartbeat_stage_emission_llvm_worker_task(), __latency_fn_structure_row_ids_uint32_from_int(php::count(workerInputs))));
		vector_t<shared_p<EmissionLLVMWorkerResult>> workerResults = required_cast<vector_t<shared_p<EmissionLLVMWorkerResult>>>(__latency_fn_llvm_text_from_plan_worker_results(workerInputs, workerCount));
		__latency_fn_production_mt_heartbeat_finish(report, __latency_fn_production_mt_heartbeat_stage_emission_llvm_worker_task(), emissionLLVMWorkerTaskStarted, __latency_fn_structure_row_ids_uint32_from_int(php::count(workerResults)));
		shared_p<EmissionLLVMWorkerResult> workerResult = __latency_fn_llvm_text_from_plan_first_worker_result(workerResults);
		int_t<std::uint32_t> coordinatorSemanticHash = required_cast<int_t<std::uint32_t>>(__latency_fn_llvm_text_from_plan_semantic_hash_for_emission_llvm(requests, emission, preflightArtifact, moduleText));
		workerHandoffMatches = ((((((((((((((((php::identical(php::count(workerResults), static_cast<int_t<> >(1)) && php::identical(workerResult->decision_rows, cast<int_t<>>(emission->decision_count))) && php::identical(workerResult->ready_rows, cast<int_t<>>(emission->ready_count))) && php::identical(workerResult->blocked_rows, cast<int_t<>>(emission->blocked_count))) && php::identical(workerResult->value_rows, cast<int_t<>>(emission->value_count))) && php::identical(workerResult->block_rows, cast<int_t<>>(emission->block_count))) && php::identical(workerResult->preflight_rows, cast<int_t<>>(preflightArtifact->row_count))) && php::identical(workerResult->preflight_ready_rows, cast<int_t<>>(preflightArtifact->ready_count))) && php::identical(workerResult->preflight_blocked_rows, cast<int_t<>>(preflightArtifact->blocked_count))) && php::identical(workerResult->sink_rows, cast<int_t<>>(__latency_fn_llvm_text_from_plan_publication_sink_boundary_row_count(emission)))) && php::identical(workerResult->sink_ready_rows, __latency_fn_llvm_text_from_plan_sink_ready_row_count(emission))) && php::identical(workerResult->sink_blocked_rows, __latency_fn_llvm_text_from_plan_sink_blocked_row_count(emission))) && php::identical(workerResult->text_nonempty_rows, cast<int_t<>>(__latency_fn_llvm_text_from_plan_publication_llvm_text_nonempty_row_count(moduleText)))) && php::identical(workerResult->text_bytes, str::length(moduleText))) && php::identical(workerResult->published_rows, cast<int_t<>>(__latency_fn_llvm_text_from_plan_emission_llvm_publication_published_row_count(emission, preflightArtifact)))) && (workerResult->semantic_hash > static_cast<int_t<> >(0))) && php::identical(workerResult->semantic_hash, cast<int_t<>>(coordinatorSemanticHash)));
		__latency_fn_llvm_text_from_plan_record_emission_llvm_worker_handoff_metrics(report, workerInputs, workerResults, emission, preflightArtifact, moduleText, cast<int_t<std::uint32_t>>(coordinatorSemanticHash), bool_t(workerHandoffMatches));
	}
	bool_t workerCandidateReady = required_cast<bool_t>((((((productionMtEnabled && hasWorkerCandidate) && upstreamBackendLoweringWorkerReady) && workerHandoffMatches) && outputsMatch) && (cast<int_t<>>(publishedRows) > static_cast<int_t<> >(0))));
	emissionLLVMWorkerCandidateReady = bool_t(workerCandidateReady);
	BackendSinkBoundaryRow sink = __latency_fn_llvm_text_from_plan_backend_sink_boundary_from_emission(emission);
	int_t<std::uint32_t> sinkRows = required_cast<int_t<std::uint32_t>>(__latency_fn_llvm_text_from_plan_publication_sink_boundary_row_count(emission));
	int_t<std::uint32_t> sinkReadyRows = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	int_t<std::uint32_t> sinkBlockedRows = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	if (static_cast<bool>((cast<int_t<>>(sinkRows) > static_cast<int_t<> >(0)))) {
		if (static_cast<bool>(php::identical(cast<int_t<>>(sink->status_id), cast<int_t<>>(__latency_fn_llvm_text_from_plan_status_ready_id())))) {
			sinkReadyRows = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
		}
		else {
			sinkBlockedRows = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
		}
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_runs(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_input_rows(), publication->row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_output_rows(), reduction->output_row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_published_rows(), publishedRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_emission_decision_rows(), emission->decision_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_emission_ready_rows(), emission->ready_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_emission_blocked_rows(), emission->blocked_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_emission_value_rows(), emission->value_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_emission_block_rows(), emission->block_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_llvm_sink_rows(), sinkRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_llvm_sink_ready_rows(), sinkReadyRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_llvm_sink_blocked_rows(), sinkBlockedRows);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_llvm_preflight_rows(), preflightArtifact->row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_llvm_preflight_ready_rows(), preflightArtifact->ready_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_llvm_preflight_blocked_rows(), preflightArtifact->blocked_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_llvm_text_nonempty_rows(), __latency_fn_llvm_text_from_plan_publication_llvm_text_nonempty_row_count(moduleText));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_llvm_text_bytes(), __latency_fn_structure_row_ids_uint32_from_int(str::length(moduleText)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_metadata_commit_bytes(), __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(publication->row_count) * static_cast<int_t<> >(sizeof(PartitionReadinessRow)))));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_simulated_first_input_order_sum(), firstSimulatedOutput->input_order_id);
	if (static_cast<bool>(php::condition_truthy(outputsMatch))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_output_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_output_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_output_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_publication_output_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	}
	__latency_fn_mt_publication_metrics_record_selected_publication_owner(report, string_t("emission_llvm_publication"), workerCandidateReady);
	__latency_fn_mt_publication_metrics_record_worker_gate(report, string_t("emission_llvm_publication"), string_t("worker_blocked_by_upstream_payload_source"), string_t("upstream_backend_lowering_worker_ready"), string_t("upstream_backend_lowering_coordinator_blocked"), string_t("worker_candidate_ready"), string_t("o3_measurement_required"), string_t("speedup_claim_blocked"), string_t("payload_copy_bytes"), hasWorkerCandidate, upstreamBackendLoweringWorkerReady, workerCandidateReady);
}

}

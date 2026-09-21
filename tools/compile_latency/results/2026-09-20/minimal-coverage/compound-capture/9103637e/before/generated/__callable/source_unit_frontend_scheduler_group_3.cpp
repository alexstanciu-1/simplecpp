#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/DeterministicWorkOrderArtifact.hpp"
#include "__types/SchedulerApiArtifact.hpp"
#include "__types/SchedulerSessionRow.hpp"
#include "__types/SchedulerTaskRow.hpp"
#include "__types/SchedulerWorkerRow.hpp"
#include "__types/SourceUnitFrontendDispatchDecisionRow.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_blocked_by_real_o3_evidence.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_default_selection_ready.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_nonclaim_o3_skip_consumed.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_o3_measurement_required.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_o3_skip_used_as_promotion_evidence.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_production_worker_selection_ready.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_real_worker_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_routine_o3_skipped.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_selected_one_thread.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_source_inputs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_speedup_claim_blocked.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_speedup_claim_ready.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_worker_candidate_rows.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_model_one_thread_coordinator_id.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_model_worker_shadow_handoff_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_decision_status_blocked_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_decision_status_required_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_promotion_nonclaim_o3_skip_precheck_enabled.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_promotion_real_o3_evidence_blocker_precheck_enabled.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_record_promotion_precheck_metrics.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_scheduler_metadata_commit_bytes.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_uint32_from_int.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_completed_tasks.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_metadata_commit_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_output_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_output_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_payload_descriptor_inputs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_payload_descriptor_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_real_payload_install_blocked.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_real_payload_install_ready.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_requested_workers.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_tasks.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_worker_rows.hpp"
#include "__callable/__latency_fn_scheduler_api_one_thread_artifact_from_work_order.hpp"
#include "__callable/__latency_fn_scheduler_api_worker_shadow_artifact_from_work_order.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_record_worker_shadow_handoff_metrics.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_scheduler_metadata_commit_bytes.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_work_order_from_source_units.hpp"
namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
void __latency_fn_source_unit_frontend_scheduler_record_promotion_precheck_metrics(shared_p<CompilerProjectRunReport>& report, SourceUnitFrontendDispatchDecisionRow row) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::record_promotion_precheck_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[26]);
	if (static_cast<bool>((!__latency_fn_source_unit_frontend_scheduler_promotion_nonclaim_o3_skip_precheck_enabled()))) {
		return;
	}
	bool_t hasWorkerCandidate = required_cast<bool_t>(php::identical(cast<int_t<>>(row->candidate_execution_model_id), cast<int_t<>>(__latency_fn_scheduler_api_execution_model_worker_shadow_handoff_id())));
	bool_t selectedOneThread = required_cast<bool_t>(php::identical(cast<int_t<>>(row->selected_execution_model_id), cast<int_t<>>(__latency_fn_scheduler_api_execution_model_one_thread_coordinator_id())));
	bool_t realWorkerSelected = required_cast<bool_t>((!selectedOneThread));
	bool_t o3MeasurementRequired = required_cast<bool_t>(php::identical(cast<int_t<>>(row->measurement_status_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_decision_status_required_id())));
	bool_t speedupClaimBlocked = required_cast<bool_t>(php::identical(cast<int_t<>>(row->speedup_claim_status_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_decision_status_blocked_id())));
	bool_t nonclaimSkipConsumed = required_cast<bool_t>((hasWorkerCandidate && o3MeasurementRequired));
	bool_t blockedByRealO3Evidence = required_cast<bool_t>((nonclaimSkipConsumed && __latency_fn_source_unit_frontend_scheduler_promotion_real_o3_evidence_blocker_precheck_enabled()));
	bool_t defaultSelectionReady = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
	bool_t productionWorkerSelectionReady = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_rows(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_source_inputs(), row->source_unit_count);
	if (static_cast<bool>(php::condition_truthy(hasWorkerCandidate))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_worker_candidate_rows(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_worker_candidate_rows(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::condition_truthy(nonclaimSkipConsumed))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_nonclaim_o3_skip_consumed(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_routine_o3_skipped(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_nonclaim_o3_skip_consumed(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_routine_o3_skipped(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_o3_skip_used_as_promotion_evidence(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	if (static_cast<bool>(php::condition_truthy(selectedOneThread))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_selected_one_thread(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_selected_one_thread(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::condition_truthy(realWorkerSelected))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_real_worker_selected(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_real_worker_selected(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::condition_truthy(defaultSelectionReady))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_default_selection_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_default_selection_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::condition_truthy(productionWorkerSelectionReady))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_production_worker_selection_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_production_worker_selection_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::condition_truthy(o3MeasurementRequired))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_o3_measurement_required(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_o3_measurement_required(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::condition_truthy(defaultSelectionReady))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_speedup_claim_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_speedup_claim_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::condition_truthy(speedupClaimBlocked))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_speedup_claim_blocked(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_speedup_claim_blocked(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::condition_truthy(blockedByRealO3Evidence))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_blocked_by_real_o3_evidence(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_blocked_by_real_o3_evidence(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_promotion_precheck_payload_copy_bytes(), row->payload_copy_bytes);
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_source_unit_frontend_scheduler_scheduler_metadata_commit_bytes(shared_p<SchedulerApiArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::scheduler_metadata_commit_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[27]);
	int_t<> bytes = required_cast<int_t<>>((((cast<int_t<>>(artifact->session_count) * static_cast<int_t<> >(sizeof(SchedulerSessionRow))) + (cast<int_t<>>(artifact->worker_count) * static_cast<int_t<> >(sizeof(SchedulerWorkerRow)))) + (cast<int_t<>>(artifact->task_count) * static_cast<int_t<> >(sizeof(SchedulerTaskRow)))));
	return __latency_fn_source_unit_frontend_scheduler_uint32_from_int(bytes);
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
void __latency_fn_source_unit_frontend_scheduler_record_worker_shadow_handoff_metrics(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits, int_t<std::uint32_t> ownerRunId, int_t<> workerCount, int_t<> payloadTableCount, int_t<> descriptorCount, bool_t handoffMatches) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::record_worker_shadow_handoff_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[28]);
	if (static_cast<bool>((cast<int_t<>>(sourceUnits->source_unit_count) <= static_cast<int_t<> >(0)))) {
		return;
	}
	shared_p<DeterministicWorkOrderArtifact> sequential = __latency_fn_source_unit_frontend_scheduler_work_order_from_source_units(sourceUnits, cast<int_t<std::uint32_t>>(ownerRunId), bool_t(static_cast<bool_t>(false)));
	shared_p<SchedulerApiArtifact> oneThread = __latency_fn_scheduler_api_one_thread_artifact_from_work_order(sequential, ownerRunId);
	shared_p<SchedulerApiArtifact> workerShadow = __latency_fn_scheduler_api_worker_shadow_artifact_from_work_order(sequential, ownerRunId, workerCount);
	bool_t outputsMatch = required_cast<bool_t>(((handoffMatches && php::identical(cast<int_t<>>(workerShadow->task_count), cast<int_t<>>(oneThread->task_count))) && php::identical(workerShadow->stable_output_hash, oneThread->stable_output_hash)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_runs(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_requested_workers(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(workerCount));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_worker_rows(), workerShadow->worker_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_tasks(), workerShadow->task_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_completed_tasks(), workerShadow->task_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_payload_descriptor_inputs(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(payloadTableCount));
	if (static_cast<bool>((handoffMatches && php::identical(descriptorCount, payloadTableCount)))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_payload_descriptor_matches(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(descriptorCount));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_payload_descriptor_matches(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::condition_truthy(outputsMatch))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_output_matches(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_output_mismatches(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_output_matches(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_output_mismatches(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_metadata_commit_bytes(), __latency_fn_source_unit_frontend_scheduler_scheduler_metadata_commit_bytes(workerShadow));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_payload_copy_bytes(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	if (static_cast<bool>(php::condition_truthy(outputsMatch))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_real_payload_install_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_real_payload_install_blocked(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_real_payload_install_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_shadow_real_payload_install_blocked(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
}

}

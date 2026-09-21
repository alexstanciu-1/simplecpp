#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/SourceUnitFrontendDispatchDecisionRow.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_scheduler_payload_handoff_blocked.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_scheduler_payload_handoff_ready.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_record_payload_handoff_metrics.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_uint32_from_int.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_o3_measurement_required.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_one_thread_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_real_worker_dispatch_ready.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_real_worker_payload_blocked.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_real_worker_payload_ready.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_single_source_thresholds.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_source_inputs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_worker_shadow_metadata_candidates.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_record_dispatch_selection_policy_metrics.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_o3_measurement_required_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_replacement_missing_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_run_value_result_boundary_id.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_model_one_thread_coordinator_id.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_model_worker_shadow_handoff_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_decision_status_blocked_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_decision_status_not_required_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_decision_status_ready_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_decision_status_required_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_dispatch_decision_row.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_installed_payload_source_worker_owned_segments_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_trial_execution_scope_not_selected_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_trial_execution_scope_worker_payload_install_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_trial_execution_scope_worker_shadow_descriptor_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_uint32_from_int.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_full_payload_worker_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_installed_payload_source_coordinator.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_installed_payload_source_worker.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_o3_measurement_required.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_physical_payload_blocked.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_physical_payload_ready.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_real_worker_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_real_worker_trial_authorized.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_real_worker_trial_candidates.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_real_worker_trial_measurement_blocked.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_real_worker_trial_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_selected_one_thread.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_speedup_claim_blocked.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_trial_descriptor_scope_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_trial_full_payload_blocked_reason_production_replacement_missing.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_trial_full_payload_install_blocked.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_trial_full_payload_install_ready.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_trial_full_payload_scope_requested.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_trial_full_payload_scope_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_trial_payload_install_deferred.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_worker_shadow_candidates.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_o3_measurement_required_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_replacement_missing_id.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_model_one_thread_coordinator_id.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_model_worker_shadow_handoff_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_decision_status_blocked_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_decision_status_ready_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_decision_status_required_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_installed_payload_source_worker_owned_segments_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_record_dispatch_decision_metrics.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_trial_execution_scope_worker_payload_install_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_trial_execution_scope_worker_shadow_descriptor_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
void __latency_fn_source_unit_frontend_scheduler_record_payload_handoff_metrics(shared_p<CompilerProjectRunReport>& report, bool_t hasWorkerCandidate, bool_t physicalPayloadReady) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::record_payload_handoff_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[22]);
	if (static_cast<bool>((hasWorkerCandidate && physicalPayloadReady))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_payload_handoff_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_payload_handoff_blocked(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		if (static_cast<bool>(php::condition_truthy(hasWorkerCandidate))) {
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_payload_handoff_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_payload_handoff_blocked(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
		}
		else {
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_payload_handoff_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_payload_handoff_blocked(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
		}
	}
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
void __latency_fn_source_unit_frontend_scheduler_record_dispatch_selection_policy_metrics(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits, int_t<> workerCount, bool_t physicalPayloadReady) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::record_dispatch_selection_policy_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[23]);
	int_t<> sourceCount = required_cast<int_t<>>(cast<int_t<>>(sourceUnits->source_unit_count));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_runs(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_source_inputs(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(sourceCount));
	if (static_cast<bool>((workerCount <= static_cast<int_t<> >(1)))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_one_thread_selected(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_single_source_thresholds(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_worker_shadow_metadata_candidates(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_real_worker_payload_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_real_worker_payload_blocked(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_real_worker_dispatch_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_o3_measurement_required(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_one_thread_selected(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_single_source_thresholds(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_worker_shadow_metadata_candidates(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
		if (static_cast<bool>(php::condition_truthy(physicalPayloadReady))) {
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_real_worker_payload_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_real_worker_payload_blocked(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
		}
		else {
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_real_worker_payload_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_real_worker_payload_blocked(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
		}
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_real_worker_dispatch_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_o3_measurement_required(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_selection_policy_payload_copy_bytes(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
SourceUnitFrontendDispatchDecisionRow __latency_fn_source_unit_frontend_scheduler_dispatch_decision_row(shared_p<SourceUnitTable> sourceUnits, int_t<std::uint32_t> ownerRunId, int_t<> workerCount, bool_t physicalPayloadReady, bool_t productionInstallReady, bool_t trialAuthorized, bool_t fullPayloadTrialRequested) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::dispatch_decision_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[24]);
	bool_t hasWorkerCandidate = required_cast<bool_t>((workerCount > static_cast<int_t<> >(1)));
	SourceUnitFrontendDispatchDecisionRow row = SourceUnitFrontendDispatchDecisionRow{};
	row->decision_row_id = ownerRunId;
	row->owner_run_id = ownerRunId;
	row->source_unit_count = __latency_fn_source_unit_frontend_scheduler_uint32_from_int(cast<int_t<>>(sourceUnits->source_unit_count));
	row->requested_worker_count = __latency_fn_source_unit_frontend_scheduler_uint32_from_int(workerCount);
	row->scheduler_task_count = row->source_unit_count;
	row->payload_handle_input_count = row->source_unit_count;
	row->payload_copy_bytes = __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0));
	row->selected_execution_model_id = __latency_fn_scheduler_api_execution_model_one_thread_coordinator_id();
	row->real_worker_trial_status_id = __latency_fn_source_unit_frontend_scheduler_decision_status_not_required_id();
	row->trial_authorization_status_id = __latency_fn_source_unit_frontend_scheduler_decision_status_not_required_id();
	row->trial_execution_scope_id = __latency_fn_source_unit_frontend_scheduler_trial_execution_scope_not_selected_id();
	row->requested_trial_execution_scope_id = __latency_fn_source_unit_frontend_scheduler_trial_execution_scope_not_selected_id();
	row->installed_payload_source_id = __latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id();
	row->trial_blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_none_id();
	if (static_cast<bool>(php::condition_truthy(hasWorkerCandidate))) {
		row->candidate_execution_model_id = __latency_fn_scheduler_api_execution_model_worker_shadow_handoff_id();
		row->descriptor_dispatch_status_id = __latency_fn_source_unit_frontend_scheduler_decision_status_ready_id();
		if (static_cast<bool>(php::condition_truthy(physicalPayloadReady))) {
			row->physical_payload_status_id = __latency_fn_source_unit_frontend_scheduler_decision_status_ready_id();
			row->real_worker_trial_status_id = __latency_fn_source_unit_frontend_scheduler_decision_status_ready_id();
		}
		else {
			row->physical_payload_status_id = __latency_fn_source_unit_frontend_scheduler_decision_status_blocked_id();
			row->real_worker_trial_status_id = __latency_fn_source_unit_frontend_scheduler_decision_status_blocked_id();
		}
		row->measurement_status_id = __latency_fn_source_unit_frontend_scheduler_decision_status_required_id();
		row->speedup_claim_status_id = __latency_fn_source_unit_frontend_scheduler_decision_status_blocked_id();
		if (static_cast<bool>((physicalPayloadReady && fullPayloadTrialRequested))) {
			row->requested_trial_execution_scope_id = __latency_fn_source_unit_frontend_scheduler_trial_execution_scope_worker_payload_install_id();
			if (static_cast<bool>(php::condition_truthy(productionInstallReady))) {
				row->selected_execution_model_id = __latency_fn_scheduler_api_execution_model_worker_shadow_handoff_id();
				row->trial_authorization_status_id = __latency_fn_source_unit_frontend_scheduler_decision_status_ready_id();
				row->trial_execution_scope_id = __latency_fn_source_unit_frontend_scheduler_trial_execution_scope_worker_payload_install_id();
				row->installed_payload_source_id = __latency_fn_source_unit_frontend_scheduler_installed_payload_source_worker_owned_segments_id();
				row->trial_blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_none_id();
				row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_o3_measurement_required_id();
			}
			else {
				row->trial_authorization_status_id = __latency_fn_source_unit_frontend_scheduler_decision_status_blocked_id();
				row->trial_blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_replacement_missing_id();
				row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_replacement_missing_id();
			}
		}
		else {
			if (static_cast<bool>((physicalPayloadReady && trialAuthorized))) {
				row->selected_execution_model_id = __latency_fn_scheduler_api_execution_model_worker_shadow_handoff_id();
				row->trial_authorization_status_id = __latency_fn_source_unit_frontend_scheduler_decision_status_ready_id();
				row->trial_execution_scope_id = __latency_fn_source_unit_frontend_scheduler_trial_execution_scope_worker_shadow_descriptor_id();
				row->requested_trial_execution_scope_id = __latency_fn_source_unit_frontend_scheduler_trial_execution_scope_worker_shadow_descriptor_id();
				row->trial_blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_none_id();
				row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_o3_measurement_required_id();
			}
			else {
				if (static_cast<bool>(php::condition_truthy(physicalPayloadReady))) {
					row->trial_authorization_status_id = __latency_fn_source_unit_frontend_scheduler_decision_status_blocked_id();
					row->trial_blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_o3_measurement_required_id();
					row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_o3_measurement_required_id();
				}
				else {
					row->trial_authorization_status_id = __latency_fn_source_unit_frontend_scheduler_decision_status_blocked_id();
					row->trial_blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_run_value_result_boundary_id();
					row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_run_value_result_boundary_id();
				}
			}
		}
	}
	else {
		row->candidate_execution_model_id = __latency_fn_scheduler_api_execution_model_one_thread_coordinator_id();
		row->descriptor_dispatch_status_id = __latency_fn_source_unit_frontend_scheduler_decision_status_not_required_id();
		row->physical_payload_status_id = __latency_fn_source_unit_frontend_scheduler_decision_status_not_required_id();
		row->measurement_status_id = __latency_fn_source_unit_frontend_scheduler_decision_status_not_required_id();
		row->speedup_claim_status_id = __latency_fn_source_unit_frontend_scheduler_decision_status_not_required_id();
		row->real_worker_trial_status_id = __latency_fn_source_unit_frontend_scheduler_decision_status_not_required_id();
		row->trial_authorization_status_id = __latency_fn_source_unit_frontend_scheduler_decision_status_not_required_id();
		row->trial_execution_scope_id = __latency_fn_source_unit_frontend_scheduler_trial_execution_scope_not_selected_id();
		row->requested_trial_execution_scope_id = __latency_fn_source_unit_frontend_scheduler_trial_execution_scope_not_selected_id();
		row->installed_payload_source_id = __latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id();
		row->trial_blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_none_id();
		row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_none_id();
	}
	return row;
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
void __latency_fn_source_unit_frontend_scheduler_record_dispatch_decision_metrics(shared_p<CompilerProjectRunReport>& report, SourceUnitFrontendDispatchDecisionRow row) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::record_dispatch_decision_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[25]);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_rows(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->selected_execution_model_id), cast<int_t<>>(__latency_fn_scheduler_api_execution_model_one_thread_coordinator_id())))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_selected_one_thread(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_real_worker_selected(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_selected_one_thread(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_real_worker_selected(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>((((php::not_identical(cast<int_t<>>(row->selected_execution_model_id), cast<int_t<>>(__latency_fn_scheduler_api_execution_model_one_thread_coordinator_id())) && php::identical(cast<int_t<>>(row->trial_execution_scope_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_trial_execution_scope_worker_payload_install_id()))) && php::identical(cast<int_t<>>(row->installed_payload_source_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_installed_payload_source_worker_owned_segments_id()))) && php::identical(cast<int_t<>>(row->payload_copy_bytes), static_cast<int_t<> >(0))))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_full_payload_worker_selected(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_full_payload_worker_selected(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->candidate_execution_model_id), cast<int_t<>>(__latency_fn_scheduler_api_execution_model_worker_shadow_handoff_id())))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_worker_shadow_candidates(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_worker_shadow_candidates(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->real_worker_trial_status_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_decision_status_ready_id())))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_real_worker_trial_candidates(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_real_worker_trial_candidates(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->trial_authorization_status_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_decision_status_ready_id())))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_real_worker_trial_authorized(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_real_worker_trial_authorized(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->trial_blocked_reason_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_o3_measurement_required_id())))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_real_worker_trial_measurement_blocked(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_real_worker_trial_measurement_blocked(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(row->selected_execution_model_id), cast<int_t<>>(__latency_fn_scheduler_api_execution_model_one_thread_coordinator_id())) && php::identical(cast<int_t<>>(row->trial_authorization_status_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_decision_status_ready_id()))))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_real_worker_trial_selected(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_real_worker_trial_selected(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->trial_execution_scope_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_trial_execution_scope_worker_shadow_descriptor_id())))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_trial_descriptor_scope_selected(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_trial_descriptor_scope_selected(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->trial_execution_scope_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_trial_execution_scope_worker_payload_install_id())))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_trial_full_payload_scope_selected(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_trial_full_payload_scope_selected(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->requested_trial_execution_scope_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_trial_execution_scope_worker_payload_install_id())))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_trial_full_payload_scope_requested(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_trial_full_payload_scope_requested(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(row->requested_trial_execution_scope_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_trial_execution_scope_worker_payload_install_id())) && php::identical(cast<int_t<>>(row->installed_payload_source_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_installed_payload_source_worker_owned_segments_id()))))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_trial_full_payload_install_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_trial_full_payload_install_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(row->requested_trial_execution_scope_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_trial_execution_scope_worker_payload_install_id())) && php::not_identical(cast<int_t<>>(row->installed_payload_source_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_installed_payload_source_worker_owned_segments_id()))))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_trial_full_payload_install_blocked(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_trial_full_payload_install_blocked(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(row->requested_trial_execution_scope_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_trial_execution_scope_worker_payload_install_id())) && php::identical(cast<int_t<>>(row->trial_blocked_reason_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_replacement_missing_id()))))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_trial_full_payload_blocked_reason_production_replacement_missing(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_trial_full_payload_blocked_reason_production_replacement_missing(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->installed_payload_source_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id())))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_installed_payload_source_coordinator(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_installed_payload_source_coordinator(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->installed_payload_source_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_installed_payload_source_worker_owned_segments_id())))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_installed_payload_source_worker(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_installed_payload_source_worker(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(((php::not_identical(cast<int_t<>>(row->selected_execution_model_id), cast<int_t<>>(__latency_fn_scheduler_api_execution_model_one_thread_coordinator_id())) && php::identical(cast<int_t<>>(row->trial_execution_scope_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_trial_execution_scope_worker_shadow_descriptor_id()))) && php::identical(cast<int_t<>>(row->installed_payload_source_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id()))))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_trial_payload_install_deferred(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_trial_payload_install_deferred(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->physical_payload_status_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_decision_status_blocked_id())))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_physical_payload_blocked(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_physical_payload_blocked(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->physical_payload_status_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_decision_status_ready_id())))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_physical_payload_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_physical_payload_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->measurement_status_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_decision_status_required_id())))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_o3_measurement_required(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_o3_measurement_required(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->speedup_claim_status_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_decision_status_blocked_id())))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_speedup_claim_blocked(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_speedup_claim_blocked(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_decision_payload_copy_bytes(), row->payload_copy_bytes);
}

}

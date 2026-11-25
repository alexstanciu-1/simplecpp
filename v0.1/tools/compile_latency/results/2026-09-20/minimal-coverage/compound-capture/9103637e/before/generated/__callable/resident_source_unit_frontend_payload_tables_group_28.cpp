#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PartitionMergeReductionArtifact.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/ResidentSourceUnitFrontendPayloadAdoptionRow.hpp"
#include "__types/ResidentSourceUnitFrontendPayloadTableRow.hpp"
#include "__types/SourceUnitFrontendWorkerActivationGateRow.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_candidate_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_descriptor_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_next_unblock_task_owned_segment_handles.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_not_required_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_o3_measurement_required_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_payload_blocked_segments.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_payload_ready_segments.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_payload_transfer_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_payload_transfer_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_publication_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_published_payload_segments.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_required_payload_segments.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_speedup_claim_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_task_boundary_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_task_boundary_ready_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_next_unblock_task_owned_segment_handles_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_record_worker_activation_gate_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_scheduler_api_execution_model_worker_shadow_handoff_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_decision_status_blocked_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_decision_status_ready_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_decision_status_required_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_adoption_payload_copy_byte_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_adoption_published_payload_segment_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_parser_error_total.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_coordinator_install_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_frontend_node_segments.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_metadata_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_metadata_commit_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_metadata_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_output_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_output_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_payload_handle_inputs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_real_payload_install_blocked.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_real_payload_install_ready.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_scheduler_task_inputs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_token_segments.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_frontend_node_segment_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_published_row_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_record_worker_payload_install_plan_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_token_segment_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
void __latency_fn_resident_source_unit_frontend_payload_tables_record_worker_activation_gate_metrics(shared_p<CompilerProjectRunReport>& report, SourceUnitFrontendWorkerActivationGateRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::record_worker_activation_gate_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[231]);
	bool_t hasWorkerCandidate = required_cast<bool_t>(php::identical(cast<int_t<>>(row->candidate_execution_model_id), cast<int_t<>>(__latency_fn_scheduler_api_execution_model_worker_shadow_handoff_id())));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::condition_truthy(hasWorkerCandidate))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_candidate_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_not_required_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_candidate_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_not_required_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->gate_status_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_decision_status_ready_id())))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_blocked_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->gate_status_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_decision_status_blocked_id())))) {
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_blocked_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
		}
		else {
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_blocked_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
		}
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->descriptor_status_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_decision_status_ready_id())))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_descriptor_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_descriptor_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->publication_status_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_decision_status_ready_id())))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_publication_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_publication_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->payload_transfer_status_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_decision_status_ready_id())))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_payload_transfer_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_payload_transfer_blocked_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->payload_transfer_status_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_decision_status_blocked_id())))) {
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_payload_transfer_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_payload_transfer_blocked_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
		}
		else {
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_payload_transfer_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_payload_transfer_blocked_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
		}
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->task_boundary_status_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_decision_status_ready_id())))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_task_boundary_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_task_boundary_blocked_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->task_boundary_status_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_decision_status_blocked_id())))) {
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_task_boundary_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_task_boundary_blocked_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
		}
		else {
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_task_boundary_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
			__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_task_boundary_blocked_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
		}
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->measurement_status_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_decision_status_required_id())))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_o3_measurement_required_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_o3_measurement_required_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->speedup_claim_status_id), cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_decision_status_blocked_id())))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_speedup_claim_blocked_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_speedup_claim_blocked_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->next_unblock_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_next_unblock_task_owned_segment_handles_id())))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_next_unblock_task_owned_segment_handles(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_next_unblock_task_owned_segment_handles(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_required_payload_segments(), row->required_payload_segment_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_payload_ready_segments(), row->payload_ready_segment_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_payload_blocked_segments(), row->payload_blocked_segment_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_published_payload_segments(), row->published_payload_segment_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_activation_gate_payload_copy_bytes(), row->payload_copy_bytes);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_adoption_payload_copy_byte_total(vector_t<ResidentSourceUnitFrontendPayloadAdoptionRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::adoption_payload_copy_byte_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[232]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->payload_copy_bytes));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_adoption_published_payload_segment_total(vector_t<ResidentSourceUnitFrontendPayloadAdoptionRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::adoption_published_payload_segment_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[233]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->published_row_count));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_parser_error_total(vector_t<ResidentSourceUnitFrontendPayloadTableRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::parser_error_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[234]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->parser_error_count));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
void __latency_fn_resident_source_unit_frontend_payload_tables_record_worker_payload_install_plan_metrics(shared_p<CompilerProjectRunReport>& report, vector_t<ResidentSourceUnitFrontendPayloadTableRow>& workerPayloadTables, shared_p<PartitionReadinessArtifact> workerPublication, shared_p<PartitionMergeReductionArtifact> sequentialReduction, shared_p<PartitionMergeReductionArtifact> workerReduction, bool_t handoffMatches) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::record_worker_payload_install_plan_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[235]);
	if (static_cast<bool>((php::count(workerPayloadTables) <= static_cast<int_t<> >(0)))) {
		return;
	}
	bool_t outputsMatch = required_cast<bool_t>(((handoffMatches && php::identical(workerReduction->stable_output_hash, sequentialReduction->stable_output_hash)) && php::identical(cast<int_t<>>(workerReduction->output_row_count), cast<int_t<>>(sequentialReduction->output_row_count))));
	int_t<> readyRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> blockedRows = required_cast<int_t<>>(php::count(workerPayloadTables));
	if (static_cast<bool>(php::condition_truthy(outputsMatch))) {
		readyRows = php::count(workerPayloadTables);
		blockedRows = static_cast<int_t<> >(0);
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_runs(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_scheduler_task_inputs(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(php::count(workerPayloadTables)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_payload_handle_inputs(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(php::count(workerPayloadTables)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_coordinator_install_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_published_row_total(workerPublication)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_metadata_ready_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(readyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_metadata_blocked_rows(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(blockedRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_token_segments(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_token_segment_total(workerPayloadTables)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_frontend_node_segments(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_frontend_node_segment_total(workerPayloadTables)));
	if (static_cast<bool>(php::condition_truthy(outputsMatch))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_output_matches(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_output_mismatches(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_output_matches(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_output_mismatches(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)));
	}
	int_t<> metadataCommitBytes = required_cast<int_t<>>(((php::count(workerPayloadTables) * static_cast<int_t<> >(sizeof(ResidentSourceUnitFrontendPayloadTableRow))) + (cast<int_t<>>(workerPublication->row_count) * static_cast<int_t<> >(sizeof(PartitionReadinessRow)))));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_metadata_commit_bytes(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(metadataCommitBytes));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_payload_copy_bytes(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_real_payload_install_ready(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(readyRows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_worker_payload_install_plan_real_payload_install_blocked(), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(blockedRows));
}

}

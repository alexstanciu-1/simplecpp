#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/DeterministicWorkOrderArtifact.hpp"
#include "__types/DeterministicWorkOrderRow.hpp"
#include "__types/SchedulerApiArtifact.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_trial_execution_scope_worker_shadow_descriptor_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_trial_execution_scope_worker_payload_install_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_installed_payload_source_worker_owned_segments_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_promotion_nonclaim_o3_skip_precheck_enabled.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_promotion_real_o3_evidence_blocker_precheck_enabled.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_row.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_work_kind_source_unit_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_source_work.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_append_row.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_execution_model_sequential_id.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_execution_model_simulated_partitions_id.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_finalize_artifact.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_new_artifact.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_source_work.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_work_order_from_source_units.hpp"
#include "__callable/__latency_fn_deterministic_work_ordering_first_stored_row.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_scheduler_descriptor_eval_ready.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_dispatch_blocked.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_dispatch_ready.hpp"
#include "__callable/__latency_fn_scheduler_api_one_thread_artifact_from_work_order.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_add_u32.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_record_dispatch_plan_metrics.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_record_report.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_work_order_from_source_units.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_plan_one_thread_fallbacks.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_plan_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_plan_payload_handle_inputs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_plan_real_worker_candidates.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_plan_requested_workers.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_plan_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_frontend_dispatch_plan_tasks.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_record_dispatch_plan_metrics.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_worker_count_for_sources.hpp"
namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_frontend_scheduler_trial_execution_scope_worker_shadow_descriptor_id() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::trial_execution_scope_worker_shadow_descriptor_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[12]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_frontend_scheduler_trial_execution_scope_worker_payload_install_id() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::trial_execution_scope_worker_payload_install_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[13]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_frontend_scheduler_installed_payload_source_coordinator_id() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::installed_payload_source_coordinator_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[14]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_frontend_scheduler_installed_payload_source_worker_owned_segments_id() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::installed_payload_source_worker_owned_segments_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[15]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
bool_t __latency_fn_source_unit_frontend_scheduler_promotion_nonclaim_o3_skip_precheck_enabled() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::promotion_nonclaim_o3_skip_precheck_enabled", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[16]);
	return bool_t(php::identical(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_SOURCE_UNIT_FRONTEND_PROMOTION_NONCLAIM_O3_SKIP_OK")), string_t("1")));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
bool_t __latency_fn_source_unit_frontend_scheduler_promotion_real_o3_evidence_blocker_precheck_enabled() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::promotion_real_o3_evidence_blocker_precheck_enabled", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[17]);
	return bool_t(php::identical(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_SOURCE_UNIT_FRONTEND_PROMOTION_REAL_O3_EVIDENCE_BLOCKER")), string_t("1")));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
DeterministicWorkOrderRow __latency_fn_source_unit_frontend_scheduler_source_work(SourceUnitTableRow sourceUnit, int_t<std::uint32_t> ownerRunId, int_t<std::uint16_t> executionModelId, int_t<> outputOrder, int_t<> completionOrder) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::source_work", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[18]);
	return __latency_fn_deterministic_work_ordering_row(sourceUnit->source_unit_id, executionModelId, __latency_fn_deterministic_work_ordering_work_kind_source_unit_id(), ownerRunId, sourceUnit->source_unit_id, __latency_fn_structure_row_ids_none_id(), sourceUnit->source_unit_id, __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(completionOrder), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(outputOrder));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
shared_p<DeterministicWorkOrderArtifact> __latency_fn_source_unit_frontend_scheduler_work_order_from_source_units(shared_p<SourceUnitTable> sourceUnits, int_t<std::uint32_t> ownerRunId, bool_t simulatedCompletion) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::work_order_from_source_units", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[19]);
	shared_p<DeterministicWorkOrderArtifact> artifact = __latency_fn_deterministic_work_ordering_new_artifact(cast<int_t<>>(sourceUnits->source_unit_count));
	if (static_cast<bool>(php::condition_truthy(simulatedCompletion))) {
		int_t<> index = required_cast<int_t<>>((cast<int_t<>>(sourceUnits->source_unit_count) - static_cast<int_t<> >(1)));
		int_t<> completionOrder = required_cast<int_t<>>(static_cast<int_t<> >(1));
		while (static_cast<bool>(php::condition_truthy((index >= static_cast<int_t<> >(0))))) {
			SourceUnitTableRow sourceUnit = sourceUnits->rows[index];
			__latency_fn_deterministic_work_ordering_append_row(artifact, __latency_fn_source_unit_frontend_scheduler_source_work(sourceUnit, cast<int_t<std::uint32_t>>(ownerRunId), __latency_fn_deterministic_work_ordering_execution_model_simulated_partitions_id(), (index + static_cast<int_t<> >(1)), completionOrder));
			completionOrder = (completionOrder + static_cast<int_t<> >(1));
			index = (index - static_cast<int_t<> >(1));
		}
	}
	else {
		int_t<> outputOrder = required_cast<int_t<>>(static_cast<int_t<> >(1));
		auto __latency_local_0 = sourceUnits->rows;
		for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
			auto sourceUnit = __latency_local_1.value_copy();
			__latency_fn_deterministic_work_ordering_append_row(artifact, __latency_fn_source_unit_frontend_scheduler_source_work(sourceUnit, cast<int_t<std::uint32_t>>(ownerRunId), __latency_fn_deterministic_work_ordering_execution_model_sequential_id(), outputOrder, outputOrder));
			outputOrder = (outputOrder + static_cast<int_t<> >(1));
		}
	}
	__latency_fn_deterministic_work_ordering_finalize_artifact(artifact);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
void __latency_fn_source_unit_frontend_scheduler_record_report(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::record_report", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[20]);
	if (static_cast<bool>((cast<int_t<>>(sourceUnits->source_unit_count) <= static_cast<int_t<> >(0)))) {
		return;
	}
	shared_p<DeterministicWorkOrderArtifact> sequential = __latency_fn_source_unit_frontend_scheduler_work_order_from_source_units(sourceUnits, cast<int_t<std::uint32_t>>(ownerRunId), bool_t(static_cast<bool_t>(false)));
	shared_p<DeterministicWorkOrderArtifact> simulated = __latency_fn_source_unit_frontend_scheduler_work_order_from_source_units(sourceUnits, cast<int_t<std::uint32_t>>(ownerRunId), bool_t(static_cast<bool_t>(true)));
	shared_p<SchedulerApiArtifact> oneThread = __latency_fn_scheduler_api_one_thread_artifact_from_work_order(sequential, ownerRunId);
	shared_p<SchedulerApiArtifact> simulatedOneThread = __latency_fn_scheduler_api_one_thread_artifact_from_work_order(simulated, ownerRunId);
	DeterministicWorkOrderRow firstSimulatedInput = __latency_fn_deterministic_work_ordering_first_stored_row(simulated);
	bool_t outputsMatch = required_cast<bool_t>(((php::identical(sequential->stable_output_hash, simulated->stable_output_hash) && php::identical(oneThread->stable_output_hash, simulatedOneThread->stable_output_hash)) && php::identical(cast<int_t<>>(oneThread->task_count), cast<int_t<>>(simulatedOneThread->task_count))));
	report->source_unit_frontend_scheduler_run_count = __latency_fn_source_unit_frontend_scheduler_add_u32(report->source_unit_frontend_scheduler_run_count, __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	report->source_unit_frontend_scheduler_work_row_count = __latency_fn_source_unit_frontend_scheduler_add_u32(report->source_unit_frontend_scheduler_work_row_count, sequential->row_count);
	report->source_unit_frontend_scheduler_partition_count = __latency_fn_source_unit_frontend_scheduler_add_u32(report->source_unit_frontend_scheduler_partition_count, sequential->partition_count);
	report->source_unit_frontend_scheduler_task_count = __latency_fn_source_unit_frontend_scheduler_add_u32(report->source_unit_frontend_scheduler_task_count, oneThread->task_count);
	report->source_unit_frontend_scheduler_completed_task_count = __latency_fn_source_unit_frontend_scheduler_add_u32(report->source_unit_frontend_scheduler_completed_task_count, oneThread->task_count);
	if (static_cast<bool>(php::condition_truthy(outputsMatch))) {
		report->source_unit_frontend_scheduler_output_match_count = __latency_fn_source_unit_frontend_scheduler_add_u32(report->source_unit_frontend_scheduler_output_match_count, __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		report->source_unit_frontend_scheduler_output_mismatch_count = __latency_fn_source_unit_frontend_scheduler_add_u32(report->source_unit_frontend_scheduler_output_mismatch_count, __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>((cast<int_t<>>(firstSimulatedInput->output_order_id) > cast<int_t<>>(report->source_unit_frontend_scheduler_simulated_first_input_order_max)))) {
		report->source_unit_frontend_scheduler_simulated_first_input_order_max = firstSimulatedInput->output_order_id;
	}
	bool_t workerDispatchReady = required_cast<bool_t>(((cast<int_t<>>(sourceUnits->source_unit_count) > static_cast<int_t<> >(1)) && outputsMatch));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_descriptor_eval_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::condition_truthy(workerDispatchReady))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_dispatch_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_dispatch_blocked(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_dispatch_ready(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_scheduler_worker_dispatch_blocked(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	__latency_fn_source_unit_frontend_scheduler_record_dispatch_plan_metrics(report, sourceUnits);
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
void __latency_fn_source_unit_frontend_scheduler_record_dispatch_plan_metrics(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::record_dispatch_plan_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[21]);
	int_t<> workerCount = required_cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_worker_count_for_sources(sourceUnits));
	bool_t hasRealWorkerCandidate = required_cast<bool_t>((workerCount > static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_plan_runs(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_plan_requested_workers(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(workerCount));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_plan_tasks(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(cast<int_t<>>(sourceUnits->source_unit_count)));
	if (static_cast<bool>(php::condition_truthy(hasRealWorkerCandidate))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_plan_one_thread_fallbacks(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_plan_real_worker_candidates(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_plan_one_thread_fallbacks(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_plan_real_worker_candidates(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_plan_payload_handle_inputs(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(cast<int_t<>>(sourceUnits->source_unit_count)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_source_unit_frontend_dispatch_plan_payload_copy_bytes(), __latency_fn_source_unit_frontend_scheduler_uint32_from_int(static_cast<int_t<> >(0)));
}

}

#include <scpp/lang/php.hpp>
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/BackendSinkBoundaryRow.hpp"
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/EmissionLLVMWorkerInput.hpp"
#include "__types/EmissionLLVMWorkerResult.hpp"
#include "__types/FunctionBodyTextEmissionPreflightArtifact.hpp"
#include "__types/LoweringPlan.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_lowering_plan_from_worker_input.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_target_lowering_plan_from_worker_input.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_backend_sink_boundary_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_publication_sink_boundary_row_count.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_ready_row_count.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_status_ready_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_backend_sink_boundary_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_publication_sink_boundary_row_count.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_blocked_row_count.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_status_ready_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_from_lowering_plan_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_composite_text_mode_direct_call_target_requests_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_composite_text_mode_fixed_arg_parameter_add_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_emission_llvm_publication_published_row_count.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_lowering_plan_from_worker_input.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_entry_and_target_emissions_with_target_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_module_text_from_entry_call_with_target_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_parameter_add_function_text_from_value.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_preflight_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_publication_llvm_text_nonempty_row_count.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_publication_sink_boundary_row_count.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_request_artifact_from_worker_input.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_semantic_hash_for_emission_llvm.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_blocked_row_count.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_ready_row_count.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_target_lowering_plan_from_worker_input.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_target_request_artifact_from_worker_input.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_worker_result_for_input.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_worker_result_for_input.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_worker_results.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_first_worker_result.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_first_worker_result.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_publication_llvm_text_nonempty_row_count.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_publication_sink_boundary_row_count.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_record_emission_llvm_worker_handoff_metrics.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_blocked_row_count.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_ready_row_count.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_block_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_decision_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_preflight_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_preflight_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_preflight_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_semantic_hash.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_sink_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_sink_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_sink_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_text_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_text_nonempty_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_value_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_descriptor_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_inputs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_matches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_mismatches.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_snapshot_backend_request_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_snapshot_lowering_plan_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_block_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_decision_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_preflight_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_preflight_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_preflight_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_semantic_hash.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_sink_blocked_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_sink_ready_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_sink_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_text_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_text_nonempty_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_value_rows.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
shared_p<LoweringPlan> __latency_fn_llvm_text_from_plan_lowering_plan_from_worker_input(shared_p<EmissionLLVMWorkerInput> input) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::lowering_plan_from_worker_input", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[186]);
	shared_p<LoweringPlan> plan = create<LoweringPlan>();
	plan->artifact_kind_id = __latency_fn_structure_row_ids_uint16_from_int(input->plan_artifact_kind_id);
	plan->schema_version = __latency_fn_structure_row_ids_uint16_from_int(input->plan_schema_version);
	plan->source_model_id = __latency_fn_structure_row_ids_uint16_from_int(input->plan_source_model_id);
	plan->plan_model_id = __latency_fn_structure_row_ids_uint16_from_int(input->plan_model_id);
	plan->backend_emit_status_id = __latency_fn_structure_row_ids_uint16_from_int(input->plan_backend_emit_status_id);
	plan->owner_source_unit_id = __latency_fn_structure_row_ids_uint32_from_int(input->source_unit_id);
	plan->owner_symbol_id = __latency_fn_structure_row_ids_uint32_from_int(input->symbol_id);
	plan->step_count = __latency_fn_structure_row_ids_uint32_from_int(input->plan_step_count);
	plan->work_ref_count = __latency_fn_structure_row_ids_uint32_from_int(input->plan_work_ref_count);
	plan->blocked_request_count = __latency_fn_structure_row_ids_uint32_from_int(input->plan_blocked_request_count);
	plan->binary_operand_count = __latency_fn_structure_row_ids_uint32_from_int(input->plan_binary_operand_count);
	plan->local_operand_count = __latency_fn_structure_row_ids_uint32_from_int(input->plan_local_operand_count);
	plan->call_argument_count = __latency_fn_structure_row_ids_uint32_from_int(input->plan_call_argument_count);
	plan->control_flow_operand_count = __latency_fn_structure_row_ids_uint32_from_int(input->plan_control_flow_operand_count);
	return plan;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
shared_p<LoweringPlan> __latency_fn_llvm_text_from_plan_target_lowering_plan_from_worker_input(shared_p<EmissionLLVMWorkerInput> input) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::target_lowering_plan_from_worker_input", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[187]);
	shared_p<LoweringPlan> plan = create<LoweringPlan>();
	plan->artifact_kind_id = __latency_fn_structure_row_ids_uint16_from_int(input->target_plan_artifact_kind_id);
	plan->schema_version = __latency_fn_structure_row_ids_uint16_from_int(input->target_plan_schema_version);
	plan->source_model_id = __latency_fn_structure_row_ids_uint16_from_int(input->target_plan_source_model_id);
	plan->plan_model_id = __latency_fn_structure_row_ids_uint16_from_int(input->target_plan_model_id);
	plan->backend_emit_status_id = __latency_fn_structure_row_ids_uint16_from_int(input->target_plan_backend_emit_status_id);
	plan->owner_source_unit_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_source_unit_id);
	plan->owner_symbol_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_symbol_id);
	plan->step_count = __latency_fn_structure_row_ids_uint32_from_int(input->target_plan_step_count);
	plan->work_ref_count = __latency_fn_structure_row_ids_uint32_from_int(input->target_plan_work_ref_count);
	plan->blocked_request_count = __latency_fn_structure_row_ids_uint32_from_int(input->target_plan_blocked_request_count);
	plan->binary_operand_count = __latency_fn_structure_row_ids_uint32_from_int(input->target_plan_binary_operand_count);
	plan->local_operand_count = __latency_fn_structure_row_ids_uint32_from_int(input->target_plan_local_operand_count);
	plan->call_argument_count = __latency_fn_structure_row_ids_uint32_from_int(input->target_plan_call_argument_count);
	plan->control_flow_operand_count = __latency_fn_structure_row_ids_uint32_from_int(input->target_plan_control_flow_operand_count);
	return plan;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<> __latency_fn_llvm_text_from_plan_sink_ready_row_count(BackendEmissionDecisionArtifact& emission) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::sink_ready_row_count", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[188]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(__latency_fn_llvm_text_from_plan_publication_sink_boundary_row_count(emission)), static_cast<int_t<> >(0)))) {
		return static_cast<int_t<> >(0);
	}
	BackendSinkBoundaryRow sink = __latency_fn_llvm_text_from_plan_backend_sink_boundary_from_emission(emission);
	if (static_cast<bool>(php::identical(cast<int_t<>>(sink->status_id), cast<int_t<>>(__latency_fn_llvm_text_from_plan_status_ready_id())))) {
		return static_cast<int_t<> >(1);
	}
	return static_cast<int_t<> >(0);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<> __latency_fn_llvm_text_from_plan_sink_blocked_row_count(BackendEmissionDecisionArtifact& emission) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::sink_blocked_row_count", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[189]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(__latency_fn_llvm_text_from_plan_publication_sink_boundary_row_count(emission)), static_cast<int_t<> >(0)))) {
		return static_cast<int_t<> >(0);
	}
	BackendSinkBoundaryRow sink = __latency_fn_llvm_text_from_plan_backend_sink_boundary_from_emission(emission);
	if (static_cast<bool>(php::identical(cast<int_t<>>(sink->status_id), cast<int_t<>>(__latency_fn_llvm_text_from_plan_status_ready_id())))) {
		return static_cast<int_t<> >(0);
	}
	return static_cast<int_t<> >(1);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
shared_p<EmissionLLVMWorkerResult> __latency_fn_llvm_text_from_plan_worker_result_for_input(shared_p<EmissionLLVMWorkerInput> input) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::worker_result_for_input", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[190]);
	shared_p<BackendRequestAuthorizationArtifact> requests = __latency_fn_llvm_text_from_plan_request_artifact_from_worker_input(input);
	shared_p<LoweringPlan> plan = __latency_fn_llvm_text_from_plan_lowering_plan_from_worker_input(input);
	BackendEmissionDecisionArtifact emission = __latency_fn_backend_emission_decisions_from_lowering_plan_and_backend_requests(plan, requests);
	FunctionBodyTextEmissionPreflightArtifact preflightArtifact = __latency_fn_llvm_text_from_plan_preflight_from_emission(emission);
	string_t moduleText = required_cast<string_t>(__latency_fn_llvm_text_from_plan_module_text_from_emission_and_backend_requests(emission, requests));
	if (static_cast<bool>(php::identical(input->composite_text_mode_id, __latency_fn_llvm_text_from_plan_composite_text_mode_direct_call_target_requests_id()))) {
		shared_p<BackendRequestAuthorizationArtifact> targetRequests = __latency_fn_llvm_text_from_plan_target_request_artifact_from_worker_input(input);
		shared_p<LoweringPlan> targetPlan = __latency_fn_llvm_text_from_plan_target_lowering_plan_from_worker_input(input);
		BackendEmissionDecisionArtifact targetEmission = __latency_fn_backend_emission_decisions_from_lowering_plan_and_backend_requests(targetPlan, targetRequests);
		moduleText = __latency_fn_llvm_text_from_plan_module_text_from_entry_and_target_emissions_with_target_name(emission, requests, targetEmission, targetRequests, input->target_llvm_function_name);
	}
	else {
		if (static_cast<bool>(php::identical(input->composite_text_mode_id, __latency_fn_llvm_text_from_plan_composite_text_mode_fixed_arg_parameter_add_id()))) {
			string_t targetFunctionText = required_cast<string_t>(__latency_fn_llvm_text_from_plan_parameter_add_function_text_from_value(input->target_llvm_function_name, input->target_fixed_arg_right_literal_value));
			moduleText = __latency_fn_llvm_text_from_plan_module_text_from_entry_call_with_target_text(emission, requests, targetFunctionText, input->target_llvm_function_name);
		}
	}
	shared_p<EmissionLLVMWorkerResult> result = create<EmissionLLVMWorkerResult>();
	result->source_unit_id = input->source_unit_id;
	result->symbol_id = input->symbol_id;
	result->decision_rows = cast<int_t<>>(emission->decision_count);
	result->ready_rows = cast<int_t<>>(emission->ready_count);
	result->blocked_rows = cast<int_t<>>(emission->blocked_count);
	result->value_rows = cast<int_t<>>(emission->value_count);
	result->block_rows = cast<int_t<>>(emission->block_count);
	result->preflight_rows = cast<int_t<>>(preflightArtifact->row_count);
	result->preflight_ready_rows = cast<int_t<>>(preflightArtifact->ready_count);
	result->preflight_blocked_rows = cast<int_t<>>(preflightArtifact->blocked_count);
	result->sink_rows = cast<int_t<>>(__latency_fn_llvm_text_from_plan_publication_sink_boundary_row_count(emission));
	result->sink_ready_rows = __latency_fn_llvm_text_from_plan_sink_ready_row_count(emission);
	result->sink_blocked_rows = __latency_fn_llvm_text_from_plan_sink_blocked_row_count(emission);
	result->text_nonempty_rows = cast<int_t<>>(__latency_fn_llvm_text_from_plan_publication_llvm_text_nonempty_row_count(moduleText));
	result->text_bytes = str::length(moduleText);
	result->published_rows = cast<int_t<>>(__latency_fn_llvm_text_from_plan_emission_llvm_publication_published_row_count(emission, preflightArtifact));
	result->semantic_hash = cast<int_t<>>(__latency_fn_llvm_text_from_plan_semantic_hash_for_emission_llvm(requests, emission, preflightArtifact, moduleText));
	return result;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
vector_t<shared_p<EmissionLLVMWorkerResult>> __latency_fn_llvm_text_from_plan_worker_results(const vector_t<shared_p<EmissionLLVMWorkerInput>>& inputs, int_t<> workerCount) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::worker_results", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[191]);
	vector_t<shared_p<EmissionLLVMWorkerResult>> results = required_cast<vector_t<shared_p<EmissionLLVMWorkerResult>>>(tasks::run(inputs, workerCount, [](shared_p<EmissionLLVMWorkerInput> input) -> shared_p<EmissionLLVMWorkerResult> {
	return __latency_fn_llvm_text_from_plan_worker_result_for_input(input);
}));
	return results;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
shared_p<EmissionLLVMWorkerResult> __latency_fn_llvm_text_from_plan_first_worker_result(const vector_t<shared_p<EmissionLLVMWorkerResult>>& results) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::first_worker_result", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[192]);
	if (static_cast<bool>((php::count(results) > static_cast<int_t<> >(0)))) {
		return results.at(static_cast<int_t<> >(0));
	}
	shared_p<EmissionLLVMWorkerResult> empty = create<EmissionLLVMWorkerResult>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_record_emission_llvm_worker_handoff_metrics(shared_p<CompilerProjectRunReport>& report, const vector_t<shared_p<EmissionLLVMWorkerInput>>& inputs, const vector_t<shared_p<EmissionLLVMWorkerResult>>& results, BackendEmissionDecisionArtifact& emission, FunctionBodyTextEmissionPreflightArtifact preflightArtifact, const string_t& moduleText, int_t<std::uint32_t> coordinatorSemanticHash, bool_t matches) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::record_emission_llvm_worker_handoff_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[193]);
	shared_p<EmissionLLVMWorkerResult> worker = __latency_fn_llvm_text_from_plan_first_worker_result(results);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_runs(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_inputs(), __latency_fn_structure_row_ids_uint32_from_int(php::count(inputs)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_snapshot_backend_request_rows(), __latency_fn_structure_row_ids_uint32_from_int(inputs.at(static_cast<int_t<> >(0))->request_count));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_snapshot_lowering_plan_rows(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_descriptor_rows(), __latency_fn_structure_row_ids_uint32_from_int(php::count(results)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_decision_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->decision_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_decision_rows(), emission->decision_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_ready_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->ready_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_ready_rows(), emission->ready_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_blocked_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->blocked_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_blocked_rows(), emission->blocked_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_value_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->value_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_value_rows(), emission->value_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_block_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->block_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_block_rows(), emission->block_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_preflight_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->preflight_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_preflight_rows(), preflightArtifact->row_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_preflight_ready_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->preflight_ready_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_preflight_ready_rows(), preflightArtifact->ready_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_preflight_blocked_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->preflight_blocked_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_preflight_blocked_rows(), preflightArtifact->blocked_count);
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_sink_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->sink_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_sink_rows(), __latency_fn_llvm_text_from_plan_publication_sink_boundary_row_count(emission));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_sink_ready_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->sink_ready_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_sink_ready_rows(), __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_llvm_text_from_plan_sink_ready_row_count(emission)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_sink_blocked_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->sink_blocked_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_sink_blocked_rows(), __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_llvm_text_from_plan_sink_blocked_row_count(emission)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_text_nonempty_rows(), __latency_fn_structure_row_ids_uint32_from_int(worker->text_nonempty_rows));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_text_nonempty_rows(), __latency_fn_llvm_text_from_plan_publication_llvm_text_nonempty_row_count(moduleText));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_text_bytes(), __latency_fn_structure_row_ids_uint32_from_int(worker->text_bytes));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_text_bytes(), __latency_fn_structure_row_ids_uint32_from_int(str::length(moduleText)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_worker_semantic_hash(), __latency_fn_structure_row_ids_uint32_from_int(worker->semantic_hash));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_coordinator_semantic_hash(), coordinatorSemanticHash);
	if (static_cast<bool>(php::condition_truthy(matches))) {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	}
	else {
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_matches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_mismatches(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_emission_llvm_worker_handoff_payload_copy_bytes(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
}

}

#include <scpp/lang/php.hpp>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendCallArgumentRow.hpp"
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/EmissionLLVMCompositeTextSnapshot.hpp"
#include "__types/EmissionLLVMWorkerInput.hpp"
#include "__types/LoweringPlan.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_snapshot_entry_plan_into_input.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_snapshot_target_plan_into_input.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_composite_text_mode_direct_call_target_requests_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_emission_llvm_worker_input_from_rows.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_snapshot_entry_plan_into_input.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_snapshot_entry_requests_into_input.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_snapshot_target_plan_into_input.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_snapshot_target_requests_into_input.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_backend_request.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_binary_operand.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_call_argument.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_control_flow_operand.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_local_operand.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_new_backend_request_artifact_with_sidecar_capacity.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_request_artifact_from_worker_input.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_backend_request.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_binary_operand.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_call_argument.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_control_flow_operand.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_append_local_operand.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_new_backend_request_artifact_with_sidecar_capacity.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_target_request_artifact_from_worker_input.hpp"
#include "__callable/__latency_fn_structure_row_ids_int32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_snapshot_entry_plan_into_input(shared_p<EmissionLLVMWorkerInput>& input, shared_p<LoweringPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::snapshot_entry_plan_into_input", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[181]);
	input->plan_artifact_kind_id = cast<int_t<>>(plan->artifact_kind_id);
	input->plan_schema_version = cast<int_t<>>(plan->schema_version);
	input->plan_source_model_id = cast<int_t<>>(plan->source_model_id);
	input->plan_model_id = cast<int_t<>>(plan->plan_model_id);
	input->plan_backend_emit_status_id = cast<int_t<>>(plan->backend_emit_status_id);
	input->plan_step_count = cast<int_t<>>(plan->step_count);
	input->plan_work_ref_count = cast<int_t<>>(plan->work_ref_count);
	input->plan_blocked_request_count = cast<int_t<>>(plan->blocked_request_count);
	input->plan_binary_operand_count = cast<int_t<>>(plan->binary_operand_count);
	input->plan_local_operand_count = cast<int_t<>>(plan->local_operand_count);
	input->plan_call_argument_count = cast<int_t<>>(plan->call_argument_count);
	input->plan_control_flow_operand_count = cast<int_t<>>(plan->control_flow_operand_count);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_snapshot_target_plan_into_input(shared_p<EmissionLLVMWorkerInput>& input, shared_p<LoweringPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::snapshot_target_plan_into_input", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[182]);
	input->target_plan_artifact_kind_id = cast<int_t<>>(plan->artifact_kind_id);
	input->target_plan_schema_version = cast<int_t<>>(plan->schema_version);
	input->target_plan_source_model_id = cast<int_t<>>(plan->source_model_id);
	input->target_plan_model_id = cast<int_t<>>(plan->plan_model_id);
	input->target_plan_backend_emit_status_id = cast<int_t<>>(plan->backend_emit_status_id);
	input->target_plan_step_count = cast<int_t<>>(plan->step_count);
	input->target_plan_work_ref_count = cast<int_t<>>(plan->work_ref_count);
	input->target_plan_blocked_request_count = cast<int_t<>>(plan->blocked_request_count);
	input->target_plan_binary_operand_count = cast<int_t<>>(plan->binary_operand_count);
	input->target_plan_local_operand_count = cast<int_t<>>(plan->local_operand_count);
	input->target_plan_call_argument_count = cast<int_t<>>(plan->call_argument_count);
	input->target_plan_control_flow_operand_count = cast<int_t<>>(plan->control_flow_operand_count);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
shared_p<EmissionLLVMWorkerInput> __latency_fn_llvm_text_from_plan_emission_llvm_worker_input_from_rows(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, shared_p<BackendRequestAuthorizationArtifact>& requests, shared_p<LoweringPlan> plan, shared_p<EmissionLLVMCompositeTextSnapshot> compositeSnapshot) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::emission_llvm_worker_input_from_rows", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[183]);
	shared_p<EmissionLLVMWorkerInput> input = create<EmissionLLVMWorkerInput>();
	input->owner_run_id = cast<int_t<>>(ownerRunId);
	input->source_unit_id = cast<int_t<>>(entrySymbol->source_unit_id);
	input->symbol_id = cast<int_t<>>(entrySymbol->symbol_id);
	__latency_fn_llvm_text_from_plan_snapshot_entry_requests_into_input(input, requests);
	__latency_fn_llvm_text_from_plan_snapshot_entry_plan_into_input(input, plan);
	input->composite_text_mode_id = compositeSnapshot->mode_id;
	input->target_llvm_function_name = compositeSnapshot->target_llvm_function_name;
	input->target_source_unit_id = compositeSnapshot->target_source_unit_id;
	input->target_symbol_id = compositeSnapshot->target_symbol_id;
	input->target_fixed_arg_right_literal_value = compositeSnapshot->fixed_arg_right_literal_value;
	if (static_cast<bool>(php::identical(compositeSnapshot->mode_id, __latency_fn_llvm_text_from_plan_composite_text_mode_direct_call_target_requests_id()))) {
		shared_p<BackendRequestAuthorizationArtifact> targetRequests = compositeSnapshot->target_requests;
		__latency_fn_llvm_text_from_plan_snapshot_target_requests_into_input(input, targetRequests);
		__latency_fn_llvm_text_from_plan_snapshot_target_plan_into_input(input, compositeSnapshot->target_plan);
	}
	return input;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
shared_p<BackendRequestAuthorizationArtifact> __latency_fn_llvm_text_from_plan_request_artifact_from_worker_input(shared_p<EmissionLLVMWorkerInput> input) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::request_artifact_from_worker_input", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[184]);
	shared_p<BackendRequestAuthorizationArtifact> requests = __latency_fn_backend_preflight_requests_new_backend_request_artifact_with_sidecar_capacity(input->request_count, input->binary_operand_count, input->local_operand_count, input->call_argument_count, input->control_flow_operand_count);
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < php::count(input->request_ids)))) {
		BackendRequestAuthorizationRow row = BackendRequestAuthorizationRow{};
		row->request_id = __latency_fn_structure_row_ids_uint32_from_int(input->request_ids.at(index));
		row->contract_id = __latency_fn_structure_row_ids_uint16_from_int(input->request_contract_ids.at(index));
		row->project_callable_contract_id = __latency_fn_structure_row_ids_uint32_from_int(input->request_project_callable_contract_ids.at(index));
		row->source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->request_source_row_ids.at(index));
		row->source_reference_id = __latency_fn_structure_row_ids_uint32_from_int(input->request_source_reference_ids.at(index));
		row->target_symbol_id = __latency_fn_structure_row_ids_uint32_from_int(input->request_target_symbol_ids.at(index));
		row->feature_id = __latency_fn_structure_row_ids_uint16_from_int(input->request_feature_ids.at(index));
		row->lowering_adapter_id = __latency_fn_structure_row_ids_uint16_from_int(input->request_lowering_adapter_ids.at(index));
		row->lowering_step_kind_id = __latency_fn_structure_row_ids_uint16_from_int(input->request_lowering_step_kind_ids.at(index));
		row->provider_type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(input->request_provider_type_ref_ids.at(index));
		row->cache_owner_symbol_id = __latency_fn_structure_row_ids_uint32_from_int(input->request_cache_owner_symbol_ids.at(index));
		row->status_id = __latency_fn_structure_row_ids_uint16_from_int(input->request_status_ids.at(index));
		row->blocked_reason_id = __latency_fn_structure_row_ids_uint16_from_int(input->request_blocked_reason_ids.at(index));
		row->project_callable_blocked_reason_id = __latency_fn_structure_row_ids_uint16_from_int(input->request_project_callable_blocked_reason_ids.at(index));
		row->value = __latency_fn_structure_row_ids_int32_from_int(input->request_values.at(index));
		__latency_fn_backend_preflight_requests_append_backend_request(requests, row);
		index = (index + static_cast<int_t<> >(1));
	}
	index = static_cast<int_t<> >(0);
	while (static_cast<bool>((index < php::count(input->binary_owner_row_ids)))) {
		BackendBinaryOperandRow row = BackendBinaryOperandRow{};
		row->owner_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->binary_owner_row_ids.at(index));
		row->left_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->binary_left_source_row_ids.at(index));
		row->right_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->binary_right_source_row_ids.at(index));
		row->left_value = __latency_fn_structure_row_ids_int32_from_int(input->binary_left_values.at(index));
		row->right_value = __latency_fn_structure_row_ids_int32_from_int(input->binary_right_values.at(index));
		__latency_fn_backend_preflight_requests_append_binary_operand(requests, row);
		index = (index + static_cast<int_t<> >(1));
	}
	index = static_cast<int_t<> >(0);
	while (static_cast<bool>((index < php::count(input->local_owner_row_ids)))) {
		BackendLocalOperandRow row = BackendLocalOperandRow{};
		row->owner_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->local_owner_row_ids.at(index));
		row->local_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->local_source_row_ids.at(index));
		row->value_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->local_value_source_row_ids.at(index));
		row->type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(input->local_type_ref_ids.at(index));
		row->value = __latency_fn_structure_row_ids_int32_from_int(input->local_values.at(index));
		row->local_operation_id = __latency_fn_structure_row_ids_uint16_from_int(input->local_operation_ids.at(index));
		__latency_fn_backend_preflight_requests_append_local_operand(requests, row);
		index = (index + static_cast<int_t<> >(1));
	}
	index = static_cast<int_t<> >(0);
	while (static_cast<bool>((index < php::count(input->call_argument_owner_row_ids)))) {
		BackendCallArgumentRow row = BackendCallArgumentRow{};
		row->owner_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->call_argument_owner_row_ids.at(index));
		row->argument_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->call_argument_source_row_ids.at(index));
		row->type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(input->call_argument_type_ref_ids.at(index));
		row->value = __latency_fn_structure_row_ids_int32_from_int(input->call_argument_values.at(index));
		row->position = __latency_fn_structure_row_ids_uint16_from_int(input->call_argument_positions.at(index));
		__latency_fn_backend_preflight_requests_append_call_argument(requests, row);
		index = (index + static_cast<int_t<> >(1));
	}
	index = static_cast<int_t<> >(0);
	while (static_cast<bool>((index < php::count(input->control_owner_row_ids)))) {
		BackendControlFlowOperandRow row = BackendControlFlowOperandRow{};
		row->owner_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->control_owner_row_ids.at(index));
		row->condition_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->control_condition_source_row_ids.at(index));
		row->body_first_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->control_body_first_source_row_ids.at(index));
		row->body_last_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->control_body_last_source_row_ids.at(index));
		row->body_terminator_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->control_body_terminator_source_row_ids.at(index));
		row->body_terminator_kind_id = __latency_fn_structure_row_ids_uint16_from_int(input->control_body_terminator_kind_ids.at(index));
		row->else_body_first_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->control_else_body_first_source_row_ids.at(index));
		row->else_body_last_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->control_else_body_last_source_row_ids.at(index));
		row->condition_type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(input->control_condition_type_ref_ids.at(index));
		row->condition_value = __latency_fn_structure_row_ids_int32_from_int(input->control_condition_values.at(index));
		row->condition_operand_kind_id = __latency_fn_structure_row_ids_uint16_from_int(input->control_condition_operand_kind_ids.at(index));
		row->condition_local_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->control_condition_local_source_row_ids.at(index));
		row->condition_left_local_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->control_condition_left_local_source_row_ids.at(index));
		row->condition_right_local_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->control_condition_right_local_source_row_ids.at(index));
		row->condition_lhs_type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(input->control_condition_lhs_type_ref_ids.at(index));
		row->condition_local_operation_id = __latency_fn_structure_row_ids_uint16_from_int(input->control_condition_local_operation_ids.at(index));
		row->control_operation_id = __latency_fn_structure_row_ids_uint16_from_int(input->control_operation_ids.at(index));
		__latency_fn_backend_preflight_requests_append_control_flow_operand(requests, row);
		index = (index + static_cast<int_t<> >(1));
	}
	return requests;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
shared_p<BackendRequestAuthorizationArtifact> __latency_fn_llvm_text_from_plan_target_request_artifact_from_worker_input(shared_p<EmissionLLVMWorkerInput> input) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::target_request_artifact_from_worker_input", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[185]);
	shared_p<BackendRequestAuthorizationArtifact> requests = __latency_fn_backend_preflight_requests_new_backend_request_artifact_with_sidecar_capacity(input->target_request_count, input->target_binary_operand_count, input->target_local_operand_count, input->target_call_argument_count, input->target_control_flow_operand_count);
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < php::count(input->target_request_ids)))) {
		BackendRequestAuthorizationRow row = BackendRequestAuthorizationRow{};
		row->request_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_request_ids.at(index));
		row->contract_id = __latency_fn_structure_row_ids_uint16_from_int(input->target_request_contract_ids.at(index));
		row->project_callable_contract_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_request_project_callable_contract_ids.at(index));
		row->source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_request_source_row_ids.at(index));
		row->source_reference_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_request_source_reference_ids.at(index));
		row->target_symbol_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_request_target_symbol_ids.at(index));
		row->feature_id = __latency_fn_structure_row_ids_uint16_from_int(input->target_request_feature_ids.at(index));
		row->lowering_adapter_id = __latency_fn_structure_row_ids_uint16_from_int(input->target_request_lowering_adapter_ids.at(index));
		row->lowering_step_kind_id = __latency_fn_structure_row_ids_uint16_from_int(input->target_request_lowering_step_kind_ids.at(index));
		row->provider_type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_request_provider_type_ref_ids.at(index));
		row->cache_owner_symbol_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_request_cache_owner_symbol_ids.at(index));
		row->status_id = __latency_fn_structure_row_ids_uint16_from_int(input->target_request_status_ids.at(index));
		row->blocked_reason_id = __latency_fn_structure_row_ids_uint16_from_int(input->target_request_blocked_reason_ids.at(index));
		row->project_callable_blocked_reason_id = __latency_fn_structure_row_ids_uint16_from_int(input->target_request_project_callable_blocked_reason_ids.at(index));
		row->value = __latency_fn_structure_row_ids_int32_from_int(input->target_request_values.at(index));
		__latency_fn_backend_preflight_requests_append_backend_request(requests, row);
		index = (index + static_cast<int_t<> >(1));
	}
	index = static_cast<int_t<> >(0);
	while (static_cast<bool>((index < php::count(input->target_binary_owner_row_ids)))) {
		BackendBinaryOperandRow row = BackendBinaryOperandRow{};
		row->owner_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_binary_owner_row_ids.at(index));
		row->left_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_binary_left_source_row_ids.at(index));
		row->right_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_binary_right_source_row_ids.at(index));
		row->left_value = __latency_fn_structure_row_ids_int32_from_int(input->target_binary_left_values.at(index));
		row->right_value = __latency_fn_structure_row_ids_int32_from_int(input->target_binary_right_values.at(index));
		__latency_fn_backend_preflight_requests_append_binary_operand(requests, row);
		index = (index + static_cast<int_t<> >(1));
	}
	index = static_cast<int_t<> >(0);
	while (static_cast<bool>((index < php::count(input->target_local_owner_row_ids)))) {
		BackendLocalOperandRow row = BackendLocalOperandRow{};
		row->owner_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_local_owner_row_ids.at(index));
		row->local_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_local_source_row_ids.at(index));
		row->value_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_local_value_source_row_ids.at(index));
		row->type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_local_type_ref_ids.at(index));
		row->value = __latency_fn_structure_row_ids_int32_from_int(input->target_local_values.at(index));
		row->local_operation_id = __latency_fn_structure_row_ids_uint16_from_int(input->target_local_operation_ids.at(index));
		__latency_fn_backend_preflight_requests_append_local_operand(requests, row);
		index = (index + static_cast<int_t<> >(1));
	}
	index = static_cast<int_t<> >(0);
	while (static_cast<bool>((index < php::count(input->target_call_argument_owner_row_ids)))) {
		BackendCallArgumentRow row = BackendCallArgumentRow{};
		row->owner_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_call_argument_owner_row_ids.at(index));
		row->argument_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_call_argument_source_row_ids.at(index));
		row->type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_call_argument_type_ref_ids.at(index));
		row->value = __latency_fn_structure_row_ids_int32_from_int(input->target_call_argument_values.at(index));
		row->position = __latency_fn_structure_row_ids_uint16_from_int(input->target_call_argument_positions.at(index));
		__latency_fn_backend_preflight_requests_append_call_argument(requests, row);
		index = (index + static_cast<int_t<> >(1));
	}
	index = static_cast<int_t<> >(0);
	while (static_cast<bool>((index < php::count(input->target_control_owner_row_ids)))) {
		BackendControlFlowOperandRow row = BackendControlFlowOperandRow{};
		row->owner_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_control_owner_row_ids.at(index));
		row->condition_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_control_condition_source_row_ids.at(index));
		row->body_first_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_control_body_first_source_row_ids.at(index));
		row->body_last_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_control_body_last_source_row_ids.at(index));
		row->body_terminator_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_control_body_terminator_source_row_ids.at(index));
		row->body_terminator_kind_id = __latency_fn_structure_row_ids_uint16_from_int(input->target_control_body_terminator_kind_ids.at(index));
		row->else_body_first_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_control_else_body_first_source_row_ids.at(index));
		row->else_body_last_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_control_else_body_last_source_row_ids.at(index));
		row->condition_type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_control_condition_type_ref_ids.at(index));
		row->condition_value = __latency_fn_structure_row_ids_int32_from_int(input->target_control_condition_values.at(index));
		row->condition_operand_kind_id = __latency_fn_structure_row_ids_uint16_from_int(input->target_control_condition_operand_kind_ids.at(index));
		row->condition_local_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_control_condition_local_source_row_ids.at(index));
		row->condition_left_local_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_control_condition_left_local_source_row_ids.at(index));
		row->condition_right_local_source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_control_condition_right_local_source_row_ids.at(index));
		row->condition_lhs_type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(input->target_control_condition_lhs_type_ref_ids.at(index));
		row->condition_local_operation_id = __latency_fn_structure_row_ids_uint16_from_int(input->target_control_condition_local_operation_ids.at(index));
		row->control_operation_id = __latency_fn_structure_row_ids_uint16_from_int(input->target_control_operation_ids.at(index));
		__latency_fn_backend_preflight_requests_append_control_flow_operand(requests, row);
		index = (index + static_cast<int_t<> >(1));
	}
	return requests;
}

}

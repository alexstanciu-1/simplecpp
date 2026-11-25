#include <scpp/lang/php.hpp>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendCallArgumentRow.hpp"
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/EmissionLLVMWorkerInput.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_by_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_snapshot_target_requests_into_input.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_snapshot_target_requests_into_input(shared_p<EmissionLLVMWorkerInput>& input, shared_p<BackendRequestAuthorizationArtifact>& requests) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::snapshot_target_requests_into_input", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[180]);
	input->target_request_count = cast<int_t<>>(requests->request_count);
	input->target_ready_count = cast<int_t<>>(requests->ready_count);
	input->target_blocked_count = cast<int_t<>>(requests->blocked_count);
	input->target_binary_operand_count = cast<int_t<>>(requests->binary_operand_count);
	input->target_local_operand_count = cast<int_t<>>(requests->local_operand_count);
	input->target_call_argument_count = cast<int_t<>>(requests->call_argument_count);
	input->target_control_flow_operand_count = cast<int_t<>>(requests->control_flow_operand_count);
	int_t<> requestIndex = required_cast<int_t<>>(static_cast<int_t<> >(1));
	while (static_cast<bool>((requestIndex <= cast<int_t<>>(requests->request_count)))) {
		BackendRequestAuthorizationRow request = __latency_fn_backend_preflight_requests_backend_request_by_id(requests, __latency_fn_structure_row_ids_uint32_from_int(requestIndex));
		if (static_cast<bool>((cast<int_t<>>(request->request_id) > static_cast<int_t<> >(0)))) {
			{
			auto __latency_local_0 = cast<int_t<>>(request->request_id);
			(void) input->target_request_ids.push_back(__latency_local_0);
			}
			{
			auto __latency_local_1 = cast<int_t<>>(request->contract_id);
			(void) input->target_request_contract_ids.push_back(__latency_local_1);
			}
			{
			auto __latency_local_2 = cast<int_t<>>(request->project_callable_contract_id);
			(void) input->target_request_project_callable_contract_ids.push_back(__latency_local_2);
			}
			{
			auto __latency_local_3 = cast<int_t<>>(request->source_row_id);
			(void) input->target_request_source_row_ids.push_back(__latency_local_3);
			}
			{
			auto __latency_local_4 = cast<int_t<>>(request->source_reference_id);
			(void) input->target_request_source_reference_ids.push_back(__latency_local_4);
			}
			{
			auto __latency_local_5 = cast<int_t<>>(request->target_symbol_id);
			(void) input->target_request_target_symbol_ids.push_back(__latency_local_5);
			}
			{
			auto __latency_local_6 = cast<int_t<>>(request->feature_id);
			(void) input->target_request_feature_ids.push_back(__latency_local_6);
			}
			{
			auto __latency_local_7 = cast<int_t<>>(request->lowering_adapter_id);
			(void) input->target_request_lowering_adapter_ids.push_back(__latency_local_7);
			}
			{
			auto __latency_local_8 = cast<int_t<>>(request->lowering_step_kind_id);
			(void) input->target_request_lowering_step_kind_ids.push_back(__latency_local_8);
			}
			{
			auto __latency_local_9 = cast<int_t<>>(request->provider_type_ref_id);
			(void) input->target_request_provider_type_ref_ids.push_back(__latency_local_9);
			}
			{
			auto __latency_local_10 = cast<int_t<>>(request->cache_owner_symbol_id);
			(void) input->target_request_cache_owner_symbol_ids.push_back(__latency_local_10);
			}
			{
			auto __latency_local_11 = cast<int_t<>>(request->status_id);
			(void) input->target_request_status_ids.push_back(__latency_local_11);
			}
			{
			auto __latency_local_12 = cast<int_t<>>(request->blocked_reason_id);
			(void) input->target_request_blocked_reason_ids.push_back(__latency_local_12);
			}
			{
			auto __latency_local_13 = cast<int_t<>>(request->project_callable_blocked_reason_id);
			(void) input->target_request_project_callable_blocked_reason_ids.push_back(__latency_local_13);
			}
			{
			auto __latency_local_14 = cast<int_t<>>(request->value);
			(void) input->target_request_values.push_back(__latency_local_14);
			}
		}
		requestIndex = (requestIndex + static_cast<int_t<> >(1));
	}
	auto __latency_local_15 = requests->binary_operands;
	for (auto __latency_local_16 : foreach_range(__latency_local_15)) {
		auto row = __latency_local_16.value_copy();
		{
		auto __latency_local_17 = cast<int_t<>>(row->owner_row_id);
		(void) input->target_binary_owner_row_ids.push_back(__latency_local_17);
		}
		{
		auto __latency_local_18 = cast<int_t<>>(row->left_source_row_id);
		(void) input->target_binary_left_source_row_ids.push_back(__latency_local_18);
		}
		{
		auto __latency_local_19 = cast<int_t<>>(row->right_source_row_id);
		(void) input->target_binary_right_source_row_ids.push_back(__latency_local_19);
		}
		{
		auto __latency_local_20 = cast<int_t<>>(row->left_value);
		(void) input->target_binary_left_values.push_back(__latency_local_20);
		}
		{
		auto __latency_local_21 = cast<int_t<>>(row->right_value);
		(void) input->target_binary_right_values.push_back(__latency_local_21);
		}
	}
	auto __latency_local_22 = requests->local_operands;
	for (auto __latency_local_23 : foreach_range(__latency_local_22)) {
		auto row = __latency_local_23.value_copy();
		{
		auto __latency_local_24 = cast<int_t<>>(row->owner_row_id);
		(void) input->target_local_owner_row_ids.push_back(__latency_local_24);
		}
		{
		auto __latency_local_25 = cast<int_t<>>(row->local_source_row_id);
		(void) input->target_local_source_row_ids.push_back(__latency_local_25);
		}
		{
		auto __latency_local_26 = cast<int_t<>>(row->value_source_row_id);
		(void) input->target_local_value_source_row_ids.push_back(__latency_local_26);
		}
		{
		auto __latency_local_27 = cast<int_t<>>(row->type_ref_id);
		(void) input->target_local_type_ref_ids.push_back(__latency_local_27);
		}
		{
		auto __latency_local_28 = cast<int_t<>>(row->value);
		(void) input->target_local_values.push_back(__latency_local_28);
		}
		{
		auto __latency_local_29 = cast<int_t<>>(row->local_operation_id);
		(void) input->target_local_operation_ids.push_back(__latency_local_29);
		}
	}
	auto __latency_local_30 = requests->call_arguments;
	for (auto __latency_local_31 : foreach_range(__latency_local_30)) {
		auto row = __latency_local_31.value_copy();
		{
		auto __latency_local_32 = cast<int_t<>>(row->owner_row_id);
		(void) input->target_call_argument_owner_row_ids.push_back(__latency_local_32);
		}
		{
		auto __latency_local_33 = cast<int_t<>>(row->argument_source_row_id);
		(void) input->target_call_argument_source_row_ids.push_back(__latency_local_33);
		}
		{
		auto __latency_local_34 = cast<int_t<>>(row->type_ref_id);
		(void) input->target_call_argument_type_ref_ids.push_back(__latency_local_34);
		}
		{
		auto __latency_local_35 = cast<int_t<>>(row->value);
		(void) input->target_call_argument_values.push_back(__latency_local_35);
		}
		{
		auto __latency_local_36 = cast<int_t<>>(row->position);
		(void) input->target_call_argument_positions.push_back(__latency_local_36);
		}
	}
	auto __latency_local_37 = requests->control_flow_operands;
	for (auto __latency_local_38 : foreach_range(__latency_local_37)) {
		auto row = __latency_local_38.value_copy();
		{
		auto __latency_local_39 = cast<int_t<>>(row->owner_row_id);
		(void) input->target_control_owner_row_ids.push_back(__latency_local_39);
		}
		{
		auto __latency_local_40 = cast<int_t<>>(row->condition_source_row_id);
		(void) input->target_control_condition_source_row_ids.push_back(__latency_local_40);
		}
		{
		auto __latency_local_41 = cast<int_t<>>(row->body_first_source_row_id);
		(void) input->target_control_body_first_source_row_ids.push_back(__latency_local_41);
		}
		{
		auto __latency_local_42 = cast<int_t<>>(row->body_last_source_row_id);
		(void) input->target_control_body_last_source_row_ids.push_back(__latency_local_42);
		}
		{
		auto __latency_local_43 = cast<int_t<>>(row->body_terminator_source_row_id);
		(void) input->target_control_body_terminator_source_row_ids.push_back(__latency_local_43);
		}
		{
		auto __latency_local_44 = cast<int_t<>>(row->body_terminator_kind_id);
		(void) input->target_control_body_terminator_kind_ids.push_back(__latency_local_44);
		}
		{
		auto __latency_local_45 = cast<int_t<>>(row->else_body_first_source_row_id);
		(void) input->target_control_else_body_first_source_row_ids.push_back(__latency_local_45);
		}
		{
		auto __latency_local_46 = cast<int_t<>>(row->else_body_last_source_row_id);
		(void) input->target_control_else_body_last_source_row_ids.push_back(__latency_local_46);
		}
		{
		auto __latency_local_47 = cast<int_t<>>(row->condition_type_ref_id);
		(void) input->target_control_condition_type_ref_ids.push_back(__latency_local_47);
		}
		{
		auto __latency_local_48 = cast<int_t<>>(row->condition_value);
		(void) input->target_control_condition_values.push_back(__latency_local_48);
		}
		{
		auto __latency_local_49 = cast<int_t<>>(row->condition_operand_kind_id);
		(void) input->target_control_condition_operand_kind_ids.push_back(__latency_local_49);
		}
		{
		auto __latency_local_50 = cast<int_t<>>(row->condition_local_source_row_id);
		(void) input->target_control_condition_local_source_row_ids.push_back(__latency_local_50);
		}
		{
		auto __latency_local_51 = cast<int_t<>>(row->condition_left_local_source_row_id);
		(void) input->target_control_condition_left_local_source_row_ids.push_back(__latency_local_51);
		}
		{
		auto __latency_local_52 = cast<int_t<>>(row->condition_right_local_source_row_id);
		(void) input->target_control_condition_right_local_source_row_ids.push_back(__latency_local_52);
		}
		{
		auto __latency_local_53 = cast<int_t<>>(row->condition_lhs_type_ref_id);
		(void) input->target_control_condition_lhs_type_ref_ids.push_back(__latency_local_53);
		}
		{
		auto __latency_local_54 = cast<int_t<>>(row->condition_local_operation_id);
		(void) input->target_control_condition_local_operation_ids.push_back(__latency_local_54);
		}
		{
		auto __latency_local_55 = cast<int_t<>>(row->control_operation_id);
		(void) input->target_control_operation_ids.push_back(__latency_local_55);
		}
	}
}

}

#include <scpp/lang/php.hpp>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendEmissionValueRow.hpp"
#include "__types/BackendLocalImmediateTextOperandRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
#include "__types/BackendLoopTextEmissionState.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/BackendStringLiteralOperandRow.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id_and_backend_requests.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_echo_string_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_role_return_value_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_work_id.hpp"
#include "__callable/__latency_fn_backend_loop_text_emission_append_end_after_close_source.hpp"
#include "__callable/__latency_fn_backend_loop_text_emission_append_exit_without_back_edge.hpp"
#include "__callable/__latency_fn_backend_loop_text_emission_begin_for.hpp"
#include "__callable/__latency_fn_backend_loop_text_emission_begin_while.hpp"
#include "__callable/__latency_fn_backend_loop_text_emission_empty_state.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_backend_request_by_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_binary_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_operation_for_bool_loop_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_operation_if_bool_then_else_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_operation_if_bool_then_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_operation_while_bool_loop_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_immediate_text_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_alloca_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_echo_scalar_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_echo_string_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_return_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_runtime_text_coercion_echo_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_string_compare_ternary_echo_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_ternary_select_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_store_from_local_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_store_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_string_release_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_string_store_literal_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_echo_scalar_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_echo_string_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_runtime_text_coercion_echo_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_ternary_select_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_string_literal_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_after_return_branch_boundary.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_echo_local_load_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_echo_string_runtime_calls_from_global_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_function_body_text_from_local_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_if_condition_branch_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_if_end_after_body_last.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_alloca_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_binary_immediate_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_binary_local_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_binary_result_store_from_binary_operand_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_load_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_rhs_load_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_store_binary_result_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_store_loaded_value_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_store_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_return_binary_result_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_return_int_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_return_local_load_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_runtime_string_compare_ternary_echo_calls.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_runtime_string_local_echo_calls.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_runtime_string_local_release_calls.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_runtime_string_local_store_literal_calls.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_runtime_text_coercion_local_echo_calls.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_condition_text_from_control_flow_operand.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_local_operation_is_loaded_binary_immediate.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_local_operation_is_loaded_binary_store_immediate.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_local_slot_index.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_reserve_local_slot_lookup_vectors.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_return_ternary_select_from_condition_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_return_value_text_from_emission_and_backend_requests.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_step_type_policy_supports_type_ref.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_ternary_select_id.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_function_body_text_from_local_emission_and_backend_requests(str::text_builder& lines, BackendEmissionDecisionArtifact& emission, shared_p<BackendRequestAuthorizationArtifact>& requests, const string_t& returnType) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_function_body_text_from_local_emission_and_backend_requests", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[92]);
	vector_t<int_t<std::uint32_t>> localSourceRows = {};
	vector_t<int_t<std::uint32_t>> localTypeRefs = {};
	__latency_fn_llvm_text_from_plan_reserve_local_slot_lookup_vectors(localSourceRows, localTypeRefs, cast<int_t<>>(requests->local_operand_count));
	bool_t returned = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
	string_t activeIfEndLabel = required_cast<string_t>(string_t(""));
	int_t<std::uint32_t> activeIfBodyLastSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	string_t activeIfElseLabel = required_cast<string_t>(string_t(""));
	int_t<std::uint32_t> activeIfElseBodyLastSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	shared_p<BackendLoopTextEmissionState> whileLoopState = __latency_fn_backend_loop_text_emission_empty_state();
	shared_p<BackendLoopTextEmissionState> forLoopState = __latency_fn_backend_loop_text_emission_empty_state();
	int_t<> valueIndex = required_cast<int_t<>>(static_cast<int_t<> >(1));
	while (static_cast<bool>((valueIndex <= cast<int_t<>>(emission->value_count)))) {
		BackendEmissionValueRow value = __latency_fn_backend_emission_decisions_value_by_id_and_backend_requests(emission, requests, __latency_fn_structure_row_ids_uint32_from_int(valueIndex));
		valueIndex = (valueIndex + static_cast<int_t<> >(1));
		if (static_cast<bool>(php::identical(cast<int_t<>>(value->value_id), static_cast<int_t<> >(0)))) {
			continue;
		}
		int_t<std::uint32_t> workId = required_cast<int_t<std::uint32_t>>(__latency_fn_backend_emission_decisions_value_work_id(value));
		BackendControlFlowOperandRow controlFlowOperand = __latency_fn_backend_preflight_requests_control_flow_operand_by_owner_row_id(requests, workId);
		if (static_cast<bool>(((cast<int_t<>>(controlFlowOperand->owner_row_id) > static_cast<int_t<> >(0)) && (php::identical(cast<int_t<>>(controlFlowOperand->control_operation_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_control_operation_if_bool_then_id())) || php::identical(cast<int_t<>>(controlFlowOperand->control_operation_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_control_operation_if_bool_then_else_id())))))) {
			string_t endLabel = required_cast<string_t>((string_t("if_end_") + cast<string_t>(cast<int_t<>>(value->value_id))));
			__latency_fn_llvm_text_from_plan_append_if_condition_branch_text(lines, controlFlowOperand, cast<int_t<>>(value->value_id), localSourceRows);
			activeIfEndLabel = endLabel;
			activeIfBodyLastSourceRowId = controlFlowOperand->body_last_source_row_id;
			if (static_cast<bool>(php::identical(cast<int_t<>>(controlFlowOperand->control_operation_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_control_operation_if_bool_then_else_id())))) {
				activeIfElseLabel = (string_t("if_else_") + cast<string_t>(cast<int_t<>>(value->value_id)));
				activeIfElseBodyLastSourceRowId = controlFlowOperand->else_body_last_source_row_id;
			}
		}
		else {
			if (static_cast<bool>(((cast<int_t<>>(controlFlowOperand->owner_row_id) > static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(controlFlowOperand->control_operation_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_control_operation_while_bool_loop_id()))))) {
				__latency_fn_backend_loop_text_emission_begin_while(whileLoopState, lines, controlFlowOperand, cast<int_t<>>(value->value_id), localSourceRows);
			}
			else {
				if (static_cast<bool>(((cast<int_t<>>(controlFlowOperand->owner_row_id) > static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(controlFlowOperand->control_operation_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_control_operation_for_bool_loop_id()))))) {
					__latency_fn_backend_loop_text_emission_begin_for(forLoopState, lines, controlFlowOperand, cast<int_t<>>(value->value_id), localSourceRows);
				}
			}
		}
		BackendLocalOperandRow localOperand = __latency_fn_backend_preflight_requests_local_operand_by_owner_row_id(requests, workId);
		if (static_cast<bool>(php::identical(cast<int_t<>>(localOperand->owner_row_id), static_cast<int_t<> >(0)))) {
			if (static_cast<bool>(php::identical(cast<int_t<>>(value->value_role_id), cast<int_t<>>(__latency_fn_backend_emission_decisions_value_role_echo_string_id())))) {
				shared_p<BackendStringLiteralOperandRow> literal = __latency_fn_backend_preflight_requests_string_literal_operand_by_owner_row_id(requests, workId);
				if (static_cast<bool>((cast<int_t<>>(literal->owner_row_id) > static_cast<int_t<> >(0)))) {
					__latency_fn_llvm_text_from_plan_append_echo_string_runtime_calls_from_global_id(lines, cast<int_t<>>(value->value_id), cast<int_t<>>(literal->owner_row_id), literal);
				}
			}
			else {
				if (static_cast<bool>((php::identical(cast<int_t<>>(value->value_role_id), cast<int_t<>>(__latency_fn_backend_emission_decisions_value_role_return_value_id())) && (cast<int_t<>>(controlFlowOperand->owner_row_id) > static_cast<int_t<> >(0))))) {
					BackendRequestAuthorizationRow request = __latency_fn_backend_preflight_requests_backend_request_by_id(requests, workId);
					BackendBinaryOperandRow binaryOperand = __latency_fn_backend_preflight_requests_binary_operand_by_owner_row_id(requests, workId);
					if (static_cast<bool>(((php::identical(cast<int_t<>>(request->feature_id), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_ternary_select_id())) && php::identical(cast<int_t<>>(request->lowering_step_kind_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_lowering_step_kind_ternary_select_id()))) && (cast<int_t<>>(binaryOperand->owner_row_id) > static_cast<int_t<> >(0))))) {
						string_t conditionText = required_cast<string_t>(__latency_fn_llvm_text_from_plan_condition_text_from_control_flow_operand(lines, controlFlowOperand, cast<int_t<>>(value->value_id), localSourceRows));
						str::text_builder_append_string(lines, __latency_fn_llvm_text_from_plan_return_ternary_select_from_condition_text(returnType, cast<int_t<>>(value->value_id), conditionText, cast<int_t<>>(binaryOperand->left_value), cast<int_t<>>(binaryOperand->right_value)));
						returned = bool_t(static_cast<bool_t>(true));
					}
				}
				else {
					if (static_cast<bool>((php::identical(cast<int_t<>>(controlFlowOperand->owner_row_id), static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(value->value_role_id), cast<int_t<>>(__latency_fn_backend_emission_decisions_value_role_return_value_id()))))) {
						str::text_builder_append_string(lines, __latency_fn_llvm_text_from_plan_return_value_text_from_emission_and_backend_requests(emission, requests, value, returnType));
						__latency_fn_llvm_text_from_plan_append_after_return_branch_boundary(lines, activeIfEndLabel, activeIfBodyLastSourceRowId, activeIfElseLabel, activeIfElseBodyLastSourceRowId, value->source_row_id, returned);
					}
				}
			}
			__latency_fn_llvm_text_from_plan_append_if_end_after_body_last(lines, activeIfEndLabel, activeIfBodyLastSourceRowId, activeIfElseLabel, activeIfElseBodyLastSourceRowId, value->source_row_id);
			__latency_fn_backend_loop_text_emission_append_end_after_close_source(lines, whileLoopState, value->source_row_id);
			__latency_fn_backend_loop_text_emission_append_end_after_close_source(lines, forLoopState, value->source_row_id);
			continue;
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(localOperand->local_operation_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_local_operation_alloca_id())))) {
			{
			auto __latency_local_0 = localOperand->local_source_row_id;
			(void) localSourceRows.push_back(__latency_local_0);
			}
			{
			auto __latency_local_1 = localOperand->type_ref_id;
			(void) localTypeRefs.push_back(__latency_local_1);
			}
			int_t<> slotIndex = required_cast<int_t<>>(php::count(localSourceRows));
			string_t llvmType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(localOperand->type_ref_id));
			int_t<> align = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(localOperand->type_ref_id));
			__latency_fn_llvm_text_from_plan_append_local_alloca_text(lines, slotIndex, llvmType, align);
		}
		else {
			if (static_cast<bool>(php::identical(cast<int_t<>>(localOperand->local_operation_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_local_operation_store_id())))) {
				int_t<> slotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, localOperand->local_source_row_id));
				if (static_cast<bool>((slotIndex > static_cast<int_t<> >(0)))) {
					string_t llvmType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(localOperand->type_ref_id));
					int_t<> align = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(localOperand->type_ref_id));
					shared_p<BackendLocalImmediateTextOperandRow> immediateText = __latency_fn_backend_preflight_requests_local_immediate_text_operand_by_owner_row_id(requests, workId);
					__latency_fn_llvm_text_from_plan_append_local_store_text(lines, slotIndex, llvmType, align, cast<int_t<>>(localOperand->value), immediateText->value_text);
				}
			}
			else {
				if (static_cast<bool>(php::identical(cast<int_t<>>(localOperand->local_operation_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_local_operation_store_from_local_id())))) {
					int_t<> slotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, localOperand->local_source_row_id));
					int_t<> sourceSlotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, localOperand->value_source_row_id));
					if (static_cast<bool>(((slotIndex > static_cast<int_t<> >(0)) && (sourceSlotIndex > static_cast<int_t<> >(0))))) {
						string_t llvmType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(localOperand->type_ref_id));
						int_t<> align = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(localOperand->type_ref_id));
						__latency_fn_llvm_text_from_plan_append_local_load_text(lines, cast<int_t<>>(value->value_id), sourceSlotIndex, llvmType, align);
						__latency_fn_llvm_text_from_plan_append_local_store_loaded_value_text(lines, slotIndex, llvmType, align, cast<int_t<>>(value->value_id));
					}
				}
				else {
					if (static_cast<bool>(php::identical(cast<int_t<>>(localOperand->local_operation_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_local_operation_string_store_literal_id())))) {
						int_t<> slotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, localOperand->local_source_row_id));
						shared_p<BackendStringLiteralOperandRow> literal = __latency_fn_backend_preflight_requests_string_literal_operand_by_owner_row_id(requests, workId);
						if (static_cast<bool>(((slotIndex > static_cast<int_t<> >(0)) && (cast<int_t<>>(literal->owner_row_id) > static_cast<int_t<> >(0))))) {
							int_t<> align = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(localOperand->type_ref_id));
							__latency_fn_llvm_text_from_plan_append_runtime_string_local_store_literal_calls(lines, cast<int_t<>>(value->value_id), cast<int_t<>>(literal->owner_row_id), slotIndex, align, literal);
						}
					}
					else {
						if (static_cast<bool>(php::identical(cast<int_t<>>(localOperand->local_operation_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_local_operation_string_release_id())))) {
							int_t<> slotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, localOperand->local_source_row_id));
							if (static_cast<bool>((slotIndex > static_cast<int_t<> >(0)))) {
								int_t<> align = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(localOperand->type_ref_id));
								__latency_fn_llvm_text_from_plan_append_runtime_string_local_release_calls(lines, cast<int_t<>>(value->value_id), slotIndex, align);
							}
						}
						else {
							if (static_cast<bool>(php::identical(cast<int_t<>>(localOperand->local_operation_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_local_operation_loaded_ternary_select_id())))) {
								int_t<> slotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, localOperand->local_source_row_id));
								BackendBinaryOperandRow binaryOperand = __latency_fn_backend_preflight_requests_binary_operand_by_owner_row_id(requests, workId);
								if (static_cast<bool>(((slotIndex > static_cast<int_t<> >(0)) && (cast<int_t<>>(binaryOperand->owner_row_id) > static_cast<int_t<> >(0))))) {
									string_t llvmType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(localOperand->type_ref_id));
									int_t<> align = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(localOperand->type_ref_id));
									__latency_fn_llvm_text_from_plan_append_local_load_text(lines, cast<int_t<>>(value->value_id), slotIndex, llvmType, align);
									str::text_builder_append_string(lines, __latency_fn_llvm_text_from_plan_return_ternary_select_from_condition_text(returnType, cast<int_t<>>(value->value_id), (string_t("%local_load_") + cast<string_t>(cast<int_t<>>(value->value_id))), cast<int_t<>>(binaryOperand->left_value), cast<int_t<>>(binaryOperand->right_value)));
									returned = bool_t(static_cast<bool_t>(true));
								}
							}
							else {
								if (static_cast<bool>(php::condition_truthy(__latency_fn_llvm_text_from_plan_local_operation_is_loaded_binary_store_immediate(localOperand->local_operation_id)))) {
									int_t<> slotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, localOperand->local_source_row_id));
									if (static_cast<bool>((slotIndex > static_cast<int_t<> >(0)))) {
										BackendBinaryOperandRow binaryOperand = __latency_fn_backend_preflight_requests_binary_operand_by_owner_row_id(requests, workId);
										if (static_cast<bool>((cast<int_t<>>(binaryOperand->owner_row_id) > static_cast<int_t<> >(0)))) {
											__latency_fn_llvm_text_from_plan_append_local_binary_result_store_from_binary_operand_text(lines, cast<int_t<>>(value->value_id), localOperand, binaryOperand, localSourceRows, localTypeRefs);
										}
										else {
											string_t llvmType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(localOperand->type_ref_id));
											int_t<> align = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(localOperand->type_ref_id));
											__latency_fn_llvm_text_from_plan_append_local_load_text(lines, cast<int_t<>>(value->value_id), slotIndex, llvmType, align);
											int_t<> sourceSlotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, localOperand->value_source_row_id));
											if (static_cast<bool>((sourceSlotIndex > static_cast<int_t<> >(0)))) {
												__latency_fn_llvm_text_from_plan_append_local_rhs_load_text(lines, cast<int_t<>>(value->value_id), sourceSlotIndex, llvmType, align);
												__latency_fn_llvm_text_from_plan_append_local_binary_local_text(lines, cast<int_t<>>(value->value_id), llvmType, localOperand->local_operation_id);
											}
											else {
												__latency_fn_llvm_text_from_plan_append_local_binary_immediate_text(lines, cast<int_t<>>(value->value_id), llvmType, cast<int_t<>>(localOperand->value), localOperand->local_operation_id);
											}
											__latency_fn_llvm_text_from_plan_append_local_store_binary_result_text(lines, slotIndex, llvmType, align, cast<int_t<>>(value->value_id), localOperand->local_operation_id);
										}
									}
								}
								else {
									if (static_cast<bool>(php::condition_truthy(__latency_fn_llvm_text_from_plan_local_operation_is_loaded_binary_immediate(localOperand->local_operation_id)))) {
										int_t<> slotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, localOperand->local_source_row_id));
										if (static_cast<bool>((slotIndex > static_cast<int_t<> >(0)))) {
											string_t llvmType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(localOperand->type_ref_id));
											int_t<> align = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(localOperand->type_ref_id));
											__latency_fn_llvm_text_from_plan_append_local_load_text(lines, cast<int_t<>>(value->value_id), slotIndex, llvmType, align);
											int_t<> sourceSlotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, localOperand->value_source_row_id));
											if (static_cast<bool>((sourceSlotIndex > static_cast<int_t<> >(0)))) {
												__latency_fn_llvm_text_from_plan_append_local_rhs_load_text(lines, cast<int_t<>>(value->value_id), sourceSlotIndex, llvmType, align);
												__latency_fn_llvm_text_from_plan_append_local_binary_local_text(lines, cast<int_t<>>(value->value_id), llvmType, localOperand->local_operation_id);
											}
											else {
												__latency_fn_llvm_text_from_plan_append_local_binary_immediate_text(lines, cast<int_t<>>(value->value_id), llvmType, cast<int_t<>>(localOperand->value), localOperand->local_operation_id);
											}
											__latency_fn_llvm_text_from_plan_append_return_binary_result_text(lines, returnType, cast<int_t<>>(value->value_id), localOperand->local_operation_id);
											returned = bool_t(static_cast<bool_t>(true));
										}
									}
									else {
										if (static_cast<bool>(php::identical(cast<int_t<>>(localOperand->local_operation_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_local_operation_loaded_return_id())))) {
											int_t<> slotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, localOperand->local_source_row_id));
											if (static_cast<bool>((slotIndex > static_cast<int_t<> >(0)))) {
												if (static_cast<bool>(php::condition_truthy(php::not_identical(activeIfEndLabel, string_t(""))))) {
													str::text_builder_append_string(lines, (string_t("  br label %") + cast<string_t>(activeIfEndLabel) + string_t("\n")));
													str::text_builder_append_string(lines, (cast<string_t>(activeIfEndLabel) + string_t(":\n")));
													activeIfEndLabel = string_t("");
													activeIfBodyLastSourceRowId = __latency_fn_structure_row_ids_none_id();
													activeIfElseLabel = string_t("");
													activeIfElseBodyLastSourceRowId = __latency_fn_structure_row_ids_none_id();
												}
												__latency_fn_backend_loop_text_emission_append_exit_without_back_edge(lines, whileLoopState);
												__latency_fn_backend_loop_text_emission_append_exit_without_back_edge(lines, forLoopState);
												string_t llvmType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(localOperand->type_ref_id));
												int_t<> align = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(localOperand->type_ref_id));
												__latency_fn_llvm_text_from_plan_append_local_load_text(lines, cast<int_t<>>(value->value_id), slotIndex, llvmType, align);
												__latency_fn_llvm_text_from_plan_append_return_local_load_text(lines, returnType, cast<int_t<>>(value->value_id));
												returned = bool_t(static_cast<bool_t>(true));
											}
										}
										else {
											if (static_cast<bool>(php::identical(cast<int_t<>>(localOperand->local_operation_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_local_operation_loaded_echo_string_id())))) {
												int_t<> slotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, localOperand->local_source_row_id));
												if (static_cast<bool>(((slotIndex > static_cast<int_t<> >(0)) && __latency_fn_llvm_text_from_plan_step_type_policy_supports_type_ref(__latency_fn_backend_preflight_requests_lowering_step_kind_echo_string_id(), localOperand->type_ref_id)))) {
													int_t<> align = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(localOperand->type_ref_id));
													__latency_fn_llvm_text_from_plan_append_runtime_string_local_echo_calls(lines, cast<int_t<>>(value->value_id), slotIndex, align);
												}
											}
											else {
												if (static_cast<bool>(php::identical(cast<int_t<>>(localOperand->local_operation_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_local_operation_loaded_runtime_text_coercion_echo_id())))) {
													int_t<> slotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, localOperand->local_source_row_id));
													if (static_cast<bool>(((slotIndex > static_cast<int_t<> >(0)) && __latency_fn_llvm_text_from_plan_step_type_policy_supports_type_ref(__latency_fn_backend_preflight_requests_lowering_step_kind_runtime_text_coercion_echo_id(), localOperand->type_ref_id)))) {
														int_t<> align = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(localOperand->type_ref_id));
														__latency_fn_llvm_text_from_plan_append_runtime_text_coercion_local_echo_calls(lines, cast<int_t<>>(value->value_id), slotIndex, localOperand->type_ref_id, align);
													}
												}
												else {
													if (static_cast<bool>(php::identical(cast<int_t<>>(localOperand->local_operation_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_local_operation_loaded_string_compare_ternary_echo_id())))) {
														int_t<> leftSlotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, localOperand->local_source_row_id));
														int_t<> rightSlotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, localOperand->value_source_row_id));
														BackendBinaryOperandRow binaryOperand = __latency_fn_backend_preflight_requests_binary_operand_by_owner_row_id(requests, workId);
														if (static_cast<bool>(((((leftSlotIndex > static_cast<int_t<> >(0)) && (rightSlotIndex > static_cast<int_t<> >(0))) && (cast<int_t<>>(binaryOperand->owner_row_id) > static_cast<int_t<> >(0))) && __latency_fn_llvm_text_from_plan_step_type_policy_supports_type_ref(__latency_fn_backend_preflight_requests_lowering_step_kind_echo_string_id(), localOperand->type_ref_id)))) {
															shared_p<BackendStringLiteralOperandRow> thenLiteral = __latency_fn_backend_preflight_requests_string_literal_operand_by_owner_row_id(requests, binaryOperand->left_source_row_id);
															shared_p<BackendStringLiteralOperandRow> elseLiteral = __latency_fn_backend_preflight_requests_string_literal_operand_by_owner_row_id(requests, binaryOperand->right_source_row_id);
															if (static_cast<bool>(((cast<int_t<>>(thenLiteral->owner_row_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(elseLiteral->owner_row_id) > static_cast<int_t<> >(0))))) {
																int_t<> align = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(localOperand->type_ref_id));
																__latency_fn_llvm_text_from_plan_append_runtime_string_compare_ternary_echo_calls(lines, cast<int_t<>>(value->value_id), leftSlotIndex, rightSlotIndex, __latency_fn_structure_row_ids_uint16_from_int(cast<int_t<>>(binaryOperand->left_value)), align, thenLiteral, elseLiteral);
															}
														}
													}
													else {
														if (static_cast<bool>(php::identical(cast<int_t<>>(localOperand->local_operation_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_local_operation_loaded_echo_scalar_id())))) {
															int_t<> slotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, localOperand->local_source_row_id));
															if (static_cast<bool>(((slotIndex > static_cast<int_t<> >(0)) && __latency_fn_llvm_text_from_plan_step_type_policy_supports_type_ref(__latency_fn_backend_preflight_requests_lowering_step_kind_echo_scalar_id(), localOperand->type_ref_id)))) {
																string_t llvmType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(localOperand->type_ref_id));
																int_t<> align = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(localOperand->type_ref_id));
																__latency_fn_llvm_text_from_plan_append_local_load_text(lines, cast<int_t<>>(value->value_id), slotIndex, llvmType, align);
																__latency_fn_llvm_text_from_plan_append_echo_local_load_text(lines, cast<int_t<>>(value->value_id), localOperand->type_ref_id);
															}
														}
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		__latency_fn_llvm_text_from_plan_append_if_end_after_body_last(lines, activeIfEndLabel, activeIfBodyLastSourceRowId, activeIfElseLabel, activeIfElseBodyLastSourceRowId, value->source_row_id);
		__latency_fn_backend_loop_text_emission_append_end_after_close_source(lines, whileLoopState, value->source_row_id);
		__latency_fn_backend_loop_text_emission_append_end_after_close_source(lines, forLoopState, value->source_row_id);
	}
	if (static_cast<bool>((!returned))) {
		if (static_cast<bool>(php::condition_truthy(php::not_identical(activeIfEndLabel, string_t(""))))) {
			str::text_builder_append_string(lines, (string_t("  br label %") + cast<string_t>(activeIfEndLabel) + string_t("\n")));
			str::text_builder_append_string(lines, (cast<string_t>(activeIfEndLabel) + string_t(":\n")));
			activeIfEndLabel = string_t("");
			activeIfBodyLastSourceRowId = __latency_fn_structure_row_ids_none_id();
			activeIfElseLabel = string_t("");
			activeIfElseBodyLastSourceRowId = __latency_fn_structure_row_ids_none_id();
		}
		BackendEmissionValueRow lastValue = __latency_fn_backend_emission_decisions_value_by_id_and_backend_requests(emission, requests, emission->value_count);
		if (static_cast<bool>((cast<int_t<>>(lastValue->value_id) > static_cast<int_t<> >(0)))) {
			__latency_fn_llvm_text_from_plan_append_return_int_text(lines, returnType, cast<int_t<>>(lastValue->value));
		}
	}
}

}

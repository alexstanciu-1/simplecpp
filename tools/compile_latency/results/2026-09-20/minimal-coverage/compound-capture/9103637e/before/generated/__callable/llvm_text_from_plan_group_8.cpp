#include <scpp/lang/php.hpp>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendEmissionValueRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
#include "__types/BackendLoopTextEmissionState.hpp"
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_binary_immediate_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_binary_local_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_binary_result_store_from_binary_operand_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_load_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_rhs_load_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_store_binary_result_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_local_slot_index.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_local_type_ref_for_slot_index.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_local_operation_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_load_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_return_local_load_text.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_add_immediate_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_return_binary_add_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_return_binary_result_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_binary_result_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_return_binary_result_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_return_int_text.hpp"
#include "__callable/__latency_fn_backend_emission_adapter_routes_type_policy_integer_echo_i64_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_echo_i64_call_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_integer_echo_i64_conversion_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_integer_echo_i64_operand.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_type_policy_supports_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_echo_i64_call_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_echo_local_load_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_local_load_name.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_binary_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_control_flow_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_local_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_backend_emission_decisions_value_by_id.hpp"
#include "__callable/__latency_fn_backend_loop_text_emission_append_end_after_close_source.hpp"
#include "__callable/__latency_fn_backend_loop_text_emission_append_exit_without_back_edge.hpp"
#include "__callable/__latency_fn_backend_loop_text_emission_begin_for.hpp"
#include "__callable/__latency_fn_backend_loop_text_emission_begin_while.hpp"
#include "__callable/__latency_fn_backend_loop_text_emission_empty_state.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_operation_for_bool_loop_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_operation_if_bool_then_else_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_operation_if_bool_then_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_operation_while_bool_loop_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_alloca_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_echo_scalar_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_return_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_runtime_text_coercion_echo_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_store_from_local_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_store_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_echo_scalar_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_lowering_step_kind_runtime_text_coercion_echo_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_echo_local_load_text.hpp"
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
#include "__callable/__latency_fn_llvm_text_from_plan_append_runtime_text_coercion_local_echo_calls.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_estimated_local_body_text_bytes.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_function_body_text_from_local_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_local_operation_is_loaded_binary_immediate.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_local_operation_is_loaded_binary_store_immediate.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_local_slot_index.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_reserve_local_slot_lookup_vectors.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_step_type_policy_supports_type_ref.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
bool_t __latency_fn_llvm_text_from_plan_append_local_binary_result_store_from_binary_operand_text(str::text_builder& lines, int_t<> valueId, BackendLocalOperandRow localOperand, BackendBinaryOperandRow binaryOperand, vector_t<int_t<std::uint32_t>>& localSourceRows, vector_t<int_t<std::uint32_t>>& localTypeRefs) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_local_binary_result_store_from_binary_operand_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[84]);
	int_t<> targetSlotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, localOperand->local_source_row_id));
	int_t<> leftSlotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, binaryOperand->left_source_row_id));
	if (static_cast<bool>(((targetSlotIndex <= static_cast<int_t<> >(0)) || (leftSlotIndex <= static_cast<int_t<> >(0))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	shared_p<SemanticOperatorLookupRow> operatorRow = __latency_fn_semantic_operator_lookup_row_by_local_operation_id(localOperand->local_operation_id);
	int_t<std::uint32_t> operandTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_llvm_text_from_plan_local_type_ref_for_slot_index(localTypeRefs, leftSlotIndex));
	if (static_cast<bool>(php::identical(cast<int_t<>>(operandTypeRefId), static_cast<int_t<> >(0)))) {
		operandTypeRefId = operatorRow->lhs_type_ref_id;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operandTypeRefId), static_cast<int_t<> >(0)))) {
		operandTypeRefId = localOperand->type_ref_id;
	}
	string_t operandType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(cast<int_t<std::uint32_t>>(operandTypeRefId)));
	int_t<> operandAlign = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(cast<int_t<std::uint32_t>>(operandTypeRefId)));
	string_t resultType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(localOperand->type_ref_id));
	int_t<> resultAlign = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(localOperand->type_ref_id));
	__latency_fn_llvm_text_from_plan_append_local_load_text(lines, valueId, leftSlotIndex, operandType, operandAlign);
	int_t<> rightSlotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, binaryOperand->right_source_row_id));
	if (static_cast<bool>((rightSlotIndex > static_cast<int_t<> >(0)))) {
		__latency_fn_llvm_text_from_plan_append_local_rhs_load_text(lines, valueId, rightSlotIndex, operandType, operandAlign);
		__latency_fn_llvm_text_from_plan_append_local_binary_local_text(lines, valueId, operandType, localOperand->local_operation_id);
	}
	else {
		__latency_fn_llvm_text_from_plan_append_local_binary_immediate_text(lines, valueId, operandType, cast<int_t<>>(binaryOperand->right_value), localOperand->local_operation_id);
	}
	__latency_fn_llvm_text_from_plan_append_local_store_binary_result_text(lines, targetSlotIndex, resultType, resultAlign, valueId, localOperand->local_operation_id);
	return bool_t(static_cast<bool_t>(true));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_return_local_load_text(str::text_builder& lines, const string_t& returnType, int_t<> valueId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_return_local_load_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[85]);
	str::text_builder_append_string(lines, string_t("  ret "));
	str::text_builder_append_string(lines, returnType);
	str::text_builder_append_string(lines, string_t(" "));
	__latency_fn_llvm_text_from_plan_append_local_load_name(lines, valueId);
	str::text_builder_append_string(lines, string_t("\n"));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_return_binary_add_text(str::text_builder& lines, const string_t& returnType, int_t<> valueId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_return_binary_add_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[86]);
	__latency_fn_llvm_text_from_plan_append_return_binary_result_text(lines, returnType, valueId, __latency_fn_backend_preflight_requests_local_operation_loaded_add_immediate_id());
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_return_binary_result_text(str::text_builder& lines, const string_t& returnType, int_t<> valueId, int_t<std::uint16_t> localOperationId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_return_binary_result_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[87]);
	str::text_builder_append_string(lines, string_t("  ret "));
	str::text_builder_append_string(lines, returnType);
	str::text_builder_append_string(lines, string_t(" "));
	__latency_fn_llvm_text_from_plan_append_binary_result_name(lines, valueId, cast<int_t<std::uint16_t>>(localOperationId));
	str::text_builder_append_string(lines, string_t("\n"));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_return_int_text(str::text_builder& lines, const string_t& returnType, int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_return_int_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[88]);
	str::text_builder_append_string(lines, string_t("  ret "));
	str::text_builder_append_string(lines, returnType);
	str::text_builder_append_string(lines, string_t(" "));
	str::text_builder_append_int(lines, value);
	str::text_builder_append_string(lines, string_t("\n"));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_echo_i64_call_text(str::text_builder& lines, int_t<> valueId, int_t<std::uint32_t> typeRefId, const string_t& sourceOperand) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_echo_i64_call_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[89]);
	if (static_cast<bool>((!__latency_fn_llvm_text_from_plan_type_policy_supports_type_ref(__latency_fn_backend_emission_adapter_routes_type_policy_integer_echo_i64_id(), cast<int_t<std::uint32_t>>(typeRefId))))) {
		return;
	}
	string_t targetName = required_cast<string_t>((string_t("echo_i64_arg_") + cast<string_t>(valueId)));
	string_t conversionText = required_cast<string_t>(__latency_fn_llvm_text_from_plan_integer_echo_i64_conversion_text(cast<int_t<std::uint32_t>>(typeRefId), sourceOperand, targetName));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(conversionText, string_t(""))))) {
		str::text_builder_append_string(lines, conversionText);
	}
	string_t operand = required_cast<string_t>(__latency_fn_llvm_text_from_plan_integer_echo_i64_operand(cast<int_t<std::uint32_t>>(typeRefId), sourceOperand, targetName));
	if (static_cast<bool>(php::identical(operand, string_t("")))) {
		return;
	}
	str::text_builder_append_string(lines, string_t("  %echo_call_"));
	str::text_builder_append_int(lines, valueId);
	str::text_builder_append_string(lines, string_t(" = call i32 (ptr, ...) @printf(ptr @.scpp_echo_i64_fmt, i64 "));
	str::text_builder_append_string(lines, operand);
	str::text_builder_append_string(lines, string_t(")\n"));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_echo_local_load_text(str::text_builder& lines, int_t<> valueId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_echo_local_load_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[90]);
	__latency_fn_llvm_text_from_plan_append_echo_i64_call_text(lines, valueId, cast<int_t<std::uint32_t>>(typeRefId), __latency_fn_llvm_text_from_plan_local_load_name(valueId));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_function_body_text_from_local_emission(BackendEmissionDecisionArtifact& emission, const string_t& returnType) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::function_body_text_from_local_emission", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[91]);
	str::text_builder lines = required_cast<str::text_builder>(str::text_builder_create());
	str::text_builder_reserve_bytes(lines, __latency_fn_llvm_text_from_plan_estimated_local_body_text_bytes(cast<int_t<>>(emission->value_count)));
	vector_t<int_t<std::uint32_t>> localSourceRows = {};
	vector_t<int_t<std::uint32_t>> localTypeRefs = {};
	__latency_fn_llvm_text_from_plan_reserve_local_slot_lookup_vectors(localSourceRows, localTypeRefs, cast<int_t<>>(emission->local_operand_count));
	bool_t returned = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
	string_t activeIfEndLabel = required_cast<string_t>(string_t(""));
	int_t<std::uint32_t> activeIfBodyLastSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	string_t activeIfElseLabel = required_cast<string_t>(string_t(""));
	int_t<std::uint32_t> activeIfElseBodyLastSourceRowId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	shared_p<BackendLoopTextEmissionState> whileLoopState = __latency_fn_backend_loop_text_emission_empty_state();
	shared_p<BackendLoopTextEmissionState> forLoopState = __latency_fn_backend_loop_text_emission_empty_state();
	auto __latency_local_0 = emission->values;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto value = __latency_local_1.value_copy();
		BackendControlFlowOperandRow controlFlowOperand = __latency_fn_backend_emission_decisions_control_flow_operand_by_owner_row_id(emission, value->value_id);
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
		BackendLocalOperandRow localOperand = __latency_fn_backend_emission_decisions_local_operand_by_owner_row_id(emission, value->value_id);
		if (static_cast<bool>(php::identical(cast<int_t<>>(localOperand->owner_row_id), static_cast<int_t<> >(0)))) {
			__latency_fn_llvm_text_from_plan_append_if_end_after_body_last(lines, activeIfEndLabel, activeIfBodyLastSourceRowId, activeIfElseLabel, activeIfElseBodyLastSourceRowId, value->source_row_id);
			__latency_fn_backend_loop_text_emission_append_end_after_close_source(lines, whileLoopState, value->source_row_id);
			__latency_fn_backend_loop_text_emission_append_end_after_close_source(lines, forLoopState, value->source_row_id);
			continue;
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(localOperand->local_operation_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_local_operation_alloca_id())))) {
			{
			auto __latency_local_2 = localOperand->local_source_row_id;
			(void) localSourceRows.push_back(__latency_local_2);
			}
			{
			auto __latency_local_3 = localOperand->type_ref_id;
			(void) localTypeRefs.push_back(__latency_local_3);
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
					__latency_fn_llvm_text_from_plan_append_local_store_text(lines, slotIndex, llvmType, align, cast<int_t<>>(localOperand->value), string_t(""));
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
					if (static_cast<bool>(php::condition_truthy(__latency_fn_llvm_text_from_plan_local_operation_is_loaded_binary_store_immediate(localOperand->local_operation_id)))) {
						int_t<> slotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, localOperand->local_source_row_id));
						if (static_cast<bool>((slotIndex > static_cast<int_t<> >(0)))) {
							BackendBinaryOperandRow binaryOperand = __latency_fn_backend_emission_decisions_binary_operand_by_owner_row_id(emission, value->value_id);
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
									string_t llvmType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(localOperand->type_ref_id));
									int_t<> align = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(localOperand->type_ref_id));
									__latency_fn_llvm_text_from_plan_append_local_load_text(lines, cast<int_t<>>(value->value_id), slotIndex, llvmType, align);
									__latency_fn_llvm_text_from_plan_append_return_local_load_text(lines, returnType, cast<int_t<>>(value->value_id));
									returned = bool_t(static_cast<bool_t>(true));
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
								else {
									if (static_cast<bool>(php::identical(cast<int_t<>>(localOperand->local_operation_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_local_operation_loaded_runtime_text_coercion_echo_id())))) {
										int_t<> slotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, localOperand->local_source_row_id));
										if (static_cast<bool>(((slotIndex > static_cast<int_t<> >(0)) && __latency_fn_llvm_text_from_plan_step_type_policy_supports_type_ref(__latency_fn_backend_preflight_requests_lowering_step_kind_runtime_text_coercion_echo_id(), localOperand->type_ref_id)))) {
											int_t<> align = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(localOperand->type_ref_id));
											__latency_fn_llvm_text_from_plan_append_runtime_text_coercion_local_echo_calls(lines, cast<int_t<>>(value->value_id), slotIndex, localOperand->type_ref_id, align);
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
		BackendEmissionValueRow lastValue = __latency_fn_backend_emission_decisions_value_by_id(emission, emission->value_count);
		if (static_cast<bool>((cast<int_t<>>(lastValue->value_id) > static_cast<int_t<> >(0)))) {
			__latency_fn_llvm_text_from_plan_append_return_int_text(lines, returnType, cast<int_t<>>(lastValue->value));
		}
	}
	return str::text_builder_take_string(lines);
}

}

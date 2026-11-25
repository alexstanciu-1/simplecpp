#include <scpp/lang/php.hpp>
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_immediate_value_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_immediate_value_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_slot_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_store_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_load_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_slot_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_store_loaded_value_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_binary_result_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_slot_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_store_binary_result_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_load_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_load_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_slot_name.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_condition_operand_local_binary_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_condition_operand_local_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_condition_operand_local_immediate_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_if_condition_left_load_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_if_condition_load_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_if_condition_right_load_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_slot_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_condition_text_from_control_flow_operand.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_local_slot_index.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_local_operation_id.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_immediate_value_text(str::text_builder& lines, int_t<> value, const string_t& valueText) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_immediate_value_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[71]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(valueText, string_t(""))))) {
		str::text_builder_append_string(lines, valueText);
		return;
	}
	str::text_builder_append_int(lines, value);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_local_store_text(str::text_builder& lines, int_t<> slotIndex, const string_t& llvmType, int_t<> align, int_t<> value, const string_t& valueText) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_local_store_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[72]);
	str::text_builder_append_string(lines, string_t("  store "));
	str::text_builder_append_string(lines, llvmType);
	str::text_builder_append_string(lines, string_t(" "));
	__latency_fn_llvm_text_from_plan_append_immediate_value_text(lines, value, valueText);
	str::text_builder_append_string(lines, string_t(", ptr "));
	__latency_fn_llvm_text_from_plan_append_local_slot_name(lines, slotIndex);
	str::text_builder_append_string(lines, string_t(", align "));
	str::text_builder_append_int(lines, align);
	str::text_builder_append_string(lines, string_t("\n"));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_local_store_loaded_value_text(str::text_builder& lines, int_t<> slotIndex, const string_t& llvmType, int_t<> align, int_t<> valueId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_local_store_loaded_value_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[73]);
	str::text_builder_append_string(lines, string_t("  store "));
	str::text_builder_append_string(lines, llvmType);
	str::text_builder_append_string(lines, string_t(" "));
	__latency_fn_llvm_text_from_plan_append_local_load_name(lines, valueId);
	str::text_builder_append_string(lines, string_t(", ptr "));
	__latency_fn_llvm_text_from_plan_append_local_slot_name(lines, slotIndex);
	str::text_builder_append_string(lines, string_t(", align "));
	str::text_builder_append_int(lines, align);
	str::text_builder_append_string(lines, string_t("\n"));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_local_store_binary_result_text(str::text_builder& lines, int_t<> slotIndex, const string_t& llvmType, int_t<> align, int_t<> valueId, int_t<std::uint16_t> localOperationId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_local_store_binary_result_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[74]);
	str::text_builder_append_string(lines, string_t("  store "));
	str::text_builder_append_string(lines, llvmType);
	str::text_builder_append_string(lines, string_t(" "));
	__latency_fn_llvm_text_from_plan_append_binary_result_name(lines, valueId, cast<int_t<std::uint16_t>>(localOperationId));
	str::text_builder_append_string(lines, string_t(", ptr "));
	__latency_fn_llvm_text_from_plan_append_local_slot_name(lines, slotIndex);
	str::text_builder_append_string(lines, string_t(", align "));
	str::text_builder_append_int(lines, align);
	str::text_builder_append_string(lines, string_t("\n"));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_local_load_text(str::text_builder& lines, int_t<> valueId, int_t<> slotIndex, const string_t& llvmType, int_t<> align) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_local_load_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[75]);
	str::text_builder_append_string(lines, string_t("  "));
	__latency_fn_llvm_text_from_plan_append_local_load_name(lines, valueId);
	str::text_builder_append_string(lines, string_t(" = load "));
	str::text_builder_append_string(lines, llvmType);
	str::text_builder_append_string(lines, string_t(", ptr "));
	__latency_fn_llvm_text_from_plan_append_local_slot_name(lines, slotIndex);
	str::text_builder_append_string(lines, string_t(", align "));
	str::text_builder_append_int(lines, align);
	str::text_builder_append_string(lines, string_t("\n"));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_condition_text_from_control_flow_operand(str::text_builder& lines, BackendControlFlowOperandRow controlFlowOperand, int_t<> valueId, vector_t<int_t<std::uint32_t>>& localSourceRows) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::condition_text_from_control_flow_operand", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[76]);
	string_t conditionText = required_cast<string_t>(string_t("false"));
	if (static_cast<bool>(php::identical(cast<int_t<>>(controlFlowOperand->condition_operand_kind_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_control_condition_operand_local_id())))) {
		int_t<> slotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, controlFlowOperand->condition_local_source_row_id));
		if (static_cast<bool>((slotIndex > static_cast<int_t<> >(0)))) {
			string_t llvmType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(controlFlowOperand->condition_type_ref_id));
			int_t<> align = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(controlFlowOperand->condition_type_ref_id));
			str::text_builder_append_string(lines, string_t("  "));
			__latency_fn_llvm_text_from_plan_append_if_condition_load_name(lines, valueId);
			str::text_builder_append_string(lines, string_t(" = load "));
			str::text_builder_append_string(lines, llvmType);
			str::text_builder_append_string(lines, string_t(", ptr "));
			__latency_fn_llvm_text_from_plan_append_local_slot_name(lines, slotIndex);
			str::text_builder_append_string(lines, string_t(", align "));
			str::text_builder_append_int(lines, align);
			str::text_builder_append_string(lines, string_t("\n"));
			conditionText = (string_t("%if_condition_") + cast<string_t>(valueId));
		}
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(controlFlowOperand->condition_operand_kind_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_control_condition_operand_local_binary_id())))) {
			int_t<> leftSlotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, controlFlowOperand->condition_left_local_source_row_id));
			int_t<> rightSlotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, controlFlowOperand->condition_right_local_source_row_id));
			shared_p<SemanticOperatorLookupRow> operatorRow = __latency_fn_semantic_operator_lookup_row_by_local_operation_id(controlFlowOperand->condition_local_operation_id);
			if (static_cast<bool>(((((leftSlotIndex > static_cast<int_t<> >(0)) && (rightSlotIndex > static_cast<int_t<> >(0))) && (cast<int_t<>>(operatorRow->operator_id) > static_cast<int_t<> >(0))) && php::not_identical(operatorRow->llvm_opcode, string_t(""))))) {
				string_t llvmType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(controlFlowOperand->condition_lhs_type_ref_id));
				int_t<> align = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(controlFlowOperand->condition_lhs_type_ref_id));
				str::text_builder_append_string(lines, string_t("  "));
				__latency_fn_llvm_text_from_plan_append_if_condition_left_load_name(lines, valueId);
				str::text_builder_append_string(lines, string_t(" = load "));
				str::text_builder_append_string(lines, llvmType);
				str::text_builder_append_string(lines, string_t(", ptr "));
				__latency_fn_llvm_text_from_plan_append_local_slot_name(lines, leftSlotIndex);
				str::text_builder_append_string(lines, string_t(", align "));
				str::text_builder_append_int(lines, align);
				str::text_builder_append_string(lines, string_t("\n"));
				str::text_builder_append_string(lines, string_t("  "));
				__latency_fn_llvm_text_from_plan_append_if_condition_right_load_name(lines, valueId);
				str::text_builder_append_string(lines, string_t(" = load "));
				str::text_builder_append_string(lines, llvmType);
				str::text_builder_append_string(lines, string_t(", ptr "));
				__latency_fn_llvm_text_from_plan_append_local_slot_name(lines, rightSlotIndex);
				str::text_builder_append_string(lines, string_t(", align "));
				str::text_builder_append_int(lines, align);
				str::text_builder_append_string(lines, string_t("\n"));
				str::text_builder_append_string(lines, string_t("  "));
				__latency_fn_llvm_text_from_plan_append_if_condition_load_name(lines, valueId);
				str::text_builder_append_string(lines, string_t(" = "));
				str::text_builder_append_string(lines, operatorRow->llvm_opcode);
				str::text_builder_append_string(lines, string_t(" "));
				str::text_builder_append_string(lines, llvmType);
				str::text_builder_append_string(lines, string_t(" %if_condition_left_"));
				str::text_builder_append_int(lines, valueId);
				str::text_builder_append_string(lines, string_t(", %if_condition_right_"));
				str::text_builder_append_int(lines, valueId);
				str::text_builder_append_string(lines, string_t("\n"));
				conditionText = (string_t("%if_condition_") + cast<string_t>(valueId));
			}
		}
		else {
			if (static_cast<bool>(php::identical(cast<int_t<>>(controlFlowOperand->condition_operand_kind_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_control_condition_operand_local_immediate_id())))) {
				int_t<> slotIndex = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_local_slot_index(localSourceRows, controlFlowOperand->condition_local_source_row_id));
				shared_p<SemanticOperatorLookupRow> operatorRow = __latency_fn_semantic_operator_lookup_row_by_local_operation_id(controlFlowOperand->condition_local_operation_id);
				if (static_cast<bool>((((slotIndex > static_cast<int_t<> >(0)) && (cast<int_t<>>(operatorRow->operator_id) > static_cast<int_t<> >(0))) && php::not_identical(operatorRow->llvm_opcode, string_t(""))))) {
					string_t llvmType = required_cast<string_t>(__latency_fn_llvm_text_from_plan_llvm_type_for_type_ref(controlFlowOperand->condition_lhs_type_ref_id));
					int_t<> align = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_llvm_align_for_type_ref(controlFlowOperand->condition_lhs_type_ref_id));
					str::text_builder_append_string(lines, string_t("  "));
					__latency_fn_llvm_text_from_plan_append_if_condition_left_load_name(lines, valueId);
					str::text_builder_append_string(lines, string_t(" = load "));
					str::text_builder_append_string(lines, llvmType);
					str::text_builder_append_string(lines, string_t(", ptr "));
					__latency_fn_llvm_text_from_plan_append_local_slot_name(lines, slotIndex);
					str::text_builder_append_string(lines, string_t(", align "));
					str::text_builder_append_int(lines, align);
					str::text_builder_append_string(lines, string_t("\n"));
					str::text_builder_append_string(lines, string_t("  "));
					__latency_fn_llvm_text_from_plan_append_if_condition_load_name(lines, valueId);
					str::text_builder_append_string(lines, string_t(" = "));
					str::text_builder_append_string(lines, operatorRow->llvm_opcode);
					str::text_builder_append_string(lines, string_t(" "));
					str::text_builder_append_string(lines, llvmType);
					str::text_builder_append_string(lines, string_t(" %if_condition_left_"));
					str::text_builder_append_int(lines, valueId);
					str::text_builder_append_string(lines, string_t(", "));
					str::text_builder_append_int(lines, cast<int_t<>>(controlFlowOperand->condition_value));
					str::text_builder_append_string(lines, string_t("\n"));
					conditionText = (string_t("%if_condition_") + cast<string_t>(valueId));
				}
			}
			else {
				if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(controlFlowOperand->condition_value), static_cast<int_t<> >(0))))) {
					conditionText = string_t("true");
				}
			}
		}
	}
	return conditionText;
}

}

#include <scpp/lang/php.hpp>
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_operation_if_bool_then_else_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_if_condition_branch_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_condition_text_from_control_flow_operand.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_while_condition_branch_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_condition_text_from_control_flow_operand.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_for_condition_branch_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_condition_text_from_control_flow_operand.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_if_end_after_body_last__exec.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_after_return_branch_boundary__exec.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_rhs_load_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_rhs_load_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_slot_name.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operation_loaded_add_immediate_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_add_immediate_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_binary_immediate_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_binary_result_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_binary_immediate_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_load_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_binary_instruction_for_local_operation.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_binary_result_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_binary_local_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_load_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_rhs_load_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_binary_instruction_for_local_operation.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_if_condition_branch_text(str::text_builder& lines, BackendControlFlowOperandRow controlFlowOperand, int_t<> valueId, vector_t<int_t<std::uint32_t>>& localSourceRows) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_if_condition_branch_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[77]);
	string_t thenLabel = required_cast<string_t>((string_t("if_then_") + cast<string_t>(valueId)));
	string_t endLabel = required_cast<string_t>((string_t("if_end_") + cast<string_t>(valueId)));
	string_t elseLabel = required_cast<string_t>(endLabel);
	if (static_cast<bool>(((php::identical(cast<int_t<>>(controlFlowOperand->control_operation_id), cast<int_t<>>(__latency_fn_backend_preflight_requests_control_operation_if_bool_then_else_id())) && (cast<int_t<>>(controlFlowOperand->else_body_first_source_row_id) > static_cast<int_t<> >(0))) && (cast<int_t<>>(controlFlowOperand->else_body_last_source_row_id) > static_cast<int_t<> >(0))))) {
		elseLabel = (string_t("if_else_") + cast<string_t>(valueId));
	}
	string_t conditionText = required_cast<string_t>(__latency_fn_llvm_text_from_plan_condition_text_from_control_flow_operand(lines, controlFlowOperand, valueId, localSourceRows));
	str::text_builder_append_string(lines, (string_t("  br i1 ") + cast<string_t>(conditionText) + string_t(", label %") + cast<string_t>(thenLabel) + string_t(", label %") + cast<string_t>(elseLabel) + string_t("\n")));
	str::text_builder_append_string(lines, (cast<string_t>(thenLabel) + string_t(":\n")));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_while_condition_branch_text(str::text_builder& lines, BackendControlFlowOperandRow controlFlowOperand, int_t<> valueId, vector_t<int_t<std::uint32_t>>& localSourceRows) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_while_condition_branch_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[78]);
	string_t conditionLabel = required_cast<string_t>((string_t("while_condition_") + cast<string_t>(valueId)));
	string_t bodyLabel = required_cast<string_t>((string_t("while_body_") + cast<string_t>(valueId)));
	string_t exitLabel = required_cast<string_t>((string_t("while_exit_") + cast<string_t>(valueId)));
	str::text_builder_append_string(lines, (string_t("  br label %") + cast<string_t>(conditionLabel) + string_t("\n")));
	str::text_builder_append_string(lines, (cast<string_t>(conditionLabel) + string_t(":\n")));
	string_t conditionText = required_cast<string_t>(__latency_fn_llvm_text_from_plan_condition_text_from_control_flow_operand(lines, controlFlowOperand, valueId, localSourceRows));
	str::text_builder_append_string(lines, (string_t("  br i1 ") + cast<string_t>(conditionText) + string_t(", label %") + cast<string_t>(bodyLabel) + string_t(", label %") + cast<string_t>(exitLabel) + string_t("\n")));
	str::text_builder_append_string(lines, (cast<string_t>(bodyLabel) + string_t(":\n")));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_for_condition_branch_text(str::text_builder& lines, BackendControlFlowOperandRow controlFlowOperand, int_t<> valueId, vector_t<int_t<std::uint32_t>>& localSourceRows) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_for_condition_branch_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[79]);
	string_t conditionLabel = required_cast<string_t>((string_t("for_condition_") + cast<string_t>(valueId)));
	string_t bodyLabel = required_cast<string_t>((string_t("for_body_") + cast<string_t>(valueId)));
	string_t exitLabel = required_cast<string_t>((string_t("for_exit_") + cast<string_t>(valueId)));
	str::text_builder_append_string(lines, (string_t("  br label %") + cast<string_t>(conditionLabel) + string_t("\n")));
	str::text_builder_append_string(lines, (cast<string_t>(conditionLabel) + string_t(":\n")));
	string_t conditionText = required_cast<string_t>(__latency_fn_llvm_text_from_plan_condition_text_from_control_flow_operand(lines, controlFlowOperand, valueId, localSourceRows));
	str::text_builder_append_string(lines, (string_t("  br i1 ") + cast<string_t>(conditionText) + string_t(", label %") + cast<string_t>(bodyLabel) + string_t(", label %") + cast<string_t>(exitLabel) + string_t("\n")));
	str::text_builder_append_string(lines, (cast<string_t>(bodyLabel) + string_t(":\n")));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_if_end_after_body_last__exec(str::text_builder& lines, string_t& activeIfEndLabel, int_t<std::uint32_t>& activeIfBodyLastSourceRowId, string_t& activeIfElseLabel, int_t<std::uint32_t>& activeIfElseBodyLastSourceRowId, int_t<std::uint32_t> currentSourceRowId) {
	if (static_cast<bool>((php::identical(activeIfEndLabel, string_t("")) || php::identical(cast<int_t<>>(currentSourceRowId), static_cast<int_t<> >(0))))) {
		return;
	}
	if (static_cast<bool>(((cast<int_t<>>(activeIfBodyLastSourceRowId) > static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(currentSourceRowId), cast<int_t<>>(activeIfBodyLastSourceRowId))))) {
		str::text_builder_append_string(lines, (string_t("  br label %") + cast<string_t>(activeIfEndLabel) + string_t("\n")));
		if (static_cast<bool>(php::condition_truthy(php::not_identical(activeIfElseLabel, string_t(""))))) {
			str::text_builder_append_string(lines, (cast<string_t>(activeIfElseLabel) + string_t(":\n")));
			activeIfBodyLastSourceRowId = __latency_fn_structure_row_ids_none_id();
			return;
		}
		str::text_builder_append_string(lines, (cast<string_t>(activeIfEndLabel) + string_t(":\n")));
		activeIfEndLabel = string_t("");
		activeIfBodyLastSourceRowId = __latency_fn_structure_row_ids_none_id();
		activeIfElseBodyLastSourceRowId = __latency_fn_structure_row_ids_none_id();
		return;
	}
	if (static_cast<bool>(((php::not_identical(activeIfElseLabel, string_t("")) && (cast<int_t<>>(activeIfElseBodyLastSourceRowId) > static_cast<int_t<> >(0))) && php::identical(cast<int_t<>>(currentSourceRowId), cast<int_t<>>(activeIfElseBodyLastSourceRowId))))) {
		str::text_builder_append_string(lines, (string_t("  br label %") + cast<string_t>(activeIfEndLabel) + string_t("\n")));
		str::text_builder_append_string(lines, (cast<string_t>(activeIfEndLabel) + string_t(":\n")));
		activeIfEndLabel = string_t("");
		activeIfBodyLastSourceRowId = __latency_fn_structure_row_ids_none_id();
		activeIfElseLabel = string_t("");
		activeIfElseBodyLastSourceRowId = __latency_fn_structure_row_ids_none_id();
	}
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_after_return_branch_boundary__exec(str::text_builder& lines, string_t& activeIfEndLabel, int_t<std::uint32_t>& activeIfBodyLastSourceRowId, string_t& activeIfElseLabel, int_t<std::uint32_t>& activeIfElseBodyLastSourceRowId, int_t<std::uint32_t> currentSourceRowId, bool_t& returned) {
	if (static_cast<bool>((php::identical(activeIfEndLabel, string_t("")) || php::identical(cast<int_t<>>(currentSourceRowId), static_cast<int_t<> >(0))))) {
		returned = bool_t(static_cast<bool_t>(true));
		return;
	}
	if (static_cast<bool>(((php::not_identical(activeIfElseLabel, string_t("")) && (cast<int_t<>>(activeIfBodyLastSourceRowId) > static_cast<int_t<> >(0))) && php::identical(cast<int_t<>>(currentSourceRowId), cast<int_t<>>(activeIfBodyLastSourceRowId))))) {
		str::text_builder_append_string(lines, (cast<string_t>(activeIfElseLabel) + string_t(":\n")));
		activeIfBodyLastSourceRowId = __latency_fn_structure_row_ids_none_id();
		return;
	}
	if (static_cast<bool>(((php::not_identical(activeIfElseLabel, string_t("")) && (cast<int_t<>>(activeIfElseBodyLastSourceRowId) > static_cast<int_t<> >(0))) && php::identical(cast<int_t<>>(currentSourceRowId), cast<int_t<>>(activeIfElseBodyLastSourceRowId))))) {
		activeIfEndLabel = string_t("");
		activeIfElseLabel = string_t("");
		activeIfBodyLastSourceRowId = __latency_fn_structure_row_ids_none_id();
		activeIfElseBodyLastSourceRowId = __latency_fn_structure_row_ids_none_id();
		returned = bool_t(static_cast<bool_t>(true));
		return;
	}
	str::text_builder_append_string(lines, (cast<string_t>(activeIfEndLabel) + string_t(":\n")));
	activeIfEndLabel = string_t("");
	activeIfBodyLastSourceRowId = __latency_fn_structure_row_ids_none_id();
	activeIfElseLabel = string_t("");
	activeIfElseBodyLastSourceRowId = __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_local_rhs_load_text(str::text_builder& lines, int_t<> valueId, int_t<> slotIndex, const string_t& llvmType, int_t<> align) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_local_rhs_load_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[80]);
	str::text_builder_append_string(lines, string_t("  "));
	__latency_fn_llvm_text_from_plan_append_local_rhs_load_name(lines, valueId);
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
void __latency_fn_llvm_text_from_plan_append_local_add_immediate_text(str::text_builder& lines, int_t<> valueId, const string_t& llvmType, int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_local_add_immediate_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[81]);
	__latency_fn_llvm_text_from_plan_append_local_binary_immediate_text(lines, valueId, llvmType, value, __latency_fn_backend_preflight_requests_local_operation_loaded_add_immediate_id());
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_local_binary_immediate_text(str::text_builder& lines, int_t<> valueId, const string_t& llvmType, int_t<> value, int_t<std::uint16_t> localOperationId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_local_binary_immediate_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[82]);
	str::text_builder_append_string(lines, string_t("  "));
	__latency_fn_llvm_text_from_plan_append_binary_result_name(lines, valueId, cast<int_t<std::uint16_t>>(localOperationId));
	str::text_builder_append_string(lines, string_t(" = "));
	str::text_builder_append_string(lines, __latency_fn_llvm_text_from_plan_binary_instruction_for_local_operation(cast<int_t<std::uint16_t>>(localOperationId)));
	str::text_builder_append_string(lines, string_t(" "));
	str::text_builder_append_string(lines, llvmType);
	str::text_builder_append_string(lines, string_t(" "));
	__latency_fn_llvm_text_from_plan_append_local_load_name(lines, valueId);
	str::text_builder_append_string(lines, string_t(", "));
	str::text_builder_append_int(lines, value);
	str::text_builder_append_string(lines, string_t("\n"));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_local_binary_local_text(str::text_builder& lines, int_t<> valueId, const string_t& llvmType, int_t<std::uint16_t> localOperationId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_local_binary_local_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[83]);
	str::text_builder_append_string(lines, string_t("  "));
	__latency_fn_llvm_text_from_plan_append_binary_result_name(lines, valueId, cast<int_t<std::uint16_t>>(localOperationId));
	str::text_builder_append_string(lines, string_t(" = "));
	str::text_builder_append_string(lines, __latency_fn_llvm_text_from_plan_binary_instruction_for_local_operation(cast<int_t<std::uint16_t>>(localOperationId)));
	str::text_builder_append_string(lines, string_t(" "));
	str::text_builder_append_string(lines, llvmType);
	str::text_builder_append_string(lines, string_t(" "));
	__latency_fn_llvm_text_from_plan_append_local_load_name(lines, valueId);
	str::text_builder_append_string(lines, string_t(", "));
	__latency_fn_llvm_text_from_plan_append_local_rhs_load_name(lines, valueId);
	str::text_builder_append_string(lines, string_t("\n"));
}

}

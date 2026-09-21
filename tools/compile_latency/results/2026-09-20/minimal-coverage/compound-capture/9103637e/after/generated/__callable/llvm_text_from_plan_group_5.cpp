#include <scpp/lang/php.hpp>
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_local_slot_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_local_load_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_int_literal_operand_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_slot_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_load_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_rhs_load_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_if_condition_load_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_if_condition_left_load_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_if_condition_right_load_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_binary_add_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_binary_sub_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_binary_add_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_binary_result_name.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_local_operation_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_local_operation_is_loaded_binary_immediate.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_local_operation_is_immediate.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_local_operation_is_loaded_binary_store_immediate.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_local_operation_is_store_immediate.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_binary_instruction_for_local_operation.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_local_operation_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_alloca_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_append_local_slot_name.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_local_slot_name(int_t<> slotIndex) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::local_slot_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[55]);
	return (string_t("%local_") + cast<string_t>(slotIndex));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_local_load_name(int_t<> valueId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::local_load_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[56]);
	return (string_t("%local_load_") + cast<string_t>(valueId));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_int_literal_operand_text(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::int_literal_operand_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[57]);
	str::text_builder text = required_cast<str::text_builder>(str::text_builder_create());
	str::text_builder_append_int(text, value);
	return str::text_builder_take_string(text);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_local_slot_name(str::text_builder& lines, int_t<> slotIndex) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_local_slot_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[58]);
	str::text_builder_append_string(lines, string_t("%local_"));
	str::text_builder_append_int(lines, slotIndex);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_local_load_name(str::text_builder& lines, int_t<> valueId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_local_load_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[59]);
	str::text_builder_append_string(lines, string_t("%local_load_"));
	str::text_builder_append_int(lines, valueId);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_local_rhs_load_name(str::text_builder& lines, int_t<> valueId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_local_rhs_load_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[60]);
	str::text_builder_append_string(lines, string_t("%local_rhs_load_"));
	str::text_builder_append_int(lines, valueId);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_if_condition_load_name(str::text_builder& lines, int_t<> valueId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_if_condition_load_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[61]);
	str::text_builder_append_string(lines, string_t("%if_condition_"));
	str::text_builder_append_int(lines, valueId);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_if_condition_left_load_name(str::text_builder& lines, int_t<> valueId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_if_condition_left_load_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[62]);
	str::text_builder_append_string(lines, string_t("%if_condition_left_"));
	str::text_builder_append_int(lines, valueId);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_if_condition_right_load_name(str::text_builder& lines, int_t<> valueId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_if_condition_right_load_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[63]);
	str::text_builder_append_string(lines, string_t("%if_condition_right_"));
	str::text_builder_append_int(lines, valueId);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_binary_add_name(str::text_builder& lines, int_t<> valueId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_binary_add_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[64]);
	str::text_builder_append_string(lines, string_t("%binary_add_"));
	str::text_builder_append_int(lines, valueId);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_binary_sub_name(str::text_builder& lines, int_t<> valueId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_binary_sub_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[65]);
	str::text_builder_append_string(lines, string_t("%binary_sub_"));
	str::text_builder_append_int(lines, valueId);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_binary_result_name(str::text_builder& lines, int_t<> valueId, int_t<std::uint16_t> localOperationId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_binary_result_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[66]);
	shared_p<SemanticOperatorLookupRow> operatorRow = __latency_fn_semantic_operator_lookup_row_by_local_operation_id(localOperationId);
	if (static_cast<bool>(((cast<int_t<>>(operatorRow->operator_id) > static_cast<int_t<> >(0)) && php::not_identical(operatorRow->binary_result_key, string_t(""))))) {
		str::text_builder_append_string(lines, string_t("%binary_"));
		str::text_builder_append_string(lines, operatorRow->binary_result_key);
		str::text_builder_append_string(lines, string_t("_"));
		str::text_builder_append_int(lines, valueId);
		return;
	}
	__latency_fn_llvm_text_from_plan_append_binary_add_name(lines, valueId);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
bool_t __latency_fn_llvm_text_from_plan_local_operation_is_loaded_binary_immediate(int_t<std::uint16_t> localOperationId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::local_operation_is_loaded_binary_immediate", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[67]);
	return __latency_fn_semantic_operator_lookup_local_operation_is_immediate(localOperationId);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
bool_t __latency_fn_llvm_text_from_plan_local_operation_is_loaded_binary_store_immediate(int_t<std::uint16_t> localOperationId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::local_operation_is_loaded_binary_store_immediate", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[68]);
	return __latency_fn_semantic_operator_lookup_local_operation_is_store_immediate(localOperationId);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_binary_instruction_for_local_operation(int_t<std::uint16_t> localOperationId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::binary_instruction_for_local_operation", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[69]);
	shared_p<SemanticOperatorLookupRow> operatorRow = __latency_fn_semantic_operator_lookup_row_by_local_operation_id(localOperationId);
	if (static_cast<bool>(((cast<int_t<>>(operatorRow->operator_id) > static_cast<int_t<> >(0)) && php::not_identical(operatorRow->llvm_opcode, string_t(""))))) {
		return operatorRow->llvm_opcode;
	}
	return string_t("add");
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
void __latency_fn_llvm_text_from_plan_append_local_alloca_text(str::text_builder& lines, int_t<> slotIndex, const string_t& llvmType, int_t<> align) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::append_local_alloca_text", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[70]);
	str::text_builder_append_string(lines, string_t("  "));
	__latency_fn_llvm_text_from_plan_append_local_slot_name(lines, slotIndex);
	str::text_builder_append_string(lines, string_t(" = alloca "));
	str::text_builder_append_string(lines, llvmType);
	str::text_builder_append_string(lines, string_t(", align "));
	str::text_builder_append_int(lines, align);
	str::text_builder_append_string(lines, string_t("\n"));
}

}

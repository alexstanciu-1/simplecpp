#include <scpp/lang/php.hpp>
#include "__types/LlvmPrimitiveAbiDescriptorRow.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_text_for_descriptor.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_text_for_return_type.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_primitive_abi_by_llvm_type.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_text_for_descriptor.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_text_for_type_ref.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_primitive_abi_by_type_ref_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_policy_blocked_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_policy_bool_zext_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_policy_i32_return_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_policy_signed_extend_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_policy_unsigned_extend_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_text_for_descriptor.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_policy_i64_trunc_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_main_exit_policy_i64_trunc_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_policy_bool_zext_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_main_exit_policy_bool_zext_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_policy_signed_extend_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_main_exit_policy_signed_extend_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_policy_i32_return_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_main_exit_policy_i32_return_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_policy_unsigned_extend_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_main_exit_policy_unsigned_extend_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_policy_blocked_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_main_exit_policy_blocked_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_primitive_abi_descriptor.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_primitive_abi_descriptors.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_llvm_primitive_abi_descriptors.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_policy_blocked_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_primitive_abi_by_type_ref_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_primitive_abi_descriptor.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_primitive_abi_descriptors.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_main_exit_policy_blocked_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_primitive_abi_by_llvm_type.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_primitive_abi_descriptor.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_primitive_abi_descriptors.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_main_exit_text_for_return_type(const string_t& returnType) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::main_exit_text_for_return_type", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[32]);
	shared_p<LlvmPrimitiveAbiDescriptorRow> descriptor = __latency_fn_llvm_text_from_plan_primitive_abi_by_llvm_type(returnType);
	return __latency_fn_llvm_text_from_plan_main_exit_text_for_descriptor(descriptor);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_main_exit_text_for_type_ref(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::main_exit_text_for_type_ref", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[33]);
	shared_p<LlvmPrimitiveAbiDescriptorRow> descriptor = __latency_fn_llvm_text_from_plan_primitive_abi_by_type_ref_id(cast<int_t<std::uint32_t>>(typeRefId));
	return __latency_fn_llvm_text_from_plan_main_exit_text_for_descriptor(descriptor);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_main_exit_text_for_descriptor(shared_p<LlvmPrimitiveAbiDescriptorRow> descriptor) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::main_exit_text_for_descriptor", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[34]);
	string_t returnType = required_cast<string_t>(descriptor->llvm_type);
	if (static_cast<bool>(php::identical(cast<int_t<>>(descriptor->main_exit_policy_id), cast<int_t<>>(__latency_fn_llvm_text_from_plan_main_exit_policy_bool_zext_id())))) {
		string_t text = required_cast<string_t>(string_t("  %exit_value = zext i1 %run_value to i32\n"));
		text = (cast<string_t>(text) + string_t("  ret i32 %exit_value\n"));
		return text;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(descriptor->main_exit_policy_id), cast<int_t<>>(__latency_fn_llvm_text_from_plan_main_exit_policy_signed_extend_id())))) {
		string_t text = required_cast<string_t>((string_t("  %exit_value = sext ") + cast<string_t>(returnType) + string_t(" %run_value to i32\n")));
		text = (cast<string_t>(text) + string_t("  ret i32 %exit_value\n"));
		return text;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(descriptor->main_exit_policy_id), cast<int_t<>>(__latency_fn_llvm_text_from_plan_main_exit_policy_unsigned_extend_id())))) {
		string_t text = required_cast<string_t>((string_t("  %exit_value = zext ") + cast<string_t>(returnType) + string_t(" %run_value to i32\n")));
		text = (cast<string_t>(text) + string_t("  ret i32 %exit_value\n"));
		return text;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(descriptor->main_exit_policy_id), cast<int_t<>>(__latency_fn_llvm_text_from_plan_main_exit_policy_i32_return_id())))) {
		return string_t("  ret i32 %run_value\n");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(descriptor->main_exit_policy_id), cast<int_t<>>(__latency_fn_llvm_text_from_plan_main_exit_policy_blocked_id())))) {
		return string_t("  ret i32 0\n");
	}
	string_t text = required_cast<string_t>((string_t("  %exit_value = trunc ") + cast<string_t>(returnType) + string_t(" %run_value to i32\n")));
	text = (cast<string_t>(text) + string_t("  ret i32 %exit_value\n"));
	return text;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_llvm_text_from_plan_main_exit_policy_i64_trunc_id() {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::main_exit_policy_i64_trunc_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[35]);
	return __latency_fn_primitive_abi_adapter_matrix_main_exit_policy_i64_trunc_id();
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_llvm_text_from_plan_main_exit_policy_bool_zext_id() {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::main_exit_policy_bool_zext_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[36]);
	return __latency_fn_primitive_abi_adapter_matrix_main_exit_policy_bool_zext_id();
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_llvm_text_from_plan_main_exit_policy_signed_extend_id() {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::main_exit_policy_signed_extend_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[37]);
	return __latency_fn_primitive_abi_adapter_matrix_main_exit_policy_signed_extend_id();
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_llvm_text_from_plan_main_exit_policy_i32_return_id() {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::main_exit_policy_i32_return_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[38]);
	return __latency_fn_primitive_abi_adapter_matrix_main_exit_policy_i32_return_id();
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_llvm_text_from_plan_main_exit_policy_unsigned_extend_id() {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::main_exit_policy_unsigned_extend_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[39]);
	return __latency_fn_primitive_abi_adapter_matrix_main_exit_policy_unsigned_extend_id();
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_llvm_text_from_plan_main_exit_policy_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::main_exit_policy_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[40]);
	return __latency_fn_primitive_abi_adapter_matrix_main_exit_policy_blocked_id();
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
shared_p<LlvmPrimitiveAbiDescriptorRow> __latency_fn_llvm_text_from_plan_primitive_abi_descriptor(int_t<std::uint32_t> typeRefId, const string_t& llvmType, int_t<> alignBytes, int_t<std::uint16_t> mainExitPolicyId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::primitive_abi_descriptor", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[41]);
	shared_p<LlvmPrimitiveAbiDescriptorRow> row = create<LlvmPrimitiveAbiDescriptorRow>();
	row->type_ref_id = typeRefId;
	row->llvm_type = llvmType;
	row->align_bytes = __latency_fn_structure_row_ids_uint16_from_int(alignBytes);
	row->main_exit_policy_id = mainExitPolicyId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
vector_t<shared_p<LlvmPrimitiveAbiDescriptorRow>> __latency_fn_llvm_text_from_plan_primitive_abi_descriptors() {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::primitive_abi_descriptors", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[42]);
	return __latency_fn_primitive_abi_adapter_matrix_llvm_primitive_abi_descriptors();
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
shared_p<LlvmPrimitiveAbiDescriptorRow> __latency_fn_llvm_text_from_plan_primitive_abi_by_type_ref_id(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::primitive_abi_by_type_ref_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[43]);
	auto __latency_local_0 = __latency_fn_llvm_text_from_plan_primitive_abi_descriptors();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->type_ref_id), cast<int_t<>>(typeRefId)))) {
			return row;
		}
	}
	return __latency_fn_llvm_text_from_plan_primitive_abi_descriptor(cast<int_t<std::uint32_t>>(typeRefId), string_t(""), static_cast<int_t<> >(0), __latency_fn_llvm_text_from_plan_main_exit_policy_blocked_id());
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
shared_p<LlvmPrimitiveAbiDescriptorRow> __latency_fn_llvm_text_from_plan_primitive_abi_by_llvm_type(const string_t& llvmType) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::primitive_abi_by_llvm_type", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[44]);
	auto __latency_local_0 = __latency_fn_llvm_text_from_plan_primitive_abi_descriptors();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(row->llvm_type, llvmType))) {
			return row;
		}
	}
	return __latency_fn_llvm_text_from_plan_primitive_abi_descriptor(__latency_fn_structure_row_ids_none_id(), llvmType, static_cast<int_t<> >(0), __latency_fn_llvm_text_from_plan_main_exit_policy_blocked_id());
}

}

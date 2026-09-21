#include <scpp/lang/php.hpp>
#include "__types/LlvmPrimitiveAbiDescriptorRow.hpp"
#include "__types/PrimitiveAbiAdapterMatrixArtifact.hpp"
#include "__types/PrimitiveAbiAdapterRow.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_build.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_llvm_descriptor_from_row.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_llvm_primitive_abi_descriptors.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_llvm_descriptor_by_type_ref_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_llvm_descriptor_from_row.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_row_by_type_ref_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_status_blocked_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_status_name.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_status_ready_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_f32_arithmetic_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_f64_arithmetic_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_i64_add_sub_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_name.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_none_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_blocked_reason_floating_main_exit_policy_not_ready_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_blocked_reason_lowering_numeric_adapter_not_ready_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_blocked_reason_name.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_blocked_reason_native_exit_policy_not_ready_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_blocked_reason_non_numeric_type_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_blocked_reason_name.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_name.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_row_debug_string.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_status_name.hpp"
#include "__callable/__latency_fn_type_ref_identity_name.hpp"
#include "__callable/__latency_fn_type_traits_numeric_operator_mask_name.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_debug_string.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
vector_t<shared_p<LlvmPrimitiveAbiDescriptorRow>> __latency_fn_primitive_abi_adapter_matrix_llvm_primitive_abi_descriptors() {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::llvm_primitive_abi_descriptors", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[43]);
	vector_t<shared_p<LlvmPrimitiveAbiDescriptorRow>> rows = {};
	php::vector_reserve(rows, static_cast<int_t<> >(12));
	shared_p<PrimitiveAbiAdapterMatrixArtifact> artifact = __latency_fn_primitive_abi_adapter_matrix_build();
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		{
		auto __latency_local_2 = __latency_fn_primitive_abi_adapter_matrix_llvm_descriptor_from_row(row);
		(void) rows.push_back(__latency_local_2);
		}
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
shared_p<LlvmPrimitiveAbiDescriptorRow> __latency_fn_primitive_abi_adapter_matrix_llvm_descriptor_by_type_ref_id(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::llvm_descriptor_by_type_ref_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[44]);
	shared_p<PrimitiveAbiAdapterRow> row = __latency_fn_primitive_abi_adapter_matrix_row_by_type_ref_id(cast<int_t<std::uint32_t>>(typeRefId));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(row->adapter_id), static_cast<int_t<> >(0))))) {
		return __latency_fn_primitive_abi_adapter_matrix_llvm_descriptor_from_row(row);
	}
	shared_p<LlvmPrimitiveAbiDescriptorRow> empty = create<LlvmPrimitiveAbiDescriptorRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
string_t __latency_fn_primitive_abi_adapter_matrix_status_name(int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[45]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_status_ready_id())))) {
		return string_t("ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_status_blocked_id())))) {
		return string_t("blocked");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
string_t __latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_name(int_t<std::uint16_t> familyId) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::lowering_adapter_family_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[46]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(familyId), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_i64_add_sub_id())))) {
		return string_t("i64_add_sub");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(familyId), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_f32_arithmetic_id())))) {
		return string_t("f32_arithmetic");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(familyId), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_f64_arithmetic_id())))) {
		return string_t("f64_arithmetic");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(familyId), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_none_id())))) {
		return string_t("none");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
string_t __latency_fn_primitive_abi_adapter_matrix_blocked_reason_name(int_t<std::uint16_t> reasonId) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::blocked_reason_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[47]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(reasonId), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_blocked_reason_none_id())))) {
		return string_t("");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(reasonId), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_blocked_reason_non_numeric_type_id())))) {
		return string_t("non_numeric_type");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(reasonId), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_blocked_reason_lowering_numeric_adapter_not_ready_id())))) {
		return string_t("lowering_numeric_adapter_not_ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(reasonId), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_blocked_reason_native_exit_policy_not_ready_id())))) {
		return string_t("native_exit_policy_not_ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(reasonId), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_blocked_reason_floating_main_exit_policy_not_ready_id())))) {
		return string_t("floating_main_exit_policy_not_ready");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
string_t __latency_fn_primitive_abi_adapter_matrix_row_debug_string(shared_p<PrimitiveAbiAdapterRow> row) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::row_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[48]);
	return (string_t("primitive_abi_adapter:") + cast<string_t>(__latency_fn_type_ref_identity_name(row->type_ref_id)) + string_t(":llvm=") + cast<string_t>(row->llvm_type) + string_t(":align=") + cast<string_t>(cast<int_t<>>(row->align_bytes)) + string_t(":main_exit=") + cast<string_t>(__latency_fn_primitive_abi_adapter_matrix_status_name(row->main_exit_status_id)) + string_t(":native=") + cast<string_t>(__latency_fn_primitive_abi_adapter_matrix_status_name(row->native_status_id)) + string_t(":numeric_lowering=") + cast<string_t>(__latency_fn_primitive_abi_adapter_matrix_status_name(row->numeric_lowering_status_id)) + string_t(":adapter=") + cast<string_t>(__latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_name(row->lowering_adapter_family_id)) + string_t(":operators=") + cast<string_t>(__latency_fn_type_traits_numeric_operator_mask_name(row->operator_mask)) + string_t(":reason=") + cast<string_t>(__latency_fn_primitive_abi_adapter_matrix_blocked_reason_name(row->blocked_reason_id)));
}

}

namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
string_t __latency_fn_primitive_abi_adapter_matrix_debug_string(shared_p<PrimitiveAbiAdapterMatrixArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[49]);
	return (string_t("primitive_abi_adapter_matrix:rows=") + cast<string_t>(cast<int_t<>>(artifact->row_count)) + string_t(":llvm_ready=") + cast<string_t>(cast<int_t<>>(artifact->llvm_value_ready_count)) + string_t(":main_exit_ready=") + cast<string_t>(cast<int_t<>>(artifact->main_exit_ready_count)) + string_t(":numeric_lowering_ready=") + cast<string_t>(cast<int_t<>>(artifact->numeric_lowering_ready_count)) + string_t(":native_ready=") + cast<string_t>(cast<int_t<>>(artifact->native_ready_count)) + string_t(":blocked=") + cast<string_t>(cast<int_t<>>(artifact->blocked_count)) + string_t(":signed=") + cast<string_t>(cast<int_t<>>(artifact->signed_integer_count)) + string_t(":unsigned=") + cast<string_t>(cast<int_t<>>(artifact->unsigned_integer_count)) + string_t(":floating=") + cast<string_t>(cast<int_t<>>(artifact->floating_point_count)));
}

}

namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_primitive_abi_adapter_matrix_stable_hash(shared_p<PrimitiveAbiAdapterMatrixArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[50]);
	string_t identity = required_cast<string_t>((string_t("primitive_abi_adapter_matrix:v1:") + cast<string_t>(cast<int_t<>>(artifact->row_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->llvm_value_ready_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->main_exit_ready_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->numeric_lowering_ready_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->native_ready_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->blocked_count))));
	return php::stable_hash_string_u64(identity);
}

}

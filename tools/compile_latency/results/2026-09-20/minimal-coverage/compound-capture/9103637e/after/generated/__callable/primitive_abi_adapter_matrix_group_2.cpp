#include <scpp/lang/php.hpp>
#include "__types/PrimitiveAbiAdapterMatrixArtifact.hpp"
#include "__types/PrimitiveAbiAdapterRow.hpp"
#include "__types/ProviderTraitDescriptorRow.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_llvm_type_for_abi_shape.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_llvm_value_status_for_descriptor.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_status_blocked_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_status_ready_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_main_exit_policy_blocked_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_main_exit_policy_for_descriptor.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_main_exit_status_for_descriptor.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_status_blocked_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_status_ready_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_numeric_lowering_status_for_descriptor.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_status_blocked_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_status_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_primitive_f32_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_primitive_f64_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_primitive_i32_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_primitive_i64_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_floating_point_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_signed_integer_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_native_status_for_descriptor.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_status_blocked_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_status_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_primitive_i16_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_primitive_i1_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_primitive_i32_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_primitive_i64_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_signed_integer_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_f32_arithmetic_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_f64_arithmetic_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_for_descriptor.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_i64_add_sub_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_none_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_numeric_lowering_status_for_descriptor.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_status_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_primitive_f32_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_primitive_f64_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_floating_point_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_signed_integer_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_blocked_reason_floating_main_exit_policy_not_ready_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_blocked_reason_for_descriptor.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_blocked_reason_lowering_numeric_adapter_not_ready_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_blocked_reason_native_exit_policy_not_ready_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_blocked_reason_non_numeric_type_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_native_status_for_descriptor.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_numeric_lowering_status_for_descriptor.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_status_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_floating_point_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_none_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_append_row.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_floating_point_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_signed_integer_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_unsigned_integer_id.hpp"
namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_primitive_abi_adapter_matrix_llvm_value_status_for_descriptor(ProviderTraitDescriptorRow descriptor) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::llvm_value_status_for_descriptor", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[26]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(__latency_fn_primitive_abi_adapter_matrix_llvm_type_for_abi_shape(descriptor->abi_shape_id), string_t(""))))) {
		return __latency_fn_primitive_abi_adapter_matrix_status_ready_id();
	}
	return __latency_fn_primitive_abi_adapter_matrix_status_blocked_id();
}

}

namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_primitive_abi_adapter_matrix_main_exit_status_for_descriptor(ProviderTraitDescriptorRow descriptor) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::main_exit_status_for_descriptor", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[27]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_main_exit_policy_for_descriptor(descriptor)), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_main_exit_policy_blocked_id())))) {
		return __latency_fn_primitive_abi_adapter_matrix_status_blocked_id();
	}
	return __latency_fn_primitive_abi_adapter_matrix_status_ready_id();
}

}

namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_primitive_abi_adapter_matrix_numeric_lowering_status_for_descriptor(ProviderTraitDescriptorRow descriptor) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::numeric_lowering_status_for_descriptor", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[28]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(descriptor->numeric_kind_id), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_signed_integer_id())) && (php::identical(cast<int_t<>>(descriptor->abi_shape_id), cast<int_t<>>(__latency_fn_type_traits_abi_shape_primitive_i64_id())) || php::identical(cast<int_t<>>(descriptor->abi_shape_id), cast<int_t<>>(__latency_fn_type_traits_abi_shape_primitive_i32_id())))))) {
		return __latency_fn_primitive_abi_adapter_matrix_status_ready_id();
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(descriptor->numeric_kind_id), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_floating_point_id())) && php::identical(cast<int_t<>>(descriptor->abi_shape_id), cast<int_t<>>(__latency_fn_type_traits_abi_shape_primitive_f32_id()))))) {
		return __latency_fn_primitive_abi_adapter_matrix_status_ready_id();
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(descriptor->numeric_kind_id), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_floating_point_id())) && php::identical(cast<int_t<>>(descriptor->abi_shape_id), cast<int_t<>>(__latency_fn_type_traits_abi_shape_primitive_f64_id()))))) {
		return __latency_fn_primitive_abi_adapter_matrix_status_ready_id();
	}
	return __latency_fn_primitive_abi_adapter_matrix_status_blocked_id();
}

}

namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_primitive_abi_adapter_matrix_native_status_for_descriptor(ProviderTraitDescriptorRow descriptor) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::native_status_for_descriptor", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[29]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(descriptor->abi_shape_id), cast<int_t<>>(__latency_fn_type_traits_abi_shape_primitive_i1_id())))) {
		return __latency_fn_primitive_abi_adapter_matrix_status_ready_id();
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(descriptor->numeric_kind_id), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_signed_integer_id())) && ((php::identical(cast<int_t<>>(descriptor->abi_shape_id), cast<int_t<>>(__latency_fn_type_traits_abi_shape_primitive_i64_id())) || php::identical(cast<int_t<>>(descriptor->abi_shape_id), cast<int_t<>>(__latency_fn_type_traits_abi_shape_primitive_i16_id()))) || php::identical(cast<int_t<>>(descriptor->abi_shape_id), cast<int_t<>>(__latency_fn_type_traits_abi_shape_primitive_i32_id())))))) {
		return __latency_fn_primitive_abi_adapter_matrix_status_ready_id();
	}
	return __latency_fn_primitive_abi_adapter_matrix_status_blocked_id();
}

}

namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_for_descriptor(ProviderTraitDescriptorRow descriptor) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::lowering_adapter_family_for_descriptor", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[30]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_numeric_lowering_status_for_descriptor(descriptor)), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_status_ready_id()))))) {
		return __latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_none_id();
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(descriptor->numeric_kind_id), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_floating_point_id())) && php::identical(cast<int_t<>>(descriptor->abi_shape_id), cast<int_t<>>(__latency_fn_type_traits_abi_shape_primitive_f32_id()))))) {
		return __latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_f32_arithmetic_id();
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(descriptor->numeric_kind_id), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_floating_point_id())) && php::identical(cast<int_t<>>(descriptor->abi_shape_id), cast<int_t<>>(__latency_fn_type_traits_abi_shape_primitive_f64_id()))))) {
		return __latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_f64_arithmetic_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(descriptor->numeric_kind_id), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_signed_integer_id())))) {
		return __latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_i64_add_sub_id();
	}
	return __latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_none_id();
}

}

namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_primitive_abi_adapter_matrix_blocked_reason_for_descriptor(ProviderTraitDescriptorRow descriptor) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::blocked_reason_for_descriptor", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[31]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(descriptor->numeric_kind_id), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_none_id())))) {
		return __latency_fn_primitive_abi_adapter_matrix_blocked_reason_non_numeric_type_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(descriptor->numeric_kind_id), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_floating_point_id())))) {
		return __latency_fn_primitive_abi_adapter_matrix_blocked_reason_floating_main_exit_policy_not_ready_id();
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_native_status_for_descriptor(descriptor)), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_status_ready_id()))))) {
		return __latency_fn_primitive_abi_adapter_matrix_blocked_reason_native_exit_policy_not_ready_id();
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_numeric_lowering_status_for_descriptor(descriptor)), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_status_ready_id()))))) {
		return __latency_fn_primitive_abi_adapter_matrix_blocked_reason_lowering_numeric_adapter_not_ready_id();
	}
	return __latency_fn_primitive_abi_adapter_matrix_blocked_reason_none_id();
}

}

namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
void __latency_fn_primitive_abi_adapter_matrix_append_row(shared_p<PrimitiveAbiAdapterMatrixArtifact>& artifact, shared_p<PrimitiveAbiAdapterRow> row) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::append_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[32]);
	(void) artifact->rows.append(row);
	artifact->row_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->rows));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->llvm_value_status_id), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_status_ready_id())))) {
		artifact->llvm_value_ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->llvm_value_ready_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->main_exit_status_id), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_status_ready_id())))) {
		artifact->main_exit_ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->main_exit_ready_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->numeric_lowering_status_id), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_status_ready_id())))) {
		artifact->numeric_lowering_ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->numeric_lowering_ready_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->native_status_id), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_status_ready_id())))) {
		artifact->native_ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->native_ready_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(row->blocked_reason_id), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_blocked_reason_none_id()))))) {
		artifact->blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->blocked_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->numeric_kind_id), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_signed_integer_id())))) {
		artifact->signed_integer_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->signed_integer_count) + static_cast<int_t<> >(1)));
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->numeric_kind_id), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_unsigned_integer_id())))) {
			artifact->unsigned_integer_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->unsigned_integer_count) + static_cast<int_t<> >(1)));
		}
		else {
			if (static_cast<bool>(php::identical(cast<int_t<>>(row->numeric_kind_id), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_floating_point_id())))) {
				artifact->floating_point_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->floating_point_count) + static_cast<int_t<> >(1)));
			}
		}
	}
}

}

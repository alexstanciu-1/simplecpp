#include <scpp/lang/php.hpp>
#include "__types/LlvmPrimitiveAbiDescriptorRow.hpp"
#include "__types/PrimitiveAbiAdapterMatrixArtifact.hpp"
#include "__types/PrimitiveAbiAdapterRow.hpp"
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_adapter_descriptors.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_append_row.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_build.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_new_artifact.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_build.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_row_by_type_ref_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_main_return_supported_by_row.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_status_ready_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_main_return_supported_by_row.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_main_return_supported_for_type_ref.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_row_by_type_ref_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_none_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_f32_arithmetic_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_f64_arithmetic_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_i64_add_sub_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_numeric_operator_feature_has_lowering_adapter.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_row_by_type_ref_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_status_ready_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_binary_operator_rows.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_none_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_comparison_operator_feature_has_lowering_adapter.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_comparison_result_type_ref_supported.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_row_by_type_ref_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_status_ready_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_lookup_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_ref.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_comparison_result_type_ref_supported.hpp"
#include "__callable/__latency_fn_type_capability_readiness_condition_truthiness_supported_for_type_ref.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_none_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_logical_operator_feature_has_lowering_adapter.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_row_by_type_ref_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_status_ready_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_lookup_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_ref.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_integer_echo_i64_supported_for_type_ref.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_row_by_type_ref_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_status_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_signed_integer_id.hpp"
#include "__callable/__latency_fn_type_traits_numeric_kind_unsigned_integer_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_llvm_descriptor_from_row.hpp"
namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
shared_p<PrimitiveAbiAdapterMatrixArtifact> __latency_fn_primitive_abi_adapter_matrix_build() {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::build", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[33]);
	shared_p<PrimitiveAbiAdapterMatrixArtifact> artifact = __latency_fn_primitive_abi_adapter_matrix_new_artifact();
	auto __latency_local_0 = __latency_fn_primitive_abi_adapter_matrix_adapter_descriptors();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto descriptor = __latency_local_1.value_copy();
		__latency_fn_primitive_abi_adapter_matrix_append_row(artifact, descriptor);
	}
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
shared_p<PrimitiveAbiAdapterRow> __latency_fn_primitive_abi_adapter_matrix_row_by_type_ref_id(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::row_by_type_ref_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[34]);
	shared_p<PrimitiveAbiAdapterMatrixArtifact> artifact = __latency_fn_primitive_abi_adapter_matrix_build();
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->type_ref_id), cast<int_t<>>(typeRefId)))) {
			return row;
		}
	}
	shared_p<PrimitiveAbiAdapterRow> empty = create<PrimitiveAbiAdapterRow>();
	return empty;
}

}

namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
bool_t __latency_fn_primitive_abi_adapter_matrix_main_return_supported_by_row(shared_p<PrimitiveAbiAdapterRow> row) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::main_return_supported_by_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[35]);
	return ((((cast<int_t<>>(row->adapter_id) > static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(row->llvm_value_status_id), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_status_ready_id()))) && php::identical(cast<int_t<>>(row->main_exit_status_id), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_status_ready_id()))) && php::identical(cast<int_t<>>(row->native_status_id), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_status_ready_id())));
}

}

namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
bool_t __latency_fn_primitive_abi_adapter_matrix_main_return_supported_for_type_ref(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::main_return_supported_for_type_ref", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[36]);
	return __latency_fn_primitive_abi_adapter_matrix_main_return_supported_by_row(__latency_fn_primitive_abi_adapter_matrix_row_by_type_ref_id(cast<int_t<std::uint32_t>>(typeRefId)));
}

}

namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
bool_t __latency_fn_primitive_abi_adapter_matrix_numeric_operator_feature_has_lowering_adapter(int_t<std::uint32_t> typeRefId, int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::numeric_operator_feature_has_lowering_adapter", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[37]);
	shared_p<PrimitiveAbiAdapterRow> row = __latency_fn_primitive_abi_adapter_matrix_row_by_type_ref_id(cast<int_t<std::uint32_t>>(typeRefId));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(row->numeric_lowering_status_id), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_status_ready_id()))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	if (static_cast<bool>(((php::not_identical(cast<int_t<>>(row->lowering_adapter_family_id), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_i64_add_sub_id())) && php::not_identical(cast<int_t<>>(row->lowering_adapter_family_id), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_f32_arithmetic_id()))) && php::not_identical(cast<int_t<>>(row->lowering_adapter_family_id), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_lowering_adapter_family_f64_arithmetic_id()))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	auto __latency_local_0 = __latency_fn_semantic_operator_lookup_binary_operator_rows();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto operatorRow = __latency_local_1.value_copy();
		if (static_cast<bool>(((((((php::identical(cast<int_t<>>(operatorRow->feature_id), cast<int_t<>>(featureId)) && php::identical(cast<int_t<>>(operatorRow->lhs_type_ref_id), cast<int_t<>>(row->type_ref_id))) && php::identical(cast<int_t<>>(operatorRow->rhs_type_ref_id), cast<int_t<>>(row->type_ref_id))) && php::not_identical(cast<int_t<>>(operatorRow->lowering_adapter_id), cast<int_t<>>(__latency_fn_operation_readiness_lowering_adapter_none_id()))) && php::not_identical(cast<int_t<>>(operatorRow->lowering_step_kind_id), cast<int_t<>>(__latency_fn_structure_row_ids_none_kind_id()))) && php::not_identical(cast<int_t<>>(operatorRow->local_immediate_operation_id), cast<int_t<>>(__latency_fn_structure_row_ids_none_kind_id()))) && php::not_identical(operatorRow->llvm_opcode, string_t(""))))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
bool_t __latency_fn_primitive_abi_adapter_matrix_comparison_operator_feature_has_lowering_adapter(int_t<std::uint32_t> typeRefId, int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::comparison_operator_feature_has_lowering_adapter", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[38]);
	int_t<std::uint32_t> lookupTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_semantic_operator_lookup_lookup_type_ref_id(typeRefId));
	shared_p<PrimitiveAbiAdapterRow> row = __latency_fn_primitive_abi_adapter_matrix_row_by_type_ref_id(cast<int_t<std::uint32_t>>(lookupTypeRefId));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(row->llvm_value_status_id), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_status_ready_id()))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(row->native_status_id), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_status_ready_id()))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	shared_p<SemanticOperatorLookupRow> operatorRow = __latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_ref(featureId, lookupTypeRefId);
	return (((((((cast<int_t<>>(operatorRow->operator_id) > static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(operatorRow->lhs_type_ref_id), cast<int_t<>>(lookupTypeRefId))) && php::identical(cast<int_t<>>(operatorRow->rhs_type_ref_id), cast<int_t<>>(lookupTypeRefId))) && __latency_fn_primitive_abi_adapter_matrix_comparison_result_type_ref_supported(operatorRow->result_type_ref_id)) && php::not_identical(cast<int_t<>>(operatorRow->lowering_adapter_id), cast<int_t<>>(__latency_fn_operation_readiness_lowering_adapter_none_id()))) && php::not_identical(cast<int_t<>>(operatorRow->lowering_step_kind_id), cast<int_t<>>(__latency_fn_structure_row_ids_none_kind_id()))) && php::not_identical(operatorRow->llvm_opcode, string_t("")));
}

}

namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
bool_t __latency_fn_primitive_abi_adapter_matrix_comparison_result_type_ref_supported(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::comparison_result_type_ref_supported", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[39]);
	return __latency_fn_type_capability_readiness_condition_truthiness_supported_for_type_ref(typeRefId);
}

}

namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
bool_t __latency_fn_primitive_abi_adapter_matrix_logical_operator_feature_has_lowering_adapter(int_t<std::uint32_t> typeRefId, int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::logical_operator_feature_has_lowering_adapter", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[40]);
	int_t<std::uint32_t> lookupTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_semantic_operator_lookup_lookup_type_ref_id(typeRefId));
	shared_p<PrimitiveAbiAdapterRow> row = __latency_fn_primitive_abi_adapter_matrix_row_by_type_ref_id(cast<int_t<std::uint32_t>>(lookupTypeRefId));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(row->llvm_value_status_id), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_status_ready_id()))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(row->native_status_id), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_status_ready_id()))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	shared_p<SemanticOperatorLookupRow> operatorRow = __latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_ref(featureId, lookupTypeRefId);
	return (((((((cast<int_t<>>(operatorRow->operator_id) > static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(operatorRow->lhs_type_ref_id), cast<int_t<>>(lookupTypeRefId))) && php::identical(cast<int_t<>>(operatorRow->rhs_type_ref_id), cast<int_t<>>(lookupTypeRefId))) && php::identical(cast<int_t<>>(operatorRow->result_type_ref_id), cast<int_t<>>(lookupTypeRefId))) && php::not_identical(cast<int_t<>>(operatorRow->lowering_adapter_id), cast<int_t<>>(__latency_fn_operation_readiness_lowering_adapter_none_id()))) && php::not_identical(cast<int_t<>>(operatorRow->lowering_step_kind_id), cast<int_t<>>(__latency_fn_structure_row_ids_none_kind_id()))) && php::not_identical(operatorRow->llvm_opcode, string_t("")));
}

}

namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
bool_t __latency_fn_primitive_abi_adapter_matrix_integer_echo_i64_supported_for_type_ref(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::integer_echo_i64_supported_for_type_ref", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[41]);
	shared_p<PrimitiveAbiAdapterRow> row = __latency_fn_primitive_abi_adapter_matrix_row_by_type_ref_id(cast<int_t<std::uint32_t>>(typeRefId));
	if (static_cast<bool>(((php::identical(cast<int_t<>>(row->adapter_id), static_cast<int_t<> >(0)) || php::not_identical(cast<int_t<>>(row->llvm_value_status_id), cast<int_t<>>(__latency_fn_primitive_abi_adapter_matrix_status_ready_id()))) || (php::not_identical(cast<int_t<>>(row->numeric_kind_id), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_signed_integer_id())) && php::not_identical(cast<int_t<>>(row->numeric_kind_id), cast<int_t<>>(__latency_fn_type_traits_numeric_kind_unsigned_integer_id())))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return (((php::identical(cast<int_t<>>(row->width_bits), static_cast<int_t<> >(8)) || php::identical(cast<int_t<>>(row->width_bits), static_cast<int_t<> >(16))) || php::identical(cast<int_t<>>(row->width_bits), static_cast<int_t<> >(32))) || php::identical(cast<int_t<>>(row->width_bits), static_cast<int_t<> >(64)));
}

}

namespace scpp { extern const int __latency_lines_primitive_abi_adapter_matrix[]; }
namespace scpp {
shared_p<LlvmPrimitiveAbiDescriptorRow> __latency_fn_primitive_abi_adapter_matrix_llvm_descriptor_from_row(shared_p<PrimitiveAbiAdapterRow> row) {
	SCPP_CALL_DEPTH_GUARD("primitive_abi_adapter_matrix::llvm_descriptor_from_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/primitive_abi_adapter_matrix.phs", __latency_lines_primitive_abi_adapter_matrix[42]);
	shared_p<LlvmPrimitiveAbiDescriptorRow> descriptor = create<LlvmPrimitiveAbiDescriptorRow>();
	descriptor->type_ref_id = row->type_ref_id;
	descriptor->main_exit_policy_id = row->main_exit_policy_id;
	descriptor->align_bytes = row->align_bytes;
	descriptor->llvm_type = row->llvm_type;
	return descriptor;
}

}

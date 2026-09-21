#include <scpp/lang/php.hpp>
#include "__types/FeatureOperationRouteDescriptorRow.hpp"
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_non_numeric_type_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_numeric_operator_not_in_trait_mask_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_backend_numeric_abi_not_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_condition_truthiness_not_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_string_concat_lifetime_not_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_operation_readiness_route_kind_default_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_operation_readiness_route_kind_numeric_binary_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_operation_readiness_feature_route_descriptor.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_callable_return_type_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_echo_scalar_runtime_helper_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_echo_string_runtime_helper_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_for_bool_loop_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_if_bool_then_block_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_literal_scalar_value_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_return_lowering_blocked_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_runtime_text_coercion_echo_runtime_helper_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_scalar_local_assignment_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_scalar_local_load_return_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_scalar_local_storage_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_string_concat_runtime_concat_assign_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_while_bool_loop_id.hpp"
#include "__callable/__latency_fn_operation_readiness_feature_operation_route_descriptors.hpp"
#include "__callable/__latency_fn_operation_readiness_feature_route_descriptor.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_echo_scalar_i64_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_echo_string_runtime_abi_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_if_bool_then_block_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_llvm_const_scalar_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_llvm_local_alloca_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_llvm_local_load_return_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_llvm_local_store_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_none_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_runtime_text_coercion_echo_runtime_abi_id.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_string_concat_runtime_abi_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_callable_return_value_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_echo_scalar_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_echo_string_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_for_bool_loop_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_if_bool_then_block_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_literal_scalar_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_local_assignment_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_local_load_return_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_local_storage_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_return_lowering_boundary_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_runtime_text_coercion_echo_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_string_concat_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_while_bool_loop_id.hpp"
#include "__callable/__latency_fn_operation_readiness_route_kind_default_id.hpp"
#include "__callable/__latency_fn_operation_readiness_route_kind_numeric_binary_id.hpp"
#include "__callable/__latency_fn_operation_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_operation_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_binary_operator_rows.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_callable_return_lowering_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_callable_return_type_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_echo_scalar_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_echo_string_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_for_bool_loop_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_if_bool_then_block_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_literal_scalar_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_local_assignment_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_local_load_return_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_local_storage_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_runtime_text_coercion_echo_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_string_concat_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_while_bool_loop_id.hpp"
namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_operation_readiness_blocked_reason_non_numeric_type_id() {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::blocked_reason_non_numeric_type_id", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[101]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_operation_readiness_blocked_reason_numeric_operator_not_in_trait_mask_id() {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::blocked_reason_numeric_operator_not_in_trait_mask_id", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[102]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5));
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_operation_readiness_blocked_reason_backend_numeric_abi_not_ready_id() {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::blocked_reason_backend_numeric_abi_not_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[103]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(6));
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_operation_readiness_blocked_reason_condition_truthiness_not_ready_id() {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::blocked_reason_condition_truthiness_not_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[104]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(7));
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_operation_readiness_blocked_reason_string_concat_lifetime_not_ready_id() {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::blocked_reason_string_concat_lifetime_not_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[105]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(8));
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_operation_readiness_route_kind_default_id() {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::route_kind_default_id", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[106]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_operation_readiness_route_kind_numeric_binary_id() {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::route_kind_numeric_binary_id", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[107]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
FeatureOperationRouteDescriptorRow __latency_fn_operation_readiness_feature_route_descriptor(int_t<std::uint16_t> featureId, int_t<std::uint16_t> routeKindId, int_t<std::uint16_t> contractId, int_t<std::uint16_t> operationKindId, int_t<std::uint16_t> loweringAdapterId, int_t<std::uint16_t> defaultStatusId) {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::feature_route_descriptor", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[108]);
	FeatureOperationRouteDescriptorRow row = FeatureOperationRouteDescriptorRow{};
	row->feature_id = featureId;
	row->route_kind_id = routeKindId;
	row->contract_id = contractId;
	row->operation_kind_id = operationKindId;
	row->lowering_adapter_id = loweringAdapterId;
	row->default_status_id = defaultStatusId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
vector_t<FeatureOperationRouteDescriptorRow> __latency_fn_operation_readiness_feature_operation_route_descriptors() {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::feature_operation_route_descriptors", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[109]);
	vector_t<FeatureOperationRouteDescriptorRow> rows = {};
	php::vector_reserve(rows, static_cast<int_t<> >(30));
	{
	auto __latency_local_0 = __latency_fn_operation_readiness_feature_route_descriptor(__latency_fn_type_capability_readiness_feature_callable_return_type_id(), __latency_fn_operation_readiness_route_kind_default_id(), __latency_fn_operation_readiness_contract_callable_return_type_id(), __latency_fn_operation_readiness_operation_kind_callable_return_value_id(), __latency_fn_operation_readiness_lowering_adapter_none_id(), __latency_fn_operation_readiness_status_ready_id());
	(void) rows.push_back(__latency_local_0);
	}
	{
	auto __latency_local_1 = __latency_fn_operation_readiness_feature_route_descriptor(__latency_fn_type_capability_readiness_feature_callable_return_lowering_id(), __latency_fn_operation_readiness_route_kind_default_id(), __latency_fn_operation_readiness_contract_return_lowering_blocked_id(), __latency_fn_operation_readiness_operation_kind_return_lowering_boundary_id(), __latency_fn_operation_readiness_lowering_adapter_none_id(), __latency_fn_operation_readiness_status_blocked_id());
	(void) rows.push_back(__latency_local_1);
	}
	{
	auto __latency_local_2 = __latency_fn_operation_readiness_feature_route_descriptor(__latency_fn_type_capability_readiness_feature_literal_scalar_id(), __latency_fn_operation_readiness_route_kind_default_id(), __latency_fn_operation_readiness_contract_literal_scalar_value_id(), __latency_fn_operation_readiness_operation_kind_literal_scalar_id(), __latency_fn_operation_readiness_lowering_adapter_llvm_const_scalar_id(), __latency_fn_operation_readiness_status_ready_id());
	(void) rows.push_back(__latency_local_2);
	}
	auto __latency_local_3 = __latency_fn_semantic_operator_lookup_binary_operator_rows();
	for (auto __latency_local_4 : foreach_range(__latency_local_3)) {
		auto operatorRow = __latency_local_4.value_copy();
		int_t<std::uint16_t> operatorStatusId = required_cast<int_t<std::uint16_t>>(__latency_fn_operation_readiness_status_blocked_id());
		if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(operatorRow->lowering_adapter_id), cast<int_t<>>(__latency_fn_operation_readiness_lowering_adapter_none_id()))))) {
			operatorStatusId = __latency_fn_operation_readiness_status_ready_id();
		}
		{
		auto __latency_local_5 = __latency_fn_operation_readiness_feature_route_descriptor(operatorRow->feature_id, __latency_fn_operation_readiness_route_kind_numeric_binary_id(), operatorRow->contract_id, operatorRow->operation_kind_id, operatorRow->lowering_adapter_id, cast<int_t<std::uint16_t>>(operatorStatusId));
		(void) rows.push_back(__latency_local_5);
		}
	}
	{
	auto __latency_local_6 = __latency_fn_operation_readiness_feature_route_descriptor(__latency_fn_type_capability_readiness_feature_local_storage_id(), __latency_fn_operation_readiness_route_kind_default_id(), __latency_fn_operation_readiness_contract_scalar_local_storage_id(), __latency_fn_operation_readiness_operation_kind_local_storage_id(), __latency_fn_operation_readiness_lowering_adapter_llvm_local_alloca_id(), __latency_fn_operation_readiness_status_ready_id());
	(void) rows.push_back(__latency_local_6);
	}
	{
	auto __latency_local_7 = __latency_fn_operation_readiness_feature_route_descriptor(__latency_fn_type_capability_readiness_feature_local_assignment_id(), __latency_fn_operation_readiness_route_kind_default_id(), __latency_fn_operation_readiness_contract_scalar_local_assignment_id(), __latency_fn_operation_readiness_operation_kind_local_assignment_id(), __latency_fn_operation_readiness_lowering_adapter_llvm_local_store_id(), __latency_fn_operation_readiness_status_ready_id());
	(void) rows.push_back(__latency_local_7);
	}
	{
	auto __latency_local_8 = __latency_fn_operation_readiness_feature_route_descriptor(__latency_fn_type_capability_readiness_feature_echo_scalar_id(), __latency_fn_operation_readiness_route_kind_default_id(), __latency_fn_operation_readiness_contract_echo_scalar_runtime_helper_id(), __latency_fn_operation_readiness_operation_kind_echo_scalar_id(), __latency_fn_operation_readiness_lowering_adapter_echo_scalar_i64_id(), __latency_fn_operation_readiness_status_ready_id());
	(void) rows.push_back(__latency_local_8);
	}
	{
	auto __latency_local_9 = __latency_fn_operation_readiness_feature_route_descriptor(__latency_fn_type_capability_readiness_feature_echo_string_id(), __latency_fn_operation_readiness_route_kind_default_id(), __latency_fn_operation_readiness_contract_echo_string_runtime_helper_id(), __latency_fn_operation_readiness_operation_kind_echo_string_id(), __latency_fn_operation_readiness_lowering_adapter_echo_string_runtime_abi_id(), __latency_fn_operation_readiness_status_ready_id());
	(void) rows.push_back(__latency_local_9);
	}
	{
	auto __latency_local_10 = __latency_fn_operation_readiness_feature_route_descriptor(__latency_fn_type_capability_readiness_feature_runtime_text_coercion_echo_id(), __latency_fn_operation_readiness_route_kind_default_id(), __latency_fn_operation_readiness_contract_runtime_text_coercion_echo_runtime_helper_id(), __latency_fn_operation_readiness_operation_kind_runtime_text_coercion_echo_id(), __latency_fn_operation_readiness_lowering_adapter_runtime_text_coercion_echo_runtime_abi_id(), __latency_fn_operation_readiness_status_ready_id());
	(void) rows.push_back(__latency_local_10);
	}
	{
	auto __latency_local_11 = __latency_fn_operation_readiness_feature_route_descriptor(__latency_fn_type_capability_readiness_feature_string_concat_id(), __latency_fn_operation_readiness_route_kind_default_id(), __latency_fn_operation_readiness_contract_string_concat_runtime_concat_assign_id(), __latency_fn_operation_readiness_operation_kind_string_concat_id(), __latency_fn_operation_readiness_lowering_adapter_string_concat_runtime_abi_id(), __latency_fn_operation_readiness_status_blocked_id());
	(void) rows.push_back(__latency_local_11);
	}
	{
	auto __latency_local_12 = __latency_fn_operation_readiness_feature_route_descriptor(__latency_fn_type_capability_readiness_feature_if_bool_then_block_id(), __latency_fn_operation_readiness_route_kind_default_id(), __latency_fn_operation_readiness_contract_if_bool_then_block_id(), __latency_fn_operation_readiness_operation_kind_if_bool_then_block_id(), __latency_fn_operation_readiness_lowering_adapter_if_bool_then_block_id(), __latency_fn_operation_readiness_status_ready_id());
	(void) rows.push_back(__latency_local_12);
	}
	{
	auto __latency_local_13 = __latency_fn_operation_readiness_feature_route_descriptor(__latency_fn_type_capability_readiness_feature_while_bool_loop_id(), __latency_fn_operation_readiness_route_kind_default_id(), __latency_fn_operation_readiness_contract_while_bool_loop_id(), __latency_fn_operation_readiness_operation_kind_while_bool_loop_id(), __latency_fn_operation_readiness_lowering_adapter_if_bool_then_block_id(), __latency_fn_operation_readiness_status_ready_id());
	(void) rows.push_back(__latency_local_13);
	}
	{
	auto __latency_local_14 = __latency_fn_operation_readiness_feature_route_descriptor(__latency_fn_type_capability_readiness_feature_for_bool_loop_id(), __latency_fn_operation_readiness_route_kind_default_id(), __latency_fn_operation_readiness_contract_for_bool_loop_id(), __latency_fn_operation_readiness_operation_kind_for_bool_loop_id(), __latency_fn_operation_readiness_lowering_adapter_if_bool_then_block_id(), __latency_fn_operation_readiness_status_ready_id());
	(void) rows.push_back(__latency_local_14);
	}
	{
	auto __latency_local_15 = __latency_fn_operation_readiness_feature_route_descriptor(__latency_fn_type_capability_readiness_feature_local_load_return_id(), __latency_fn_operation_readiness_route_kind_default_id(), __latency_fn_operation_readiness_contract_scalar_local_load_return_id(), __latency_fn_operation_readiness_operation_kind_local_load_return_id(), __latency_fn_operation_readiness_lowering_adapter_llvm_local_load_return_id(), __latency_fn_operation_readiness_status_ready_id());
	(void) rows.push_back(__latency_local_15);
	}
	return rows;
}

}

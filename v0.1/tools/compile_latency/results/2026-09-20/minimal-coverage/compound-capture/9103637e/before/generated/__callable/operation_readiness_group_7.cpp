#include <scpp/lang/php.hpp>
#include "__types/FeatureOperationRouteDescriptorRow.hpp"
#include "__callable/__latency_fn_operation_readiness_feature_operation_route_by_feature_id.hpp"
#include "__callable/__latency_fn_operation_readiness_feature_operation_route_descriptors.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_divide_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_equal_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_greater_than_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_greater_than_or_equal_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_identical_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_less_than_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_less_than_or_equal_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_logical_and_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_logical_or_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_minus_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_modulo_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_multiply_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_not_equal_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_not_identical_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_binary_operator_plus_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_callable_return_value_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_echo_scalar_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_echo_string_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_for_bool_loop_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_if_bool_then_block_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_literal_scalar_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_local_assignment_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_local_load_return_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_local_storage_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_name.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_return_lowering_boundary_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_runtime_text_coercion_echo_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_string_concat_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_unary_operator_logical_not_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_unary_operator_minus_id.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_while_bool_loop_id.hpp"
#include "__callable/__latency_fn_operation_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_operation_readiness_status_name.hpp"
#include "__callable/__latency_fn_operation_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_backend_lowering_not_reintroduced_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_backend_numeric_abi_not_ready_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_condition_truthiness_not_ready_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_name.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_non_numeric_type_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_numeric_operator_not_in_trait_mask_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_string_concat_lifetime_not_ready_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_unknown_type_trait_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_unsupported_numeric_operator_id.hpp"
namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
FeatureOperationRouteDescriptorRow __latency_fn_operation_readiness_feature_operation_route_by_feature_id(int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::feature_operation_route_by_feature_id", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[110]);
	auto __latency_local_0 = __latency_fn_operation_readiness_feature_operation_route_descriptors();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->feature_id), cast<int_t<>>(featureId)))) {
			return row;
		}
	}
	FeatureOperationRouteDescriptorRow empty = FeatureOperationRouteDescriptorRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
string_t __latency_fn_operation_readiness_operation_kind_name(int_t<std::uint16_t> operationKindId) {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::operation_kind_name", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[111]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_callable_return_value_id())))) {
		return string_t("callable_return_value");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_return_lowering_boundary_id())))) {
		return string_t("return_lowering_boundary");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_literal_scalar_id())))) {
		return string_t("literal_scalar");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_binary_operator_plus_id())))) {
		return string_t("binary_operator_plus");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_local_storage_id())))) {
		return string_t("local_storage");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_local_assignment_id())))) {
		return string_t("local_assignment");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_echo_scalar_id())))) {
		return string_t("echo_scalar");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_echo_string_id())))) {
		return string_t("echo_string");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_runtime_text_coercion_echo_id())))) {
		return string_t("runtime_text_coercion_echo");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_string_concat_id())))) {
		return string_t("string_concat");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_if_bool_then_block_id())))) {
		return string_t("if_bool_then_block");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_while_bool_loop_id())))) {
		return string_t("while_bool_loop");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_for_bool_loop_id())))) {
		return string_t("for_bool_loop");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_local_load_return_id())))) {
		return string_t("local_load_return");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_binary_operator_minus_id())))) {
		return string_t("binary_operator_minus");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_binary_operator_multiply_id())))) {
		return string_t("binary_operator_multiply");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_binary_operator_divide_id())))) {
		return string_t("binary_operator_divide");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_binary_operator_modulo_id())))) {
		return string_t("binary_operator_modulo");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_binary_operator_equal_id())))) {
		return string_t("binary_operator_equal");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_binary_operator_not_equal_id())))) {
		return string_t("binary_operator_not_equal");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_binary_operator_less_than_id())))) {
		return string_t("binary_operator_less_than");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_binary_operator_greater_than_id())))) {
		return string_t("binary_operator_greater_than");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_binary_operator_less_than_or_equal_id())))) {
		return string_t("binary_operator_less_than_or_equal");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_binary_operator_greater_than_or_equal_id())))) {
		return string_t("binary_operator_greater_than_or_equal");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_binary_operator_identical_id())))) {
		return string_t("binary_operator_identical");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_binary_operator_not_identical_id())))) {
		return string_t("binary_operator_not_identical");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_binary_operator_logical_and_id())))) {
		return string_t("binary_operator_logical_and");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_binary_operator_logical_or_id())))) {
		return string_t("binary_operator_logical_or");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_unary_operator_minus_id())))) {
		return string_t("unary_operator_minus");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(operationKindId), cast<int_t<>>(__latency_fn_operation_readiness_operation_kind_unary_operator_logical_not_id())))) {
		return string_t("unary_operator_logical_not");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
string_t __latency_fn_operation_readiness_status_name(int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[112]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_operation_readiness_status_ready_id())))) {
		return string_t("ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_operation_readiness_status_blocked_id())))) {
		return string_t("blocked");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
string_t __latency_fn_operation_readiness_blocked_reason_name(int_t<std::uint16_t> blockedReasonId) {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::blocked_reason_name", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[113]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_operation_readiness_blocked_reason_none_id())))) {
		return string_t("");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_operation_readiness_blocked_reason_backend_lowering_not_reintroduced_id())))) {
		return string_t("backend_lowering_not_reintroduced");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_operation_readiness_blocked_reason_unsupported_numeric_operator_id())))) {
		return string_t("unsupported_numeric_operator");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_operation_readiness_blocked_reason_unknown_type_trait_id())))) {
		return string_t("unknown_type_trait");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_operation_readiness_blocked_reason_non_numeric_type_id())))) {
		return string_t("non_numeric_type");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_operation_readiness_blocked_reason_numeric_operator_not_in_trait_mask_id())))) {
		return string_t("numeric_operator_not_in_trait_mask");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_operation_readiness_blocked_reason_backend_numeric_abi_not_ready_id())))) {
		return string_t("backend_numeric_abi_not_ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_operation_readiness_blocked_reason_condition_truthiness_not_ready_id())))) {
		return string_t("condition_truthiness_not_ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_operation_readiness_blocked_reason_string_concat_lifetime_not_ready_id())))) {
		return string_t("string_concat_lifetime_not_ready");
	}
	return string_t("unknown");
}

}

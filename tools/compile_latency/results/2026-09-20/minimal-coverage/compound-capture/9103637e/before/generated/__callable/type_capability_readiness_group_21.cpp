#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerRow.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_backend_preflight_not_reintroduced_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_callable_contract_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_type_ref_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_synthetic_load_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_bool_logical_operator_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_control_flow_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_name.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_numeric_binary_operator_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_numeric_unary_operator_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_return_lowering_authorized_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_call_boundary_storage_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_echo_output_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_local_storage_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_string_concat_operator_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_actual_argument_by_reference_stable_local_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_actual_argument_by_value_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_divide_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_equal_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_greater_than_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_greater_than_or_equal_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_identical_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_less_than_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_less_than_or_equal_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_logical_and_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_logical_or_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_minus_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_modulo_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_multiply_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_not_equal_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_not_identical_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_plus_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_callable_return_lowering_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_callable_return_type_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_echo_scalar_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_echo_string_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_for_bool_loop_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_formal_parameter_by_reference_slot_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_formal_parameter_slot_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_if_bool_then_block_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_literal_scalar_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_local_assignment_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_local_load_return_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_local_storage_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_name.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_runtime_text_coercion_echo_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_string_concat_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_unary_operator_logical_not_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_unary_operator_minus_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_while_bool_loop_id.hpp"
namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_synthetic_load_consumer(int_t<std::uint32_t> sourceRowId, int_t<std::uint16_t> featureId, int_t<std::uint32_t> providerTypeRefId, bool_t blocked) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::synthetic_load_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[185]);
	CapabilityConsumerRow row = CapabilityConsumerRow{};
	row->capability_id = __latency_fn_type_capability_readiness_capability_type_ref_known_id();
	row->source_row_id = sourceRowId;
	row->provider_source_row_id = providerTypeRefId;
	row->feature_id = featureId;
	row->source_key_id = __latency_fn_type_capability_readiness_source_key_callable_contract_row_id();
	row->provider_source_key_id = __latency_fn_type_capability_readiness_source_key_type_ref_row_id();
	row->provider_type_ref_id = providerTypeRefId;
	if (static_cast<bool>(php::condition_truthy(blocked))) {
		row->status_id = __latency_fn_type_capability_readiness_status_blocked_id();
		row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_backend_preflight_not_reintroduced_id();
	}
	else {
		row->status_id = __latency_fn_type_capability_readiness_status_ready_id();
		row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_none_id();
	}
	return row;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
string_t __latency_fn_type_capability_readiness_capability_name(int_t<std::uint16_t> capabilityId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::capability_name", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[186]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(capabilityId), cast<int_t<>>(__latency_fn_type_capability_readiness_capability_type_ref_known_id())))) {
		return string_t("type_ref_known");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(capabilityId), cast<int_t<>>(__latency_fn_type_capability_readiness_capability_return_lowering_authorized_id())))) {
		return string_t("return_lowering_authorized");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(capabilityId), cast<int_t<>>(__latency_fn_type_capability_readiness_capability_numeric_binary_operator_id())))) {
		return string_t("numeric_binary_operator");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(capabilityId), cast<int_t<>>(__latency_fn_type_capability_readiness_capability_scalar_local_storage_id())))) {
		return string_t("scalar_local_storage");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(capabilityId), cast<int_t<>>(__latency_fn_type_capability_readiness_capability_scalar_echo_output_id())))) {
		return string_t("scalar_echo_output");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(capabilityId), cast<int_t<>>(__latency_fn_type_capability_readiness_capability_control_flow_id())))) {
		return string_t("control_flow");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(capabilityId), cast<int_t<>>(__latency_fn_type_capability_readiness_capability_scalar_comparison_operator_id())))) {
		return string_t("scalar_comparison_operator");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(capabilityId), cast<int_t<>>(__latency_fn_type_capability_readiness_capability_bool_logical_operator_id())))) {
		return string_t("bool_logical_operator");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(capabilityId), cast<int_t<>>(__latency_fn_type_capability_readiness_capability_numeric_unary_operator_id())))) {
		return string_t("numeric_unary_operator");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(capabilityId), cast<int_t<>>(__latency_fn_type_capability_readiness_capability_string_concat_operator_id())))) {
		return string_t("string_concat_operator");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(capabilityId), cast<int_t<>>(__latency_fn_type_capability_readiness_capability_scalar_call_boundary_storage_id())))) {
		return string_t("scalar_call_boundary_storage");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
string_t __latency_fn_type_capability_readiness_feature_name(int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::feature_name", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[187]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_callable_return_type_id())))) {
		return string_t("callable_return_type");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_callable_return_lowering_id())))) {
		return string_t("callable_return_lowering");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_literal_scalar_id())))) {
		return string_t("literal_scalar");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_binary_operator_plus_id())))) {
		return string_t("binary_operator_plus");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_binary_operator_minus_id())))) {
		return string_t("binary_operator_minus");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_binary_operator_multiply_id())))) {
		return string_t("binary_operator_multiply");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_binary_operator_divide_id())))) {
		return string_t("binary_operator_divide");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_binary_operator_modulo_id())))) {
		return string_t("binary_operator_modulo");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_binary_operator_equal_id())))) {
		return string_t("binary_operator_equal");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_binary_operator_not_equal_id())))) {
		return string_t("binary_operator_not_equal");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_binary_operator_less_than_id())))) {
		return string_t("binary_operator_less_than");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_binary_operator_greater_than_id())))) {
		return string_t("binary_operator_greater_than");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_binary_operator_less_than_or_equal_id())))) {
		return string_t("binary_operator_less_than_or_equal");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_binary_operator_greater_than_or_equal_id())))) {
		return string_t("binary_operator_greater_than_or_equal");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_binary_operator_identical_id())))) {
		return string_t("binary_operator_identical");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_binary_operator_not_identical_id())))) {
		return string_t("binary_operator_not_identical");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_binary_operator_logical_and_id())))) {
		return string_t("binary_operator_logical_and");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_binary_operator_logical_or_id())))) {
		return string_t("binary_operator_logical_or");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_unary_operator_minus_id())))) {
		return string_t("unary_operator_minus");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_unary_operator_logical_not_id())))) {
		return string_t("unary_operator_logical_not");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_local_storage_id())))) {
		return string_t("local_storage");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_local_assignment_id())))) {
		return string_t("local_assignment");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_echo_scalar_id())))) {
		return string_t("echo_scalar");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_echo_string_id())))) {
		return string_t("echo_string");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_runtime_text_coercion_echo_id())))) {
		return string_t("runtime_text_coercion_echo");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_string_concat_id())))) {
		return string_t("string_concat");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_formal_parameter_slot_id())))) {
		return string_t("formal_parameter_slot");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_actual_argument_by_value_id())))) {
		return string_t("actual_argument_by_value");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_formal_parameter_by_reference_slot_id())))) {
		return string_t("formal_parameter_by_reference_slot");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_actual_argument_by_reference_stable_local_id())))) {
		return string_t("actual_argument_by_reference_stable_local");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_if_bool_then_block_id())))) {
		return string_t("if_bool_then_block");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_while_bool_loop_id())))) {
		return string_t("while_bool_loop");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_for_bool_loop_id())))) {
		return string_t("for_bool_loop");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(featureId), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_local_load_return_id())))) {
		return string_t("local_load_return");
	}
	return string_t("unknown");
}

}

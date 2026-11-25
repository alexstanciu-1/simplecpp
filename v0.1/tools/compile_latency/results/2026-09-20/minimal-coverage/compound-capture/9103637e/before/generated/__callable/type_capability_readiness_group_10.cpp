#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/RuntimeAbiBridgeArtifact.hpp"
#include "__types/SemanticRuntimeAbiBridgeDescriptorRow.hpp"
#include "__types/TypeTraitRow.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_local_storage_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_local_assignment_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_assignment_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_value_storage_blocked_reason_from_trait.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_frontend_statement_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_type_ref_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_row_from_type_ref_id.hpp"
#include "__callable/__latency_fn_primitive_abi_adapter_matrix_integer_echo_i64_supported_for_type_ref.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_backend_numeric_abi_not_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_echo_output_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_echo_scalar_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_echo_scalar_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_frontend_statement_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_type_ref_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_operation_readiness_result_type_ref_id_from_runtime_abi_bridge.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_build.hpp"
#include "__callable/__latency_fn_string_runtime_abi_literal_echo_source_slice_ready.hpp"
#include "__callable/__latency_fn_string_runtime_abi_literal_from_cstr_descriptor.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_runtime_string_abi_not_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_echo_output_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_echo_string_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_echo_string_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_frontend_statement_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_type_ref_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_build.hpp"
#include "__callable/__latency_fn_string_runtime_abi_runtime_text_coercion_echo_source_slice_ready.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_runtime_string_abi_not_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_echo_output_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_runtime_text_coercion_echo_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_runtime_text_coercion_echo_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_frontend_statement_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_type_ref_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_operation_readiness_result_type_ref_id_from_runtime_abi_bridge.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_build.hpp"
#include "__callable/__latency_fn_string_runtime_abi_concat_assign_descriptor.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_runtime_string_abi_not_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_string_concat_lifetime_not_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_string_concat_operator_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_string_concat_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_frontend_expression_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_type_ref_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_string_concat_consumer.hpp"
namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_local_assignment_consumer(int_t<std::uint32_t> statementSourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::local_assignment_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[124]);
	CapabilityConsumerRow row = CapabilityConsumerRow{};
	row->capability_id = __latency_fn_type_capability_readiness_capability_scalar_local_storage_id();
	row->source_row_id = statementSourceRowId;
	row->provider_source_row_id = typeRefId;
	row->feature_id = __latency_fn_type_capability_readiness_feature_local_assignment_id();
	row->source_key_id = __latency_fn_type_capability_readiness_source_key_frontend_statement_row_id();
	row->provider_source_key_id = __latency_fn_type_capability_readiness_source_key_type_ref_row_id();
	row->provider_type_ref_id = typeRefId;
	TypeTraitRow trait = __latency_fn_type_traits_row_from_type_ref_id(typeRefId);
	int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_type_capability_readiness_local_value_storage_blocked_reason_from_trait(trait));
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_none_id())))) {
		row->status_id = __latency_fn_type_capability_readiness_status_ready_id();
		row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_none_id();
		return row;
	}
	row->status_id = __latency_fn_type_capability_readiness_status_blocked_id();
	row->blocked_reason_id = blockedReasonId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_echo_scalar_consumer(int_t<std::uint32_t> statementSourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::echo_scalar_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[125]);
	CapabilityConsumerRow row = CapabilityConsumerRow{};
	row->capability_id = __latency_fn_type_capability_readiness_capability_scalar_echo_output_id();
	row->source_row_id = statementSourceRowId;
	row->provider_source_row_id = typeRefId;
	row->feature_id = __latency_fn_type_capability_readiness_feature_echo_scalar_id();
	row->source_key_id = __latency_fn_type_capability_readiness_source_key_frontend_statement_row_id();
	row->provider_source_key_id = __latency_fn_type_capability_readiness_source_key_type_ref_row_id();
	row->provider_type_ref_id = typeRefId;
	if (static_cast<bool>(php::condition_truthy(__latency_fn_primitive_abi_adapter_matrix_integer_echo_i64_supported_for_type_ref(typeRefId)))) {
		row->status_id = __latency_fn_type_capability_readiness_status_ready_id();
		row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_none_id();
		return row;
	}
	row->status_id = __latency_fn_type_capability_readiness_status_blocked_id();
	row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_backend_numeric_abi_not_ready_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_echo_string_consumer(int_t<std::uint32_t> statementSourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::echo_string_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[126]);
	CapabilityConsumerRow row = CapabilityConsumerRow{};
	row->capability_id = __latency_fn_type_capability_readiness_capability_scalar_echo_output_id();
	row->source_row_id = statementSourceRowId;
	row->provider_source_row_id = typeRefId;
	row->feature_id = __latency_fn_type_capability_readiness_feature_echo_string_id();
	row->source_key_id = __latency_fn_type_capability_readiness_source_key_frontend_statement_row_id();
	row->provider_source_key_id = __latency_fn_type_capability_readiness_source_key_type_ref_row_id();
	row->provider_type_ref_id = typeRefId;
	shared_p<RuntimeAbiBridgeArtifact> bridge = __latency_fn_runtime_abi_bridge_build();
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> literal = __latency_fn_string_runtime_abi_literal_from_cstr_descriptor(bridge);
	if (static_cast<bool>((php::identical(cast<int_t<>>(__latency_fn_operation_readiness_result_type_ref_id_from_runtime_abi_bridge(literal)), cast<int_t<>>(typeRefId)) && __latency_fn_string_runtime_abi_literal_echo_source_slice_ready(bridge)))) {
		row->status_id = __latency_fn_type_capability_readiness_status_ready_id();
		row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_none_id();
		return row;
	}
	row->status_id = __latency_fn_type_capability_readiness_status_blocked_id();
	row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_runtime_string_abi_not_ready_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_runtime_text_coercion_echo_consumer(int_t<std::uint32_t> statementSourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::runtime_text_coercion_echo_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[127]);
	CapabilityConsumerRow row = CapabilityConsumerRow{};
	row->capability_id = __latency_fn_type_capability_readiness_capability_scalar_echo_output_id();
	row->source_row_id = statementSourceRowId;
	row->provider_source_row_id = typeRefId;
	row->feature_id = __latency_fn_type_capability_readiness_feature_runtime_text_coercion_echo_id();
	row->source_key_id = __latency_fn_type_capability_readiness_source_key_frontend_statement_row_id();
	row->provider_source_key_id = __latency_fn_type_capability_readiness_source_key_type_ref_row_id();
	row->provider_type_ref_id = typeRefId;
	shared_p<RuntimeAbiBridgeArtifact> bridge = __latency_fn_runtime_abi_bridge_build();
	if (static_cast<bool>(php::condition_truthy(__latency_fn_string_runtime_abi_runtime_text_coercion_echo_source_slice_ready(bridge, typeRefId)))) {
		row->status_id = __latency_fn_type_capability_readiness_status_ready_id();
		row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_none_id();
		return row;
	}
	row->status_id = __latency_fn_type_capability_readiness_status_blocked_id();
	row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_runtime_string_abi_not_ready_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_string_concat_consumer(int_t<std::uint32_t> expressionSourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::string_concat_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[128]);
	CapabilityConsumerRow row = CapabilityConsumerRow{};
	row->capability_id = __latency_fn_type_capability_readiness_capability_string_concat_operator_id();
	row->source_row_id = expressionSourceRowId;
	row->provider_source_row_id = typeRefId;
	row->feature_id = __latency_fn_type_capability_readiness_feature_string_concat_id();
	row->source_key_id = __latency_fn_type_capability_readiness_source_key_frontend_expression_row_id();
	row->provider_source_key_id = __latency_fn_type_capability_readiness_source_key_type_ref_row_id();
	row->provider_type_ref_id = typeRefId;
	shared_p<RuntimeAbiBridgeArtifact> bridge = __latency_fn_runtime_abi_bridge_build();
	shared_p<SemanticRuntimeAbiBridgeDescriptorRow> concat = __latency_fn_string_runtime_abi_concat_assign_descriptor(bridge);
	if (static_cast<bool>((php::identical(cast<int_t<>>(__latency_fn_operation_readiness_result_type_ref_id_from_runtime_abi_bridge(concat)), cast<int_t<>>(typeRefId)) && (cast<int_t<>>(concat->bridge_row_id) > static_cast<int_t<> >(0))))) {
		row->status_id = __latency_fn_type_capability_readiness_status_blocked_id();
		row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_string_concat_lifetime_not_ready_id();
		return row;
	}
	row->status_id = __latency_fn_type_capability_readiness_status_blocked_id();
	row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_runtime_string_abi_not_ready_id();
	return row;
}

}

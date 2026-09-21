#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/CapabilityReadinessRow.hpp"
#include "__types/FeatureOperationRouteDescriptorRow.hpp"
#include "__types/OperationContract.hpp"
#include "__types/OperationReadiness.hpp"
#include "__types/RuntimeAbiBridgeArtifact.hpp"
#include "__types/SemanticOperatorLookupRow.hpp"
#include "__types/SemanticRuntimeAbiBridgeDescriptorRow.hpp"
#include "__types/TypeTraitRow.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_from_capability_consumer.hpp"
#include "__callable/__latency_fn_operation_readiness_feature_operation_route_by_feature_id.hpp"
#include "__callable/__latency_fn_operation_readiness_is_numeric_binary_feature.hpp"
#include "__callable/__latency_fn_operation_readiness_lowering_adapter_none_id.hpp"
#include "__callable/__latency_fn_operation_readiness_result_type_ref_id_from_runtime_abi_bridge.hpp"
#include "__callable/__latency_fn_operation_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_operation_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_build.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_text_coercion_row_by_source_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_lookup_type_ref_id.hpp"
#include "__callable/__latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_ref.hpp"
#include "__callable/__latency_fn_string_runtime_abi_concat_assign_descriptor.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_feature_supported_by_trait.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_runtime_text_coercion_echo_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_string_concat_id.hpp"
#include "__callable/__latency_fn_type_traits_row_from_type_ref_id.hpp"
#include "__callable/__latency_fn_operation_readiness_result_type_ref_id_from_runtime_abi_bridge.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_type_traits_abi_shape_opaque_runtime_string_id.hpp"
#include "__callable/__latency_fn_type_traits_type_ref_id_by_abi_shape_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_backend_lowering_not_reintroduced_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_from_capability_blocked_reason.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_operation_readiness_blocked_reason_unsupported_numeric_operator_id.hpp"
#include "__callable/__latency_fn_operation_readiness_contract_from_capability_consumer.hpp"
#include "__callable/__latency_fn_operation_readiness_readiness_from_capability.hpp"
#include "__callable/__latency_fn_operation_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_operation_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_operation_readiness_equals.hpp"
#include "__callable/__latency_fn_operation_readiness_debug_string.hpp"
#include "__callable/__latency_fn_operation_readiness_operation_kind_name.hpp"
#include "__callable/__latency_fn_operation_readiness_status_name.hpp"
#include "__callable/__latency_fn_type_ref_identity_name.hpp"
#include "__callable/__latency_fn_operation_readiness_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
OperationContract __latency_fn_operation_readiness_contract_from_capability_consumer(CapabilityConsumerRow consumer) {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::contract_from_capability_consumer", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[123]);
	OperationContract row = OperationContract{};
	row->lowering_adapter_id = __latency_fn_operation_readiness_lowering_adapter_none_id();
	row->result_type_ref_id = consumer->provider_type_ref_id;
	row->lhs_type_ref_id = consumer->provider_type_ref_id;
	row->rhs_type_ref_id = __latency_fn_structure_row_ids_none_id();
	FeatureOperationRouteDescriptorRow route = __latency_fn_operation_readiness_feature_operation_route_by_feature_id(consumer->feature_id);
	if (static_cast<bool>(php::identical(cast<int_t<>>(route->feature_id), cast<int_t<>>(consumer->feature_id)))) {
		row->contract_id = route->contract_id;
		row->operation_kind_id = route->operation_kind_id;
		row->lowering_adapter_id = route->lowering_adapter_id;
		row->status_id = route->default_status_id;
	}
	else {
		row->status_id = __latency_fn_operation_readiness_status_blocked_id();
		return row;
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_operation_readiness_is_numeric_binary_feature(consumer->feature_id)))) {
		shared_p<SemanticOperatorLookupRow> operatorRow = __latency_fn_semantic_operator_lookup_row_by_feature_id_and_type_ref(consumer->feature_id, consumer->provider_type_ref_id);
		if (static_cast<bool>((cast<int_t<>>(operatorRow->operator_id) > static_cast<int_t<> >(0)))) {
			int_t<std::uint32_t> lookupProviderTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_semantic_operator_lookup_lookup_type_ref_id(consumer->provider_type_ref_id));
			row->contract_id = operatorRow->contract_id;
			row->operation_kind_id = operatorRow->operation_kind_id;
			row->lowering_adapter_id = operatorRow->lowering_adapter_id;
			row->lhs_type_ref_id = operatorRow->lhs_type_ref_id;
			row->rhs_type_ref_id = operatorRow->rhs_type_ref_id;
			row->result_type_ref_id = operatorRow->result_type_ref_id;
			if (static_cast<bool>((php::identical(cast<int_t<>>(lookupProviderTypeRefId), cast<int_t<>>(operatorRow->lhs_type_ref_id)) && php::identical(cast<int_t<>>(operatorRow->rhs_type_ref_id), cast<int_t<>>(operatorRow->lhs_type_ref_id))))) {
				row->lhs_type_ref_id = consumer->provider_type_ref_id;
				row->rhs_type_ref_id = consumer->provider_type_ref_id;
			}
			if (static_cast<bool>((php::identical(cast<int_t<>>(lookupProviderTypeRefId), cast<int_t<>>(operatorRow->lhs_type_ref_id)) && php::identical(cast<int_t<>>(operatorRow->result_type_ref_id), cast<int_t<>>(operatorRow->lhs_type_ref_id))))) {
				row->result_type_ref_id = consumer->provider_type_ref_id;
			}
		}
		TypeTraitRow trait = __latency_fn_type_traits_row_from_type_ref_id(consumer->provider_type_ref_id);
		if (static_cast<bool>(php::condition_truthy(__latency_fn_type_capability_readiness_binary_operator_feature_supported_by_trait(trait, consumer->feature_id)))) {
			row->status_id = __latency_fn_operation_readiness_status_ready_id();
			return row;
		}
		row->status_id = __latency_fn_operation_readiness_status_blocked_id();
		return row;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(consumer->feature_id), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_runtime_text_coercion_echo_id())))) {
		shared_p<SemanticRuntimeAbiBridgeDescriptorRow> textCoercionBridgeRow = __latency_fn_runtime_abi_bridge_text_coercion_row_by_source_type_ref_id(__latency_fn_runtime_abi_bridge_build(), consumer->provider_type_ref_id);
		row->result_type_ref_id = __latency_fn_operation_readiness_result_type_ref_id_from_runtime_abi_bridge(textCoercionBridgeRow);
		row->lhs_type_ref_id = consumer->provider_type_ref_id;
		row->rhs_type_ref_id = __latency_fn_structure_row_ids_none_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(consumer->feature_id), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_string_concat_id())))) {
		shared_p<SemanticRuntimeAbiBridgeDescriptorRow> stringConcatBridgeRow = __latency_fn_string_runtime_abi_concat_assign_descriptor(__latency_fn_runtime_abi_bridge_build());
		int_t<std::uint32_t> runtimeOpaqueTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_operation_readiness_result_type_ref_id_from_runtime_abi_bridge(stringConcatBridgeRow));
		row->result_type_ref_id = runtimeOpaqueTypeRefId;
		row->lhs_type_ref_id = runtimeOpaqueTypeRefId;
		row->rhs_type_ref_id = runtimeOpaqueTypeRefId;
	}
	return row;
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_operation_readiness_result_type_ref_id_from_runtime_abi_bridge(shared_p<SemanticRuntimeAbiBridgeDescriptorRow> bridgeRow) {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::result_type_ref_id_from_runtime_abi_bridge", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[124]);
	if (static_cast<bool>((cast<int_t<>>(bridgeRow->result_type_ref_id) > static_cast<int_t<> >(0)))) {
		return bridgeRow->result_type_ref_id;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(bridgeRow->return_carrier_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_abi_carrier_opaque_runtime_string_id())))) {
		return __latency_fn_type_traits_type_ref_id_by_abi_shape_id(__latency_fn_type_traits_abi_shape_opaque_runtime_string_id());
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
OperationReadiness __latency_fn_operation_readiness_readiness_from_capability(CapabilityConsumerRow consumer, CapabilityReadinessRow capabilityReadiness) {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::readiness_from_capability", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[125]);
	OperationContract contract = __latency_fn_operation_readiness_contract_from_capability_consumer(consumer);
	OperationReadiness row = OperationReadiness{};
	row->contract_id = contract->contract_id;
	row->lowering_adapter_id = contract->lowering_adapter_id;
	row->source_row_id = consumer->source_row_id;
	row->operation_kind_id = contract->operation_kind_id;
	row->capability_id = consumer->capability_id;
	row->consumer_feature_id = consumer->feature_id;
	row->provider_type_ref_id = consumer->provider_type_ref_id;
	row->result_type_ref_id = contract->result_type_ref_id;
	if (static_cast<bool>((php::identical(cast<int_t<>>(capabilityReadiness->status_id), cast<int_t<>>(__latency_fn_type_capability_readiness_status_ready_id())) && php::identical(cast<int_t<>>(contract->status_id), cast<int_t<>>(__latency_fn_operation_readiness_status_ready_id()))))) {
		row->status_id = __latency_fn_operation_readiness_status_ready_id();
		row->blocked_reason_id = __latency_fn_operation_readiness_blocked_reason_none_id();
		return row;
	}
	row->status_id = __latency_fn_operation_readiness_status_blocked_id();
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(capabilityReadiness->blocked_reason_id), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_none_id()))))) {
		row->blocked_reason_id = __latency_fn_operation_readiness_blocked_reason_from_capability_blocked_reason(capabilityReadiness->blocked_reason_id);
		return row;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(contract->status_id), cast<int_t<>>(__latency_fn_operation_readiness_status_blocked_id())))) {
		row->blocked_reason_id = __latency_fn_operation_readiness_blocked_reason_unsupported_numeric_operator_id();
		return row;
	}
	row->blocked_reason_id = __latency_fn_operation_readiness_blocked_reason_backend_lowering_not_reintroduced_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
bool_t __latency_fn_operation_readiness_equals(OperationReadiness left, OperationReadiness right) {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::equals", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[126]);
	return ((((php::identical(cast<int_t<>>(left->contract_id), cast<int_t<>>(right->contract_id)) && php::identical(cast<int_t<>>(left->source_row_id), cast<int_t<>>(right->source_row_id))) && php::identical(cast<int_t<>>(left->operation_kind_id), cast<int_t<>>(right->operation_kind_id))) && php::identical(cast<int_t<>>(left->provider_type_ref_id), cast<int_t<>>(right->provider_type_ref_id))) && php::identical(cast<int_t<>>(left->status_id), cast<int_t<>>(right->status_id)));
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
string_t __latency_fn_operation_readiness_debug_string(OperationReadiness row) {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[127]);
	return (string_t("operation_readiness:") + cast<string_t>(__latency_fn_operation_readiness_operation_kind_name(row->operation_kind_id)) + string_t(":") + cast<string_t>(__latency_fn_type_ref_identity_name(row->provider_type_ref_id)) + string_t(":") + cast<string_t>(__latency_fn_operation_readiness_status_name(row->status_id)));
}

}

namespace scpp { extern const int __latency_lines_operation_readiness[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_operation_readiness_stable_hash(OperationReadiness row) {
	SCPP_CALL_DEPTH_GUARD("operation_readiness::stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/operations/operation_readiness.phs", __latency_lines_operation_readiness[128]);
	string_t identity = required_cast<string_t>((string_t("operation_readiness:v2:") + cast<string_t>(cast<int_t<>>(row->contract_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->operation_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->capability_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->consumer_feature_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->provider_type_ref_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->blocked_reason_id))));
	return php::stable_hash_string_u64(identity);
}

}

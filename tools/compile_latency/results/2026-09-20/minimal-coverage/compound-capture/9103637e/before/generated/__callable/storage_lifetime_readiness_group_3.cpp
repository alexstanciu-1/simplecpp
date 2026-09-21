#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/CapabilityProviderRow.hpp"
#include "__types/OperationReadiness.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectReferenceActualArgumentRow.hpp"
#include "__types/ProjectSymbolParameterRow.hpp"
#include "__types/RuntimeAbiBridgeArtifact.hpp"
#include "__types/StorageLifetimeRequestRow.hpp"
#include "__types/StorageLifetimeRouteDescriptorRow.hpp"
#include "__types/TypeTraitRow.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_status_name.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_operation_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_build.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_consumer_return_value_id.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_lifetime_policy_from_trait.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_provider_result_storage_ready.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_from_operation.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_storage_context_return_value_id.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_storage_route_by_feature_id.hpp"
#include "__callable/__latency_fn_string_runtime_abi_literal_echo_source_slice_ready.hpp"
#include "__callable/__latency_fn_string_runtime_abi_runtime_text_coercion_echo_source_slice_ready.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_runtime_text_coercion_echo_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_string_concat_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_row_from_type_ref_id.hpp"
#include "__callable/__latency_fn_type_traits_storage_policy_runtime_opaque_id.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_lifetime_policy_from_trait.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_scalar_call_boundary_request.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_call_boundary_storage_id.hpp"
#include "__callable/__latency_fn_type_traits_cleanup_policy_none_id.hpp"
#include "__callable/__latency_fn_type_traits_copy_policy_trivial_id.hpp"
#include "__callable/__latency_fn_type_traits_row_from_type_ref_id.hpp"
#include "__callable/__latency_fn_type_traits_status_ready_id.hpp"
#include "__callable/__latency_fn_type_traits_storage_policy_inline_scalar_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_argument_status_matched_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_argument_type_status_matched_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_project_callable_contracts_status_compatible_id.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_callable_contract_ready.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_callable_contract_ready.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_consumer_formal_parameter_slot_id.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_from_formal_parameter_slot.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_scalar_call_boundary_request.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_storage_context_formal_parameter_slot_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_formal_parameter_slot_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_literal_status_ready_id.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_callable_contract_ready.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_consumer_actual_argument_by_value_id.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_from_actual_argument_by_value.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_scalar_call_boundary_request.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_storage_context_actual_argument_by_value_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_actual_argument_by_value_id.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_callable_contract_ready.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_consumer_formal_parameter_by_reference_slot_id.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_request_from_formal_parameter_by_reference_slot.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_scalar_call_boundary_request.hpp"
#include "__callable/__latency_fn_storage_lifetime_readiness_storage_context_formal_parameter_by_reference_slot_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_formal_parameter_by_reference_slot_id.hpp"
namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
string_t __latency_fn_storage_lifetime_readiness_status_name(int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[37]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_storage_lifetime_readiness_status_ready_id())))) {
		return string_t("ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_storage_lifetime_readiness_status_blocked_id())))) {
		return string_t("blocked");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
StorageLifetimeRequestRow __latency_fn_storage_lifetime_readiness_request_from_operation(int_t<std::uint32_t> requestId, OperationReadiness operation, CapabilityConsumerRow consumer, CapabilityProviderRow provider) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::request_from_operation", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[38]);
	StorageLifetimeRequestRow row = StorageLifetimeRequestRow{};
	row->request_id = requestId;
	row->consumer_kind_id = __latency_fn_storage_lifetime_readiness_consumer_return_value_id();
	row->capability_id = consumer->capability_id;
	row->consumer_feature_id = consumer->feature_id;
	row->storage_context_id = __latency_fn_storage_lifetime_readiness_storage_context_return_value_id();
	row->type_ref_id = operation->result_type_ref_id;
	row->source_row_id = operation->source_row_id;
	StorageLifetimeRouteDescriptorRow route = __latency_fn_storage_lifetime_readiness_storage_route_by_feature_id(operation->consumer_feature_id);
	if (static_cast<bool>(php::identical(cast<int_t<>>(route->feature_id), cast<int_t<>>(operation->consumer_feature_id)))) {
		row->consumer_kind_id = route->consumer_kind_id;
		row->storage_context_id = route->storage_context_id;
	}
	TypeTraitRow resultTrait = __latency_fn_type_traits_row_from_type_ref_id(operation->result_type_ref_id);
	row->storage_policy_id = resultTrait->storage_policy_id;
	row->copy_policy_id = resultTrait->copy_policy_id;
	row->cleanup_policy_id = resultTrait->cleanup_policy_id;
	row->lifetime_policy_id = __latency_fn_storage_lifetime_readiness_lifetime_policy_from_trait(resultTrait);
	if (static_cast<bool>(php::identical(cast<int_t<>>(resultTrait->storage_policy_id), cast<int_t<>>(__latency_fn_type_traits_storage_policy_runtime_opaque_id())))) {
		shared_p<RuntimeAbiBridgeArtifact> bridge = __latency_fn_runtime_abi_bridge_build();
		bool_t sourceSliceReady = required_cast<bool_t>(__latency_fn_string_runtime_abi_literal_echo_source_slice_ready(bridge));
		if (static_cast<bool>(php::identical(cast<int_t<>>(operation->consumer_feature_id), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_runtime_text_coercion_echo_id())))) {
			sourceSliceReady = __latency_fn_string_runtime_abi_runtime_text_coercion_echo_source_slice_ready(bridge, operation->provider_type_ref_id);
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(operation->consumer_feature_id), cast<int_t<>>(__latency_fn_type_capability_readiness_feature_string_concat_id())))) {
			sourceSliceReady = bool_t(static_cast<bool_t>(false));
		}
		if (static_cast<bool>((((php::identical(cast<int_t<>>(operation->status_id), cast<int_t<>>(__latency_fn_operation_readiness_status_ready_id())) && php::identical(cast<int_t<>>(provider->status_id), cast<int_t<>>(__latency_fn_type_capability_readiness_status_ready_id()))) && __latency_fn_storage_lifetime_readiness_provider_result_storage_ready(operation, provider)) && sourceSliceReady))) {
			row->readiness_status_id = __latency_fn_storage_lifetime_readiness_status_ready_id();
			return row;
		}
		row->readiness_status_id = __latency_fn_storage_lifetime_readiness_status_blocked_id();
		return row;
	}
	if (static_cast<bool>(((php::identical(cast<int_t<>>(operation->status_id), cast<int_t<>>(__latency_fn_operation_readiness_status_ready_id())) && php::identical(cast<int_t<>>(provider->status_id), cast<int_t<>>(__latency_fn_type_capability_readiness_status_ready_id()))) && __latency_fn_storage_lifetime_readiness_provider_result_storage_ready(operation, provider)))) {
		row->readiness_status_id = __latency_fn_storage_lifetime_readiness_status_ready_id();
		return row;
	}
	row->readiness_status_id = __latency_fn_storage_lifetime_readiness_status_blocked_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
StorageLifetimeRequestRow __latency_fn_storage_lifetime_readiness_scalar_call_boundary_request(int_t<std::uint32_t> requestId, int_t<std::uint16_t> featureId, int_t<std::uint16_t> consumerKindId, int_t<std::uint16_t> storageContextId, int_t<std::uint32_t> typeRefId, int_t<std::uint32_t> sourceRowId, bool_t callableContractReady) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::scalar_call_boundary_request", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[39]);
	StorageLifetimeRequestRow row = StorageLifetimeRequestRow{};
	row->request_id = requestId;
	row->consumer_kind_id = consumerKindId;
	row->capability_id = __latency_fn_type_capability_readiness_capability_scalar_call_boundary_storage_id();
	row->consumer_feature_id = featureId;
	row->storage_context_id = storageContextId;
	row->type_ref_id = typeRefId;
	row->source_row_id = sourceRowId;
	TypeTraitRow trait = __latency_fn_type_traits_row_from_type_ref_id(typeRefId);
	row->storage_policy_id = trait->storage_policy_id;
	row->copy_policy_id = trait->copy_policy_id;
	row->cleanup_policy_id = trait->cleanup_policy_id;
	row->lifetime_policy_id = __latency_fn_storage_lifetime_readiness_lifetime_policy_from_trait(trait);
	if (static_cast<bool>((((((callableContractReady && (cast<int_t<>>(typeRefId) > static_cast<int_t<> >(0))) && php::identical(cast<int_t<>>(trait->status_id), cast<int_t<>>(__latency_fn_type_traits_status_ready_id()))) && php::identical(cast<int_t<>>(trait->storage_policy_id), cast<int_t<>>(__latency_fn_type_traits_storage_policy_inline_scalar_id()))) && php::identical(cast<int_t<>>(trait->copy_policy_id), cast<int_t<>>(__latency_fn_type_traits_copy_policy_trivial_id()))) && php::identical(cast<int_t<>>(trait->cleanup_policy_id), cast<int_t<>>(__latency_fn_type_traits_cleanup_policy_none_id()))))) {
		row->readiness_status_id = __latency_fn_storage_lifetime_readiness_status_ready_id();
		return row;
	}
	row->readiness_status_id = __latency_fn_storage_lifetime_readiness_status_blocked_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
bool_t __latency_fn_storage_lifetime_readiness_callable_contract_ready(ProjectCallableContractRow contract) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::callable_contract_ready", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[40]);
	return (((php::identical(cast<int_t<>>(contract->status_id), cast<int_t<>>(__latency_fn_project_callable_contracts_status_compatible_id())) && php::identical(cast<int_t<>>(contract->argument_count_status_id), cast<int_t<>>(__latency_fn_project_callable_contracts_argument_status_matched_id()))) && php::identical(cast<int_t<>>(contract->argument_type_status_id), cast<int_t<>>(__latency_fn_project_callable_contracts_argument_type_status_matched_id()))) && php::identical(cast<int_t<>>(contract->blocked_reason_id), cast<int_t<>>(__latency_fn_project_callable_contracts_blocked_reason_none_id())));
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
StorageLifetimeRequestRow __latency_fn_storage_lifetime_readiness_request_from_formal_parameter_slot(int_t<std::uint32_t> requestId, ProjectCallableContractRow contract, ProjectSymbolParameterRow parameter) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::request_from_formal_parameter_slot", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[41]);
	return __latency_fn_storage_lifetime_readiness_scalar_call_boundary_request(cast<int_t<std::uint32_t>>(requestId), __latency_fn_type_capability_readiness_feature_formal_parameter_slot_id(), __latency_fn_storage_lifetime_readiness_consumer_formal_parameter_slot_id(), __latency_fn_storage_lifetime_readiness_storage_context_formal_parameter_slot_id(), parameter->type_ref_id, parameter->parameter_source_row_id, __latency_fn_storage_lifetime_readiness_callable_contract_ready(contract));
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
StorageLifetimeRequestRow __latency_fn_storage_lifetime_readiness_request_from_actual_argument_by_value(int_t<std::uint32_t> requestId, ProjectCallableContractRow contract, ProjectReferenceActualArgumentRow argument) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::request_from_actual_argument_by_value", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[42]);
	bool_t argumentReady = required_cast<bool_t>(php::identical(cast<int_t<>>(argument->literal_status_id), cast<int_t<>>(__latency_fn_frontend_model_builder_literal_status_ready_id())));
	return __latency_fn_storage_lifetime_readiness_scalar_call_boundary_request(cast<int_t<std::uint32_t>>(requestId), __latency_fn_type_capability_readiness_feature_actual_argument_by_value_id(), __latency_fn_storage_lifetime_readiness_consumer_actual_argument_by_value_id(), __latency_fn_storage_lifetime_readiness_storage_context_actual_argument_by_value_id(), argument->type_ref_id, argument->argument_source_row_id, (__latency_fn_storage_lifetime_readiness_callable_contract_ready(contract) && argumentReady));
}

}

namespace scpp { extern const int __latency_lines_storage_lifetime_readiness[]; }
namespace scpp {
StorageLifetimeRequestRow __latency_fn_storage_lifetime_readiness_request_from_formal_parameter_by_reference_slot(int_t<std::uint32_t> requestId, ProjectCallableContractRow contract, ProjectSymbolParameterRow parameter) {
	SCPP_CALL_DEPTH_GUARD("storage_lifetime_readiness::request_from_formal_parameter_by_reference_slot", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/storage_lifetime_readiness.phs", __latency_lines_storage_lifetime_readiness[43]);
	return __latency_fn_storage_lifetime_readiness_scalar_call_boundary_request(cast<int_t<std::uint32_t>>(requestId), __latency_fn_type_capability_readiness_feature_formal_parameter_by_reference_slot_id(), __latency_fn_storage_lifetime_readiness_consumer_formal_parameter_by_reference_slot_id(), __latency_fn_storage_lifetime_readiness_storage_context_formal_parameter_by_reference_slot_id(), parameter->type_ref_id, parameter->parameter_source_row_id, __latency_fn_storage_lifetime_readiness_callable_contract_ready(contract));
}

}

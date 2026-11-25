#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/CapabilityCoverageArtifact.hpp"
#include "__types/CapabilityProviderRow.hpp"
#include "__types/CapabilityReadinessRow.hpp"
#include "__types/CapabilityRegistryRow.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/TypeRefRow.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_build_readiness_lookup_index_if_recommended.hpp"
#include "__callable/__latency_fn_type_capability_readiness_lookup_policy_index_recommended_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_max_readiness_feature_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_update_lookup_policy.hpp"
#include "__callable/__latency_fn_type_capability_readiness_registry_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_registry.hpp"
#include "__callable/__latency_fn_type_capability_readiness_registry_row.hpp"
#include "__callable/__latency_fn_type_capability_readiness_adapter_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_evidence_type_ref_table_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_from_type_ref.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_type_ref_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_type_ref_known_blocked_reason_from_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_provider.hpp"
#include "__callable/__latency_fn_type_capability_readiness_invalidate_provider_lookup_index.hpp"
#include "__callable/__latency_fn_type_capability_readiness_update_lookup_policy.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_callable_return_type_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_ready_consumer_from_contract.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_callable_contract_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_type_ref_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_type_ref_known_blocked_reason_from_row.hpp"
#include "__callable/__latency_fn_type_refs_row_from_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_lowering_consumer_from_contract.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_backend_preflight_not_reintroduced_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_return_lowering_authorized_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_callable_return_lowering_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_callable_contract_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_source_key_type_ref_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_blocked_id.hpp"
namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
void __latency_fn_type_capability_readiness_build_readiness_lookup_index_if_recommended(shared_p<CapabilityCoverageArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::build_readiness_lookup_index_if_recommended", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[101]);
	__latency_fn_type_capability_readiness_update_lookup_policy(artifact);
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(artifact->lookup_policy_status_id), cast<int_t<>>(__latency_fn_type_capability_readiness_lookup_policy_index_recommended_id())) || (cast<int_t<>>(artifact->readiness_lookup_index_slot_count) > static_cast<int_t<> >(0))))) {
		return;
	}
	int_t<> slotCount = required_cast<int_t<>>(cast<int_t<>>(__latency_fn_type_capability_readiness_max_readiness_feature_id(artifact)));
	if (static_cast<bool>((slotCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	php::vector_reserve(artifact->readiness_row_ids_by_feature_id, slotCount);
	while (static_cast<bool>((php::count(artifact->readiness_row_ids_by_feature_id) < slotCount))) {
		{
		auto __latency_local_0 = __latency_fn_structure_row_ids_none_id();
		(void) artifact->readiness_row_ids_by_feature_id.append(__latency_local_0);
		}
	}
	int_t<> rowIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_1 = artifact->readiness;
	for (auto __latency_local_2 : foreach_range(__latency_local_1)) {
		auto row = __latency_local_2.value_copy();
		int_t<std::uint32_t> featureDenseId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(row->consumer_feature_id)));
		if (static_cast<bool>((__latency_fn_structure_row_ids_has_dense_id(featureDenseId, slotCount) && php::identical(cast<int_t<>>(artifact->readiness_row_ids_by_feature_id[__latency_fn_structure_row_ids_dense_index(featureDenseId)]), static_cast<int_t<> >(0))))) {
			artifact->readiness_row_ids_by_feature_id[__latency_fn_structure_row_ids_dense_index(featureDenseId)] = __latency_fn_structure_row_ids_uint32_from_int((rowIndex + static_cast<int_t<> >(1)));
		}
		rowIndex = (rowIndex + static_cast<int_t<> >(1));
	}
	artifact->readiness_lookup_index_slot_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->readiness_row_ids_by_feature_id));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityRegistryRow __latency_fn_type_capability_readiness_registry_row(int_t<std::uint16_t> capabilityId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::registry_row", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[102]);
	CapabilityRegistryRow row = CapabilityRegistryRow{};
	row->capability_id = capabilityId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
void __latency_fn_type_capability_readiness_append_registry(shared_p<CapabilityCoverageArtifact> artifact, int_t<std::uint16_t> capabilityId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::append_registry", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[103]);
	{
	auto __latency_local_0 = __latency_fn_type_capability_readiness_registry_row(cast<int_t<std::uint16_t>>(capabilityId));
	(void) artifact->capabilities.append(__latency_local_0);
	}
	artifact->capability_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->capabilities));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityProviderRow __latency_fn_type_capability_readiness_provider_from_type_ref(TypeRefRow typeRef) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::provider_from_type_ref", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[104]);
	CapabilityProviderRow row = CapabilityProviderRow{};
	row->capability_id = __latency_fn_type_capability_readiness_capability_type_ref_known_id();
	row->source_row_id = typeRef->type_ref_id;
	row->type_ref_id = typeRef->type_ref_id;
	row->source_key_id = __latency_fn_type_capability_readiness_source_key_type_ref_row_id();
	row->adapter_id = __latency_fn_type_capability_readiness_adapter_none_id();
	row->evidence_id = __latency_fn_type_capability_readiness_evidence_type_ref_table_id();
	int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_type_capability_readiness_type_ref_known_blocked_reason_from_row(typeRef));
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_none_id())))) {
		row->status_id = __latency_fn_type_capability_readiness_status_ready_id();
		row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_none_id();
	}
	else {
		row->status_id = __latency_fn_type_capability_readiness_status_blocked_id();
		row->blocked_reason_id = blockedReasonId;
	}
	return row;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
void __latency_fn_type_capability_readiness_append_provider(shared_p<CapabilityCoverageArtifact> artifact, CapabilityProviderRow row) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::append_provider", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[105]);
	(void) artifact->providers.append(row);
	artifact->provider_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->providers));
	__latency_fn_type_capability_readiness_invalidate_provider_lookup_index(artifact);
	__latency_fn_type_capability_readiness_update_lookup_policy(artifact);
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_ready_consumer_from_contract(ProjectCallableContractRow contract) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::ready_consumer_from_contract", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[106]);
	CapabilityConsumerRow row = CapabilityConsumerRow{};
	row->capability_id = __latency_fn_type_capability_readiness_capability_type_ref_known_id();
	row->source_row_id = contract->contract_id;
	row->provider_source_row_id = contract->return_type_ref_id;
	row->feature_id = __latency_fn_type_capability_readiness_feature_callable_return_type_id();
	row->source_key_id = __latency_fn_type_capability_readiness_source_key_callable_contract_row_id();
	row->provider_source_key_id = __latency_fn_type_capability_readiness_source_key_type_ref_row_id();
	row->provider_type_ref_id = contract->return_type_ref_id;
	TypeRefRow typeRef = __latency_fn_type_refs_row_from_id(contract->return_type_ref_id);
	int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_type_capability_readiness_type_ref_known_blocked_reason_from_row(typeRef));
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_type_capability_readiness_blocked_reason_none_id())))) {
		row->status_id = __latency_fn_type_capability_readiness_status_ready_id();
		row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_none_id();
	}
	else {
		row->status_id = __latency_fn_type_capability_readiness_status_blocked_id();
		row->blocked_reason_id = blockedReasonId;
	}
	return row;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityConsumerRow __latency_fn_type_capability_readiness_blocked_lowering_consumer_from_contract(ProjectCallableContractRow contract) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::blocked_lowering_consumer_from_contract", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[107]);
	CapabilityConsumerRow row = CapabilityConsumerRow{};
	row->capability_id = __latency_fn_type_capability_readiness_capability_return_lowering_authorized_id();
	row->source_row_id = contract->contract_id;
	row->provider_source_row_id = contract->return_type_ref_id;
	row->feature_id = __latency_fn_type_capability_readiness_feature_callable_return_lowering_id();
	row->source_key_id = __latency_fn_type_capability_readiness_source_key_callable_contract_row_id();
	row->provider_source_key_id = __latency_fn_type_capability_readiness_source_key_type_ref_row_id();
	row->provider_type_ref_id = contract->return_type_ref_id;
	row->status_id = __latency_fn_type_capability_readiness_status_blocked_id();
	row->blocked_reason_id = __latency_fn_type_capability_readiness_blocked_reason_backend_preflight_not_reintroduced_id();
	return row;
}

}

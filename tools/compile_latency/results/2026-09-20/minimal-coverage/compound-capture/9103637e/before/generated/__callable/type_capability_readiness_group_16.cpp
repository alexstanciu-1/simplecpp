#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/CapabilityCoverageArtifact.hpp"
#include "__types/CapabilityProviderRow.hpp"
#include "__types/CapabilityReadinessRow.hpp"
#include "__types/TypeRefRow.hpp"
#include "__types/TypeRefTable.hpp"
#include "__callable/__latency_fn_type_capability_readiness_type_ref_id_for_source_row_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_type_ref_id_for_index_or_unknown.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_provider.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_readiness.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_registry.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_control_flow_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_return_lowering_authorized_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_local_storage_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_if_local_bool_return.hpp"
#include "__callable/__latency_fn_type_capability_readiness_if_condition_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_literal_scalar_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_assignment_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_storage_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_from_type_ref.hpp"
#include "__callable/__latency_fn_type_capability_readiness_readiness_from_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_provider.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_readiness.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_registry.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_capability_id_for_feature.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_control_flow_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_return_lowering_authorized_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_local_storage_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_if_local_binary_return.hpp"
#include "__callable/__latency_fn_type_capability_readiness_if_condition_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_literal_scalar_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_assignment_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_storage_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_from_type_ref.hpp"
#include "__callable/__latency_fn_type_capability_readiness_readiness_from_consumer.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_build_provider_lookup_index_if_recommended.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_by_type_ref_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_record_index_lookup_probe.hpp"
#include "__callable/__latency_fn_type_capability_readiness_record_lookup_probe.hpp"
namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_type_capability_readiness_type_ref_id_for_source_row_id(const vector_t<int_t<std::uint32_t>>& sourceRowIds, const vector_t<int_t<std::uint32_t>>& typeRefIds, int_t<std::uint32_t> sourceRowId, int_t<std::uint32_t> fallbackTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::type_ref_id_for_source_row_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[159]);
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>(((index < php::count(sourceRowIds)) && (index < php::count(typeRefIds))))) {
		if (static_cast<bool>(php::identical(cast<int_t<>>(sourceRowIds.at(index)), cast<int_t<>>(sourceRowId)))) {
			return cast<int_t<std::uint32_t>>(typeRefIds.at(index));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return cast<int_t<std::uint32_t>>(fallbackTypeRefId);
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_type_capability_readiness_type_ref_id_for_index_or_unknown(const vector_t<int_t<std::uint32_t>>& typeRefIds, int_t<> index) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::type_ref_id_for_index_or_unknown", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[160]);
	if (static_cast<bool>(((index < static_cast<int_t<> >(0)) || (index >= php::count(typeRefIds))))) {
		return __latency_fn_type_refs_unknown_id();
	}
	return cast<int_t<std::uint32_t>>(typeRefIds.at(index));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<CapabilityCoverageArtifact> __latency_fn_type_capability_readiness_coverage_from_type_refs_and_if_local_bool_return(TypeRefTable typeRefs, int_t<std::uint32_t> localSourceRowId, int_t<std::uint32_t> ifSourceRowId, int_t<std::uint32_t> thenLiteralSourceRowId, int_t<std::uint32_t> fallbackLiteralSourceRowId, int_t<std::uint32_t> localTypeRefId, int_t<std::uint32_t> thenTypeRefId, int_t<std::uint32_t> fallbackTypeRefId, int_t<std::uint32_t> conditionTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::coverage_from_type_refs_and_if_local_bool_return", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[161]);
	shared_p<CapabilityCoverageArtifact> artifact = __latency_fn_type_capability_readiness_new_artifact(php::count(typeRefs->types), static_cast<int_t<> >(5));
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_type_ref_known_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_scalar_local_storage_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_return_lowering_authorized_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_control_flow_id());
	auto __latency_local_0 = typeRefs->types;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto typeRef = __latency_local_1.value_copy();
		__latency_fn_type_capability_readiness_append_provider(artifact, __latency_fn_type_capability_readiness_provider_from_type_ref(typeRef));
	}
	CapabilityConsumerRow storageConsumer = __latency_fn_type_capability_readiness_local_storage_consumer(cast<int_t<std::uint32_t>>(localSourceRowId), cast<int_t<std::uint32_t>>(localTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, storageConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(storageConsumer));
	CapabilityConsumerRow initConsumer = __latency_fn_type_capability_readiness_local_assignment_consumer(cast<int_t<std::uint32_t>>(localSourceRowId), cast<int_t<std::uint32_t>>(localTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, initConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(initConsumer));
	CapabilityConsumerRow ifConsumer = __latency_fn_type_capability_readiness_if_condition_consumer(cast<int_t<std::uint32_t>>(ifSourceRowId), cast<int_t<std::uint32_t>>(conditionTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, ifConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(ifConsumer));
	CapabilityConsumerRow thenConsumer = __latency_fn_type_capability_readiness_literal_scalar_consumer(cast<int_t<std::uint32_t>>(thenLiteralSourceRowId), cast<int_t<std::uint32_t>>(thenTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, thenConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(thenConsumer));
	CapabilityConsumerRow fallbackConsumer = __latency_fn_type_capability_readiness_literal_scalar_consumer(cast<int_t<std::uint32_t>>(fallbackLiteralSourceRowId), cast<int_t<std::uint32_t>>(fallbackTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, fallbackConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(fallbackConsumer));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<CapabilityCoverageArtifact> __latency_fn_type_capability_readiness_coverage_from_type_refs_and_if_local_binary_return(TypeRefTable typeRefs, int_t<std::uint32_t> leftLocalSourceRowId, int_t<std::uint32_t> rightLocalSourceRowId, int_t<std::uint32_t> conditionBinarySourceRowId, int_t<std::uint16_t> conditionBinaryFeatureId, int_t<std::uint32_t> ifSourceRowId, int_t<std::uint32_t> thenLiteralSourceRowId, int_t<std::uint32_t> fallbackLiteralSourceRowId, int_t<std::uint32_t> conditionProviderTypeRefId, int_t<std::uint32_t> thenTypeRefId, int_t<std::uint32_t> fallbackTypeRefId, int_t<std::uint32_t> conditionTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::coverage_from_type_refs_and_if_local_binary_return", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[162]);
	shared_p<CapabilityCoverageArtifact> artifact = __latency_fn_type_capability_readiness_new_artifact(php::count(typeRefs->types), static_cast<int_t<> >(8));
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_type_ref_known_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_scalar_local_storage_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_binary_operator_capability_id_for_feature(cast<int_t<std::uint16_t>>(conditionBinaryFeatureId)));
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_return_lowering_authorized_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_control_flow_id());
	auto __latency_local_0 = typeRefs->types;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto typeRef = __latency_local_1.value_copy();
		__latency_fn_type_capability_readiness_append_provider(artifact, __latency_fn_type_capability_readiness_provider_from_type_ref(typeRef));
	}
	CapabilityConsumerRow leftStorageConsumer = __latency_fn_type_capability_readiness_local_storage_consumer(cast<int_t<std::uint32_t>>(leftLocalSourceRowId), cast<int_t<std::uint32_t>>(conditionProviderTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, leftStorageConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(leftStorageConsumer));
	CapabilityConsumerRow leftInitConsumer = __latency_fn_type_capability_readiness_local_assignment_consumer(cast<int_t<std::uint32_t>>(leftLocalSourceRowId), cast<int_t<std::uint32_t>>(conditionProviderTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, leftInitConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(leftInitConsumer));
	CapabilityConsumerRow rightStorageConsumer = __latency_fn_type_capability_readiness_local_storage_consumer(cast<int_t<std::uint32_t>>(rightLocalSourceRowId), cast<int_t<std::uint32_t>>(conditionProviderTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, rightStorageConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(rightStorageConsumer));
	CapabilityConsumerRow rightInitConsumer = __latency_fn_type_capability_readiness_local_assignment_consumer(cast<int_t<std::uint32_t>>(rightLocalSourceRowId), cast<int_t<std::uint32_t>>(conditionProviderTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, rightInitConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(rightInitConsumer));
	CapabilityConsumerRow binaryConsumer = __latency_fn_type_capability_readiness_binary_operator_consumer(cast<int_t<std::uint32_t>>(conditionBinarySourceRowId), cast<int_t<std::uint32_t>>(conditionProviderTypeRefId), cast<int_t<std::uint16_t>>(conditionBinaryFeatureId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, binaryConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(binaryConsumer));
	CapabilityConsumerRow ifConsumer = __latency_fn_type_capability_readiness_if_condition_consumer(cast<int_t<std::uint32_t>>(ifSourceRowId), cast<int_t<std::uint32_t>>(conditionTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, ifConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(ifConsumer));
	CapabilityConsumerRow thenConsumer = __latency_fn_type_capability_readiness_literal_scalar_consumer(cast<int_t<std::uint32_t>>(thenLiteralSourceRowId), cast<int_t<std::uint32_t>>(thenTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, thenConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(thenConsumer));
	CapabilityConsumerRow fallbackConsumer = __latency_fn_type_capability_readiness_literal_scalar_consumer(cast<int_t<std::uint32_t>>(fallbackLiteralSourceRowId), cast<int_t<std::uint32_t>>(fallbackTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, fallbackConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(fallbackConsumer));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
CapabilityProviderRow __latency_fn_type_capability_readiness_provider_by_type_ref_id(shared_p<CapabilityCoverageArtifact> artifact, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::provider_by_type_ref_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[163]);
	__latency_fn_type_capability_readiness_build_provider_lookup_index_if_recommended(artifact);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(typeRefId, cast<int_t<>>(artifact->provider_lookup_index_slot_count))))) {
		int_t<std::uint32_t> rowId = required_cast<int_t<std::uint32_t>>(artifact->provider_row_ids_by_type_ref_id[__latency_fn_structure_row_ids_dense_index(typeRefId)]);
		if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(rowId, cast<int_t<>>(artifact->provider_count))))) {
			CapabilityProviderRow row = artifact->providers[__latency_fn_structure_row_ids_dense_index(rowId)];
			if (static_cast<bool>(php::identical(cast<int_t<>>(row->type_ref_id), cast<int_t<>>(typeRefId)))) {
				__latency_fn_type_capability_readiness_record_index_lookup_probe(artifact);
				return row;
			}
		}
	}
	int_t<> scannedRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = artifact->providers;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		scannedRows = (scannedRows + static_cast<int_t<> >(1));
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->type_ref_id), cast<int_t<>>(typeRefId)))) {
			__latency_fn_type_capability_readiness_record_lookup_probe(artifact, __latency_fn_structure_row_ids_uint32_from_int(scannedRows));
			return row;
		}
	}
	__latency_fn_type_capability_readiness_record_lookup_probe(artifact, __latency_fn_structure_row_ids_uint32_from_int(scannedRows));
	CapabilityProviderRow empty = CapabilityProviderRow{};
	return empty;
}

}

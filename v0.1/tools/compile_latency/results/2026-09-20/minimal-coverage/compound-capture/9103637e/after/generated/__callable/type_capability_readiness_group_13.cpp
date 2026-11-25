#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/CapabilityCoverageArtifact.hpp"
#include "__types/CapabilityProviderRow.hpp"
#include "__types/CapabilityReadinessRow.hpp"
#include "__types/TypeRefRow.hpp"
#include "__types/TypeRefTable.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_provider.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_readiness.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_registry.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_control_flow_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_return_lowering_authorized_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_local_storage_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_local_bool_ternary_literal_return.hpp"
#include "__callable/__latency_fn_type_capability_readiness_literal_scalar_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_assignment_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_load_return_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_storage_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_from_type_ref.hpp"
#include "__callable/__latency_fn_type_capability_readiness_readiness_from_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_ternary_select_consumer.hpp"
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
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_local_binary_ternary_literal_return.hpp"
#include "__callable/__latency_fn_type_capability_readiness_literal_scalar_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_assignment_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_storage_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_from_type_ref.hpp"
#include "__callable/__latency_fn_type_capability_readiness_readiness_from_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_ternary_select_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_scalar_assignment.hpp"
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_scalar_assignment_feature.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_plus_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_provider.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_readiness.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_registry.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_capability_id_for_feature.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_local_storage_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_scalar_assignment_feature.hpp"
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
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_echo_output_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_local_storage_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_scalar_assignment_update_echo_return.hpp"
#include "__callable/__latency_fn_type_capability_readiness_echo_scalar_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_literal_scalar_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_assignment_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_storage_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_from_type_ref.hpp"
#include "__callable/__latency_fn_type_capability_readiness_readiness_from_consumer.hpp"
namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<CapabilityCoverageArtifact> __latency_fn_type_capability_readiness_coverage_from_type_refs_and_local_bool_ternary_literal_return(TypeRefTable typeRefs, int_t<std::uint32_t> localSourceRowId, int_t<std::uint32_t> ternarySourceRowId, int_t<std::uint32_t> conditionSourceRowId, int_t<std::uint32_t> thenSourceRowId, int_t<std::uint32_t> elseSourceRowId, int_t<std::uint32_t> localTypeRefId, int_t<std::uint32_t> thenTypeRefId, int_t<std::uint32_t> elseTypeRefId, int_t<std::uint32_t> resultTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::coverage_from_type_refs_and_local_bool_ternary_literal_return", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[145]);
	shared_p<CapabilityCoverageArtifact> artifact = __latency_fn_type_capability_readiness_new_artifact(php::count(typeRefs->types), static_cast<int_t<> >(6));
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
	CapabilityConsumerRow conditionConsumer = __latency_fn_type_capability_readiness_local_load_return_consumer(cast<int_t<std::uint32_t>>(conditionSourceRowId), cast<int_t<std::uint32_t>>(localTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, conditionConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(conditionConsumer));
	CapabilityConsumerRow thenConsumer = __latency_fn_type_capability_readiness_literal_scalar_consumer(cast<int_t<std::uint32_t>>(thenSourceRowId), cast<int_t<std::uint32_t>>(thenTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, thenConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(thenConsumer));
	CapabilityConsumerRow elseConsumer = __latency_fn_type_capability_readiness_literal_scalar_consumer(cast<int_t<std::uint32_t>>(elseSourceRowId), cast<int_t<std::uint32_t>>(elseTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, elseConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(elseConsumer));
	CapabilityConsumerRow ternaryConsumer = __latency_fn_type_capability_readiness_ternary_select_consumer(cast<int_t<std::uint32_t>>(ternarySourceRowId), cast<int_t<std::uint32_t>>(localTypeRefId), cast<int_t<std::uint32_t>>(thenTypeRefId), cast<int_t<std::uint32_t>>(elseTypeRefId), cast<int_t<std::uint32_t>>(resultTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, ternaryConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(ternaryConsumer));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<CapabilityCoverageArtifact> __latency_fn_type_capability_readiness_coverage_from_type_refs_and_local_binary_ternary_literal_return(TypeRefTable typeRefs, int_t<std::uint32_t> leftLocalSourceRowId, int_t<std::uint32_t> rightLocalSourceRowId, int_t<std::uint32_t> conditionBinarySourceRowId, int_t<std::uint16_t> conditionBinaryFeatureId, int_t<std::uint32_t> ternarySourceRowId, int_t<std::uint32_t> thenSourceRowId, int_t<std::uint32_t> elseSourceRowId, int_t<std::uint32_t> conditionProviderTypeRefId, int_t<std::uint32_t> conditionResultTypeRefId, int_t<std::uint32_t> thenTypeRefId, int_t<std::uint32_t> elseTypeRefId, int_t<std::uint32_t> resultTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::coverage_from_type_refs_and_local_binary_ternary_literal_return", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[146]);
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
	CapabilityConsumerRow thenConsumer = __latency_fn_type_capability_readiness_literal_scalar_consumer(cast<int_t<std::uint32_t>>(thenSourceRowId), cast<int_t<std::uint32_t>>(thenTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, thenConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(thenConsumer));
	CapabilityConsumerRow elseConsumer = __latency_fn_type_capability_readiness_literal_scalar_consumer(cast<int_t<std::uint32_t>>(elseSourceRowId), cast<int_t<std::uint32_t>>(elseTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, elseConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(elseConsumer));
	CapabilityConsumerRow ternaryConsumer = __latency_fn_type_capability_readiness_ternary_select_consumer(cast<int_t<std::uint32_t>>(ternarySourceRowId), cast<int_t<std::uint32_t>>(conditionResultTypeRefId), cast<int_t<std::uint32_t>>(thenTypeRefId), cast<int_t<std::uint32_t>>(elseTypeRefId), cast<int_t<std::uint32_t>>(resultTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, ternaryConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(ternaryConsumer));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<CapabilityCoverageArtifact> __latency_fn_type_capability_readiness_coverage_from_type_refs_and_scalar_assignment(TypeRefTable typeRefs, int_t<std::uint32_t> localSourceRowId, int_t<std::uint32_t> assignmentSourceRowId, int_t<std::uint32_t> binarySourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::coverage_from_type_refs_and_scalar_assignment", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[147]);
	return __latency_fn_type_capability_readiness_coverage_from_type_refs_and_scalar_assignment_feature(typeRefs, cast<int_t<std::uint32_t>>(localSourceRowId), cast<int_t<std::uint32_t>>(assignmentSourceRowId), cast<int_t<std::uint32_t>>(binarySourceRowId), cast<int_t<std::uint32_t>>(typeRefId), __latency_fn_type_capability_readiness_feature_binary_operator_plus_id());
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<CapabilityCoverageArtifact> __latency_fn_type_capability_readiness_coverage_from_type_refs_and_scalar_assignment_feature(TypeRefTable typeRefs, int_t<std::uint32_t> localSourceRowId, int_t<std::uint32_t> assignmentSourceRowId, int_t<std::uint32_t> binarySourceRowId, int_t<std::uint32_t> typeRefId, int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::coverage_from_type_refs_and_scalar_assignment_feature", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[148]);
	shared_p<CapabilityCoverageArtifact> artifact = __latency_fn_type_capability_readiness_new_artifact(php::count(typeRefs->types), static_cast<int_t<> >(4));
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_type_ref_known_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_scalar_local_storage_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_binary_operator_capability_id_for_feature(cast<int_t<std::uint16_t>>(featureId)));
	auto __latency_local_0 = typeRefs->types;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto typeRef = __latency_local_1.value_copy();
		__latency_fn_type_capability_readiness_append_provider(artifact, __latency_fn_type_capability_readiness_provider_from_type_ref(typeRef));
	}
	CapabilityConsumerRow storageConsumer = __latency_fn_type_capability_readiness_local_storage_consumer(cast<int_t<std::uint32_t>>(localSourceRowId), cast<int_t<std::uint32_t>>(typeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, storageConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(storageConsumer));
	CapabilityConsumerRow initConsumer = __latency_fn_type_capability_readiness_local_assignment_consumer(cast<int_t<std::uint32_t>>(localSourceRowId), cast<int_t<std::uint32_t>>(typeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, initConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(initConsumer));
	CapabilityConsumerRow assignmentConsumer = __latency_fn_type_capability_readiness_local_assignment_consumer(cast<int_t<std::uint32_t>>(assignmentSourceRowId), cast<int_t<std::uint32_t>>(typeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, assignmentConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(assignmentConsumer));
	CapabilityConsumerRow binaryConsumer = __latency_fn_type_capability_readiness_binary_operator_consumer(cast<int_t<std::uint32_t>>(binarySourceRowId), cast<int_t<std::uint32_t>>(typeRefId), cast<int_t<std::uint16_t>>(featureId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, binaryConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(binaryConsumer));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<CapabilityCoverageArtifact> __latency_fn_type_capability_readiness_coverage_from_type_refs_and_scalar_assignment_update_echo_return(TypeRefTable typeRefs, int_t<std::uint32_t> firstLocalSourceRowId, int_t<std::uint32_t> secondLocalSourceRowId, int_t<std::uint32_t> updateAssignmentSourceRowId, int_t<std::uint32_t> binarySourceRowId, int_t<std::uint32_t> echoSourceRowId, int_t<std::uint32_t> returnLiteralSourceRowId, int_t<std::uint32_t> typeRefId, int_t<std::uint16_t> binaryFeatureId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::coverage_from_type_refs_and_scalar_assignment_update_echo_return", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[149]);
	shared_p<CapabilityCoverageArtifact> artifact = __latency_fn_type_capability_readiness_new_artifact(php::count(typeRefs->types), static_cast<int_t<> >(8));
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_type_ref_known_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_scalar_local_storage_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_binary_operator_capability_id_for_feature(cast<int_t<std::uint16_t>>(binaryFeatureId)));
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_scalar_echo_output_id());
	auto __latency_local_0 = typeRefs->types;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto typeRef = __latency_local_1.value_copy();
		__latency_fn_type_capability_readiness_append_provider(artifact, __latency_fn_type_capability_readiness_provider_from_type_ref(typeRef));
	}
	CapabilityConsumerRow firstStorageConsumer = __latency_fn_type_capability_readiness_local_storage_consumer(cast<int_t<std::uint32_t>>(firstLocalSourceRowId), cast<int_t<std::uint32_t>>(typeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, firstStorageConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(firstStorageConsumer));
	CapabilityConsumerRow firstAssignmentConsumer = __latency_fn_type_capability_readiness_local_assignment_consumer(cast<int_t<std::uint32_t>>(firstLocalSourceRowId), cast<int_t<std::uint32_t>>(typeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, firstAssignmentConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(firstAssignmentConsumer));
	CapabilityConsumerRow secondStorageConsumer = __latency_fn_type_capability_readiness_local_storage_consumer(cast<int_t<std::uint32_t>>(secondLocalSourceRowId), cast<int_t<std::uint32_t>>(typeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, secondStorageConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(secondStorageConsumer));
	CapabilityConsumerRow secondAssignmentConsumer = __latency_fn_type_capability_readiness_local_assignment_consumer(cast<int_t<std::uint32_t>>(secondLocalSourceRowId), cast<int_t<std::uint32_t>>(typeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, secondAssignmentConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(secondAssignmentConsumer));
	CapabilityConsumerRow updateAssignmentConsumer = __latency_fn_type_capability_readiness_local_assignment_consumer(cast<int_t<std::uint32_t>>(updateAssignmentSourceRowId), cast<int_t<std::uint32_t>>(typeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, updateAssignmentConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(updateAssignmentConsumer));
	CapabilityConsumerRow binaryConsumer = __latency_fn_type_capability_readiness_binary_operator_consumer(cast<int_t<std::uint32_t>>(binarySourceRowId), cast<int_t<std::uint32_t>>(typeRefId), cast<int_t<std::uint16_t>>(binaryFeatureId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, binaryConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(binaryConsumer));
	CapabilityConsumerRow echoConsumer = __latency_fn_type_capability_readiness_echo_scalar_consumer(cast<int_t<std::uint32_t>>(echoSourceRowId), cast<int_t<std::uint32_t>>(typeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, echoConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(echoConsumer));
	CapabilityConsumerRow returnConsumer = __latency_fn_type_capability_readiness_literal_scalar_consumer(cast<int_t<std::uint32_t>>(returnLiteralSourceRowId), cast<int_t<std::uint32_t>>(typeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, returnConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(returnConsumer));
	return artifact;
}

}

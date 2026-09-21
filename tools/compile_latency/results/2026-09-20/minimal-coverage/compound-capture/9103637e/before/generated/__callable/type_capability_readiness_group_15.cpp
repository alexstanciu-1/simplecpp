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
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_capability_id_for_feature.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_control_flow_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_echo_output_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_local_storage_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_string_comparison_ternary_echo_return.hpp"
#include "__callable/__latency_fn_type_capability_readiness_echo_string_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_literal_scalar_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_assignment_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_storage_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_from_type_ref.hpp"
#include "__callable/__latency_fn_type_capability_readiness_readiness_from_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_ternary_select_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_type_ref_id_for_index_or_unknown.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_provider.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_readiness.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_registry.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_capability_id_for_feature.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_control_flow_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_echo_output_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_local_storage_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_scalar_comparison_if_string_echo_return.hpp"
#include "__callable/__latency_fn_type_capability_readiness_echo_string_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_if_condition_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_literal_scalar_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_assignment_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_storage_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_from_type_ref.hpp"
#include "__callable/__latency_fn_type_capability_readiness_readiness_from_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_type_ref_id_for_index_or_unknown.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_provider.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_readiness.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_registry.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_echo_output_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_scalar_local_storage_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_runtime_text_coercion_echo_return.hpp"
#include "__callable/__latency_fn_type_capability_readiness_echo_string_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_literal_scalar_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_assignment_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_local_storage_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_from_type_ref.hpp"
#include "__callable/__latency_fn_type_capability_readiness_readiness_from_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_runtime_text_coercion_echo_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_type_ref_id_for_index_or_unknown.hpp"
namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<CapabilityCoverageArtifact> __latency_fn_type_capability_readiness_coverage_from_type_refs_and_string_comparison_ternary_echo_return(TypeRefTable typeRefs, const vector_t<int_t<std::uint32_t>>& localSourceRowIds, const vector_t<int_t<std::uint32_t>>& localSourceTypeRefIds, const vector_t<int_t<std::uint32_t>>& assignmentSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentTypeRefIds, int_t<std::uint32_t> comparisonSourceRowId, int_t<std::uint16_t> comparisonFeatureId, int_t<std::uint32_t> conditionProviderTypeRefId, int_t<std::uint32_t> conditionResultTypeRefId, int_t<std::uint32_t> ternarySourceRowId, int_t<std::uint32_t> ternaryResultTypeRefId, int_t<std::uint32_t> thenLiteralSourceRowId, int_t<std::uint32_t> thenLiteralTypeRefId, int_t<std::uint32_t> elseLiteralSourceRowId, int_t<std::uint32_t> elseLiteralTypeRefId, const vector_t<int_t<std::uint32_t>>& echoSourceRowIds, const vector_t<int_t<std::uint32_t>>& echoTypeRefIds, int_t<std::uint32_t> returnLiteralSourceRowId, int_t<std::uint32_t> returnLiteralTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::coverage_from_type_refs_and_string_comparison_ternary_echo_return", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[156]);
	int_t<> consumerCapacity = required_cast<int_t<>>((((php::count(localSourceRowIds) + php::count(assignmentSourceRowIds)) + php::count(echoSourceRowIds)) + static_cast<int_t<> >(5)));
	shared_p<CapabilityCoverageArtifact> artifact = __latency_fn_type_capability_readiness_new_artifact(php::count(typeRefs->types), consumerCapacity);
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_type_ref_known_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_scalar_local_storage_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_binary_operator_capability_id_for_feature(cast<int_t<std::uint16_t>>(comparisonFeatureId)));
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_control_flow_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_scalar_echo_output_id());
	auto __latency_local_0 = typeRefs->types;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto typeRef = __latency_local_1.value_copy();
		__latency_fn_type_capability_readiness_append_provider(artifact, __latency_fn_type_capability_readiness_provider_from_type_ref(typeRef));
	}
	int_t<> localIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_2 = localSourceRowIds;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto localSourceRowId = __latency_local_3.value_copy();
		int_t<std::uint32_t> localTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_type_capability_readiness_type_ref_id_for_index_or_unknown(localSourceTypeRefIds, localIndex));
		CapabilityConsumerRow storageConsumer = __latency_fn_type_capability_readiness_local_storage_consumer(cast<int_t<std::uint32_t>>(localSourceRowId), cast<int_t<std::uint32_t>>(localTypeRefId));
		__latency_fn_type_capability_readiness_append_consumer(artifact, storageConsumer);
		__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(storageConsumer));
		localIndex = (localIndex + static_cast<int_t<> >(1));
	}
	int_t<> assignmentIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_4 = assignmentSourceRowIds;
	for (auto __latency_local_5 : foreach_range(__latency_local_4)) {
		auto assignmentSourceRowId = __latency_local_5.value_copy();
		int_t<std::uint32_t> assignmentTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_type_capability_readiness_type_ref_id_for_index_or_unknown(assignmentTypeRefIds, assignmentIndex));
		CapabilityConsumerRow assignmentConsumer = __latency_fn_type_capability_readiness_local_assignment_consumer(cast<int_t<std::uint32_t>>(assignmentSourceRowId), cast<int_t<std::uint32_t>>(assignmentTypeRefId));
		__latency_fn_type_capability_readiness_append_consumer(artifact, assignmentConsumer);
		__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(assignmentConsumer));
		assignmentIndex = (assignmentIndex + static_cast<int_t<> >(1));
	}
	CapabilityConsumerRow comparisonConsumer = __latency_fn_type_capability_readiness_binary_operator_consumer(cast<int_t<std::uint32_t>>(comparisonSourceRowId), cast<int_t<std::uint32_t>>(conditionProviderTypeRefId), cast<int_t<std::uint16_t>>(comparisonFeatureId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, comparisonConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(comparisonConsumer));
	CapabilityConsumerRow thenConsumer = __latency_fn_type_capability_readiness_literal_scalar_consumer(cast<int_t<std::uint32_t>>(thenLiteralSourceRowId), cast<int_t<std::uint32_t>>(thenLiteralTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, thenConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(thenConsumer));
	CapabilityConsumerRow elseConsumer = __latency_fn_type_capability_readiness_literal_scalar_consumer(cast<int_t<std::uint32_t>>(elseLiteralSourceRowId), cast<int_t<std::uint32_t>>(elseLiteralTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, elseConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(elseConsumer));
	CapabilityConsumerRow ternaryConsumer = __latency_fn_type_capability_readiness_ternary_select_consumer(cast<int_t<std::uint32_t>>(ternarySourceRowId), cast<int_t<std::uint32_t>>(conditionResultTypeRefId), cast<int_t<std::uint32_t>>(thenLiteralTypeRefId), cast<int_t<std::uint32_t>>(elseLiteralTypeRefId), cast<int_t<std::uint32_t>>(ternaryResultTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, ternaryConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(ternaryConsumer));
	int_t<> echoIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_6 = echoSourceRowIds;
	for (auto __latency_local_7 : foreach_range(__latency_local_6)) {
		auto echoSourceRowId = __latency_local_7.value_copy();
		int_t<std::uint32_t> echoTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_type_capability_readiness_type_ref_id_for_index_or_unknown(echoTypeRefIds, echoIndex));
		CapabilityConsumerRow echoConsumer = __latency_fn_type_capability_readiness_echo_string_consumer(cast<int_t<std::uint32_t>>(echoSourceRowId), cast<int_t<std::uint32_t>>(echoTypeRefId));
		__latency_fn_type_capability_readiness_append_consumer(artifact, echoConsumer);
		__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(echoConsumer));
		echoIndex = (echoIndex + static_cast<int_t<> >(1));
	}
	CapabilityConsumerRow returnConsumer = __latency_fn_type_capability_readiness_literal_scalar_consumer(cast<int_t<std::uint32_t>>(returnLiteralSourceRowId), cast<int_t<std::uint32_t>>(returnLiteralTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, returnConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(returnConsumer));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<CapabilityCoverageArtifact> __latency_fn_type_capability_readiness_coverage_from_type_refs_and_scalar_comparison_if_string_echo_return(TypeRefTable typeRefs, const vector_t<int_t<std::uint32_t>>& localSourceRowIds, const vector_t<int_t<std::uint32_t>>& localSourceTypeRefIds, const vector_t<int_t<std::uint32_t>>& assignmentSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentTypeRefIds, int_t<std::uint32_t> comparisonSourceRowId, int_t<std::uint16_t> comparisonFeatureId, int_t<std::uint32_t> conditionProviderTypeRefId, int_t<std::uint32_t> conditionResultTypeRefId, int_t<std::uint32_t> ifSourceRowId, const vector_t<int_t<std::uint32_t>>& echoSourceRowIds, const vector_t<int_t<std::uint32_t>>& echoTypeRefIds, int_t<std::uint32_t> returnLiteralSourceRowId, int_t<std::uint32_t> returnLiteralTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::coverage_from_type_refs_and_scalar_comparison_if_string_echo_return", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[157]);
	int_t<> consumerCapacity = required_cast<int_t<>>((((php::count(localSourceRowIds) + php::count(assignmentSourceRowIds)) + php::count(echoSourceRowIds)) + static_cast<int_t<> >(3)));
	shared_p<CapabilityCoverageArtifact> artifact = __latency_fn_type_capability_readiness_new_artifact(php::count(typeRefs->types), consumerCapacity);
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_type_ref_known_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_scalar_local_storage_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_binary_operator_capability_id_for_feature(cast<int_t<std::uint16_t>>(comparisonFeatureId)));
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_control_flow_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_scalar_echo_output_id());
	auto __latency_local_0 = typeRefs->types;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto typeRef = __latency_local_1.value_copy();
		__latency_fn_type_capability_readiness_append_provider(artifact, __latency_fn_type_capability_readiness_provider_from_type_ref(typeRef));
	}
	int_t<> localIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_2 = localSourceRowIds;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto localSourceRowId = __latency_local_3.value_copy();
		int_t<std::uint32_t> localTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_type_capability_readiness_type_ref_id_for_index_or_unknown(localSourceTypeRefIds, localIndex));
		CapabilityConsumerRow storageConsumer = __latency_fn_type_capability_readiness_local_storage_consumer(cast<int_t<std::uint32_t>>(localSourceRowId), cast<int_t<std::uint32_t>>(localTypeRefId));
		__latency_fn_type_capability_readiness_append_consumer(artifact, storageConsumer);
		__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(storageConsumer));
		localIndex = (localIndex + static_cast<int_t<> >(1));
	}
	int_t<> assignmentIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_4 = assignmentSourceRowIds;
	for (auto __latency_local_5 : foreach_range(__latency_local_4)) {
		auto assignmentSourceRowId = __latency_local_5.value_copy();
		int_t<std::uint32_t> assignmentTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_type_capability_readiness_type_ref_id_for_index_or_unknown(assignmentTypeRefIds, assignmentIndex));
		CapabilityConsumerRow assignmentConsumer = __latency_fn_type_capability_readiness_local_assignment_consumer(cast<int_t<std::uint32_t>>(assignmentSourceRowId), cast<int_t<std::uint32_t>>(assignmentTypeRefId));
		__latency_fn_type_capability_readiness_append_consumer(artifact, assignmentConsumer);
		__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(assignmentConsumer));
		assignmentIndex = (assignmentIndex + static_cast<int_t<> >(1));
	}
	CapabilityConsumerRow comparisonConsumer = __latency_fn_type_capability_readiness_binary_operator_consumer(cast<int_t<std::uint32_t>>(comparisonSourceRowId), cast<int_t<std::uint32_t>>(conditionProviderTypeRefId), cast<int_t<std::uint16_t>>(comparisonFeatureId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, comparisonConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(comparisonConsumer));
	CapabilityConsumerRow ifConsumer = __latency_fn_type_capability_readiness_if_condition_consumer(cast<int_t<std::uint32_t>>(ifSourceRowId), cast<int_t<std::uint32_t>>(conditionResultTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, ifConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(ifConsumer));
	int_t<> echoIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_6 = echoSourceRowIds;
	for (auto __latency_local_7 : foreach_range(__latency_local_6)) {
		auto echoSourceRowId = __latency_local_7.value_copy();
		int_t<std::uint32_t> echoTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_type_capability_readiness_type_ref_id_for_index_or_unknown(echoTypeRefIds, echoIndex));
		CapabilityConsumerRow echoConsumer = __latency_fn_type_capability_readiness_echo_string_consumer(cast<int_t<std::uint32_t>>(echoSourceRowId), cast<int_t<std::uint32_t>>(echoTypeRefId));
		__latency_fn_type_capability_readiness_append_consumer(artifact, echoConsumer);
		__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(echoConsumer));
		echoIndex = (echoIndex + static_cast<int_t<> >(1));
	}
	CapabilityConsumerRow returnConsumer = __latency_fn_type_capability_readiness_literal_scalar_consumer(cast<int_t<std::uint32_t>>(returnLiteralSourceRowId), cast<int_t<std::uint32_t>>(returnLiteralTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, returnConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(returnConsumer));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<CapabilityCoverageArtifact> __latency_fn_type_capability_readiness_coverage_from_type_refs_and_runtime_text_coercion_echo_return(TypeRefTable typeRefs, const vector_t<int_t<std::uint32_t>>& localSourceRowIds, const vector_t<int_t<std::uint32_t>>& localSourceTypeRefIds, const vector_t<int_t<std::uint32_t>>& assignmentSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentTypeRefIds, const vector_t<int_t<std::uint32_t>>& textCoercionEchoSourceRowIds, const vector_t<int_t<std::uint32_t>>& textCoercionEchoTypeRefIds, const vector_t<int_t<std::uint32_t>>& stringEchoSourceRowIds, const vector_t<int_t<std::uint32_t>>& stringEchoTypeRefIds, int_t<std::uint32_t> returnLiteralSourceRowId, int_t<std::uint32_t> returnLiteralTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::coverage_from_type_refs_and_runtime_text_coercion_echo_return", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[158]);
	shared_p<CapabilityCoverageArtifact> artifact = __latency_fn_type_capability_readiness_new_artifact(php::count(typeRefs->types), ((((php::count(localSourceRowIds) + php::count(assignmentSourceRowIds)) + php::count(textCoercionEchoSourceRowIds)) + php::count(stringEchoSourceRowIds)) + static_cast<int_t<> >(1)));
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_type_ref_known_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_scalar_local_storage_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_scalar_echo_output_id());
	auto __latency_local_0 = typeRefs->types;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto typeRef = __latency_local_1.value_copy();
		__latency_fn_type_capability_readiness_append_provider(artifact, __latency_fn_type_capability_readiness_provider_from_type_ref(typeRef));
	}
	int_t<> localIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_2 = localSourceRowIds;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto localSourceRowId = __latency_local_3.value_copy();
		int_t<std::uint32_t> localTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_type_capability_readiness_type_ref_id_for_index_or_unknown(localSourceTypeRefIds, localIndex));
		CapabilityConsumerRow storageConsumer = __latency_fn_type_capability_readiness_local_storage_consumer(cast<int_t<std::uint32_t>>(localSourceRowId), cast<int_t<std::uint32_t>>(localTypeRefId));
		__latency_fn_type_capability_readiness_append_consumer(artifact, storageConsumer);
		__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(storageConsumer));
		localIndex = (localIndex + static_cast<int_t<> >(1));
	}
	int_t<> assignmentIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_4 = assignmentSourceRowIds;
	for (auto __latency_local_5 : foreach_range(__latency_local_4)) {
		auto assignmentSourceRowId = __latency_local_5.value_copy();
		int_t<std::uint32_t> assignmentTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_type_capability_readiness_type_ref_id_for_index_or_unknown(assignmentTypeRefIds, assignmentIndex));
		CapabilityConsumerRow assignmentConsumer = __latency_fn_type_capability_readiness_local_assignment_consumer(cast<int_t<std::uint32_t>>(assignmentSourceRowId), cast<int_t<std::uint32_t>>(assignmentTypeRefId));
		__latency_fn_type_capability_readiness_append_consumer(artifact, assignmentConsumer);
		__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(assignmentConsumer));
		assignmentIndex = (assignmentIndex + static_cast<int_t<> >(1));
	}
	int_t<> textCoercionIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>(((textCoercionIndex < php::count(textCoercionEchoSourceRowIds)) && (textCoercionIndex < php::count(textCoercionEchoTypeRefIds))))) {
		int_t<std::uint32_t> echoSourceRowId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(textCoercionEchoSourceRowIds.at(textCoercionIndex)));
		int_t<std::uint32_t> echoTypeRefId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(textCoercionEchoTypeRefIds.at(textCoercionIndex)));
		CapabilityConsumerRow echoConsumer = __latency_fn_type_capability_readiness_runtime_text_coercion_echo_consumer(cast<int_t<std::uint32_t>>(echoSourceRowId), cast<int_t<std::uint32_t>>(echoTypeRefId));
		__latency_fn_type_capability_readiness_append_consumer(artifact, echoConsumer);
		__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(echoConsumer));
		textCoercionIndex = (textCoercionIndex + static_cast<int_t<> >(1));
	}
	int_t<> stringEchoIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_6 = stringEchoSourceRowIds;
	for (auto __latency_local_7 : foreach_range(__latency_local_6)) {
		auto echoSourceRowId = __latency_local_7.value_copy();
		int_t<std::uint32_t> echoTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_type_capability_readiness_type_ref_id_for_index_or_unknown(stringEchoTypeRefIds, stringEchoIndex));
		CapabilityConsumerRow echoConsumer = __latency_fn_type_capability_readiness_echo_string_consumer(cast<int_t<std::uint32_t>>(echoSourceRowId), cast<int_t<std::uint32_t>>(echoTypeRefId));
		__latency_fn_type_capability_readiness_append_consumer(artifact, echoConsumer);
		__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(echoConsumer));
		stringEchoIndex = (stringEchoIndex + static_cast<int_t<> >(1));
	}
	CapabilityConsumerRow returnConsumer = __latency_fn_type_capability_readiness_literal_scalar_consumer(cast<int_t<std::uint32_t>>(returnLiteralSourceRowId), cast<int_t<std::uint32_t>>(returnLiteralTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, returnConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(returnConsumer));
	return artifact;
}

}

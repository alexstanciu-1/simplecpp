#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerPlan.hpp"
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/CapabilityCoverageArtifact.hpp"
#include "__types/CapabilityProviderRow.hpp"
#include "__types/CapabilityReadinessRow.hpp"
#include "__types/ProjectCallableContractArtifact.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/TypeRefRow.hpp"
#include "__types/TypeRefTable.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_provider.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_readiness.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_registry.hpp"
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_consumer_plan.hpp"
#include "__callable/__latency_fn_type_capability_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_from_type_ref.hpp"
#include "__callable/__latency_fn_type_capability_readiness_readiness_from_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_provider.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_readiness.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_registry.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_lowering_consumer_from_contract.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_return_lowering_authorized_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_contracts.hpp"
#include "__callable/__latency_fn_type_capability_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_from_type_ref.hpp"
#include "__callable/__latency_fn_type_capability_readiness_readiness_from_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_ready_consumer_from_contract.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_provider.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_readiness.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_registry.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_return_lowering_authorized_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_literal.hpp"
#include "__callable/__latency_fn_type_capability_readiness_literal_scalar_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_from_type_ref.hpp"
#include "__callable/__latency_fn_type_capability_readiness_readiness_from_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_binary_operator.hpp"
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_binary_plus.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_plus_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_provider.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_readiness.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_registry.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_capability_id_for_feature.hpp"
#include "__callable/__latency_fn_type_capability_readiness_binary_operator_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_binary_operator.hpp"
#include "__callable/__latency_fn_type_capability_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_from_type_ref.hpp"
#include "__callable/__latency_fn_type_capability_readiness_readiness_from_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_provider.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_readiness.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_registry.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_string_concat_operator_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_blocked_string_concat.hpp"
#include "__callable/__latency_fn_type_capability_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_from_type_ref.hpp"
#include "__callable/__latency_fn_type_capability_readiness_readiness_from_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_string_concat_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_provider.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_readiness.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_registry.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_control_flow_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_return_lowering_authorized_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_type_ref_known_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_coverage_from_type_refs_and_ternary_literal_return.hpp"
#include "__callable/__latency_fn_type_capability_readiness_literal_scalar_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_from_type_ref.hpp"
#include "__callable/__latency_fn_type_capability_readiness_readiness_from_consumer.hpp"
#include "__callable/__latency_fn_type_capability_readiness_ternary_select_consumer.hpp"
namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<CapabilityCoverageArtifact> __latency_fn_type_capability_readiness_coverage_from_type_refs_and_consumer_plan(TypeRefTable typeRefs, shared_p<CapabilityConsumerPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::coverage_from_type_refs_and_consumer_plan", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[138]);
	shared_p<CapabilityCoverageArtifact> artifact = __latency_fn_type_capability_readiness_new_artifact(php::count(typeRefs->types), php::count(plan->consumers));
	auto __latency_local_0 = plan->registry_capability_ids;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto capabilityId = __latency_local_1.value_copy();
		__latency_fn_type_capability_readiness_append_registry(artifact, capabilityId);
	}
	auto __latency_local_2 = typeRefs->types;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto typeRef = __latency_local_3.value_copy();
		__latency_fn_type_capability_readiness_append_provider(artifact, __latency_fn_type_capability_readiness_provider_from_type_ref(typeRef));
	}
	auto __latency_local_4 = plan->consumers;
	for (auto __latency_local_5 : foreach_range(__latency_local_4)) {
		auto consumer = __latency_local_5.value_copy();
		__latency_fn_type_capability_readiness_append_consumer(artifact, consumer);
		__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(consumer));
	}
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<CapabilityCoverageArtifact> __latency_fn_type_capability_readiness_coverage_from_type_refs_and_contracts(TypeRefTable typeRefs, shared_p<ProjectCallableContractArtifact> contracts) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::coverage_from_type_refs_and_contracts", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[139]);
	shared_p<CapabilityCoverageArtifact> artifact = __latency_fn_type_capability_readiness_new_artifact(php::count(typeRefs->types), (php::count(contracts->rows) * static_cast<int_t<> >(2)));
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_type_ref_known_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_return_lowering_authorized_id());
	auto __latency_local_0 = typeRefs->types;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto typeRef = __latency_local_1.value_copy();
		__latency_fn_type_capability_readiness_append_provider(artifact, __latency_fn_type_capability_readiness_provider_from_type_ref(typeRef));
	}
	auto __latency_local_2 = contracts->rows;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto contract = __latency_local_3.value_copy();
		CapabilityConsumerRow readyConsumer = __latency_fn_type_capability_readiness_ready_consumer_from_contract(contract);
		__latency_fn_type_capability_readiness_append_consumer(artifact, readyConsumer);
		__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(readyConsumer));
		CapabilityConsumerRow blockedConsumer = __latency_fn_type_capability_readiness_blocked_lowering_consumer_from_contract(contract);
		__latency_fn_type_capability_readiness_append_consumer(artifact, blockedConsumer);
		__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(blockedConsumer));
	}
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<CapabilityCoverageArtifact> __latency_fn_type_capability_readiness_coverage_from_type_refs_and_literal(TypeRefTable typeRefs, int_t<std::uint32_t> literalSourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::coverage_from_type_refs_and_literal", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[140]);
	shared_p<CapabilityCoverageArtifact> artifact = __latency_fn_type_capability_readiness_new_artifact(php::count(typeRefs->types), static_cast<int_t<> >(1));
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_type_ref_known_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_return_lowering_authorized_id());
	auto __latency_local_0 = typeRefs->types;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto typeRef = __latency_local_1.value_copy();
		__latency_fn_type_capability_readiness_append_provider(artifact, __latency_fn_type_capability_readiness_provider_from_type_ref(typeRef));
	}
	CapabilityConsumerRow consumer = __latency_fn_type_capability_readiness_literal_scalar_consumer(cast<int_t<std::uint32_t>>(literalSourceRowId), cast<int_t<std::uint32_t>>(typeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, consumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(consumer));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<CapabilityCoverageArtifact> __latency_fn_type_capability_readiness_coverage_from_type_refs_and_binary_plus(TypeRefTable typeRefs, int_t<std::uint32_t> binarySourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::coverage_from_type_refs_and_binary_plus", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[141]);
	return __latency_fn_type_capability_readiness_coverage_from_type_refs_and_binary_operator(typeRefs, cast<int_t<std::uint32_t>>(binarySourceRowId), cast<int_t<std::uint32_t>>(typeRefId), __latency_fn_type_capability_readiness_feature_binary_operator_plus_id());
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<CapabilityCoverageArtifact> __latency_fn_type_capability_readiness_coverage_from_type_refs_and_binary_operator(TypeRefTable typeRefs, int_t<std::uint32_t> binarySourceRowId, int_t<std::uint32_t> typeRefId, int_t<std::uint16_t> featureId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::coverage_from_type_refs_and_binary_operator", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[142]);
	shared_p<CapabilityCoverageArtifact> artifact = __latency_fn_type_capability_readiness_new_artifact(php::count(typeRefs->types), static_cast<int_t<> >(1));
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_type_ref_known_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_binary_operator_capability_id_for_feature(cast<int_t<std::uint16_t>>(featureId)));
	auto __latency_local_0 = typeRefs->types;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto typeRef = __latency_local_1.value_copy();
		__latency_fn_type_capability_readiness_append_provider(artifact, __latency_fn_type_capability_readiness_provider_from_type_ref(typeRef));
	}
	CapabilityConsumerRow consumer = __latency_fn_type_capability_readiness_binary_operator_consumer(cast<int_t<std::uint32_t>>(binarySourceRowId), cast<int_t<std::uint32_t>>(typeRefId), cast<int_t<std::uint16_t>>(featureId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, consumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(consumer));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<CapabilityCoverageArtifact> __latency_fn_type_capability_readiness_coverage_from_type_refs_and_blocked_string_concat(TypeRefTable typeRefs, int_t<std::uint32_t> binarySourceRowId, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::coverage_from_type_refs_and_blocked_string_concat", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[143]);
	shared_p<CapabilityCoverageArtifact> artifact = __latency_fn_type_capability_readiness_new_artifact(php::count(typeRefs->types), static_cast<int_t<> >(1));
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_type_ref_known_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_string_concat_operator_id());
	auto __latency_local_0 = typeRefs->types;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto typeRef = __latency_local_1.value_copy();
		__latency_fn_type_capability_readiness_append_provider(artifact, __latency_fn_type_capability_readiness_provider_from_type_ref(typeRef));
	}
	CapabilityConsumerRow consumer = __latency_fn_type_capability_readiness_string_concat_consumer(cast<int_t<std::uint32_t>>(binarySourceRowId), cast<int_t<std::uint32_t>>(typeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, consumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(consumer));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<CapabilityCoverageArtifact> __latency_fn_type_capability_readiness_coverage_from_type_refs_and_ternary_literal_return(TypeRefTable typeRefs, int_t<std::uint32_t> ternarySourceRowId, int_t<std::uint32_t> conditionSourceRowId, int_t<std::uint32_t> thenSourceRowId, int_t<std::uint32_t> elseSourceRowId, int_t<std::uint32_t> conditionTypeRefId, int_t<std::uint32_t> thenTypeRefId, int_t<std::uint32_t> elseTypeRefId, int_t<std::uint32_t> resultTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::coverage_from_type_refs_and_ternary_literal_return", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[144]);
	shared_p<CapabilityCoverageArtifact> artifact = __latency_fn_type_capability_readiness_new_artifact(php::count(typeRefs->types), static_cast<int_t<> >(4));
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_type_ref_known_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_return_lowering_authorized_id());
	__latency_fn_type_capability_readiness_append_registry(artifact, __latency_fn_type_capability_readiness_capability_control_flow_id());
	auto __latency_local_0 = typeRefs->types;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto typeRef = __latency_local_1.value_copy();
		__latency_fn_type_capability_readiness_append_provider(artifact, __latency_fn_type_capability_readiness_provider_from_type_ref(typeRef));
	}
	CapabilityConsumerRow conditionConsumer = __latency_fn_type_capability_readiness_literal_scalar_consumer(cast<int_t<std::uint32_t>>(conditionSourceRowId), cast<int_t<std::uint32_t>>(conditionTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, conditionConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(conditionConsumer));
	CapabilityConsumerRow thenConsumer = __latency_fn_type_capability_readiness_literal_scalar_consumer(cast<int_t<std::uint32_t>>(thenSourceRowId), cast<int_t<std::uint32_t>>(thenTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, thenConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(thenConsumer));
	CapabilityConsumerRow elseConsumer = __latency_fn_type_capability_readiness_literal_scalar_consumer(cast<int_t<std::uint32_t>>(elseSourceRowId), cast<int_t<std::uint32_t>>(elseTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, elseConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(elseConsumer));
	CapabilityConsumerRow ternaryConsumer = __latency_fn_type_capability_readiness_ternary_select_consumer(cast<int_t<std::uint32_t>>(ternarySourceRowId), cast<int_t<std::uint32_t>>(conditionTypeRefId), cast<int_t<std::uint32_t>>(thenTypeRefId), cast<int_t<std::uint32_t>>(elseTypeRefId), cast<int_t<std::uint32_t>>(resultTypeRefId));
	__latency_fn_type_capability_readiness_append_consumer(artifact, ternaryConsumer);
	__latency_fn_type_capability_readiness_append_readiness(artifact, __latency_fn_type_capability_readiness_readiness_from_consumer(ternaryConsumer));
	return artifact;
}

}

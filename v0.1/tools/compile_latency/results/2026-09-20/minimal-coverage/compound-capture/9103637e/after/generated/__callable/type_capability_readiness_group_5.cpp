#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/CapabilityCoverageArtifact.hpp"
#include "__types/CapabilityProviderRow.hpp"
#include "__types/CapabilityReadinessRow.hpp"
#include "__types/CapabilityRegistryRow.hpp"
#include "__types/ConditionTruthinessPolicyArtifact.hpp"
#include "__types/ConditionTruthinessPolicyRow.hpp"
#include "__types/TypeRefRow.hpp"
#include "__types/TypeRefTable.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_blocked_reason_unknown_type_ref_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_type_ref_known_blocked_reason_from_row.hpp"
#include "__callable/__latency_fn_type_refs_type_status_known_id.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_artifact_kind_condition_truthiness_policy_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_new_condition_truthiness_policy_artifact.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_condition_truthiness_policy.hpp"
#include "__callable/__latency_fn_type_capability_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_append_condition_truthiness_policy.hpp"
#include "__callable/__latency_fn_type_capability_readiness_condition_truthiness_policies_from_type_refs.hpp"
#include "__callable/__latency_fn_type_capability_readiness_condition_truthiness_policy_from_type_ref.hpp"
#include "__callable/__latency_fn_type_capability_readiness_new_condition_truthiness_policy_artifact.hpp"
#include "__callable/__latency_fn_type_capability_readiness_condition_truthiness_policy_debug_string.hpp"
#include "__callable/__latency_fn_type_capability_readiness_condition_truthiness_policy_stable_hash.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_lookup_policy_scan_ok_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_lookup_policy_index_recommended_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_lookup_index_threshold.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_consumer_lookup_index_threshold.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_artifact_kind_capability_coverage_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_lookup_policy_scan_ok_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_type_capability_readiness_consumer_lookup_index_threshold.hpp"
#include "__callable/__latency_fn_type_capability_readiness_lookup_policy_index_recommended_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_lookup_policy_scan_ok_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_lookup_index_threshold.hpp"
#include "__callable/__latency_fn_type_capability_readiness_update_lookup_policy.hpp"
namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_type_ref_known_blocked_reason_from_row(TypeRefRow typeRef) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::type_ref_known_blocked_reason_from_row", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[79]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeRef->type_ref_id), cast<int_t<>>(__latency_fn_type_refs_unknown_id())))) {
		return __latency_fn_type_capability_readiness_blocked_reason_unknown_type_ref_id();
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(typeRef->status_id), cast<int_t<>>(__latency_fn_type_refs_type_status_known_id()))))) {
		return __latency_fn_type_capability_readiness_blocked_reason_unknown_type_ref_id();
	}
	return __latency_fn_type_capability_readiness_blocked_reason_none_id();
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<ConditionTruthinessPolicyArtifact> __latency_fn_type_capability_readiness_new_condition_truthiness_policy_artifact(int_t<> rowCapacity) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::new_condition_truthiness_policy_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[80]);
	shared_p<ConditionTruthinessPolicyArtifact> artifact = create<ConditionTruthinessPolicyArtifact>();
	artifact->artifact_kind_id = __latency_fn_type_capability_readiness_artifact_kind_condition_truthiness_policy_id();
	artifact->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	php::vector_reserve(artifact->rows, rowCapacity);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
void __latency_fn_type_capability_readiness_append_condition_truthiness_policy(shared_p<ConditionTruthinessPolicyArtifact>& artifact, ConditionTruthinessPolicyRow row) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::append_condition_truthiness_policy", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[81]);
	(void) artifact->rows.append(row);
	artifact->row_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->rows));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_type_capability_readiness_status_ready_id())))) {
		artifact->ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->ready_count) + static_cast<int_t<> >(1)));
	}
	else {
		artifact->blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->blocked_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<ConditionTruthinessPolicyArtifact> __latency_fn_type_capability_readiness_condition_truthiness_policies_from_type_refs(TypeRefTable typeRefs) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::condition_truthiness_policies_from_type_refs", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[82]);
	shared_p<ConditionTruthinessPolicyArtifact> artifact = __latency_fn_type_capability_readiness_new_condition_truthiness_policy_artifact(php::count(typeRefs->types));
	auto __latency_local_0 = typeRefs->types;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto typeRef = __latency_local_1.value_copy();
		__latency_fn_type_capability_readiness_append_condition_truthiness_policy(artifact, __latency_fn_type_capability_readiness_condition_truthiness_policy_from_type_ref(typeRef->type_ref_id));
	}
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
string_t __latency_fn_type_capability_readiness_condition_truthiness_policy_debug_string(shared_p<ConditionTruthinessPolicyArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::condition_truthiness_policy_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[83]);
	return (string_t("condition_truthiness_policy:rows=") + cast<string_t>(cast<int_t<>>(artifact->row_count)) + string_t(":ready=") + cast<string_t>(cast<int_t<>>(artifact->ready_count)) + string_t(":blocked=") + cast<string_t>(cast<int_t<>>(artifact->blocked_count)));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_type_capability_readiness_condition_truthiness_policy_stable_hash(shared_p<ConditionTruthinessPolicyArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::condition_truthiness_policy_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[84]);
	string_t identity = required_cast<string_t>((string_t("condition_truthiness_policy:v1:") + cast<string_t>(cast<int_t<>>(artifact->row_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->ready_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->blocked_count))));
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		identity = (cast<string_t>(identity) + string_t(":") + cast<string_t>(cast<int_t<>>(row->type_ref_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->policy_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->blocked_reason_id)));
	}
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_lookup_policy_scan_ok_id() {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::lookup_policy_scan_ok_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[85]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_type_capability_readiness_lookup_policy_index_recommended_id() {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::lookup_policy_index_recommended_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[86]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_type_capability_readiness_provider_lookup_index_threshold() {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::provider_lookup_index_threshold", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[87]);
	return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(32));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_type_capability_readiness_consumer_lookup_index_threshold() {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::consumer_lookup_index_threshold", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[88]);
	return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(64));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<CapabilityCoverageArtifact> __latency_fn_type_capability_readiness_new_artifact(int_t<> providerCapacity, int_t<> consumerCapacity) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::new_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[89]);
	shared_p<CapabilityCoverageArtifact> artifact = create<CapabilityCoverageArtifact>();
	artifact->artifact_kind_id = __latency_fn_type_capability_readiness_artifact_kind_capability_coverage_id();
	artifact->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	artifact->lookup_policy_status_id = __latency_fn_type_capability_readiness_lookup_policy_scan_ok_id();
	php::vector_reserve(artifact->capabilities, static_cast<int_t<> >(8));
	php::vector_reserve(artifact->providers, providerCapacity);
	php::vector_reserve(artifact->consumers, consumerCapacity);
	php::vector_reserve(artifact->readiness, consumerCapacity);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
void __latency_fn_type_capability_readiness_update_lookup_policy(shared_p<CapabilityCoverageArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::update_lookup_policy", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[90]);
	if (static_cast<bool>(((cast<int_t<>>(artifact->provider_count) > cast<int_t<>>(__latency_fn_type_capability_readiness_provider_lookup_index_threshold())) || (cast<int_t<>>(artifact->consumer_count) > cast<int_t<>>(__latency_fn_type_capability_readiness_consumer_lookup_index_threshold()))))) {
		artifact->lookup_policy_status_id = __latency_fn_type_capability_readiness_lookup_policy_index_recommended_id();
	}
	else {
		artifact->lookup_policy_status_id = __latency_fn_type_capability_readiness_lookup_policy_scan_ok_id();
	}
}

}

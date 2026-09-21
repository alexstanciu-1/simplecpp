#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/CapabilityCoverageArtifact.hpp"
#include "__types/CapabilityProviderRow.hpp"
#include "__types/CapabilityReadinessRow.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_invalidate_provider_lookup_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_invalidate_consumer_lookup_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_invalidate_readiness_lookup_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_record_lookup_probe.hpp"
#include "__callable/__latency_fn_type_capability_readiness_update_lookup_policy.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_record_index_lookup_probe.hpp"
#include "__callable/__latency_fn_type_capability_readiness_update_lookup_policy.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_max_provider_type_ref_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_max_consumer_feature_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_max_readiness_feature_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_build_provider_lookup_index_if_recommended.hpp"
#include "__callable/__latency_fn_type_capability_readiness_lookup_policy_index_recommended_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_max_provider_type_ref_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_update_lookup_policy.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_build_consumer_lookup_index_if_recommended.hpp"
#include "__callable/__latency_fn_type_capability_readiness_lookup_policy_index_recommended_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_max_consumer_feature_id.hpp"
#include "__callable/__latency_fn_type_capability_readiness_update_lookup_policy.hpp"
namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
void __latency_fn_type_capability_readiness_invalidate_provider_lookup_index(shared_p<CapabilityCoverageArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::invalidate_provider_lookup_index", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[91]);
	artifact->provider_lookup_index_slot_count = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
void __latency_fn_type_capability_readiness_invalidate_consumer_lookup_index(shared_p<CapabilityCoverageArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::invalidate_consumer_lookup_index", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[92]);
	artifact->consumer_lookup_index_slot_count = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
void __latency_fn_type_capability_readiness_invalidate_readiness_lookup_index(shared_p<CapabilityCoverageArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::invalidate_readiness_lookup_index", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[93]);
	artifact->readiness_lookup_index_slot_count = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
void __latency_fn_type_capability_readiness_record_lookup_probe(shared_p<CapabilityCoverageArtifact> artifact, int_t<std::uint32_t> scannedRows) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::record_lookup_probe", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[94]);
	artifact->lookup_probe_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->lookup_probe_count) + static_cast<int_t<> >(1)));
	artifact->lookup_fallback_scan_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->lookup_fallback_scan_count) + cast<int_t<>>(scannedRows)));
	__latency_fn_type_capability_readiness_update_lookup_policy(artifact);
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
void __latency_fn_type_capability_readiness_record_index_lookup_probe(shared_p<CapabilityCoverageArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::record_index_lookup_probe", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[95]);
	artifact->lookup_probe_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->lookup_probe_count) + static_cast<int_t<> >(1)));
	__latency_fn_type_capability_readiness_update_lookup_policy(artifact);
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_type_capability_readiness_max_provider_type_ref_id(shared_p<CapabilityCoverageArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::max_provider_type_ref_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[96]);
	int_t<> maxId = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = artifact->providers;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((cast<int_t<>>(row->type_ref_id) > maxId))) {
			maxId = cast<int_t<>>(row->type_ref_id);
		}
	}
	return __latency_fn_structure_row_ids_uint32_from_int(maxId);
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_type_capability_readiness_max_consumer_feature_id(shared_p<CapabilityCoverageArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::max_consumer_feature_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[97]);
	int_t<> maxId = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = artifact->consumers;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((cast<int_t<>>(row->feature_id) > maxId))) {
			maxId = cast<int_t<>>(row->feature_id);
		}
	}
	return __latency_fn_structure_row_ids_uint32_from_int(maxId);
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_type_capability_readiness_max_readiness_feature_id(shared_p<CapabilityCoverageArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::max_readiness_feature_id", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[98]);
	int_t<> maxId = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = artifact->readiness;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((cast<int_t<>>(row->consumer_feature_id) > maxId))) {
			maxId = cast<int_t<>>(row->consumer_feature_id);
		}
	}
	return __latency_fn_structure_row_ids_uint32_from_int(maxId);
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
void __latency_fn_type_capability_readiness_build_provider_lookup_index_if_recommended(shared_p<CapabilityCoverageArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::build_provider_lookup_index_if_recommended", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[99]);
	__latency_fn_type_capability_readiness_update_lookup_policy(artifact);
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(artifact->lookup_policy_status_id), cast<int_t<>>(__latency_fn_type_capability_readiness_lookup_policy_index_recommended_id())) || (cast<int_t<>>(artifact->provider_lookup_index_slot_count) > static_cast<int_t<> >(0))))) {
		return;
	}
	int_t<> slotCount = required_cast<int_t<>>(cast<int_t<>>(__latency_fn_type_capability_readiness_max_provider_type_ref_id(artifact)));
	if (static_cast<bool>((slotCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	php::vector_reserve(artifact->provider_row_ids_by_type_ref_id, slotCount);
	while (static_cast<bool>((php::count(artifact->provider_row_ids_by_type_ref_id) < slotCount))) {
		{
		auto __latency_local_0 = __latency_fn_structure_row_ids_none_id();
		(void) artifact->provider_row_ids_by_type_ref_id.append(__latency_local_0);
		}
	}
	int_t<> rowIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_1 = artifact->providers;
	for (auto __latency_local_2 : foreach_range(__latency_local_1)) {
		auto row = __latency_local_2.value_copy();
		if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(row->type_ref_id, slotCount)))) {
			artifact->provider_row_ids_by_type_ref_id[__latency_fn_structure_row_ids_dense_index(row->type_ref_id)] = __latency_fn_structure_row_ids_uint32_from_int((rowIndex + static_cast<int_t<> >(1)));
		}
		rowIndex = (rowIndex + static_cast<int_t<> >(1));
	}
	artifact->provider_lookup_index_slot_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->provider_row_ids_by_type_ref_id));
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
void __latency_fn_type_capability_readiness_build_consumer_lookup_index_if_recommended(shared_p<CapabilityCoverageArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::build_consumer_lookup_index_if_recommended", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[100]);
	__latency_fn_type_capability_readiness_update_lookup_policy(artifact);
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(artifact->lookup_policy_status_id), cast<int_t<>>(__latency_fn_type_capability_readiness_lookup_policy_index_recommended_id())) || (cast<int_t<>>(artifact->consumer_lookup_index_slot_count) > static_cast<int_t<> >(0))))) {
		return;
	}
	int_t<> slotCount = required_cast<int_t<>>(cast<int_t<>>(__latency_fn_type_capability_readiness_max_consumer_feature_id(artifact)));
	if (static_cast<bool>((slotCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	php::vector_reserve(artifact->consumer_row_ids_by_feature_id, slotCount);
	while (static_cast<bool>((php::count(artifact->consumer_row_ids_by_feature_id) < slotCount))) {
		{
		auto __latency_local_0 = __latency_fn_structure_row_ids_none_id();
		(void) artifact->consumer_row_ids_by_feature_id.append(__latency_local_0);
		}
	}
	int_t<> rowIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_1 = artifact->consumers;
	for (auto __latency_local_2 : foreach_range(__latency_local_1)) {
		auto row = __latency_local_2.value_copy();
		int_t<std::uint32_t> featureDenseId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(row->feature_id)));
		if (static_cast<bool>((__latency_fn_structure_row_ids_has_dense_id(featureDenseId, slotCount) && php::identical(cast<int_t<>>(artifact->consumer_row_ids_by_feature_id[__latency_fn_structure_row_ids_dense_index(featureDenseId)]), static_cast<int_t<> >(0))))) {
			artifact->consumer_row_ids_by_feature_id[__latency_fn_structure_row_ids_dense_index(featureDenseId)] = __latency_fn_structure_row_ids_uint32_from_int((rowIndex + static_cast<int_t<> >(1)));
		}
		rowIndex = (rowIndex + static_cast<int_t<> >(1));
	}
	artifact->consumer_lookup_index_slot_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->consumer_row_ids_by_feature_id));
}

}

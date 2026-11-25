#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/CapabilityProviderRow.hpp"
#include "__types/CapabilityReadinessRow.hpp"
#include "__types/CapabilityRegistryRow.hpp"
namespace scpp {
class CapabilityCoverageArtifact {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<std::uint16_t> artifact_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> schema_version = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint32_t> capability_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> provider_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> consumer_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> readiness_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> blocked_consumer_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> lookup_probe_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> lookup_fallback_scan_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint16_t> lookup_policy_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> provider_lookup_index_slot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> consumer_lookup_index_slot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> readiness_lookup_index_slot_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	vector_t<CapabilityRegistryRow> capabilities = vector_t<CapabilityRegistryRow>{};
	vector_t<CapabilityProviderRow> providers = vector_t<CapabilityProviderRow>{};
	vector_t<CapabilityConsumerRow> consumers = vector_t<CapabilityConsumerRow>{};
	vector_t<CapabilityReadinessRow> readiness = vector_t<CapabilityReadinessRow>{};
	vector_t<int_t<std::uint32_t>> provider_row_ids_by_type_ref_id = vector_t<int_t<std::uint32_t>>{};
	vector_t<int_t<std::uint32_t>> consumer_row_ids_by_feature_id = vector_t<int_t<std::uint32_t>>{};
	vector_t<int_t<std::uint32_t>> readiness_row_ids_by_feature_id = vector_t<int_t<std::uint32_t>>{};
};
}

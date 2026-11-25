#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/ContractConsumerRouteDescriptorRow.hpp"
#include "__types/ContractConsumerRow.hpp"
#include "__types/ContractProviderRouteDescriptorRow.hpp"
#include "__types/ContractProviderRow.hpp"
#include "__types/ContractRegistryRow.hpp"
namespace scpp {
class ContractCoverageArtifact {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> artifact_kind_id = static_cast<int_t<> >(1);
	int_t<> schema_version = static_cast<int_t<> >(1);
	int_t<> provider_route_model_id = static_cast<int_t<> >(1);
	int_t<> consumer_route_model_id = static_cast<int_t<> >(1);
	int_t<> contract_count = static_cast<int_t<> >(0);
	int_t<> provider_route_count = static_cast<int_t<> >(0);
	int_t<> consumer_route_count = static_cast<int_t<> >(0);
	int_t<> provider_count = static_cast<int_t<> >(0);
	int_t<> consumer_count = static_cast<int_t<> >(0);
	int_t<> blocked_consumer_count = static_cast<int_t<> >(0);
	int_t<> non_gating_blocked_consumer_count = static_cast<int_t<> >(0);
	vector_t<ContractRegistryRow> contracts = vector_t<ContractRegistryRow>{};
	vector_t<ContractProviderRouteDescriptorRow> provider_routes = vector_t<ContractProviderRouteDescriptorRow>{};
	vector_t<ContractConsumerRouteDescriptorRow> consumer_routes = vector_t<ContractConsumerRouteDescriptorRow>{};
	vector_t<ContractProviderRow> providers = vector_t<ContractProviderRow>{};
	vector_t<ContractConsumerRow> consumers = vector_t<ContractConsumerRow>{};
};
}

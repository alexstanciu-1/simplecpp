#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct CapabilityConsumerRow;
struct CapabilityProviderRow;
struct OperationReadiness;
struct StorageLifetimeRequestRow;
StorageLifetimeRequestRow __latency_fn_storage_lifetime_readiness_request_from_operation(int_t<std::uint32_t> requestId, OperationReadiness operation, CapabilityConsumerRow consumer, CapabilityProviderRow provider);
}

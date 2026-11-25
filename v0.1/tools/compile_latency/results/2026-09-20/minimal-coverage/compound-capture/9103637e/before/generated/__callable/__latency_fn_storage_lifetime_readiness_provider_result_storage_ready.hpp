#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct CapabilityProviderRow;
struct OperationReadiness;
bool_t __latency_fn_storage_lifetime_readiness_provider_result_storage_ready(OperationReadiness operation, CapabilityProviderRow provider);
}

#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendRequestAuthorizationArtifact;
struct CapabilityConsumerRow;
struct CapabilityProviderRow;
struct CapabilityReadinessRow;
struct ProjectSymbolIndexRow;
void __latency_fn_local_body_lowering_append_local_backend_request_from_capability_rows(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, CapabilityConsumerRow consumer, CapabilityReadinessRow readiness, CapabilityProviderRow provider, int_t<std::uint32_t> sourceRowId, int_t<std::uint32_t> localSourceRowId, int_t<std::uint32_t> valueSourceRowId, int_t<std::int32_t> value, int_t<std::uint16_t> localOperationId, ProjectSymbolIndexRow entrySymbol);
}

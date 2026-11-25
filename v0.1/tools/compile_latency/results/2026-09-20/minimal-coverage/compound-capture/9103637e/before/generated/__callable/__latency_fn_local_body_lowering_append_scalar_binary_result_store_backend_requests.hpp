#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendRequestAuthorizationArtifact;
class CapabilityCoverageArtifact;
struct ProjectSymbolIndexRow;
void __latency_fn_local_body_lowering_append_scalar_binary_result_store_backend_requests(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, int_t<std::uint16_t> featureId, int_t<std::uint32_t> binarySourceRowId, int_t<std::uint32_t> targetLocalSourceRowId, int_t<std::uint32_t> targetTypeRefId, int_t<std::uint32_t> leftLocalSourceRowId, int_t<std::uint32_t> rightValueSourceRowId, int_t<std::int32_t> rightValue, int_t<std::uint16_t> localOperationId, ProjectSymbolIndexRow entrySymbol);
}

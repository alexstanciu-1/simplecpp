#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendRequestAuthorizationArtifact;
class CapabilityCoverageArtifact;
struct ProjectSymbolIndexRow;
void __latency_fn_local_body_lowering_append_local_load_return_backend_request(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, int_t<std::uint32_t> returnSourceRowId, int_t<std::uint32_t> localSourceRowId, int_t<std::uint32_t> valueSourceRowId, int_t<std::uint32_t> typeRefId, ProjectSymbolIndexRow entrySymbol);
}

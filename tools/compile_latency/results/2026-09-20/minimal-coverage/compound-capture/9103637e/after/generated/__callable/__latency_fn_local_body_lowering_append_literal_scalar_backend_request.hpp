#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendRequestAuthorizationArtifact;
class CapabilityCoverageArtifact;
struct FrontendLiteralPayloadRow;
struct FrontendNodeRow;
struct ProjectSymbolIndexRow;
void __latency_fn_local_body_lowering_append_literal_scalar_backend_request(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, FrontendNodeRow literalNode, FrontendLiteralPayloadRow literal, ProjectSymbolIndexRow entrySymbol);
}

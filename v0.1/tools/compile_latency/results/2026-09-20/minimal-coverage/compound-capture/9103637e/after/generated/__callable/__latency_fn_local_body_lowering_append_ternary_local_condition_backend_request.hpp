#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendRequestAuthorizationArtifact;
class CapabilityCoverageArtifact;
struct FrontendLiteralPayloadRow;
struct FrontendNodeRow;
struct ProjectSymbolIndexRow;
void __latency_fn_local_body_lowering_append_ternary_local_condition_backend_request(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, FrontendNodeRow ternaryNode, FrontendNodeRow conditionNode, FrontendNodeRow thenNode, FrontendLiteralPayloadRow thenLiteral, FrontendNodeRow elseNode, FrontendLiteralPayloadRow elseLiteral, int_t<std::uint32_t> conditionTypeRefId, int_t<std::uint32_t> conditionLocalSourceRowId, ProjectSymbolIndexRow entrySymbol);
}

#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendRequestAuthorizationArtifact;
class CapabilityCoverageArtifact;
struct FrontendLiteralPayloadRow;
struct FrontendNodeRow;
struct ProjectSymbolIndexRow;
void __latency_fn_local_body_lowering_append_ternary_local_binary_condition_backend_request(shared_p<BackendRequestAuthorizationArtifact>& backendRequests, shared_p<CapabilityCoverageArtifact> capabilityCoverage, FrontendNodeRow ternaryNode, FrontendNodeRow conditionBinaryNode, FrontendNodeRow thenNode, FrontendLiteralPayloadRow thenLiteral, FrontendNodeRow elseNode, FrontendLiteralPayloadRow elseLiteral, int_t<std::uint32_t> leftLocalSourceRowId, int_t<std::uint32_t> rightLocalSourceRowId, int_t<std::uint32_t> conditionProviderTypeRefId, int_t<std::uint32_t> conditionResultTypeRefId, int_t<std::uint16_t> conditionLocalOperationId, ProjectSymbolIndexRow entrySymbol);
}

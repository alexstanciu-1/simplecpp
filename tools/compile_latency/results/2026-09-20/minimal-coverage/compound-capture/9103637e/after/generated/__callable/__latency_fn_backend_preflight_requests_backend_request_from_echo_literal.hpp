#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendRequestAuthorizationRow;
struct FrontendLiteralPayloadRow;
struct FrontendNodeRow;
struct OperationReadiness;
struct ProjectSymbolIndexRow;
struct StorageLifetimeRequestRow;
BackendRequestAuthorizationRow __latency_fn_backend_preflight_requests_backend_request_from_echo_literal(int_t<std::uint32_t> requestId, OperationReadiness operation, StorageLifetimeRequestRow storage, FrontendNodeRow echoNode, FrontendLiteralPayloadRow literal, ProjectSymbolIndexRow ownerSymbol);
}

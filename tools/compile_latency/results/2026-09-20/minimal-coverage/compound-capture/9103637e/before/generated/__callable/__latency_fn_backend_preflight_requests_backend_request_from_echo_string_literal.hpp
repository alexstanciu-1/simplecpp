#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendRequestAuthorizationRow;
struct FrontendNodeRow;
struct OperationReadiness;
struct ProjectSymbolIndexRow;
struct StorageLifetimeRequestRow;
BackendRequestAuthorizationRow __latency_fn_backend_preflight_requests_backend_request_from_echo_string_literal(int_t<std::uint32_t> requestId, OperationReadiness operation, StorageLifetimeRequestRow storage, FrontendNodeRow echoNode, ProjectSymbolIndexRow ownerSymbol);
}

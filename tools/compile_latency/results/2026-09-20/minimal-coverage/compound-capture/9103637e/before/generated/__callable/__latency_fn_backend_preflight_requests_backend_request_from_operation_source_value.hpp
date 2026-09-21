#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendRequestAuthorizationRow;
struct OperationReadiness;
struct ProjectSymbolIndexRow;
struct StorageLifetimeRequestRow;
BackendRequestAuthorizationRow __latency_fn_backend_preflight_requests_backend_request_from_operation_source_value(int_t<std::uint32_t> requestId, OperationReadiness operation, StorageLifetimeRequestRow storage, int_t<std::uint32_t> sourceRowId, int_t<std::int32_t> value, ProjectSymbolIndexRow ownerSymbol);
}

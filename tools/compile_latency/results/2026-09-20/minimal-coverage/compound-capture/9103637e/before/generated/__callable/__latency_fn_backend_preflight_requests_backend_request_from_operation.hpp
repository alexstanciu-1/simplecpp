#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendRequestAuthorizationRow;
struct OperationReadiness;
struct ProjectCallableContractRow;
struct StorageLifetimeRequestRow;
BackendRequestAuthorizationRow __latency_fn_backend_preflight_requests_backend_request_from_operation(int_t<std::uint32_t> requestId, OperationReadiness operation, StorageLifetimeRequestRow storage, ProjectCallableContractRow contract);
}

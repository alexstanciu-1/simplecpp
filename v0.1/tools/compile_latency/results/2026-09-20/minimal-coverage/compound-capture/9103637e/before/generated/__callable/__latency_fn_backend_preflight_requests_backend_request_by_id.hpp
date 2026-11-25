#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendRequestAuthorizationArtifact;
struct BackendRequestAuthorizationRow;
BackendRequestAuthorizationRow __latency_fn_backend_preflight_requests_backend_request_by_id(shared_p<BackendRequestAuthorizationArtifact>& artifact, int_t<std::uint32_t> requestId);
}

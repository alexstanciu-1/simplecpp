#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendRequestAuthorizationArtifact;
struct BackendRequestAuthorizationRow;
void __latency_fn_backend_preflight_requests_append_backend_request(shared_p<BackendRequestAuthorizationArtifact> artifact, BackendRequestAuthorizationRow row);
}

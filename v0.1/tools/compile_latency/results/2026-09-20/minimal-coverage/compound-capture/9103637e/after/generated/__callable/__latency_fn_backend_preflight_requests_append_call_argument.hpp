#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendCallArgumentRow;
class BackendRequestAuthorizationArtifact;
void __latency_fn_backend_preflight_requests_append_call_argument(shared_p<BackendRequestAuthorizationArtifact> artifact, BackendCallArgumentRow row);
}

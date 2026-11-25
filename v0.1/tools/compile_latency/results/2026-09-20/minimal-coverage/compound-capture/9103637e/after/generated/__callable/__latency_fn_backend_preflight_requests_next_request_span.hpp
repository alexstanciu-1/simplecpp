#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendRequestAuthorizationArtifact;
struct BackendRequestRowSpan;
BackendRequestRowSpan __latency_fn_backend_preflight_requests_next_request_span(shared_p<BackendRequestAuthorizationArtifact> artifact, BackendRequestRowSpan span);
}

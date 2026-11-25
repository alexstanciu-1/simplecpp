#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendRequestAuthorizationArtifact;
struct BackendRequestAuthorizationRow;
struct BackendRequestRowSpan;
BackendRequestAuthorizationRow __latency_fn_backend_preflight_requests_span_request_at(shared_p<BackendRequestAuthorizationArtifact> artifact, BackendRequestRowSpan span, int_t<> offset);
}

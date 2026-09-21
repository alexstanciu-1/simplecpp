#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendLocalOperandRow;
class BackendRequestAuthorizationArtifact;
void __latency_fn_backend_preflight_requests_append_local_operand(shared_p<BackendRequestAuthorizationArtifact> artifact, BackendLocalOperandRow row);
}

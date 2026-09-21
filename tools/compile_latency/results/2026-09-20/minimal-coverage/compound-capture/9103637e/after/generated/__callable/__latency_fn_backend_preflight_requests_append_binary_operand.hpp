#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendBinaryOperandRow;
class BackendRequestAuthorizationArtifact;
void __latency_fn_backend_preflight_requests_append_binary_operand(shared_p<BackendRequestAuthorizationArtifact> artifact, BackendBinaryOperandRow row);
}

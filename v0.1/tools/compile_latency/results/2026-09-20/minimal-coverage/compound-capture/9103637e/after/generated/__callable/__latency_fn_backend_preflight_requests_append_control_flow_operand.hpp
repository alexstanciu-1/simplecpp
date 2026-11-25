#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendControlFlowOperandRow;
class BackendRequestAuthorizationArtifact;
void __latency_fn_backend_preflight_requests_append_control_flow_operand(shared_p<BackendRequestAuthorizationArtifact> artifact, BackendControlFlowOperandRow row);
}

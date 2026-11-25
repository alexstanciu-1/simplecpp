#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendLocalImmediateTextOperandRow;
class BackendRequestAuthorizationArtifact;
void __latency_fn_backend_preflight_requests_append_local_immediate_text_operand(shared_p<BackendRequestAuthorizationArtifact> artifact, shared_p<BackendLocalImmediateTextOperandRow> row);
}

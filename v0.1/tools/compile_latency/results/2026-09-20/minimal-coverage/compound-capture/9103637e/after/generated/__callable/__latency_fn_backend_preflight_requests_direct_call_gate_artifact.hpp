#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendRequestAuthorizationRow;
struct CallableAbiReadinessRow;
struct DirectCallAuthorizationGateArtifact;
DirectCallAuthorizationGateArtifact __latency_fn_backend_preflight_requests_direct_call_gate_artifact(CallableAbiReadinessRow abi, BackendRequestAuthorizationRow request);
}

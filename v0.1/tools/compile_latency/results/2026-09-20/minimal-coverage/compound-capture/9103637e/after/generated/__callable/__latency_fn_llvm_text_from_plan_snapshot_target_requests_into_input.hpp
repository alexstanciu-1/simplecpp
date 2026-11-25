#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendRequestAuthorizationArtifact;
class EmissionLLVMWorkerInput;
void __latency_fn_llvm_text_from_plan_snapshot_target_requests_into_input(shared_p<EmissionLLVMWorkerInput>& input, shared_p<BackendRequestAuthorizationArtifact>& requests);
}

#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendLoweringWorkerInput;
class BackendLoweringWorkerResult;
class BackendRequestAuthorizationArtifact;
class CompilerProjectRunReport;
class LoweringPlan;
void __latency_fn_lowering_plan_record_backend_lowering_worker_handoff_metrics(shared_p<CompilerProjectRunReport>& report, const vector_t<shared_p<BackendLoweringWorkerInput>>& inputs, const vector_t<shared_p<BackendLoweringWorkerResult>>& results, shared_p<BackendRequestAuthorizationArtifact> requests, shared_p<LoweringPlan> plan, int_t<std::uint32_t> coordinatorSemanticHash, bool_t matches);
}

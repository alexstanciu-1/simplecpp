#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendLoweringWorkerInput;
class BackendLoweringWorkerResult;
vector_t<shared_p<BackendLoweringWorkerResult>> __latency_fn_lowering_plan_worker_results(const vector_t<shared_p<BackendLoweringWorkerInput>>& inputs, int_t<> workerCount);
}

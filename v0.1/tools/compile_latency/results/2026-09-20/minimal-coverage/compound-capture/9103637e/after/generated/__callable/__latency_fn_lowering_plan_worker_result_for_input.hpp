#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendLoweringWorkerInput;
class BackendLoweringWorkerResult;
shared_p<BackendLoweringWorkerResult> __latency_fn_lowering_plan_worker_result_for_input(shared_p<BackendLoweringWorkerInput> input);
}

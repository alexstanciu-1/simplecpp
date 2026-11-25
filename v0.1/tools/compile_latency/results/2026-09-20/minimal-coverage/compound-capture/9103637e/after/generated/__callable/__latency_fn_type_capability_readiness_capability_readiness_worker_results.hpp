#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CapabilityReadinessWorkerInput;
class CapabilityReadinessWorkerResult;
vector_t<shared_p<CapabilityReadinessWorkerResult>> __latency_fn_type_capability_readiness_capability_readiness_worker_results(const vector_t<shared_p<CapabilityReadinessWorkerInput>>& inputs, int_t<> workerCount);
}

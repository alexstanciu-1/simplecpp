#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class StorageLifetimeWorkerInput;
class StorageLifetimeWorkerResult;
vector_t<shared_p<StorageLifetimeWorkerResult>> __latency_fn_storage_lifetime_readiness_worker_results(const vector_t<shared_p<StorageLifetimeWorkerInput>>& inputs, int_t<> workerCount);
}

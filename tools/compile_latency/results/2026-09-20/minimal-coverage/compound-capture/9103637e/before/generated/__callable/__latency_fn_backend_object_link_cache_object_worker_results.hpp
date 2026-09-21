#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ObjectOutputWorkerInput;
class ObjectOutputWorkerResult;
vector_t<shared_p<ObjectOutputWorkerResult>> __latency_fn_backend_object_link_cache_object_worker_results(const vector_t<shared_p<ObjectOutputWorkerInput>>& inputs, int_t<> workerCount);
}

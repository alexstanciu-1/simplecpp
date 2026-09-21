#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendPartitionExecutionRow;
class ObjectOutputWorkerInput;
shared_p<ObjectOutputWorkerInput> __latency_fn_backend_object_link_cache_object_worker_input_from_partition(int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> cacheObjectId, BackendPartitionExecutionRow partition);
}

#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendObjectCacheDecisionRow;
struct BackendPartitionExecutionRow;
BackendObjectCacheDecisionRow __latency_fn_backend_object_link_cache_object_row_from_partition(int_t<std::uint32_t> cacheObjectId, BackendPartitionExecutionRow partition);
}

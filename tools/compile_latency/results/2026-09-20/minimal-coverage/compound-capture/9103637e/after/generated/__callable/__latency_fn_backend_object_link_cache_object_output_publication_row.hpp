#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendObjectCacheDecisionRow;
struct PartitionReadinessRow;
PartitionReadinessRow __latency_fn_backend_object_link_cache_object_output_publication_row(int_t<std::uint32_t> ownerRunId, BackendObjectCacheDecisionRow objectRow);
}

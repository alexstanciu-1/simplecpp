#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendLinkCacheDecisionRow;
struct PartitionReadinessRow;
PartitionReadinessRow __latency_fn_backend_object_link_cache_link_coordinator_publication_row(int_t<std::uint32_t> ownerRunId, BackendLinkCacheDecisionRow linkRow);
}

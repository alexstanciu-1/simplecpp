#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendLinkCacheDecisionRow;
class BackendObjectLinkCacheArtifact;
struct BackendProjectLinkExecutionRow;
BackendLinkCacheDecisionRow __latency_fn_backend_object_link_cache_link_row_from_execution(int_t<std::uint32_t> cacheLinkId, BackendProjectLinkExecutionRow link, shared_p<BackendObjectLinkCacheArtifact> artifact);
}

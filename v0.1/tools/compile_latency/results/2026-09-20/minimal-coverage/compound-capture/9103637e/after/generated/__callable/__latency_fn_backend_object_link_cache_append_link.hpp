#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendLinkCacheDecisionRow;
class BackendObjectLinkCacheArtifact;
void __latency_fn_backend_object_link_cache_append_link(shared_p<BackendObjectLinkCacheArtifact>& artifact, BackendLinkCacheDecisionRow row);
}

#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendObjectCacheDecisionRow;
class BackendObjectLinkCacheArtifact;
void __latency_fn_backend_object_link_cache_append_object(shared_p<BackendObjectLinkCacheArtifact>& artifact, BackendObjectCacheDecisionRow row);
}

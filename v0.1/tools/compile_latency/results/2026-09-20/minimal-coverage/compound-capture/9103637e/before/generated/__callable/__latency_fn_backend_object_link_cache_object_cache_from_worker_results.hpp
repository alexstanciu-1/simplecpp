#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendObjectLinkCacheArtifact;
class ObjectOutputWorkerResult;
shared_p<BackendObjectLinkCacheArtifact> __latency_fn_backend_object_link_cache_object_cache_from_worker_results(const vector_t<shared_p<ObjectOutputWorkerResult>>& results);
}

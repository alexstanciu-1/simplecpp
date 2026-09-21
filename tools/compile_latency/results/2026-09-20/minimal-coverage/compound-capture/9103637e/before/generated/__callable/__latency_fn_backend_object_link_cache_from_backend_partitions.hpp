#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendObjectLinkCacheArtifact;
class BackendPartitionExecutionArtifact;
shared_p<BackendObjectLinkCacheArtifact> __latency_fn_backend_object_link_cache_from_backend_partitions(shared_p<BackendPartitionExecutionArtifact> partitions);
}

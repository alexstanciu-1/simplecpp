#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendObjectLinkCacheArtifact;
class PartitionReadinessArtifact;
shared_p<PartitionReadinessArtifact> __latency_fn_backend_object_link_cache_link_coordinator_publication_artifact_from_cache(shared_p<BackendObjectLinkCacheArtifact> cache, int_t<std::uint32_t> ownerRunId);
}

#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class PartitionReadinessArtifact;
struct ProjectSymbolIndexRow;
struct StorageLifetimeRequestRow;
shared_p<PartitionReadinessArtifact> __latency_fn_storage_lifetime_readiness_storage_lifetime_publication_artifact(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, StorageLifetimeRequestRow readyRow, StorageLifetimeRequestRow blockedRow);
}

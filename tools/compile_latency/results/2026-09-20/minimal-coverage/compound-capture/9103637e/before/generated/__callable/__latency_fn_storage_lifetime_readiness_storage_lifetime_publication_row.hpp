#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct PartitionReadinessRow;
struct ProjectSymbolIndexRow;
struct StorageLifetimeRequestRow;
PartitionReadinessRow __latency_fn_storage_lifetime_readiness_storage_lifetime_publication_row(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, StorageLifetimeRequestRow readyRow, StorageLifetimeRequestRow blockedRow);
}

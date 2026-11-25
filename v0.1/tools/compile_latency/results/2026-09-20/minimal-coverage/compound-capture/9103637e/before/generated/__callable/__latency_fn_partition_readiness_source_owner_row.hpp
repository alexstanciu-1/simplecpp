#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct PartitionReadinessRow;
struct ResidentSourceUnitSnapshotRow;
PartitionReadinessRow __latency_fn_partition_readiness_source_owner_row(int_t<std::uint32_t> rowId, ResidentSourceUnitSnapshotRow snapshot);
}

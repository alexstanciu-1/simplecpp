#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct PartitionReadinessRow;
PartitionReadinessRow __latency_fn_partition_readiness_row(int_t<std::uint32_t> rowId, int_t<std::uint32_t> ownerRunId, int_t<std::uint16_t> ownerKindId, int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> symbolId, int_t<std::uint32_t> ownerRowId, int_t<std::uint16_t> statusId, int_t<std::uint16_t> blockedReasonId);
}

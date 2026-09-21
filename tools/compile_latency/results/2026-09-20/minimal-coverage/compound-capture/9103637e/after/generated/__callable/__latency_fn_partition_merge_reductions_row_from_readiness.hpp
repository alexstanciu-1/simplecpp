#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct PartitionMergeReductionRow;
struct PartitionReadinessRow;
PartitionMergeReductionRow __latency_fn_partition_merge_reductions_row_from_readiness(PartitionReadinessRow inputRow, int_t<std::uint32_t> inputOrderId, int_t<std::uint32_t> reductionOrderId, int_t<std::uint32_t> outputOrderId);
}

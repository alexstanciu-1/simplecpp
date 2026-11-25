#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class PartitionMergeReductionArtifact;
class PartitionReadinessArtifact;
void __latency_fn_partition_merge_reductions_append_status_rows_or_reduce(shared_p<PartitionMergeReductionArtifact>& artifact, shared_p<PartitionReadinessArtifact> input, int_t<std::uint16_t> statusId, vector_t<int_t<std::uint32_t>>& inputOrderByRowId, int_t<std::uint32_t>& outputOrderId);
}

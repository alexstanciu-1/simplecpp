#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class PartitionMergeReductionArtifact;
struct PartitionReadinessRow;
void __latency_fn_partition_merge_reductions_append_sorted_status_rows(shared_p<PartitionMergeReductionArtifact>& artifact, vector_t<PartitionReadinessRow>& rows, vector_t<int_t<std::uint32_t>>& inputOrderByRowId, int_t<std::uint32_t>& outputOrderId);
}

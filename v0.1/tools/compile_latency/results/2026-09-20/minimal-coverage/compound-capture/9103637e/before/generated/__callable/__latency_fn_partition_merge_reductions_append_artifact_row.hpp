#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class PartitionMergeReductionArtifact;
struct PartitionMergeReductionRow;
void __latency_fn_partition_merge_reductions_append_artifact_row(shared_p<PartitionMergeReductionArtifact>& artifact, PartitionMergeReductionRow row);
}

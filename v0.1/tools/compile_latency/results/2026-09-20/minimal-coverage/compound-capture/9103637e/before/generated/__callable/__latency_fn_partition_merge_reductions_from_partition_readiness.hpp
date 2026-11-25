#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class PartitionMergeReductionArtifact;
class PartitionReadinessArtifact;
shared_p<PartitionMergeReductionArtifact> __latency_fn_partition_merge_reductions_from_partition_readiness(shared_p<PartitionReadinessArtifact> input);
}

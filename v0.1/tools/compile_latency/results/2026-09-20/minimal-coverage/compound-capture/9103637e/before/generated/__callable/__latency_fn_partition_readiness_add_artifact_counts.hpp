#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class PartitionReadinessArtifact;
struct PartitionReadinessRow;
void __latency_fn_partition_readiness_add_artifact_counts(shared_p<PartitionReadinessArtifact>& artifact, PartitionReadinessRow row);
}

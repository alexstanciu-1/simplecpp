#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class PartitionReadinessArtifact;
struct PartitionReadinessRow;
void __latency_fn_partition_readiness_append_artifact_row(shared_p<PartitionReadinessArtifact>& artifact, PartitionReadinessRow row);
}

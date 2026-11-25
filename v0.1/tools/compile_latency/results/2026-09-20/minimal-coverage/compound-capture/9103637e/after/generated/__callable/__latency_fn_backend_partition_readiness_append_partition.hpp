#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendPartitionExecutionArtifact;
struct BackendPartitionExecutionRow;
void __latency_fn_backend_partition_readiness_append_partition(shared_p<BackendPartitionExecutionArtifact>& artifact, BackendPartitionExecutionRow row);
}

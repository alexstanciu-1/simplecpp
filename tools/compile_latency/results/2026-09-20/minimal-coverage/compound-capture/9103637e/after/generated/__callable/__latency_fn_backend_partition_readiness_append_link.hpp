#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendPartitionExecutionArtifact;
struct BackendProjectLinkExecutionRow;
void __latency_fn_backend_partition_readiness_append_link(shared_p<BackendPartitionExecutionArtifact>& artifact, BackendProjectLinkExecutionRow row);
}

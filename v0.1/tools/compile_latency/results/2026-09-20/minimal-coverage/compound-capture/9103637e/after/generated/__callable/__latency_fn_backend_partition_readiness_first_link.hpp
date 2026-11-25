#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendPartitionExecutionArtifact;
struct BackendProjectLinkExecutionRow;
BackendProjectLinkExecutionRow __latency_fn_backend_partition_readiness_first_link(shared_p<BackendPartitionExecutionArtifact> artifact);
}

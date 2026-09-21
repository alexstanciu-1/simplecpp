#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendPartitionExecutionArtifact;
struct BackendProjectLinkExecutionRow;
BackendProjectLinkExecutionRow __latency_fn_backend_partition_readiness_link_row_from_partitions(shared_p<BackendPartitionExecutionArtifact> artifact, int_t<std::uint16_t> linkExecutionId);
}

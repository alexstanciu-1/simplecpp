#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendPartitionExecutionArtifact;
struct BackendPartitionExecutionRow;
BackendPartitionExecutionRow __latency_fn_backend_partition_readiness_partition_by_execution_id(shared_p<BackendPartitionExecutionArtifact> artifact, int_t<std::uint32_t> executionId);
}

#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendPartitionExecutionArtifact;
class ObjectOutputWorkerInput;
vector_t<shared_p<ObjectOutputWorkerInput>> __latency_fn_backend_object_link_cache_object_worker_inputs_from_partitions(int_t<std::uint32_t> ownerRunId, shared_p<BackendPartitionExecutionArtifact> partitions);
}
